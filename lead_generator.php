<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db = getCRMDB(); $user = currentUser(); $uid = (int)$user['id'];
renderLayout('Lead Generator', 'lead_generator');
?>
<style>
.lg-grid{display:grid;grid-template-columns:280px 1fr 260px;gap:16px;margin-bottom:18px}
.lg-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px}
.lg-ring-card{background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);border:none;color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:170px}
.lg-ring-wrap{position:relative;width:100px;height:100px;margin:8px auto}
.lg-ring-svg{transform:rotate(-90deg)}
.lg-ring-bg{fill:none;stroke:rgba(255,255,255,.2);stroke-width:8}
.lg-ring-fill{fill:none;stroke:#fff;stroke-width:8;stroke-linecap:round;transition:stroke-dashoffset .6s ease}
.lg-ring-text{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
.lg-ring-pct{font-size:20px;font-weight:800;color:#fff}
.lg-ring-sub{font-size:11px;color:rgba(255,255,255,.7)}
.lg-ring-title{font-size:11px;color:rgba(255,255,255,.75);text-transform:uppercase;letter-spacing:.07em;margin-bottom:4px}
.lg-chart-title{font-size:12.5px;font-weight:600;color:var(--text2);margin-bottom:10px}
.lg-act-item{display:flex;align-items:flex-start;gap:8px;padding:6px 0;border-bottom:1px solid var(--border)}
.lg-act-item:last-child{border-bottom:none}
.lg-act-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;margin-top:5px}
.lg-act-text{font-size:12px;color:var(--text2)}
.lg-act-time{font-size:10px;color:var(--text3);margin-top:1px}
.lg-form-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px;margin-bottom:18px}
.lg-form-title{font-size:14px;font-weight:700;font-family:var(--font-display);margin-bottom:14px}
.lg-row{display:grid;grid-template-columns:1fr 1fr 90px auto;gap:10px;align-items:end}
.lg-input{padding:9px 12px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13px;font-family:var(--font);width:100%;transition:border-color .15s}
.lg-input:focus{outline:none;border-color:var(--orange)}
.lg-input::placeholder{color:var(--text3)}
.lg-btn{padding:9px 22px;background:var(--orange);color:#fff;border:none;border-radius:var(--radius-sm);font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap}
.lg-btn:hover{opacity:.88} .lg-btn:disabled{opacity:.5;cursor:not-allowed}
.lg-results{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px}
.lg-results-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px}
.lg-results-title{font-size:14px;font-weight:700;font-family:var(--font-display)}
.lg-tbl{width:100%;border-collapse:collapse}
.lg-tbl th{font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;padding:7px 8px;border-bottom:2px solid var(--border);text-align:left;white-space:nowrap}
.lg-tbl td{padding:9px 8px;border-bottom:1px solid var(--border);font-size:12.5px;color:var(--text2);vertical-align:middle}
.lg-tbl tr:last-child td{border-bottom:none}
.lg-tbl tr:hover td{background:var(--bg3)}
.lg-name{font-weight:600;color:var(--text)}
.lg-phone{font-family:monospace;font-size:12px}
.lg-addr{max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:12px;color:var(--text3)}
.lg-acts{display:flex;gap:5px;align-items:center}
.lg-call{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;background:#10b981;color:#fff;border-radius:50%;text-decoration:none;font-size:13px;border:none;cursor:pointer;flex-shrink:0}
.lg-call:hover{opacity:.8}
.lg-imp{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;background:var(--orange);color:#fff;border-radius:50%;border:none;cursor:pointer;font-size:11px;flex-shrink:0}
.lg-imp:hover{opacity:.8} .lg-imp:disabled{background:var(--bg4);cursor:default}
.lg-done{font-size:10.5px;background:rgba(16,185,129,.12);color:#10b981;border:1px solid rgba(16,185,129,.25);border-radius:99px;padding:2px 8px;white-space:nowrap}
.lg-empty{text-align:center;padding:36px;color:var(--text3)}
.lg-spinner{display:inline-block;width:24px;height:24px;border:3px solid var(--border);border-top-color:var(--orange);border-radius:50%;animation:lgspin .7s linear infinite;margin-bottom:8px}
@keyframes lgspin{to{transform:rotate(360deg)}}
.lg-setup-box{background:rgba(16,185,129,.06);border:1px solid rgba(16,185,129,.3);border-radius:var(--radius);padding:14px 16px;margin-bottom:14px;font-size:12.5px;line-height:1.7;color:var(--text2)}
.lg-setup-box strong{color:var(--text)}
.lg-setup-box a{color:var(--orange);font-weight:600}
.lg-warn-box{background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.25);border-radius:var(--radius-sm);padding:12px 14px;font-size:12.5px;color:var(--text2);margin-bottom:12px;line-height:1.7}
.lg-settings{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;margin-bottom:18px;display:none}
.lg-settings.open{display:block}
.prov-tab{padding:8px 16px;border:2px solid var(--border);border-radius:var(--radius-sm);font-size:12.5px;font-weight:600;cursor:pointer;background:none;color:var(--text2);transition:all .12s}
.prov-tab.active{background:var(--orange);border-color:var(--orange);color:#fff}
@media(max-width:1000px){.lg-grid{grid-template-columns:1fr 1fr}}
@media(max-width:700px){.lg-grid{grid-template-columns:1fr}.lg-row{grid-template-columns:1fr 1fr}}
@media(max-width:480px){.lg-row{grid-template-columns:1fr}}
</style>

<!-- STATS ROW -->
<div class="lg-grid">
  <div class="lg-card lg-ring-card">
    <div class="lg-ring-title">Usage This Month</div>
    <div class="lg-ring-wrap">
      <svg class="lg-ring-svg" viewBox="0 0 100 100" width="100" height="100">
        <circle class="lg-ring-bg" cx="50" cy="50" r="42"/>
        <circle class="lg-ring-fill" id="lg-ring-fill" cx="50" cy="50" r="42" stroke-dasharray="264" stroke-dashoffset="264"/>
      </svg>
      <div class="lg-ring-text">
        <div class="lg-ring-pct" id="lg-ring-pct">0%</div>
        <div class="lg-ring-sub" id="lg-ring-sub">0 / 2500</div>
      </div>
    </div>
  </div>
  <div class="lg-card">
    <div class="lg-chart-title">Monthly Usage Trend</div>
    <div style="height:130px"><canvas id="lg-trend"></canvas></div>
  </div>
  <div class="lg-card">
    <div class="lg-chart-title">Recent Searches</div>
    <div id="lg-recent"><div style="color:var(--text3);font-size:12px;padding:6px 0">No searches yet</div></div>
  </div>
</div>

<!-- NO API BANNER -->
<div id="lg-no-api" style="display:none;background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.3);border-radius:var(--radius);padding:12px 16px;margin-bottom:16px;display:none;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
  <div style="display:flex;align-items:center;gap:10px">
    <span style="font-size:18px">🔑</span>
    <div><div style="font-size:13px;font-weight:700;color:var(--orange)">API key not configured</div>
    <div style="font-size:12px;color:var(--text2)">Set up TomTom free API key to start generating leads.</div></div>
  </div>
  <button onclick="openSettings()" class="btn btn-sm" style="background:var(--orange);color:#fff;border:none;flex-shrink:0">⚙ Setup (Free)</button>
</div>

<!-- SETTINGS PANEL -->
<?php if(isAdmin()): ?>
<div id="lg-settings" class="lg-settings">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
    <div style="font-size:13px;font-weight:700">⚙ Lead Generator Settings</div>
    <button onclick="closeSettings()" class="btn btn-ghost btn-sm">✕</button>
  </div>

  <!-- Provider tabs -->
  <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <button class="prov-tab active" id="tab-tomtom"     onclick="switchProv('tomtom')">🗺 TomTom <span style="font-size:10px;opacity:.8">FREE · Recommended</span></button>
    <button class="prov-tab"        id="tab-foursquare" onclick="switchProv('foursquare')">📍 Foursquare <span style="font-size:10px;opacity:.8">FREE</span></button>
    <button class="prov-tab"        id="tab-google"     onclick="switchProv('google')">🌏 Google <span style="font-size:10px;opacity:.8">Paid</span></button>
  </div>

  <!-- TomTom setup -->
  <div id="setup-tomtom">
    <div class="lg-setup-box">
      <strong>✅ TomTom — 2,500 free searches/day · No credit card · No domain restrictions</strong><br><br>
      <strong>Get your free key in 2 minutes:</strong><br>
      1. Go to <a href="https://developer.tomtom.com/" target="_blank">developer.tomtom.com</a> → click <strong>"Get Free API Key"</strong><br>
      2. Sign up with email (free, no card)<br>
      3. After signup, your API key is shown immediately on the dashboard<br>
      4. Copy it → paste below → Save → Test
    </div>
    <div style="display:grid;grid-template-columns:1fr 100px;gap:10px;align-items:end">
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">TomTom API Key</label>
        <input type="password" id="tt-key" class="lg-input" placeholder="Paste your TomTom API key here..." autocomplete="off">
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Monthly Quota</label>
        <input type="number" id="lg-quota" class="lg-input" value="2500" min="100">
      </div>
    </div>
  </div>

  <!-- Foursquare setup -->
  <div id="setup-foursquare" style="display:none">
    <div class="lg-setup-box">
      <strong>📍 Foursquare — Free BUT requires allowlist fix first</strong><br><br>
      <strong>Why "Invalid token" error happens:</strong><br>
      Foursquare blocks requests from unlisted servers by default.<br><br>
      <strong>Fix (30 seconds):</strong><br>
      1. Go to <a href="https://foursquare.com/developer" target="_blank">foursquare.com/developer</a> → your project → <strong>Settings</strong><br>
      2. Find <strong>"Allowed Hosts"</strong> section<br>
      3. Add <strong>*</strong> (just an asterisk) → Save<br>
      4. Then copy your <strong>Service API Key</strong> (NOT Client ID or Client Secret) and paste below
    </div>
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Foursquare Service API Key</label>
      <input type="password" id="fsq-key" class="lg-input" placeholder="fsq3..." autocomplete="off">
    </div>
  </div>

  <!-- Google setup -->
  <div id="setup-google" style="display:none">
    <div class="lg-warn-box">
      ⚠ <strong>Google requires billing setup</strong> — the ₹2 charges and redirect loop you experienced are a known Google issue for India/Sri Lanka accounts. <strong>We recommend TomTom instead.</strong>
    </div>
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Google Places API Key</label>
      <input type="password" id="goog-key" class="lg-input" placeholder="AIzaSy..." autocomplete="off">
    </div>
  </div>

  <div style="display:flex;gap:8px;margin-top:14px;align-items:center;flex-wrap:wrap">
    <button onclick="saveSettings()" class="btn btn-primary">💾 Save Settings</button>
    <button onclick="testKey()" class="btn btn-ghost btn-sm" id="lg-test-btn">🔌 Test Connection</button>
    <div id="lg-test-result" style="font-size:12.5px;display:none;padding:6px 10px;border-radius:var(--radius-sm);flex:1;white-space:pre-line"></div>
  </div>
</div>
<?php endif; ?>

<!-- SEARCH FORM -->
<div class="lg-form-card">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px">
    <div class="lg-form-title">🔍 Generate Leads</div>
    <?php if(isAdmin()): ?>
    <button onclick="openSettings()" class="btn btn-ghost btn-sm" style="font-size:12px">⚙ Settings</button>
    <?php endif; ?>
  </div>
  <div class="lg-row">
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Location / City</label>
      <input type="text" id="lg-loc" class="lg-input" placeholder="e.g. Colombo, Batticaloa, Chennai">
    </div>
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Industry / Business Type</label>
      <input type="text" id="lg-ind" class="lg-input" placeholder="e.g. Restaurant, Web development, Hotel">
    </div>
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Count</label>
      <input type="number" id="lg-cnt" class="lg-input" value="5" min="1" max="20">
    </div>
    <div>
      <label style="display:block;margin-bottom:4px;opacity:0">_</label>
      <button class="lg-btn" id="lg-gen-btn" onclick="doSearch()">Generate</button>
    </div>
  </div>
  <div id="lg-quota-info" style="margin-top:8px;font-size:12px;color:var(--text3)"></div>
</div>

<!-- LOADING -->
<div id="lg-loading" style="display:none;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:36px;text-align:center">
  <div class="lg-spinner"></div>
  <div style="font-size:13px;color:var(--text3)" id="lg-load-txt">Searching...</div>
</div>

<!-- RESULTS -->
<div id="lg-results-wrap" style="display:none" class="lg-results">
  <div class="lg-results-head">
    <div class="lg-results-title">Generated Leads <span id="lg-res-count" style="font-size:12px;color:var(--text3);font-weight:400"></span></div>
    <div style="display:flex;gap:8px">
      <button onclick="importAll()" class="btn btn-sm" id="lg-imp-all" style="background:var(--orange);color:#fff;border:none">⬇ Import All to CRM</button>
      <button onclick="doExport()"  class="btn btn-ghost btn-sm">⬇ Download CSV</button>
    </div>
  </div>
  <div style="overflow-x:auto">
    <table class="lg-tbl">
      <thead><tr><th>#</th><th>Business Name</th><th>Phone</th><th>Address</th><th>Actions</th></tr></thead>
      <tbody id="lg-tbody"></tbody>
    </table>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
var lgIds=[], lgChart=null, lgProv='tomtom', lgApiOk=false;

document.addEventListener('DOMContentLoaded',function(){
    loadStats();
    ['lg-loc','lg-ind','lg-cnt'].forEach(function(id){
        var el=document.getElementById(id);
        if(el)el.addEventListener('keydown',function(e){if(e.key==='Enter')doSearch();});
    });
});

function loadStats(){
    fetch('lead_generator_api.php?action=get_stats')
    .then(function(r){return r.json();})
    .then(function(d){
        if(!d.ok)return;
        lgApiOk=d.api_set; lgProv=d.provider||'tomtom';
        var banner=document.getElementById('lg-no-api');
        if(banner)banner.style.display=d.api_set?'none':'flex';
        var pct=d.quota>0?Math.round(d.used/d.quota*100):0;
        document.getElementById('lg-ring-pct').textContent=pct+'%';
        document.getElementById('lg-ring-sub').textContent=d.used+' / '+d.quota;
        var circ=2*Math.PI*42;
        document.getElementById('lg-ring-fill').setAttribute('stroke-dasharray',circ.toFixed(1));
        document.getElementById('lg-ring-fill').setAttribute('stroke-dashoffset',(circ-(pct/100*circ)).toFixed(1));
        var qi=document.getElementById('lg-quota-info');
        if(qi)qi.textContent='Provider: '+(lgProv==='tomtom'?'TomTom':lgProv==='foursquare'?'Foursquare':'Google')+' · Used: '+d.used+'/'+d.quota+' this month · '+Math.max(0,d.quota-d.used)+' remaining';
        renderTrend(d.trend||[]);
        renderRecent(d.recent||[]);
    }).catch(function(){});
}

function renderTrend(data){
    var ctx=document.getElementById('lg-trend');
    if(!ctx)return;
    if(lgChart){lgChart.destroy();lgChart=null;}
    var labels=data.map(function(r){return r.mo;});
    var vals=data.map(function(r){return parseInt(r.cnt)||0;});
    if(!labels.length){labels=['Jan','Feb','Mar','Apr','May','Jun'];vals=[0,0,0,0,0,0];}
    lgChart=new Chart(ctx,{type:'line',data:{labels:labels,datasets:[{data:vals,borderColor:'#4f46e5',backgroundColor:'rgba(79,70,229,.08)',fill:true,tension:.4,pointRadius:4,pointBackgroundColor:'#4f46e5',borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(148,163,184,.12)'},ticks:{color:'#94a3b8',font:{size:10}}},y:{grid:{color:'rgba(148,163,184,.12)'},ticks:{color:'#94a3b8',precision:0},beginAtZero:true}}}});
}

function renderRecent(data){
    var el=document.getElementById('lg-recent');if(!el)return;
    if(!data.length){el.innerHTML='<div style="color:var(--text3);font-size:12px;padding:6px 0">No searches yet</div>';return;}
    var cols=['#4f46e5','#10b981','#8b5cf6','#f97316','#f59e0b'];
    el.innerHTML=data.map(function(r,i){
        return '<div class="lg-act-item"><div class="lg-act-dot" style="background:'+cols[i%cols.length]+'"></div>'
            +'<div><div class="lg-act-text">'+r.result_count+' leads · '+esc(r.industry)+' in '+esc(r.location)+'</div>'
            +'<div class="lg-act-time">'+fmtAgo(r.created_at)+'</div></div></div>';
    }).join('');
}

function doSearch(){
    var loc=document.getElementById('lg-loc').value.trim();
    var ind=document.getElementById('lg-ind').value.trim();
    var cnt=parseInt(document.getElementById('lg-cnt').value)||5;
    if(!loc){toast('Enter a location','error');document.getElementById('lg-loc').focus();return;}
    if(!ind){toast('Enter an industry','error');document.getElementById('lg-ind').focus();return;}
    if(!lgApiOk){toast('Configure API key first — click ⚙ Settings','error');openSettings();return;}

    var btn=document.getElementById('lg-gen-btn');
    btn.disabled=true;btn.textContent='Generating…';
    document.getElementById('lg-loading').style.display='block';
    document.getElementById('lg-results-wrap').style.display='none';
    document.getElementById('lg-load-txt').textContent='Searching for '+ind+' in '+loc+'…';

    var fd=new FormData();
    fd.append('action','search');fd.append('location',loc);fd.append('industry',ind);fd.append('count',cnt);
    fetch('lead_generator_api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        btn.disabled=false;btn.textContent='Generate';
        document.getElementById('lg-loading').style.display='none';
        if(!d.ok){toast(d.error||'Generation failed','error');return;}
        if(!d.leads||!d.leads.length){toast(d.message||'No results found. Try different keywords.','info');return;}
        renderResults(d.leads,ind,loc);
        if(d.used!==undefined){
            var qi=document.getElementById('lg-quota-info');
            if(qi)qi.textContent='Provider: '+(lgProv==='tomtom'?'TomTom':'Foursquare')+' · Used: '+d.used+'/'+d.quota+' · '+(d.quota-d.used)+' remaining';
        }
        loadStats();
    })
    .catch(function(e){
        btn.disabled=false;btn.textContent='Generate';
        document.getElementById('lg-loading').style.display='none';
        toast('Network error — check console','error');console.error(e);
    });
}

function renderResults(leads,ind,loc){
    lgIds=leads.map(function(l){return l.id;});
    document.getElementById('lg-res-count').textContent='('+leads.length+' results for '+ind+', '+loc+')';
    var tbody=document.getElementById('lg-tbody');
    tbody.innerHTML=leads.map(function(l,i){
        var phone_link=l.phone?'<a href="tel:'+esc(l.phone)+'" class="lg-call" title="Call">📞</a>':'<span style="width:30px;display:inline-block"></span>';
        var imp=l.imported?'<span class="lg-done">✓ In CRM</span>':'<button class="lg-imp" onclick="impOne('+l.id+',this)" title="Import to CRM">⬇</button>';
        var web=l.website?'<div style="font-size:10.5px;margin-top:2px"><a href="'+esc(l.website)+'" target="_blank" style="color:var(--orange)">🌐 Website</a></div>':'';
        return '<tr id="lr-'+l.id+'"><td style="color:var(--text3);font-size:11px">'+(i+1)+'</td>'
            +'<td><div class="lg-name">'+esc(l.name)+'</div>'+web+'</td>'
            +'<td class="lg-phone">'+(l.phone?esc(l.phone):'<span style="color:var(--text3)">—</span>')+'</td>'
            +'<td><div class="lg-addr" title="'+esc(l.address)+'">'+esc(l.address||'—')+'</div></td>'
            +'<td><div class="lg-acts">'+phone_link+'&nbsp;'+imp+'</div></td></tr>';
    }).join('');
    document.getElementById('lg-results-wrap').style.display='block';
    document.getElementById('lg-results-wrap').scrollIntoView({behavior:'smooth',block:'start'});
}

function impOne(id,btn){
    btn.disabled=true;btn.textContent='…';
    var fd=new FormData();fd.append('action','import_lead');fd.append('result_id',id);
    fetch('lead_generator_api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        if(d.ok){btn.replaceWith(Object.assign(document.createElement('span'),{className:'lg-done',textContent:'✓ In CRM'}));toast(d.message,'success');}
        else{btn.disabled=false;btn.textContent='⬇';toast(d.error||'Failed','error');}
    });
}

function importAll(){
    var pending=lgIds.filter(function(id){var b=document.querySelector('#lr-'+id+' .lg-imp');return b&&!b.disabled;});
    if(!pending.length){toast('All leads already imported','info');return;}
    if(!confirm('Import '+pending.length+' leads to CRM?'))return;
    var btn=document.getElementById('lg-imp-all');
    btn.disabled=true;btn.textContent='Importing…';
    var fd=new FormData();fd.append('action','import_all');fd.append('ids',pending.join(','));
    fetch('lead_generator_api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        btn.disabled=false;btn.textContent='⬇ Import All to CRM';
        if(d.ok){toast(d.imported+' leads added to CRM!','success');
            pending.forEach(function(id){var b=document.querySelector('#lr-'+id+' .lg-imp');if(b)b.replaceWith(Object.assign(document.createElement('span'),{className:'lg-done',textContent:'✓ In CRM'}));});}
        else toast(d.error||'Failed','error');
    });
}

function doExport(){
    if(!lgIds.length){toast('No data','error');return;}
    var fd=new FormData();fd.append('action','export_excel');fd.append('ids',lgIds.join(','));
    fetch('lead_generator_api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        if(!d.ok||!d.csv){toast('Export failed','error');return;}
        var a=document.createElement('a');a.href=URL.createObjectURL(new Blob([d.csv],{type:'text/csv'}));
        a.download='leads_'+new Date().toISOString().slice(0,10)+'.csv';a.click();
    });
}

function openSettings(){var p=document.getElementById('lg-settings');if(p)p.classList.add('open');}
function closeSettings(){var p=document.getElementById('lg-settings');if(p)p.classList.remove('open');}

function switchProv(p){
    lgProv=p;
    ['tomtom','foursquare','google'].forEach(function(k){
        var tab=document.getElementById('tab-'+k);
        var setup=document.getElementById('setup-'+k);
        if(tab)tab.classList.toggle('active',k===p);
        if(setup)setup.style.display=k===p?'block':'none';
    });
}

function testKey(){
    var btn=document.getElementById('lg-test-btn');
    var res=document.getElementById('lg-test-result');
    btn.disabled=true;btn.textContent='Testing…';
    if(res){res.style.display='none';}
    var fd=new FormData();fd.append('action','test_key');
    fetch('lead_generator_api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        btn.disabled=false;btn.textContent='🔌 Test Connection';
        if(res){
            res.style.display='block';
            res.style.background=d.ok?'rgba(16,185,129,.1)':'rgba(239,68,68,.08)';
            res.style.border=d.ok?'1px solid rgba(16,185,129,.3)':'1px solid rgba(239,68,68,.25)';
            res.style.color=d.ok?'var(--green)':'var(--red)';
            res.textContent=d.ok?d.message:d.error;
        }
    })
    .catch(function(){btn.disabled=false;btn.textContent='🔌 Test Connection';toast('Network error','error');});
}

function saveSettings(){
    var prov=lgProv;
    var tk=document.getElementById('tt-key')?.value.trim()||'';
    var fk=document.getElementById('fsq-key')?.value.trim()||'';
    var gk=document.getElementById('goog-key')?.value.trim()||'';
    var quota=document.getElementById('lg-quota')?.value||2500;
    if(prov==='tomtom'&&!tk){toast('Paste your TomTom API key','error');return;}
    if(prov==='foursquare'&&!fk){toast('Paste your Foursquare Service API Key','error');return;}
    if(prov==='google'&&!gk){toast('Paste your Google API key','error');return;}
    var fd=new FormData();
    fd.append('action','save_settings');fd.append('provider',prov);
    fd.append('tomtom_key',tk);fd.append('foursquare_key',fk);fd.append('google_key',gk);fd.append('quota',quota);
    fetch('lead_generator_api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        if(d.ok){toast('Settings saved! Click "Test Connection" to verify.','success');lgApiOk=true;
            document.getElementById('lg-no-api').style.display='none';
            ['tt-key','fsq-key','goog-key'].forEach(function(id){var el=document.getElementById(id);if(el)el.value='';});
            loadStats();
        }else toast(d.error||'Save failed','error');
    });
}

function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function fmtAgo(dt){if(!dt)return'';var diff=Math.floor((Date.now()-new Date(dt).getTime())/1000);if(diff<60)return'just now';if(diff<3600)return Math.floor(diff/60)+'m ago';if(diff<86400)return Math.floor(diff/3600)+'h ago';return Math.floor(diff/86400)+'d ago';}
</script>
<?php renderLayoutEnd(); ?>