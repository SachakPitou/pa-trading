<?php
// php diag.php  -> why are there no signals? Shows current bar status + which filter is the bottleneck.
require __DIR__.'/lib/bootstrap.php';
$c=get_candles(5000,null,3600); $H=get_candles(900,'1h',3600);
if(count($c)<500||count($H)<100) exit("Feed error: ".($GLOBALS['last_curl_error'] ?? 'not enough candles')."\n");
$P=prep($c); $HP=prep($H); $map=map_htf($c,$H); $o=strat(); $L=count($c)-1; $i=$L-1; // last CLOSED bar
echo "Last closed candle: {$c[$i]['t']} UTC   (now: ".gmdate('Y-m-d H:i:s')." UTC)\n\n";

// 1) State of the latest bar against each hard filter
$h=(int)gmdate('G',strtotime($c[$i]['t'])); $a=$P['atr'][$i]; $avg=array_sum(array_slice($P['atr'],$i-49,50))/50;
$e9=$P['e9'];$e21=$P['e21'];$e50=$P['e50']; $x=$c[$i]; $cl=$P['c'][$i];
$bx=$e9[$i-1]<=$e21[$i-1]&&$e9[$i]>$e21[$i]; $sx=$e9[$i-1]>=$e21[$i-1]&&$e9[$i]<$e21[$i];
$bp=$e9[$i]>$e21[$i]&&$e21[$i]>$e50[$i]&&$x['l']<=$e21[$i]+0.1*$a&&$cl>$e21[$i]&&$cl>$x['o'];
$sp=$e9[$i]<$e21[$i]&&$e21[$i]<$e50[$i]&&$x['h']>=$e21[$i]-0.1*$a&&$cl<$e21[$i]&&$cl<$x['o'];
$hi=$map[$i]; $ht=($HP['c'][$hi]>$HP['e50'][$hi]&&$HP['e21'][$hi]>$HP['e50'][$hi])?'bullish':(($HP['c'][$hi]<$HP['e50'][$hi]&&$HP['e21'][$hi]<$HP['e50'][$hi])?'bearish':'neutral');
$ok=fn($b)=>$b?'PASS':'FAIL';
printf("Session %02d:00 UTC (need %d-%d)        %s\n",$h,$o['sess_from'],$o['sess_to'],$ok($h>=$o['sess_from']&&$h<$o['sess_to']));
printf("ADX %.1f (need >= %s)                  %s\n",$P['adx'][$i],$o['adx_min'],$ok($P['adx'][$i]>=$o['adx_min']));
printf("ATR %.2f vs 50-bar avg %.2f (>= 60%%)   %s\n",$a,$avg,$ok($a>=0.6*$avg));
printf("Trigger (cross/pullback) now           %s\n",($bx||$sx||$bp||$sp)?'PASS ('.(($bx||$bp)?'BUY':'SELL').')':'FAIL: no EMA cross or pullback on this candle');
printf("1H trend: %s   RSI %.1f   MACD hist %+.3f\n",$ht,$P['rsi'][$i],$P['mh'][$i]);
$open=db()->query("SELECT id,side,candle_time FROM signals WHERE status='OPEN'")->fetchAll();
echo $open?"BLOCKED: open signal(s) exist: ".json_encode($open)."\n":"No open signal blocking.\n";

// 2) Bottleneck test: how many raw setups over the whole history if one filter is removed
$days=round(($L-60)*5/60/24,1); echo "\nRaw setups over $days days of history (before the one-at-a-time rule):\n";
$tests=['current settings'=>[],'without score limit'=>['min_score'=>0],'without ADX filter'=>['adx_min'=>0],'without 1H trend filter'=>['htf_filter'=>false],
  'without session filter'=>['sess_from'=>0,'sess_to'=>24],'score+ADX+1H all off'=>['min_score'=>0,'adx_min'=>0,'htf_filter'=>false]];
foreach($tests as $name=>$ch){ $oo=array_merge($o,$ch); $n=0; $last='-';
  for($k=60;$k<$L;$k++) if(evaluate($c,$P,$k,$HP,$map[$k],$oo)){ $n++; $last=$c[$k]['t']; }
  printf("  %-26s %4d setups (%.1f/day)  last: %s\n",$name,$n,$n/max($days,1),$last); }
echo "\nThe biggest jump in setups shows the filter that blocks the most.\n";