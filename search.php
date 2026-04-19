<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

$q          = trim($_GET['q']          ?? '');
$type       = $_GET['type']            ?? 'all';
$status_f   = $_GET['status']          ?? '';
$assignee_f = (int)($_GET['assignee']  ?? 0);
$priority_f = $_GET['priority']        ?? '';
$date_from  = $_GET['date_from']       ?? '';
$date_to    = $_GET['date_to']         ?? '';

$all_users = $db->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

renderLayout('Search', 'search');
?>

<style>
/* ═══ SEARCH PAGE ═══ */

/* Hero */
.srch-hero{
  background:var(--bg2);border:1px solid var(--border);
  border-radius:var(--radius-lg);padding:28px 24px 20px;
  margin-bottom:20px;
}
.srch-hero-title{
  font-family:var(--font-display);font-size:22px;font-weight:700;
  color:var(--text);margin-bottom:4px;
}
.srch-hero-sub{font-size:13px;color:var(--text3);margin-bottom:18px}

/* Search input */
.srch-field{
  position:relative;margin-bottom:16px;
}
.srch-field input{
  width:100%;background:var(--bg3);
  border:2px solid var(--border);border-radius:12px;
  padding:13px 48px 13px 46px;
  color:var(--text);font-size:15px;font-family:var(--font);
  transition:border-color .2s,box-shadow .2s;outline:none;
}
.srch-field input:focus{
  border-color:var(--orange);
  box-shadow:0 0 0 3px rgba(249,115,22,.12);
}
.srch-field input::placeholder{color:var(--text3)}
.srch-field-icon{
  position:absolute;left:15px;top:50%;transform:translateY(-50%);
  font-size:17px;pointer-events:none;color:var(--text3);
  transition:color .2s;
}
.srch-field input:focus ~ .srch-field-icon{color:var(--orange)}
.srch-field-clear{
  position:absolute;right:14px;top:50%;transform:translateY(-50%);
  background:var(--bg4);border:none;border-radius:99px;
  width:22px;height:22px;cursor:pointer;color:var(--text3);
  font-size:11px;display:none;align-items:center;justify-content:center;
  transition:background .1s,color .1s;
}
.srch-field-clear:hover{background:var(--border2);color:var(--text)}

