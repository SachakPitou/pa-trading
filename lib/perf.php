<?php
// Performance analytics. A "win" is a closed signal with r > 0. R = multiples of the initial risk (SL = -1R).
// Note: the scanner closes a signal at TP1, SL or time expiry; TP2 is a target level only and is not tracked separately.

function perf_group(array $rows): array {
  $n = count($rows); $w = 0; $gp = 0.0; $gl = 0.0; $net = 0.0;
  foreach ($rows as $r) { $v = (float)$r['r']; $net += $v; if ($v > 0) { $w++; $gp += $v; } else { $gl += -$v; } }
  return ['n'=>$n, 'wins'=>$w, 'losses'=>$n-$w, 'win_rate'=>$n ? round($w/$n*100, 1) : null, 'net_r'=>round($net, 2),
          'avg_r'=>$n ? round($net/$n, 2) : null, 'pf'=>$gl > 0 ? round($gp/$gl, 2) : null, 'pf_inf'=>($gl == 0 && $gp > 0)];
}

/** Pure function (no DB). rows: side, status, r, candle_time - already sorted oldest to newest. */
function perf_compute(array $rows): array {
  $all = perf_group($rows); $eq = 0.0; $peak = 0.0; $mdd = 0.0; $curve = [0.0];
  $cw = 0; $cl = 0; $mw = 0; $ml = 0; $gp = 0.0; $gl = 0.0; $best = null; $worst = null; $dist = ['TP1'=>0, 'SL'=>0, 'EXP'=>0];
  foreach ($rows as $r) {
    $v = (float)$r['r']; $eq += $v; $peak = max($peak, $eq); $mdd = max($mdd, $peak - $eq); $curve[] = round($eq, 2);
    if ($v > 0) { $cw++; $cl = 0; $gp += $v; } else { $cl++; $cw = 0; $gl += -$v; }
    $mw = max($mw, $cw); $ml = max($ml, $cl);
    $best = $best === null ? $v : max($best, $v); $worst = $worst === null ? $v : min($worst, $v);
    if (isset($dist[$r['status']])) $dist[$r['status']]++;
  }
  $order = ['Asia (quiet)', 'London', 'London/NY overlap', 'New York', 'Late NY (quiet)']; $sess = []; $side = [];
  foreach ($rows as $r) { $sess[sess_label((int)substr((string)$r['candle_time'], 11, 2))][] = $r; $side[$r['side']][] = $r; }
  $bySession = []; foreach ($order as $k) if (!empty($sess[$k])) $bySession[$k] = perf_group($sess[$k]);
  $bySide = []; foreach (['BUY', 'SELL'] as $k) if (!empty($side[$k])) $bySide[$k] = perf_group($side[$k]);
  $n = count($rows);
  return ['overall'=>$all, 'avg_win'=>$all['wins'] ? round($gp/$all['wins'], 2) : null, 'avg_loss'=>$all['losses'] ? round(-$gl/$all['losses'], 2) : null,
    'best'=>$best, 'worst'=>$worst, 'max_drawdown_r'=>round($mdd, 2), 'current_drawdown_r'=>round($peak - $eq, 2),
    'streaks'=>['max_win'=>$mw, 'max_loss'=>$ml, 'current'=>$cw ? ['type'=>'win', 'len'=>$cw] : ($cl ? ['type'=>'loss', 'len'=>$cl] : null)],
    'distribution'=>$dist, 'by_session'=>$bySession, 'by_side'=>$bySide, 'curve'=>$curve];
}

function perf_report(string $tier = 'all', ?string $from = null, ?string $to = null): array {
  $w = ["status<>'OPEN'", "candle_time NOT LIKE 'test-%'"]; $p = [];
  if (in_array($tier, ['confirmed', 'early'], true)) { $w[] = 'tier=?'; $p[] = $tier; } else { $tier = 'all'; }
  if ($from !== null && $from !== '') { if (!cal_date_ok($from)) throw new InvalidArgumentException('Bad from date'); $w[] = 'COALESCE(closed_at,created_at) >= FROM_UNIXTIME(?)'; $p[] = cal_ts($from); }
  if ($to !== null && $to !== '') { if (!cal_date_ok($to)) throw new InvalidArgumentException('Bad to date'); $w[] = 'COALESCE(closed_at,created_at) < FROM_UNIXTIME(?)'; $p[] = cal_ts($to) + 86400; }
  $q = db()->prepare('SELECT side,status,r,candle_time FROM signals WHERE '.implode(' AND ', $w).' ORDER BY COALESCE(closed_at,created_at), id');
  $q->execute($p);
  return perf_compute($q->fetchAll()) + ['tier'=>$tier, 'from'=>$from, 'to'=>$to];
}