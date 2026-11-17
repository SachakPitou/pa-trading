<?php
// Blog posts. To add one, copy an entry. 'slug' is the URL (post.php?slug=...). Newest date shows first.
return [
  'size-your-gold-trade' => [
    'title'=>'How to size a gold trade: the 1% rule', 'date'=>'2026-11-15', 'category'=>'Risk',
    'excerpt'=>'Position size matters as much as entry. Here is a simple way to calculate it before every trade.',
    'body'=>'<p>Most traders think about where to enter. Fewer think about <b>how much</b> to trade. A common approach is to risk a fixed small percentage of your balance on each trade, often around 1%.</p>
<h2>The formula</h2>
<p>For XAU/USD, 1.00 lot is 100 ounces, so a $1 move equals $100. The lot size is:</p>
<p><b>Lots = (Balance &times; Risk%) &divide; (100 &times; Stop distance in $)</b></p>
<h2>Example</h2>
<ul><li>Balance: $5,000, risk 1% = <b>$50</b></li><li>Stop loss: $5.00 away from entry</li><li>Lots = 50 &divide; (100 &times; 5) = <b>0.10 lots</b></li></ul>
<p>If the stop is hit you lose about $50, which is 1% of the account. A wider stop means a smaller lot size, never a bigger risk. The risk calculator in the terminal does this for you.</p>
<p><i>Educational content only, not financial advice.</i></p>'],
  'reading-a-signal' => [
    'title'=>'Reading a signal: entry, stop, target and the reasoning', 'date'=>'2026-11-12', 'category'=>'Education',
    'excerpt'=>'What each part of a signal means, and why the reasoning behind it matters more than the entry price.',
    'body'=>'<p>Every signal has the same core parts:</p>
<ul><li><b>Side</b>: BUY or SELL.</li><li><b>Entry</b>: the price the setup is based on.</li><li><b>Stop loss</b>: where the idea is wrong and the trade closes.</li><li><b>Take profit</b>: the target.</li></ul>
<h2>Why the reasoning matters</h2>
<p>A signal that passes several independent rules and confirmations is usually more meaningful than one that passes a single check. That is why each signal shows which checks it passed, so you can decide whether you agree.</p>
<h2>Reward to risk</h2>
<p>Compare the distance to the target with the distance to the stop. A trade that risks $5 to make $10 has a 1:2 ratio, so it can be profitable even if it wins less than half the time.</p>
<p><i>Educational content only, not financial advice.</i></p>'],
  'usd-news-and-gold' => [
    'title'=>'Why high-impact USD news moves gold', 'date'=>'2026-11-10', 'category'=>'Markets',
    'excerpt'=>'Gold is priced in dollars, so US data releases can cause sudden spikes. How to trade around them.',
    'body'=>'<p>Gold is quoted in US dollars, so anything that moves expectations for the dollar or for US interest rates can move gold quickly.</p>
<h2>Events to watch</h2>
<ul><li>Non-Farm Payrolls (jobs report)</li><li>CPI (inflation)</li><li>Federal Reserve rate decisions and speeches</li></ul>
<h2>Trading around them</h2>
<p>Around these releases spreads can widen and price can jump past your stop. Many scalpers stay out for a short window before and after a high-impact event, or reduce their size. The news calendar in the terminal lists upcoming events and their impact level.</p>
<p><i>Educational content only, not financial advice.</i></p>'],
];