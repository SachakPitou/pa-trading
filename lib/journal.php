<?php
// Advanced trading journal: reasons, emotions, planned vs actual R, rule adherence and screenshots.
// Screenshots are stored in data/journal/ (never inside public/) and served only to their owner by journal_api.php.

function journal_rules(): array { return [
  'signal'=>'Took a valid scanner signal (no gut-feel trade)', 'window'=>'Traded inside my trading window',
  'price'=>'Entered at the planned price (did not chase)', 'stop'=>'Kept the planned stop loss (did not widen or remove it)',
  'size'=>'Position size matched my risk %', 'news'=>'Avoided high-impact news time',
  'exit'=>'Exited by plan (TP, SL or time), no panic or greed', 'revenge'=>'No revenge or extra trades afterwards']; }
function journal_emotions(): array { return ['Calm','Confident','Anxious','FOMO','Greedy','Fearful','Revenge','Bored','Tired']; }

function journal_dir(): string {
  $d = __DIR__.'/../data/journal';
  if (!is_dir($d) && !@mkdir($d, 0775, true)) throw new RuntimeException('Cannot create the data/journal folder (check that data/ is writable by the web server).');
  return $d;
}

function journal_save_shot(string $field): ?string {
  if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
  $f = $_FILES[$field];
  if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException("Upload failed for $field (code {$f['error']}). The image may be too large.");
  if ($f['size'] > 4*1024*1024) throw new RuntimeException('Screenshots must be under 4 MB.');
  $info = @getimagesize($f['tmp_name']);
  $ext = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'][$info['mime'] ?? ''] ?? null;
  if (!$ext) throw new RuntimeException('Screenshots must be JPG, PNG or WebP images.');
  $name = bin2hex(random_bytes(16)).'.'.$ext;
  if (!move_uploaded_file($f['tmp_name'], journal_dir().'/'.$name)) throw new RuntimeException('Could not save the screenshot (is data/journal writable?).');
  return $name;
}

function journal_add(int $uid, array $in): int {
  $side = ($in['side'] ?? '') === 'SELL' ? 'SELL' : 'BUY'; $dir = $side === 'BUY' ? 1 : -1;
  $lots = (float)($in['lots'] ?? 0); $e = (float)($in['entry'] ?? 0); $x = (float)($in['exit'] ?? 0);
  $sl = ($in['sl'] ?? '') !== '' ? (float)$in['sl'] : null; $tp = ($in['tp'] ?? '') !== '' ? (float)$in['tp'] : null;
  if ($lots <= 0 || $e <= 0 || $x <= 0) throw new InvalidArgumentException('Lots, entry and exit must be positive numbers.');
  if ($sl !== null && ($sl <= 0 || $dir*($e-$sl) <= 0)) throw new InvalidArgumentException('Planned stop loss must be '.($dir>0?'below':'above').' the entry for a '.$side.'.');
  if ($tp !== null && ($tp <= 0 || $dir*($tp-$e) <= 0)) throw new InvalidArgumentException('Planned target must be '.($dir>0?'above':'below').' the entry for a '.$side.'.');
  $risk = $sl !== null ? abs($e-$sl) : 0;
  $plannedR = ($risk > 0 && $tp !== null) ? round($dir*($tp-$e)/$risk, 2) : null;
  $actualR = $risk > 0 ? round($dir*($x-$e)/$risk, 2) : null;
  $pnl = ($x-$e) * $dir * $lots * 100;
  $emo = in_array($in['emotion'] ?? '', journal_emotions(), true) ? $in['emotion'] : null;
  $dis = (int)($in['discipline'] ?? 0); $dis = ($dis >= 1 && $dis <= 5) ? $dis : null;
  $txt = fn($k, $n) => ($v = trim((string)($in[$k] ?? ''))) === '' ? null : mb_substr($v, 0, $n);
  $ticked = array_map('strval', (array)($in['rules'] ?? [])); $rules = [];
  foreach (array_keys(journal_rules()) as $k) $rules[$k] = in_array($k, $ticked, true);
  $shots = []; $saved = [];
  try {
    foreach (['entry', 'exit'] as $k) { $shots[$k] = journal_save_shot('shot_'.$k); if ($shots[$k]) $saved[] = $shots[$k]; }
    db()->beginTransaction();
    db()->prepare('INSERT INTO journal(user_id,side,lots,entry,exit_price,pnl,note) VALUES(?,?,?,?,?,?,?)')
       ->execute([$uid, $side, $lots, $e, $x, $pnl, mb_substr((string)($txt('notes', 255) ?? $txt('entry_reason', 255) ?? ''), 0, 255)]);
    $id = (int)db()->lastInsertId();
    db()->prepare('INSERT INTO journal_extra(journal_id,sl,tp,planned_r,actual_r,entry_reason,exit_reason,emotion,discipline,notes,rules,shot_entry,shot_exit) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)')
       ->execute([$id, $sl, $tp, $plannedR, $actualR, $txt('entry_reason', 1000), $txt('exit_reason', 1000), $emo, $dis, $txt('notes', 2000), json_encode($rules), $shots['entry'], $shots['exit']]);
    db()->commit();
    return $id;
  } catch (Throwable $ex) {
    if (db()->inTransaction()) db()->rollBack();
    foreach ($saved as $f) @unlink(journal_dir().'/'.$f);
    throw $ex;
  }
}

