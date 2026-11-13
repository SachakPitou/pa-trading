<?php require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/nav.php'; require_login(); $u = user(); ?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf" content="<?= csrf() ?>"><title>XAU/USD Scalp Terminal</title>
<style>
:root{--bg:#0c0e12;--card:#14171d;--tx:#e8eaed;--mu:#8b93a1;--bd:#242933;--up:#2ed39b;--dn:#ff5c75;--ac:#f0b429;--in:#0c0e12}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--tx);font:14px/1.45 system-ui,sans-serif}
header{display:flex;flex-wrap:wrap;gap:12px 24px;align-items:center;padding:12px 16px;border-bottom:1px solid var(--bd);background:var(--card)}
.logo{font-weight:800}.logo b{color:var(--ac)}.px{font:700 26px ui-monospace,monospace}.up{color:var(--up)}.dn{color:var(--dn)}.mu{color:var(--mu)}
.main{display:grid;gap:12px;padding:12px 16px;grid-template-columns:minmax(0,2fr) minmax(0,1fr)}@media(max-width:900px){.main{grid-template-columns:1fr}}
.wrap{padding:0 16px 12px}.tools{display:grid;gap:12px;padding:0 16px 16px;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));align-items:start}
.chip{font-size:12px;padding:3px 10px;border-radius:12px;background:var(--in);border:1px solid var(--bd);cursor:pointer}
.hd{display:flex;justify-content:space-between;align-items:center}.hd a{color:var(--ac);text-decoration:none;font-weight:600;letter-spacing:0;text-transform:none}
.tabs{display:flex;gap:6px;margin-bottom:10px}.tabs button.on{background:var(--ac);color:#111;border-color:var(--ac);font-weight:600}
.tiers{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px}.tier{background:var(--in);border-radius:8px;padding:8px}
details.card>summary{cursor:pointer;font-size:12px;letter-spacing:.8px;text-transform:uppercase;color:var(--mu);font-weight:600}details.card[open]>summary{margin-bottom:10px}
.card{background:var(--card);border:1px solid var(--bd);border-radius:10px;padding:12px;min-width:0}
h2{margin:0 0 8px;font-size:12px;letter-spacing:.8px;text-transform:uppercase;color:var(--mu)}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:10px}.stat{background:var(--in);border-radius:8px;padding:8px}.stat b{display:block;font-size:16px}
.sig{border:1px solid var(--bd);border-left-width:4px;border-radius:8px;padding:8px;margin-bottom:8px}.sig.BUY{border-left-color:var(--up)}.sig.SELL{border-left-color:var(--dn)}
.r{display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap}.mono{font:12px ui-monospace,monospace}
button,input,select{font:inherit;color:var(--tx);background:var(--in);border:1px solid var(--bd);border-radius:6px;padding:6px 8px}button{cursor:pointer}
button.p{background:var(--ac);color:#111;border-color:var(--ac);font-weight:600}
label{display:block;font-size:11px;color:var(--mu);margin-bottom:2px}.f{display:grid;grid-template-columns:1fr 1fr;gap:8px}.f input,.f select{width:100%}
.row{display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--bd)}.ev{padding:5px 0;border-bottom:1px solid var(--bd)}
table{width:100%;border-collapse:collapse;font-size:12px}td{padding:5px 6px;border-bottom:1px solid var(--bd);white-space:nowrap}
.tag{font-size:10px;padding:1px 6px;border-radius:4px;background:var(--in)}#tv{height:460px}
.nav{display:flex;gap:4px}.nav a{color:var(--mu);text-decoration:none;padding:5px 12px;border-radius:6px;font-weight:600}
.nav a:hover{color:var(--tx)}.nav a.on{background:var(--in);color:var(--ac);border:1px solid var(--bd)}
</style></head><body>
<header><div class="logo">XAU<b>/</b>USD <span class="mu">SCALP TERMINAL</span></div>
<?= nav('dashboard.php') ?>
<div class="px" id="px">–</div><div id="feed" class="mu mono">connecting…</div><div id="scan" class="chip mono mu" title="Click for scanner health">Scanner …</div>
<span class="mu" style="margin-left:auto"><?= h($u['email']) ?> · <a href="logout.php" style="color:var(--ac)">Logout</a></span></header>
<div class="main">
 <div class="card"><h2>Chart (TradingView)</h2><div id="tv"></div></div>
 <div class="card"><h2 class="hd"><span>Signals</span><a href="performance.php">Performance →</a></h2>
  <div id="tiers"></div>
  <div id="sigs" style="max-height:430px;overflow:auto"></div></div>
