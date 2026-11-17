<?php
require __DIR__.'/../lib/bootstrap.php';
$t = (string)($_GET['token'] ?? ''); $ok = false;
if (preg_match('/^[a-f0-9]{64}$/', $t)) {
  $s = db()->prepare('SELECT user_id FROM email_tokens WHERE token_hash=? AND expires_at>NOW()');
  $s->execute([hash('sha256', $t)]);
  if ($uid = $s->fetchColumn()) {
    db()->prepare('UPDATE users SET email_verified_at=NOW() WHERE id=?')->execute([$uid]);
    db()->prepare('DELETE FROM email_tokens WHERE user_id=?')->execute([$uid]);
    $ok = true;
  }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Confirm email – XAU Terminal</title><link rel="stylesheet" href="auth.css"></head><body>
<div class="auth"><div class="brand"><div class="logo">Au</div><h1>XAU<b>/</b>USD Terminal</h1></div>
<div class="card">
<?php if ($ok): ?><div class="msg o">Email confirmed. Your account is active.</div><a href="login.php"><button class="btn" type="button">Sign in</button></a>
<?php else: ?><div class="msg e">This link is invalid or expired. Sign in to request a new one.</div><a href="login.php"><button class="btn ghost" type="button">Go to sign in</button></a><?php endif; ?>
</div></div></body></html>