function journal_delete(int $uid, int $id): void {
  $s = db()->prepare('SELECT e.shot_entry, e.shot_exit FROM journal j LEFT JOIN journal_extra e ON e.journal_id=j.id WHERE j.id=? AND j.user_id=?');
  $s->execute([$id, $uid]); $r = $s->fetch();
  if (!$r) return;
  db()->prepare('DELETE FROM journal WHERE id=? AND user_id=?')->execute([$id, $uid]);
  foreach ([$r['shot_entry'], $r['shot_exit']] as $f) if ($f && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $f)) @unlink(journal_dir().'/'.$f);
}

function journal_serve_shot(int $uid, int $id, string $k): void {
  if (!in_array($k, ['entry', 'exit'], true)) { http_response_code(404); exit; }
  $s = db()->prepare("SELECT e.shot_$k FROM journal j JOIN journal_extra e ON e.journal_id=j.id WHERE j.id=? AND j.user_id=?");
  $s->execute([$id, $uid]); $f = (string)$s->fetchColumn();
  if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $f, $m) || !is_file(journal_dir().'/'.$f)) { http_response_code(404); exit; }
  header('Content-Type: image/'.($m[1] === 'jpg' ? 'jpeg' : $m[1])); header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, max-age=86400');
  readfile(journal_dir().'/'.$f); exit;
}

/** Pure function: trades as returned by journal_list(). Separates strategy results from execution quality. */
function journal_summary(array $t): array {
  $n = count($t); if (!$n) return ['n'=>0];
  $avg = fn(array $a) => count($a) ? round(array_sum($a)/count($a), 2) : null;
  $col = fn(string $k) => array_values(array_filter(array_column($t, $k), fn($v) => $v !== null));
  $gap = []; foreach ($t as $x) if ($x['planned_r'] !== null && $x['actual_r'] !== null) $gap[] = $x['actual_r'] - $x['planned_r'];
  $hi = []; $lo = []; $broken = []; $emo = [];
  foreach ($t as $x) {
    if ($x['adherence'] !== null) { if ($x['actual_r'] !== null) { if ($x['adherence'] >= 80) $hi[] = $x['actual_r']; else $lo[] = $x['actual_r']; }
      foreach (journal_rules() as $k => $label) if (empty($x['rules'][$k])) $broken[$label] = ($broken[$label] ?? 0) + 1; }
    if ($x['emotion']) { $emo[$x['emotion']]['n'] = ($emo[$x['emotion']]['n'] ?? 0) + 1; $emo[$x['emotion']]['pnl'] = ($emo[$x['emotion']]['pnl'] ?? 0) + $x['pnl']; if ($x['actual_r'] !== null) $emo[$x['emotion']]['r'][] = $x['actual_r']; }
  }
  arsort($broken); $emoOut = [];
  foreach ($emo as $k => $v) $emoOut[$k] = ['n'=>$v['n'], 'pnl'=>round($v['pnl'], 2), 'avg_r'=>isset($v['r']) ? $avg($v['r']) : null];
  return ['n'=>$n, 'net_pnl'=>round(array_sum(array_column($t, 'pnl')), 2), 'avg_planned_r'=>$avg($col('planned_r')), 'avg_actual_r'=>$avg($col('actual_r')),
    'avg_gap_r'=>$avg($gap), 'avg_adherence'=>$avg($col('adherence')), 'avg_discipline'=>$avg($col('discipline')),
    'followed'=>['n'=>count($hi), 'avg_r'=>$avg($hi)], 'broke'=>['n'=>count($lo), 'avg_r'=>$avg($lo)],
    'most_broken'=>array_slice($broken, 0, 3, true), 'by_emotion'=>$emoOut];
}

