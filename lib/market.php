<?php
// Twelve Data proxy with DB cache (protects the free-plan rate limit: 8 req/min).
function cache_get(string $k,int $ttl){
  $s=db()->prepare('SELECT cval,updated FROM cache WHERE ckey=?'); $s->execute([$k]); $r=$s->fetch();
  return ($r && time()-(int)$r['updated']<$ttl) ? json_decode($r['cval'],true) : null;
}
function cache_set(string $k,$v): void { db()->prepare('REPLACE INTO cache(ckey,cval,updated) VALUES(?,?,?)')->execute([$k,json_encode($v),time()]); }
function api_used(): int { $s=db()->prepare('SELECT n FROM api_usage WHERE day=?'); $s->execute([gmdate('Y-m-d')]); return (int)$s->fetchColumn(); }
function api_bump(): void { db()->prepare('INSERT INTO api_usage(day,n) VALUES(?,1) ON DUPLICATE KEY UPDATE n=n+1')->execute([gmdate('Y-m-d')]); }
function http_json(string $url,?array $post=null){
  if(strpos($url,'twelvedata.com')!==false) api_bump();   // count every Twelve Data request (UTC day)
  $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>15,CURLOPT_USERAGENT=>'Mozilla/5.0 xau-terminal']);
  if($post!==null){ curl_setopt($ch,CURLOPT_POST,1); curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($post)); }
  $r=curl_exec($ch);
  if($r===false){ $GLOBALS['last_curl_error']=curl_error($ch); return null; }
  return json_decode($r,true);
}
function td(string $ep,array $q,int $ttl): array {
  $q['apikey']=cfg('td_key'); $key=substr($ep.md5(json_encode($q)),0,100);
  if(!empty($GLOBALS['web_mode'])){ $c=cache_get($key,10**9); return $c ?: ['error'=>'Waiting for the scanner to fetch data']; }   // website never spends API credits
  if($c=cache_get($key,$ttl)) return $c;
  if(api_used()>=(int)(cfg('daily_budget') ?? 600)) return ['error'=>'Daily API budget reached; resumes at 00:00 UTC'];            // stop before the provider's hard cap
  $d=http_json('https://api.twelvedata.com/'.$ep.'?'.http_build_query($q));
  if(!$d || ($d['status'] ?? '')==='error') return ['error'=>$d['message'] ?? ($GLOBALS['last_curl_error'] ?? 'Price feed unavailable')];
  cache_set($key,$d); return $d;
}
function get_price(): array {
  $d=td('price',['symbol'=>cfg('symbol')],10);
  return isset($d['price']) ? ['price'=>(float)$d['price']] : $d;
}
function get_candles(int $n=150,?string $interval=null,int $ttl=45): array {
  $d=td('time_series',['symbol'=>cfg('symbol'),'interval'=>$interval ?? cfg('interval'),'outputsize'=>$n,'timezone'=>'UTC'],$ttl);
  if(empty($d['values'])) return [];
  $v=array_reverse($d['values']); // oldest -> newest
  return array_map(fn($x)=>['t'=>$x['datetime'],'o'=>(float)$x['open'],'h'=>(float)$x['high'],'l'=>(float)$x['low'],'c'=>(float)$x['close']],$v);
}
