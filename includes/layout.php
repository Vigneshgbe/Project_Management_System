<?php
// includes/layout.php — Shared nav/header/sidebar
function renderLayout(string $pageTitle, string $activePage): void {
    $user = currentUser();
    $initials = implode('', array_map(fn($w) => strtoupper($w[0]), explode(' ', $user['name'])));
    $initials = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> — Padak CRM</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Bricolage+Grotesque:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

/* DARK THEME (default) */
:root,[data-theme="dark"]{
  --bg:#0f1117;--bg2:#161b27;--bg3:#1e2538;--bg4:#252d40;
  --border:#2a3348;--border2:#3a4560;
  --text:#e8eaf0;--text2:#9aa3b8;--text3:#5a6478;
  --orange:#f97316;--orange-dim:#7c3810;--orange-bg:rgba(249,115,22,0.1);
  --green:#10b981;--red:#ef4444;--blue:#6366f1;--yellow:#f59e0b;--purple:#8b5cf6;
  --radius:10px;--radius-sm:6px;--radius-lg:16px;
  --shadow:0 1px 4px rgba(0,0,0,.4);--shadow-lg:0 8px 30px rgba(0,0,0,.5);
  --sidebar-w:240px;--header-h:58px;
  --font:'Plus Jakarta Sans',sans-serif;--font-display:'Bricolage Grotesque',sans-serif;
  --modal-overlay:rgba(0,0,0,.65);
  --tr-hover:rgba(255,255,255,.02);
}

/* LIGHT THEME */
[data-theme="light"]{
  --bg:#f0f2f7;--bg2:#ffffff;--bg3:#f5f6fa;--bg4:#eaecf2;
  --border:#dde1ec;--border2:#c8cdde;
  --text:#111827;--text2:#4b5563;--text3:#9ca3af;
  --orange:#f97316;--orange-dim:#fed7aa;--orange-bg:rgba(249,115,22,0.08);
  --green:#059669;--red:#dc2626;--blue:#4f46e5;--yellow:#d97706;--purple:#7c3aed;
  --shadow:0 1px 4px rgba(0,0,0,.08);--shadow-lg:0 8px 30px rgba(0,0,0,.12);
  --modal-overlay:rgba(0,0,0,.45);
  --tr-hover:rgba(0,0,0,.02);
}

html{font-size:14px;scroll-behavior:smooth}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow-x:hidden;transition:background .2s,color .2s}
a{color:inherit;text-decoration:none}
button{cursor:pointer;font-family:var(--font)}
input,select,textarea{font-family:var(--font)}
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:var(--bg2)}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:99px}

