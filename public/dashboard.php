<?php require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/nav.php'; $u = user(); if (!$u) { header('Location: home.php'); exit; } ?>
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
/* trade levels: Entry yellow, Stop loss red, Take profit green */
.lvs{display:flex;flex-wrap:wrap;gap:6px;margin:6px 0}
.lv{font:700 13px ui-monospace,monospace;padding:3px 9px;border-radius:6px;border:1px solid;white-space:nowrap}
.lv b{font-size:10px;letter-spacing:.6px;margin-right:6px;opacity:.85}
.lv.e{color:#f0b429;background:rgba(240,180,41,.12);border-color:rgba(240,180,41,.5)}
.lv.sl{color:#ff5c75;background:rgba(255,92,117,.12);border-color:rgba(255,92,117,.5)}
.lv.tp{color:#2ed39b;background:rgba(46,211,155,.12);border-color:rgba(46,211,155,.5)}
.le{color:#f0b429!important}.ls{color:#ff5c75!important}.lt{color:#2ed39b!important}#en{border-color:rgba(240,180,41,.55)}#sl{border-color:rgba(255,92,117,.55)}#tp{border-color:rgba(46,211,155,.55)}
/* signal card: big side badge, coloured status, levels with reward:risk */
.sig{border-left-width:5px;padding:10px}.sig.BUY{background:linear-gradient(90deg,rgba(46,211,155,.08),transparent 45%)}.sig.SELL{background:linear-gradient(90deg,rgba(255,92,117,.08),transparent 45%)}
.sh{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.sgb{font-weight:800;font-size:15px;padding:2px 12px;border-radius:6px;letter-spacing:.5px}.sgb.BUY{background:var(--up);color:#06231a}.sgb.SELL{background:var(--dn);color:#2a0710}
.tag.te{color:#f0b429;border:1px solid rgba(240,180,41,.4)}.tag.tc{color:#2ed39b;border:1px solid rgba(46,211,155,.4)}
.tag.st.o{color:#f0b429;background:rgba(240,180,41,.15);animation:pl 1.6s infinite}.tag.st.w{color:#2ed39b;background:rgba(46,211,155,.15)}.tag.st.l{color:#ff5c75;background:rgba(255,92,117,.15)}.tag.st.x{color:var(--mu)}
.res{font:800 14px ui-monospace,monospace}.lv i{font-style:normal;font-size:10px;margin-left:6px;opacity:.75}@keyframes pl{50%{opacity:.5}}
.aib{margin-top:8px;border:1px solid rgba(138,125,255,.45);border-radius:8px;padding:6px 10px;background:rgba(138,125,255,.08)}.aib summary{cursor:pointer;font-size:13px}.tag.ai{color:#a99bff;font-weight:700}

/* --- modern dashboard layer --- */
body{background:radial-gradient(900px 300px at 50% -80px,rgba(240,180,41,.07),transparent),var(--bg)}
.card{padding:16px;box-shadow:0 1px 0 rgba(255,255,255,.03),0 12px 32px rgba(0,0,0,.18)}h2{font-size:13px;letter-spacing:.6px}
.kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;padding:12px 16px 0}
.kpi{background:var(--card);border:1px solid var(--bd);border-radius:12px;padding:12px 14px}
.kpi>span{font-size:11px;letter-spacing:.6px;text-transform:uppercase;color:var(--mu);font-weight:600}
.kpi b{display:block;font-size:20px;margin-top:2px}.kpi small{color:var(--mu);font-size:12px}.wn{color:var(--ac)}
.seg{display:flex;gap:4px;background:var(--in);border:1px solid var(--bd);border-radius:10px;padding:4px;margin-bottom:16px;overflow-x:auto}
.seg button{flex:1;border:0;background:none;color:var(--mu);font-weight:600;padding:9px 12px;border-radius:7px;white-space:nowrap}
.seg button.on{background:var(--card);color:var(--tx);box-shadow:0 0 0 1px var(--bd)}.pn[hidden]{display:none}
.rcg{display:grid;grid-template-columns:1.1fr .9fr;gap:20px}.f2{display:grid;grid-template-columns:1fr 1fr;gap:12px}.f2 input{width:100%}.f2 .full{grid-column:1/-1}
.chips{display:flex;gap:6px;margin-top:6px}.chips button{flex:1;padding:6px 0}.chips button.on{background:var(--ac);color:#111;border-color:var(--ac);font-weight:700}
.rout .stats{grid-template-columns:repeat(2,1fr)}.rout .stat b{font-size:22px}
#rsum{background:var(--in);border:1px solid var(--bd);border-radius:10px;padding:12px;line-height:1.6}#rwarn{color:var(--ac);min-height:18px;margin-top:8px;font-size:13px}
.chn{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px;border:1px solid var(--bd);border-radius:12px;background:var(--in);margin-bottom:10px;flex-wrap:wrap}
.chh{display:flex;gap:12px;align-items:center}.ico{font-size:22px}#tgact{display:flex;align-items:center;gap:8px;flex-wrap:wrap}button.gh{background:none;color:var(--mu)}
.sw{position:relative;display:inline-block;width:42px;height:24px;margin:0}.sw input{opacity:0;width:0;height:0;position:absolute}
.sw span{position:absolute;inset:0;background:var(--bd);border-radius:24px;cursor:pointer;transition:.15s}
.sw span:before{content:"";position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.15s}
.sw input:checked+span{background:var(--up)}.sw input:checked+span:before{transform:translateX(18px)}
@media(max-width:900px){.kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.rcg{grid-template-columns:1fr}}
</style></head><body>
<header><a class="logo" href="dashboard.php" style="text-decoration:none;color:inherit">XAU<b>/</b>USD <span class="mu">Terminal</span></a>
<?= nav('dashboard.php') ?>
<div class="pxbox"><div class="px" id="px">–</div><div id="feed" class="mu mono">connecting…</div></div>
<div id="scan" class="chip mono mu" title="Click for scanner health">Scanner …</div>
<?= user_menu() ?></header>
<div class="wrap" id="gs" style="display:none;padding-top:12px"><div class="card gs">
<div class="gsh"><b>👋 New here? Four steps to use this terminal</b><button id="gsx" aria-label="Dismiss guide">✕</button></div>
<ol><li><b>Read the signal.</b> BUY means the idea is that gold goes up, SELL that it goes down.</li>
<li><b>Open the “why”.</b> Each signal lists the checks it passed, so you can judge it yourself.</li>
<li><b>Size your trade.</b> Use the Risk calculator below so a loss costs about 1% of your balance.</li>
<li><b>Always set the stop loss (SL).</b> It is your exit if the idea is wrong. No signal wins every time.</li></ol>
<div class="mu">Experienced? Dismiss this with ✕. You can reopen it from your account menu → Quick guide.</div></div></div>
<div class="kpis">
 <div class="kpi"><span>Scanner</span><div id="k_scan"><b>–</b><small>checking…</small></div></div>
 <div class="kpi"><span>Trading window</span><div id="k_win"><b>–</b><small>&nbsp;</small></div></div>
 <div class="kpi"><span>Data freshness</span><div id="k_data"><b>–</b><small>&nbsp;</small></div></div>
 <div class="kpi"><span>Data credits left</span><div id="k_api"><b>–</b><small>&nbsp;</small></div></div>
</div>
<div class="main">
 <div class="card"><h2>Chart (TradingView)</h2><div id="tv"></div></div>
 <div class="card"><h2 class="hd"><span>Signals</span><a href="performance.php">Performance →</a></h2>
  <div class="legend mu"><b>BUY</b> ↑ / <b>SELL</b> ↓ · <b>SL</b> stop loss (exit if wrong) · <b>TP</b> take profit (target) · <b>R:R</b> reward vs risk</div><div id="tiers"></div>
  <div id="sigs" style="max-height:430px;overflow:auto"></div></div>
</div>
<div class="wrap"><div class="card"><div class="tabs"><button class="on" data-tab="an">Market analysis</button><button data-tab="nw">USD news</button></div>
 <div id="an" class="mu">Loading…</div><div id="nw" class="mu" style="display:none;max-height:300px;overflow:auto">Loading…</div></div></div>
<div class="wrap" style="padding-top:12px"><div class="card" id="tools">
 <div class="seg" role="tablist"><button class="on" data-p="rc">🧮 Risk calculator</button><button data-p="al">🔔 Alerts</button><button data-p="hd">🩺 System health <span id="hs"></span></button></div>
 <div class="pn" id="p_rc"><div class="rcg">
  <div><div class="f2">
   <div><label>Account balance ($)</label><input id="bal" type="number" value="5000" min="0"></div>
   <div><label>Risk per trade (%)</label><input id="rk" type="number" value="1" step=".25" min="0"><div class="chips" id="rkc"><button data-v="0.5">0.5%</button><button data-v="1" class="on">1%</button><button data-v="2">2%</button></div></div>
   <div><label class="le">Entry price</label><input id="en" type="number" step=".01"></div>
   <div><label class="ls">Stop loss</label><input id="sl" type="number" step=".01"></div>
   <div><label class="lt">Take profit (optional)</label><input id="tp" type="number" step=".01"></div>
   <div><label>&nbsp;</label><button id="fill" style="width:100%">Use live price</button></div>
  </div><div class="mu" style="font-size:12px;margin-top:10px">Tip: most traders risk 1% or less per trade. Size is based on 1 lot = 100 oz of gold.</div></div>
  <div class="rout"><div class="stats"><div class="stat"><span class="mu">Position size (lots)</span><b id="o1">–</b></div><div class="stat"><span class="mu">Max loss</span><b id="o2">–</b></div><div class="stat"><span class="mu">Target gain</span><b id="o3">–</b></div><div class="stat"><span class="mu">Reward : risk</span><b id="o4">–</b></div></div>
   <div id="rsum" class="mu">Enter your entry and stop loss to size the trade.</div><div id="rwarn"></div></div>
 </div></div>
 <div class="pn" id="p_al" hidden>
  <div class="chn"><div class="chh"><span class="ico">✈️</span><div><b>Telegram</b><div class="mu" id="tgsub">Get signals instantly in Telegram. One tap to connect.</div></div></div><div id="tgact"></div></div>
  <div class="chn"><div class="chh"><span class="ico">✉️</span><div><b>Email</b><div class="mu"><?= h($u['email']) ?></div></div></div><label class="sw"><input type="checkbox" id="aml"><span></span></label></div>
  <div id="amsg" class="mu" style="min-height:18px"></div>
 </div>
 <div class="pn" id="p_hd" hidden><div id="hl" class="mu">Loading…</div></div>
</div></div>
<script src="https://s3.tradingview.com/tv.js"></script>
<script>
new TradingView.widget({container_id:'tv',symbol:'OANDA:XAUUSD',interval:'5',theme:'dark',style:'1',locale:'en',autosize:true,timezone:'Etc/UTC',studies:['MAExp@tv-basicstudies','RSI@tv-basicstudies']});
const $=i=>document.getElementById(i),CS=document.querySelector('meta[name=csrf]').content,f2=n=>(+n).toFixed(2);
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function api(a,body){const o=body?{method:'POST',headers:{'X-CSRF':CS,'Content-Type':'application/json'},body:JSON.stringify(body)}:{};
 const r=await fetch('api.php?action='+a,o);if(r.status===401)location='login.php';if(r.status===402)location='billing.php';return r.json();}
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
const aiBox=x=>{const a=x.ai;if(!a)return x.status==='OPEN'?'<div class="mu" style="font-size:11px;margin-top:8px">🤖 AI review: not available for this signal</div>':'';return`<details class="aib" ${x.status==='OPEN'?'open':''}><summary><span class="tag ai">🤖 AI REVIEW</span> ${esc(a.summary)}</summary>${aiList('Why it may be valid',a.why_valid,'up')}${aiList('Potential weaknesses',a.weaknesses,'dn')}${aiList('Conflicting indicators',a.conflicts)}${aiList('Invalidation conditions',a.invalidation)}<div class="mu" style="font-size:10px;margin-top:6px">AI review of the setup, not financial advice.</div></details>`};
/* signal card pieces */
const stCls=s=>/TP/.test(s)?'w':/SL/.test(s)?'l':s==='OPEN'?'o':'x';
const sigHead=x=>`<div class="sh"><span class="sgb ${esc(x.side)}">${x.side=='BUY'?'▲':'▼'} ${esc(x.side)}</span><span class="tag ${x.tier=='early'?'te':'tc'}">${x.tier=='early'?'⚡ EARLY':'✅ CONFIRMED'}</span><span class="tag st ${stCls(String(x.status))}">${esc(x.status)}</span><span class="tag">score ${x.conf}</span>${x.r!==null&&x.r!==undefined?`<span class="res ${x.r>0?'up':x.r<0?'dn':'mu'}">${x.r>0?'+':''}${x.r}R</span>`:''}<span class="mu" style="margin-left:auto;font-size:11px">${esc(x.created_at)}</span></div>`;
const sigLevels=x=>{const d=Math.abs(x.entry-x.sl)||1,rr=v=>(Math.abs(v-x.entry)/d).toFixed(1)+'R';return `<div class="lvs"><span class="lv e"><b>ENTRY</b>${f2(x.entry)}</span><span class="lv sl"><b>SL</b>${f2(x.sl)}</span><span class="lv tp"><b>TP1</b>${f2(x.tp1)}<i>${rr(x.tp1)}</i></span><span class="lv tp"><b>TP2</b>${f2(x.tp2)}<i>${rr(x.tp2)}</i></span></div>`};
async function loadSigs(){const d=await api('signals');sigs=d.signals;
 $('tiers').innerHTML='<div class="tiers">'+Object.entries(d.tiers).map(([k,t])=>`<div class="tier"><div class="r"><b>${k=='confirmed'?'Confirmed':'Early'}</b><span class="tag ${t.visible?'up':'dn'}">${t.visible?'LIVE':'PAUSED'}</span></div><div class="mu" style="font-size:11px">${t.win_rate==null?'No closed trades yet':t.win_rate+'% win · '+(t.net_r>=0?'+':'')+t.net_r+'R (last '+t.window+')'}</div>${t.visible?'':'<div class="dn" style="font-size:11px">Paused: win rate below '+t.min_win+'%</div>'}</div>`).join('')+'</div>';
 $('sigs').innerHTML=sigs.length?sigs.map(x=>`<div class="sig ${esc(x.side)}">${sigHead(x)}${sigLevels(x)}<div class="mu" style="font-size:11px">${esc(x.reasons||'')}</div>${whyBox(x)}${aiBox(x)}<button data-id="${x.id}" style="margin-top:6px;font-size:12px">Load to calculator</button></div>`).join(''):'<div class="mu">No signals yet. The scanner (cron.php) creates them on each closed candle.</div>';}
$('sigs').onclick=e=>{const s=sigs.find(x=>x.id==e.target.dataset.id);if(!s)return;$('en').value=f2(s.entry);$('sl').value=f2(s.sl);$('tp').value=f2(s.tp1);$('rc').open=true;calc();};
/* ---- Tools panel ---- */
function showTool(id){document.querySelectorAll('.seg button').forEach(b=>b.classList.toggle('on',b.dataset.p==id));document.querySelectorAll('.pn').forEach(p=>p.hidden=p.id!=='p_'+id)}
document.querySelectorAll('.seg button').forEach(b=>b.onclick=()=>showTool(b.dataset.p));
const kp=(id,v,c,sub)=>{$(id).innerHTML=`<b class="${c}">${v}</b><small>${sub}</small>`};
/* ---- Risk calculator ---- */
function calc(){const b=+$('bal').value,r=+$('rk').value,e=+$('en').value,s=+$('sl').value,t=+$('tp').value,d=Math.abs(e-s),w=$('rwarn'),m=$('rsum');
 ['o1','o2','o3','o4'].forEach(i=>$(i).textContent='–');w.textContent='';m.textContent='Enter your entry and stop loss to size the trade.';
 if(!b||!r||!e||!s||!d)return;
 const risk=b*r/100,lot=risk/(100*d),rw=t?Math.abs(t-e)*100*lot:0,side=s<e?'BUY':'SELL',bad=t&&((side=='BUY'&&t<=e)||(side=='SELL'&&t>=e));
 $('o1').textContent=lot.toFixed(2);$('o2').textContent='$'+f2(risk);$('o3').textContent=t&&!bad?'$'+f2(rw):'–';$('o4').textContent=t&&!bad?'1:'+(rw/risk).toFixed(2):'–';
 if(bad)w.textContent='⚠ For a '+side+', take profit should be '+(side=='BUY'?'above':'below')+' your entry.';
 else if(lot<0.01)w.textContent='⚠ Below the 0.01 minimum lot: use a tighter stop, a bigger account or a higher risk %.';
 m.innerHTML=`<b>${side}</b> ${lot.toFixed(2)} lots. If your stop is hit you lose about <b class="dn">$${f2(risk)}</b>`+(t&&!bad?`; if the target is hit you gain about <b class="up">$${f2(rw)}</b> (reward:risk 1:${(rw/risk).toFixed(2)}).`:'.');}
const syncChips=()=>document.querySelectorAll('#rkc button').forEach(x=>x.classList.toggle('on',+x.dataset.v==+$('rk').value));
try{const sb=localStorage.getItem('rc_bal'),sr=localStorage.getItem('rc_rk');if(sb)$('bal').value=sb;if(sr)$('rk').value=sr}catch(e){}
const saveRc=()=>{try{localStorage.setItem('rc_bal',$('bal').value);localStorage.setItem('rc_rk',$('rk').value)}catch(e){}};
['bal','rk','en','sl','tp'].forEach(i=>$(i).oninput=()=>{saveRc();syncChips();calc()});
document.querySelectorAll('#rkc button').forEach(b=>b.onclick=()=>{$('rk').value=b.dataset.v;saveRc();syncChips();calc()});
$('fill').onclick=()=>{$('en').value=f2(live);calc()};syncChips();calc();
/* ---- Alerts: Telegram one-tap connect ---- */
let tgLinked=false,tgOn=false,tgUrl='',tgPoll=0;const amsg=t=>{$('amsg').textContent=t};
const saveAl=async()=>{await api('settings',{tg:$('atg')?$('atg').checked:false,mail:$('aml').checked});amsg('Saved ✓')};
function tgRender(wait){const a=$('tgact'),s=$('tgsub');
 if(tgLinked){s.textContent='Connected ✓ Signal alerts go to your Telegram.';
  a.innerHTML=`<label class="sw"><input type="checkbox" id="atg" ${tgOn?'checked':''}><span></span></label><button id="atest">Send test</button><button id="atd" class="gh">Disconnect</button>`;
  $('atg').onchange=saveAl;$('atest').onclick=async()=>{const r=await api('test_alert',{});amsg(r.error||'Test sent to Telegram ✓')};
  $('atd').onclick=async()=>{await api('tg_disconnect',{});tgLinked=false;tgOn=false;tgRender();amsg('Telegram disconnected')};}
 else{s.textContent=wait?'Waiting… open Telegram and tap START.':'Get signals instantly in Telegram. One tap to connect.';
  a.innerHTML=wait?`<span class="mu">⏳ Waiting for Telegram</span><a id="tgl" target="_blank" rel="noopener" style="color:var(--ac)">Open Telegram again</a>`:`<button class="p" id="tgc">Connect Telegram</button>`;
  if(wait)$('tgl').href=tgUrl;else $('tgc').onclick=tgConnect;}}
async function tgConnect(){const w=window.open('','_blank');const r=await api('tg_connect',{});
 if(r.error){if(w)w.close();amsg(r.error);return}
 tgUrl=r.url;if(w)w.location=tgUrl;tgRender(true);if(!w)amsg('Popup blocked: use “Open Telegram again”.');
 clearInterval(tgPoll);let n=0;tgPoll=setInterval(async()=>{n++;const s=await api('tg_status');
  if(s.linked){clearInterval(tgPoll);tgLinked=true;tgOn=true;tgRender();amsg('Telegram connected ✓')}
  else if(n>90){clearInterval(tgPoll);tgRender();amsg('Link expired. Please try again.')}},2000)}
async function loadMe(){const m=await api('me');tgLinked=!!m.tg_linked;tgOn=!!m.tg;$('aml').checked=!!m.mail;tgRender()}
$('aml').onchange=saveAl;
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
$('scan').onclick=()=>{showTool('hd');$('tools').scrollIntoView({behavior:'smooth'})};
document.querySelectorAll('.tabs button').forEach(b=>b.onclick=()=>{document.querySelectorAll('.tabs button').forEach(x=>x.classList.toggle('on',x==b));$('an').style.display=b.dataset.tab=='an'?'':'none';$('nw').style.display=b.dataset.tab=='nw'?'':'none'});
/* Scanner health card */
const ago=t=>{if(!t)return'–';const m=Math.round((Date.now()/1000-t)/60);return m<1?'just now':m<90?m+' min ago':Math.round(m/60)+' h ago'};
const hcell=(k,v)=>`<div class="stat"><span class="mu">${k}</span><b>${v}</b></div>`;
async function loadHealth(){try{const h=await api('health');if(h.error){$('hl').textContent=h.error;return}
 const S=h.scanner,F=h.feed,Q=h.quota,Y=h.system,miss=F.gaps.reduce((a,g)=>a+g.missing,0);
 const hc=h.status=='ok'?'up':h.status=='down'?'dn':'wn';
 kp('k_scan',h.status=='ok'?'Running':h.status=='down'?'Down':'Check',hc,'Last scan '+ago(S.heartbeat));
 kp('k_win',h.expected_active?'Open':'Closed',h.expected_active?'up':'mu',h.expected_active?'Scanning for signals':'Quiet hours, idle is normal');
 kp('k_data',S.last_candle?S.candle_age_min+' min':'–',S.candle_age_min>15?'dn':'up','Age of the latest candle');
 kp('k_api',Q.remaining+' / '+Q.budget,Q.remaining<50?'dn':'','Market-data requests today');
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
try{ if(!localStorage.getItem('gs_off')) $('gs').style.display='block';
 $('gsx').onclick=()=>{ $('gs').style.display='none'; try{localStorage.setItem('gs_off','1')}catch(e){} }; }catch(e){}
</script></body></html>