<?php
require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/site.php';
$join = cfg('allow_register') ? 'login.php?register=1' : 'login.php';
site_header('Gold scalping signals with the reasoning shown', 'home.php'); ?>
<section class="hero"><div class="wrap">
  <div>
    <span class="badge">XAU/USD · Gold scalping</span>
    <h1>Gold signals that <em>show their reasoning</em></h1>
    <p class="lead">Get scalping signals for XAU/USD with the rules behind each one, an AI review, and a track record you can check, plus a journal and risk tools to trade with discipline.</p>
    <div class="cta"><a class="btn pri lg2" href="<?= $join ?>">Get started</a><a class="btn lg2" href="pricing.php">View pricing</a></div>
  </div>
  <div class="live"><small>Live · XAU/USD</small><div class="px up" id="px">–</div><p id="feed">Connecting to market feed…</p></div>
</div></section>

<section class="s"><div class="wrap">
  <h2>Everything you need to trade gold with a plan</h2><p class="sub">Built for scalpers who want to know why a trade is suggested, not just when.</p>
  <div class="grid">
    <div class="c"><div class="ic">📡</div><h3>Live scalping signals</h3><p>Entry, stop loss and take profit for XAU/USD, organised in quality tiers so you can focus on the setups you trust.</p></div>
    <div class="c"><div class="ic">🔍</div><h3>Reasoning on every signal</h3><p>See which rules and confirmations each signal passed, instead of trusting a black box.</p></div>
    <div class="c"><div class="ic">🤖</div><h3>AI review &amp; market analysis</h3><p>An AI second opinion on signals and a readable market analysis to add context before you click.</p></div>
    <div class="c"><div class="ic">📈</div><h3>Transparent performance</h3><p>Filter results by tier and date range, so you can judge the strategy on its record.</p></div>
    <div class="c"><div class="ic">🛡️</div><h3>Journal &amp; risk tools</h3><p>Log your trades and size every position with a risk calculator based on your balance and stop loss.</p></div>
    <div class="c"><div class="ic">🔔</div><h3>Alerts &amp; news calendar</h3><p>Get Telegram or email alerts, and see high-impact USD news so you can avoid the spikes.</p></div>
  </div>
</div></section>

<section class="s"><div class="wrap">
  <h2>How it works</h2><p class="sub">From sign up to your first signal in a few minutes.</p>
  <div class="steps">
    <div class="c"><h3>Create an account</h3><p>Sign up with your email.</p></div>
    <div class="c"><h3>Confirm your email</h3><p>Click the link we send you.</p></div>
    <div class="c"><h3>Choose a plan</h3><p>Pick the plan that fits how you trade.</p></div>
    <div class="c"><h3>Trade with context</h3><p>Follow signals, alerts and your journal.</p></div>
  </div>
</div></section>

<section class="s"><div class="wrap"><div class="band"><h2>Ready to trade with more clarity?</h2><p>Create your account and see the terminal for yourself.</p>
<a class="btn pri lg2" href="<?= $join ?>">Get started</a></div></div></section>

<script>
let last=0;async function px(){const f=document.getElementById('feed');try{const r=await fetch('api.php?action=price');const d=await r.json();
 if(d.price){const e=document.getElementById('px');e.textContent=(+d.price).toFixed(2);e.className='px '+(last&&d.price<last?'dn':'up');last=d.price;f.textContent='Updated '+new Date().toLocaleTimeString();}
 else f.textContent='Feed unavailable ('+(d.error||r.status)+')';}catch(e){f.textContent='Feed unavailable';}}
px();setInterval(px,10000);
</script>
<?php site_footer();