/* SIDEBAR */
#sidebar{
  width:var(--sidebar-w);min-width:var(--sidebar-w);height:100vh;
  background:var(--bg2);border-right:1px solid var(--border);
  display:flex;flex-direction:column;position:fixed;left:0;top:0;z-index:100;
  transition:transform .25s ease,background .2s,border-color .2s
}
.sidebar-logo{padding:18px 20px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.logo-mark{width:34px;height:34px;background:var(--orange);border-radius:8px;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:700;font-size:16px;color:#fff;flex-shrink:0}
.logo-text{font-family:var(--font-display);font-weight:700;font-size:17px;color:var(--text)}
.logo-text span{color:var(--orange)}
.nav-section{padding:14px 12px 6px;flex:1;overflow-y:auto}
.nav-label{font-size:10px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.08em;padding:0 8px;margin-bottom:4px}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:var(--radius-sm);color:var(--text2);font-size:13.5px;font-weight:500;transition:background .15s,color .15s;margin-bottom:1px;white-space:nowrap}
.nav-item:hover{background:var(--bg3);color:var(--text)}
.nav-item.active{background:var(--orange-bg);color:var(--orange);font-weight:600}
.nav-item .icon{width:18px;text-align:center;flex-shrink:0;font-size:15px}
.nav-badge{margin-left:auto;background:var(--orange);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:99px}
.sidebar-footer{padding:12px;border-top:1px solid var(--border)}
.user-card{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:var(--radius-sm);cursor:pointer;transition:background .15s}
.user-card:hover{background:var(--bg3)}
.avatar{width:32px;height:32px;border-radius:50%;background:var(--orange);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;overflow:hidden}
.avatar img{width:100%;height:100%;object-fit:cover}
.user-info{flex:1;min-width:0}
.user-name{font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.user-role{font-size:11px;color:var(--text3);text-transform:capitalize}

/* MAIN */
#main{margin-left:var(--sidebar-w);flex:1;min-height:100vh;display:flex;flex-direction:column}

/* HEADER */
#header{height:var(--header-h);background:var(--bg2);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 24px;gap:12px;position:sticky;top:0;z-index:50;transition:background .2s,border-color .2s}
.header-title{font-family:var(--font-display);font-weight:700;font-size:18px;flex:1}
.header-title span{color:var(--text3);font-weight:400;font-size:14px;margin-left:8px}

/* THEME TOGGLE */
#theme-toggle{width:36px;height:36px;border-radius:var(--radius-sm);background:var(--bg3);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;flex-shrink:0;transition:background .15s,border-color .15s,transform .1s;color:var(--text2)}
#theme-toggle:hover{background:var(--bg4);color:var(--text);border-color:var(--border2)}
#theme-toggle:active{transform:scale(.92)}
[data-theme="light"] #theme-toggle .icon-dark{display:none}
[data-theme="light"] #theme-toggle .icon-light{display:flex}
[data-theme="dark"] #theme-toggle .icon-dark{display:flex}
[data-theme="dark"] #theme-toggle .icon-light{display:none}

.btn{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:var(--radius-sm);font-size:13px;font-weight:600;border:none;transition:opacity .15s,transform .1s;cursor:pointer}
.btn:active{transform:scale(.97)}
.btn-primary{background:var(--orange);color:#fff}
.btn-primary:hover{opacity:.9}
.btn-ghost{background:var(--bg3);color:var(--text2);border:1px solid var(--border)}
.btn-ghost:hover{background:var(--bg4);color:var(--text)}
.btn-danger{background:rgba(239,68,68,.12);color:var(--red);border:1px solid rgba(239,68,68,.2)}
.btn-danger:hover{background:rgba(239,68,68,.22)}
.btn-sm{padding:5px 12px;font-size:12px}
.btn-icon{padding:7px;gap:0}
#hamburger{display:none;background:none;border:none;color:var(--text);font-size:20px;padding:4px;cursor:pointer}

/* GLOBAL SEARCH BAR */
#global-search-wrap{position:relative;flex:1;max-width:380px}
#global-search{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:7px 36px 7px 34px;color:var(--text);font-size:13px;font-family:var(--font);transition:border-color .15s;outline:none}
#global-search:focus{border-color:var(--orange)}
#global-search::placeholder{color:var(--text3)}
.gs-icon{position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:13px;color:var(--text3);pointer-events:none}
#gs-kbd{position:absolute;right:8px;top:50%;transform:translateY(-50%);font-size:10px;background:var(--bg4);border:1px solid var(--border);border-radius:3px;padding:1px 5px;color:var(--text3)}
#gs-dropdown{position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-lg);z-index:500;display:none;max-height:400px;overflow-y:auto}
#gs-dropdown.open{display:block}
.gs-item{display:flex;align-items:center;gap:10px;padding:9px 14px;cursor:pointer;transition:background .1s;text-decoration:none;color:inherit}
.gs-item:hover,.gs-item.selected{background:var(--bg3)}
.gs-item-icon{font-size:16px;flex-shrink:0;width:26px;text-align:center}
.gs-item-label{flex:1;min-width:0;font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gs-item-sub{font-size:11.5px;color:var(--text3);white-space:nowrap}
.gs-footer{padding:8px 14px;border-top:1px solid var(--border);font-size:11.5px;color:var(--text3);text-align:center;cursor:pointer}
.gs-footer:hover{color:var(--orange)}
.gs-section-lbl{padding:5px 14px 3px;font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;background:var(--bg3)}
@media(max-width:900px){#global-search-wrap{max-width:200px}}
@media(max-width:600px){#global-search-wrap{display:none}}

/* CONTENT */
#content{padding:24px;flex:1}

/* CARDS */
.card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;transition:background .2s,border-color .2s}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;gap:12px}
.card-title{font-family:var(--font-display);font-weight:700;font-size:16px}

/* STAT CARDS */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:22px}
.stat-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:18px;display:flex;align-items:center;gap:14px;transition:background .2s,border-color .2s}
.stat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.stat-val{font-family:var(--font-display);font-weight:700;font-size:26px;line-height:1}
.stat-lbl{font-size:12px;color:var(--text2);margin-top:3px}

/* TABLE */
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
table{width:100%;border-collapse:collapse}
th{text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);padding:10px 14px;border-bottom:1px solid var(--border);white-space:nowrap}
td{padding:11px 14px;border-bottom:1px solid var(--border);font-size:13.5px;color:var(--text2);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:var(--tr-hover)}
.td-main{color:var(--text);font-weight:500}

/* BADGE */
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:600;text-transform:capitalize}

