<?php
// php backtest.php [ties] [invert]          -> backtest the current config
// php backtest.php sweep [ties] [invert]    -> parameter sweep, train (70%) vs unseen test (30%)
require __DIR__.'/lib/bootstrap.php';
$sweep=in_array('sweep',$argv,true); $inv=in_array('invert',$argv,true); $tn=in_array('ties',$argv,true);

function load(): array {
  $f5=__DIR__.'/data/5min.json'; $f1=__DIR__.'/data/1h.json';
  if(is_file($f5) && is_file($f1)) return [json_decode(file_get_contents($f5),true),json_decode(file_get_contents($f1),true)];
  $c=get_candles(5000,null,3600); $H=get_candles(900,'1h',3600);
  if(count($c)<500||count($H)<100){ exit("Feed error: ".($GLOBALS['last_curl_error'] ?? 'not enough candles')."\n"); }
  return [$c,$H];
}
function run(array $c,array $H,array $o,string $tier='confirmed'): array {
  $P=prep($c); $HP=prep($H); $map=map_htf($c,$H); $t=[]; $busy=-1; $oe=array_merge($o,(array)($o['early'] ?? []));
  for($i=60;$i<count($c)-1;$i++){
    if($i<=$busy) continue;
    if($tier==='early'){ if(evaluate($c,$P,$i,$HP,$map[$i],$o)) continue; $s=evaluate($c,$P,$i,$HP,$map[$i],$oe); }   // early = looser rules, minus the strict setups
    else $s=evaluate($c,$P,$i,$HP,$map[$i],$o);
    if(!$s) continue;
    $r=simulate($c,$i,$s,$o['max_bars'],$o['cost'],!empty($o['tie_neutral']));
    $busy=$r['exit'];
    $t[]=['i'=>$i,'t'=>$s['t'],'side'=>$s['side'],'sc'=>$s['score']]+$r;
  }
  return $t;
}
function stats(array $t): array {
  $n=count($t); if(!$n) return ['n'=>0,'wr'=>0,'net'=>0,'exp'=>0,'pf'=>0];
  $w=0;$gp=0;$gl=0;$net=0; foreach($t as $x){ $net+=$x['r']; if($x['r']>0){$w++;$gp+=$x['r'];} else $gl-=$x['r']; }
  return ['n'=>$n,'wr'=>$w/$n*100,'net'=>$net,'exp'=>$net/$n,'pf'=>$gl>0?$gp/$gl:99];
}
function line(string $tag,array $s): string { return sprintf("%-8s trades %3d | win %5.1f%% | net %+7.1fR | expectancy %+.2fR | profit factor %.2f",$tag,$s['n'],$s['wr'],$s['net'],$s['exp'],$s['pf']); }

[$c,$H]=load(); $split=(int)(count($c)*0.7);
echo count($c)," x 5m candles ({$c[0]['t']} -> {$c[count($c)-1]['t']}), ",count($H)," x 1h";
echo $inv?"  [INVERTED]":"", $tn?"  [same-candle ties = 0R]":"", "\n\n";

if(in_array('tiers',$argv,true)){   // php backtest.php tiers -> track record of each tier separately
  foreach(['confirmed','early'] as $tr){
    $o=array_merge(strat(),['invert'=>$inv,'tie_neutral'=>$tn]); $tt=run($c,$H,$o,$tr);
    echo strtoupper($tr),"\n  ",line('ALL',stats($tt)),"\n  ",line('TRAIN',stats(array_filter($tt,fn($x)=>$x['i']<$split))),"\n  ",line('TEST',stats(array_filter($tt,fn($x)=>$x['i']>=$split))),"\n\n"; }
  exit;
}
if(in_array('sessions',$argv,true)){   // php backtest.php sessions -> compare trading windows (labels in Phnom Penh time, UTC+7)
  foreach([['Asia 07-14 PP',0,7],['London 14-19 PP',7,12],['Overlap 19-23 PP',12,16],['New York 23-04 PP',16,21],['All 14-04 PP',7,21]] as [$nm,$f,$to]){
    $o=array_merge(strat(),['invert'=>$inv,'tie_neutral'=>$tn,'sess_from'=>$f,'sess_to'=>$to]); $tt=run($c,$H,$o);
    echo line($nm,stats($tt)),"\n"; }
  exit;
}
if(!$sweep){
  $o=array_merge(strat(),['invert'=>$inv,'tie_neutral'=>$tn]); $t=run($c,$H,$o);
  echo line('ALL',stats($t)),"\n",
       line('TRAIN',stats(array_filter($t,fn($x)=>$x['i']<$split))),"\n",
       line('TEST',stats(array_filter($t,fn($x)=>$x['i']>=$split))),"\n";
  echo "\nOutcomes: ".json_encode(array_count_values(array_column($t,'st')))."\n";
  echo "\nLast signals this logic would have fired on real data:\n";
  foreach(array_slice($t,-8) as $x) printf("  %s  %-4s score %d -> %s (%+.2fR)\n",$x['t'],$x['side'],$x['sc'],$x['st'],$x['r']);
  echo "\nNote: breakeven win rate = 1/(1+RR) plus costs. With RR {$o['rr']} that is ~".round(100/(1+$o['rr']))."%.\n";
  exit;
}
$rows=[];
foreach(['both','pullback','cross'] as $tg) foreach([60,70] as $ms) foreach([20,25,30] as $ax) foreach([1.0,2.0] as $rr) foreach([1.5,2.0,2.5] as $sl){
  $o=array_merge(strat(),['invert'=>$inv,'tie_neutral'=>$tn,'trigger'=>$tg,'min_score'=>$ms,'adx_min'=>$ax,'rr'=>$rr,'sl_atr'=>$sl]); $t=run($c,$H,$o);
  $tr=stats(array_filter($t,fn($x)=>$x['i']<$split)); $te=stats(array_filter($t,fn($x)=>$x['i']>=$split));
  if($tr['n']>=20) $rows[]=['o'=>"$tg score>=$ms adx>=$ax rr=$rr sl={$sl}ATR",'tr'=>$tr,'te'=>$te];
}
usort($rows,fn($a,$b)=>$b['tr']['exp']<=>$a['tr']['exp']);
echo "Top settings ranked on TRAIN data only; TEST is data the ranking never saw:\n\n";
foreach(array_slice($rows,0,10) as $r)
  printf("%-34s TRAIN %3d tr %4.1f%% %+.2fR | TEST %3d tr %4.1f%% %+.2fR\n",$r['o'],$r['tr']['n'],$r['tr']['wr'],$r['tr']['exp'],$r['te']['n'],$r['te']['wr'],$r['te']['exp']);
echo "\nOnly trust a setting if TEST is also positive. Put the winner into 'strategy' in config.php.\n";