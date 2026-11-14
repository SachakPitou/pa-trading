<?php
// Stage 3 — risk management: position-size calculator + trading guard.
// risk_calc() and risk_guard() are pure (no DB), so tests can cover them. Everything else just loads/saves.
// Money per 1.00 of price move per 1 lot = tick_value / tick_size (that is how MT4/MT5 publish it).
// XAU/USD default: contract 100 oz, tick size 0.01, tick value $1.00 per lot.

function risk_defaults(): array { return [
  'balance'=>10000.0, 'risk_pct'=>1.0,
  'contract_size'=>100.0, 'tick_size'=>0.01, 'tick_value'=>1.0, 'lot_step'=>0.01,
  'daily_loss_pct'=>3.0, 'max_consec_losses'=>3, 'max_open_risk_pct'=>2.0,   // a limit of 0 switches that check off
  'cooldown_on'=>0, 'cooldown_min'=>60]; }

function risk_clean(array $in): array {
  $d = risk_defaults(); $out = [];
  $rules = ['balance'=>[1,1e9,'Account balance'], 'risk_pct'=>[0.01,100,'Risk %'], 'contract_size'=>[0.0001,1e9,'Contract size'],
    'tick_size'=>[0.000001,1e6,'Tick size'], 'tick_value'=>[0.000001,1e9,'Tick value'], 'lot_step'=>[0.0001,1000,'Lot step'],
    'daily_loss_pct'=>[0,100,'Daily loss limit'], 'max_consec_losses'=>[0,100,'Max consecutive losses'],
    'max_open_risk_pct'=>[0,100,'Max open risk'], 'cooldown_min'=>[1,1440,'Cooldown minutes']];
  foreach ($rules as $k => [$lo, $hi, $label]) {
    $v = $in[$k] ?? $d[$k];
    if (!is_numeric($v) || $v < $lo || $v > $hi) throw new InvalidArgumentException("$label must be a number between $lo and ".number_format($hi, 0, '.', '').'.');
    $out[$k] = in_array($k, ['max_consec_losses', 'cooldown_min'], true) ? (int)$v : (float)$v;
  }
  $out['cooldown_on'] = !empty($in['cooldown_on']) ? 1 : 0;
  return $out;
}

/** Position size, loss and reward for one planned trade. Side is read from entry vs stop (stop below entry = BUY). */
function risk_calc(array $in, array $set, float $openRisk = 0.0): array {
  $bal = (float)($in['balance'] ?? $set['balance']); $pct = (float)($in['risk_pct'] ?? $set['risk_pct']);
  $e = (float)($in['entry'] ?? 0); $sl = (float)($in['sl'] ?? 0); $tp = ($in['tp'] ?? '') !== '' ? (float)$in['tp'] : null;
  if ($bal <= 0 || $pct <= 0 || $pct > 100) throw new InvalidArgumentException('Balance must be positive and risk % between 0 and 100.');
  if ($e <= 0 || $sl <= 0) throw new InvalidArgumentException('Enter a positive entry price and stop-loss price.');
  if ($e == $sl) throw new InvalidArgumentException('Stop loss must be different from the entry price.');
  $dir = $e > $sl ? 1 : -1;
  if ($tp !== null && ($tp <= 0 || $dir*($tp-$e) <= 0)) throw new InvalidArgumentException('Take profit must be '.($dir > 0 ? 'above' : 'below').' the entry for a '.($dir > 0 ? 'BUY' : 'SELL').'.');
  $perUnit = $set['tick_value'] / $set['tick_size'];
  $dist = abs($e-$sl); $lossPerLot = $dist*$perUnit; $riskMoney = $bal*$pct/100; $step = $set['lot_step'];
  $exact = $riskMoney / $lossPerLot;
  $lots = round(floor($exact/$step + 1e-9) * $step, 6);          // always round DOWN so you never risk more than planned
  $actual = $lots * $lossPerLot; $warn = [];
  if ($lots <= 0) $warn[] = 'Even the smallest lot ('.$step.') would risk '.number_format($step*$lossPerLot, 2).' — more than your '.number_format($riskMoney, 2).' target. Widen your risk %, or tighten the stop.';
  $limit = $set['max_open_risk_pct'] > 0 ? $bal*$set['max_open_risk_pct']/100 : 0;
  if ($lots > 0 && $limit > 0 && $openRisk + $actual > $limit + 1e-9) $warn[] = 'With your open positions this trade takes open risk to '.number_format(($openRisk+$actual)/$bal*100, 2).'% (limit '.$set['max_open_risk_pct'].'%).';
  return ['side'=>$dir > 0 ? 'BUY' : 'SELL', 'stop_distance'=>round($dist, 5), 'stop_ticks'=>round($dist/$set['tick_size'], 1),
    'risk_money'=>round($riskMoney, 2), 'lots_exact'=>round($exact, 4), 'lots'=>$lots,
    'risk_actual'=>round($actual, 2), 'risk_actual_pct'=>round($actual/$bal*100, 3),
    'reward'=>$tp !== null ? round($lots*abs($tp-$e)*$perUnit, 2) : null, 'rr'=>$tp !== null ? round(abs($tp-$e)/$dist, 2) : null,
    'notional'=>round($lots*$set['contract_size']*$e, 2), 'warnings'=>$warn];
}