/* FORM */
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em}
.form-control{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:9px 12px;color:var(--text);font-size:13.5px;transition:border-color .15s,background .2s}
.form-control:focus{outline:none;border-color:var(--orange)}
.form-control::placeholder{color:var(--text3)}
textarea.form-control{resize:vertical;min-height:90px}
select.form-control{cursor:pointer}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}

/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:var(--modal-overlay);z-index:1000;align-items:center;justify-content:center;padding:20px}
.modal-overlay.open{display:flex}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);width:100%;max-width:580px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-lg);transition:background .2s}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg2)}
.modal-title{font-family:var(--font-display);font-weight:700;font-size:16px}
.modal-body{padding:20px}
.modal-footer{padding:16px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px}
.modal-close{background:none;border:none;color:var(--text3);font-size:20px;cursor:pointer;line-height:1;padding:2px}
.modal-close:hover{color:var(--text)}

/* EMPTY STATE */
.empty-state{text-align:center;padding:48px 20px;color:var(--text3)}
.empty-state .icon{font-size:40px;margin-bottom:12px}
.empty-state p{font-size:13px}

/* PROGRESS BAR */
.progress-bar{height:6px;background:var(--bg4);border-radius:99px;overflow:hidden}
.progress-fill{height:100%;border-radius:99px;background:var(--orange);transition:width .3s}

/* AVATAR GROUP */
.avatar-group{display:flex;align-items:center}
.avatar-group .avatar{margin-left:-6px;border:2px solid var(--bg2);width:26px;height:26px;font-size:10px}
.avatar-group .avatar:first-child{margin-left:0}

/* TOAST */
#toast-container{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px}
.toast{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px;font-size:13px;color:var(--text);box-shadow:var(--shadow-lg);display:flex;align-items:center;gap:10px;min-width:260px;max-width:360px;animation:toastIn .2s ease}
.toast.success{border-left:3px solid var(--green)}
.toast.error{border-left:3px solid var(--red)}
.toast.info{border-left:3px solid var(--blue)}
@keyframes toastIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}

/* SEARCH INPUT */
.search-box{display:flex;align-items:center;gap:8px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:7px 12px;min-width:220px}
.search-box input{background:none;border:none;color:var(--text);font-size:13px;width:100%}
.search-box input:focus{outline:none}
.search-box input::placeholder{color:var(--text3)}

/* DROPDOWN */
.dropdown{position:relative;display:inline-block}
.dropdown-menu{display:none;position:absolute;right:0;top:calc(100% + 6px);background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);min-width:160px;box-shadow:var(--shadow-lg);z-index:200}
.dropdown-menu.open{display:block}
.dropdown-item{display:block;padding:9px 14px;font-size:13px;color:var(--text2);transition:background .1s;cursor:pointer}
.dropdown-item:hover{background:var(--bg4);color:var(--text)}
.dropdown-item.danger{color:var(--red)}
.dropdown-divider{height:1px;background:var(--border);margin:4px 0}

/* PRIORITY DOT */
.priority-dot{width:8px;height:8px;border-radius:50%;display:inline-block;flex-shrink:0}

/* SIDEBAR OVERLAY MOBILE */
#sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99}

/* RESPONSIVE */
@media(max-width:900px){
  :root{--sidebar-w:240px}
  #sidebar{transform:translateX(-100%)}
  #sidebar.open{transform:translateX(0)}
  #sidebar-overlay.open{display:block}
  #main{margin-left:0}
  #hamburger{display:block}
  .form-row,.form-row-3{grid-template-columns:1fr}
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  #content{padding:16px}
}
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr 1fr}
  .btn span{display:none}
  .header-title span{display:none}
}
</style>
<script>(function(){var t=localStorage.getItem('padak_theme')||'dark';document.documentElement.setAttribute('data-theme',t)})();</script>
</head>
<body>
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

