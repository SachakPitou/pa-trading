<?php
require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/risk.php';
$u = user(); if (!$u) out(['error'=>'auth'], 401);
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
  catch (Throwable $e) { out(['error'=>'Could not complete the request.'], 500); }