/** Money at risk for one open position if its stop is hit. */
function risk_pos_risk(array $p, array $set): float { return abs($p['entry']-$p['sl']) * $p['lots'] * ($set['tick_value']/$set['tick_size']); }

/**
 * Trading guard. $trades = journal rows newest first ['pnl','ts']; $positions = open positions; $dayStart = unix time of today's start.
 * Daily loss and the loss streak only count trades logged today, so both reset every day.
 * Statuses: ok | warn (80% of a limit used) | breach | off (limit is 0). Overall: ok | warn | stop.
 */
function risk_guard(array $trades, array $positions, array $set, int $now, int $dayStart): array {
  $bal = (float)$set['balance']; $today = array_values(array_filter($trades, fn($t) => $t['ts'] >= $dayStart));
  $pnl = array_sum(array_column($today, 'pnl')); $loss = max(0.0, -$pnl);
  $streak = 0; foreach ($today as $t) { if ($t['pnl'] < 0) $streak++; else break; }
  $open = 0.0; foreach ($positions as $p) $open += risk_pos_risk($p, $set);
  $st = fn(float $used, float $lim, bool $hitAtLimit) => $lim <= 0 ? 'off' : (($hitAtLimit ? $used >= $lim : $used > $lim) ? 'breach' : ($used >= 0.8*$lim ? 'warn' : 'ok'));
  $m = fn(float $v) => '$'.number_format($v, 2);

  $dl = $bal*$set['daily_loss_pct']/100; $s1 = $st($loss, $dl, true);
  $c1 = ['key'=>'daily', 'label'=>'Daily loss', 'used'=>round($loss, 2), 'limit'=>round($dl, 2), 'unit'=>'$', 'status'=>$s1,
    'msg'=>$s1 === 'breach' ? 'Daily loss limit reached ('.$m($loss).' of '.$m($dl).'). Stop trading for today.' : ($s1 === 'warn' ? 'Close to the daily loss limit ('.$m($loss).' of '.$m($dl).').' : '')];

  $mx = (int)$set['max_consec_losses']; $cdActive = false; $until = null;
  if ($set['cooldown_on'] && $mx > 0 && $streak >= $mx) { $until = $today[0]['ts'] + (int)$set['cooldown_min']*60; $cdActive = $now < $until; }
  $s2 = $mx <= 0 ? 'off' : ($streak >= $mx ? 'breach' : ($mx > 1 && $streak == $mx-1 ? 'warn' : 'ok'));
  if ($s2 === 'breach' && $set['cooldown_on'] && !$cdActive) $s2 = 'warn';      // cooldown served: allowed back, but the next loss restarts it
  $c2 = ['key'=>'streak', 'label'=>'Consecutive losses', 'used'=>$streak, 'limit'=>$mx, 'unit'=>'losses', 'status'=>$s2,
    'msg'=>$s2 === 'breach' ? $streak.' losses in a row (limit '.$mx.'). '.($set['cooldown_on'] ? 'Cooldown active.' : 'Step away for the day.')
      : ($s2 === 'warn' ? ($streak >= $mx ? 'Cooldown finished — one more loss starts it again.' : $streak.' losses in a row — one more hits the limit.') : '')];

  $ol = $bal*$set['max_open_risk_pct']/100; $s3 = $st($open, $ol, false);
  $c3 = ['key'=>'open', 'label'=>'Open risk', 'used'=>round($open, 2), 'limit'=>round($ol, 2), 'unit'=>'$', 'status'=>$s3,
    'msg'=>$s3 === 'breach' ? 'Open risk '.$m($open).' is above your limit of '.$m($ol).'. Reduce or close a position.' : ($s3 === 'warn' ? 'Open risk is near the limit ('.$m($open).' of '.$m($ol).').' : '')];

  $checks = [$c1, $c2, $c3]; $all = array_column($checks, 'status');
  $overall = in_array('breach', $all, true) ? 'stop' : (in_array('warn', $all, true) ? 'warn' : 'ok');
  return ['status'=>$overall, 'checks'=>$checks, 'today_pnl'=>round($pnl, 2), 'today_trades'=>count($today),
    'cooldown'=>['on'=>(bool)$set['cooldown_on'], 'active'=>$cdActive, 'until'=>$cdActive ? $until : null, 'mins_left'=>$cdActive ? (int)ceil(($until-$now)/60) : 0]];
}

