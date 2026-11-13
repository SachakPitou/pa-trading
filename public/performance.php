<?php require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/nav.php'; require_login(); $u = user(); ?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf" content="<?= csrf() ?>"><title>Performance · XAU/USD Scalp Terminal</title>
<style>
:root{--bg:#0c0e12;--card:#14171d;--tx:#e8eaed;--mu:#8b93a1;--bd:#242933;--up:#2ed39b;--dn:#ff5c75;--ac:#f0b429;--in:#0c0e12}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--tx);font:14px/1.45 system-ui,sans-serif}
header{display:flex;flex-wrap:wrap;gap:12px 24px;align-items:center;padding:12px 16px;border-bottom:1px solid var(--bd);background:var(--card)}
.logo{font-weight:800}.logo b{color:var(--ac)}.up{color:var(--up)}.dn{color:var(--dn)}.mu{color:var(--mu)}.mono{font:12px ui-monospace,monospace}
.nav{display:flex;gap:4px}.nav a{color:var(--mu);text-decoration:none;padding:5px 12px;border-radius:6px;font-weight:600}
.nav a:hover{color:var(--tx)}.nav a.on{background:var(--in);color:var(--ac);border:1px solid var(--bd)}
main{padding:14px 16px;display:grid;gap:14px;max-width:1100px;margin:0 auto}
.card{background:var(--card);border:1px solid var(--bd);border-radius:10px;padding:14px;min-width:0}
h2{margin:0 0 10px;font-size:12px;letter-spacing:.8px;text-transform:uppercase;color:var(--mu)}
select{font:inherit;color:var(--tx);background:var(--in);border:1px solid var(--bd);border-radius:6px;padding:6px 10px}
.bar{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px}
.kpi{background:var(--card);border:1px solid var(--bd);border-radius:10px;padding:12px}.kpi span{font-size:11px;color:var(--mu)}.kpi b{display:block;font-size:22px;margin-top:2px}.kpi small{color:var(--mu);font-size:11px}
.two{display:grid;grid-template-columns:2fr 1fr;gap:14px}@media(max-width:820px){.two{grid-template-columns:1fr}}
table{width:100%;border-collapse:collapse;font-size:13px}td{padding:6px 8px;border-bottom:1px solid var(--bd)}td:not(:first-child){text-align:right}
.db{margin:8px 0}.db div.t{display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px}.db div.b{height:8px;background:var(--in);border-radius:4px;overflow:hidden}.db div.b i{display:block;height:100%}
</style></head><body>
<header><div class="logo">XAU<b>/</b>USD <span class="mu">SCALP TERMINAL</span></div><?= nav('performance.php') ?>
<span class="mu" style="margin-left:auto"><?= h($u['email']) ?> · <a href="logout.php" style="color:var(--ac)">Logout</a></span></header>
<main>
 <div class="bar"><select id="per"><option value="">All time</option><option value="7">Last 7 days</option><option value="30">Last 30 days</option><option value="90">Last 90 days</option></select>
  <select id="tier"><option value="all">All signals</option><option value="confirmed">Confirmed only</option><option value="early">Early only</option></select>
  <span class="mu" id="sub"></span></div>
 <div id="kp" class="kpis"></div>
 <div class="two"><div class="card"><h2>Equity curve (cumulative R)</h2><div id="eq"></div></div>
  <div class="card"><h2>How trades ended</h2><div id="ds"></div></div></div>
 <div class="two"><div class="card"><h2>By trading session (UTC candle time)</h2><div id="se"></div></div>
  <div class="card"><h2>By direction</h2><div id="sd"></div></div></div>
</main>
<script>
const $=i=>document.getElementById(i);
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const sg=v=>v>0?'up':v<0?'dn':'';
const pf=g=>g.pf_inf?'∞':(g.pf??'–');
const day=n=>new Date(Date.now()-n*864e5).toISOString().slice(0,10);
const kpi=(k,v,sub,cls)=>`<div class="kpi"><span>${k}</span><b class="${cls||''}">${v}</b><small>${sub||'&nbsp;'}</small></div>`;
function curve(c){if(c.length<2)return'<div class="mu">Not enough closed trades yet.</div>';
 const w=600,h=170,p=6,mn=Math.min(0,...c),mx=Math.max(0,...c),rg=(mx-mn)||1,X=i=>p+i/(c.length-1)*(w-2*p),Y=v=>h-p-(v-mn)/rg*(h-2*p);
 const pts=c.map((v,i)=>X(i).toFixed(1)+','+Y(v).toFixed(1)).join(' '),up=c[c.length-1]>=0;
 return `<svg viewBox="0 0 ${w} ${h}" preserveAspectRatio="none" style="width:100%;height:170px"><line x1="0" x2="${w}" y1="${Y(0)}" y2="${Y(0)}" stroke="#3a4150" stroke-dasharray="4"/><polyline fill="none" stroke="${up?'#2ed39b':'#ff5c75'}" stroke-width="2" vector-effect="non-scaling-stroke" points="${pts}"/></svg><div class="mu mono" style="display:flex;justify-content:space-between"><span>Trade 1</span><span>Now: ${c[c.length-1]}R</span></div>`}
const tbl=rows=>rows.length?`<table><tr class="mu"><td>&nbsp;</td><td>Trades</td><td>Win rate</td><td>Net R</td><td>Avg R</td><td>PF</td></tr>${rows.map(([k,g])=>`<tr><td>${esc(k)}</td><td>${g.n}</td><td>${g.win_rate==null?'–':g.win_rate+'%'}</td><td class="${sg(g.net_r)}">${g.net_r}</td><td>${g.avg_r??'–'}</td><td>${pf(g)}</td></tr>`).join('')}</table>`:'<div class="mu">No data yet.</div>';
async function load(){
 const q=new URLSearchParams({action:'performance',tier:$('tier').value}),p=$('per').value;if(p)q.set('from',day(+p));
 const r=await fetch('api.php?'+q);if(r.status===401)location='login.php';const d=await r.json();
 if(d.error){$('kp').innerHTML=`<div class="card dn">${esc(d.error)}</div>`;return}
 const o=d.overall,S=d.streaks;
 if(!o.n){$('kp').innerHTML='<div class="card mu">No closed signals in this period yet. Results appear here as signals hit TP1, SL or expire.</div>';['eq','ds','se','sd'].forEach(i=>$(i).innerHTML='');$('sub').textContent='';return}
 $('sub').textContent=o.n+' closed signals · open signals are not counted';
 $('kp').innerHTML=kpi('Win rate',o.win_rate+'%',o.wins+' wins · '+o.losses+' losses',o.win_rate>=50?'up':'')
  +kpi('Profit factor',pf(o),'gross profit ÷ gross loss',o.pf_inf||o.pf>=1?'up':'dn')
  +kpi('Average R / trade',o.avg_r,'expectancy',sg(o.avg_r))
  +kpi('Net R',o.net_r,'total result',sg(o.net_r))
  +kpi('Max drawdown',d.max_drawdown_r+'R','now '+d.current_drawdown_r+'R below peak',d.max_drawdown_r>0?'dn':'')
  +kpi('Avg win / avg loss',(d.avg_win??'–')+' / '+(d.avg_loss??'–'),'in R')
  +kpi('Best / worst trade',d.best+' / '+d.worst,'in R')
  +kpi('Longest win streak',S.max_win,S.current&&S.current.type=='win'?'current: '+S.current.len:'','up')
  +kpi('Longest loss streak',S.max_loss,S.current&&S.current.type=='loss'?'current: '+S.current.len:'','dn');
 $('eq').innerHTML=curve(d.curve);
 const D=d.distribution,tot=D.TP1+D.SL+D.EXP||1,bar=(l,n,c)=>`<div class="db"><div class="t"><span>${l}</span><span>${n} · ${Math.round(n/tot*100)}%</span></div><div class="b"><i style="width:${n/tot*100}%;background:${c}"></i></div></div>`;
 $('ds').innerHTML=bar('Hit TP1',D.TP1,'#2ed39b')+bar('Hit stop loss',D.SL,'#ff5c75')+bar('Expired (closed on time)',D.EXP,'#f0b429')+'<div class="mu" style="font-size:11px;margin-top:8px">A signal closes at TP1, SL or on expiry. TP2 is a target level and is not tracked separately.</div>';
 $('se').innerHTML=tbl(Object.entries(d.by_session));$('sd').innerHTML=tbl(Object.entries(d.by_side));
}
$('per').onchange=load;$('tier').onchange=load;load();
</script></body></html>