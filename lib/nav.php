<?php
/** Top navigation shared by the newer pages. To add a page to the menu, add one line here: file => [label, feature]. */
if (!function_exists('nav')) { function nav(string $on): string {
  $items = ['dashboard.php'=>['Dashboard','chart'], 'performance.php'=>['Performance','performance'], 'journal.php'=>['Journal','journal'],
            'calendar.php'=>['Calendar','calendar'], 'risk.php'=>['Risk','risk'], 'news.php'=>['News','ai']];
  $h = '<nav class="nav">';
  foreach ($items as $f => [$label, $feat]) $h .= '<a href="'.$f.'"'.($f === $on ? ' class="on"' : '').'>'.$label.(can($feat) ? '' : ' 🔒').'</a>';
  return $h.'</nav>';
}
}