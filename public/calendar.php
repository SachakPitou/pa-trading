<?php require __DIR__.'/../lib/bootstrap.php'; require_login(); $u = user();
$off = (int)(strat()['tz_offset'] ?? 7); $today = gmdate('Y-m-d', time() + $off * 3600); ?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Signal Calendar · XAU/USD Scalp Terminal</title>
<style>
:root{--bg:#0c0e12;--card:#14171d;--tx:#e8eaed;--mu:#8b93a1;--bd:#242933;--up:#2ed39b;--dn:#ff5c75;--ac:#f0b429;--in:#0c0e12}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--tx);font:14px/1.45 system-ui,sans-serif}
header{display:flex;flex-wrap:wrap;gap:12px 24px;align-items:center;padding:12px 16px;border-bottom:1px solid var(--bd);background:var(--card)}
.logo{font-weight:800}.logo b{color:var(--ac)}.up{color:var(--up)}.dn{color:var(--dn)}.mu{color:var(--mu)}.mono{font:12px ui-monospace,monospace}
.nav{display:flex;gap:4px}.nav a{color:var(--mu);text-decoration:none;padding:5px 12px;border-radius:6px;font-weight:600}
.nav a:hover{color:var(--tx)}.nav a.on{background:var(--in);color:var(--ac);border:1px solid var(--bd)}
main{padding:12px 16px;display:grid;gap:12px;max-width:1400px;margin:0 auto}
.card{background:var(--card);border:1px solid var(--bd);border-radius:10px;padding:12px;min-width:0}
h2{margin:0 0 8px;font-size:12px;letter-spacing:.8px;text-transform:uppercase;color:var(--mu)}
button,input,select{font:inherit;color:var(--tx);background:var(--in);border:1px solid var(--bd);border-radius:6px;padding:6px 10px}button{cursor:pointer}
button:hover{border-color:#3a4150}button.p,button.on{background:var(--ac);color:#111;border-color:var(--ac);font-weight:600}
input[type=date]{color-scheme:dark}label{display:block;font-size:11px;color:var(--mu);margin-bottom:2px}
.toolbar{display:flex;flex-wrap:wrap;gap:12px 20px;align-items:flex-end}.presets{display:flex;flex-wrap:wrap;gap:6px}
.custom{display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end}
.kpis{display:grid;gap:10px;grid-template-columns:repeat(5,minmax(0,1fr))}@media(max-width:1000px){.kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
.kpi{background:var(--card);border:1px solid var(--bd);border-radius:10px;padding:12px;border-top:3px solid var(--bd)}
.kpi.g{border-top-color:var(--up)}.kpi.r{border-top-color:var(--dn)}.kpi.a{border-top-color:var(--ac)}
.kpi span{font-size:11px;letter-spacing:.6px;text-transform:uppercase;color:var(--mu)}
.kpi b{display:block;font:700 24px ui-monospace,monospace;margin:2px 0}.kpi small{color:var(--mu);font-size:12px}
.two{display:grid;gap:12px;grid-template-columns:minmax(0,1.7fr) minmax(0,1fr)}@media(max-width:960px){.two{grid-template-columns:1fr}}
.mnav{display:flex;align-items:center;gap:8px;margin-bottom:10px}.mnav b{flex:1;text-align:center;font-size:16px}
.cal{display:grid;gap:4px;grid-template-columns:repeat(7,minmax(0,1fr)) minmax(0,1.15fr)}
.dh{font-size:11px;color:var(--mu);text-transform:uppercase;text-align:center;padding:2px 0}
.cell{min-height:84px;border:1px solid var(--bd);border-radius:8px;padding:6px;background:transparent;display:flex;flex-direction:column;align-items:flex-start;gap:1px;text-align:left;line-height:1.25}
.cell.empty{border-color:transparent}.cell.we{opacity:.55}.cell.per{border-color:#3a4150}
.cell.today{box-shadow:0 0 0 2px var(--ac) inset}.cell.sel{outline:2px solid var(--tx);outline-offset:-2px}
.cell .n{font-size:11px;color:var(--mu)}.cell .cnt{font-size:11px;color:var(--mu)}.cell .wr{font-size:17px}.cell .nr{font:11px ui-monospace,monospace}
.cell.wk{background:var(--in);border-style:dashed;cursor:default}.cell.wk .n{text-transform:uppercase;letter-spacing:.5px}
@media(max-width:700px){.cal{grid-template-columns:repeat(7,minmax(0,1fr))}.cell.wk,.dh.wk,.cell .cnt,.cell .nr{display:none}.cell{min-height:52px;padding:4px}.cell .wr{font-size:13px}}
.legend{display:flex;gap:14px;flex-wrap:wrap;margin-top:10px;font-size:12px;color:var(--mu);align-items:center}.legend i{display:inline-block;width:12px;height:12px;border-radius:3px;margin-right:4px;vertical-align:-2px}
.msum{margin-top:8px;padding-top:8px;border-top:1px solid var(--bd)}
.sig{border:1px solid var(--bd);border-left-width:4px;border-radius:8px;padding:8px;margin-bottom:8px}.sig.BUY{border-left-color:var(--up)}.sig.SELL{border-left-color:var(--dn)}
.r{display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap}.tag{font-size:10px;padding:1px 6px;border-radius:4px;background:var(--in)}
.dsum{display:flex;gap:14px;flex-wrap:wrap;margin:6px 0 10px}.dsum div{background:var(--in);border-radius:8px;padding:6px 10px}.dsum b{display:block;font:700 16px ui-monospace,monospace}
.tabs{display:flex;gap:6px;margin-bottom:8px}.tw{overflow:auto;max-height:440px}
table{width:100%;border-collapse:collapse;font-size:13px}th{font-size:11px;color:var(--mu);text-transform:uppercase;text-align:right;padding:5px 8px;border-bottom:1px solid var(--bd);position:sticky;top:0;background:var(--card)}
td{padding:6px 8px;border-bottom:1px solid var(--bd);text-align:right;white-space:nowrap}th:first-child,td:first-child{text-align:left}
.bar{display:inline-block;width:60px;height:6px;border-radius:3px;background:var(--in);vertical-align:middle;margin-right:6px;overflow:hidden}.bar i{display:block;height:100%;background:var(--up)}
.note{font-size:12px}#err{color:var(--dn)}
</style></head><body>
<header><div class="logo">XAU<b>/</b>USD <span class="mu">SCALP TERMINAL</span></div><?= nav('calendar.php') ?>
<span class="mu" style="margin-left:auto"><?= h($u['email']) ?> · <a href="logout.php" style="color:var(--ac)">Logout</a></span></header>
<main>
 <section class="card toolbar">
  <div><label>Period</label><div class="presets" id="presets">
   <button data-p="today">Today</button><button data-p="week">This week</button><button data-p="month">This month</button>
   <button data-p="d7">Last 7 days</button><button data-p="d30">Last 30 days</button><button data-p="d90">Last 90 days</button>
   <button data-p="year">This year</button><button data-p="all">All time</button></div></div>
  <div class="custom"><div><label for="from">From</label><input type="date" id="from"></div><div><label for="to">To</label><input type="date" id="to"></div>
   <button class="p" id="apply">Show</button></div>
  <div><label for="tier">Signals</label><select id="tier"><option value="all">All signals</option><option value="confirmed">Confirmed only</option><option value="early">Early setups only</option></select></div>
  <div class="mu note" id="range" style="flex:1;min-width:200px"></div>
 </section>
 <div id="err"></div>
 <section class="kpis" id="kpis"></section>
 <section class="two">
  <div class="card">
   <div class="mnav"><button id="prev" aria-label="Previous month">◀</button><b id="mtitle">–</b><button id="thism">Today</button><button id="next" aria-label="Next month">▶</button></div>
   <div class="cal" id="grid"></div>
   <div class="legend"><span><i style="background:rgba(46,211,155,.4)"></i>Profit day</span><span><i style="background:rgba(255,92,117,.4)"></i>Losing day</span>
    <span><i style="border:1px solid #3a4150"></i>Inside selected period</span><span class="mu">Click a day for its signals. Win % = wins ÷ closed signals.</span></div>
   <div class="msum mono" id="msum"></div>
  </div>
  <div class="card"><h2>Day detail</h2><div id="day" class="mu">Click a day in the calendar.</div></div>
 </section>
 <section class="card"><h2>Cumulative result (R) over the period</h2><div id="curve"></div></section>
 <section class="card"><div class="tabs" id="tabs"><button data-t="week" class="on">By week</button><button data-t="month">By month</button><button data-t="day">By day</button></div>
  <div class="tw"><table id="tbl"></table></div></section>
</main>
<script>
const TODAY='<?= h($today) ?>';
const $=i=>document.getElementById(i);
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const D=s=>new Date(s+'T00:00:00Z'),F=d=>d.toISOString().slice(0,10);
const addDays=(s,n)=>{const d=D(s);d.setUTCDate(d.getUTCDate()+n);return F(d)};
const monday=s=>addDays(s,-((D(s).getUTCDay()+6)%7));
const mStart=s=>s.slice(0,7)+'-01';
const mEnd=s=>{const d=D(mStart(s));d.setUTCMonth(d.getUTCMonth()+1);d.setUTCDate(0);return F(d)};
const shiftM=(ym,n)=>{const d=D(ym+'-01');d.setUTCMonth(d.getUTCMonth()+n);return F(d).slice(0,7)};
const fmt=(s,o)=>D(s).toLocaleDateString('en-GB',Object.assign({timeZone:'UTC'},o));
const pct=v=>v==null?'–':Math.round(v)+'%';
const R=(v,d=2)=>v==null?'–':(v>0?'+':'')+(+v).toFixed(d)+'R';
const cl=v=>v>0?'up':(v<0?'dn':'mu');
const wc=v=>v==null?'mu':(v>=50?'up':'dn');
const PRE={today:()=>[TODAY,TODAY],week:()=>[monday(TODAY),addDays(monday(TODAY),6)],month:()=>[mStart(TODAY),mEnd(TODAY)],
 d7:()=>[addDays(TODAY,-6),TODAY],d30:()=>[addDays(TODAY,-29),TODAY],d90:()=>[addDays(TODAY,-89),TODAY],year:()=>[TODAY.slice(0,4)+'-01-01',TODAY],all:()=>['all',TODAY]};
const S={p:'month',from:'',to:'',tier:'all',gm:TODAY.slice(0,7),sel:null,tab:'week',per:null,mon:null,tz:7};

async function q(from,to,sig){
 const u='api.php?action=calendar&from='+encodeURIComponent(from)+'&to='+encodeURIComponent(to)+'&tier='+S.tier+(sig?'&signals=1':'');
 const r=await fetch(u);if(r.status===401){location='login.php';return null}return r.json();}

function kpis(t){
 const best=t.best_day,worst=t.worst_day,k=(c,l,v,s)=>`<div class="kpi ${c}"><span>${l}</span><b class="${c=='g'?'up':c=='r'?'dn':''}">${v}</b><small>${s}</small></div>`;
 $('kpis').innerHTML=k('a','Signals',t.signals,t.active_days+' active days · '+t.open+' still open')
  +k(t.win_rate==null?'':(t.win_rate>=50?'g':'r'),'Win rate',pct(t.win_rate),t.wins+' won · '+t.losses+' lost · '+t.closed+' closed')
  +k(t.closed?(t.net_r>=0?'g':'r'):'','Net result',R(t.net_r),'average '+R(t.avg_r)+' per closed signal')
  +k(best?'g':'','Best day',best?R(best.net_r):'–',best?fmt(best.date,{weekday:'short',day:'numeric',month:'short'}):'no closed signals')
  +k(worst&&worst.net_r<0?'r':'','Worst day',worst?R(worst.net_r):'–',worst?fmt(worst.date,{weekday:'short',day:'numeric',month:'short'}):'no closed signals');}

function curve(days){
 const pts=[];let c=0;days.forEach(d=>{c+=d.net_r;pts.push([d.date,+c.toFixed(2)])});
 if(pts.length<2){$('curve').innerHTML='<div class="mu">Not enough data yet: this chart needs results on at least 2 days.</div>';return}
 const W=720,H=170,P=30,ys=pts.map(p=>p[1]).concat(0),mn=Math.min(...ys),mx=Math.max(...ys),rg=(mx-mn)||1;
 const X=i=>P+i*(W-P*2)/(pts.length-1),Y=v=>H-P-(v-mn)/rg*(H-P*2),last=pts[pts.length-1][1],col=last>=0?'var(--up)':'var(--dn)';
 const line=pts.map((p,i)=>(i?'L':'M')+X(i).toFixed(1)+' '+Y(p[1]).toFixed(1)).join(' ');
 const area=line+' L'+X(pts.length-1).toFixed(1)+' '+Y(0).toFixed(1)+' L'+X(0).toFixed(1)+' '+Y(0).toFixed(1)+' Z';
 $('curve').innerHTML=`<svg viewBox="0 0 ${W} ${H}" style="width:100%;height:auto;max-height:230px" role="img" aria-label="Cumulative R">
  <line x1="${P}" x2="${W-P}" y1="${Y(0)}" y2="${Y(0)}" stroke="#3a4150" stroke-dasharray="4 4"/>
  <path d="${area}" fill="${col}" opacity=".12"/><path d="${line}" fill="none" stroke="${col}" stroke-width="2.2" stroke-linejoin="round"/>
  <circle cx="${X(pts.length-1)}" cy="${Y(last)}" r="4" fill="${col}"/>
  <text x="${W-P}" y="${Y(last)-9}" text-anchor="end" fill="${col}" font-size="13" font-weight="700">${R(last)}</text>
  <text x="${P}" y="${H-8}" fill="#8b93a1" font-size="11">${esc(pts[0][0])}</text><text x="${W-P}" y="${H-8}" text-anchor="end" fill="#8b93a1" font-size="11">${esc(pts[pts.length-1][0])}</text>
  <text x="4" y="${Y(mx)+4}" fill="#8b93a1" font-size="10">${mx.toFixed(1)}</text><text x="4" y="${Y(mn)+4}" fill="#8b93a1" font-size="10">${mn.toFixed(1)}</text></svg>`;}

function table(){
 const p=S.per;if(!p)return;let rows,lab,head;
 if(S.tab=='week'){rows=p.weeks;head='Week';lab=x=>esc(x.week)+' <span class="mu">'+fmt(x.start,{day:'numeric',month:'short'})+' – '+fmt(x.end,{day:'numeric',month:'short'})+'</span>';}
 else if(S.tab=='month'){rows=p.months;head='Month';lab=x=>fmt(x.month+'-01',{month:'long',year:'numeric'});}
 else{rows=p.days.slice().reverse();head='Day';lab=x=>fmt(x.date,{weekday:'short',day:'numeric',month:'short',year:'numeric'});}
 if(S.tab!='day')rows=rows.slice().reverse();
 $('tbl').innerHTML='<tr><th>'+head+'</th><th>Signals</th><th>Won</th><th>Lost</th><th>Open</th><th>Win rate</th><th>Net R</th></tr>'
  +(rows.length?rows.map(x=>`<tr><td>${lab(x)}</td><td>${x.signals}</td><td class="up">${x.wins}</td><td class="dn">${x.losses}</td><td class="mu">${x.open}</td>
   <td><span class="bar"><i style="width:${x.win_rate||0}%"></i></span><b class="${wc(x.win_rate)}">${pct(x.win_rate)}</b></td><td class="mono ${cl(x.net_r)}">${R(x.net_r)}</td></tr>`).join('')
   :'<tr><td colspan="7" class="mu" style="text-align:center;padding:16px">No signals in this period.</td></tr>');}

function grid(){
 const m=S.mon;if(!m)return;const by={};m.days.forEach(x=>by[x.date]=x);
 const first=mStart(S.gm),n=+mEnd(S.gm).slice(8),lead=(D(first).getUTCDay()+6)%7;
 const mx=Math.max(1,...m.days.map(x=>Math.abs(x.net_r)));
 $('mtitle').textContent=fmt(first,{month:'long',year:'numeric'});$('next').disabled=S.gm>=TODAY.slice(0,7);
 let cells=[];for(let i=0;i<lead;i++)cells.push(null);for(let d=1;d<=n;d++)cells.push(S.gm+'-'+String(d).padStart(2,'0'));while(cells.length%7)cells.push(null);
 let h=['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].map(x=>'<div class="dh">'+x+'</div>').join('')+'<div class="dh wk">Week</div>';
 for(let w=0;w<cells.length/7;w++){const a={s:0,c:0,w:0,r:0};
  for(let k=0;k<7;k++){const dt=cells[w*7+k];if(!dt){h+='<div class="cell empty"></div>';continue}
   const x=by[dt],cls=['cell'];if(k>=5)cls.push('we');if(dt==TODAY)cls.push('today');if(dt==S.sel)cls.push('sel');if(S.from!='all'&&dt>=S.from&&dt<=S.to||S.from=='all'&&dt<=S.to)cls.push('per');
   let st='',body='<span class="n">'+(+dt.slice(8))+'</span>';
   if(x){a.s+=x.signals;a.c+=x.closed;a.w+=x.wins;a.r+=x.net_r;
    if(x.closed){const al=(.12+.3*Math.min(1,Math.abs(x.net_r)/mx)).toFixed(2);st=x.net_r>0?`background:rgba(46,211,155,${al})`:x.net_r<0?`background:rgba(255,92,117,${al})`:'background:rgba(240,180,41,.12)';}
    body+=`<span class="cnt">${x.signals} signal${x.signals>1?'s':''}</span>`+(x.closed?`<b class="wr ${wc(x.win_rate)}">${pct(x.win_rate)}</b><span class="nr ${cl(x.net_r)}">${R(x.net_r,1)}</span>`:'<b class="wr mu">open</b>');}
   h+=`<button class="${cls.join(' ')}" data-d="${dt}" style="${st}">${body}</button>`;}
  const wr=a.c?a.w/a.c*100:null;
  h+='<div class="cell wk"><span class="n">Week</span>'+(a.s?`<span class="cnt">${a.s} signal${a.s>1?'s':''}</span><b class="wr ${wc(wr)}">${pct(wr)}</b><span class="nr ${cl(a.r)}">${R(a.r,1)}</span>`:'<span class="cnt">–</span>')+'</div>';}
 $('grid').innerHTML=h;
 const t=m.total;$('msum').innerHTML=`<b>${fmt(first,{month:'long',year:'numeric'})}</b> · ${t.signals} signals · win rate <b class="${wc(t.win_rate)}">${pct(t.win_rate)}</b> (${t.wins}W / ${t.losses}L${t.open?' / '+t.open+' open':''}) · net <b class="${cl(t.net_r)}">${R(t.net_r)}</b>`;}

function day(){
 const m=S.mon,el=$('day');if(!S.sel||!m){el.className='mu';el.textContent='Click a day in the calendar.';return}
 const x=m.days.find(d=>d.date==S.sel),list=(m.signals||[]).filter(s=>s.date==S.sel);
 let h='<b style="font-size:15px">'+fmt(S.sel,{weekday:'long',day:'numeric',month:'long',year:'numeric'})+'</b>';
 if(!x){h+='<div class="mu" style="margin-top:8px">No signals on this day.</div>';el.className='';el.innerHTML=h;return}
 h+=`<div class="dsum"><div><span class="mu">Signals</span><b>${x.signals}</b></div><div><span class="mu">Win rate</span><b class="${wc(x.win_rate)}">${pct(x.win_rate)}</b></div><div><span class="mu">Net</span><b class="${cl(x.net_r)}">${R(x.net_r)}</b></div></div>`;
 h+=list.map(s=>`<div class="sig ${esc(s.side)}"><div class="r"><b class="${s.side=='BUY'?'up':'dn'}">${esc(s.side)}</b><span class="tag">${s.tier=='early'?'EARLY':'CONFIRMED'}</span><span class="tag">${esc(s.status)}</span><span class="mu">${esc(s.time)} · score ${s.conf}</span></div>
  <div class="mono">E ${s.entry.toFixed(2)} · SL ${s.sl.toFixed(2)} · TP1 ${s.tp1.toFixed(2)} · TP2 ${s.tp2.toFixed(2)}</div>
  <div class="mu" style="font-size:11px">${esc(s.reasons)}${s.r!==null?' · <b class="'+cl(s.r)+'">'+R(s.r)+'</b>':''}</div></div>`).join('');
 el.className='';el.innerHTML=h;}

function sync(){$('from').value=S.from=='all'?'':S.from;$('to').value=S.to;
 document.querySelectorAll('#presets button').forEach(b=>b.classList.toggle('p',b.dataset.p==S.p));
 $('range').textContent='Showing '+(S.from=='all'?'all time':S.from)+' → '+S.to+' · days are in UTC'+(S.tz>=0?'+':'')+S.tz+' (Phnom Penh) · a signal counts on the day it was created.';}

let seqP=0,seqM=0;   // only the newest request may draw (fast clicks can make replies arrive out of order)
async function loadPeriod(){const my=++seqP;const d=await q(S.from,S.to,false);if(!d||my!==seqP)return;
 if(d.error){$('err').textContent=d.error;return}$('err').textContent='';
 S.per=d;S.from=d.from;S.to=d.to;S.tz=d.tz;sync();kpis(d.total);curve(d.days);table();}
async function loadMonth(){const my=++seqM;const d=await q(mStart(S.gm),mEnd(S.gm),true);if(!d||d.error||my!==seqM)return;S.mon=d;grid();day();}
const loadAll=()=>Promise.all([loadPeriod(),loadMonth()]);

$('presets').onclick=e=>{const p=e.target.dataset.p;if(!p)return;S.p=p;[S.from,S.to]=PRE[p]();
 const end=S.to>TODAY?TODAY:S.to;S.gm=end.slice(0,7);S.sel=null;sync();loadAll();};
$('apply').onclick=()=>{const f=$('from').value,t=$('to').value;if(!f||!t){$('err').textContent='Please choose both dates.';return}
 if(f>t){$('err').textContent='"From" must be before "To".';return}S.p='custom';S.from=f;S.to=t;S.gm=(t>TODAY?TODAY:t).slice(0,7);S.sel=null;sync();loadAll();};
$('tier').onchange=()=>{S.tier=$('tier').value;loadAll()};
$('prev').onclick=()=>{S.gm=shiftM(S.gm,-1);S.sel=null;loadMonth()};
$('next').onclick=()=>{if(S.gm<TODAY.slice(0,7)){S.gm=shiftM(S.gm,1);S.sel=null;loadMonth()}};
$('thism').onclick=()=>{S.gm=TODAY.slice(0,7);S.sel=TODAY;loadMonth()};
$('grid').onclick=e=>{const c=e.target.closest('[data-d]');if(c){S.sel=c.dataset.d;grid();day()}};
$('tabs').onclick=e=>{const t=e.target.dataset.t;if(!t)return;S.tab=t;document.querySelectorAll('#tabs button').forEach(b=>b.classList.toggle('on',b===e.target));table()};

[S.from,S.to]=PRE[S.p]();S.sel=TODAY;sync();loadAll();setInterval(loadAll,60000);
</script></body></html>