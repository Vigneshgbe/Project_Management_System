<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db  = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

$q          = trim($_GET['q'] ?? '');
$type       = $_GET['type'] ?? 'all';
$status_f   = $_GET['status']   ?? '';
$assignee_f = (int)($_GET['assignee'] ?? 0);
$priority_f = $_GET['priority'] ?? '';
$date_from  = $_GET['date_from'] ?? '';
$date_to    = $_GET['date_to']   ?? '';

$all_users = $db->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

renderLayout('Search', 'search');
?>

<style>
/* ── SEARCH PAGE ── */
.srch-hero{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;margin-bottom:18px}
.srch-box-wrap{position:relative;margin-bottom:14px}
.srch-input{
  width:100%;background:var(--bg3);border:2px solid var(--border);
  border-radius:12px;padding:14px 50px 14px 48px;
  color:var(--text);font-size:16px;font-family:var(--font);
  transition:border-color .15s;outline:none
}
.srch-input:focus{border-color:var(--orange)}
.srch-icon{position:absolute;left:16px;top:50%;transform:translateY(-50%);font-size:18px;pointer-events:none;color:var(--text3)}
.srch-clear{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text3);cursor:pointer;font-size:18px;padding:4px;line-height:1;display:none}

/* TYPE TABS */
.type-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:0}
.type-tab{padding:5px 14px;border-radius:99px;font-size:12.5px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:var(--bg3);color:var(--text2);text-decoration:none;transition:all .15s;white-space:nowrap}
.type-tab:hover{border-color:var(--orange);color:var(--orange)}
.type-tab.active{background:var(--orange);border-color:var(--orange);color:#fff}

/* FILTERS */
.filter-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;padding:14px;background:var(--bg3);border-radius:var(--radius);margin-bottom:16px;border:1px solid var(--border)}

