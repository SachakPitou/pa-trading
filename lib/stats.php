<?php
declare(strict_types=1);
// Calendar statistics: how many signals per day / week / month, win rate and net R.
//  - A "day" is a Phnom Penh day (UTC + tz_offset from strategy.php), weeks run Monday-Sunday (ISO), months are calendar months.
//  - A signal belongs to the day it was CREATED. OPEN signals are counted as signals but not in the win rate.
//  - Win = closed with r > 0 (same rule as tier_stats()); everything else closed (SL, flat/negative expiry) is a loss.
//  - Win rate = wins / closed.  Net R = sum of r over closed signals.

function cal_metrics(): array { return ['signals'=>0,'closed'=>0,'wins'=>0,'losses'=>0,'open'=>0,'net_r'=>0.0]; }

function cal_add(array &$m, array $row): void {
  $m['signals']++;
  if ($row['status'] === 'OPEN') { $m['open']++; return; }
  $r = (float)($row['r'] ?? 0);
  $m['closed']++; $r > 0 ? $m['wins']++ : $m['losses']++;
  $m['net_r'] += $r;
}

function cal_finish(array $m): array {
  $m['net_r']    = round($m['net_r'], 2);
  $m['win_rate'] = $m['closed'] ? round($m['wins'] / $m['closed'] * 100, 1) : null;
  return $m;
}

/** Pure function (no DB): rows need ts (unix), status, r. Returns days / weeks / months / total. */
function calendar_buckets(array $rows, int $offset): array {
  $days = $weeks = $months = []; $tot = cal_metrics();
  foreach ($rows as $x) {
    $lt   = (int)$x['ts'] + $offset * 3600;                  // local wall-clock as if it were UTC
    $date = gmdate('Y-m-d', $lt);
    $mid  = $lt - ($lt % 86400);                             // local midnight
    $wk   = gmdate('o-\WW', $lt);
    $mon  = gmdate('Y-m', $lt);
    $start = $mid - ((int)gmdate('N', $lt) - 1) * 86400;     // Monday of that week
    $days[$date]   ??= cal_metrics() + ['date' => $date];
    $weeks[$wk]    ??= cal_metrics() + ['week' => $wk, 'start' => gmdate('Y-m-d', $start), 'end' => gmdate('Y-m-d', $start + 6 * 86400)];
    $months[$mon]  ??= cal_metrics() + ['month' => $mon];
    cal_add($days[$date], $x); cal_add($weeks[$wk], $x); cal_add($months[$mon], $x); cal_add($tot, $x);
  }
  ksort($days); ksort($weeks); ksort($months);
  $days = array_map('cal_finish', array_values($days));
  $tot = cal_finish($tot); $tot['active_days'] = count($days);
  $tot['avg_r'] = $tot['closed'] ? round($tot['net_r'] / $tot['closed'], 2) : null;
  $withClosed = array_values(array_filter($days, fn($d) => $d['closed'] > 0));
  $tot['best_day'] = $tot['worst_day'] = null;
  foreach ($withClosed as $d) {
    if ($tot['best_day']  === null || $d['net_r'] > $tot['best_day']['net_r'])  $tot['best_day']  = ['date'=>$d['date'], 'net_r'=>$d['net_r']];
    if ($tot['worst_day'] === null || $d['net_r'] < $tot['worst_day']['net_r']) $tot['worst_day'] = ['date'=>$d['date'], 'net_r'=>$d['net_r']];
  }
  return ['days'=>$days, 'weeks'=>array_map('cal_finish', array_values($weeks)), 'months'=>array_map('cal_finish', array_values($months)), 'total'=>$tot];
}

function cal_date_ok(string $d): bool {
  return (bool)preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}
function cal_ts(string $d): int { return (new DateTimeImmutable($d . ' 00:00:00', new DateTimeZone('UTC')))->getTimestamp(); }

/** DB wrapper. $from may be 'all'. Throws InvalidArgumentException on bad input. */
function calendar_data(?string $from, ?string $to, string $tier = 'all', bool $withSignals = false, ?int $offset = null): array {
  $off   = $offset ?? (int)(strat()['tz_offset'] ?? 7);
  $today = gmdate('Y-m-d', time() + $off * 3600);
  $to    = $to ?: $today;
  if ($from === 'all') {
    $min  = db()->query("SELECT MIN(UNIX_TIMESTAMP(created_at)) FROM signals WHERE candle_time NOT LIKE 'test-%'")->fetchColumn();
    $from = $min ? gmdate('Y-m-d', (int)$min + $off * 3600) : $today;
  }
  $from = $from ?: gmdate('Y-m-d', cal_ts($to) - 29 * 86400);
  if (!cal_date_ok($from) || !cal_date_ok($to)) throw new InvalidArgumentException('Dates must look like 2026-11-12');
  if ($from > $to)                              throw new InvalidArgumentException('"From" must be before "To"');
  if ((cal_ts($to) - cal_ts($from)) / 86400 > 3660) throw new InvalidArgumentException('Period too long (max 10 years)');
  $tier = in_array($tier, ['confirmed', 'early'], true) ? $tier : 'all';

  $t0 = cal_ts($from) - $off * 3600; $t1 = cal_ts($to) + 86400 - $off * 3600;     // local midnight .. next local midnight, as UTC
  $sql = "SELECT id,tier,side,entry,sl,tp1,tp2,conf,status,r,reasons,UNIX_TIMESTAMP(created_at) AS ts FROM signals
          WHERE candle_time NOT LIKE 'test-%' AND UNIX_TIMESTAMP(created_at) >= :a AND UNIX_TIMESTAMP(created_at) < :b"
       . ($tier !== 'all' ? ' AND tier = :t' : '') . ' ORDER BY created_at, id LIMIT 20000';
  $q = db()->prepare($sql);
  $q->bindValue(':a', $t0, PDO::PARAM_INT); $q->bindValue(':b', $t1, PDO::PARAM_INT);
  if ($tier !== 'all') $q->bindValue(':t', $tier);
  $q->execute(); $rows = $q->fetchAll();

  $res = ['from'=>$from, 'to'=>$to, 'tier'=>$tier, 'tz'=>$off, 'today'=>$today] + calendar_buckets($rows, $off);
  if ($withSignals) {
    $res['signals'] = array_map(fn($x) => [
      'id'=>(int)$x['id'], 'date'=>gmdate('Y-m-d', (int)$x['ts'] + $off * 3600), 'time'=>gmdate('H:i', (int)$x['ts'] + $off * 3600),
      'tier'=>$x['tier'], 'side'=>$x['side'], 'entry'=>(float)$x['entry'], 'sl'=>(float)$x['sl'], 'tp1'=>(float)$x['tp1'], 'tp2'=>(float)$x['tp2'],
      'conf'=>(int)$x['conf'], 'status'=>$x['status'], 'r'=>$x['r'] === null ? null : (float)$x['r'], 'reasons'=>(string)($x['reasons'] ?? ''),
    ], $rows);
  }
  return $res;
}