function journal_list(int $uid): array {
  $s = db()->prepare('SELECT j.id,j.side,j.lots,j.entry,j.exit_price,j.pnl,j.note,UNIX_TIMESTAMP(j.created_at) ts,
    e.sl,e.tp,e.planned_r,e.actual_r,e.entry_reason,e.exit_reason,e.emotion,e.discipline,e.notes,e.rules,
    (e.shot_entry IS NOT NULL) has_entry,(e.shot_exit IS NOT NULL) has_exit
    FROM journal j LEFT JOIN journal_extra e ON e.journal_id=j.id WHERE j.user_id=? ORDER BY j.id DESC LIMIT 100');
  $s->execute([$uid]); $rows = $s->fetchAll(); $all = journal_rules(); $out = [];
  foreach ($rows as $r) {
    $rules = $r['rules'] !== null ? (json_decode($r['rules'], true) ?: []) : null;
    $adh = $rules !== null ? (int)round(count(array_filter($rules)) / count($all) * 100) : null;
    $f = fn($v) => $v === null ? null : (float)$v;
    $out[] = ['id'=>(int)$r['id'], 'ts'=>(int)$r['ts'], 'side'=>$r['side'], 'lots'=>(float)$r['lots'], 'entry'=>(float)$r['entry'], 'exit'=>(float)$r['exit_price'], 'pnl'=>(float)$r['pnl'],
      'sl'=>$f($r['sl']), 'tp'=>$f($r['tp']), 'planned_r'=>$f($r['planned_r']), 'actual_r'=>$f($r['actual_r']),
      'entry_reason'=>$r['entry_reason'], 'exit_reason'=>$r['exit_reason'], 'emotion'=>$r['emotion'], 'discipline'=>$r['discipline'] === null ? null : (int)$r['discipline'],
      'notes'=>$r['notes'] ?? (($r['entry_reason'] === null && $r['rules'] === null) ? $r['note'] : null), 'rules'=>$rules, 'adherence'=>$adh, 'has_entry'=>(bool)$r['has_entry'], 'has_exit'=>(bool)$r['has_exit']];
  }
  return ['trades'=>$out, 'summary'=>journal_summary($out), 'rules'=>$all, 'emotions'=>journal_emotions()];
}

/** Time range [start, end) of the day, week (Monday to Sunday) or month that contains $date (Y-m-d, empty = today), in the site's local time. */
function journal_period(string $period, string $date, int $offHours): array {
  if (!in_array($period, ['day', 'week', 'month'], true)) throw new InvalidArgumentException('Period must be day, week or month.');
  $off = $offHours*3600;
  if ($date === '') $loc = time() + $off;
  else {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) throw new InvalidArgumentException('Date must look like 2026-11-16.');
    $loc = gmmktime(0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1]);
  }
  $d0 = intdiv($loc, 86400)*86400;                                       // local midnight, as "local clock" seconds
  if ($period === 'day') { $s = $d0; $e = $d0 + 86400; $label = gmdate('Y-m-d', $s); }
  elseif ($period === 'week') { $s = $d0 - ((int)gmdate('N', $d0) - 1)*86400; $e = $s + 7*86400; $label = gmdate('Y-m-d', $s).'_to_'.gmdate('Y-m-d', $e - 86400); }
  else { $y = (int)gmdate('Y', $d0); $mo = (int)gmdate('n', $d0); $s = gmmktime(0, 0, 0, $mo, 1, $y); $e = gmmktime(0, 0, 0, $mo + 1, 1, $y); $label = gmdate('Y-m', $s); }
  return ['period'=>$period, 'start'=>$s - $off, 'end'=>$e - $off, 'label'=>$label];
}