/* RESULTS */
.result-item{display:flex;align-items:flex-start;gap:14px;padding:14px 16px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:8px;cursor:pointer;transition:border-color .15s,box-shadow .15s;text-decoration:none;color:inherit}
.result-item:hover{border-color:var(--border2);box-shadow:0 2px 10px rgba(0,0,0,.15)}
.result-icon{font-size:24px;flex-shrink:0;width:36px;height:36px;display:flex;align-items:center;justify-content:center;background:var(--bg3);border-radius:8px}
.result-body{flex:1;min-width:0}
.result-title{font-size:14px;font-weight:700;color:var(--text);margin-bottom:3px}
.result-title mark{background:rgba(249,115,22,.25);color:var(--orange);border-radius:2px;padding:0 2px}
.result-snippet{font-size:12.5px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:5px}
.result-meta{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.result-type{font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.05em}

/* SAVED / RECENT PANELS */
.saved-row{display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:6px;transition:border-color .15s}
.saved-row:hover{border-color:var(--border2)}
.saved-query{font-size:13px;font-weight:600;color:var(--text);flex:1;cursor:pointer}
.saved-query:hover{color:var(--orange)}
.recent-chip{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;background:var(--bg3);border:1px solid var(--border);border-radius:99px;font-size:12.5px;color:var(--text2);cursor:pointer;margin:3px;transition:all .15s}
.recent-chip:hover{border-color:var(--orange);color:var(--orange)}
.recent-chip .rm{color:var(--text3);font-size:10px;margin-left:2px;line-height:1}
.recent-chip .rm:hover{color:var(--red)}

/* WEB SEARCH SANDBOX */
.wsearch-wrap{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-top:18px}
.wsearch-head{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;background:var(--bg3);flex-wrap:wrap;gap:8px}
.wsearch-notice{font-size:11.5px;color:var(--text3);display:flex;align-items:center;gap:5px}
#web-frame{width:100%;height:580px;border:none;display:none}
.web-engine-btn{padding:5px 12px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--bg3);color:var(--text2);font-size:12px;font-weight:600;cursor:pointer;transition:all .15s}
.web-engine-btn.active,.web-engine-btn:hover{background:var(--orange);border-color:var(--orange);color:#fff}

.empty-state-search{text-align:center;padding:48px 20px;color:var(--text3)}
.result-count{font-size:13px;color:var(--text3);margin-bottom:12px}
.result-count strong{color:var(--text)}

.sidebar-panel{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;height:fit-content}

@media(max-width:900px){
  .search-layout{flex-direction:column}
  .search-sidebar{width:100%!important}
  .filter-row{grid-template-columns:1fr 1fr}
}
@media(max-width:480px){
  .filter-row{grid-template-columns:1fr}
  .srch-input{font-size:14px;padding:12px 44px}
}
</style>

<!-- HERO SEARCH BAR -->
<div class="srch-hero">
  <div class="srch-box-wrap">
    <span class="srch-icon">🔍</span>
    <input type="text" class="srch-input" id="main-search"
      placeholder="Search projects, tasks, contacts, documents, leads…"
      value="<?= h($q) ?>"
      autofocus
      oninput="onSearchInput(this)"
      onkeydown="if(event.key==='Enter')doSearch()">
    <button class="srch-clear" id="srch-clear" onclick="clearSearch()">✕</button>
  </div>

  <!-- TYPE TABS -->
  <div class="type-tabs" id="type-tabs">
    <?php
    $types = ['all'=>'All','projects'=>'📁 Projects','tasks'=>'✅ Tasks','contacts'=>'👥 Contacts','documents'=>'📄 Documents'];
    if (isManager()) $types['leads'] = '🎯 Leads';
    foreach ($types as $tv=>$tl):
    ?>
    <a href="#" class="type-tab <?= $type===$tv?'active':'' ?>" onclick="setType('<?= $tv ?>');return false"><?= $tl ?></a>
    <?php endforeach; ?>
  </div>
</div>

<!-- LAYOUT: Results + Sidebar -->
<div style="display:flex;gap:18px;align-items:flex-start" class="search-layout">

  <!-- MAIN RESULTS COLUMN -->
  <div style="flex:1;min-width:0">

    <!-- FILTERS (shown when query active) -->
    <div id="filters-row" class="filter-row" style="<?= $q?'':'display:none' ?>">
      <div>
        <div style="font-size:11px;font-weight:600;color:var(--text3);margin-bottom:4px;text-transform:uppercase">Status</div>
        <select id="f-status" class="form-control" style="padding:6px 10px;font-size:12.5px" onchange="doSearch()">
          <option value="">Any</option>
          <option value="active" <?= $status_f==='active'?'selected':'' ?>>Active</option>
          <option value="planning" <?= $status_f==='planning'?'selected':'' ?>>Planning</option>
          <option value="completed" <?= $status_f==='completed'?'selected':'' ?>>Completed</option>
          <option value="todo" <?= $status_f==='todo'?'selected':'' ?>>To Do</option>
          <option value="in_progress" <?= $status_f==='in_progress'?'selected':'' ?>>In Progress</option>
          <option value="done" <?= $status_f==='done'?'selected':'' ?>>Done</option>
        </select>
      </div>
      <div>
        <div style="font-size:11px;font-weight:600;color:var(--text3);margin-bottom:4px;text-transform:uppercase">Priority</div>
        <select id="f-priority" class="form-control" style="padding:6px 10px;font-size:12.5px" onchange="doSearch()">
          <option value="">Any</option>
          <?php foreach (['urgent','high','medium','low'] as $pv): ?>
          <option value="<?= $pv ?>" <?= $priority_f===$pv?'selected':'' ?>><?= ucfirst($pv) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <div style="font-size:11px;font-weight:600;color:var(--text3);margin-bottom:4px;text-transform:uppercase">Assignee</div>
        <select id="f-assignee" class="form-control" style="padding:6px 10px;font-size:12.5px" onchange="doSearch()">
          <option value="">Anyone</option>
          <?php foreach ($all_users as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $assignee_f==$u['id']?'selected':'' ?>><?= h($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <div style="font-size:11px;font-weight:600;color:var(--text3);margin-bottom:4px;text-transform:uppercase">From Date</div>
        <input type="date" id="f-from" class="form-control" style="padding:6px 10px;font-size:12.5px" value="<?= h($date_from) ?>" onchange="doSearch()">
      </div>
      <div>
        <div style="font-size:11px;font-weight:600;color:var(--text3);margin-bottom:4px;text-transform:uppercase">To Date</div>
        <input type="date" id="f-to" class="form-control" style="padding:6px 10px;font-size:12.5px" value="<?= h($date_to) ?>" onchange="doSearch()">
      </div>
      <div style="display:flex;align-items:flex-end;gap:6px">
        <button class="btn btn-ghost btn-sm" onclick="clearFilters()">Clear</button>
        <button class="btn btn-ghost btn-sm" onclick="saveCurrentSearch()">💾 Save</button>
      </div>
    </div>

    <!-- RESULTS CONTAINER -->
    <div id="results-container">
      <?php if (!$q): ?>
      <!-- EMPTY STATE: show recent + tips -->
      <div class="empty-state-search">
        <div style="font-size:40px;margin-bottom:12px">🔍</div>
        <div style="font-size:15px;font-weight:600;color:var(--text);margin-bottom:6px">Search everything in Padak CRM</div>
        <div style="font-size:13px;color:var(--text3);max-width:400px;margin:0 auto">
          Projects, tasks, contacts, documents, leads — all in one place.<br>
          Use filters to narrow results by status, priority, date, or assignee.
        </div>
      </div>
      <?php else: ?>
      <div id="results-inner">
        <div style="text-align:center;padding:30px;color:var(--text3)">Searching…</div>
      </div>
      <?php endif; ?>
    </div>

    <!-- WEB SEARCH SANDBOX -->
    <div class="wsearch-wrap" id="wsearch-section">
      <div class="wsearch-head">
        <span style="font-size:13px;font-weight:700;color:var(--text)">🌐 Web Search</span>
        <div class="wsearch-notice">🔒 Sandboxed — your CRM data is never sent to search engines</div>
        <div style="display:flex;gap:6px;margin-left:auto;flex-wrap:wrap">
          <button class="web-engine-btn active" id="btn-google" onclick="loadWebSearch('google',this)">Google</button>
          <button class="web-engine-btn" id="btn-bing"   onclick="loadWebSearch('bing',this)">Bing</button>
          <button class="web-engine-btn" id="btn-ddg"    onclick="loadWebSearch('ddg',this)">DuckDuckGo</button>
        </div>
        <button class="btn btn-ghost btn-sm" onclick="toggleWebSearch()" id="wsearch-toggle">Open ▾</button>
      </div>
      <div id="wsearch-body" style="display:none">
        <div style="padding:10px 16px;background:var(--bg3);border-bottom:1px solid var(--border);display:flex;gap:8px;align-items:center">
          <input type="text" id="web-query" class="form-control" style="flex:1"
            placeholder="Search the web (your CRM data stays private)…"
            onkeydown="if(event.key==='Enter')doWebSearch()"
            value="<?= h($q) ?>">
          <button class="btn btn-primary btn-sm" onclick="doWebSearch()">Search</button>
        </div>
        <div id="web-frame-wrap" style="position:relative;background:var(--bg3);min-height:100px">
          <div id="web-placeholder" style="text-align:center;padding:32px;color:var(--text3);font-size:13px">
            Press Search to open web search in a secure sandboxed frame.<br>
            <small>Opens in an isolated iframe — no CRM data is shared with search engines.</small>
          </div>
          <iframe id="web-frame"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox"
            referrerpolicy="no-referrer"
            style="width:100%;height:560px;border:none;display:none"></iframe>
        </div>
      </div>
    </div>
  </div>

  <!-- SIDEBAR: Recent + Saved Searches -->
  <div style="width:280px;flex-shrink:0" class="search-sidebar">

    <!-- RECENT SEARCHES -->
    <div class="sidebar-panel" style="margin-bottom:14px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <div style="font-size:13px;font-weight:700;color:var(--text)">🕐 Recent</div>
        <button onclick="clearAllRecent()" style="background:none;border:none;font-size:11px;color:var(--text3);cursor:pointer">Clear all</button>
      </div>
      <div id="recent-list">
        <div style="font-size:12px;color:var(--text3);text-align:center;padding:12px">Loading…</div>
      </div>
    </div>

    <!-- SAVED SEARCHES -->
    <div class="sidebar-panel">
      <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:12px">💾 Saved Searches</div>
      <div id="saved-list">
        <div style="font-size:12px;color:var(--text3);text-align:center;padding:12px">Loading…</div>
      </div>
    </div>

  </div>
</div>

<script>
var CURRENT_TYPE    = '<?= h($type) ?>';
var CURRENT_QUERY   = <?= json_encode($q) ?>;
var searchDebounce  = null;
var activeEngine    = 'google';

// ── INIT ──
document.addEventListener('DOMContentLoaded', function() {
    loadRecent();
    loadSaved();
    updateClearBtn();
    if (CURRENT_QUERY) { doSearch(); }
    // Keyboard shortcut: / to focus search
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && !['INPUT','TEXTAREA'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            document.getElementById('main-search').focus();
        }
    });
});

// ── SEARCH INPUT ──
function onSearchInput(el) {
    updateClearBtn();
    clearTimeout(searchDebounce);
    var q = el.value.trim();
    if (q.length === 0) {
        document.getElementById('filters-row').style.display = 'none';
        document.getElementById('results-container').innerHTML = '<div class="empty-state-search"><div style="font-size:40px;margin-bottom:12px">🔍</div><div style="font-size:15px;font-weight:600;color:var(--text);margin-bottom:6px">Type to search everything</div></div>';
        return;
    }
    document.getElementById('filters-row').style.display = '';
    if (q.length >= 2) {
        searchDebounce = setTimeout(doSearch, 320);
    }
}
function updateClearBtn() {
    var q = document.getElementById('main-search').value;
    document.getElementById('srch-clear').style.display = q ? 'block' : 'none';
}
function clearSearch() {
    document.getElementById('main-search').value = '';
    updateClearBtn();
    document.getElementById('filters-row').style.display = 'none';
    document.getElementById('results-container').innerHTML = '<div class="empty-state-search"><div style="font-size:40px;margin-bottom:12px">🔍</div><div style="font-size:15px;font-weight:600;color:var(--text);margin-bottom:6px">Search everything</div></div>';
    history.replaceState(null,'','search.php');
    CURRENT_QUERY = '';
}
function setType(t) {
    CURRENT_TYPE = t;
    document.querySelectorAll('.type-tab').forEach(function(el) {
        el.classList.toggle('active', el.textContent.trim().toLowerCase().includes(t === 'all' ? 'all' : t.replace('s','')));
    });
    // Re-set active properly
    document.querySelectorAll('.type-tab').forEach(function(el, i) {
        var types = ['all','projects','tasks','contacts','documents','leads'];
        el.classList.toggle('active', types[i] === t);
    });
    if (CURRENT_QUERY) doSearch();
}

// ── MAIN SEARCH ──
function doSearch() {
    var q    = document.getElementById('main-search').value.trim();
    CURRENT_QUERY = q;
    var status   = document.getElementById('f-status')?.value   || '';
    var priority = document.getElementById('f-priority')?.value || '';
    var assignee = document.getElementById('f-assignee')?.value || '';
    var from     = document.getElementById('f-from')?.value     || '';
    var to       = document.getElementById('f-to')?.value       || '';

    var params = new URLSearchParams({ action:'search', q:q, type:CURRENT_TYPE,
        status:status, priority:priority, assignee:assignee, date_from:from, date_to:to });

    // Update URL
    history.replaceState(null,'','search.php?q='+encodeURIComponent(q)+'&type='+CURRENT_TYPE);
    document.getElementById('web-query').value = q;

    // Show loading
    document.getElementById('results-container').innerHTML = '<div style="text-align:center;padding:30px;color:var(--text3)"><span style="font-size:20px">⏳</span><br>Searching…</div>';

    fetch('search_api.php?' + params.toString())
        .then(function(r){return r.json();})
        .then(renderResults)
        .catch(function(e){ document.getElementById('results-container').innerHTML = '<div style="padding:20px;color:var(--red)">Search error.</div>'; });
}

// ── RENDER RESULTS ──
function renderResults(d) {
    var el = document.getElementById('results-container');
    if (!d.results || !d.results.length) {
        el.innerHTML = '<div class="empty-state-search"><div style="font-size:32px;margin-bottom:10px">😕</div><div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:4px">No results for "'+escHtml(d.query)+'"</div><div style="font-size:12.5px;color:var(--text3)">Try different keywords or filters</div></div>';
        loadRecent();
        return;
    }
    var q = d.query;
    var html = '<div class="result-count">Found <strong>'+d.total+'</strong> results for "<strong>'+escHtml(q)+'</strong>"</div>';
    d.results.forEach(function(r) {
        var title = r.title ? highlightQuery(r.title, q) : escHtml(r.title||'Untitled');
        var snip  = r.snippet ? escHtml(r.snippet.substring(0,120)) : '';
        var date  = r.updated_at ? ' · '+r.updated_at.substring(0,10) : '';
        html += '<a href="'+escHtml(r.url||'#')+'" class="result-item">'
            + '<div class="result-icon">'+r.icon+'</div>'
            + '<div class="result-body">'
                + '<div class="result-title">'+title+'</div>'
                + (snip ? '<div class="result-snippet">'+snip+'</div>' : '')
                + '<div class="result-meta">'
                    + '<span class="result-type">'+escHtml(r._type)+'</span>'
                    + (r.badge ? ' <span class="badge" style="font-size:10px;padding:2px 7px;background:'+escHtml(r.badge_color)+'20;color:'+escHtml(r.badge_color)+'">'+escHtml(r.badge)+'</span>' : '')
                    + (date ? '<span style="font-size:11px;color:var(--text3)">'+escHtml(date)+'</span>' : '')
                + '</div>'
            + '</div>'
            + '</a>';
    });
    el.innerHTML = html;
    loadRecent(); // Refresh recent after search
}

function highlightQuery(text, q) {
    if (!q) return escHtml(text);
    var regex = new RegExp('('+q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')', 'gi');
    return escHtml(text).replace(regex, '<mark>$1</mark>');
}

// ── RECENT SEARCHES ──
function loadRecent() {
    fetch('search_api.php?action=recent')
        .then(function(r){return r.json();})
        .then(function(d) {
            var el = document.getElementById('recent-list');
            if (!d.recent || !d.recent.length) {
                el.innerHTML = '<div style="font-size:12px;color:var(--text3);text-align:center;padding:8px">No recent searches</div>';
                return;
            }
            el.innerHTML = d.recent.map(function(r) {
                return '<div class="recent-chip" onclick="applyRecent(\''+escAttr(r.query)+'\')">'
                    + '<span>'+escHtml(r.query)+'</span>'
                    + (r.result_count ? '<span style="font-size:10px;color:var(--text3)">('+r.result_count+')</span>' : '')
                    + '<span class="rm" onclick="deleteRecent(event,'+r.id+')">✕</span>'
                    + '</div>';
            }).join('');
        });
}
function applyRecent(q) {
    document.getElementById('main-search').value = q;
    CURRENT_QUERY = q;
    updateClearBtn();
    document.getElementById('filters-row').style.display = '';
    doSearch();
}
function deleteRecent(e, id) {
    e.stopPropagation();
    fetch('search_api.php?action=delete_recent', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id='+id })
        .then(function(){ loadRecent(); });
}
function clearAllRecent() {
    if (!confirm('Clear all recent searches?')) return;
    fetch('search_api.php?action=delete_recent', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id=0' })
        .then(function(){ loadRecent(); });
}

// ── SAVED SEARCHES ──
function loadSaved() {
    fetch('search_api.php?action=saved')
        .then(function(r){return r.json();})
        .then(function(d) {
            var el = document.getElementById('saved-list');
            if (!d.saved || !d.saved.length) {
                el.innerHTML = '<div style="font-size:12px;color:var(--text3);text-align:center;padding:8px">No saved searches.<br><small>Run a search and click 💾 Save.</small></div>';
                return;
            }
            el.innerHTML = d.saved.map(function(s) {
                var filters = '';
                try { var f = JSON.parse(s.filters||'{}'); if(f.status) filters+=' · '+f.status; } catch(e){}
                return '<div class="saved-row">'
                    + (s.is_pinned ? '<span title="Pinned" style="color:var(--orange)">📌</span>' : '<span style="color:var(--text3)">🔖</span>')
                    + '<div style="flex:1;min-width:0">'
                        + '<div class="saved-query" onclick="applyRecent(\''+escAttr(s.query||'')+'\')" title="'+escAttr(s.label)+'">'+escHtml(s.label)+'</div>'
                        + (filters ? '<div style="font-size:11px;color:var(--text3)">'+escHtml(filters)+'</div>' : '')
                    + '</div>'
                    + '<button class="btn btn-ghost btn-sm btn-icon" title="'+(s.is_pinned?'Unpin':'Pin')+'" onclick="pinSaved('+s.id+')">📌</button>'
                    + '<button class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="deleteSaved('+s.id+')">✕</button>'
                    + '</div>';
            }).join('');
        });
}
function saveCurrentSearch() {
    var q = document.getElementById('main-search').value.trim();
    if (!q) { toast('Enter a search query first','error'); return; }
    var label = prompt('Save this search as:', q);
    if (!label) return;
    var filters = JSON.stringify({
        status:   document.getElementById('f-status')?.value||'',
        priority: document.getElementById('f-priority')?.value||'',
        assignee: document.getElementById('f-assignee')?.value||'',
        date_from:document.getElementById('f-from')?.value||'',
        date_to:  document.getElementById('f-to')?.value||'',
        type: CURRENT_TYPE
    });
    fetch('search_api.php?action=save_search', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: 'label='+encodeURIComponent(label)+'&query='+encodeURIComponent(q)+'&filters='+encodeURIComponent(filters)
    }).then(function(r){return r.json();}).then(function(d){
        if (d.ok) { toast('Search saved!','success'); loadSaved(); }
    });
}
function pinSaved(id) {
    fetch('search_api.php?action=pin_saved', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id='+id })
        .then(function(){ loadSaved(); });
}
function deleteSaved(id) {
    fetch('search_api.php?action=delete_saved', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id='+id })
        .then(function(){ loadSaved(); });
}
function clearFilters() {
    ['f-status','f-priority','f-assignee','f-from','f-to'].forEach(function(id) {
        var el = document.getElementById(id); if (el) el.value = '';
    });
    doSearch();
}

// ── WEB SEARCH SANDBOX ──
function toggleWebSearch() {
    var body = document.getElementById('wsearch-body');
    var btn  = document.getElementById('wsearch-toggle');
    var open = body.style.display !== 'none';
    body.style.display = open ? 'none' : '';
    btn.textContent    = open ? 'Open ▾' : 'Close ▴';
}
function loadWebSearch(engine, btn) {
    activeEngine = engine;
    document.querySelectorAll('.web-engine-btn').forEach(function(b){b.classList.remove('active')});
    btn.classList.add('active');
}
function doWebSearch() {
    var q = document.getElementById('web-query').value.trim();
    if (!q) return;
    var urls = {
        google: 'https://www.google.com/search?q=',
        bing:   'https://www.bing.com/search?q=',
        ddg:    'https://duckduckgo.com/?q='
    };
    var base = urls[activeEngine] || urls.google;
    var frame = document.getElementById('web-frame');
    var ph    = document.getElementById('web-placeholder');
    frame.src = base + encodeURIComponent(q);
    frame.style.display = 'block';
    ph.style.display    = 'none';
    // Ensure body is open
    document.getElementById('wsearch-body').style.display = '';
    document.getElementById('wsearch-toggle').textContent = 'Close ▴';
}

// ── UTILS ──
function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(s) {
    return String(s||'').replace(/'/g,"\\'").replace(/"/g,'&quot;');
}
</script>

<?php renderLayoutEnd(); ?>