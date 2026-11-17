<?php
require __DIR__.'/../lib/bootstrap.php';
require_admin(); $me = user(); $myId = (int)$me['id'];
$q = trim((string)($_GET['q'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf(); $id = (int)($_POST['id'] ?? 0); $act = (string)($_POST['act'] ?? ''); $msg = '';
  $s = db()->prepare('SELECT * FROM users WHERE id=?'); $s->execute([$id]); $t = $s->fetch();
  $admins = (int)db()->query("SELECT COUNT(*) FROM users WHERE role='admin' AND disabled_at IS NULL")->fetchColumn();
  if (!$t) $msg = 'User not found.';
  elseif ($id === $myId) $msg = 'You can\'t change your own account here.';
  elseif (in_array($act, ['make_user', 'disable'], true) && $t['role'] === 'admin' && $admins <= 1) $msg = 'That is the last active admin.';
  else {
    $sql = [
      'make_admin' => 'UPDATE users SET role=\'admin\' WHERE id=?',
      'make_user'  => 'UPDATE users SET role=\'user\' WHERE id=?',
      'disable'    => 'UPDATE users SET disabled_at=NOW(), sess_ver=sess_ver+1 WHERE id=?',
      'enable'     => 'UPDATE users SET disabled_at=NULL WHERE id=?',
      'signout'    => 'UPDATE users SET sess_ver=sess_ver+1 WHERE id=?',
      'verify'     => 'UPDATE users SET email_verified_at=COALESCE(email_verified_at,NOW()) WHERE id=?',
    ][$act] ?? null;
    if ($sql) { db()->prepare($sql)->execute([$id]); $msg = 'Done: '.$act.' for '.$t['email']; } else $msg = 'Unknown action.';
  }
  $_SESSION['flash'] = $msg; header('Location: admin.php'.($q !== '' ? '?q='.urlencode($q) : '')); exit;
}
$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$st = db()->query("SELECT COUNT(*) n, SUM(email_verified_at IS NOT NULL) v, SUM(role='admin') a, SUM(telegram_chat_id IS NOT NULL) tg, SUM(disabled_at IS NOT NULL) d FROM users")->fetch();
$sql = 'SELECT id,email,role,email_verified_at,disabled_at,telegram_chat_id,alert_telegram,alert_email,created_at FROM users'; $args = [];
if ($q !== '') { $sql .= ' WHERE email LIKE ?'; $args[] = '%'.addcslashes($q, '%_\\').'%'; }
$st2 = db()->prepare($sql.' ORDER BY id DESC LIMIT 100'); $st2->execute($args); $rows = $st2->fetchAll();
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin – XAU Terminal</title>
<style>:root{--bg:#0c0e12;--card:#14171d;--bd:#242933;--tx:#e8eaed;--mu:#8b93a1;--ac:#f0b429;--up:#2ed39b;--dn:#ff5c75;--in:#0c0e12}
body{margin:0;background:var(--bg);color:var(--tx);font:14px system-ui,sans-serif}.mu{color:var(--mu)}
header{display:flex;align-items:center;gap:20px;padding:10px 16px;border-bottom:1px solid var(--bd);background:var(--card)}
.logo b{color:var(--ac)}.wrap{max-width:1100px;margin:0 auto;padding:16px}
.kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:14px}.kpi{background:var(--card);border:1px solid var(--bd);border-radius:12px;padding:12px}
.kpi b{display:block;font-size:22px}.card{background:var(--card);border:1px solid var(--bd);border-radius:12px;padding:14px}
input,select,button{font:inherit;color:var(--tx);background:var(--in);border:1px solid var(--bd);border-radius:6px;padding:7px 10px}button{cursor:pointer}button.p{background:var(--ac);color:#111;border-color:var(--ac);font-weight:600}
table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:9px 8px;border-bottom:1px solid var(--bd);white-space:nowrap}th{font-size:11px;text-transform:uppercase;letter-spacing:.6px;color:var(--mu)}
.b{padding:2px 8px;border-radius:10px;font-size:12px;border:1px solid var(--bd)}.ok{color:var(--up)}.bad{color:var(--dn)}.adm{color:var(--ac);border-color:rgba(240,180,41,.5)}
.flash{background:rgba(240,180,41,.1);border:1px solid rgba(240,180,41,.4);border-radius:8px;padding:10px;margin-bottom:12px}
@media(max-width:800px){.kpis{grid-template-columns:repeat(2,1fr)}}</style></head><body>
<header><a class="logo" href="dashboard.php" style="text-decoration:none;color:inherit">XAU<b>/</b>USD <span class="mu">Admin</span></a><?= nav('admin.php') ?><span style="margin-left:auto"></span><?= user_menu() ?></header>
<div class="wrap">
<?php if ($flash): ?><div class="flash"><?= h($flash) ?></div><?php endif; ?>
<div class="kpis"><?php foreach ([['Users',$st['n']],['Verified',$st['v']],['Admins',$st['a']],['Telegram linked',$st['tg']],['Disabled',$st['d']]] as [$k,$v]): ?>
<div class="kpi"><span class="mu"><?= $k ?></span><b><?= (int)$v ?></b></div><?php endforeach; ?></div>
<div class="card">
<form method="get" style="display:flex;gap:8px;margin-bottom:12px"><input name="q" value="<?= h($q) ?>" placeholder="Search by email" style="flex:1"><button class="p">Search</button><?php if ($q !== ''): ?><a href="admin.php"><button type="button">Clear</button></a><?php endif; ?></form>
<div style="overflow-x:auto"><table><tr><th>Email</th><th>Role</th><th>Status</th><th>Telegram</th><th>Joined</th><th>Action</th></tr>
<?php foreach ($rows as $r): $self = (int)$r['id'] === $myId; ?>
<tr><td><?= h($r['email']) ?><?= $self ? ' <span class="mu">(you)</span>' : '' ?></td>
<td><span class="b <?= $r['role'] === 'admin' ? 'adm' : '' ?>"><?= h($r['role']) ?></span></td>
<td><?php if ($r['disabled_at']): ?><span class="b bad">Disabled</span><?php elseif ($r['email_verified_at']): ?><span class="b ok">Verified</span><?php else: ?><span class="b">Unverified</span><?php endif; ?></td>
<td><?= $r['telegram_chat_id'] ? ($r['alert_telegram'] ? '<span class="ok">On</span>' : 'Linked, off') : '<span class="mu">–</span>' ?></td>
<td class="mu"><?= h(substr((string)$r['created_at'], 0, 10)) ?></td>
<td><?php if ($self): ?><span class="mu">–</span><?php else: ?>
<form method="post" data-email="<?= h($r['email']) ?>" onsubmit="return confirm(this.act.selectedOptions[0].text+' for '+this.dataset.email+'?')" style="display:flex;gap:6px">
<input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
<select name="act"><?php if ($r['role'] === 'admin'): ?><option value="make_user">Make user</option><?php else: ?><option value="make_admin">Make admin</option><?php endif; ?>
<?php if ($r['disabled_at']): ?><option value="enable">Enable account</option><?php else: ?><option value="disable">Disable account</option><?php endif; ?>
<option value="signout">Sign out everywhere</option><?php if (!$r['email_verified_at']): ?><option value="verify">Mark email verified</option><?php endif; ?></select>
<button>Apply</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></table></div>
<div class="mu" style="margin-top:10px">Showing <?= count($rows) ?> user<?= count($rows) === 1 ? '' : 's' ?> (newest first, max 100).</div>
</div></div></body></html>