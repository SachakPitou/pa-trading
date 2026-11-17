<?php
declare(strict_types=1);
date_default_timezone_set('UTC');
$GLOBALS['cfg'] = require __DIR__ . '/../config.php';
// Strategy settings are not secret, so they live in git (strategy.php) and win over any 'strategy' block in config.php.
if (is_file(__DIR__ . '/../strategy.php')) {
  $GLOBALS['cfg']['strategy'] = array_merge((array)($GLOBALS['cfg']['strategy'] ?? []), (array)require __DIR__ . '/../strategy.php');
}
if (PHP_SAPI !== 'cli') {
  ini_set('session.use_strict_mode', '1'); ini_set('session.use_only_cookies', '1');
  session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);
  session_start();
  if (!empty($_SESSION['uid'])) {                       // idle 8h or total 7 days => signed out
    $now = time(); $_SESSION['born'] ??= $now;
    if ($now - ($_SESSION['last'] ?? $now) > 8*3600 || $now - $_SESSION['born'] > 7*86400) { $_SESSION = []; session_regenerate_id(true); }
    else { $_SESSION['last'] = $now; header('Cache-Control: private, no-store'); }
  }
}
require __DIR__.'/market.php'; require __DIR__.'/indicators.php'; require __DIR__.'/news.php'; require __DIR__.'/signals.php'; require __DIR__.'/notify.php'; require __DIR__.'/stats.php'; require __DIR__.'/ai.php'; require __DIR__.'/health.php'; require_once __DIR__.'/nav.php'; require_once __DIR__.'/access.php';

function cfg(string $k){ return $GLOBALS['cfg'][$k] ?? null; }
function db(): PDO {
  static $p; if(!$p){ $p=new PDO('mysql:host='.cfg('db_host').';dbname='.cfg('db_name').';charset=utf8mb4',
    cfg('db_user'),cfg('db_pass'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }
  return $p;
}
function user(): ?array {
  static $u=false;
  if($u===false){ $u=null; if(!empty($_SESSION['uid'])){ $s=db()->prepare('SELECT * FROM users WHERE id=?'); $s->execute([$_SESSION['uid']]); $u=$s->fetch()?:null;
    if ($u && ((int)($_SESSION['sv'] ?? 0) !== (int)($u['sess_ver'] ?? 0) || !empty($u['disabled_at']))) { $u=null; $_SESSION=[]; } } }   // password changed elsewhere
  return $u;
}
function is_admin(): bool { $u=user(); return $u && ($u['role'] ?? 'user') === 'admin'; }
function require_login(): void { if(!user()){ header('Location: login.php'); exit; } }
function require_admin(): void {
  if(!user()){ header('Location: login.php'); exit; }
  if(!is_admin()){ http_response_code(403); exit('Forbidden: admin only'); }
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function check_csrf(): void {
  $t=$_SERVER['HTTP_X_CSRF'] ?? $_POST['csrf'] ?? '';
  if(!hash_equals(csrf(),(string)$t)){ http_response_code(403); exit('Bad CSRF token'); }
}
function h($s): string { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function out($d,int $c=200): void { http_response_code($c); header('Content-Type: application/json'); echo json_encode($d); exit; }