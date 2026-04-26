<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
if (!isManager()) { header('Location: lead_generator.php'); exit; }
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];
renderLayout('Stored Leads', 'lead_stored');
?>
<style>
.ls-toolbar{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 20px;margin-bottom:16px}
.ls-filters{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
.ls-filter-group{display:flex;flex-direction:column;gap:4px}
.ls-filter-group label{font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em}
.ls-input,.ls-select{padding:7px 10px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13px}
.ls-input:focus,.ls-select:focus{outline:none;border-color:var(--orange)}
.ls-input{min-width:180px}.ls-select{cursor:pointer}
.ls-score-row{display:flex;gap:8px;align-items:center}
.ls-score-row input{width:65px;text-align:center}
.ls-tbl-wrap{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.ls-tbl-head{padding:13px 16px;border-bottom:1px solid var(--border);background:var(--bg3);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.ls-tbl{width:100%;border-collapse:collapse}
.ls-tbl th{font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;padding:8px 12px;border-bottom:2px solid var(--border);text-align:left;background:var(--bg3);white-space:nowrap;cursor:pointer;user-select:none}
.ls-tbl th:hover{color:var(--text)}
.ls-tbl th.sort-asc::after{content:' ↑';color:var(--orange)}
.ls-tbl th.sort-desc::after{content:' ↓';color:var(--orange)}
.ls-tbl td{padding:10px 12px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text2);vertical-align:middle}
.ls-tbl tr:last-child td{border-bottom:none}
.ls-tbl tr:hover td{background:var(--bg3)}
.ls-name{font-weight:700;color:var(--text);font-size:13.5px}
.ls-owner{font-size:11px;color:var(--text3);margin-top:1px}
.ls-phone{font-family:monospace;font-size:12.5px}
.ls-email a{font-size:11.5px;color:var(--orange);word-break:break-all}
.ls-web-yes{background:rgba(16,185,129,.1);color:#10b981;border:1px solid rgba(16,185,129,.25);padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap}
.ls-web-no{background:rgba(249,115,22,.08);color:var(--orange);border:1px solid rgba(249,115,22,.25);padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700}
.ls-imp-done{background:rgba(16,185,129,.12);color:#10b981;border:1px solid rgba(16,185,129,.25);padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700}
.ls-imp-btn{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:var(--orange);color:#fff;border-radius:50%;border:none;cursor:pointer;font-size:11px;transition:opacity .15s}
.ls-imp-btn:hover{opacity:.8}
.ls-del-btn{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:var(--bg4);color:var(--text3);border-radius:50%;border:none;cursor:pointer;font-size:11px;transition:all .15s}
.ls-del-btn:hover{background:#ef4444;color:#fff}
.ls-stats-bar{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:16px}
.ls-stat{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:12px 14px;text-align:center}
.ls-stat-val{font-size:20px;font-weight:800;font-family:var(--font-display);color:var(--orange)}
.ls-stat-lbl{font-size:10.5px;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-top:2px}
.ls-empty{text-align:center;padding:48px;color:var(--text3)}
.ls-loading{text-align:center;padding:36px;color:var(--text3)}
.ls-spinner{display:inline-block;width:24px;height:24px;border:3px solid var(--border);border-top-color:var(--orange);border-radius:50%;animation:lsspin .7s linear infinite;vertical-align:middle;margin-right:8px}
@keyframes lsspin{to{transform:rotate(360deg)}}
.ls-pagination{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--border)}
.ls-per-page{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text3)}
@media(max-width:900px){.ls-stats-bar{grid-template-columns:repeat(3,1fr)}.ls-filters{gap:8px}}
@media(max-width:600px){.ls-stats-bar{grid-template-columns:repeat(2,1fr)}}
</style>

<!-- STATS BAR (loaded dynamically) -->
<div class="ls-stats-bar" id="ls-stats-bar">
  <div class="ls-stat"><div class="ls-stat-val" id="stat-total">—</div><div class="ls-stat-lbl">Total Stored</div></div>
  <div class="ls-stat"><div class="ls-stat-val" id="stat-no-web" style="color:#10b981">—</div><div class="ls-stat-lbl">No Website</div></div>
  <div class="ls-stat"><div class="ls-stat-val" id="stat-imported" style="color:#6366f1">—</div><div class="ls-stat-lbl">Imported</div></div>
  <div class="ls-stat"><div class="ls-stat-val" id="stat-hot" style="color:#ef4444">—</div><div class="ls-stat-lbl">Hot (Score 70+)</div></div>
  <div class="ls-stat"><div class="ls-stat-val" id="stat-locations">—</div><div class="ls-stat-lbl">Locations</div></div>
</div>

<!-- FILTERS TOOLBAR -->
<div class="ls-toolbar">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
    <div style="font-size:14px;font-weight:700;font-family:var(--font-display)">📚 All Stored Leads</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button onclick="bulkImport()" class="btn btn-sm" style="background:var(--orange);color:#fff;border:none" id="bulk-imp-btn" disabled>⬇ Import Selected</button>
      <button onclick="bulkDelete()" class="btn btn-danger btn-sm" id="bulk-del-btn" disabled>🗑 Delete Selected</button>
      <button onclick="exportAll()" class="btn btn-ghost btn-sm">⬇ Export CSV</button>
      <a href="lead_generator.php" class="btn btn-ghost btn-sm">🔍 Generator</a>
    </div>
  </div>
  <div class="ls-filters">
    <div class="ls-filter-group">
      <label>Search</label>
      <input type="text" id="f-search" class="ls-input" placeholder="Name, phone, email, address..." oninput="debounceLoad()">
    </div>
    <div class="ls-filter-group">
      <label>Location</label>
      <select id="f-location" class="ls-select" onchange="loadLeads(1)"><option value="">All Locations</option></select>
    </div>
    <div class="ls-filter-group">
      <label>Industry</label>
      <select id="f-industry" class="ls-select" onchange="loadLeads(1)"><option value="">All Industries</option></select>
    </div>
    <div class="ls-filter-group">
      <label>Website</label>
      <select id="f-website" class="ls-select" onchange="loadLeads(1)">
        <option value="">All</option>
        <option value="0">🔥 No Website (Hot leads)</option>
        <option value="1">✅ Has Website</option>
      </select>
    </div>
    <div class="ls-filter-group">
      <label>Status</label>
      <select id="f-imported" class="ls-select" onchange="loadLeads(1)">
        <option value="">All</option>
        <option value="0">Not Imported</option>
        <option value="1">Imported to CRM</option>
      </select>
    </div>
    <div class="ls-filter-group">
      <label>Score Range</label>
      <div class="ls-score-row">
        <input type="number" id="f-score-min" class="ls-input" placeholder="Min" min="0" max="100" value="0" onchange="loadLeads(1)">
        <span style="color:var(--text3)">–</span>
        <input type="number" id="f-score-max" class="ls-input" placeholder="Max" min="0" max="100" value="100" onchange="loadLeads(1)">
      </div>
    </div>
    <div class="ls-filter-group">
      <label>Sort By</label>
      <select id="f-sort" class="ls-select" onchange="loadLeads(1)">
        <option value="id">Date Added</option>
        <option value="opportunity_score">Opportunity Score</option>
        <option value="rating">Rating</option>
        <option value="name">Name A–Z</option>
        <option value="created_at">Date Found</option>
      </select>
    </div>
    <div class="ls-filter-group">
      <label>Order</label>
      <select id="f-dir" class="ls-select" onchange="loadLeads(1)">
        <option value="desc">Newest / Highest First</option>
        <option value="asc">Oldest / Lowest First</option>
      </select>
    </div>
    <div class="ls-filter-group" style="justify-content:flex-end">
      <label style="opacity:0">_</label>
      <button onclick="resetFilters()" class="btn btn-ghost btn-sm">🔄 Reset</button>
    </div>
  </div>
</div>

<!-- TABLE -->
<div class="ls-tbl-wrap">
  <div class="ls-tbl-head">
    <div id="ls-count-label" style="font-size:13px;color:var(--text3)">Loading...</div>
    <div style="display:flex;align-items:center;gap:10px">
      <div class="ls-per-page">
        Show
        <select id="f-perpage" class="ls-select" style="padding:4px 8px;font-size:12px" onchange="loadLeads(1)">
          <option value="25">25</option>
          <option value="50" selected>50</option>
          <option value="100">100</option>
          <option value="200">200</option>
        </select>
        per page
      </div>
    </div>
  </div>
  <div style="overflow-x:auto">
    <table class="ls-tbl">
      <thead>
        <tr>
          <th style="width:32px"><input type="checkbox" id="sel-all" onchange="toggleAll(this)"></th>
          <th onclick="changeSort('opportunity_score')" id="th-score">Score</th>
          <th onclick="changeSort('name')" id="th-name">#  Business Name / Owner</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Address</th>
          <th onclick="changeSort('id')" id="th-location">Location</th>
          <th>Industry</th>
          <th>Website</th>
          <th onclick="changeSort('rating')" id="th-rating">Rating</th>
          <th onclick="changeSort('created_at')" id="th-date">Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="ls-tbody">
        <tr><td colspan="13" class="ls-loading"><span class="ls-spinner"></span>Loading leads...</td></tr>
      </tbody>
    </table>
  </div>
  <div class="ls-pagination">
    <div style="font-size:12px;color:var(--text3)" id="ls-page-info"></div>
    <div style="display:flex;gap:4px" id="ls-page-btns"></div>
  </div>
</div>

<script>
var lsPage=1, lsTotal=0, lsIds=[], lsDebounce=null;
var lsSort='id', lsDir='desc';

document.addEventListener('DOMContentLoaded', function(){ loadLeads(1); loadStats(); });

function loadStats() {
    // Load aggregate stats
    fetch('lead_generator_api.php?action=get_all_stored&page=1&per_page=1')
    .then(function(r){return r.json();})
    .then(function(d){
        if(!d.ok) return;
        // Set total
        document.getElementById('stat-total').textContent = d.total || 0;
        document.getElementById('stat-locations').textContent = (d.locations||[]).length;
    });
    // No website count
    fetch('lead_generator_api.php?action=get_all_stored&page=1&per_page=1&website=0')
    .then(function(r){return r.json();})
    .then(function(d){ if(d.ok) document.getElementById('stat-no-web').textContent=d.total; });
    // Imported count
    fetch('lead_generator_api.php?action=get_all_stored&page=1&per_page=1&imported=1')
    .then(function(r){return r.json();})
    .then(function(d){ if(d.ok) document.getElementById('stat-imported').textContent=d.total; });
    // Hot leads count
    fetch('lead_generator_api.php?action=get_all_stored&page=1&per_page=1&score_min=70')
    .then(function(r){return r.json();})
    .then(function(d){ if(d.ok) document.getElementById('stat-hot').textContent=d.total; });
}

function loadLeads(page) {
    page = page||1; lsPage = page;
    lsSort = document.getElementById('f-sort')?.value || 'id';
    lsDir  = document.getElementById('f-dir')?.value  || 'desc';
    var perPage = parseInt(document.getElementById('f-perpage')?.value)||50;
    var search   = document.getElementById('f-search')?.value||'';
    var location = document.getElementById('f-location')?.value||'';
    var industry = document.getElementById('f-industry')?.value||'';
    var website  = document.getElementById('f-website')?.value||'';
    var imported = document.getElementById('f-imported')?.value||'';
    var scoreMin = document.getElementById('f-score-min')?.value||'0';
    var scoreMax = document.getElementById('f-score-max')?.value||'100';
    var url = 'lead_generator_api.php?action=get_all_stored&page='+page+'&per_page='+perPage
        +'&sort='+lsSort+'&dir='+lsDir;
    if(search)   url+='&search='+encodeURIComponent(search);
    if(location) url+='&location='+encodeURIComponent(location);
    if(industry) url+='&industry='+encodeURIComponent(industry);
    if(website)  url+='&website='+website;
    if(imported) url+='&imported='+imported;
    if(parseInt(scoreMin)>0)  url+='&score_min='+parseInt(scoreMin);
    if(parseInt(scoreMax)<100) url+='&score_max='+parseInt(scoreMax);
    var tbody = document.getElementById('ls-tbody');
    if(tbody) tbody.innerHTML='<tr><td colspan="13" class="ls-loading"><span class="ls-spinner"></span>Loading...</td></tr>';
    fetch(url)
    .then(function(r){return r.json();})
    .then(function(d){
        if(!d.ok){toast(d.error||'Failed','error');return;}
        lsTotal=d.total; lsIds=[];
        renderTable(d.leads||[]);
        renderPagination(d.total,page,perPage);
        populateDropdowns(d.locations||[],d.industries||[]);
        var label=document.getElementById('ls-count-label');
        if(label) label.textContent=d.total+' lead'+(d.total!==1?'s':'')+' found';
        document.getElementById('stat-total').textContent=d.total;
        // Update sort header indicators
        ['score','name','location','rating','date'].forEach(function(k){
            var th=document.getElementById('th-'+k); if(th){th.className='';}
        });
        var fieldMap={opportunity_score:'score',name:'name',id:'location',rating:'rating',created_at:'date'};
        var thId='th-'+fieldMap[lsSort];
        var th=document.getElementById(thId);
        if(th) th.className=lsDir==='asc'?'sort-asc':'sort-desc';
    })
    .catch(function(e){toast('Network error','error');console.error(e);});
}

function debounceLoad() {
    clearTimeout(lsDebounce);
    lsDebounce = setTimeout(function(){loadLeads(1);},300);
}

function renderTable(leads) {
    var tbody=document.getElementById('ls-tbody'); if(!tbody) return;
    if(!leads.length){
        tbody.innerHTML='<tr><td colspan="13" class="ls-empty"><div style="font-size:32px;margin-bottom:10px">📭</div><div>No leads found matching your filters</div><button onclick="resetFilters()" class="btn btn-ghost btn-sm" style="margin-top:10px">Clear Filters</button></td></tr>';
        return;
    }
    tbody.innerHTML = leads.map(function(l,i){
        lsIds.push(l.id);
        var score=parseInt(l.opportunity_score)||0;
        var sc=score>=70?'#10b981':score>=40?'#f59e0b':'#94a3b8';
        var scoreBg=score>=70?'rgba(16,185,129,.1)':score>=40?'rgba(245,158,11,.1)':'transparent';
        var scoreHot=score>=70?' title="Hot lead - no website + established"':'';
        var scoreBadge='<div style="text-align:center;min-width:36px"><div style="font-size:15px;font-weight:800;color:'+sc+'">'+score+'</div>'+(score>=70?'<div style="font-size:9px;font-weight:700;background:'+scoreBg+';color:'+sc+';border-radius:99px;padding:0 4px">HOT</div>':'')+'</div>';
        var hasWeb=parseInt(l.has_website)===1;
        var webBadge=hasWeb
            ?(l.website?'<a href="'+esc(l.website)+'" target="_blank" class="ls-web-yes" style="font-size:10px">✅ Yes ↗</a>':'<span class="ls-web-yes" style="font-size:10px">✅ Yes</span>')
            :'<span class="ls-web-no" style="font-size:10px">🔥 No</span>';
        var statusBadge=l.imported
            ?'<span class="ls-imp-done" style="font-size:10px">✓ Imported</span>'
            :'<span style="font-size:10px;color:var(--text3)">—</span>';
        var actions='<div style="display:flex;gap:4px;align-items:center">';
        if(!l.imported) actions+='<button onclick="impOne('+l.id+',this)" class="ls-imp-btn" title="Import to CRM Leads">⬇</button>';
        actions+='<button onclick="delOne('+l.id+',this)" class="ls-del-btn" title="Delete">🗑</button></div>';
        return '<tr id="lsr-'+l.id+'" style="'+(hasWeb?'':'background:rgba(249,115,22,.02)')+'">'
            +'<td><input type="checkbox" class="ls-chk" data-id="'+l.id+'" onchange="updateBulkBtns()"></td>'
            +'<td'+scoreHot+'>'+scoreBadge+'</td>'
            +'<td><div class="ls-name">'+esc(l.name)+'</div>'+(l.owner_name?'<div class="ls-owner">👤 '+esc(l.owner_name)+'</div>':'')+'</td>'
            +'<td class="ls-phone">'+(l.phone?'<a href="tel:'+esc(l.phone)+'" style="color:var(--text)">'+esc(l.phone)+'</a>':'<span style="color:var(--text3)">—</span>')+'</td>'
            +'<td class="ls-email">'+(l.email?'<a href="mailto:'+esc(l.email)+'">'+esc(l.email)+'</a>':'<span style="color:var(--text3)">—</span>')+'</td>'
            +'<td style="font-size:12px;color:var(--text3);max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="'+esc(l.address||'')+'">'+esc(l.address||'—')+'</td>'
            +'<td style="font-size:12px;color:var(--text2)">'+esc(l.location||'—')+'</td>'
            +'<td style="font-size:12px;color:var(--text3)">'+esc(l.industry||'—')+'</td>'
            +'<td>'+webBadge+'</td>'
            +'<td style="font-size:12px;color:#f59e0b;font-weight:700">'+(l.rating?'⭐ '+parseFloat(l.rating).toFixed(1)+(l.ratings_total?'<div style="font-size:10px;color:var(--text3)">'+l.ratings_total+' reviews</div>':''):'—')+'</td>'
            +'<td style="font-size:11px;color:var(--text3);white-space:nowrap">'+fmtDate(l.created_at)+'</td>'
            +'<td>'+statusBadge+'</td>'
            +'<td>'+actions+'</td>'
            +'</tr>';
    }).join('');
    updateSelectAllState();
}

function renderPagination(total,currentPage,perPage) {
    var totalPages=Math.ceil(total/perPage);
    var info=document.getElementById('ls-page-info'); var btns=document.getElementById('ls-page-btns');
    if(!info||!btns) return;
    var start=((currentPage-1)*perPage)+1; var end=Math.min(currentPage*perPage,total);
    info.textContent='Showing '+(total>0?start:0)+'–'+end+' of '+total+' leads';
    if(totalPages<=1){btns.innerHTML='';return;}
    var html='';
    if(currentPage>1) html+='<button onclick="loadLeads('+(currentPage-1)+')" class="btn btn-ghost btn-sm">← Prev</button>';
    var s=Math.max(1,currentPage-2),e=Math.min(totalPages,currentPage+2);
    if(s>1){html+='<button onclick="loadLeads(1)" class="btn btn-ghost btn-sm">1</button>';if(s>2)html+='<span style="padding:5px 6px;color:var(--text3)">...</span>';}
    for(var i=s;i<=e;i++){
        if(i===currentPage) html+='<button class="btn btn-sm" style="background:var(--orange);color:#fff;border:none">'+i+'</button>';
        else html+='<button onclick="loadLeads('+i+')" class="btn btn-ghost btn-sm">'+i+'</button>';
    }
    if(e<totalPages){if(e<totalPages-1)html+='<span style="padding:5px 6px;color:var(--text3)">...</span>';html+='<button onclick="loadLeads('+totalPages+')" class="btn btn-ghost btn-sm">'+totalPages+'</button>';}
    if(currentPage<totalPages) html+='<button onclick="loadLeads('+(currentPage+1)+')" class="btn btn-ghost btn-sm">Next →</button>';
    btns.innerHTML=html;
}

function populateDropdowns(locations,industries) {
    var locSel=document.getElementById('f-location'); var indSel=document.getElementById('f-industry');
    if(locSel&&locations.length){var cv=locSel.value;locSel.innerHTML='<option value="">All Locations</option>'+locations.map(function(l){return'<option value="'+esc(l)+'">'+esc(l)+'</option>';}).join('');locSel.value=cv;}
    if(indSel&&industries.length){var cv=indSel.value;indSel.innerHTML='<option value="">All Industries</option>'+industries.map(function(i){return'<option value="'+esc(i)+'">'+esc(i)+'</option>';}).join('');indSel.value=cv;}
}

function changeSort(field) {
    var currentSort=document.getElementById('f-sort').value;
    var currentDir=document.getElementById('f-dir').value;
    document.getElementById('f-sort').value=field;
    if(currentSort===field) document.getElementById('f-dir').value=currentDir==='asc'?'desc':'asc';
    else document.getElementById('f-dir').value='desc';
    loadLeads(1);
}

function resetFilters() {
    ['f-search','f-location','f-industry','f-website','f-imported'].forEach(function(id){var el=document.getElementById(id);if(el)el.value='';});
    document.getElementById('f-score-min').value='0';
    document.getElementById('f-score-max').value='100';
    document.getElementById('f-sort').value='id';
    document.getElementById('f-dir').value='desc';
    loadLeads(1);
}

// ── SELECTION & BULK ACTIONS ──
function toggleAll(cb) {
    document.querySelectorAll('.ls-chk').forEach(function(c){c.checked=cb.checked;});
    updateBulkBtns();
}
function updateSelectAllState() {
    var sa=document.getElementById('sel-all');
    var all=document.querySelectorAll('.ls-chk');
    var chk=document.querySelectorAll('.ls-chk:checked').length;
    if(!sa||!all.length) return;
    sa.checked=chk>0&&chk===all.length; sa.indeterminate=chk>0&&chk<all.length;
    updateBulkBtns();
}
function updateBulkBtns() {
    var sel=document.querySelectorAll('.ls-chk:checked').length;
    var ib=document.getElementById('bulk-imp-btn'); var db=document.getElementById('bulk-del-btn');
    if(ib){ib.disabled=sel===0;ib.textContent=sel>0?'⬇ Import Selected ('+sel+')':'⬇ Import Selected';}
    if(db){db.disabled=sel===0;db.textContent=sel>0?'🗑 Delete Selected ('+sel+')':'🗑 Delete Selected';}
}
document.addEventListener('change',function(e){if(e.target.classList.contains('ls-chk'))updateSelectAllState();});

function impOne(id,btn) {
    btn.disabled=true; btn.textContent='...';
    var fd=new FormData(); fd.append('action','import_lead'); fd.append('result_id',id);
    fetch('lead_generator_api.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        if(d.ok){var row=document.getElementById('lsr-'+id);if(row){var td=row.querySelector('td:nth-last-child(2)');if(td)td.innerHTML='<span class="ls-imp-done" style="font-size:10px">✓ Imported</span>';}btn.replaceWith(Object.assign(document.createElement('span'),{style:'font-size:10px',textContent:''}));toast(d.message,'success');loadStats();}
        else{btn.disabled=false;btn.textContent='⬇';toast(d.error||'Failed','error');}
    });
}

function delOne(id,btn) {
    if(!confirm('Delete this lead?')) return;
    btn.disabled=true;
    var fd=new FormData(); fd.append('action','bulk_delete'); fd.append('ids',id);
    fetch('lead_generator_api.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        if(d.ok){var row=document.getElementById('lsr-'+id);if(row)row.remove();toast('Lead deleted','success');loadStats();}
        else{btn.disabled=false;toast(d.error||'Failed','error');}
    });
}

function bulkImport() {
    var ids=Array.from(document.querySelectorAll('.ls-chk:checked')).map(function(c){return c.dataset.id;});
    if(!ids.length){toast('No leads selected','info');return;}
    if(!confirm('Import '+ids.length+' leads to CRM pipeline?'))return;
    var btn=document.getElementById('bulk-imp-btn'); btn.disabled=true; btn.textContent='Importing...';
    var fd=new FormData(); fd.append('action','import_all'); fd.append('ids',ids.join(','));
    fetch('lead_generator_api.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        btn.textContent='⬇ Import Selected';
        if(d.ok){toast(d.imported+' leads imported to CRM!','success');loadLeads(lsPage);loadStats();}
        else{toast(d.error||'Failed','error');updateBulkBtns();}
    });
}

function bulkDelete() {
    var ids=Array.from(document.querySelectorAll('.ls-chk:checked')).map(function(c){return c.dataset.id;});
    if(!ids.length){toast('No leads selected','info');return;}
    if(!confirm('Delete '+ids.length+' leads? Cannot be undone.'))return;
    var btn=document.getElementById('bulk-del-btn'); btn.disabled=true; btn.textContent='Deleting...';
    var fd=new FormData(); fd.append('action','bulk_delete'); fd.append('ids',ids.join(','));
    fetch('lead_generator_api.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        btn.textContent='🗑 Delete Selected';
        if(d.ok){toast(d.deleted+' leads deleted','success');loadLeads(lsPage);loadStats();}
        else{toast(d.error||'Failed','error');updateBulkBtns();}
    });
}

function exportAll() {
    var ids=Array.from(document.querySelectorAll('.ls-chk:checked')).map(function(c){return c.dataset.id;});
    if(!ids.length&&!confirm('No leads selected — export ALL visible leads?'))return;
    var idsToExport = ids.length ? ids : lsIds;
    if(!idsToExport.length){toast('Load leads first','info');return;}
    var fd=new FormData(); fd.append('action','export_csv'); fd.append('ids',idsToExport.join(','));
    fetch('lead_generator_api.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        if(!d.ok||!d.csv){toast('Export failed','error');return;}
        var a=document.createElement('a');
        a.href=URL.createObjectURL(new Blob([d.csv],{type:'text/csv'}));
        a.download='stored_leads_'+new Date().toISOString().slice(0,10)+'.csv'; a.click();
        toast('CSV downloaded','success');
    });
}

function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function fmtDate(dt){if(!dt)return'';var d=new Date(dt);return['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][d.getMonth()]+' '+d.getDate()+', '+d.getFullYear();}
</script>

<?php renderLayoutEnd(); ?>