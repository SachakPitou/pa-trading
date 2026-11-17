<?php
/** Shared layout for the public website (home, pricing, about, blog). */
function site_header(string $title, string $active = ''): void {
  $u = user();
  $links = ['home.php'=>'Home','pricing.php'=>'Pricing','blog.php'=>'Blog','about.php'=>'About'];
  ?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?> · XAU/USD Terminal</title><link rel="stylesheet" href="site.css"></head><body>
<header class="top"><div class="wrap"><a class="lg" href="home.php">XAU<b>/</b>USD Terminal</a>
<nav><?php foreach ($links as $f => $l): ?><a href="<?= $f ?>"<?= $f === $active ? ' class="on"' : '' ?>><?= $l ?></a><?php endforeach; ?></nav>
<div class="acts"><?php if ($u): ?><a class="btn pri" href="dashboard.php">Open dashboard</a>
<?php else: ?><a class="btn" href="login.php">Sign in</a><?php if (cfg('allow_register')): ?><a class="btn pri" href="login.php?register=1">Get started</a><?php endif; endif; ?></div></div></header>
<?php }

function site_footer(): void { ?>
<footer class="ft"><div class="wrap"><div><b style="color:var(--text)">XAU/USD Terminal</b><br>Gold scalping signals &amp; risk tools</div>
<div class="links"><a href="pricing.php">Pricing</a><a href="blog.php">Blog</a><a href="about.php">About</a><a href="login.php">Sign in</a></div>
<div class="risk">Trading leveraged products such as gold CFDs carries a high risk of loss. Signals are for information only and are not financial advice. Past performance does not guarantee future results. &copy; <?= date('Y') ?></div></div></footer>
</body></html>
<?php }