<?php
// Adds (or removes) SAMPLE journal trades so you can test the journal, its statistics and the CSV downloads.
//   php bin/seed_journal.php you@example.com            add about 40 sample trades over the last 5 weeks
//   php bin/seed_journal.php you@example.com --clear    remove only the sample trades (their note starts with [TEST])
// Use it on your local copy, not on the live site: sample trades count in the Risk page's daily loss and loss-streak checks.
require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/journal.php';
$args = array_slice($argv, 1); $clear = in_array('--clear', $args, true);
$email = current(array_filter($args, fn($a) => $a !== '--clear')) ?: null;
if ($email) { $s = db()->prepare('SELECT id FROM users WHERE email=?'); $s->execute([$email]); } else $s = db()->query('SELECT id FROM users LIMIT 2');
$ids = array_column($s->fetchAll(), 'id');
if (count($ids) !== 1) exit($email ? "No user with e-mail $email\n" : "Please give your login e-mail:  php bin/seed_journal.php you@example.com\n");
$uid = (int)$ids[0];

if ($clear) { $d = db()->prepare("DELETE FROM journal WHERE user_id=? AND note LIKE '[TEST]%'"); $d->execute([$uid]); exit("Removed {$d->rowCount()} sample trades.\n"); }

mt_srand(2026);                                                          // same sample data every time
$off = (int)(strat()['tz_offset'] ?? 7)*3600; $now = time(); $todayStart = intdiv($now + $off, 86400)*86400 - $off;
$rules = array_keys(journal_rules());
$ins = db()->prepare('INSERT INTO journal(user_id,side,lots,entry,exit_price,pnl,note,created_at) VALUES(?,?,?,?,?,?,?,FROM_UNIXTIME(?))');
$insX = db()->prepare('INSERT INTO journal_extra(journal_id,sl,tp,planned_r,actual_r,entry_reason,exit_reason,emotion,discipline,notes,rules) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
$n = 0; db()->beginTransaction();
for ($d = 0; $d < 35; $d++) {
  $dayStart = $todayStart - $d*86400;
  if ($d > 0 && (int)gmdate('N', $dayStart + $off) >= 6) continue;     // no weekend trading (today is always included, so there is something to download)
  $count = $d === 0 ? 2 : mt_rand(1, 3);
  for ($i = 0; $i < $count; $i++) {
    $secs = $d === 0 ? mt_rand(0, max(60, min(86000, $now - $dayStart - 60))) : mt_rand(9*3600, 23*3600);
    $dir = mt_rand(0, 1) ? 1 : -1; $side = $dir > 0 ? 'BUY' : 'SELL'; $lots = [0.05, 0.10, 0.10, 0.20][mt_rand(0, 3)];
    $e = round(2600 + mt_rand(0, 8000)/100, 2); $risk = mt_rand(40, 90)/10; $rr = [1.5, 2.0, 2.0, 3.0][mt_rand(0, 3)];
    $sl = round($e - $dir*$risk, 2); $tp = round($e + $dir*$risk*$rr, 2);
    $win = mt_rand(1, 100) <= 55;
    $R = $win ? (mt_rand(1, 100) <= 60 ? $rr : mt_rand(50, (int)(($rr - 0.2)*100))/100) : (mt_rand(1, 100) <= 75 ? -1.0 : -mt_rand(40, 95)/100);
    $x = round($e + $dir*$risk*$R, 2); $pnl = ($x - $e)*$dir*$lots*100; $actualR = round($dir*($x - $e)/$risk, 2);
    $ticked = []; foreach ($rules as $k) $ticked[$k] = mt_rand(1, 100) <= ($win ? 88 : 62);
    $emo = $win ? ['Calm', 'Confident', 'Calm', 'Greedy'][mt_rand(0, 3)] : ['Anxious', 'FOMO', 'Revenge', 'Tired', 'Calm', 'Bored'][mt_rand(0, 5)];
    $dis = max(1, min(5, (int)round(count(array_filter($ticked))/count($ticked)*5)));
    $note = '[TEST] Sample trade for testing';
    $ins->execute([$uid, $side, $lots, $e, $x, $pnl, $note, $dayStart + $secs]); $id = (int)db()->lastInsertId();
    $insX->execute([$id, $sl, $tp, round($rr, 2), $actualR, "Scanner $side signal, trend aligned (sample)", $win ? 'Target reached (sample)' : 'Stop loss hit (sample)', $emo, $dis, $note, json_encode($ticked)]);
    $n++;
  }
}
db()->commit();
echo "Added $n sample trades over the last 5 weeks (weekdays, plus 2 today).\nRemove them any time with:  php bin/seed_journal.php ".($email ?: 'you@example.com')." --clear\n";