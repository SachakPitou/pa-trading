<?php
require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/mailer.php';
if (user()) { header('Location: dashboard.php'); exit; }
$ok = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf(); $ip = $_SERVER['REMOTE_ADDR'] ?? ''; $now = time();
  db()->prepare('DELETE FROM login_attempts WHERE at<?')->execute([$now-900]);
  $c = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip=?'); $c->execute([$ip]);
  if ((int)$c->fetchColumn() >= 5) $err = 'Too many attempts. Try again in 15 minutes.';
  else {
    db()->prepare('INSERT INTO login_attempts(ip,at) VALUES(?,?)')->execute([$ip, $now]);
    $email = strtolower(trim($_POST['email'] ?? ''));
    $s = db()->prepare('SELECT id FROM users WHERE email=?'); $s->execute([$email]);
    if ($uid = $s->fetchColumn()) {
      $token = bin2hex(random_bytes(32));
      db()->prepare('DELETE FROM password_resets WHERE user_id=?')->execute([$uid]);
      db()->prepare('INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 1 HOUR))')->execute([$uid, hash('sha256', $token)]);
      send_mail($email, 'Reset your password - XAU Terminal',
        "We got a request to reset your password. This link works once and expires in 1 hour:\n\n".app_url().'/reset.php?token='.$token."\n\nIf you didn't ask for this, ignore this email. Your password won't change.");
    }
    $ok = 'If that email has an account, we sent a reset link. It expires in 1 hour.';
  }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot password – XAU Terminal</title><link rel="stylesheet" href="auth.css"></head><body>
<div class="auth"><div class="brand"><div class="logo">Au</div><h1>XAU<b>/</b>USD Terminal</h1><p>Reset your password</p></div>
<div class="card">
<?php if ($err): ?><div class="msg e"><?= h($err) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="msg o"><?= h($ok) ?></div><a href="login.php"><button class="btn ghost" type="button">Back to sign in</button></a>
<?php else: ?>
<form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>">
<div class="f"><label for="email">Email</label><input id="email" type="email" name="email" placeholder="you@example.com" required autofocus></div>
<button class="btn">Send reset link</button></form>
<div class="foot"><a href="login.php">Back to sign in</a></div>
<?php endif; ?>
</div></div></body></html>