<?php
declare(strict_types=1);
date_default_timezone_set('UTC');
$GLOBALS['cfg'] = require __DIR__ . '/../config.php';
// Strategy settings are not secret, so they live in git (strategy.php) and win over any 'strategy' block in config.php.
if (is_file(__DIR__ . '/../strategy.php')) {
  $GLOBALS['cfg']['strategy'] = array_merge((array)($GLOBALS['cfg']['strategy'] ?? []), (array)require __DIR__ . '/../strategy.php');
}
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);
if (PHP_SAPI !== 'cli') session_start();
require __DIR__.'/market.php'; require __DIR__.'/indicators.php'; require __DIR__.'/news.php'; require __DIR__.'/signals.php'; require __DIR__.'/notify.php'; require __DIR__.'/stats.php'; require __DIR__.'/ai.php';

function cfg(string $k){ return $GLOBALS['cfg'][$k] ?? null; }
function db(): PDO {
  static $p; if(!$p){ $p=new PDO('mysql:host='.cfg('db_host').';dbname='.cfg('db_name').';charset=utf8mb4',
    cfg('db_user'),cfg('db_pass'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }
  return $p;
}
function user(): ?array {
  static $u=false;
  if($u===false){ $u=null; if(!empty($_SESSION['uid'])){ $s=db()->prepare('SELECT * FROM users WHERE id=?'); $s->execute([$_SESSION['uid']]); $u=$s->fetch()?:null; } }
  return $u;
}
function require_login(): void { if(!user()){ header('Location: login.php'); exit; } }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function check_csrf(): void {
  $t=$_SERVER['HTTP_X_CSRF'] ?? $_POST['csrf'] ?? '';
  if(!hash_equals(csrf(),(string)$t)){ http_response_code(403); exit('Bad CSRF token'); }
}
function h($s): string { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function out($d,int $c=200): void { http_response_code($c); header('Content-Type: application/json'); echo json_encode($d); exit; }