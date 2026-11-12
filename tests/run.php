<?php
// php tests/run.php   -> offline checks: no network, no real API credits.
// DB checks only run when PA_TESTS_DB=1 (docker-compose and CI set it). NEVER set it against the production database.
error_reporting(E_ALL);
set_error_handler(function ($no, $str, $file, $line) { throw new ErrorException($str, 0, $no, $file, $line); });   // warnings AND deprecations fail the test run

$root = dirname(__DIR__);
if (!is_file($root . '/config.php')) { fwrite(STDERR, "config.php missing. Run: cp config.example.php config.php\n"); exit(2); }
require $root . '/lib/bootstrap.php';

$fail = 0; $pass = 0;
function check(string $name, bool $ok, string $detail = ''): void {
  global $fail, $pass; $ok ? $pass++ : $fail++;
  echo ($ok ? "  ok    " : "  FAIL  ") . $name . ($ok || $detail === '' ? '' : "  -> $detail") . "\n";
}
function section(string $t): void { echo "\n$t\n"; }
function with_strategy(array $s, callable $fn) { $old = $GLOBALS['cfg']['strategy'] ?? null; $GLOBALS['cfg']['strategy'] = $s; try { return $fn(); } finally { $GLOBALS['cfg']['strategy'] = $old; } }

section('Trading window');
check('in_session 07-21 edges', !in_session(6,7,21) && in_session(7,7,21) && in_session(20,7,21) && !in_session(21,7,21));
check('in_session wraps midnight (21-07)', in_session(22,21,7) && in_session(3,21,7) && !in_session(8,21,7));
check('in_session from==to means 24h', in_session(0,0,0) && in_session(13,5,5) && in_session(23,0,24));
$w = with_strategy(['local_from'=>7,'local_to'=>4,'tz_offset'=>7], fn() => strat());
check('Phnom Penh 07:00-04:00 -> 00:00-21:00 UTC', $w['sess_from']===0 && $w['sess_to']===21, json_encode([$w['sess_from'],$w['sess_to']]));
$w = with_strategy(['local_from'=>14,'local_to'=>4,'tz_offset'=>7], fn() => strat());
check('Phnom Penh 14:00-04:00 -> 07:00-21:00 UTC (old window)', $w['sess_from']===7 && $w['sess_to']===21);
$w = with_strategy(['local_from'=>7,'local_to'=>7,'tz_offset'=>7], fn() => strat());
check('local 7-7 -> 24 hours', in_session(0,$w['sess_from'],$w['sess_to']) && in_session(12,$w['sess_from'],$w['sess_to']) && in_session(23,$w['sess_from'],$w['sess_to']));
$s = strat();
check('strategy.php values are valid', isset($s['sess_from'],$s['sess_to']) && $s['sess_from']>=0 && $s['sess_from']<=23 && $s['sess_to']>=0 && $s['sess_to']<=23);
printf("  info  live window: %02d:00 -> %02d:00 UTC  =  %02d:00 -> %02d:00 Phnom Penh\n", $s['sess_from'], $s['sess_to'], ($s['sess_from']+7)%24, ($s['sess_to']+7)%24);

section('Indicators');
check('EMA of a flat series is flat', abs(ema_s(array_fill(0,50,10.0),9)[49]-10.0) < 1e-9);
check('RSI of a rising series is high', rsi_s(range(1,60),14)[59] > 90);
check('RSI of a falling series is low', rsi_s(range(60,1),14)[59] < 10);

// synthetic market: ~10 days of 5-minute candles (starts Monday 2026-01-05 00:00 UTC) with a slow trend so ADX moves
mt_srand(7); $c = []; $px = 2000.0; $t0 = strtotime('2026-01-05 00:00:00 UTC');
for ($i = 0; $i < 3000; $i++) {
  $o = $px; $px += (mt_rand(-100,100)/100)*2.5 + sin($i/200)*0.6;
  $c[] = ['t'=>gmdate('Y-m-d H:i:s',$t0+$i*300),'o'=>$o,'h'=>max($o,$px)+mt_rand(0,100)/100,'l'=>min($o,$px)-mt_rand(0,100)/100,'c'=>$px];
}
$byHour = []; foreach ($c as $x) { $k = substr($x['t'],0,13).':00:00'; $byHour[$k] = isset($byHour[$k]) ? ['t'=>$k,'o'=>$byHour[$k]['o'],'h'=>max($byHour[$k]['h'],$x['h']),'l'=>min($byHour[$k]['l'],$x['l']),'c'=>$x['c']] : ['t'=>$k,'o'=>$x['o'],'h'=>$x['h'],'l'=>$x['l'],'c'=>$x['c']]; }
$H = array_values($byHour);
$atr = atr_s($c); check('ATR is positive on synthetic data', $atr[count($atr)-1] > 0);