</div>
<div class="wrap"><div class="card"><div class="tabs"><button class="on" data-tab="an">Market analysis</button><button data-tab="nw">USD news</button></div>
 <div id="an" class="mu">Loading…</div><div id="nw" class="mu" style="display:none;max-height:300px;overflow:auto">Loading…</div></div></div>
<div class="tools">
 <details class="card" id="rc"><summary>Risk calculator</summary>
  <div class="f"><div><label>Balance ($)</label><input id="bal" type="number" value="5000"></div><div><label>Risk %</label><input id="rk" type="number" value="1" step=".25"></div>
  <div><label>Entry</label><input id="en" type="number" step=".01"></div><div><label>Stop loss</label><input id="sl" type="number" step=".01"></div>
  <div><label>Take profit</label><input id="tp" type="number" step=".01"></div><div><label>&nbsp;</label><button id="fill">Use live price</button></div></div>
  <div class="stats" style="margin:10px 0 0;grid-template-columns:repeat(4,1fr)"><div class="stat"><span class="mu">Lots</span><b id="o1">–</b></div><div class="stat"><span class="mu">Risk</span><b id="o2">–</b></div><div class="stat"><span class="mu">Reward</span><b id="o3">–</b></div><div class="stat"><span class="mu">R:R</span><b id="o4">–</b></div></div></details>
 <details class="card" id="hd"><summary>Scanner health <span id="hs"></span></summary><div id="hl" class="mu">Loading…</div></details>
 <details class="card"><summary>Alert settings</summary>
  <label>Telegram chat ID (message your bot, then use @userinfobot to get it)</label><input id="chat" style="width:100%;margin-bottom:8px">
  <label><input type="checkbox" id="atg"> Telegram alerts</label><label><input type="checkbox" id="aml"> Email alerts (<?= h($u['email']) ?>)</label>
  <div style="margin-top:8px;display:flex;gap:8px"><button class="p" id="asave">Save</button><button id="atest">Send test</button><span id="amsg" class="mu"></span></div></details>
</div>
<script src="https://s3.tradingview.com/tv.js"></script>
<script>
new TradingView.widget({container_id:'tv',symbol:'OANDA:XAUUSD',interval:'5',theme:'dark',style:'1',locale:'en',autosize:true,timezone:'Etc/UTC',studies:['MAExp@tv-basicstudies','RSI@tv-basicstudies']});
const $=i=>document.getElementById(i),CS=document.querySelector('meta[name=csrf]').content,f2=n=>(+n).toFixed(2);
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function api(a,body){const o=body?{method:'POST',headers:{'X-CSRF':CS,'Content-Type':'application/json'},body:JSON.stringify(body)}:{};
 const r=await fetch('api.php?action='+a,o);if(r.status===401)location='login.php';return r.json();}
