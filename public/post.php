<?php
require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/site.php';
$posts = require __DIR__.'/../content/posts.php';
$p = $posts[(string)($_GET['slug'] ?? '')] ?? null;
$join = cfg('allow_register') ? 'login.php?register=1' : 'login.php';
if (!$p) { http_response_code(404); site_header('Not found', 'blog.php'); ?>
  <div class="art"><h1>Post not found</h1><p><a href="blog.php">← Back to the blog</a></p></div><?php site_footer(); exit; }
site_header($p['title'], 'blog.php'); ?>
<article class="art"><a class="back" href="blog.php">← All posts</a>
  <div class="meta"><b><?= h($p['category']) ?></b> · <?= h(date('M j, Y', strtotime($p['date']))) ?></div>
  <h1><?= h($p['title']) ?></h1><?= $p['body'] ?>
</article>
<section class="s" style="border-top:0"><div class="wrap"><div class="band"><h2>Put it into practice</h2><p>Create an account to use the risk calculator and journal.</p>
<a class="btn pri lg2" href="<?= $join ?>">Get started</a></div></div></section>
<?php site_footer();