<nav id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">P</div>
    <div class="logo-text">Padak <span>CRM</span></div>
  </div>
  <div class="nav-section">
    <div class="nav-label">Main</div>
    <a href="search.php" class="nav-item <?= $activePage==='search'?'active':'' ?>">
      <span class="icon">🔍</span> Search
    </a>
    <a href="dashboard.php" class="nav-item <?= $activePage==='dashboard'?'active':'' ?>">
      <span class="icon">⬛</span> Dashboard
    </a>
    <a href="projects.php" class="nav-item <?= $activePage==='projects'?'active':'' ?>">
      <span class="icon">📁</span> Projects
    </a>
    <a href="tasks.php" class="nav-item <?= $activePage==='tasks'?'active':'' ?>">
      <span class="icon">✅</span> Tasks
    </a>
    <a href="calendar.php" class="nav-item <?= $activePage==='calendar'?'active':'' ?>">
      <span class="icon">📅</span> Calendar
    </a>
    <a href="chat.php" class="nav-item <?= $activePage==='chat'?'active':'' ?>">
      <span class="icon">💬</span> Chat
    </a>
    <div class="nav-label" style="margin-top:12px">Resources</div>
    <a href="documents.php" class="nav-item <?= $activePage==='documents'?'active':'' ?>">
      <span class="icon">📄</span> Documents
    </a>
    <a href="contacts.php" class="nav-item <?= $activePage==='contacts'?'active':'' ?>">
      <span class="icon">👥</span> Contacts
    </a>
    <?php if (isManager()): ?>
    <div class="nav-label" style="margin-top:12px">Business</div>
    <a href="leads.php" class="nav-item <?= $activePage==='leads'?'active':'' ?>">
      <span class="icon">🎯</span> Leads Pipeline
    </a>
    <a href="expenses.php" class="nav-item <?= $activePage==='expenses'?'active':'' ?>">
      <span class="icon">💰</span> Expenses
    </a>
    <div class="nav-label" style="margin-top:12px">Admin</div>
    <a href="analytics.php" class="nav-item <?= $activePage==='analytics'?'active':'' ?>">
      <span class="icon">📊</span> Analytics
    </a>
    <a href="users.php" class="nav-item <?= $activePage==='users'?'active':'' ?>">
      <span class="icon">👤</span> Team
    </a>
    <?php if (isAdmin()): ?>
    <a href="activity.php" class="nav-item <?= $activePage==='activity'?'active':'' ?>">
      <span class="icon">📋</span> Activity Log
    </a>
    <?php endif; ?>
    <?php endif; ?>
  </div>
  <div class="sidebar-footer">
    <a href="profile.php" class="user-card">
      <div class="avatar">
        <?php if ($user['avatar']): ?>
          <img src="uploads/avatars/<?= h($user['avatar']) ?>" alt="">
        <?php else: echo h($initials); endif; ?>
      </div>
      <div class="user-info">
        <div class="user-name"><?= h($user['name']) ?></div>
        <div class="user-role"><?= h($user['role']) ?></div>
      </div>
    </a>
    <a href="logout.php" style="display:flex;align-items:center;gap:8px;padding:8px 10px;color:var(--text3);font-size:12px;margin-top:4px;border-radius:var(--radius-sm);transition:background .15s" onmouseover="this.style.background='var(--bg3)'" onmouseout="this.style.background=''">
      <span>⬡</span> Sign out
    </a>
  </div>
</nav>

<div id="main">
  <header id="header">
    <button id="hamburger" onclick="toggleSidebar()">☰</button>
    <div class="header-title"><?= h($pageTitle) ?> <span><?= SITE_NAME ?></span></div>
    <!-- GLOBAL SEARCH -->
    <div id="global-search-wrap">
      <span class="gs-icon">🔍</span>
      <input type="text" id="global-search" placeholder="Search… (press /)" autocomplete="off"
        oninput="gsInput(this)" onkeydown="gsKey(event)">
      <span id="gs-kbd">/</span>
      <div id="gs-dropdown"></div>
    </div>
    <span style="font-size:12px;color:var(--text3)"><?= date('D, M j') ?></span>
    <button id="theme-toggle" onclick="toggleTheme()" title="Toggle dark/light mode" aria-label="Toggle theme">
      <span class="icon-dark">🌙</span>
      <span class="icon-light">☀️</span>
    </button>
  </header>
  <main id="content">
<?php
} // end renderLayout

function renderLayoutEnd(): void {
?>
  </main>
</div>

<div id="toast-container"></div>

<script>
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebar-overlay').classList.toggle('open');
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
}
function toggleTheme(){
  var html=document.documentElement;
  var next=html.getAttribute('data-theme')==='dark'?'light':'dark';
  html.setAttribute('data-theme',next);
  localStorage.setItem('padak_theme',next);
  document.dispatchEvent(new CustomEvent('themeChanged',{detail:{theme:next}}));
}
function openModal(id){document.getElementById(id).classList.add('open')}
function closeModal(id){document.getElementById(id).classList.remove('open')}
function toast(msg,type){
  type=type||'info';
  var t=document.createElement('div');
  t.className='toast '+type;
  var icons={success:'✓',error:'✕',info:'ℹ'};
  t.innerHTML='<span>'+(icons[type]||'ℹ')+'</span><span>'+msg+'</span>';
  document.getElementById('toast-container').appendChild(t);
  setTimeout(function(){t.style.opacity='0';t.style.transition='opacity .3s';setTimeout(function(){t.remove()},300)},3200);
}
document.addEventListener('click',function(e){
  if(!e.target.closest('.dropdown')) document.querySelectorAll('.dropdown-menu').forEach(function(m){m.classList.remove('open')});
});
function toggleDropdown(id){
  var m=document.getElementById(id);
  document.querySelectorAll('.dropdown-menu').forEach(function(x){if(x.id!==id)x.classList.remove('open')});
  m.classList.toggle('open');
}
document.querySelectorAll('.modal-overlay').forEach(function(o){
  o.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open')});
});