let live=0,last=0;
async function price(){try{const d=await api('price');if(d.price){const p=d.price;$('px').textContent=f2(p);$('px').className='px '+(last&&p<last?'dn':'up');last=live=p;$('feed').textContent='Twelve Data · '+new Date().toLocaleTimeString();}else $('feed').textContent=d.error||'feed error';}catch(e){$('feed').textContent='offline'}}
let sigs=[];
/* Signal explanation: which rules and confirmations passed or failed */
const whyBox=x=>{const k=x.checks;if(!k||!k.length)return'';
 const row=c=>`<div style="font-size:12px;margin:2px 0"><b class="${c.ok?'up':'dn'}">${c.ok?'✓':'✗'}</b> ${esc(c.name)}${c.max?` <span class="mono mu">${c.pts}/${c.max}</span>`:''} <span class="mu">· ${esc(c.detail)}</span></div>`;
 const G=k.filter(c=>c.g=='gate'),S=k.filter(c=>c.g=='score'),N=k.filter(c=>c.g=='note'),pts=S.reduce((a,c)=>a+c.pts,0),mx=S.reduce((a,c)=>a+c.max,0);
 return `<details style="margin-top:6px"><summary style="cursor:pointer;font-size:12px"><span class="tag">WHY</span> ${G.length} rules passed · ${S.filter(c=>c.ok).length}/${S.length} confirmations · points ${pts}/${mx}</summary><b style="font-size:12px;display:block;margin-top:4px">Required rules (all must pass)</b>${G.map(row).join('')}<b style="font-size:12px;display:block;margin-top:6px">Confirmations (they build the score)</b>${S.map(row).join('')}${N.map(row).join('')}</details>`};
/* AI Market Analyst review shown under each signal (collapsed by default) */
const aiList=(t,a,c)=>a&&a.length?`<div style="margin-top:6px"><b class="${c||''}" style="font-size:12px">${t}</b><ul style="margin:2px 0 0 16px;padding:0;font-size:12px">${a.map(i=>`<li>${esc(i)}</li>`).join('')}</ul></div>`:'';
const aiBox=x=>{const a=x.ai;if(!a)return'';return`<details style="margin-top:6px"><summary style="cursor:pointer;font-size:12px"><span class="tag">AI</span> ${esc(a.summary)}</summary>${aiList('Why it may be valid',a.why_valid,'up')}${aiList('Potential weaknesses',a.weaknesses,'dn')}${aiList('Conflicting indicators',a.conflicts)}${aiList('Invalidation conditions',a.invalidation)}<div class="mu" style="font-size:10px;margin-top:6px">AI review of the setup, not financial advice.</div></details>`};
async function loadSigs(){const d=await api('signals');sigs=d.signals;
 $('tiers').innerHTML='<div class="tiers">'+Object.entries(d.tiers).map(([k,t])=>`<div class="tier"><div class="r"><b>${k=='confirmed'?'Confirmed':'Early'}</b><span class="tag ${t.visible?'up':'dn'}">${t.visible?'LIVE':'PAUSED'}</span></div><div class="mu" style="font-size:11px">${t.win_rate==null?'No closed trades yet':t.win_rate+'% win · '+(t.net_r>=0?'+':'')+t.net_r+'R (last '+t.window+')'}</div>${t.visible?'':'<div class="dn" style="font-size:11px">Paused: win rate below '+t.min_win+'%</div>'}</div>`).join('')+'</div>';
 $('sigs').innerHTML=sigs.length?sigs.map(x=>`<div class="sig ${esc(x.side)}"><div class="r"><b class="${x.side=='BUY'?'up':'dn'}">${esc(x.side)}</b><span class="tag">${x.tier=='early'?'EARLY':'CONFIRMED'}</span><span class="tag">${esc(x.status)}</span><span class="mu">${esc(x.created_at)} · score ${x.conf}</span></div>
 <div class="mono">E ${f2(x.entry)} · SL ${f2(x.sl)} · TP1 ${f2(x.tp1)} · TP2 ${f2(x.tp2)}</div><div class="mu" style="font-size:11px">${esc(x.reasons||'')}${x.r!==null?' · '+x.r+'R':''}</div>${whyBox(x)}${aiBox(x)}<button data-id="${x.id}" style="margin-top:6px;font-size:12px">Load to calculator</button></div>`).join(''):'<div class="mu">No signals yet. The scanner (cron.php) creates them on each closed candle.</div>';}
