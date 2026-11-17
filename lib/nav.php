<?php
/** Shared navigation + user menu. To add a page: one line in $items (file => [label, icon, feature]). */
if (!function_exists('nav')) {
function nav(string $on): string {
  $items = [
    'dashboard.php'=>['Dashboard','📊','chart'], 'performance.php'=>['Performance','📈','performance'], 'journal.php'=>['Journal','📓','journal'],
    'calendar.php'=>['Calendar','📅','calendar'], 'risk.php'=>['Risk','🛡️','risk'], 'news.php'=>['News','📰','ai'],
  ];
  if (is_admin()) $items['admin.php'] = ['Admin','⚙️','admin'];
  $h = '<link rel="stylesheet" href="app.css"><nav class="an" aria-label="Main">';
  foreach ($items as $f => [$label, $ic, $feat])
    $h .= '<a href="'.$f.'"'.($f === $on ? ' class="on" aria-current="page"' : '').'><i>'.$ic.'</i><span>'.$label.'</span>'.(can($feat) ? '' : '<em>🔒</em>').'</a>';
  return $h.'</nav>';
}
function user_menu(): string {
  $u = user(); if (!$u) return '';
  $e = h($u['email']); $role = is_admin() ? 'Admin' : 'Member';
  return '<details class="um"><summary><span class="av">'.h(strtoupper($u['email'][0])).'</span><span class="em">'.$e.'</span></summary>'
    .'<div class="dd"><div class="who">'.$e.'<br><small>'.$role.'</small></div>'
    .'<a href="#" onclick="try{localStorage.removeItem(\'gs_off\')}catch(x){}location=\'dashboard.php\';return false">Quick guide</a>'
    .'<form method="post" action="logout.php" style="margin:0"><input type="hidden" name="csrf" value="'.csrf().'"><button class="lo">Log out</button></form></div></details>'
    .'<script>document.addEventListener("click",e=>document.querySelectorAll(".um[open]").forEach(d=>{if(!d.contains(e.target))d.open=false}))</script>';
}
}