/* GLOBAL SEARCH */
var _gsDebounce=null, _gsIdx=-1, _gsSuggs=[];
function gsInput(el){
  clearTimeout(_gsDebounce);
  var q=el.value.trim();
  var dd=document.getElementById('gs-dropdown');
  document.getElementById('gs-kbd').style.display=q?'none':'block';
  if(!q){dd.classList.remove('open');return;}
  if(q.length<2){return;}
  _gsDebounce=setTimeout(function(){
    fetch('search_api.php?action=suggest&q='+encodeURIComponent(q))
      .then(function(r){return r.json();})
      .then(function(d){
        _gsSuggs=d.suggestions||[];
        _gsIdx=-1;
        renderGsDrop(q,_gsSuggs);
      }).catch(function(){});
  },220);
}
function renderGsDrop(q,suggs){
  var dd=document.getElementById('gs-dropdown');
  if(!suggs.length){dd.classList.remove('open');return;}
  var html='';
  var lastType='';
  suggs.forEach(function(s,i){
    if(s.type!==lastType){html+='<div class="gs-section-lbl">'+s.type+'s</div>';lastType=s.type;}
    html+='<a href="'+s.url+'" class="gs-item" data-idx="'+i+'">'
      +'<span class="gs-item-icon">'+s.icon+'</span>'
      +'<span class="gs-item-label">'+escGS(s.label)+'</span>'
      +'<span class="gs-item-sub">'+escGS(s.sub||'')+'</span>'
      +'</a>';
  });
  html+='<div class="gs-footer" onclick="location.href='search.php?q='+encodeURIComponent(document.getElementById('global-search').value)">See all results →</div>';
  dd.innerHTML=html;
  dd.classList.add('open');
}
function gsKey(e){
  var dd=document.getElementById('gs-dropdown');
  var items=dd.querySelectorAll('.gs-item');
  if(!dd.classList.contains('open')&&e.key==='Enter'){
    var q=document.getElementById('global-search').value.trim();
    if(q)location.href='search.php?q='+encodeURIComponent(q);
    return;
  }
  if(e.key==='ArrowDown'){e.preventDefault();_gsIdx=Math.min(_gsIdx+1,items.length-1);items.forEach(function(el,i){el.classList.toggle('selected',i===_gsIdx);});}
  else if(e.key==='ArrowUp'){e.preventDefault();_gsIdx=Math.max(_gsIdx-1,0);items.forEach(function(el,i){el.classList.toggle('selected',i===_gsIdx);});}
  else if(e.key==='Enter'){e.preventDefault();if(_gsIdx>=0&&items[_gsIdx]){location.href=items[_gsIdx].href;}else{var q=document.getElementById('global-search').value.trim();if(q)location.href='search.php?q='+encodeURIComponent(q);}}
  else if(e.key==='Escape'){dd.classList.remove('open');document.getElementById('global-search').blur();}
}
document.addEventListener('click',function(e){if(!e.target.closest('#global-search-wrap'))document.getElementById('gs-dropdown').classList.remove('open');});
document.addEventListener('keydown',function(e){if(e.key==='/'&&!['INPUT','TEXTAREA'].includes(document.activeElement.tagName)){e.preventDefault();document.getElementById('global-search').focus();}});
function escGS(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
<?php if (!empty($_SESSION['crm_flash'])): ?>
toast(<?= json_encode($_SESSION['crm_flash']['msg']) ?>,<?= json_encode($_SESSION['crm_flash']['type']) ?>);
<?php unset($_SESSION['crm_flash']); endif; ?>
</script>
</body>
</html>
<?php
} // end renderLayoutEnd

function flash(string $msg, string $type = 'info'): void {
    initSession();
    $_SESSION['crm_flash'] = ['msg' => $msg, 'type' => $type];
}