<?php
require __DIR__.'/../lib/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $_SESSION = [];
  if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']); }
  session_destroy(); header('Location: home.php'); exit;
}
if (!user()) { header('Location: home.php'); exit; }
// GET only asks for confirmation, so another website can't log you out with a hidden link.
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Log out – XAU Terminal</title><link rel="stylesheet" href="auth.css"></head><body>
<div class="auth"><div class="brand"><div class="logo">Au</div><h1>XAU<b>/</b>USD Terminal</h1></div>
<div class="card"><p style="margin-top:0">Log out of <b><?= h(user()['email']) ?></b>?</p>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><button class="btn">Log out</button></form>
<a href="dashboard.php"><button class="btn ghost" type="button">Cancel</button></a></div></div></body></html>