<?php
function strat(): array {
  $o=array_merge(['min_score'=>70,'adx_min'=>20,'sl_atr'=>1.5,'rr'=>1.0,'sess_from'=>7,'sess_to'=>21,'htf_filter'=>true,
    'news_block'=>true,'news_mins'=>30,'max_bars'=>48,'cost'=>0.30,
    'early'=>['min_score'=>60,'adx_min'=>15],'tier_min_win'=>45,'tier_min_trades'=>10,'tier_window'=>30],(array)(cfg('strategy') ?? []));
  if(isset($o['local_from'],$o['local_to'])){   // trading window in YOUR clock (tz_offset = hours ahead of UTC, Phnom Penh = 7)
    $off=(int)($o['tz_offset'] ?? 7); $o['sess_from']=(((int)$o['local_from']-$off)%24+24)%24; $o['sess_to']=(((int)$o['local_to']-$off)%24+24)%24;
  }
  return $o;
}
/** True if UTC hour $h is inside [from,to); works for windows that cross midnight. */
function in_session(int $h,int $from,int $to): bool { return $from<$to ? ($h>=$from && $h<$to) : ($h>=$from || $h<$to); }
function sess_label(int $h): string { return $h<7?'Asia (quiet)':($h<12?'London':($h<16?'London/NY overlap':($h<21?'New York':'Late NY (quiet)'))); }

/** Pure strategy: evaluate closed bar $i. Used identically by the live scanner and the backtester. */
function evaluate(array $c,array $P,int $i,array $HP,int $hi,array $o): ?array {
  if($i<60 || $hi<55) return null;
  $x=$c[$i]; $cl=$P['c'][$i]; $a=$P['atr'][$i]; if($a<=0) return null;
  if((int)gmdate('N',strtotime($x['t']))>=6) return null;                       // gold is closed Saturday/Sunday
  $h=(int)gmdate('G',strtotime($x['t'])); if(!in_session($h,$o['sess_from'],$o['sess_to'])) return null;
  $adx=$P['adx'][$i]; if($adx<$o['adx_min']) return null;                       // ranging market: skip
  if($a<0.6*(array_sum(array_slice($P['atr'],$i-49,50))/50)) return null;       // dead volatility: skip
  $e9=$P['e9'];$e21=$P['e21'];$e50=$P['e50'];
  $bx=$e9[$i-1]<=$e21[$i-1]&&$e9[$i]>$e21[$i]; $sx=$e9[$i-1]>=$e21[$i-1]&&$e9[$i]<$e21[$i];
  $bp=$e9[$i]>$e21[$i]&&$e21[$i]>$e50[$i]&&$x['l']<=$e21[$i]+0.1*$a&&$cl>$e21[$i]&&$cl>$x['o'];
  $sp=$e9[$i]<$e21[$i]&&$e21[$i]<$e50[$i]&&$x['h']>=$e21[$i]-0.1*$a&&$cl<$e21[$i]&&$cl<$x['o'];
  $tg=$o['trigger'] ?? 'both'; if($tg==='pullback'){ $bx=false; $sx=false; } if($tg==='cross'){ $bp=false; $sp=false; }
  if($bx||$bp){ $side='BUY'; $m=1; $trig=$bx?'EMA cross':'EMA21 pullback'; }
  elseif($sx||$sp){ $side='SELL'; $m=-1; $trig=$sx?'EMA cross':'EMA21 pullback'; } else return null;
  $hc=$HP['c'][$hi]; $h21=$HP['e21'][$hi]; $h50=$HP['e50'][$hi];
  $ht=($hc>$h50&&$h21>$h50)?1:(($hc<$h50&&$h21<$h50)?-1:0);
  if($o['htf_filter'] && $ht===-$m) return null;                                // never trade against the 1H trend
  $r=$P['rsi'][$i]; if(($m>0&&$r>72)||($m<0&&$r<28)) return null;               // overextended
  $score=0; $why=[$trig];
  if($ht===$m){ $score+=25; $why[]='1H trend'; }
  $score+=$adx>=30?20:($adx>=25?15:10); $why[]='ADX '.round($adx);
  if($m*($cl-$e50[$i])>0){ $score+=15; $why[]=($m>0?'above':'below').' EMA50'; }
  $rin=$m>0?(($r>=50&&$r<=68)?15:(($r>=45&&$r<50)?8:0)):(($r<=50&&$r>=32)?15:(($r>50&&$r<=55)?8:0));
  if($rin){ $score+=$rin; $why[]='RSI '.round($r); }
  $mh=$P['mh']; if($m*$mh[$i]>0) $score+=5; if($m*($mh[$i]-$mh[$i-1])>0){ $score+=5; $why[]='MACD momentum'; }
  $rng=$x['h']-$x['l']; if($rng>0 && $m*($x['c']-$x['o'])>0 && abs($x['c']-$x['o'])/$rng>=0.5){ $score+=10; $why[]='strong candle'; }
  if($score<$o['min_score']) return null;
  $win=array_slice($c,$i-5,6); $sw=$m>0?min(array_column($win,'l')):max(array_column($win,'h'));
  $d=max($o['sl_atr']*$a,$m>0?$cl-($sw-0.1*$a):($sw+0.1*$a)-$cl); $d=min($d,2.5*$a);
  if($d<10*$o['cost']) return null;                                                // spread must be under 10% of the stop distance
  if(!empty($o['invert'])){ $m=-$m; $side=$m>0?'BUY':'SELL'; $why[]='inverted'; }   // research mode: trade the opposite way
  return ['side'=>$side,'entry'=>$cl,'sl'=>$cl-$m*$d,'tp1'=>$cl+$m*$d*$o['rr'],'tp2'=>$cl+$m*$d*$o['rr']*2,'d'=>$d,'rr'=>$o['rr'],
          'score'=>min(99,$score),'why'=>implode(' · ',$why),'t'=>$x['t']];
}

