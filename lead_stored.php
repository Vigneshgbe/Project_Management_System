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
/* ── STATS ── */
.ls-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:18px}
.ls-stat{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px 16px;text-align:center}
.ls-stat-val{font-size:22px;font-weight:800;font-family:var(--font-display)}
.ls-stat-lbl{font-size:10.5px;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-top:3px}
/* ── TOOLBAR ── */
.ls-toolbar{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 18px;margin-bottom:16px}
.ls-filters{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-top:12px}
.ls-fg{display:flex;flex-direction:column;gap:3px}
.ls-fg label{font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.04em}
.ls-inp,.ls-sel{padding:7px 10px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13px}
.ls-inp:focus,.ls-sel:focus{outline:none;border-color:var(--orange)}
.ls-sel{cursor:pointer}
.ls-score-wrap{display:flex;align-items:center;gap:6px}
.ls-score-wrap input{width:60px;text-align:center}
/* ── TABLE WRAP ── */
.ls-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.ls-card-head{padding:12px 16px;border-bottom:1px solid var(--border);background:var(--bg3);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.ls-tbl{width:100%;border-collapse:collapse}
.ls-tbl th{font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.04em;padding:9px 12px;border-bottom:2px solid var(--border);background:var(--bg3);text-align:left;white-space:nowrap;user-select:none}
.ls-tbl th.sortable{cursor:pointer}
.ls-tbl th.sortable:hover{color:var(--orange)}
.ls-tbl th.sort-asc::after{content:' ↑';color:var(--orange)}
.ls-tbl th.sort-desc::after{content:' ↓';color:var(--orange)}
.ls-tbl td{padding:10px 12px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text2);vertical-align:middle}
.ls-tbl tr:last-child td{border-bottom:none}
.ls-tbl tr:hover td{background:var(--bg3);cursor:pointer}
.ls-name{font-weight:700;color:var(--text);font-size:13.5px;line-height:1.3}
.ls-owner{font-size:11px;color:var(--text3);margin-top:2px}
/* badges */
.ls-web-yes{background:rgba(16,185,129,.1);color:#10b981;border:1px solid rgba(16,185,129,.25);padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap;display:inline-block}
.ls-web-no{background:rgba(249,115,22,.08);color:var(--orange);border:1px solid rgba(249,115,22,.25);padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700;white-space:nowrap;display:inline-block}
.ls-imp-done{background:rgba(16,185,129,.12);color:#10b981;border:1px solid rgba(16,185,129,.25);padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700}
/* score */
.ls-score-cell{text-align:center;min-width:44px}
.ls-score-num{font-size:16px;font-weight:900}
.ls-score-hot{font-size:9px;font-weight:800;padding:1px 5px;border-radius:99px;display:block;text-align:center;margin-top:1px}
/* action buttons */
.ls-btn{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;border:none;cursor:pointer;font-size:12px;transition:all .15s;flex-shrink:0}
.ls-btn-imp{background:var(--orange);color:#fff}
.ls-btn-imp:hover{opacity:.8}
.ls-btn-del{background:var(--bg4);color:var(--text3)}
.ls-btn-del:hover{background:#ef4444;color:#fff}
.ls-btn-view{background:rgba(99,102,241,.1);color:#6366f1;border:1px solid rgba(99,102,241,.2)}
.ls-btn-view:hover{background:#6366f1;color:#fff}
/* pagination */
.ls-pagination{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--border);flex-wrap:wrap;gap:8px}
.ls-pp{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text3)}
/* ── MODAL ── */
.ls-modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px}
.ls-modal{background:var(--bg2);border-radius:var(--radius-lg);width:100%;max-width:680px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.ls-modal-head{padding:18px 20px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;justify-content:space-between;gap:12px;background:var(--bg3);border-radius:var(--radius-lg) var(--radius-lg) 0 0;position:sticky;top:0;z-index:1}
.ls-modal-body{padding:20px}
.ls-modal-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.ls-detail-block{background:var(--bg3);border-radius:var(--radius-sm);padding:12px 14px}
.ls-detail-label{font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px}
.ls-detail-val{font-size:13.5px;color:var(--text);font-weight:500;word-break:break-word;line-height:1.5}
.ls-detail-val a{color:var(--orange);text-decoration:none}
.ls-detail-val a:hover{text-decoration:underline}
.ls-modal-full{grid-column:1/-1}
.ls-score-badge-lg{display:inline-flex;flex-direction:column;align-items:center;padding:10px 18px;border-radius:var(--radius-sm);margin-bottom:14px}
/* empty / loading */
.ls-empty{text-align:center;padding:48px 20px;color:var(--text3)}
.ls-spin{display:inline-block;width:20px;height:20px;border:2px solid var(--border);border-top-color:var(--orange);border-radius:50%;animation:lsspin .7s linear infinite;vertical-align:middle;margin-right:6px}
@keyframes lsspin{to{transform:rotate(360deg)}}
@media(max-width:900px){.ls-stats{grid-template-columns:repeat(3,1fr)}.ls-modal-grid{grid-template-columns:1fr}}
@media(max-width:600px){.ls-stats{grid-template-columns:repeat(2,1fr)}}
</style>

<!-- STATS -->
<div class="ls-stats">
  <div class="ls-stat"><div class="ls-stat-val" id="st-total" style="color:var(--orange)">—</div><div class="ls-stat-lbl">Total Leads</div></div>
  <div class="ls-stat"><div class="ls-stat-val" id="st-noweb" style="color:#10b981">—</div><div class="ls-stat-lbl">No Website</div></div>
  <div class="ls-stat"><div class="ls-stat-val" id="st-hot" style="color:#ef4444">—</div><div class="ls-stat-lbl">HOT (70+ Score)</div></div>
  <div class="ls-stat"><div class="ls-stat-val" id="st-imp" style="color:#6366f1">—</div><div class="ls-stat-lbl">Imported to CRM</div></div>
  <div class="ls-stat"><div class="ls-stat-val" id="st-loc" style="color:#f59e0b">—</div><div class="ls-stat-lbl">Locations</div></div>
</div>

<!-- TOOLBAR -->
<div class="ls-toolbar">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <div style="font-size:14px;font-weight:700;font-family:var(--font-display)">📚 All Stored Leads</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button onclick="bulkImport()" class="btn btn-sm" id="btn-imp" style="background:var(--orange);color:#fff;border:none" disabled>⬇ Import Selected</button>
      <button onclick="bulkDelete()" class="btn btn-danger btn-sm" id="btn-del" disabled>🗑 Delete Selected</button>
      <button onclick="exportVisible()" class="btn btn-ghost btn-sm">⬇ Export CSV</button>
      <a href="lead_generator.php" class="btn btn-ghost btn-sm">🔍 Generator</a>
    </div>
  </div>
  <div class="ls-filters">
    <div class="ls-fg">
      <label>Search</label>
      <input type="text" id="f-search" class="ls-inp" placeholder="Name, phone, email..." style="min-width:180px" oninput="debounce()">
    </div>
    <div class="ls-fg">
      <label>Location</label>
      <select id="f-loc" class="ls-sel" onchange="load(1)"><option value="">All Locations</option></select>
    </div>
    <div class="ls-fg">
      <label>Industry</label>
      <select id="f-ind" class="ls-sel" onchange="load(1)"><option value="">All Industries</option></select>
    </div>
    <div class="ls-fg">
      <label>Website</label>
      <select id="f-web" class="ls-sel" onchange="load(1)">
        <option value="">All</option>
        <option value="0">🔥 No Website</option>
        <option value="1">✅ Has Website</option>
      </select>
    </div>
    <div class="ls-fg">
      <label>Imported</label>
      <select id="f-imp" class="ls-sel" onchange="load(1)">
        <option value="">All</option>
        <option value="0">Not Yet</option>
        <option value="1">Imported</option>
      </select>
    </div>
    <div class="ls-fg">
      <label>Score Range</label>
      <div class="ls-score-wrap">
        <input type="number" id="f-smin" class="ls-inp" value="0" min="0" max="100" onchange="load(1)">
        <span style="color:var(--text3)">–</span>
        <input type="number" id="f-smax" class="ls-inp" value="100" min="0" max="100" onchange="load(1)">
      </div>
    </div>
    <div class="ls-fg">
      <label>Sort By</label>
      <select id="f-sort" class="ls-sel" onchange="load(1)">
        <option value="id">Date Added</option>
        <option value="opportunity_score">Score</option>
        <option value="rating">Rating</option>
        <option value="name">Name A–Z</option>
      </select>
    </div>
    <div class="ls-fg">
      <label>Order</label>
      <select id="f-dir" class="ls-sel" onchange="load(1)">
        <option value="desc">Highest / Newest First</option>
        <option value="asc">Lowest / Oldest First</option>
      </select>
    </div>
    <div class="ls-fg" style="justify-content:flex-end">
      <label style="opacity:0">_</label>
      <button onclick="resetFilters()" class="btn btn-ghost btn-sm">🔄 Reset</button>
    </div>
  </div>
</div>

<!-- TABLE -->
<div class="ls-card">
  <div class="ls-card-head">
    <span id="ls-count" style="font-size:13px;color:var(--text3)">Loading...</span>
    <div class="ls-pp">
      Show
      <select id="f-pp" class="ls-sel" style="padding:4px 8px;font-size:12px" onchange="load(1)">
        <option value="25">25</option><option value="50" selected>50</option>
        <option value="100">100</option><option value="200">200</option>
      </select>
      per page
    </div>
  </div>
  <div style="overflow-x:auto">
    <table class="ls-tbl">
      <thead>
        <tr>
          <th style="width:32px"><input type="checkbox" id="sel-all" onchange="selAll(this)"></th>
          <th class="sortable" onclick="changeSort('opportunity_score')" id="th-score">Score</th>
          <th class="sortable" onclick="changeSort('name')" id="th-name">Business Name</th>
          <th>Phone</th>
          <th>Email</th>
          <th class="sortable" onclick="changeSort('id')" id="th-loc">Location</th>
          <th>Industry</th>
          <th>Website</th>
          <th class="sortable" onclick="changeSort('rating')" id="th-rat">Rating</th>
          <th class="sortable" onclick="changeSort('created_at')" id="th-date">Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="ls-tbody">
        <tr><td colspan="11" class="ls-empty"><span class="ls-spin"></span>Loading leads...</td></tr>
      </tbody>
    </table>
  </div>
  <div class="ls-pagination">
    <div style="font-size:12px;color:var(--text3)" id="ls-pginfo"></div>
    <div style="display:flex;gap:4px" id="ls-pgbtns"></div>
  </div>
</div>

<!-- LEAD DETAIL MODAL -->
<div id="ls-modal" class="ls-modal-backdrop" style="display:none" onclick="if(event.target===this)closeModal()">
  <div class="ls-modal">
    <div class="ls-modal-head">
      <div>
        <div style="font-size:16px;font-weight:800;color:var(--text)" id="modal-name">—</div>
        <div style="font-size:12px;color:var(--text3);margin-top:2px" id="modal-industry-loc">—</div>
      </div>
      <button onclick="closeModal()" style="background:none;border:none;font-size:18px;color:var(--text3);cursor:pointer;padding:4px;flex-shrink:0">✕</button>
    </div>
    <div class="ls-modal-body">
      <!-- Score badge -->
      <div id="modal-score-badge"></div>
      <!-- Grid of details -->
      <div class="ls-modal-grid" id="modal-grid"></div>
      <!-- Action buttons -->
      <div style="display:flex;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)" id="modal-actions"></div>
    </div>
  </div>
</div>

<script>
var lsPage=1, lsTotal=0, lsPerPage=50, lsSort='id', lsDir='desc';
var lsIds=[], lsAllData=[], lsDebTimer=null;

document.addEventListener('DOMContentLoaded', function(){ load(1); loadStats(); });

function loadStats() {
    // Total
    fetch('lead_generator_api.php?action=get_all_stored&page=1&per_page=1').then(r=>r.json()).then(d=>{
        if(d.ok){ document.getElementById('st-total').textContent=d.total; document.getElementById('st-loc').textContent=(d.locations||[]).length; }
    });
    fetch('lead_generator_api.php?action=get_all_stored&page=1&per_page=1&website=0').then(r=>r.json()).then(d=>{if(d.ok)document.getElementById('st-noweb').textContent=d.total;});
    fetch('lead_generator_api.php?action=get_all_stored&page=1&per_page=1&score_min=70').then(r=>r.json()).then(d=>{if(d.ok)document.getElementById('st-hot').textContent=d.total;});
    fetch('lead_generator_api.php?action=get_all_stored&page=1&per_page=1&imported=1').then(r=>r.json()).then(d=>{if(d.ok)document.getElementById('st-imp').textContent=d.total;});
}

function load(page) {
    page=page||1; lsPage=page;
    lsSort=document.getElementById('f-sort').value||'id';
    lsDir=document.getElementById('f-dir').value||'desc';
    lsPerPage=parseInt(document.getElementById('f-pp').value)||50;
    var s=document.getElementById('f-search').value||'';
    var lo=document.getElementById('f-loc').value||'';
    var ind=document.getElementById('f-ind').value||'';
    var web=document.getElementById('f-web').value||'';
    var imp=document.getElementById('f-imp').value||'';
    var sm=parseInt(document.getElementById('f-smin').value)||0;
    var sx=parseInt(document.getElementById('f-smax').value)||100;
    var url='lead_generator_api.php?action=get_all_stored&page='+page+'&per_page='+lsPerPage+'&sort='+lsSort+'&dir='+lsDir;
    if(s)   url+='&search='+encodeURIComponent(s);
    if(lo)  url+='&location='+encodeURIComponent(lo);
    if(ind) url+='&industry='+encodeURIComponent(ind);
    if(web) url+='&website='+web;
    if(imp) url+='&imported='+imp;
    if(sm>0)   url+='&score_min='+sm;
    if(sx<100) url+='&score_max='+sx;
    var tb=document.getElementById('ls-tbody');
    tb.innerHTML='<tr><td colspan="11" class="ls-empty"><span class="ls-spin"></span>Loading...</td></tr>';
    fetch(url).then(r=>r.json()).then(d=>{
        if(!d.ok){toast(d.error||'Failed','error');return;}
        lsTotal=d.total; lsIds=[]; lsAllData=d.leads||[];
        renderTable(d.leads||[]);
        renderPagination(d.total,page,lsPerPage);
        populateDropdowns(d.locations||[],d.industries||[]);
        document.getElementById('ls-count').textContent=d.total+' lead'+(d.total!==1?'s':'')+' found';
        updateSortHeaders();
    }).catch(e=>{toast('Network error','error');console.error(e);});
}

function debounce(){clearTimeout(lsDebTimer);lsDebTimer=setTimeout(()=>load(1),300);}

function renderTable(leads) {
    var tb=document.getElementById('ls-tbody');
    if(!leads.length){
        tb.innerHTML='<tr><td colspan="11" class="ls-empty"><div style="font-size:32px;margin-bottom:8px">📭</div><div>No leads match your filters</div><button onclick="resetFilters()" class="btn btn-ghost btn-sm" style="margin-top:10px">Clear Filters</button></td></tr>';
        return;
    }
    tb.innerHTML=leads.map(function(l,i){
        lsIds.push(l.id);
        var score=parseInt(l.opportunity_score)||0;
        var sc=score>=70?'#10b981':score>=40?'#f59e0b':'#94a3b8';
        var scoreBg=score>=70?'rgba(16,185,129,.1)':score>=40?'rgba(245,158,11,.1)':'transparent';
        var scoreCell='<div class="ls-score-cell"><div class="ls-score-num" style="color:'+sc+'">'+score+'</div>'
            +(score>=70?'<span class="ls-score-hot" style="background:rgba(16,185,129,.12);color:#10b981">HOT</span>':'')+'</div>';
        var hasWeb=parseInt(l.has_website)===1;
        var webBadge=hasWeb
            ?(l.website?'<a href="'+esc(l.website)+'" target="_blank" class="ls-web-yes" onclick="event.stopPropagation()">✅ Yes ↗</a>':'<span class="ls-web-yes">✅ Yes</span>')
            :'<span class="ls-web-no">🔥 No</span>';
        var phone=l.phone?('<a href="tel:'+esc(l.phone)+'" style="color:var(--text);font-family:monospace;text-decoration:none;font-size:12.5px" onclick="event.stopPropagation()">'+esc(l.phone)+'</a>'):'<span style="color:var(--text3)">—</span>';
        var email=l.email?('<a href="mailto:'+esc(l.email)+'" style="color:var(--orange);font-size:12px;word-break:break-all" onclick="event.stopPropagation()">'+esc(l.email)+'</a>'):'<span style="color:var(--text3)">—</span>';
        var rating=l.rating?('⭐ '+parseFloat(l.rating).toFixed(1)+(l.ratings_total?'<div style="font-size:10px;color:var(--text3)">'+l.ratings_total+'</div>':'')):'—';
        var imp_btn=l.imported?''
            :'<button onclick="impOne('+l.id+',this,event)" class="ls-btn ls-btn-imp" title="Import to CRM">⬇</button>';
        var row_bg=hasWeb?'':'background:rgba(249,115,22,.02)';
        return '<tr id="row-'+l.id+'" style="'+row_bg+'" onclick="showDetail('+i+')">'
            +'<td onclick="event.stopPropagation()"><input type="checkbox" class="ls-chk" data-id="'+l.id+'" onchange="updBulk()"></td>'
            +'<td>'+scoreCell+'</td>'
            +'<td><div class="ls-name">'+esc(l.name)+'</div>'+(l.owner_name?'<div class="ls-owner">👤 '+esc(l.owner_name)+'</div>':'')+'</td>'
            +'<td>'+phone+'</td>'
            +'<td>'+email+'</td>'
            +'<td style="font-size:12px;white-space:nowrap">'+esc(l.location||'—')+'</td>'
            +'<td style="font-size:12px;color:var(--text3)">'+esc(l.industry||'—')+'</td>'
            +'<td>'+webBadge+'</td>'
            +'<td class="ls-rating" style="color:#f59e0b;font-size:12px;font-weight:700">'+rating+'</td>'
            +'<td style="font-size:11px;color:var(--text3);white-space:nowrap">'+fmtDate(l.created_at)+'</td>'
            +'<td onclick="event.stopPropagation()"><div style="display:flex;gap:4px;align-items:center">'
                +'<button onclick="showDetail('+i+')" class="ls-btn ls-btn-view" title="View Details">👁</button>'
                +imp_btn
                +'<button onclick="delOne('+l.id+',this)" class="ls-btn ls-btn-del" title="Delete">🗑</button>'
            +'</div></td>'
            +'</tr>';
    }).join('');
    // checkbox state
    document.getElementById('sel-all').checked=false;
    document.getElementById('sel-all').indeterminate=false;
}

function showDetail(idx) {
    var l=lsAllData[idx]; if(!l) return;
    var score=parseInt(l.opportunity_score)||0;
    var sc=score>=70?'#10b981':score>=40?'#f59e0b':'#94a3b8';
    var scoreBg=score>=70?'rgba(16,185,129,.12)':score>=40?'rgba(245,158,11,.1)':'rgba(148,163,184,.08)';
    var hasWeb=parseInt(l.has_website)===1;

    document.getElementById('modal-name').textContent=l.name||'Unknown';
    document.getElementById('modal-industry-loc').textContent=(l.industry||'')+(l.location?' · '+l.location:'');

    // Score badge
    document.getElementById('modal-score-badge').innerHTML=
        '<div class="ls-score-badge-lg" style="background:'+scoreBg+';border:1px solid '+sc+'20">'
        +'<div style="font-size:28px;font-weight:900;color:'+sc+'">'+score+'</div>'
        +'<div style="font-size:11px;font-weight:700;color:'+sc+'">'+(score>=70?'🔥 HOT LEAD':score>=40?'👍 Good Lead':'Potential Lead')+'</div>'
        +'</div>';

    // Detail grid
    var items=[
        {label:'Phone', val: l.phone?'<a href="tel:'+esc(l.phone)+'" style="color:var(--orange)">'+esc(l.phone)+'</a>':'—'},
        {label:'Email', val: l.email?'<a href="mailto:'+esc(l.email)+'" style="color:var(--orange)">'+esc(l.email)+'</a>':'—'},
        {label:'Website', val: hasWeb&&l.website?'<a href="'+esc(l.website)+'" target="_blank" style="color:var(--orange)">'+esc(l.website)+'</a>':'(No website — prime prospect!)'},
        {label:'Has Website', val: hasWeb?'<span class="ls-web-yes">✅ Yes</span>':'<span class="ls-web-no">🔥 No — Needs one built!</span>'},
        {label:'Owner Name', val: l.owner_name?esc(l.owner_name):'Not found'},
        {label:'Industry', val: esc(l.industry||'—')},
        {label:'Location', val: esc(l.location||'—')},
        {label:'Google Rating', val: l.rating?('⭐ '+parseFloat(l.rating).toFixed(1)+'/5'+(l.ratings_total?' ('+l.ratings_total+' reviews)':'')):'—'},
        {label:'Address', val: l.address?esc(l.address):'—', full:true},
        {label:'Imported to CRM', val: l.imported?'<span class="ls-imp-done">✓ Yes — in CRM pipeline</span>':'<span style="color:var(--text3)">Not yet imported</span>'},
        {label:'Date Found', val: fmtDate(l.created_at)},
        {label:'Opportunity Score', val: '<strong style="color:'+sc+';font-size:16px">'+score+'</strong> / 100'},
    ];
    document.getElementById('modal-grid').innerHTML=items.map(function(item){
        return '<div class="ls-detail-block'+(item.full?' ls-modal-full':'')+'">'
            +'<div class="ls-detail-label">'+item.label+'</div>'
            +'<div class="ls-detail-val">'+item.val+'</div>'
            +'</div>';
    }).join('');

    // Action buttons
    var actions='';
    if(l.phone)   actions+='<a href="tel:'+esc(l.phone)+'" class="btn btn-sm" style="background:#10b981;color:#fff;border:none;text-decoration:none">📞 Call</a>';
    if(l.email)   actions+='<a href="mailto:'+esc(l.email)+'" class="btn btn-sm" style="background:#6366f1;color:#fff;border:none;text-decoration:none">✉ Email</a>';
    if(l.website) actions+='<a href="'+esc(l.website)+'" target="_blank" class="btn btn-ghost btn-sm">🌐 Website</a>';
    if(!l.imported) actions+='<button onclick="impOne('+l.id+',this,null,true)" class="btn btn-sm" style="background:var(--orange);color:#fff;border:none">⬇ Import to CRM</button>';
    document.getElementById('modal-actions').innerHTML=actions||'<span style="color:var(--text3);font-size:13px">No contact info available</span>';

    document.getElementById('ls-modal').style.display='flex';
    if(document&&document.body&&document.body.style)document.body.style.overflow='hidden';
}
function closeModal(){var m=document.getElementById('ls-modal');if(m)m.style.display='none';if(document&&document.body&&document.body.style)document.body.style.overflow='';}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeModal();});

function renderPagination(total,cur,pp) {
    var tp=Math.ceil(total/pp);
    var s=((cur-1)*pp)+1, e=Math.min(cur*pp,total);
    document.getElementById('ls-pginfo').textContent='Showing '+(total?s:0)+'–'+e+' of '+total+' leads';
    if(tp<=1){document.getElementById('ls-pgbtns').innerHTML='';return;}
    var html='';
    if(cur>1) html+='<button onclick="load('+(cur-1)+')" class="btn btn-ghost btn-sm">← Prev</button>';
    var si=Math.max(1,cur-2),ei=Math.min(tp,cur+2);
    if(si>1){html+='<button onclick="load(1)" class="btn btn-ghost btn-sm">1</button>';if(si>2)html+='<span style="padding:5px 6px;color:var(--text3)">...</span>';}
    for(var i=si;i<=ei;i++){
        if(i===cur)html+='<button class="btn btn-sm" style="background:var(--orange);color:#fff;border:none">'+i+'</button>';
        else html+='<button onclick="load('+i+')" class="btn btn-ghost btn-sm">'+i+'</button>';
    }
    if(ei<tp){if(ei<tp-1)html+='<span style="padding:5px 6px;color:var(--text3)">...</span>';html+='<button onclick="load('+tp+')" class="btn btn-ghost btn-sm">'+tp+'</button>';}
    if(cur<tp) html+='<button onclick="load('+(cur+1)+')" class="btn btn-ghost btn-sm">Next →</button>';
    document.getElementById('ls-pgbtns').innerHTML=html;
}

function populateDropdowns(locs,inds) {
    var ls=document.getElementById('f-loc'),is=document.getElementById('f-ind');
    if(ls&&locs.length){var v=ls.value;ls.innerHTML='<option value="">All Locations</option>'+locs.map(l=>'<option value="'+esc(l)+'">'+esc(l)+'</option>').join('');ls.value=v;}
    if(is&&inds.length){var v=is.value;is.innerHTML='<option value="">All Industries</option>'+inds.map(i=>'<option value="'+esc(i)+'">'+esc(i)+'</option>').join('');is.value=v;}
}

function changeSort(field) {
    var cs=document.getElementById('f-sort').value;
    var cd=document.getElementById('f-dir').value;
    document.getElementById('f-sort').value=field;
    document.getElementById('f-dir').value=(cs===field&&cd==='desc')?'asc':'desc';
    load(1);
}
function updateSortHeaders() {
    var map={opportunity_score:'score',name:'name',id:'loc',rating:'rat',created_at:'date'};
    Object.keys(map).forEach(function(f){var th=document.getElementById('th-'+map[f]);if(th)th.className='sortable';});
    var cur=document.getElementById('f-sort').value;
    var dir=document.getElementById('f-dir').value;
    var thId='th-'+(map[cur]||'');
    var th=document.getElementById(thId);
    if(th) th.className='sortable sort-'+(dir==='asc'?'asc':'desc');
}

function resetFilters() {
    ['f-search','f-loc','f-ind','f-web','f-imp'].forEach(id=>{var el=document.getElementById(id);if(el)el.value='';});
    document.getElementById('f-smin').value='0';
    document.getElementById('f-smax').value='100';
    document.getElementById('f-sort').value='id';
    document.getElementById('f-dir').value='desc';
    load(1);
}

function selAll(cb){document.querySelectorAll('.ls-chk').forEach(c=>c.checked=cb.checked);updBulk();}
function updBulk(){
    var sel=document.querySelectorAll('.ls-chk:checked').length;
    var all=document.querySelectorAll('.ls-chk').length;
    var sa=document.getElementById('sel-all');
    if(sa){sa.checked=sel>0&&sel===all;sa.indeterminate=sel>0&&sel<all;}
    var bi=document.getElementById('btn-imp'),bd=document.getElementById('btn-del');
    if(bi){bi.disabled=sel===0;bi.textContent=sel>0?'⬇ Import Selected ('+sel+')':'⬇ Import Selected';}
    if(bd){bd.disabled=sel===0;bd.textContent=sel>0?'🗑 Delete Selected ('+sel+')':'🗑 Delete Selected';}
}
document.addEventListener('change',e=>{if(e.target.classList.contains('ls-chk'))updBulk();});

function impOne(id,btn,evt,fromModal) {
    if(evt) evt.stopPropagation();
    if(btn){btn.disabled=true;btn.textContent='...';}
    var fd=new FormData();fd.append('action','import_lead');fd.append('result_id',id);
    fetch('lead_generator_api.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.ok){
            toast(d.message,'success');
            // Update row
            var row=document.getElementById('row-'+id);
            if(row){var imp_td=row.querySelector('.ls-btn-imp');if(imp_td)imp_td.remove();}
            // Update modal actions if open
            if(fromModal){var ma=document.getElementById('modal-actions');if(ma){var ib=ma.querySelector('button[onclick*="impOne"]');if(ib)ib.remove();}}
            // Update data
            var dataItem=lsAllData.find(l=>l.id==id);if(dataItem)dataItem.imported=true;
            loadStats();
        } else {if(btn){btn.disabled=false;btn.textContent='⬇';}toast(d.error||'Failed','error');}
    });
}
function delOne(id,btn) {
    if(!confirm('Delete this lead permanently?')) return;
    btn.disabled=true;
    var fd=new FormData();fd.append('action','bulk_delete');fd.append('ids',id);
    fetch('lead_generator_api.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.ok){var row=document.getElementById('row-'+id);if(row)row.remove();lsTotal--;document.getElementById('ls-count').textContent=lsTotal+' leads found';toast('Lead deleted','success');loadStats();}
        else{btn.disabled=false;toast(d.error||'Failed','error');}
    });
}
function bulkImport() {
    var ids=Array.from(document.querySelectorAll('.ls-chk:checked')).map(c=>c.dataset.id);
    if(!ids.length){toast('No leads selected','info');return;}
    if(!confirm('Import '+ids.length+' leads to CRM?'))return;
    var btn=document.getElementById('btn-imp');btn.disabled=true;btn.textContent='Importing...';
    var fd=new FormData();fd.append('action','import_all');fd.append('ids',ids.join(','));
    fetch('lead_generator_api.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        btn.textContent='⬇ Import Selected';
        if(d.ok){toast(d.imported+' leads imported to CRM!','success');load(lsPage);loadStats();}
        else{toast(d.error||'Failed','error');updBulk();}
    });
}
function bulkDelete() {
    var ids=Array.from(document.querySelectorAll('.ls-chk:checked')).map(c=>c.dataset.id);
    if(!ids.length){toast('No leads selected','info');return;}
    if(!confirm('Delete '+ids.length+' leads? Cannot be undone.'))return;
    var btn=document.getElementById('btn-del');btn.disabled=true;btn.textContent='Deleting...';
    var fd=new FormData();fd.append('action','bulk_delete');fd.append('ids',ids.join(','));
    fetch('lead_generator_api.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        btn.textContent='🗑 Delete Selected';
        if(d.ok){toast(d.deleted+' leads deleted','success');load(lsPage);loadStats();}
        else{toast(d.error||'Failed','error');updBulk();}
    });
}
function exportVisible() {
    var sel=Array.from(document.querySelectorAll('.ls-chk:checked')).map(c=>c.dataset.id);
    var ids=sel.length?sel:lsIds;
    if(!ids.length){toast('No data to export','info');return;}
    var fd=new FormData();fd.append('action','export_csv');fd.append('ids',ids.join(','));
    fetch('lead_generator_api.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(!d.ok||!d.csv){toast('Export failed','error');return;}
        var a=document.createElement('a');
        a.href=URL.createObjectURL(new Blob([d.csv],{type:'text/csv'}));
        a.download='stored_leads_'+new Date().toISOString().slice(0,10)+'.csv';a.click();
        toast('CSV downloaded','success');
    });
}
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function fmtDate(dt){if(!dt)return'';var d=new Date(dt);return['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][d.getMonth()]+' '+d.getDate()+', '+d.getFullYear();}
</script>

<?php renderLayoutEnd(); ?>