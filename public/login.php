<?php
require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/mailer.php';
if (user()) { header('Location: dashboard.php'); exit; }
$reg = isset($_GET['register']) && cfg('allow_register'); $err = ''; $ok = ''; $resendFor = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf(); $ip = $_SERVER['REMOTE_ADDR'] ?? ''; $now = time();
  db()->prepare('DELETE FROM login_attempts WHERE at<?')->execute([$now-900]);
  $c = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip=?'); $c->execute([$ip]);
  $email = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? '';
  $hit = fn() => db()->prepare('INSERT INTO login_attempts(ip,at) VALUES(?,?)')->execute([$ip, $now]);
  if ((int)$c->fetchColumn() >= 5) $err = 'Too many attempts. Try again in 15 minutes.';
  elseif (!empty($_POST['resend'])) {
    $hit();
    $s = db()->prepare('SELECT id,email_verified_at FROM users WHERE email=?'); $s->execute([$email]); $u = $s->fetch();
    if ($u && !$u['email_verified_at']) send_verification((int)$u['id'], $email);
    $ok = 'If that account exists and is unconfirmed, a new link was sent.';
  }
  elseif ($reg) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Invalid email.';
    elseif (strlen($pw) < 8) $err = 'Password must be at least 8 characters.';
    else try {
      $hit();
      db()->prepare('INSERT INTO users(email,pass_hash) VALUES(?,?)')->execute([$email, password_hash($pw, PASSWORD_DEFAULT)]);
      send_verification((int)db()->lastInsertId(), $email);
      $ok = 'Almost done! We sent a confirmation link to your email. Click it, then sign in.'; $reg = false;
    } catch (PDOException $e) { $err = 'That email is already registered.'; }
  } else {
    $s = db()->prepare('SELECT * FROM users WHERE email=?'); $s->execute([$email]); $u = $s->fetch();
    if ($u && password_verify($pw, $u['pass_hash'])) {
      if (!$u['email_verified_at']) { $err = 'Please confirm your email first.'; $resendFor = $email; }
      else { session_regenerate_id(true); $_SESSION['uid'] = (int)$u['id']; header('Location: '.(gate_redirect($u) ?? 'dashboard.php')); exit; }
    } else { $hit(); $err = 'Wrong email or password.'; }
  }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $reg ? 'Create account' : 'Sign in' ?> – XAU Terminal</title><link rel="stylesheet" href="auth.css"></head><body>
<div class="auth">
  <div class="brand"><div class="logo">Au</div><h1>XAU<b>/</b>USD Terminal</h1><p>Gold scalping signals &amp; risk tools</p></div>
  <div class="card">
    <?php if (cfg('allow_register')): ?>
    <div class="tabs"><a href="login.php" class="<?= $reg ? '' : 'on' ?>">Sign in</a><a href="?register=1" class="<?= $reg ? 'on' : '' ?>">Create account</a></div>
    <?php endif; ?>
    <?php if ($err): ?><div class="msg e"><?= h($err) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="msg o"><?= h($ok) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <div class="f"><label for="email">Email</label>
        <input id="email" type="email" name="email" placeholder="you@example.com" autocomplete="email" required autofocus></div>
      <div class="f"><label for="pw">Password</label>
        <input id="pw" type="password" name="password" placeholder="<?= $reg ? 'At least 8 characters' : 'Your password' ?>" autocomplete="<?= $reg ? 'new-password' : 'current-password' ?>" <?= $reg ? 'minlength="8"' : '' ?> required>
        <button type="button" class="eye" onclick="var p=document.getElementById('pw');var s=p.type==='password';p.type=s?'text':'password';this.textContent=s?'Hide':'Show'">Show</button></div>
      <?php if ($reg): ?><p class="hint">We'll email you a link to confirm your address.</p><?php endif; ?>
      <button class="btn"><?= $reg ? 'Create account' : 'Sign in' ?></button>
    </form>
    <?php if ($resendFor): ?>
    <form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>">
      <input type="hidden" name="email" value="<?= h($resendFor) ?>"><input type="hidden" name="resend" value="1">
      <button class="btn ghost">Resend confirmation email</button></form>
    <?php endif; ?>
  </div>
  <div class="foot">Trading involves risk. Signals are not financial advice.</div>
</div>
</body></html>