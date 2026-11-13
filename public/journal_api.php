<?php
require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/journal.php';
$u = user(); if (!$u) out(['error'=>'auth'], 401);
$a = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') check_csrf();
try {
  switch ($a) {
    case 'list':   out(journal_list((int)$u['id']));
    case 'shot':   journal_serve_shot((int)$u['id'], (int)($_GET['id'] ?? 0), (string)($_GET['k'] ?? ''));
    case 'add':    out(['ok'=>true, 'id'=>journal_add((int)$u['id'], $_POST)]);
    case 'delete': journal_delete((int)$u['id'], (int)($_POST['id'] ?? 0)); out(['ok'=>true]);
    default:       out(['error'=>'unknown action'], 404);
  }
} catch (InvalidArgumentException $e) { out(['error'=>$e->getMessage()], 422); }
  catch (Throwable $e) { out(['error'=>$e instanceof RuntimeException ? $e->getMessage() : 'Could not save the trade.'], 500); }