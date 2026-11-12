<?php
// php fetch_history.php [batches] [force]   (default 6 batches of 5000 five-minute candles = about 100 days)
require __DIR__.'/lib/bootstrap.php';
$batches=6; foreach(array_slice($argv,1) as $a) if(ctype_digit($a)) $batches=max(1,(int)$a);
$force=in_array('force',$argv,true);
if(!is_dir(__DIR__.'/data')) mkdir(__DIR__.'/data');
$f5=__DIR__.'/data/5min.json'; $f1=__DIR__.'/data/1h.json';

function fetch_all(string $interval,int $batches,int $pause): array {
  $all=[]; $end=null;
  for($b=1;$b<=$batches;$b++){
    $q=['symbol'=>cfg('symbol'),'interval'=>$interval,'outputsize'=>5000,'timezone'=>'UTC','apikey'=>cfg('td_key')]; if($end) $q['end_date']=$end;
    for($try=0;$try<3;$try++){
      $d=http_json('https://api.twelvedata.com/time_series?'.http_build_query($q));
      if(!empty($d['values'])) break;
      if(stripos($d['message'] ?? '','minute')!==false){ echo "  per-minute limit reached, waiting 65s...\n"; sleep(65); continue; }
      break;
    }
    if(empty($d['values'])){ echo "  batch $b stopped: ".($d['message'] ?? ($GLOBALS['last_curl_error'] ?? 'no data'))."\n"; break; }
    $v=$d['values']; $before=count($all);
    foreach($v as $x) $all[$x['datetime']]=['t'=>$x['datetime'],'o'=>(float)$x['open'],'h'=>(float)$x['high'],'l'=>(float)$x['low'],'c'=>(float)$x['close']];
    $oldest=$v[count($v)-1]['datetime']; echo "  batch $b: ".count($v)." candles, back to $oldest\n";
    if(count($v)<5000 || count($all)===$before) break;
    $end=$oldest; if($b<$batches) sleep($pause);
  }
  ksort($all); return array_values($all);
}

if(!$force && is_file($f5)){ echo "data/5min.json already exists, skipping 5-minute download (add 'force' to redo it)\n"; $c=json_decode(file_get_contents($f5),true); }
else {
  echo "5-minute candles:\n"; $c=fetch_all(cfg('interval'),$batches,20);
  if(count($c)<500) exit("Not enough 5-minute data. Nothing saved.\n");
  file_put_contents($f5,json_encode($c)); echo "  saved ".count($c)." candles ({$c[0]['t']} -> {$c[count($c)-1]['t']})\n";
  echo "Waiting 65s so the per-minute limit resets...\n"; sleep(65);
}
echo "1-hour candles:\n"; $h=fetch_all('1h',1,0);
if(count($h)<100) exit("1-hour download failed. Run the same command again; the 5-minute file is kept and only the 1-hour part is retried.\n");
file_put_contents($f1,json_encode($h));
printf("\nDone: %d x 5m, %d x 1h saved in data/\nNow run: php backtest.php\n",count($c),count($h));