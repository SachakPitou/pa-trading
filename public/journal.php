<?php require __DIR__.'/../lib/bootstrap.php'; require __DIR__.'/../lib/nav.php'; require_login(); $u = user(); ?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf" content="<?= csrf() ?>"><title>Journal · XAU/USD Scalp Terminal</title>
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
button,input,select,textarea{font:inherit;color:var(--tx);background:var(--in);border:1px solid var(--bd);border-radius:6px;padding:6px 10px}button{cursor:pointer}
button.p{background:var(--ac);color:#111;border-color:var(--ac);font-weight:600}textarea{width:100%;min-height:58px;resize:vertical}
label{display:block;font-size:11px;color:var(--mu);margin:8px 0 2px}.g{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:8px}.g input,.g select{width:100%}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px}
.kpi{background:var(--card);border:1px solid var(--bd);border-radius:10px;padding:12px}.kpi span{font-size:11px;color:var(--mu)}.kpi b{display:block;font-size:22px;margin-top:2px}.kpi small{color:var(--mu);font-size:11px}
.two{display:grid;grid-template-columns:1fr 1fr;gap:14px}@media(max-width:820px){.two{grid-template-columns:1fr}}
summary{cursor:pointer}.formsum{font-weight:700;color:var(--ac)}
.rule{display:flex;gap:8px;align-items:center;margin:4px 0;font-size:13px;color:var(--tx)}.rule input{width:auto}
.slot{border:1px dashed var(--bd);border-radius:8px;padding:8px;margin-top:4px}.slot:focus{outline:1px solid var(--ac)}
.tr{border:1px solid var(--bd);border-left-width:4px;border-radius:8px;padding:10px;margin-bottom:8px}.tr.BUY{border-left-color:var(--up)}.tr.SELL{border-left-color:var(--dn)}
.chip{font-size:11px;padding:1px 7px;border-radius:10px;background:var(--in);border:1px solid var(--bd);margin-right:4px;white-space:nowrap}
.shots{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px}.shots img{max-height:170px;max-width:100%;border-radius:6px;border:1px solid var(--bd)}
table{width:100%;border-collapse:collapse;font-size:13px}td{padding:5px 6px;border-bottom:1px solid var(--bd)}td:not(:first-child){text-align:right}
</style></head><body>
<header><div class="logo">XAU<b>/</b>USD <span class="mu">SCALP TERMINAL</span></div><?= nav('journal.php') ?>
<span class="mu" style="margin-left:auto"><?= h($u['email']) ?> · <a href="logout.php" style="color:var(--ac)">Logout</a></span></header>
<main>
 <div id="kp" class="kpis"></div>
 <div id="ins" class="two"></div>
 <details class="card" id="fm"><summary class="formsum">+ Log a trade</summary>
  <div class="g"><div><label>Direction</label><select id="side"><option>BUY</option><option>SELL</option></select></div>
   <div><label>Lots</label><input id="lots" type="number" step="0.01" value="0.10"></div>
   <div><label>Entry</label><input id="entry" type="number" step="0.01"></div>
   <div><label>Exit</label><input id="exit" type="number" step="0.01"></div>
   <div><label>Planned stop loss</label><input id="sl" type="number" step="0.01"></div>
   <div><label>Planned target</label><input id="tp" type="number" step="0.01"></div></div>
  <div class="two"><div><label>Why did you enter?</label><textarea id="er" maxlength="1000"></textarea></div>
   <div><label>Why did you exit?</label><textarea id="xr" maxlength="1000"></textarea></div></div>
  <div class="g"><div><label>How did you feel?</label><select id="emo"></select></div>
   <div><label>Discipline (1 poor – 5 perfect)</label><select id="dis"><option value="">–</option><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option></select></div></div>
  <label>Notes / lessons</label><textarea id="nt" maxlength="2000"></textarea>
  <label>Rules I followed (tick honestly)</label><div id="rl"></div>
  <div class="two"><div><label>Entry screenshot</label><input type="file" id="s1" accept="image/*"><div class="slot mu" tabindex="0" data-for="s1">Or click here and press Ctrl+V to paste</div></div>
   <div><label>Exit screenshot</label><input type="file" id="s2" accept="image/*"><div class="slot mu" tabindex="0" data-for="s2">Or click here and press Ctrl+V to paste</div></div></div>
  <div style="margin-top:12px;display:flex;gap:10px;align-items:center"><button class="p" id="save">Save trade</button><span id="msg" class="mu"></span></div>
 </details>
 <div class="card"><h2>Trades</h2><div id="list" class="mu">Loading…</div></div>
