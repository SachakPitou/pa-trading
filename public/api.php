<?php
require __DIR__.'/../lib/bootstrap.php';
$GLOBALS['web_mode']=true;
$u = user(); if (!$u) out(['error'=>'auth'], 401);
$a = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') check_csrf();
$in = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];

switch ($a) {
  case 'price':   out(get_price());
  case 'status': out(cache_get('last_scan',86400*30) ?: ['error'=>'Scanner has not run yet']);
  case 'analysis': out(analysis());
  case 'news': out(['events'=>array_values(array_filter(news_events(),fn($e)=>$e['t']>time()-3600 && in_array($e['impact'],['High','Medium'],true)))]);
  case 'signals':
    $t=tier_stats(); $rows=db()->query('SELECT * FROM signals ORDER BY id DESC LIMIT 80')->fetchAll();
    $rows=array_slice(array_values(array_filter($rows,fn($r)=>$t[$r['tier']]['visible'])),0,30);
    out(['signals'=>$rows,'tiers'=>$t]);
  case 'journal':
    $s = db()->prepare('SELECT * FROM journal WHERE user_id=? ORDER BY id DESC LIMIT 100'); $s->execute([$u['id']]);
    out(['trades'=>$s->fetchAll()]);
  case 'journal_add':
    $side = $in['side'] === 'SELL' ? 'SELL' : 'BUY'; $l = (float)$in['lots']; $e = (float)$in['entry']; $x = (float)$in['exit'];
    if ($l <= 0 || $e <= 0 || $x <= 0) out(['error'=>'Invalid values'], 422);
    $pnl = ($x-$e) * ($side==='BUY'?1:-1) * $l * 100;
    db()->prepare('INSERT INTO journal(user_id,side,lots,entry,exit_price,pnl,note) VALUES(?,?,?,?,?,?,?)')
       ->execute([$u['id'],$side,$l,$e,$x,$pnl,substr((string)($in['note'] ?? ''),0,255)]);
    out(['ok'=>true]);
  case 'journal_del':
    db()->prepare('DELETE FROM journal WHERE id=? AND user_id=?')->execute([(int)$in['id'],$u['id']]); out(['ok'=>true]);
  case 'settings':
    db()->prepare('UPDATE users SET telegram_chat_id=?,alert_telegram=?,alert_email=? WHERE id=?')
       ->execute([preg_replace('/[^0-9\-]/','',(string)($in['chat'] ?? '')) ?: null, !empty($in['tg'])?1:0, !empty($in['mail'])?1:0, $u['id']]);
    out(['ok'=>true]);
  case 'test_alert':
    $u = user(); $err = tg_send($u['telegram_chat_id'], 'Test alert from your XAU/USD terminal ✅');
    out($err ? ['error'=>$err] : ['ok'=>true]);
  case 'me': out(['email'=>$u['email'],'chat'=>$u['telegram_chat_id'],'tg'=>(bool)$u['alert_telegram'],'mail'=>(bool)$u['alert_email']]);
  default: out(['error'=>'unknown action'], 404);
}