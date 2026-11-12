<?php
function tg_send(?string $chat,string $text): ?string {
  $t=cfg('tg_token');
  if(!$t) return 'tg_token is empty in config.php';
  if(!$chat) return 'No chat ID saved';
  $d=http_json("https://api.telegram.org/bot$t/sendMessage",['chat_id'=>$chat,'text'=>$text]);
  if(!$d) return 'Network/SSL error: '.($GLOBALS['last_curl_error'] ?? 'unknown');
  return !empty($d['ok']) ? null : ($d['description'] ?? 'Telegram error');
}
function alert_user(array $u,string $subject,string $text): void {
  if($u['alert_telegram']) tg_send($u['telegram_chat_id'],$text);
  if($u['alert_email']) @mail($u['email'],$subject,$text,'From: '.cfg('mail_from'));
}
function alert_all(string $subject,string $text): void {
  foreach(db()->query('SELECT * FROM users WHERE alert_telegram=1 OR alert_email=1')->fetchAll() as $u) alert_user($u,$subject,$text);
}