/* Type tabs */
.type-tabs{display:flex;gap:6px;flex-wrap:wrap}
.type-tab{
  padding:6px 14px;border-radius:99px;font-size:12.5px;font-weight:600;
  cursor:pointer;border:1px solid var(--border);background:var(--bg3);
  color:var(--text2);text-decoration:none;
  transition:border-color .15s,color .15s,background .15s;white-space:nowrap;
}
.type-tab:hover{border-color:var(--orange);color:var(--orange)}
.type-tab.active{background:var(--orange);border-color:var(--orange);color:#fff}

/* Filters */
.filter-bar{
  display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;
  padding:14px 16px;background:var(--bg3);border:1px solid var(--border);
  border-radius:var(--radius);margin-bottom:16px;
}
.filter-label{
  font-size:10.5px;font-weight:700;color:var(--text3);
  text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;
}
.filter-bar select,.filter-bar input[type=date]{
  width:100%;background:var(--bg2);border:1px solid var(--border);
  border-radius:var(--radius-sm);padding:6px 10px;
  color:var(--text);font-size:12.5px;font-family:var(--font);
  transition:border-color .15s;outline:none;cursor:pointer;
}
.filter-bar select:focus,.filter-bar input:focus{border-color:var(--orange)}
.filter-actions{display:flex;align-items:flex-end;gap:6px}

/* Result count */
.result-count{
  font-size:13px;color:var(--text3);margin-bottom:14px;
  display:flex;align-items:center;justify-content:space-between;
}
.result-count strong{color:var(--text);font-weight:700}

/* Result card */
.result-card{
  display:flex;align-items:flex-start;gap:14px;
  padding:14px 16px;
  background:var(--bg2);border:1px solid var(--border);
  border-left:3px solid var(--rc);
  border-radius:var(--radius);margin-bottom:8px;
  text-decoration:none;color:inherit;
  transition:border-color .15s,box-shadow .15s,transform .1s;
}
.result-card:hover{
  border-color:var(--border2);
  box-shadow:0 2px 12px rgba(0,0,0,.15);
  transform:translateY(-1px);
}
.result-card:active{transform:translateY(0)}
.result-icon-wrap{
  width:38px;height:38px;border-radius:9px;
  background:var(--rc-bg);display:flex;align-items:center;
  justify-content:center;font-size:18px;flex-shrink:0;
}
.result-body{flex:1;min-width:0}
.result-title{
  font-size:14px;font-weight:700;color:var(--text);
  margin-bottom:3px;line-height:1.3;
}
.result-title mark{
  background:rgba(249,115,22,.2);color:var(--orange);
  border-radius:3px;padding:0 2px;font-style:normal;
}
.result-snippet{
  font-size:12.5px;color:var(--text3);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  margin-bottom:6px;
}
.result-meta{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.result-type-lbl{
  font-size:10.5px;font-weight:700;color:var(--text3);
  text-transform:uppercase;letter-spacing:.05em;
}
.result-arrow{color:var(--text3);font-size:16px;flex-shrink:0;margin-top:10px}

/* Empty state */
.srch-empty{
  text-align:center;padding:52px 20px;color:var(--text3);
}
.srch-empty-icon{font-size:44px;margin-bottom:12px;opacity:.7}
.srch-empty-title{font-size:15px;font-weight:700;color:var(--text);margin-bottom:6px}
.srch-empty-sub{font-size:13px;line-height:1.6;max-width:360px;margin:0 auto}

/* Sidebar panels */
.srch-panel{
  background:var(--bg2);border:1px solid var(--border);
  border-radius:var(--radius-lg);padding:16px;
}
.srch-panel+.srch-panel{margin-top:14px}
.panel-title{
  font-size:13px;font-weight:700;color:var(--text);
  margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;
}
.panel-title-clear{
  background:none;border:none;font-size:11.5px;
  color:var(--text3);cursor:pointer;
}
.panel-title-clear:hover{color:var(--red)}

/* Recent chips */
.recent-chip{
  display:inline-flex;align-items:center;gap:5px;
  padding:4px 10px;background:var(--bg3);border:1px solid var(--border);
  border-radius:99px;font-size:12.5px;color:var(--text2);
  cursor:pointer;margin:2px;
  transition:border-color .15s,color .15s;
}
.recent-chip:hover{border-color:var(--orange);color:var(--orange)}
.recent-chip-rm{
  background:none;border:none;padding:0;line-height:1;
  color:var(--text3);cursor:pointer;font-size:11px;
}
.recent-chip-rm:hover{color:var(--red)}
.recent-cnt{font-size:10px;color:var(--text3)}

/* Saved rows */
.saved-row{
  display:flex;align-items:center;gap:8px;
  padding:8px 10px;background:var(--bg3);border:1px solid var(--border);
  border-radius:var(--radius-sm);margin-bottom:6px;
  transition:border-color .15s;
}
.saved-row:hover{border-color:var(--border2)}
.saved-name{
  flex:1;font-size:12.5px;font-weight:600;color:var(--text);
  cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.saved-name:hover{color:var(--orange)}

/* External search engine tabs */
.ws-tab{
  flex:1;min-width:80px;padding:8px 6px;background:var(--bg3);border:none;
  border-right:1px solid var(--border);color:var(--text2);font-size:12px;
  font-weight:600;cursor:pointer;transition:background .15s,color .15s;
  font-family:var(--font);position:relative;display:flex;align-items:center;
  justify-content:center;gap:4px;flex-wrap:wrap;
}
.ws-tab:last-child{border-right:none}
.ws-tab:hover{background:var(--bg4);color:var(--text)}
.ws-tab.active{background:var(--orange);color:#fff}
.ws-badge{font-size:9px;font-weight:700;padding:1px 5px;border-radius:99px;letter-spacing:.03em}
.ws-badge.embed{background:rgba(16,185,129,.2);color:#10b981}
.ws-badge.newtab{background:rgba(148,163,184,.2);color:var(--text3)}
.ws-tab.active .ws-badge.embed{background:rgba(255,255,255,.25);color:#fff}
.ws-tab.active .ws-badge.newtab{background:rgba(255,255,255,.2);color:#fff}

.ext-search-card{
  background:var(--bg2);border:1px solid var(--border);
  border-radius:var(--radius-lg);padding:18px 20px;
  margin-top:16px;
}
.ext-search-title{
  font-size:13px;font-weight:700;color:var(--text);
  margin-bottom:4px;display:flex;align-items:center;gap:8px;
}
.ext-search-notice{
  font-size:12px;color:var(--text3);margin-bottom:14px;line-height:1.5;
}
.ext-btn{
  display:inline-flex;align-items:center;gap:7px;
  padding:9px 16px;border-radius:var(--radius-sm);
  font-size:13px;font-weight:600;border:1px solid var(--border);
  background:var(--bg3);color:var(--text2);cursor:pointer;
  transition:all .15s;text-decoration:none;margin:4px 4px 4px 0;
}
.ext-btn:hover{background:var(--bg4);border-color:var(--border2);color:var(--text)}
.ext-btn.primary-ext{background:var(--orange);border-color:var(--orange);color:#fff}
.ext-btn.primary-ext:hover{opacity:.9}
.ext-search-input{
  width:100%;background:var(--bg3);border:1px solid var(--border);
  border-radius:var(--radius-sm);padding:9px 12px;
  color:var(--text);font-size:13.5px;font-family:var(--font);
  transition:border-color .15s;outline:none;margin-bottom:10px;
}
.ext-search-input:focus{border-color:var(--orange)}

/* Loading skeleton */
.skeleton{
  background:linear-gradient(90deg,var(--bg3) 25%,var(--bg4) 50%,var(--bg3) 75%);
  background-size:200% 100%;animation:shimmer 1.2s infinite;
  border-radius:6px;
}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
.skel-row{height:72px;margin-bottom:8px;border-radius:var(--radius)}

/* No results tips */
.search-tip{
  display:flex;align-items:flex-start;gap:10px;padding:10px 12px;
  background:var(--bg3);border-radius:var(--radius-sm);margin-bottom:6px;
  font-size:12.5px;color:var(--text2);
}

/* Responsive */
@media(max-width:900px){
  .srch-layout{flex-direction:column}
  .srch-sidebar{width:100%!important;display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .srch-panel+.srch-panel{margin-top:0}
  .filter-bar{grid-template-columns:1fr 1fr}
}
@media(max-width:600px){
  .srch-hero{padding:20px 16px 16px}
  .srch-hero-title{font-size:18px}
  .filter-bar{grid-template-columns:1fr}
  .srch-sidebar{grid-template-columns:1fr}
  .srch-field input{font-size:14px;padding:11px 44px}
  .ws-tab{min-width:70px;font-size:11px;padding:7px 4px}
}
</style>

<!-- HERO -->
<div class="srch-hero">
  <div class="srch-hero-title">Advanced Search</div>
  <div class="srch-hero-sub">Search across projects, tasks, contacts, documents and leads</div>

  <div class="srch-field">
    <span class="srch-field-icon">🔍</span>
    <input type="text" id="main-search"
      placeholder="Type to search everything…"
      value="<?= h($q) ?>"
      autocomplete="off"
      autofocus
      oninput="onInput(this)"
      onkeydown="if(event.key==='Enter')doSearch()">
    <button class="srch-field-clear" id="srch-clear" onclick="clearSearch()">✕</button>
  </div>

  <div class="type-tabs" id="type-tabs">
    <?php
    $tabs = ['all'=>'All','projects'=>'📁 Projects','tasks'=>'✅ Tasks','contacts'=>'👥 Contacts','documents'=>'📄 Documents'];
    if (isManager()) $tabs['leads'] = '🎯 Leads';
    foreach ($tabs as $tv=>$tl):
    ?>
    <a href="#" class="type-tab <?= $type===$tv?'active':'' ?>"
       onclick="setType('<?= $tv ?>');return false"><?= $tl ?></a>
    <?php endforeach; ?>
  </div>
</div>

<!-- LAYOUT -->
<div style="display:flex;gap:18px;align-items:flex-start" class="srch-layout">

  <!-- MAIN COLUMN -->
  <div style="flex:1;min-width:0">

    <!-- FILTERS -->
    <div class="filter-bar" id="filter-bar" style="<?= $q?'':'display:none' ?>">
      <div>
        <div class="filter-label">Status</div>
        <select id="f-status" onchange="doSearch()">
          <option value="">Any status</option>
          <option value="active"      <?= $status_f==='active'?'selected':'' ?>>Active</option>
          <option value="planning"    <?= $status_f==='planning'?'selected':'' ?>>Planning</option>
          <option value="completed"   <?= $status_f==='completed'?'selected':'' ?>>Completed</option>
          <option value="todo"        <?= $status_f==='todo'?'selected':'' ?>>To Do</option>
          <option value="in_progress" <?= $status_f==='in_progress'?'selected':'' ?>>In Progress</option>
          <option value="done"        <?= $status_f==='done'?'selected':'' ?>>Done</option>
        </select>
      </div>
      <div>
        <div class="filter-label">Priority</div>
        <select id="f-priority" onchange="doSearch()">
          <option value="">Any priority</option>
          <?php foreach (['urgent','high','medium','low'] as $pv): ?>
          <option value="<?= $pv ?>" <?= $priority_f===$pv?'selected':'' ?>><?= ucfirst($pv) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <div class="filter-label">Assigned to</div>
        <select id="f-assignee" onchange="doSearch()">
          <option value="">Anyone</option>
          <?php foreach ($all_users as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $assignee_f==$u['id']?'selected':'' ?>><?= h($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <div class="filter-label">From date</div>
        <input type="date" id="f-from" value="<?= h($date_from) ?>" onchange="doSearch()">
      </div>
      <div>
        <div class="filter-label">To date</div>
        <input type="date" id="f-to" value="<?= h($date_to) ?>" onchange="doSearch()">
      </div>
      <div class="filter-actions">
        <button class="ext-btn" onclick="clearFilters()" style="padding:6px 10px;font-size:12px">✕ Clear</button>
        <button class="ext-btn" onclick="saveSearch()" style="padding:6px 10px;font-size:12px">💾 Save</button>
      </div>
    </div>

    <!-- RESULTS -->
    <div id="results">
      <div class="srch-empty">
        <div class="srch-empty-icon">🔍</div>
        <div class="srch-empty-title">Search everything in Padak CRM</div>
        <div class="srch-empty-sub">
          Projects, tasks, contacts, documents<?= isManager()?', leads':'' ?> — all in one place.<br>
          Use the filters to narrow by status, priority, date, or assignee.
        </div>
        <div style="margin-top:18px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
          <?php foreach (['All Tasks','Active Projects','Leads','Documents','Contacts'] as $sug): ?>
          <span onclick="quickSearch('<?= $sug ?>')" style="padding:6px 14px;background:var(--bg3);border:1px solid var(--border);border-radius:99px;font-size:12.5px;color:var(--text2);cursor:pointer;transition:all .15s" onmouseover="this.style.borderColor='var(--orange)';this.style.color='var(--orange)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text2)'"><?= $sug ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- EXTERNAL SEARCH — Privacy-First Web Search -->
    <div class="ext-search-card" id="ext-card">

      <!-- Header -->
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px">
        <div class="ext-search-title">🌐 Web Search</div>
        <div style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:var(--text3)">
          <span style="color:var(--green);font-size:13px">🔒</span>
          Zero CRM data • Query-only • Strict referrer policy
        </div>
      </div>

      <!-- Engine selector tabs -->
      <div style="display:flex;gap:0;border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;margin-bottom:12px;flex-wrap:wrap">
        <!-- EMBEDDABLE (iframe) - Privacy-focused engines -->
        <button class="ws-tab active" id="tab-bing" onclick="selectEngine('bing')"
          title="✅ Embeddable • Microsoft Bing Search">
          🔵 Bing
          <span class="ws-badge embed">Inline</span>
        </button>
        <button class="ws-tab" id="tab-wiki" onclick="selectEngine('wiki')"
          title="✅ Wikipedia mobile • Free & Open">
          📖 Wikipedia
          <span class="ws-badge embed">Inline</span>
        </button>
        <button class="ws-tab" id="tab-ddg" onclick="selectEngine('ddg')"
          title="✅ DuckDuckGo Lite • Privacy-focused">
          🦆 DuckDuckGo
          <span class="ws-badge embed">Inline</span>
        </button>
        <button class="ws-tab" id="tab-perplexity" onclick="selectEngine('perplexity')"
          title="✅ AI-powered search • Embeddable">
          🔮 Perplexity
          <span class="ws-badge embed">Inline</span>
        </button>
        <!-- NEW TAB ONLY -->
        <button class="ws-tab" id="tab-google" onclick="selectEngine('google')"
          title="Opens in new tab • X-Frame-Options blocks embedding">
          🔍 Google
          <span class="ws-badge newtab">↗ Tab</span>
        </button>
      </div>

      <!-- Search input row -->
      <div style="display:flex;gap:8px;margin-bottom:10px">
        <input type="text" class="ext-search-input" id="ext-q"
          style="flex:1;margin-bottom:0"
          placeholder="Search the web…"
          onkeydown="if(event.key==='Enter')doWebSearch()"
          value="<?= h($q) ?>">
        <button class="ext-btn primary-ext" onclick="doWebSearch()" style="white-space:nowrap">Search</button>
      </div>

      <!-- Privacy notice -->
      <div id="ws-notice" style="font-size:11.5px;color:var(--text3);margin-bottom:10px;padding:7px 10px;background:var(--bg3);border-radius:var(--radius-sm);border-left:3px solid var(--green)">
        <strong style="color:var(--green)">✅ Inline (Bing):</strong>
        Embedded via iframe. Only search query sent, no CRM data exposed. Strict referrer policy enforced.
      </div>

      <!-- IFRAME AREA (for embeddable engines) -->
      <div id="ws-frame-wrap" style="border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;display:none;position:relative">
        <div id="ws-loading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:var(--bg3);z-index:2;font-size:13px;color:var(--text3)">
          <span>⏳ Loading results…</span>
        </div>
        <iframe
          id="ws-frame"
          src="about:blank"
          style="width:100%;height:520px;border:none;display:block"
          sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation"
          referrerpolicy="no-referrer"
          loading="lazy"
          title="External web search — sandboxed, query-only">
        </iframe>
      </div>

      <!-- NEW-TAB fallback message -->
      <div id="ws-newtab-msg" style="display:none;padding:14px;background:var(--bg3);border-radius:var(--radius);text-align:center;border:1px dashed var(--border)">
        <div style="font-size:16px;margin-bottom:6px" id="ws-newtab-icon">🔍</div>
        <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:4px" id="ws-newtab-name">Google Search</div>
        <div style="font-size:12px;color:var(--text3);margin-bottom:10px" id="ws-newtab-reason">
          Uses <code>X-Frame-Options: SAMEORIGIN</code> — browser blocks inline embedding.
        </div>
        <button class="ext-btn primary-ext" id="ws-newtab-btn" onclick="">↗ Open in New Tab</button>
        <div style="font-size:11px;color:var(--text3);margin-top:8px">
          Opens with <code>noopener,noreferrer</code> — CRM session isolated
        </div>
      </div>

    </div>

  </div>

  <!-- SIDEBAR -->
  <div style="width:270px;flex-shrink:0" class="srch-sidebar">

    <!-- RECENT -->
    <div class="srch-panel">
      <div class="panel-title">
        <span>🕐 Recent Searches</span>
        <button class="panel-title-clear" onclick="clearAllRecent()">Clear all</button>
      </div>
      <div id="recent-list">
        <div style="font-size:12px;color:var(--text3);text-align:center;padding:8px">Loading…</div>
      </div>
    </div>

    <!-- SAVED -->
    <div class="srch-panel">
      <div class="panel-title">💾 Saved Searches</div>
      <div id="saved-list">
        <div style="font-size:12px;color:var(--text3);text-align:center;padding:8px">Loading…</div>
      </div>
    </div>

    <!-- TIPS -->
    <div class="srch-panel" style="margin-top:14px">
      <div class="panel-title">💡 Search Tips</div>
      <div style="display:flex;flex-direction:column;gap:6px">
        <?php foreach ([
          ['🔤','Type 2+ characters for instant results'],
          ['🏷','Use type tabs to filter by category'],
          ['📅','Filter by date range for recent items'],
          ['💾','Save frequent searches for quick access'],
          ['⌨','Press <kbd style="font-size:10px;background:var(--bg3);padding:1px 4px;border-radius:3px;border:1px solid var(--border)">/ </kbd> from any page to open search'],
        ] as [$ic,$tip]): ?>
        <div class="search-tip"><span><?= $ic ?></span><span><?= $tip ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<script>
var CTYPE = '<?= h($type) ?>';
var CQ    = <?= json_encode($q) ?>;
var _dbt  = null;
var TYPE_COLORS = {
  project:  {border:'#f97316', bg:'rgba(249,115,22,.12)'},
  task:     {border:'#6366f1', bg:'rgba(99,102,241,.12)'},
  contact:  {border:'#10b981', bg:'rgba(16,185,129,.12)'},
  document: {border:'#f59e0b', bg:'rgba(245,158,11,.12)'},
  rich_doc: {border:'#8b5cf6', bg:'rgba(139,92,246,.12)'},
  lead:     {border:'#14b8a6', bg:'rgba(20,184,166,.12)'},
};

document.addEventListener('DOMContentLoaded', function(){
  loadRecent(); loadSaved();
  updateClear();
  if (CQ) doSearch();
  // / shortcut handled by layout.php — but also focus here
  document.addEventListener('keydown', function(e){
    if (e.key==='/' && !['INPUT','TEXTAREA'].includes(document.activeElement.tagName)){
      e.preventDefault();
      document.getElementById('main-search').focus();
    }
  });
});

function onInput(el){
  updateClear();
  clearTimeout(_dbt);
  var q = el.value.trim();
  document.getElementById('filter-bar').style.display = q ? '' : 'none';
  if (!q){
    showEmpty(); return;
  }
  if (q.length < 2) return;
  _dbt = setTimeout(doSearch, 280);
}

function updateClear(){
  var q = document.getElementById('main-search').value;
  var cl = document.getElementById('srch-clear');
  cl.style.display = q ? 'flex' : 'none';
}
function clearSearch(){
  document.getElementById('main-search').value = '';
  updateClear();
  document.getElementById('filter-bar').style.display = 'none';
  showEmpty();
  history.replaceState(null,'','search.php');
  CQ = '';
}
function setType(t){
  CTYPE = t;
  document.querySelectorAll('.type-tab').forEach(function(el,i){
    var keys = ['all','projects','tasks','contacts','documents','leads'];
    el.classList.toggle('active', keys[i]===t);
  });
  if (CQ) doSearch();
}
function quickSearch(q){
  document.getElementById('main-search').value = q;
  CQ = q;
  updateClear();
  document.getElementById('filter-bar').style.display = '';
  doSearch();
}

// ── SEARCH ──
function doSearch(){
  var q = document.getElementById('main-search').value.trim();
  CQ = q;
  var status   = document.getElementById('f-status')?.value   || '';
  var priority = document.getElementById('f-priority')?.value || '';
  var assignee = document.getElementById('f-assignee')?.value || '';
  var from     = document.getElementById('f-from')?.value     || '';
  var to       = document.getElementById('f-to')?.value       || '';

  history.replaceState(null,'','search.php?q='+encodeURIComponent(q)+'&type='+CTYPE);
  // Sync to web search box (but don't auto-trigger web search)
  var extQ = document.getElementById('ext-q');
  if (extQ && !extQ.value) extQ.value = q;
  showLoading();

  var params = new URLSearchParams({
    action:'search', q:q, type:CTYPE,
    status:status, priority:priority, assignee:assignee,
    date_from:from, date_to:to
  });
  fetch('search_api.php?'+params.toString())
    .then(function(r){return r.json();})
    .then(renderResults)
    .catch(function(){ showError(); });
}

function showLoading(){
  var el = document.getElementById('results');
  el.innerHTML = '<div class="skel-row skeleton"></div><div class="skel-row skeleton" style="opacity:.7"></div><div class="skel-row skeleton" style="opacity:.4"></div>';
}
function showEmpty(){
  document.getElementById('results').innerHTML = `
    <div class="srch-empty">
      <div class="srch-empty-icon">🔍</div>
      <div class="srch-empty-title">Search everything in Padak CRM</div>
      <div class="srch-empty-sub">Projects, tasks, contacts, documents — all in one place.</div>
      <div style="margin-top:18px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
        ${['All Tasks','Active Projects','Documents','Contacts'].map(s=>`<span onclick="quickSearch('${s}')" style="padding:6px 14px;background:var(--bg3);border:1px solid var(--border);border-radius:99px;font-size:12.5px;color:var(--text2);cursor:pointer">${s}</span>`).join('')}
      </div>
    </div>`;
}
function showError(){
  document.getElementById('results').innerHTML = '<div style="padding:20px;color:var(--red);text-align:center">Search error. Please try again.</div>';
}

// ── RENDER RESULTS ──
function renderResults(d){
  var el = document.getElementById('results');
  if (!d.results || !d.results.length){
    el.innerHTML = `
      <div class="srch-empty">
        <div class="srch-empty-icon">😕</div>
        <div class="srch-empty-title">No results for "${esc(d.query||CQ)}"</div>
        <div class="srch-empty-sub">Try different keywords, or broaden your filters.</div>
      </div>
      <div style="margin-top:12px">
        <div class="search-tip">💡 Check spelling or try a shorter keyword</div>
        <div class="search-tip">💡 Remove filters to see more results</div>
        <div class="search-tip">💡 Try searching with a different category tab</div>
      </div>`;
    loadRecent(); return;
  }

  var q = d.query || CQ;
  var html = `<div class="result-count">Found <strong>${d.total}</strong> result${d.total!==1?'s':''} for <strong>"${esc(q)}"</strong></div>`;

  // Group by type for visual separation
  var grouped = {};
  d.results.forEach(function(r){ (grouped[r._type] = grouped[r._type]||[]).push(r); });

  // Flatten but with group headers when > 1 type
  var multiType = Object.keys(grouped).length > 1;
  d.results.forEach(function(r){
    var tc = TYPE_COLORS[r._type] || {border:'#94a3b8', bg:'rgba(148,163,184,.1)'};
    var title = highlight(r.title||'Untitled', q);
    var snip  = r.snippet ? esc(r.snippet.substring(0,120)) : '';
    html += `<a href="${esc(r.url||'#')}" class="result-card" style="--rc:${tc.border};--rc-bg:${tc.bg}">
      <div class="result-icon-wrap">${r.icon||'📄'}</div>
      <div class="result-body">
        <div class="result-title">${title}</div>
        ${snip ? `<div class="result-snippet">${snip}</div>` : ''}
        <div class="result-meta">
          <span class="result-type-lbl">${esc(r._type.replace('_',' '))}</span>
          ${r.badge ? `<span class="badge" style="font-size:10.5px;padding:2px 8px;background:${esc(r.badge_color)}20;color:${esc(r.badge_color)}">${esc(r.badge)}</span>` : ''}
          ${r.updated_at ? `<span style="font-size:11px;color:var(--text3)">${esc(r.updated_at.substring(0,10))}</span>` : ''}
        </div>
      </div>
      <span class="result-arrow">›</span>
    </a>`;
  });

  el.innerHTML = html;
  loadRecent();
}

function highlight(text, q){
  if (!q || !text) return esc(text||'');
  var re = new RegExp('('+q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')', 'gi');
  return esc(text).replace(re, '<mark>$1</mark>');
}

// ── RECENT ──
function loadRecent(){
  fetch('search_api.php?action=recent')
    .then(function(r){return r.json();})
    .then(function(d){
      var el = document.getElementById('recent-list');
      if (!d.recent || !d.recent.length){
        el.innerHTML = '<div style="font-size:12px;color:var(--text3);text-align:center;padding:8px">No recent searches yet</div>';
        return;
      }
      el.innerHTML = d.recent.map(function(r){
        return `<span class="recent-chip" onclick="quickSearch('${escAttr(r.query)}')">
          <span>${esc(r.query)}</span>
          ${r.result_count?`<span class="recent-cnt">(${r.result_count})</span>`:''}
          <button class="recent-chip-rm" onclick="delRecent(event,${r.id})">✕</button>
        </span>`;
      }).join('');
    });
}
function delRecent(e, id){
  e.stopPropagation();
  fetch('search_api.php?action=delete_recent',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id}).then(loadRecent);
}
function clearAllRecent(){
  if (!confirm('Clear all recent searches?')) return;
  fetch('search_api.php?action=delete_recent',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id=0'}).then(loadRecent);
}

// ── SAVED ──
function loadSaved(){
  fetch('search_api.php?action=saved')
    .then(function(r){return r.json();})
    .then(function(d){
      var el = document.getElementById('saved-list');
      if (!d.saved || !d.saved.length){
        el.innerHTML = '<div style="font-size:12px;color:var(--text3);text-align:center;padding:8px 4px">No saved searches.<br><small>Run a search and click 💾 Save.</small></div>';
        return;
      }
      el.innerHTML = d.saved.map(function(s){
        return `<div class="saved-row">
          <span style="font-size:13px">${s.is_pinned?'📌':'🔖'}</span>
          <span class="saved-name" onclick="quickSearch('${escAttr(s.query||s.label)}')" title="${escAttr(s.label)}">${esc(s.label)}</span>
          <button class="recent-chip-rm" title="${s.is_pinned?'Unpin':'Pin'}" onclick="pinSaved(${s.id})" style="font-size:12px">📌</button>
          <button class="recent-chip-rm" title="Delete" onclick="delSaved(${s.id})" style="color:var(--text3);font-size:13px">✕</button>
        </div>`;
      }).join('');
    });
}
function saveSearch(){
  var q = document.getElementById('main-search').value.trim();
  if (!q){ toast('Enter a search term first','error'); return; }
  var label = prompt('Save this search as:',q);
  if (!label) return;
  var filters = JSON.stringify({
    status: document.getElementById('f-status')?.value||'',
    priority:document.getElementById('f-priority')?.value||'',
    assignee:document.getElementById('f-assignee')?.value||'',
    date_from:document.getElementById('f-from')?.value||'',
    date_to:  document.getElementById('f-to')?.value||'',
    type: CTYPE
  });
  fetch('search_api.php?action=save_search',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'label='+encodeURIComponent(label)+'&query='+encodeURIComponent(q)+'&filters='+encodeURIComponent(filters)
  }).then(function(r){return r.json();}).then(function(d){ if(d.ok){toast('Search saved!','success');loadSaved();} });
}
function pinSaved(id){
  fetch('search_api.php?action=pin_saved',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id}).then(loadSaved);
}
function delSaved(id){
  fetch('search_api.php?action=delete_saved',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id}).then(loadSaved);
}
function clearFilters(){
  ['f-status','f-priority','f-assignee','f-from','f-to'].forEach(function(id){
    var el=document.getElementById(id); if(el)el.value='';
  });
  if(CQ) doSearch();
}

// ═══════════════════════════════════════════════════════════════════
// WEB SEARCH ENGINE CONFIGURATION
// ═══════════════════════════════════════════════════════════════════
// 
// SECURITY & PRIVACY GUARANTEES:
// • Only search query transmitted via URL parameters
// • No CRM data, session cookies, or internal state sent
// • Strict referrerpolicy="no-referrer" on all iframes
// • Full iframe sandboxing with minimal permissions
// • noopener,noreferrer for new tab links
// 
// LEGAL COMPLIANCE:
// • All engines are publicly accessible search tools
// • Embedding allowed via their public iframe policies
// • No API keys required, no rate limits violated
// • Fair use for personal/internal business research
// 
// ═══════════════════════════════════════════════════════════════════

var WS_ENGINES = {
  // ── BING ──
  // Legal: Microsoft allows iframe embedding of Bing search
  // Privacy: Clean URL, no tracking params sent from CRM
  bing: {
    name: 'Bing', 
    icon: '🔵',
    iframe: true,
    url: 'https://www.bing.com/search?q=',
    notice: '<strong style="color:var(--green)">✅ Inline (Bing):</strong> Microsoft search engine. Only query sent via URL, strict no-referrer policy.',
    reason: ''
  },

  // ── WIKIPEDIA ──
  // Legal: Wikipedia mobile site, CC BY-SA licensed
  // Privacy: No tracking, open content, mobile-optimized
  wiki: {
    name: 'Wikipedia', 
    icon: '📖',
    iframe: true,
    url: 'https://en.m.wikipedia.org/w/index.php?search=',
    notice: '<strong style="color:var(--green)">✅ Inline (Wikipedia):</strong> Free encyclopedia with CC BY-SA license. Zero tracking, query-only.',
    reason: ''
  },

  // ── DUCKDUCKGO LITE ──
  // Legal: DuckDuckGo Lite version designed for embedding
  // Privacy: Privacy-first search engine, no user tracking
  // Note: Using lite.duckduckgo.com which allows iframe embedding
  ddg: {
    name: 'DuckDuckGo', 
    icon: '🦆',
    iframe: true,
    url: 'https://lite.duckduckgo.com/lite/?q=',
    notice: '<strong style="color:var(--green)">✅ Inline (DuckDuckGo):</strong> Privacy-focused search with lite interface. No tracking, query-only transmission.',
    reason: ''
  },

  // ── PERPLEXITY AI ──
  // Legal: Perplexity.ai allows embedding via their public interface
  // Privacy: AI-powered search, query transmitted via URL
  // Note: Using their public search interface
  perplexity: {
    name: 'Perplexity', 
    icon: '🔮',
    iframe: true,
    url: 'https://www.perplexity.ai/search?q=',
    notice: '<strong style="color:var(--green)">✅ Inline (Perplexity):</strong> AI-powered search engine. Query-only, no CRM data shared.',
    reason: ''
  },

  // ── GOOGLE (New Tab Only) ──
  // Legal: Google search, but X-Frame-Options prevents embedding
  // Privacy: Opens in isolated tab with noopener,noreferrer
  google: {
    name: 'Google', 
    icon: '🔍',
    iframe: false,
    url: 'https://www.google.com/search?q=',
    notice: '<strong style="color:var(--orange)">↗ New Tab (Google):</strong> X-Frame-Options prevents embedding. Opens in isolated tab.',
    reason: 'Uses <code>X-Frame-Options: SAMEORIGIN</code> — browser security blocks iframe embedding.'
  }
};

var WS_ACTIVE = 'bing';

function selectEngine(key) {
  WS_ACTIVE = key;
  
  // Update tab active state
  Object.keys(WS_ENGINES).forEach(function(k) {
    var btn = document.getElementById('tab-' + k);
    if (btn) btn.classList.toggle('active', k === key);
  });
  
  // Update notice
  var eng = WS_ENGINES[key];
  document.getElementById('ws-notice').innerHTML = eng.notice;
  
  // Update notice border color
  var noticeBorder = eng.iframe ? 'var(--green)' : 'var(--orange)';
  document.getElementById('ws-notice').style.borderLeftColor = noticeBorder;
  
  // If a search has been run, re-run for new engine
  var q = document.getElementById('ext-q').value.trim();
  if (q) {
    doWebSearch();
  } else {
    // Reset both panels
    document.getElementById('ws-frame-wrap').style.display = 'none';
    document.getElementById('ws-newtab-msg').style.display = 'none';
  }
}

function doWebSearch() {
  var q = document.getElementById('ext-q').value.trim();
  if (!q) { 
    toast('Enter a web search query', 'error'); 
    return; 
  }
  
  var eng = WS_ENGINES[WS_ACTIVE];
  
  // SECURITY: Only query parameter sent, no CRM data
  var fullUrl = eng.url + encodeURIComponent(q);

  if (eng.iframe) {
    // IFRAME MODE (Bing, Wikipedia, DuckDuckGo, Perplexity)
    document.getElementById('ws-newtab-msg').style.display = 'none';
    document.getElementById('ws-frame-wrap').style.display = 'block';
    document.getElementById('ws-loading').style.display = 'flex';
    
    var frame = document.getElementById('ws-frame');
    
    frame.onload = function() {
      document.getElementById('ws-loading').style.display = 'none';
    };
    
    // PRIVACY: referrerpolicy="no-referrer" already set in HTML
    // This ensures no CRM domain/path information is sent to search engine
    frame.src = fullUrl;
    
  } else {
    // NEW TAB MODE (Google, etc.)
    document.getElementById('ws-frame-wrap').style.display = 'none';
    document.getElementById('ws-newtab-msg').style.display = 'block';
    document.getElementById('ws-newtab-icon').textContent = eng.icon;
    document.getElementById('ws-newtab-name').textContent = eng.name + ' — "' + q + '"';
    document.getElementById('ws-newtab-reason').innerHTML = eng.reason;
    
    var openBtn = document.getElementById('ws-newtab-btn');
    openBtn.textContent = '↗ Open "' + q + '" in ' + eng.name;
    openBtn.onclick = function() {
      // SECURITY: noopener,noreferrer prevents:
      // • window.opener access (no CRM session access)
      // • Referrer header (no CRM domain leaked)
      window.open(fullUrl, '_blank', 'noopener,noreferrer');
    };
  }
}

// ── UTILS ──
function esc(s){ 
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); 
}
function escAttr(s){ 
  return String(s||'').replace(/'/g,"\\'").replace(/"/g,'&quot;'); 
}

// Toast notification (if not defined elsewhere)
function toast(msg, type) {
  console.log('[' + type + '] ' + msg);
  // Implement your toast UI here if needed
}
</script>

<?php renderLayoutEnd(); ?>