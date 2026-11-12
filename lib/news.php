<?php
// Free ForexFactory calendar JSON feed (Fair Economy). No key. Rate-limited, so cached 1 hour.
function news_events(): array {
  $c=cache_get('ff_news',3600); if($c!==null) return $c;
  $d=http_json('https://nfs.faireconomy.media/ff_calendar_thisweek.json'); $ev=[];
  if(is_array($d) && isset($d[0]['title'])){
    foreach($d as $e){ if(($e['country'] ?? '')!=='USD') continue; // gold reacts mainly to USD news
      $ev[]=['t'=>strtotime($e['date']),'title'=>$e['title'],'impact'=>$e['impact'] ?? '','forecast'=>$e['forecast'] ?? '','previous'=>$e['previous'] ?? '']; }
    usort($ev,fn($a,$b)=>$a['t']<=>$b['t']);
  }
  cache_set('ff_news',$ev); return $ev;
}
/** Title of a High-impact USD event within +-$mins of $ts, else null. */
function news_blocked(int $ts,int $mins): ?string {
  foreach(news_events() as $e) if($e['impact']==='High' && abs($ts-$e['t'])<=$mins*60) return $e['title'];
  return null;
}
function next_news(): ?array {
  foreach(news_events() as $e) if($e['impact']==='High' && $e['t']>time()-1800) return ['title'=>$e['title'],'mins'=>(int)round(($e['t']-time())/60)];
  return null;
}