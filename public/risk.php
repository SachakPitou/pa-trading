<?php require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/nav.php'; require_login(); $u = user(); ?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf" content="<?= csrf() ?>"><title>Risk · XAU/USD Scalp Terminal</title>
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
button,input,select{font:inherit;color:var(--tx);background:var(--in);border:1px solid var(--bd);border-radius:6px;padding:6px 10px}button{cursor:pointer}
button.p{background:var(--ac);color:#111;border-color:var(--ac);font-weight:600}
label{display:block;font-size:11px;color:var(--mu);margin:8px 0 2px}.g{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:8px}.g input,.g select{width:100%}
.two{display:grid;grid-template-columns:1fr 1fr;gap:14px}@media(max-width:820px){.two{grid-template-columns:1fr}}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px}
.kpi{background:var(--in);border:1px solid var(--bd);border-radius:10px;padding:12px}.kpi span{font-size:11px;color:var(--mu)}.kpi b{display:block;font-size:22px;margin-top:2px}.kpi small{color:var(--mu);font-size:11px}
.banner{border:1px solid var(--bd);border-left-width:5px;border-radius:10px;padding:12px 14px;background:var(--card);font-weight:700}
.banner.ok{border-left-color:var(--up)}.banner.warn{border-left-color:var(--ac)}.banner.stop{border-left-color:var(--dn)}
.ck{margin:10px 0}.ck .t{display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px}.ck .b{height:8px;background:var(--in);border-radius:4px;overflow:hidden}.ck .b i{display:block;height:100%}
.ck .m{font-size:12px;margin-top:3px}
.warnbox{border:1px solid var(--ac);border-radius:8px;padding:8px 10px;margin-top:10px;font-size:13px;color:var(--ac)}
.pos{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;border:1px solid var(--bd);border-left-width:4px;border-radius:8px;padding:8px 10px;margin-bottom:6px}
.pos.BUY{border-left-color:var(--up)}.pos.SELL{border-left-color:var(--dn)}
.chk{display:flex;gap:8px;align-items:center;margin-top:10px;font-size:13px;color:var(--tx)}.chk input{width:auto}
</style></head><body>
<header><div class="logo">XAU<b>/</b>USD <span class="mu">SCALP TERMINAL</span></div><?= nav('risk.php') ?>
<span class="mu" style="margin-left:auto"><?= h($u['email']) ?> · <a href="logout.php" style="color:var(--ac)">Logout</a></span></header>
<main>
 <div id="bn" class="banner mu">Loading…</div>
 <div class="two">
  <div class="card"><h2>Trading guard (today)</h2><div id="ck"></div><div id="cd"></div></div>
  <div class="card"><h2>Open positions</h2><div id="pl" class="mu"></div>
   <div class="g" style="margin-top:6px"><div><label>Direction</label><select id="p_side"><option>BUY</option><option>SELL</option></select></div>
    <div><label>Lots</label><input id="p_lots" type="number" step="0.01" value="0.10"></div>
    <div><label>Entry</label><input id="p_entry" type="number" step="0.01"></div>
    <div><label>Stop loss</label><input id="p_sl" type="number" step="0.01"></div></div>
   <div style="margin-top:10px;display:flex;gap:10px;align-items:center"><button class="p" id="p_add">Add position</button><span id="p_msg" class="mu"></span></div>
   <div class="mu" style="font-size:11px;margin-top:8px">List the trades you currently have open. Remove each one when it closes. Their stop-loss risk adds up to your open risk.</div></div>
 </div>
 <div class="two">
  <div class="card"><h2>Position size &amp; risk calculator</h2>
   <div class="g"><div><label>Account balance</label><input id="c_balance" type="number" step="0.01"></div>
    <div><label>Risk %</label><input id="c_risk_pct" type="number" step="0.05"></div>
    <div><label>Entry price</label><input id="c_entry" type="number" step="0.01"></div>
    <div><label>Stop-loss price</label><input id="c_sl" type="number" step="0.01"></div>
    <div><label>Take profit (optional)</label><input id="c_tp" type="number" step="0.01"></div></div>
   <div class="mu" style="font-size:11px;margin-top:8px">Direction is read from your prices: stop below entry = BUY, stop above entry = SELL.</div>
   <div id="cr" style="margin-top:12px"></div></div>
  <div class="card"><h2>Account, broker &amp; limits</h2>
   <div class="g"><div><label>Account balance</label><input id="s_balance" type="number" step="0.01"></div>
    <div><label>Default risk % per trade</label><input id="s_risk_pct" type="number" step="0.05"></div></div>
   <div class="mu" style="font-size:11px;margin:10px 0 0">Broker contract (look it up in your platform's symbol specification)</div>
   <div class="g"><div><label>Contract size (units per lot)</label><input id="s_contract_size" type="number" step="any"></div>
    <div><label>Tick size</label><input id="s_tick_size" type="number" step="any"></div>
    <div><label>Tick value (per lot)</label><input id="s_tick_value" type="number" step="any"></div>
    <div><label>Lot step</label><input id="s_lot_step" type="number" step="any"></div></div>
   <button id="auto" style="margin-top:6px;font-size:12px">Set tick value = contract size × tick size</button>
   <div class="mu" style="font-size:11px;margin:12px 0 0">Risk limits (0 = switched off)</div>
   <div class="g"><div><label>Daily loss limit (% of balance)</label><input id="s_daily_loss_pct" type="number" step="0.1"></div>
    <div><label>Max consecutive losses</label><input id="s_max_consec_losses" type="number" step="1"></div>
    <div><label>Max open risk (% of balance)</label><input id="s_max_open_risk_pct" type="number" step="0.1"></div></div>
   <label class="chk"><input type="checkbox" id="s_cooldown_on"> Cooldown after hitting the loss streak limit</label>
   <div class="g"><div><label>Cooldown (minutes)</label><input id="s_cooldown_min" type="number" step="1"></div></div>
   <div style="margin-top:12px;display:flex;gap:10px;align-items:center"><button class="p" id="save">Save settings</button><span id="msg" class="mu"></span></div></div>
 </div>
</main>
<script>
const $=i=>document.getElementById(i),CS=document.querySelector('meta[name=csrf]').content;
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money=v=>(v<0?'-':'')+'$'+Math.abs(v).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
const NUM=['balance','risk_pct','contract_size','tick_size','tick_value','lot_step','daily_loss_pct','max_consec_losses','max_open_risk_pct','cooldown_min'];
const COL={ok:'var(--up)',warn:'var(--ac)',breach:'var(--dn)',off:'var(--bd)'},TXT={ok:'up',warn:'ac',breach:'dn',off:'mu'};
async function api(action,data){
 const o={};if(data){const fd=new FormData();fd.append('csrf',CS);Object.entries(data).forEach(([k,v])=>fd.append(k,v));o.method='POST';o.body=fd}
 const r=await fetch('risk_api.php?action='+action,o);if(r.status===401)location='login.php';return r.json()}

function fillSettings(s){NUM.forEach(k=>$('s_'+k).value=s[k]);$('s_cooldown_on').checked=!!s.cooldown_on;$('c_balance').value=s.balance;$('c_risk_pct').value=s.risk_pct}
function draw(d){
 const g=d.guard,B=$('bn'),M={ok:['✓ Within your risk limits','ok'],warn:['⚠ Approaching a risk limit','warn'],stop:['⛔ Stop trading — a risk limit has been hit','stop']}[g.status];
 B.className='banner '+M[1];B.innerHTML=M[0]+`<span class="mu" style="font-weight:400;margin-left:12px">Today: ${g.today_trades} trade${g.today_trades==1?'':'s'} · P/L <b class="${g.today_pnl>0?'up':g.today_pnl<0?'dn':''}">${money(g.today_pnl)}</b></span>`;
 $('ck').innerHTML=g.checks.map(c=>{const p=c.limit>0?Math.min(100,c.used/c.limit*100):0,v=c.unit==='$'?money(c.used)+' / '+money(c.limit):c.used+' / '+c.limit;
  return `<div class="ck"><div class="t"><span>${esc(c.label)}</span><span class="${TXT[c.status]}">${c.status==='off'?'Off':v}</span></div>
  <div class="b"><i style="width:${p}%;background:${COL[c.status]}"></i></div>${c.msg?`<div class="m ${TXT[c.status]}">${esc(c.msg)}</div>`:''}</div>`}).join('');
 const cd=g.cooldown;$('cd').innerHTML=cd.on?(cd.active?`<div class="warnbox" style="border-color:var(--dn);color:var(--dn)">Cooldown active — ${cd.mins_left} min left (until ${new Date(cd.until*1000).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}).</div>`:'<div class="mu" style="font-size:12px">Cooldown is on: not active right now.</div>'):'<div class="mu" style="font-size:12px">Cooldown is off.</div>';
 $('pl').innerHTML=d.positions.length?d.positions.map(p=>`<div class="pos ${p.side}"><span><b>${p.side}</b> ${p.lots} lots · ${p.entry} → SL ${p.sl}</span><span><span class="mu">risk</span> <b>${money(p.risk)}</b> <button data-del="${p.id}" style="margin-left:8px;font-size:12px">Remove</button></span></div>`).join(''):'<span class="mu">No open positions.</span>';
 document.querySelectorAll('[data-del]').forEach(b=>b.onclick=async()=>{draw(await api('pos_del',{id:b.dataset.del}));calc()});
}
function showCalc(r){
 const k=(a,v,s,c)=>`<div class="kpi"><span>${a}</span><b class="${c||''}">${v}</b><small>${s||'&nbsp;'}</small></div>`;
 $('cr').innerHTML=`<div class="kpis">${k('Position size',r.lots+' lots',r.side+' · exact '+r.lots_exact,r.lots>0?'ac':'dn')}${k('Loss if stopped',money(-r.risk_actual),r.risk_actual_pct+'% of balance (target '+money(r.risk_money)+')','dn')}
  ${k('Stop distance',r.stop_distance,r.stop_ticks+' ticks')}${r.rr!=null?k('Potential reward',money(r.reward),'reward : risk = '+r.rr+' : 1','up'):k('Reward : risk','–','add a take profit')}
  ${k('Notional value',money(r.notional),'lots × contract × entry')}</div>`+r.warnings.map(w=>`<div class="warnbox">${esc(w)}</div>`).join('')}
let T;async function calc(){
 const v=i=>$('c_'+i).value;if(!v('entry')||!v('sl')){$('cr').innerHTML='<span class="mu">Enter an entry and a stop-loss price to see the position size.</span>';return}
 const r=await api('calc',{balance:v('balance'),risk_pct:v('risk_pct'),entry:v('entry'),sl:v('sl'),tp:v('tp')});
 if(r.error)$('cr').innerHTML=`<span class="dn">${esc(r.error)}</span>`;else showCalc(r)}
['balance','risk_pct','entry','sl','tp'].forEach(i=>$('c_'+i).addEventListener('input',()=>{clearTimeout(T);T=setTimeout(calc,300)}));

$('auto').onclick=()=>{$('s_tick_value').value=+(($('s_contract_size').value||0)*($('s_tick_size').value||0)).toFixed(6)};
$('save').onclick=async()=>{const m=$('msg');m.className='mu';m.textContent='Saving…';
 const o={cooldown_on:$('s_cooldown_on').checked?1:0};NUM.forEach(k=>o[k]=$('s_'+k).value);
 const d=await api('save',o);if(d.error){m.className='dn';m.textContent=d.error;return}
 m.className='up';m.textContent='Saved ✓';fillSettings(d.settings);draw(d);calc()};
$('p_add').onclick=async()=>{const m=$('p_msg');m.className='mu';m.textContent='';
 const d=await api('pos_add',{side:$('p_side').value,lots:$('p_lots').value,entry:$('p_entry').value,sl:$('p_sl').value});
 if(d.error){m.className='dn';m.textContent=d.error;return}
 ['p_entry','p_sl'].forEach(i=>$(i).value='');draw(d);calc()};
(async()=>{const d=await api('state');fillSettings(d.settings);draw(d);calc()})();
</script></body></html>