/** Walk forward from the signal bar: SL first if both hit in one bar (conservative). Spread cost subtracted in R. */
function simulate(array $c,int $i,array $s,int $maxBars,float $cost,bool $neutralTies=false): array {
  $m=$s['side']==='BUY'?1:-1; $end=min(count($c)-1,$i+$maxBars);
  for($j=$i+1;$j<=$end;$j++){
    $sl=$m>0?$c[$j]['l']<=$s['sl']:$c[$j]['h']>=$s['sl']; $tp=$m>0?$c[$j]['h']>=$s['tp1']:$c[$j]['l']<=$s['tp1'];
    if($sl&&$tp&&$neutralTies) return ['r'=>-$cost/$s['d'],'st'=>'TIE','exit'=>$j];   // same-candle SL+TP: order unknown
    if($sl) return ['r'=>-1.0-$cost/$s['d'],'st'=>'SL','exit'=>$j];
    if($tp) return ['r'=>$s['rr']-$cost/$s['d'],'st'=>'TP1','exit'=>$j];
  }
  return ['r'=>$m*($c[$end]['c']-$s['entry'])/$s['d']-$cost/$s['d'],'st'=>'EXP','exit'=>$end];
}

function resolve_open(array $all,array $closed,array $o): void {
  $q=db()->prepare("SELECT * FROM signals WHERE status='OPEN' AND symbol=?"); $q->execute([cfg('symbol')]);
  foreach($q->fetchAll() as $s){
    $b=$s['side']==='BUY'; $res=null; $bars=0; $d=abs($s['entry']-$s['sl']) ?: 1;
    foreach($all as $x){ if($x['t']<=$s['candle_time']) continue; $bars++;
      $sl=$b?$x['l']<=$s['sl']:$x['h']>=$s['sl']; $tp=$b?$x['h']>=$s['tp1']:$x['l']<=$s['tp1'];
      if($sl){ $res=['SL',-1.0]; break; } if($tp){ $res=['TP1',round(abs($s['tp1']-$s['entry'])/$d,2)]; break; } }
    if(!$res && $bars>=$o['max_bars']){ $res=['EXP',round(($b?1:-1)*($closed[count($closed)-1]['c']-$s['entry'])/$d,2)]; }
    if($res){
      db()->prepare('UPDATE signals SET status=?,r=?,closed_at=NOW() WHERE id=?')->execute([$res[0],$res[1],$s['id']]);
      alert_all("{$s['side']} signal closed: {$res[0]}","{$s['side']} {$s['symbol']} @ {$s['entry']} closed: {$res[0]} ({$res[1]}R)");
    }
  }
}