</main>
<script>
const $=i=>document.getElementById(i),CS=document.querySelector('meta[name=csrf]').content;
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const sg=v=>v>0?'up':v<0?'dn':'',n2=v=>v==null?'–':v;
const kpi=(k,v,sub,c)=>`<div class="kpi"><span>${k}</span><b class="${c||''}">${v}</b><small>${sub||'&nbsp;'}</small></div>`;
let RULES={};
async function shrink(file){if(!file)return null;const b=await createImageBitmap(file),k=Math.min(1,1600/b.width),c=document.createElement('canvas');
 c.width=Math.round(b.width*k);c.height=Math.round(b.height*k);c.getContext('2d').drawImage(b,0,0,c.width,c.height);return new Promise(r=>c.toBlob(r,'image/jpeg',.82))}
document.querySelectorAll('.slot').forEach(s=>s.addEventListener('paste',e=>{const f=[...(e.clipboardData?.files||[])][0];if(!f)return;
 const dt=new DataTransfer();dt.items.add(f);$(s.dataset.for).files=dt.files;s.textContent='Pasted: '+(f.name||'image')}));
function draw(d){
 const s=d.summary;RULES=d.rules;
 if(!$('rl').children.length){$('rl').innerHTML=Object.entries(d.rules).map(([k,l])=>`<label class="rule"><input type="checkbox" name="rules" value="${k}"> ${esc(l)}</label>`).join('');
  $('emo').innerHTML='<option value="">–</option>'+d.emotions.map(e=>`<option>${e}</option>`).join('')}
 if(!s.n){$('kp').innerHTML='<div class="card mu">No journal entries yet. Open “Log a trade” to add your first one.</div>';$('ins').innerHTML='';$('list').innerHTML='<span class="mu">Nothing here yet.</span>';return}
 $('kp').innerHTML=kpi('Trades logged',s.n)+kpi('Net P/L','$'+s.net_pnl,'',sg(s.net_pnl))
  +kpi('Planned R (avg)',n2(s.avg_planned_r),'what the plan offered')+kpi('Actual R (avg)',n2(s.avg_actual_r),'what you captured',sg(s.avg_actual_r))
  +kpi('Execution gap',s.avg_gap_r==null?'–':(s.avg_gap_r>0?'+':'')+s.avg_gap_r+'R','actual minus planned',sg(s.avg_gap_r))
  +kpi('Rule adherence',s.avg_adherence==null?'–':s.avg_adherence+'%','average of ticked rules')
  +kpi('Discipline',s.avg_discipline==null?'–':s.avg_discipline+' / 5','self-rated');
 const mb=Object.entries(s.most_broken),em=Object.entries(s.by_emotion);
 $('ins').innerHTML=`<div class="card"><h2>Strategy vs execution</h2>
  <div>Followed ≥80% of rules: <b>${s.followed.n}</b> trades · avg <b class="${sg(s.followed.avg_r)}">${n2(s.followed.avg_r)}R</b></div>
  <div>Followed &lt;80% of rules: <b>${s.broke.n}</b> trades · avg <b class="${sg(s.broke.avg_r)}">${n2(s.broke.avg_r)}R</b></div>
  <h2 style="margin-top:12px">Rules you break most</h2>${mb.length?mb.map(([k,v])=>`<div>${esc(k)} <span class="mu">· ${v}×</span></div>`).join(''):'<span class="mu">Nothing yet.</span>'}</div>
  <div class="card"><h2>Results by emotion</h2>${em.length?`<table><tr class="mu"><td>&nbsp;</td><td>Trades</td><td>Avg R</td><td>P/L</td></tr>${em.map(([k,v])=>`<tr><td>${esc(k)}</td><td>${v.n}</td><td class="${sg(v.avg_r)}">${n2(v.avg_r)}</td><td class="${sg(v.pnl)}">$${v.pnl}</td></tr>`).join('')}</table>`:'<span class="mu">Pick a feeling when you log a trade.</span>'}</div>`;
 $('list').innerHTML=d.trades.map(t=>{const dt=new Date(t.ts*1000).toLocaleString([], {dateStyle:'medium',timeStyle:'short'});
  const rl=t.rules?Object.entries(d.rules).map(([k,l])=>`<div style="font-size:12px"><b class="${t.rules[k]?'up':'dn'}">${t.rules[k]?'✓':'✗'}</b> ${esc(l)}</div>`).join(''):'';
  const sh=(t.has_entry?`<a href="journal_api.php?action=shot&id=${t.id}&k=entry" target="_blank"><img loading="lazy" src="journal_api.php?action=shot&id=${t.id}&k=entry" alt="Entry"></a>`:'')+(t.has_exit?`<a href="journal_api.php?action=shot&id=${t.id}&k=exit" target="_blank"><img loading="lazy" src="journal_api.php?action=shot&id=${t.id}&k=exit" alt="Exit"></a>`:'');
  return `<div class="tr ${t.side}"><div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap"><div><b>${t.side}</b> ${t.lots} lots · ${t.entry} → ${t.exit} <span class="mu">· ${dt}</span></div><b class="${sg(t.pnl)}">${t.pnl>=0?'+':''}$${t.pnl.toFixed(2)}</b></div>
  <div style="margin-top:4px">${t.actual_r!=null?`<span class="chip">Actual ${t.actual_r}R</span>`:''}${t.planned_r!=null?`<span class="chip">Planned ${t.planned_r}R</span>`:''}${t.adherence!=null?`<span class="chip">Rules ${t.adherence}%</span>`:''}${t.emotion?`<span class="chip">${esc(t.emotion)}</span>`:''}${t.discipline?`<span class="chip">Discipline ${t.discipline}/5</span>`:''}</div>
  ${(t.entry_reason||t.exit_reason||t.notes||rl||sh)?`<details style="margin-top:6px"><summary class="mu" style="font-size:12px">Details</summary>
   ${t.entry_reason?`<div style="margin-top:6px"><b style="font-size:12px">Why I entered</b><div>${esc(t.entry_reason)}</div></div>`:''}
   ${t.exit_reason?`<div style="margin-top:6px"><b style="font-size:12px">Why I exited</b><div>${esc(t.exit_reason)}</div></div>`:''}
   ${t.notes?`<div style="margin-top:6px"><b style="font-size:12px">Notes</b><div>${esc(t.notes)}</div></div>`:''}
   ${rl?`<div style="margin-top:6px"><b style="font-size:12px">Rules</b>${rl}</div>`:''}<div class="shots">${sh}</div>
   <button data-del="${t.id}" style="margin-top:8px;font-size:12px">Delete trade</button></details>`:`<button data-del="${t.id}" style="margin-top:6px;font-size:12px">Delete</button>`}</div>`}).join('');
 document.querySelectorAll('[data-del]').forEach(b=>b.onclick=async()=>{if(!confirm('Delete this trade and its screenshots?'))return;
  const fd=new FormData();fd.append('csrf',CS);fd.append('id',b.dataset.del);await fetch('journal_api.php?action=delete',{method:'POST',body:fd});load()});
}
async function load(){const r=await fetch('journal_api.php?action=list');if(r.status===401)location='login.php';draw(await r.json())}
$('save').onclick=async()=>{const m=$('msg');m.className='mu';m.textContent='Saving…';$('save').disabled=true;
 try{const fd=new FormData();fd.append('csrf',CS);
  [['side','side'],['lots','lots'],['entry','entry'],['exit','exit'],['sl','sl'],['tp','tp'],['entry_reason','er'],['exit_reason','xr'],['emotion','emo'],['discipline','dis'],['notes','nt']].forEach(([k,i])=>fd.append(k,$(i).value));
  document.querySelectorAll('input[name=rules]:checked').forEach(c=>fd.append('rules[]',c.value));
  const a=await shrink($('s1').files[0]),b=await shrink($('s2').files[0]);if(a)fd.append('shot_entry',a,'entry.jpg');if(b)fd.append('shot_exit',b,'exit.jpg');
  const r=await fetch('journal_api.php?action=add',{method:'POST',body:fd}),d=await r.json();
  if(d.error){m.className='dn';m.textContent=d.error}else{m.className='up';m.textContent='Saved ✓';
   ['entry','exit','sl','tp','er','xr','nt','emo','dis','s1','s2'].forEach(i=>$(i).value='');document.querySelectorAll('input[name=rules]').forEach(c=>c.checked=false);
   document.querySelectorAll('.slot').forEach(s=>s.textContent='Or click here and press Ctrl+V to paste');$('fm').open=false;load()}
 }catch(e){m.className='dn';m.textContent='Could not save: '+e.message}$('save').disabled=false};
load();
</script></body></html>