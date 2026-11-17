<?php
require __DIR__.'/../lib/bootstrap.php';
$t = (string)($_GET['token'] ?? $_POST['token'] ?? ''); $uid = 0; $err = ''; $done = false;
if (preg_match('/^[a-f0-9]{64}$/', $t)) {
  $s = db()->prepare('SELECT user_id FROM password_resets WHERE token_hash=? AND expires_at>NOW()');
  $s->execute([hash('sha256', $t)]); $uid = (int)$s->fetchColumn();
}
if ($uid && $_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf(); $p1 = $_POST['password'] ?? ''; $p2 = $_POST['password2'] ?? '';
  if (strlen($p1) < 8) $err = 'Password must be at least 8 characters.';
  elseif ($p1 !== $p2) $err = 'Passwords do not match.';
  else {
    db()->prepare('UPDATE users SET pass_hash=?, email_verified_at=COALESCE(email_verified_at,NOW()) WHERE id=?')
       ->execute([password_hash($p1, PASSWORD_DEFAULT), $uid]);
    db()->prepare('DELETE FROM password_resets WHERE user_id=?')->execute([$uid]);
    $done = true;
  }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="referrer" content="no-referrer"><title>Reset password – XAU Terminal</title><link rel="stylesheet" href="auth.css"></head><body>
<div class="auth"><div class="brand"><div class="logo">Au</div><h1>XAU<b>/</b>USD Terminal</h1><p>Choose a new password</p></div>
<div class="card">
<?php if ($done): ?><div class="msg o">Password updated. You can sign in now.</div><a href="login.php"><button class="btn" type="button">Sign in</button></a>
<?php elseif (!$uid): ?><div class="msg e">This link is invalid or has expired.</div><a href="forgot.php"><button class="btn ghost" type="button">Request a new link</button></a>
<?php else: ?>
<?php if ($err): ?><div class="msg e"><?= h($err) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="token" value="<?= h($t) ?>">
<div class="f"><label for="p1">New password</label><input id="p1" type="password" name="password" minlength="8" autocomplete="new-password" required autofocus></div>
<div class="f"><label for="p2">Confirm password</label><input id="p2" type="password" name="password2" minlength="8" autocomplete="new-password" required></div>
<button class="btn">Update password</button></form>
<?php endif; ?>
</div></div></body></html>