/** Per-tier track record. A tier is shown unless its recent win rate (last N closed) is below the minimum. */
function tier_stats(): array {
  $o=strat(); $out=[];
  foreach(['confirmed','early'] as $t){
    $q=db()->prepare("SELECT r FROM signals WHERE tier=? AND status<>'OPEN' AND candle_time NOT LIKE 'test-%' ORDER BY id DESC"); $q->execute([$t]);
    $all=$q->fetchAll(PDO::FETCH_COLUMN); $win=array_slice($all,0,(int)$o['tier_window']); $n=count($win);
    $wr=$n?(int)round(count(array_filter($win,fn($r)=>$r>0))/$n*100):null;
    $out[$t]=['all_closed'=>count($all),'window'=>$n,'win_rate'=>$wr,'net_r'=>round(array_sum($win),1),'min_win'=>$o['tier_min_win'],
              'visible'=>$n<(int)$o['tier_min_trades'] || $wr>=$o['tier_min_win']];
  }
  return $out;
}

function scan(): array {
  $o=strat(); $dow=(int)gmdate('N'); $hr=(int)gmdate('G');
  if($dow>=6 || !in_session($hr,$o['sess_from'],$o['sess_to'])){ $r=['skipped'=>'outside trading hours (no API call)']; cache_set('last_scan',$r+['at'=>time(),'api'=>api_used()]); return $r; }
  $c=get_candles(500); $H=get_candles(120,'1h',1800);
  if(count($c)<120 || count($H)<60){ $e=['error'=>'Not enough data from the feed ('.count($c).' x 5m, '.count($H).' x 1h)']; cache_set('last_scan',$e+['at'=>time()]); return $e; }
  $closed=array_slice($c,0,-1); $i=count($closed)-1; $sym=cfg('symbol');
  resolve_open($c,$closed,$o);
  $P=prep($closed); $HP=prep($H); $hi=map_htf($closed,$H)[$i]; $new=[]; $skip=null;
  $cand=['confirmed'=>evaluate($closed,$P,$i,$HP,$hi,$o)];
  $cand['early']=$cand['confirmed'] ? null : evaluate($closed,$P,$i,$HP,$hi,array_merge($o,(array)$o['early']));   // early = passes the looser rules but not the strict ones
  $stats=tier_stats();
  foreach($cand as $tier=>$s){
    if(!$s) continue;
    $op=db()->prepare("SELECT COUNT(*) FROM signals WHERE status='OPEN' AND tier=?"); $op->execute([$tier]);
    if((int)$op->fetchColumn()){ $skip="$tier: a signal is already open"; continue; }
    if($o['news_block'] && ($n=news_blocked(strtotime($s['t']),$o['news_mins']))){ $skip="news blackout: $n"; continue; }
    $q=db()->prepare('INSERT IGNORE INTO signals(symbol,tier,side,entry,sl,tp1,tp2,conf,reasons,candle_time) VALUES(?,?,?,?,?,?,?,?,?,?)');
    $q->execute([$sym,$tier,$s['side'],$s['entry'],$s['sl'],$s['tp1'],$s['tp2'],$s['score'],$s['why'],$s['t']]);
    if($q->rowCount()){ $new[$tier]=$s;
      if($stats[$tier]['visible']){   // paused tiers are still tracked, but not announced
        $lbl=$tier==='early'?'EARLY setup':'CONFIRMED';
        alert_all("New {$s['side']} signal $sym ($lbl)",sprintf("%s %s  [%s, score %d]\nEntry %.2f\nSL %.2f\nTP1 %.2f | TP2 %.2f\nWhy: %s",$s['side'],$sym,$lbl,$s['score'],$s['entry'],$s['sl'],$s['tp1'],$s['tp2'],$s['why'])); }
    }
  }
  if(!$new && !$skip) $skip=$P['adx'][$i]<min($o['adx_min'],(int)($o['early']['adx_min'] ?? $o['adx_min'])) ? 'waiting: ADX '.round($P['adx'][$i],1).' is below the minimum (ranging market)' : 'waiting: no qualifying setup yet';
  $res=['new'=>$new ?: null,'skipped'=>$skip,'rsi'=>round($P['rsi'][$i],1),'adx'=>round($P['adx'][$i],1),'atr'=>round($P['atr'][$i],2)];
  cache_set('last_scan',$res+['at'=>time(),'candle'=>$closed[$i]['t'],'api'=>api_used()]);   // heartbeat shown on the dashboard
  return $res;
}
/** Pipeline test: DB insert + dashboard + Telegram/email. Saved as EXP so it never blocks real signals. */
function fire_test_signal(): array {
  $p=get_price(); if(empty($p['price'])) return ['error'=>$p['error'] ?? 'no price'];
  $en=(float)$p['price']; $d=2.0;
  db()->prepare("INSERT INTO signals(symbol,side,entry,sl,tp1,tp2,conf,reasons,candle_time,status,r,closed_at) VALUES(?,?,?,?,?,?,?,?,?,'EXP',0,NOW())")
    ->execute([cfg('symbol'),'BUY',$en,$en-$d,$en+$d,$en+2*$d,99,'TEST SIGNAL (ignore)','test-'.time()]);
  alert_all('TEST signal','TEST BUY '.cfg('symbol').' @ '.number_format($en,2)."\nPipeline test only, not a real signal.");
  return ['ok'=>true,'entry'=>$en];
}