function risk_settings(int $uid): array {
  $s = db()->prepare('SELECT cfg FROM risk_settings WHERE user_id=?'); $s->execute([$uid]);
  $saved = json_decode((string)$s->fetchColumn(), true);
  return array_merge(risk_defaults(), is_array($saved) ? $saved : []);
}
function risk_save(int $uid, array $in): array {
  $c = risk_clean($in); $j = json_encode($c);
  db()->prepare('INSERT INTO risk_settings(user_id,cfg) VALUES(?,?) ON DUPLICATE KEY UPDATE cfg=?')->execute([$uid, $j, $j]);
  return $c;
}

function risk_positions(int $uid): array {
  $s = db()->prepare('SELECT id,side,lots,entry,sl FROM risk_positions WHERE user_id=? ORDER BY id'); $s->execute([$uid]); $o = [];
  foreach ($s->fetchAll() as $r) $o[] = ['id'=>(int)$r['id'], 'side'=>$r['side'], 'lots'=>(float)$r['lots'], 'entry'=>(float)$r['entry'], 'sl'=>(float)$r['sl']];
  return $o;
}
function risk_pos_add(int $uid, array $in): void {
  $side = ($in['side'] ?? '') === 'SELL' ? 'SELL' : 'BUY'; $dir = $side === 'BUY' ? 1 : -1;
  $lots = (float)($in['lots'] ?? 0); $e = (float)($in['entry'] ?? 0); $sl = (float)($in['sl'] ?? 0);
  if ($lots <= 0 || $e <= 0 || $sl <= 0) throw new InvalidArgumentException('Lots, entry and stop loss must be positive numbers.');
  if ($dir*($e-$sl) <= 0) throw new InvalidArgumentException('Stop loss must be '.($dir > 0 ? 'below' : 'above').' the entry for a '.$side.'.');
  $n = db()->prepare('SELECT COUNT(*) FROM risk_positions WHERE user_id=?'); $n->execute([$uid]);
  if ((int)$n->fetchColumn() >= 20) throw new InvalidArgumentException('Maximum 20 open positions. Remove closed ones first.');
  db()->prepare('INSERT INTO risk_positions(user_id,side,lots,entry,sl) VALUES(?,?,?,?,?)')->execute([$uid, $side, $lots, $e, $sl]);
}
function risk_pos_del(int $uid, int $id): void { db()->prepare('DELETE FROM risk_positions WHERE id=? AND user_id=?')->execute([$id, $uid]); }

/** Everything the risk page needs in one call. */
function risk_state(int $uid): array {
  $set = risk_settings($uid); $pos = risk_positions($uid); $now = time();
  $s = db()->prepare('SELECT pnl, UNIX_TIMESTAMP(created_at) ts FROM journal WHERE user_id=? ORDER BY id DESC LIMIT 200'); $s->execute([$uid]);
  $trades = array_map(fn($r) => ['pnl'=>(float)$r['pnl'], 'ts'=>(int)$r['ts']], $s->fetchAll());
  $off = (int)(strat()['tz_offset'] ?? 7)*3600; $dayStart = intdiv($now+$off, 86400)*86400 - $off;   // same "day" as the stats pages
  foreach ($pos as &$p) $p['risk'] = round(risk_pos_risk($p, $set), 2); unset($p);
  return ['settings'=>$set, 'positions'=>$pos, 'guard'=>risk_guard($trades, $pos, $set, $now, $dayStart)];
}