$('sigs').onclick=e=>{const s=sigs.find(x=>x.id==e.target.dataset.id);if(!s)return;$('en').value=f2(s.entry);$('sl').value=f2(s.sl);$('tp').value=f2(s.tp1);$('rc').open=true;calc();};
function calc(){const b=+$('bal').value,r=+$('rk').value,e=+$('en').value,s=+$('sl').value,t=+$('tp').value,d=Math.abs(e-s);
 if(!b||!r||!d){['o1','o2','o3','o4'].forEach(i=>$(i).textContent='–');return}
 const risk=b*r/100,lot=risk/(100*d),rw=t?Math.abs(t-e)*100*lot:0;
 $('o1').textContent=lot.toFixed(2);$('o2').textContent='$'+f2(risk);$('o3').textContent=t?'$'+f2(rw):'–';$('o4').textContent=t?'1:'+(rw/risk).toFixed(2):'–';}
['bal','rk','en','sl','tp'].forEach(i=>$(i).oninput=calc);$('fill').onclick=()=>{$('en').value=f2(live);calc()};
async function loadMe(){const m=await api('me');$('chat').value=m.chat||'';$('atg').checked=m.tg;$('aml').checked=m.mail;}
$('asave').onclick=async()=>{await api('settings',{chat:$('chat').value,tg:$('atg').checked,mail:$('aml').checked});$('amsg').textContent='Saved ✓'};
$('atest').onclick=async()=>{const r=await api('test_alert',{});$('amsg').textContent=r.error||'Test sent ✓'};
price();loadSigs();loadMe();setInterval(price,10000);setInterval(loadSigs,30000);

const cls=v=>v=='Bullish'||v=='Positive'?'up':(v=='Bearish'||v=='Negative'?'dn':'');
async function loadAn(){try{const a=await api('analysis');if(a.error){$('an').textContent=a.error;return}
 const R=(k,v,c)=>`<div class="row"><span class="mu">${k}</span><b class="${c||''}">${esc(v)}</b></div>`,L=a.levels;
 $('an').innerHTML=R('Overall bias',a.bias+' ('+a.bull_factors+'/5)',cls(a.bias))+R('1H trend',a.htf,cls(a.htf))+R('5m trend (EMA 9/21/50)',a.trend5,cls(a.trend5))
 +R('ADX '+a.adx,a.adx_state)+R('RSI 14',a.rsi)+R('MACD histogram',a.macd,cls(a.macd))+R('ATR 14',a.atr)+R('Session',a.session)
 +R('Resistance (5m / day)',f2(L.res5)+' / '+f2(L.res_day))+R('Support (5m / day)',f2(L.sup5)+' / '+f2(L.sup_day))
 +R('Next high-impact news',a.news?a.news.title+' · '+(a.news.mins>=0?'in '+a.news.mins+' min':Math.abs(a.news.mins)+' min ago'):'None this week',a.news&&Math.abs(a.news.mins)<=30?'dn':'');}catch(e){$('an').textContent='Analysis unavailable'}}
async function loadNews(){try{const d=await api('news');$('nw').innerHTML=d.events.length?d.events.map(e=>`<div class="ev"><div class="r"><b class="${e.impact=='High'?'dn':''}">${esc(e.title)}</b><span class="tag">${esc(e.impact)}</span></div><div class="mu mono">${new Date(e.t*1000).toLocaleString()} · fcst ${esc(e.forecast||'–')} · prev ${esc(e.previous||'–')}</div></div>`).join(''):'No upcoming events (feed empty or unavailable).';}catch(e){}}
async function loadSt(){try{const s=await api('status'),el=$('scan');
 if(!s.at){el.textContent='Scanner: '+(s.error||'no data');el.className='chip mono dn';return}
 const ago=Math.round((Date.now()/1000-s.at)/60),ok=ago<=10&&!s.error;
 el.textContent='● Scanner '+(ok?'OK':'STALE')+' · '+ago+' min ago';
 el.title=(s.candle?'Candle '+new Date(s.candle.replace(' ','T')+'Z').toLocaleTimeString():'')+(s.adx!==undefined?' · ADX '+s.adx+' RSI '+s.rsi:'')+(s.api!==undefined?' · API '+s.api+'/800':'')+(s.skipped?' · '+s.skipped:'')+(s.error?' · '+s.error:'');
 el.className='chip mono '+(ok?'up':'dn');}catch(e){}}
