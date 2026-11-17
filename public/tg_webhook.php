<?php
require __DIR__.'/../lib/bootstrap.php';
$secret = (string)cfg('tg_webhook_secret');
if ($secret === '' || !hash_equals($secret, (string)($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? ''))) { http_response_code(403); exit; }
$m = (json_decode(file_get_contents('php://input'), true) ?: [])['message'] ?? null;
if ($m && isset($m['text'], $m['chat']['id']) && ($m['chat']['type'] ?? '') === 'private') {
  $chat = (string)$m['chat']['id']; $txt = trim($m['text']);
  if (preg_match('/^\/start\s+([A-Za-z0-9_-]{16,64})$/', $txt, $x)) {
    $s = db()->prepare('SELECT user_id FROM telegram_links WHERE token_hash=? AND expires_at>NOW()');
    $s->execute([hash('sha256', $x[1])]);
    if ($uid = $s->fetchColumn()) {
      db()->prepare('UPDATE users SET telegram_chat_id=NULL, alert_telegram=0 WHERE telegram_chat_id=? AND id<>?')->execute([$chat, $uid]); // one chat = one account
      db()->prepare('UPDATE users SET telegram_chat_id=?, alert_telegram=1 WHERE id=?')->execute([$chat, $uid]);
      db()->prepare('DELETE FROM telegram_links WHERE user_id=?')->execute([$uid]);
      tg_send($chat, '✅ Connected! You will now get your signal alerts here. Send /stop to pause them.');
    } else tg_send($chat, 'This link has expired. Go back to your dashboard and press Connect Telegram again.');
  } elseif ($txt === '/stop') {
    db()->prepare('UPDATE users SET alert_telegram=0 WHERE telegram_chat_id=?')->execute([$chat]);
    tg_send($chat, 'Alerts paused. You can turn them back on from your dashboard.');
  } else tg_send($chat, 'Hi! To get alerts here, open your dashboard and press Connect Telegram.');
}
http_response_code(200); echo 'ok';