function analysis(): array {
  $c=get_candles(500); $H=get_candles(120,'1h',1800);
  if(count($c)<120 || count($H)<60) return ['error'=>'Not enough data from the feed'];
  $cl=array_slice($c,0,-1); $i=count($cl)-1; $P=prep($cl); $HP=prep($H); $hi=map_htf($cl,$H)[$i];
  $px=$P['c'][$i]; $hc=$HP['c'][$hi]; $h21=$HP['e21'][$hi]; $h50=$HP['e50'][$hi];
  $ht=($hc>$h50&&$h21>$h50)?1:(($hc<$h50&&$h21<$h50)?-1:0);
  $t5=($P['e9'][$i]>$P['e21'][$i]&&$P['e21'][$i]>$P['e50'][$i])?1:(($P['e9'][$i]<$P['e21'][$i]&&$P['e21'][$i]<$P['e50'][$i])?-1:0);
  $bull=($ht>0)+($t5>0)+($px>$P['e50'][$i])+($P['rsi'][$i]>50)+($P['mh'][$i]>0); // 0..5
  $w=array_slice($cl,-50); $d=array_slice($H,-25,24); $lbl=fn($n)=>$n>0?'Bullish':($n<0?'Bearish':'Neutral');
  $adx=$P['adx'][$i]; $h=(int)gmdate('G');
  return ['price'=>$px,'bias'=>$bull>=4?'Bullish':($bull<=1?'Bearish':'Mixed'),'bull_factors'=>$bull,'htf'=>$lbl($ht),'trend5'=>$lbl($t5),
    'rsi'=>round($P['rsi'][$i],1),'adx'=>round($adx,1),'adx_state'=>$adx>=25?'Trending':($adx>=20?'Weak trend':'Ranging'),'atr'=>round($P['atr'][$i],2),
    'macd'=>$P['mh'][$i]>0?'Positive':'Negative','session'=>sess_label($h),
    'levels'=>['res5'=>max(array_column($w,'h')),'sup5'=>min(array_column($w,'l')),'res_day'=>max(array_column($d,'h')),'sup_day'=>min(array_column($d,'l'))],
    'news'=>next_news()];
}
function signal_stats(): array {
  $r=db()->query("SELECT COUNT(*) n, COALESCE(SUM(r>0),0) w, COALESCE(SUM(r),0) net FROM signals WHERE status<>'OPEN' AND candle_time NOT LIKE 'test-%'")->fetch();
  return ['closed'=>(int)$r['n'],'win_rate'=>$r['n']?round($r['w']/$r['n']*100):null,'net_r'=>(float)$r['net']];
}