$('scan').onclick=()=>{$('hd').open=true;$('hd').scrollIntoView({behavior:'smooth'})};
document.querySelectorAll('.tabs button').forEach(b=>b.onclick=()=>{document.querySelectorAll('.tabs button').forEach(x=>x.classList.toggle('on',x==b));$('an').style.display=b.dataset.tab=='an'?'':'none';$('nw').style.display=b.dataset.tab=='nw'?'':'none'});
/* Scanner health card */
const ago=t=>{if(!t)return'–';const m=Math.round((Date.now()/1000-t)/60);return m<1?'just now':m<90?m+' min ago':Math.round(m/60)+' h ago'};
const hcell=(k,v)=>`<div class="stat"><span class="mu">${k}</span><b>${v}</b></div>`;
async function loadHealth(){try{const h=await api('health');if(h.error){$('hl').textContent=h.error;return}
 const S=h.scanner,F=h.feed,Q=h.quota,Y=h.system,miss=F.gaps.reduce((a,g)=>a+g.missing,0);
 $('hs').innerHTML=`<span class="tag ${h.status=='down'?'dn':h.status=='ok'?'up':''}">${h.status.toUpperCase()}</span>`;
 $('hl').innerHTML=`<div style="margin-bottom:8px"><span class="tag ${h.status=='down'?'dn':h.status=='ok'?'up':''}" style="font-weight:700">${h.status.toUpperCase()}</span> <span class="mu">${h.expected_active?'trading window open':'outside trading window (idle is normal)'}</span></div>`
 +(h.issues.length?`<ul style="margin:0 0 8px 16px;padding:0;font-size:12px">${h.issues.map(i=>`<li class="${i.level=='down'?'dn':''}">${esc(i.msg)}</li>`).join('')}</ul>`:'')
 +`<div class="stats" style="margin:0;grid-template-columns:repeat(2,1fr)">`
 +hcell('Last scan',ago(S.heartbeat))+hcell('Last successful',ago(S.last_ok))
 +hcell('Last candle',S.last_candle?S.candle_age_min+' min old':'–')+hcell('Runs today',S.runs_today)
 +hcell('API left today',Q.remaining+' / '+Q.budget)+hcell('Errors today',F.errors_today)
 +hcell('Missing candles',miss||'none')+hcell('DB / cache',Y.db_ms+' ms · '+(Y.cache_age_min??'–')+' min')+`</div>`
 +(F.last_error?`<div class="mu" style="font-size:12px;margin-top:6px">Last error ${ago(F.last_error.ts)}: ${esc(F.last_error.detail||'')}</div>`:'')
 +`<details style="margin-top:8px"><summary class="mu" style="cursor:pointer;font-size:12px">Scanner history (last ${h.history.length})</summary><div class="mono" style="max-height:200px;overflow:auto;font-size:11px">`
 +h.history.map(r=>`<div class="${r.outcome=='error'?'dn':r.outcome=='signal'?'up':'mu'}">${new Date(r.ts*1000).toLocaleTimeString()} · ${esc(r.outcome)} · ${r.ms}ms · API ${r.api_used}${r.detail?' · '+esc(r.detail):''}</div>`).join('')+`</div></details>`;
}catch(e){}}
loadHealth();setInterval(loadHealth,30000);
loadSt();setInterval(loadSt,30000);
loadAn();loadNews();setInterval(loadAn,300000);setInterval(loadNews,600000);
</script></body></html>