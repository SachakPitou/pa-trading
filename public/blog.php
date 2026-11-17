<?php
require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/site.php';
$posts = require __DIR__.'/../content/posts.php';
uasort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));
site_header('Blog', 'blog.php'); ?>
<section class="pg"><div class="wrap"><span class="badge">Blog</span><h1>Learn to trade gold with a plan</h1>
<p class="lead">Guides on risk, reading signals and the markets that move gold.</p></div></section>
<section class="s" style="border-top:0;padding-top:16px"><div class="wrap"><div class="bgrid">
<?php foreach ($posts as $slug => $p): ?>
  <div class="c"><div class="meta"><b><?= h($p['category']) ?></b> · <?= h(date('M j, Y', strtotime($p['date']))) ?></div>
    <h3><a href="post.php?slug=<?= urlencode($slug) ?>"><?= h($p['title']) ?></a></h3><p><?= h($p['excerpt']) ?></p>
    <a class="rm" href="post.php?slug=<?= urlencode($slug) ?>">Read more →</a></div>
<?php endforeach; ?>
</div></div></section>
<?php site_footer();