<?php
/** Top navigation shared by the newer pages. To add a page to the menu, add one line here. */
function nav(string $on): string {
  $items = ['dashboard.php'=>'Dashboard', 'performance.php'=>'Performance', 'journal.php'=>'Journal', 'calendar.php'=>'Calendar'];
  $h = '<nav class="nav">';
  foreach ($items as $f => $label) $h .= '<a href="'.$f.'"'.($f === $on ? ' class="on"' : '').'>'.$label.'</a>';
  return $h.'</nav>';
}