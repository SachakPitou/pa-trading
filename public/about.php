<?php
require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/site.php';
$join = cfg('allow_register') ? 'login.php?register=1' : 'login.php';
$contact = 'support@yourdomain.com';   // PLACEHOLDER: replace with your real support email
site_header('About', 'about.php'); ?>
<section class="pg"><div class="wrap">
  <span class="badge">About</span><h1>Built for gold traders who want to <em>understand</em> every trade</h1>
  <p class="lead">XAU/USD Terminal gives scalpers clear signals, the reasoning behind them, and the tools to manage risk, all in one place.</p>
</div></section>

<section class="s"><div class="wrap"><div class="prose">
  <h2>Our mission</h2>
  <p>Most signal services tell you what to trade but not why. We built this terminal so every signal comes with the rules it passed, an AI second opinion and a public track record. That way you can judge it on evidence and not on hype.</p>
  <p>We focus on one market, gold against the US dollar, and try to do it well.</p>
</div></div></section>

<section class="s"><div class="wrap">
  <h2 style="text-align:center">How the signals work</h2><p class="sub" style="text-align:center">In plain words, with no black box.</p>
  <div class="grid">
    <div class="c"><div class="ic">1️⃣</div><h3>The scanner checks the market</h3><p>Every few minutes it reads fresh XAU/USD price data and tests it against a fixed set of strategy rules.</p></div>
    <div class="c"><div class="ic">2️⃣</div><h3>Signals must pass confirmations</h3><p>A setup is only published if it passes the rules and extra confirmation checks. You can see which ones it passed.</p></div>
    <div class="c"><div class="ic">3️⃣</div><h3>Results are tracked</h3><p>Each signal is followed to its outcome and added to the performance record, wins and losses included.</p></div>
  </div>
</div></div></section>

<section class="s"><div class="wrap"><h2 style="text-align:center">What we believe</h2><p class="sub" style="text-align:center">The principles behind the product.</p>
  <div class="two">
    <div class="c"><h3>Transparency first</h3><p>Show the reasoning and the results, not only the entries.</p></div>
    <div class="c"><h3>Risk before reward</h3><p>Every trade needs a stop loss and a position size you can afford. That's why the risk tools sit next to the signals.</p></div>
    <div class="c"><h3>No guarantees</h3><p>No strategy wins every trade. We'll never promise profits or show only the good days.</p></div>
    <div class="c"><h3>Your data stays yours</h3><p>Your journal and settings are private to your account.</p></div>
  </div>
</div></section>

<section class="s"><div class="wrap"><div class="prose">
  <div class="warn"><h3>Risk disclosure</h3><p>Trading gold and other leveraged products carries a high level of risk and may not be suitable for everyone. You can lose more than you expect. Signals and analysis on this site are for information only and are not financial advice. Past performance does not guarantee future results. Only trade with money you can afford to lose.</p></div>
</div></div></section>

<section class="s"><div class="wrap"><div class="band"><h2>Questions? We'd love to hear from you</h2><p>Email us at <a href="mailto:<?= h($contact) ?>"><?= h($contact) ?></a></p>
<a class="btn pri lg2" href="<?= $join ?>">Get started</a></div></div></section>
<?php site_footer();