/** CSV of the trades in a period (oldest first, with a totals row). Returns [file name, csv text]. Opens in Excel / Google Sheets. */
function journal_csv(int $uid, string $period, string $date, int $offHours): array {
  $p = journal_period($period, $date, $offHours);
  $s = db()->prepare('SELECT j.side,j.lots,j.entry,j.exit_price,j.pnl,j.note,UNIX_TIMESTAMP(j.created_at) ts,
    e.sl,e.tp,e.planned_r,e.actual_r,e.entry_reason,e.exit_reason,e.emotion,e.discipline,e.notes,e.rules
    FROM journal j LEFT JOIN journal_extra e ON e.journal_id=j.id
    WHERE j.user_id=? AND UNIX_TIMESTAMP(j.created_at)>=? AND UNIX_TIMESTAMP(j.created_at)<? ORDER BY j.id');
  $s->execute([$uid, $p['start'], $p['end']]); $rules = journal_rules();
  $safe = fn($v) => (($v = (string)$v) !== '' && preg_match('/^[=+\-@\t\r]/', $v)) ? "'".$v : $v;   // stops spreadsheet formula injection from text fields
  $rows = [['Date', 'Time', 'Side', 'Lots', 'Entry', 'Exit', 'Planned SL', 'Planned TP', 'Planned R', 'Actual R', 'P/L ($)', 'Emotion', 'Discipline (1-5)', 'Rules followed %', 'Rules broken', 'Entry reason', 'Exit reason', 'Notes']];
  $n = 0; $pnl = 0.0; $rs = [];
  foreach ($s->fetchAll() as $r) {
    $rl = $r['rules'] !== null ? (json_decode($r['rules'], true) ?: []) : null; $local = (int)$r['ts'] + $offHours*3600;
    $broken = []; if ($rl !== null) foreach ($rules as $k => $label) if (empty($rl[$k])) $broken[] = $label;
    $adh = $rl !== null ? (int)round(count(array_filter($rl)) / count($rules) * 100) : '';
    $rows[] = [gmdate('Y-m-d', $local), gmdate('H:i', $local), $r['side'], (float)$r['lots'], (float)$r['entry'], (float)$r['exit_price'], $r['sl'] ?? '', $r['tp'] ?? '', $r['planned_r'] ?? '', $r['actual_r'] ?? '',
      round((float)$r['pnl'], 2), $r['emotion'] ?? '', $r['discipline'] ?? '', $adh, $safe(implode('; ', $broken)), $safe($r['entry_reason'] ?? ''), $safe($r['exit_reason'] ?? ''), $safe($r['notes'] ?? ($r['entry_reason'] === null ? $r['note'] : ''))];
    $n++; $pnl += (float)$r['pnl']; if ($r['actual_r'] !== null) $rs[] = (float)$r['actual_r'];
  }
  $rows[] = []; $rows[] = ['TOTAL', $n.' trades', '', '', '', '', '', '', '', $rs ? round(array_sum($rs)/count($rs), 2).' avg' : '', round($pnl, 2)];
  $fh = fopen('php://temp', 'r+'); foreach ($rows as $row) fputcsv($fh, $row, ',', '"', '\\'); rewind($fh);
  $csv = "\xEF\xBB\xBF".stream_get_contents($fh); fclose($fh);                // BOM so Excel reads UTF-8 correctly
  return ['journal_'.$p['period'].'_'.$p['label'].'.csv', $csv];
}

function journal_export(int $uid, string $period, string $date, int $offHours): void {
  ini_set('display_errors', '0'); [$name, $csv] = journal_csv($uid, $period, $date, $offHours);
  header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="'.$name.'"');
  header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store');
  echo $csv; exit;
}