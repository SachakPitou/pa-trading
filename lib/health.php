<?php
// Scanner Health & Data Monitoring: every real scan is logged, and health_report() turns the log + feed cache into a status.

/** Log one scanner run. Never throws. Off-hours "idle" runs are not logged (they would only add noise). */
function scan_log_write(array $r, int $ms): void {
  try {
    if (!empty($r['skipped']) && strpos((string)$r['skipped'], 'outside trading hours') === 0) return;
    $out = isset($r['error']) ? 'error' : (!empty($r['new']) ? 'signal' : 'ok');
    $detail = $r['error'] ?? $r['skipped'] ?? null;
    if (!empty($r['new'])) $detail = 'New: '.implode(', ', array_keys($r['new']));
    if ($out === 'error' && !empty($GLOBALS['feed_error']) && strpos((string)$detail, (string)$GLOBALS['feed_error']) === false) $detail .= ' - '.$GLOBALS['feed_error'];
    $ls = cache_get('last_scan', 600) ?: [];
    db()->prepare('INSERT INTO scan_log(outcome,detail,candle_time,api_used,ms,new_signals) VALUES(?,?,?,?,?,?)')
       ->execute([$out, $detail === null ? null : mb_substr((string)$detail, 0, 250), $ls['candle'] ?? null, api_used(), $ms, count($r['new'] ?? [])]);
    db()->exec("DELETE FROM scan_log WHERE at < NOW() - INTERVAL 14 DAY");
  } catch (Throwable $e) { /* logging must never break the scanner */ }
}

/** True when the scanner is supposed to be fetching data right now (weekday + inside the trading window). */
function expected_active(): bool { $o = strat(); return (int)gmdate('N') < 6 && in_session((int)gmdate('G'), $o['sess_from'], $o['sess_to']); }

function health_report(): array {
  $now = time(); $issues = []; $status = 'ok'; $bump = function(string $lvl, string $msg) use (&$issues, &$status) {
    $issues[] = ['level'=>$lvl, 'msg'=>$msg]; if ($lvl === 'down' || $status === 'ok') $status = $lvl === 'down' ? 'down' : 'warn'; };
  $iv = max(1, (int)cfg('interval')); $active = expected_active();

  $t0 = microtime(true); db()->query('SELECT 1')->fetchColumn(); $dbms = round((microtime(true)-$t0)*1000, 1);
  $cr = db()->query('SELECT COUNT(*) n, MAX(updated) u FROM cache')->fetch();
  $ls = cache_get('last_scan', 10**9) ?: [];
  $hb = $ls['at'] ?? null; $candle = $ls['candle'] ?? null;
  $candleAge = $candle ? (int)round(($now - strtotime($candle.' UTC'))/60) : null;
  $lastOk = db()->query("SELECT UNIX_TIMESTAMP(at) FROM scan_log WHERE outcome<>'error' ORDER BY id DESC LIMIT 1")->fetchColumn();

  $day = strtotime('today 00:00 UTC');
  $e = db()->prepare("SELECT COUNT(*) FROM scan_log WHERE outcome='error' AND at >= FROM_UNIXTIME(?)"); $e->execute([$day]); $errToday = (int)$e->fetchColumn();
  $r = db()->prepare("SELECT COUNT(*) FROM scan_log WHERE at >= FROM_UNIXTIME(?)"); $r->execute([$day]); $runsToday = (int)$r->fetchColumn();
  $lastErr = db()->query("SELECT UNIX_TIMESTAMP(at) ts, detail FROM scan_log WHERE outcome='error' ORDER BY id DESC LIMIT 1")->fetch() ?: null;
  $hist = db()->query('SELECT UNIX_TIMESTAMP(at) ts, outcome, detail, candle_time, api_used, ms FROM scan_log ORDER BY id DESC LIMIT 20')->fetchAll();

  // missing candles: holes inside the last 100 candles (breaks longer than 1h are market closures, not holes)
  $c = array_slice(get_candles(500), -100); $gaps = [];
  for ($k = 1; $k < count($c); $k++) {
    $d = strtotime($c[$k]['t'].' UTC') - strtotime($c[$k-1]['t'].' UTC');
    if ($d > $iv*60*1.5 && $d <= 3600) $gaps[] = ['after'=>$c[$k-1]['t'], 'missing'=>(int)round($d/60/$iv)-1];
  }
  $used = api_used(); $budget = (int)(cfg('daily_budget') ?? 600); $left = max(0, $budget - $used);

  if ($hb === null) $bump('warn', 'The scanner has not run yet');
  elseif ($active && $now - $hb > 600) $bump('down', 'Scanner not running: last run '.round(($now-$hb)/60).' min ago');
  if ($lastOk === false && $hb !== null && $active) $bump('warn', 'No successful scan in the log yet');
  if (!empty($ls['error'])) $bump('warn', 'Last scan failed: '.$ls['error']);
  if ($active && $candleAge !== null && $candleAge > $iv*3) $bump('warn', "Latest candle is $candleAge min old (data feed may be stale)");
  if ($gaps) $bump('warn', count($gaps).' missing-candle gap(s) in the last 100 candles');
  if ($left <= $budget*0.1) $bump($left == 0 ? 'down' : 'warn', "API quota almost used: $left of $budget left today");
  if ($errToday >= 3) $bump('warn', "$errToday scanner errors today");
  if ($dbms > 500) $bump('warn', "Database is slow ($dbms ms)");

  return ['status'=>$status, 'issues'=>$issues, 'expected_active'=>$active,
    'scanner'=>['heartbeat'=>$hb, 'last_ok'=>$lastOk ? (int)$lastOk : null, 'last_candle'=>$candle, 'candle_age_min'=>$candleAge, 'interval_min'=>$iv, 'runs_today'=>$runsToday],
    'feed'=>['errors_today'=>$errToday, 'last_error'=>$lastErr, 'gaps'=>array_slice($gaps, -5)],
    'quota'=>['used'=>$used, 'budget'=>$budget, 'remaining'=>$left],
    'system'=>['db_ms'=>$dbms, 'cache_rows'=>(int)$cr['n'], 'cache_age_min'=>$cr['u'] ? (int)round(($now-(int)$cr['u'])/60) : null, 'now'=>$now],
    'history'=>$hist];
}