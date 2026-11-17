<?php
require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/site.php';
$join = cfg('allow_register') ? 'login.php?register=1' : 'login.php';
// PLACEHOLDER PLANS: edit freely. 'm' = price per month billed monthly, 'y' = price per month billed yearly.
$plans = [
  ['name'=>'Free','tag'=>'Explore the terminal','m'=>0,'y'=>0,'pop'=>false,'cta'=>'Create free account','f'=>[
    'Live XAU/USD price','USD news feed','Basic risk calculator','Email support']],
  ['name'=>'Pro','tag'=>'For active scalpers','m'=>29,'y'=>24,'pop'=>true,'cta'=>'Get Pro','f'=>[
    'Everything in Free','Live scalping signals','Reasoning shown on every signal','Performance tracking','Trade journal','Telegram &amp; email alerts']],
  ['name'=>'Premium','tag'=>'Full toolkit','m'=>59,'y'=>49,'pop'=>false,'cta'=>'Get Premium','f'=>[
    'Everything in Pro','All signal tiers','AI signal review &amp; market analysis','News analyst','Economic calendar','Priority support']],
];
site_header('Pricing', 'pricing.php'); ?>
<section class="pg"><div class="wrap">
  <span class="badge">Pricing</span><h1>Simple plans for every trader</h1>
  <p class="lead">Start free and upgrade when you're ready. Cancel anytime.</p>
  <div class="tg"><button class="on" data-mode="m">Monthly</button><button data-mode="y">Yearly <i>save ~17%</i></button></div>
  <div class="plans">
  <?php foreach ($plans as $p): ?>
    <div class="plan<?= $p['pop'] ? ' pop' : '' ?>"><?php if ($p['pop']): ?><span class="pp">Most popular</span><?php endif; ?>
      <h3><?= h($p['name']) ?></h3><div class="tag"><?= h($p['tag']) ?></div>
      <div class="pr">$<span class="amt" data-m="<?= $p['m'] ?>" data-y="<?= $p['y'] ?>"><?= $p['m'] ?></span><small> /month</small></div>
      <div class="bill" data-y="<?= $p['y'] * 12 ?>"><?= $p['m'] ? 'Billed monthly' : 'Free forever' ?></div>
      <ul><?php foreach ($p['f'] as $f): ?><li><?= $f ?></li><?php endforeach; ?></ul>
      <a class="btn<?= $p['pop'] ? ' pri' : '' ?>" href="<?= $join ?>"><?= h($p['cta']) ?></a>
    </div>
  <?php endforeach; ?>
  </div>
  <p class="note">Preview pricing: plans and prices may change before launch. Payments by card and ABA will be available soon.</p>
</div></section>

<section class="s"><div class="wrap"><h2 style="text-align:center">Questions</h2><p class="sub" style="text-align:center">Quick answers before you sign up.</p>
<div class="faq">
  <details><summary>Can I cancel anytime?</summary><p>Yes. You can cancel whenever you like and keep access until the end of the period you paid for.</p></details>
  <details><summary>How can I pay?</summary><p>We're adding card payments and ABA payments for customers in Cambodia. They aren't live yet.</p></details>
  <details><summary>Are signals financial advice?</summary><p>No. Signals are for information only. Trading gold carries a high risk of loss, so only trade money you can afford to lose.</p></details>
  <details><summary>Do I need to confirm my email?</summary><p>Yes. After you sign up we send a confirmation link, which you need to click before you can sign in.</p></details>
</div></div></section>

<script>
const tg=document.querySelectorAll('.tg button');
tg.forEach(b=>b.onclick=()=>{const y=b.dataset.mode==='y';tg.forEach(x=>x.classList.toggle('on',x===b));
 document.querySelectorAll('.amt').forEach(a=>a.textContent=y?a.dataset.y:a.dataset.m);
 document.querySelectorAll('.bill').forEach(e=>{const t=+e.dataset.y;if(t)e.textContent=y?'Billed $'+t+' yearly':'Billed monthly';});});
</script>
<?php site_footer();