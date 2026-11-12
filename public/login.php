<?php
require __DIR__.'/../lib/bootstrap.php';
if (user()) { header('Location: dashboard.php'); exit; }
$reg = isset($_GET['register']) && cfg('allow_register'); $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf(); $ip = $_SERVER['REMOTE_ADDR'] ?? ''; $now = time();
  db()->prepare('DELETE FROM login_attempts WHERE at<?')->execute([$now-900]);
  $c = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip=?'); $c->execute([$ip]);
  $email = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? '';
  if ((int)$c->fetchColumn() >= 5) $err = 'Too many attempts. Try again in 15 minutes.';
  elseif ($reg) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Invalid email.';
    elseif (strlen($pw) < 8) $err = 'Password must be at least 8 characters.';
    else try {
      db()->prepare('INSERT INTO users(email,pass_hash) VALUES(?,?)')->execute([$email, password_hash($pw, PASSWORD_DEFAULT)]);
      session_regenerate_id(true); $_SESSION['uid'] = (int)db()->lastInsertId(); header('Location: dashboard.php'); exit;
    } catch (PDOException $e) { $err = 'That email is already registered.'; }
  } else {
    $s = db()->prepare('SELECT * FROM users WHERE email=?'); $s->execute([$email]); $u = $s->fetch();
    if ($u && password_verify($pw, $u['pass_hash'])) { session_regenerate_id(true); $_SESSION['uid'] = (int)$u['id']; header('Location: dashboard.php'); exit; }
    db()->prepare('INSERT INTO login_attempts(ip,at) VALUES(?,?)')->execute([$ip, $now]); $err = 'Wrong email or password.';
  }
}
?><!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $reg ? 'Register' : 'Login' ?> – XAU Terminal</title>
<style>body{margin:0;background:#0c0e12;color:#e8eaed;font:15px system-ui;display:grid;place-items:center;min-height:100vh}
form{background:#14171d;border:1px solid #242933;border-radius:12px;padding:28px;width:320px}h1{font-size:18px;margin:0 0 16px}
h1 b{color:#f0b429}input{width:100%;padding:10px;margin:6px 0 12px;background:#0c0e12;border:1px solid #242933;border-radius:6px;color:inherit;box-sizing:border-box}
button{width:100%;padding:10px;background:#f0b429;border:0;border-radius:6px;font-weight:700;cursor:pointer}.e{color:#ff5c75;margin-bottom:10px}a{color:#f0b429}p{font-size:13px;color:#8b93a1}</style></head><body>
<form method="post"><h1>XAU<b>/</b>USD Terminal · <?= $reg ? 'Create account' : 'Sign in' ?></h1>
<?php if ($err): ?><div class="e"><?= h($err) ?></div><?php endif; ?>
<input type="hidden" name="csrf" value="<?= csrf() ?>">
<input type="email" name="email" placeholder="Email" required autofocus>
<input type="password" name="password" placeholder="Password" required>
<button>Continue</button>
<p><?php if ($reg): ?>Have an account? <a href="login.php">Sign in</a><?php elseif (cfg('allow_register')): ?>New here? <a href="?register=1">Create account</a><?php endif; ?></p>
</form></body></html>
