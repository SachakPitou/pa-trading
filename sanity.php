<?php
// php sanity.php -> null model: RANDOM entries with the same stop/target logic. Shows whether the backtest itself carries a built-in drag.
require __DIR__.'/lib/bootstrap.php';
$f5=__DIR__.'/data/5min.json'; if(!is_file($f5)) exit("Run fetch_history.php first\n");
$c=json_decode(file_get_contents($f5),true); $a=atr_s($c); mt_srand(42);
foreach([[1.5,1.0],[2.5,1.0],[2.5,2.0]] as [$slm,$rr]){
  $tp=$sl=$tie=$exp=0; $n=0;
  for($k=0;$k<6000;$k++){
    $i=mt_rand(60,count($c)-300); $h=(int)gmdate('G',strtotime($c[$i]['t'])); if($h<7||$h>=21||$a[$i]<=0) continue;
    $m=mt_rand(0,1)?1:-1; $d=$slm*$a[$i]; $en=$c[$i]['c']; $stop=$en-$m*$d; $tgt=$en+$m*$d*$rr; $n++; $res='exp';
    for($j=$i+1;$j<=$i+48;$j++){
      $hs=$m>0?$c[$j]['l']<=$stop:$c[$j]['h']>=$stop; $ht=$m>0?$c[$j]['h']>=$tgt:$c[$j]['l']<=$tgt;
      if($hs&&$ht){ $res='tie'; break; } if($hs){ $res='sl'; break; } if($ht){ $res='tp'; break; }
    }
    if($res==='tp')$tp++; elseif($res==='sl')$sl++; elseif($res==='tie')$tie++; else $exp++;
  }
  printf("SL %.1f ATR, RR %.1f: %d random trades | TP first %.1f%% | SL first %.1f%% | same-candle both %.1f%% | timeout %.1f%%\n",$slm,$rr,$n,$tp/$n*100,$sl/$n*100,$tie/$n*100,$exp/$n*100);
}
echo "\nFair coin at RR 1: TP-first and SL-first should both be near 50%. A big 'same-candle both' share means the pessimistic tie rule drags every backtest result down.\n";