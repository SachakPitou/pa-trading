<?php require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/nav.php'; require_login(); $u = user(); ?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf" content="<?= csrf() ?>"><title>News Analyst · XAU/USD Scalp Terminal</title>
<style>
:root{--bg:#0c0e12;--card:#14171d;--tx:#e8eaed;--mu:#8b93a1;--bd:#242933;--up:#2ed39b;--dn:#ff5c75;--ac:#f0b429;--in:#0c0e12}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--tx);font:14px/1.45 system-ui,sans-serif}
header{display:flex;flex-wrap:wrap;gap:12px 24px;align-items:center;padding:12px 16px;border-bottom:1px solid var(--bd);background:var(--card)}
.logo{font-weight:800}.logo b{color:var(--ac)}.up{color:var(--up)}.dn{color:var(--dn)}.ac{color:var(--ac)}.mu{color:var(--mu)}.mono{font:12px ui-monospace,monospace}
.nav{display:flex;gap:4px}.nav a{color:var(--mu);text-decoration:none;padding:5px 12px;border-radius:6px;font-weight:600}
.nav a:hover{color:var(--tx)}.nav a.on{background:var(--in);color:var(--ac);border:1px solid var(--bd)}
main{padding:14px 16px;display:grid;gap:14px;max-width:1100px;margin:0 auto}
.card{background:var(--card);border:1px solid var(--bd);border-radius:10px;padding:14px;min-width:0}
h2{margin:0 0 10px;font-size:12px;letter-spacing:.8px;text-transform:uppercase;color:var(--mu)}
button{font:inherit;color:var(--tx);background:var(--in);border:1px solid var(--bd);border-radius:6px;padding:6px 12px;cursor:pointer}
button.p{background:var(--ac);color:#111;border-color:var(--ac);font-weight:600}button:disabled{opacity:.5;cursor:default}
.bar{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
.two{display:grid;grid-template-columns:1fr 1fr;gap:14px}@media(max-width:820px){.two{grid-template-columns:1fr}}
.chip{font-size:11px;padding:1px 8px;border-radius:10px;background:var(--in);border:1px solid var(--bd);white-space:nowrap}
.bias{font-weight:800;font-size:13px;padding:2px 10px;border-radius:10px;border:1px solid currentColor}
.it{border:1px solid var(--bd);border-left-width:4px;border-radius:8px;padding:9px 10px;margin-bottom:8px}
.it.c{border-left-color:var(--up)}.it.s{border-left-color:var(--ac)}.it b.t{display:block}
.it .l{font-size:12px;margin-top:4px}.it .l span{color:var(--mu)}
table{width:100%;border-collapse:collapse;font-size:13px}td,th{padding:6px 8px;border-bottom:1px solid var(--bd);text-align:left}th{font-size:11px;color:var(--mu);text-transform:uppercase}
.tw{overflow:auto}.note{font-size:12px}
</style></head><body>
<header><div class="logo">XAU<b>/</b>USD <span class="mu">SCALP TERMINAL</span></div><?= nav('news.php') ?>
<span class="mu" style="margin-left:auto"><?= h($u['email']) ?> · <a href="logout.php" style="color:var(--ac)">Logout</a></span></header>
<main>
 <div class="card bar"><button class="p" id="rf">Refresh analysis</button><span id="st" class="mu note"></span></div>
 <div id="sm"></div>
 <div class="two"><div class="card"><h2>✓ Confirmed (stated in the data, with a source)</h2><div id="cf" class="mu"></div></div>
  <div class="card"><h2>? Speculation / unconfirmed</h2><div id="sp" class="mu"></div></div></div>
 <div class="card"><h2>What to watch next</h2><div id="up" class="mu"></div></div>
 <div class="card"><h2>Key USD events this week (economic calendar)</h2><div class="tw" id="ev" class="mu"></div></div>
 <div class="card"><h2>Headlines the AI was given</h2><div id="hl" class="mu"></div></div>
</main>
<script>
const $=i=>document.getElementById(i),CS=document.querySelector('meta[name=csrf]').content;
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const when=t=>new Date(t*1000).toLocaleString([], {weekday:'short',day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'});
const BIAS={Bullish:'up',Bearish:'dn',Mixed:'ac',Neutral:'mu'};
let CD=0;
async function api(action,post){const o={};if(post){const fd=new FormData();fd.append('csrf',CS);o.method='POST';o.body=fd}
 let r,t;try{r=await fetch('news_api.php?action='+action,o);t=await r.text()}catch(e){return{error:'Network error: '+e.message}}
 if(r.status===401){location='login.php';return{error:'Please log in again.'}}
 try{return JSON.parse(t)}catch(e){return{error:'The server answered HTTP '+r.status+' with a web page instead of data. '+(r.status===404?'news_api.php was not found: check that it is inside the public/ folder on the server. ':'')+'Open news_api.php?action=state in a new tab to see the real error. ('+t.replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim().slice(0,120)+')'}}}
function line(l,v){return v?`<div class="l"><span>${l}</span> ${esc(v)}</div>`:''}
function draw(d){
 const s=d.saved,a=s&&s.analysis;CD=d.cooldown;
 $('st').textContent=!d.enabled?'AI is not enabled: set ai_key in config.php to get the analysis. The calendar below still works.':
  s?'Analysis from '+when(s.at)+' ('+Math.max(0,Math.round((d.now-s.at)/60))+' min ago) · model '+s.model+' · refreshed automatically about every hour':'No analysis yet. It is created automatically by the scanner, or click Refresh.';
 $('rf').disabled=!d.enabled||d.queued;
 if(d.queued)$('st').textContent='Refresh requested: the scanner will run it within about 5 minutes. This page updates by itself.';
 else if(d.last_error)$('st').textContent+=' · Last attempt failed: '+d.last_error;
 if(a){
  $('sm').innerHTML=`<div class="card"><h2>AI summary</h2><div style="font-size:15px">${esc(a.summary)}</div>
   <div style="margin-top:10px" class="bar"><span class="mu note">Likely news pressure on gold:</span><span class="bias ${BIAS[a.bias]}">${esc(a.bias)}</span><span class="note">${esc(a.bias_reason)}</span></div>
   <div class="mu note" style="margin-top:10px">${esc(a.caution)}</div></div>`;
  $('cf').innerHTML=a.confirmed.length?a.confirmed.map(x=>`<div class="it c"><b class="t">${esc(x.item)}</b><div class="note">${esc(x.detail)}</div>${line('Source:',x.source)}${line('Gold impact:',x.gold_impact)}</div>`).join(''):'<span class="mu">Nothing confirmed in the data right now.</span>';
  $('sp').innerHTML=a.speculation.length?a.speculation.map(x=>`<div class="it s"><b class="t">${esc(x.item)}</b>${line('Why uncertain:',x.why_uncertain)}${line('Gold impact:',x.gold_impact)}</div>`).join(''):'<span class="mu">No speculation flagged.</span>';
  $('up').innerHTML=a.upcoming.length?a.upcoming.map(x=>`<div class="it c" style="border-left-color:var(--bd)"><b class="t">${esc(x.event)} <span class="mu note">${esc(x.when)}${x.forecast?' · forecast '+esc(x.forecast):''}${x.previous?' · previous '+esc(x.previous):''}</span></b>${line('If stronger than forecast:',x.if_stronger)}${line('If weaker than forecast:',x.if_weaker)}</div>`).join(''):'<span class="mu">No upcoming key events in the data.</span>';
  $('hl').innerHTML=s.headlines.length?'<table>'+s.headlines.map(h=>`<tr><td class="mu mono" style="white-space:nowrap">${when(h.t)}</td><td>${esc(h.title)}</td><td class="mu">${esc(h.source)}</td></tr>`).join('')+'</table>':'<span class="mu">No headlines were available.</span>';
 }else{['sm','cf','sp','up','hl'].forEach(i=>$(i).innerHTML='')}
 $('ev').innerHTML=d.events.length?'<table><tr><th>When</th><th>Event</th><th>Type</th><th>Impact</th><th>Forecast</th><th>Previous</th><th></th></tr>'+d.events.map(e=>`<tr><td class="mono" style="white-space:nowrap">${when(e.t)}</td><td>${esc(e.title)}</td><td><span class="chip">${esc(e.tag)}</span></td>
  <td class="${e.impact=='High'?'dn':'ac'}">${esc(e.impact)}</td><td>${esc(e.forecast||'–')}</td><td>${esc(e.previous||'–')}</td><td class="mu note">${e.released?'time passed':'upcoming'}</td></tr>`).join('')+'</table>'
  +'<div class="mu note" style="margin-top:8px">Scheduled times, forecasts and previous values come from the calendar feed. Actual results are not in this feed.</div>':'<span class="mu">No key USD events found for this week yet.</span>';
}
$('rf').onclick=async()=>{const b=$('rf'),m=$('st');if(CD>0){m.textContent='Please wait about '+Math.ceil(CD/60)+' minute(s) before refreshing again.';return}
 b.disabled=true;m.textContent='Requesting…';
 const d=await api('refresh',true);if(d.error){b.disabled=false;m.textContent=d.error;return}draw(d)};
async function load(){const d=await api('state');if(d.error){$('st').textContent=d.error;return}draw(d)}
load();setInterval(load,20000);
</script></body></html>