section('Strategy (synthetic candles, relaxed filters so signals appear)');
$P = prep($c); $HP = prep($H); $map = map_htf($c,$H);
$o = array_merge(strat(), ['min_score'=>0,'adx_min'=>0,'htf_filter'=>false,'sess_from'=>0,'sess_to'=>24]);
$n = 0; $bad = 0;
for ($i = 60; $i < count($c)-1; $i++) {
  $s = evaluate($c,$P,$i,$HP,$map[$i],$o); if (!$s) continue; $n++;
  $ok = $s['side']==='BUY' ? ($s['sl']<$s['entry'] && $s['entry']<$s['tp1'] && $s['tp1']<$s['tp2']) : ($s['sl']>$s['entry'] && $s['entry']>$s['tp1'] && $s['tp1']>$s['tp2']);
  if (!$ok || !isset($s['score'],$s['why'],$s['t'])) $bad++;
}
check("evaluate() produced signals ($n) without PHP warnings", $n > 0);
check('every signal has SL/TP on the correct side', $bad === 0, "$bad bad");
$o2 = array_merge($o, ['sess_from'=>7,'sess_to'=>21]); $out = 0;
for ($i = 60; $i < count($c)-1; $i++) if (evaluate($c,$P,$i,$HP,$map[$i],$o2)) { $h = (int)gmdate('G',strtotime($c[$i]['t'])); if ($h<7 || $h>=21) $out++; }
check('no signal fires outside the session window', $out === 0, "$out outside");
$r = simulate($c, 100, ['side'=>'BUY','entry'=>2000.0,'sl'=>1e9,'tp1'=>1e10,'rr'=>1.0,'d'=>1.0], 10, 0.3);
check('simulate() reports a stop-out', $r['st']==='SL' && $r['r'] < -1.0);

section('Database');
if (getenv('PA_TESTS_DB') !== '1') { echo "  skip  set PA_TESTS_DB=1 to run (never on the production DB)\n"; }
else {
  $pdo = db();
  $cols = $pdo->query("SHOW COLUMNS FROM signals")->fetchAll(PDO::FETCH_UNIQUE);
  check('signals has tier + reasons', isset($cols['tier'], $cols['reasons']));
  check("signals.status accepts 'EXP'", str_contains((string)$cols['status']['Type'], "'EXP'"));
  check('api_usage table exists', (bool)$pdo->query("SHOW TABLES LIKE 'api_usage'")->fetchColumn());
  cache_set('phpunit_probe', ['ok'=>1]); check('cache roundtrip', (cache_get('phpunit_probe', 60)['ok'] ?? 0) === 1);
  $pdo->exec("DELETE FROM cache WHERE ckey='phpunit_probe'");
  $oldSym = $GLOBALS['cfg']['symbol']; $oldTg = $GLOBALS['cfg']['tg_token'] ?? null;
  $GLOBALS['cfg']['symbol'] = 'TEST/SYM'; $GLOBALS['cfg']['tg_token'] = '';      // isolated rows, and Telegram can never fire
  try {
    $pdo->exec("DELETE FROM signals WHERE symbol='TEST/SYM'");
    $pdo->prepare("INSERT INTO signals(symbol,side,entry,sl,tp1,tp2,conf,reasons,candle_time) VALUES('TEST/SYM','SELL',2000,2001,1999,1998,70,'t','2000-01-01 00:00:00')")->execute();
    $cl = array_slice($c,0,-1); resolve_open($c, $cl, array_merge(strat(), ['max_bars'=>48]));
    $st = $pdo->query("SELECT status,r FROM signals WHERE symbol='TEST/SYM'")->fetch();
    check('resolve_open() closes an open signal', $st && $st['status'] !== 'OPEN', json_encode($st));
  } finally { $pdo->exec("DELETE FROM signals WHERE symbol='TEST/SYM'"); $GLOBALS['cfg']['symbol'] = $oldSym; $GLOBALS['cfg']['tg_token'] = $oldTg; }
  check('tier_stats() runs', isset(tier_stats()['confirmed']));
}

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
