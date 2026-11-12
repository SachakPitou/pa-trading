<?php
function ema_s(array $a,int $n): array { if(!$a) return []; $k=2/($n+1); $e=$a[0]; $o=[]; foreach($a as $v){ $e=$v*$k+$e*(1-$k); $o[]=$e; } return $o; }
function rsi_s(array $a,int $n=14): array {
  $L=count($a); $o=array_fill(0,$L,50.0); if($L<=$n) return $o;
  $g=0.0; $l=0.0; for($i=1;$i<=$n;$i++){ $d=$a[$i]-$a[$i-1]; if($d>0)$g+=$d; else $l-=$d; } $g/=$n; $l/=$n;
  $o[$n]=$l==0?100.0:100-100/(1+$g/$l);
  for($i=$n+1;$i<$L;$i++){ $d=$a[$i]-$a[$i-1]; $g=($g*($n-1)+max($d,0))/$n; $l=($l*($n-1)+max(-$d,0))/$n; $o[$i]=$l==0?100.0:100-100/(1+$g/$l); }
  return $o;
}
function tr_s(array $c): array {
  $tr=[0.0]; for($i=1;$i<count($c);$i++) $tr[$i]=max($c[$i]['h']-$c[$i]['l'],abs($c[$i]['h']-$c[$i-1]['c']),abs($c[$i]['l']-$c[$i-1]['c'])); return $tr;
}
function atr_s(array $c,int $n=14): array {
  $L=count($c); $o=array_fill(0,$L,0.0); if($L<=$n) return $o; $tr=tr_s($c);
  $a=array_sum(array_slice($tr,1,$n))/$n; $o[$n]=$a; for($i=$n+1;$i<$L;$i++){ $a=($a*($n-1)+$tr[$i])/$n; $o[$i]=$a; } return $o;
}
function adx_s(array $c,int $n=14): array {
  $L=count($c); $adx=array_fill(0,$L,0.0); if($L<$n*2+2) return $adx;
  $tr=tr_s($c); $pdm=array_fill(0,$L,0.0); $mdm=array_fill(0,$L,0.0);
  for($i=1;$i<$L;$i++){ $up=$c[$i]['h']-$c[$i-1]['h']; $dn=$c[$i-1]['l']-$c[$i]['l'];
    $pdm[$i]=($up>$dn&&$up>0)?$up:0.0; $mdm[$i]=($dn>$up&&$dn>0)?$dn:0.0; }
  $st=array_sum(array_slice($tr,1,$n)); $sp=array_sum(array_slice($pdm,1,$n)); $sm=array_sum(array_slice($mdm,1,$n)); $dx=[];
  for($i=$n;$i<$L;$i++){
    if($i>$n){ $st=$st-$st/$n+$tr[$i]; $sp=$sp-$sp/$n+$pdm[$i]; $sm=$sm-$sm/$n+$mdm[$i]; }
    $pi=$st?100*$sp/$st:0; $mi=$st?100*$sm/$st:0; $dx[$i]=($pi+$mi)?100*abs($pi-$mi)/($pi+$mi):0;
  }
  $s=0; for($i=$n;$i<2*$n;$i++) $s+=$dx[$i]; $adx[2*$n-1]=$s/$n;
  for($i=2*$n;$i<$L;$i++) $adx[$i]=($adx[$i-1]*($n-1)+$dx[$i])/$n;
  return $adx;
}
function macd_h(array $cl): array {
  $f=ema_s($cl,12); $s=ema_s($cl,26); $m=[]; foreach($f as $i=>$v) $m[]=$v-$s[$i];
  $sg=ema_s($m,9); $h=[]; foreach($m as $i=>$v) $h[]=$v-$sg[$i]; return $h;
}
/** All indicators for a candle series (oldest -> newest). */
function prep(array $c): array {
  $cl=array_column($c,'c');
  return ['c'=>$cl,'e9'=>ema_s($cl,9),'e21'=>ema_s($cl,21),'e50'=>ema_s($cl,50),'rsi'=>rsi_s($cl),'atr'=>atr_s($c),'adx'=>adx_s($c),'mh'=>macd_h($cl)];
}
/** For each base candle, index of the latest higher-timeframe candle already CLOSED at that time (no look-ahead). */
function map_htf(array $c,array $H,int $barSecs=300,int $htfSecs=3600): array {
  $ht=array_map(fn($x)=>strtotime($x['t']),$H); $nH=count($H); $j=-1; $m=[];
  foreach($c as $i=>$x){ $close=strtotime($x['t'])+$barSecs; while($j+1<$nH && $ht[$j+1]+$htfSecs<=$close) $j++; $m[$i]=$j; }
  return $m;
}