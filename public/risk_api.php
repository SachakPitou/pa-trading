<?php
ini_set('display_errors', '0'); header('Content-Type: application/json');
require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/risk.php';
api_require('risk'); $u = user();
$uid = (int)$u['id']; $a = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') check_csrf();
try {
  switch ($a) {
    case 'state':   out(risk_state($uid));
    case 'save':    risk_save($uid, $_POST); out(risk_state($uid));
    case 'calc':    $st = risk_state($uid); out(risk_calc($_POST, $st['settings'], array_sum(array_column($st['positions'], 'risk'))));
    case 'pos_add': risk_pos_add($uid, $_POST); out(risk_state($uid));
    case 'pos_del': risk_pos_del($uid, (int)($_POST['id'] ?? 0)); out(risk_state($uid));
    default:        out(['error'=>'unknown action'], 404);
  }
} catch (InvalidArgumentException $e) { out(['error'=>$e->getMessage()], 422); }
  catch (Throwable $e) {
    $m = $e->getMessage(); error_log('risk_api: '.$m);
    out(['error'=>(stripos($m, "doesn't exist") !== false || stripos($m, 'not found') !== false)
      ? 'The risk tables are missing in the database. Run: php bin/migrate.php' : 'Server error: '.mb_substr($m, 0, 200)], 500);
  }