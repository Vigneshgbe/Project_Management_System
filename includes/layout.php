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
<link rel="icon" type="image/x-icon" href="https://thepadak.com/index_assets/padak_p.png">
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

/* ── NOTIFICATION BELL ── */
#notif-btn{position:relative;background:none;border:none;cursor:pointer;padding:7px 8px;border-radius:var(--radius-sm);color:var(--text2);font-size:20px;line-height:1;transition:color .15s,background .15s;flex-shrink:0}
#notif-btn:hover{background:var(--bg3);color:var(--text)}
#notif-badge{position:absolute;top:3px;right:3px;min-width:16px;height:16px;background:var(--red);color:#fff;font-size:9px;font-weight:800;border-radius:99px;display:none;align-items:center;justify-content:center;padding:0 3px;line-height:1;pointer-events:none}
#notif-badge.show{display:flex}
#notif-panel{position:absolute;top:calc(var(--header-h) - 4px);right:12px;width:380px;max-width:calc(100vw - 24px);background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:0 8px 32px rgba(0,0,0,.35);z-index:500;display:none;flex-direction:column;max-height:520px}
#notif-panel.open{display:flex}
.notif-panel-head{padding:14px 16px 10px;border-bottom:1px solid var(--border);flex-shrink:0}
.notif-panel-head-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.notif-panel-title{font-size:14px;font-weight:700;font-family:var(--font-display);color:var(--text)}
.notif-filters{display:flex;gap:4px;flex-wrap:wrap}
.nf-btn{padding:3px 9px;border-radius:99px;font-size:11px;font-weight:600;border:1px solid var(--border);background:none;cursor:pointer;color:var(--text3);transition:all .12s}
.nf-btn.active{background:var(--orange);border-color:var(--orange);color:#fff}
.nf-btn:hover:not(.active){background:var(--bg3);color:var(--text)}
.notif-list{flex:1;overflow-y:auto;padding:6px 0}
.notif-item{display:flex;align-items:flex-start;gap:10px;padding:10px 14px;cursor:pointer;transition:background .1s;position:relative;border-bottom:1px solid var(--border)}
.notif-item:last-child{border-bottom:none}
.notif-item:hover{background:var(--bg3)}
.notif-item.unread{background:var(--orange-bg)}
.notif-item.unread:hover{background:rgba(249,115,22,.15)}
.notif-icon{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;margin-top:1px}
.notif-body{flex:1;min-width:0}
.notif-title{font-size:12.5px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.notif-item.unread .notif-title{font-weight:700}
.notif-msg{font-size:11.5px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px}
.notif-age{font-size:10.5px;color:var(--text3);margin-top:2px;flex-shrink:0}
.notif-unread-dot{position:absolute;right:12px;top:50%;transform:translateY(-50%);width:7px;height:7px;background:var(--orange);border-radius:50%}
.notif-del{display:none;position:absolute;right:28px;top:50%;transform:translateY(-50%);background:var(--bg4);border:none;border-radius:4px;width:22px;height:22px;cursor:pointer;color:var(--text3);font-size:12px;align-items:center;justify-content:center}
.notif-item:hover .notif-del{display:flex}
.notif-item:hover .notif-unread-dot{display:none}
.notif-panel-foot{padding:10px 14px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.notif-empty{text-align:center;padding:40px 20px;color:var(--text3)}
.notif-empty-icon{font-size:32px;margin-bottom:8px}
.notif-loading{text-align:center;padding:24px;color:var(--text3);font-size:13px}
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
    <!-- MAIN -->
    <a href="search.php" class="nav-item <?= $activePage==='search'?'active':'' ?>">
        <span class="icon">🔍</span> Search
    </a>
    <a href="chatbot.php" class="nav-item <?= $activePage==='chatbot'?'active':'' ?>">
        <span class="icon">🤖</span> Chatbot
    </a>

   <?php if (isManager()): ?>
    <a href="dashboard.php" class="nav-item <?= $activePage==='dashboard'?'active':'' ?>">
        <span class="icon">⬛</span> Dashboard
    </a>
   <?php endif; ?>

    <a href="mywork.php" class="nav-item <?= $activePage==='mywork'?'active':'' ?>">
        <span class="icon">👤</span> My Work
    </a>

    <?php if (deptCan(['general','software_developer'])): ?>
    <a href="projects.php" class="nav-item <?= $activePage==='projects'?'active':'' ?>">
        <span class="icon">📁</span> Projects
    </a>
    <?php endif; ?>

    <a href="tasks.php" class="nav-item <?= $activePage==='tasks'?'active':'' ?>">
        <span class="icon">✅</span> Tasks
    </a>
    <a href="calendar.php" class="nav-item <?= $activePage==='calendar'?'active':'' ?>">
        <span class="icon">📅</span> Calendar
    </a>

    <a href="meetings.php" class="nav-item <?= $activePage==='meetings'?'active':'' ?>">
       <span class="icon">🤝</span> Meetings
    </a>

    <a href="chat.php" class="nav-item <?= $activePage==='chat'?'active':'' ?>">
        <span class="icon">💬</span> Chat
    </a>

    <!-- RESOURCES -->
    <?php if (deptCan(['general'])): // Emails: admin/manager/general member only ?>
    <a href="emails.php" class="nav-item <?= $activePage==='emails'?'active':'' ?>">
        <span class="icon">📧</span> Email Hub
    </a>
    <?php endif; ?>

    <?php if (deptCan(['general','digital_marketing'])): ?>
    <a href="email_template.php" class="nav-item <?= $activePage==='email_template'?'active':'' ?>">
        <span class="icon">📄</span> Email Template
    </a>
    <?php endif; ?>

    <a href="documents.php" class="nav-item <?= $activePage==='documents'?'active':'' ?>">
        <span class="icon">📁</span> Files Upload
    </a>
    <a href="rich_docs.php" class="nav-item <?= $activePage==='rich_docs'?'active':'' ?>">
        <span class="icon">✍</span> Rich Docs Edit
    </a>

    <?php if (deptCan(['general'])): ?>
    <a href="contacts.php" class="nav-item <?= $activePage==='contacts'?'active':'' ?>">
        <span class="icon">👥</span> Contacts
    </a>
    <?php endif; ?>

    <!-- BUSINESS -->
    <?php if (deptCan(['tele_caller','general'])): // Lead-focused pages ?>
    <a href="lead_generator.php" class="nav-item <?= $activePage==='lead_generator'?'active':'' ?>">
        <span class="icon">🔍</span> Lead Generator
    </a>
    <a href="lead_stored.php" class="nav-item <?= $activePage==='lead_stored'?'active':'' ?>">
        <span class="icon">📚</span> Stored Leads
    </a>
    <a href="leads.php" class="nav-item <?= $activePage==='leads'?'active':'' ?>">
        <span class="icon">🎯</span> Leads Pipeline
    </a>
    <a href="whatsapp.php" class="nav-item <?= $activePage==='whatsapp'?'active':'' ?>">
        <span class="icon">💬</span> WhatsApp
    </a>
    <?php endif; ?>

    <?php if (isManager()): ?>
    <a href="invoices.php" class="nav-item <?= $activePage==='invoices'?'active':'' ?>">
        <span class="icon">🧾</span> Invoices
    </a>
    <a href="expenses.php" class="nav-item <?= $activePage==='expenses'?'active':'' ?>">
        <span class="icon">💰</span> Expenses
    </a>
    <?php endif; ?>

    <?php if (isManager()): ?>
      <a href="payslip.php" class="nav-item <?= $activePage==='payslip'?'active':'' ?>">
          <span class="icon">💵</span> Payslips
      </a>
    <?php endif; ?>

    <?php if (deptCan(['digital_marketing','general'])): ?>
    <a href="social_media.php" class="nav-item <?= $activePage==='social_media'?'active':'' ?>">
        <span class="icon">📱</span> Social Media
    </a>
    <?php endif; ?>

    <!-- ADMIN -->
    <?php if (isManager()): ?>
    <a href="portal_admin.php" class="nav-item <?= $activePage==='portal_admin'?'active':'' ?>">
        <span class="icon">🌐</span> Client Portal
    </a>
    <a href="analytics.php" class="nav-item <?= $activePage==='analytics'?'active':'' ?>">
        <span class="icon">📊</span> Analytics
    </a>
    <a href="users.php" class="nav-item <?= $activePage==='users'?'active':'' ?>">
        <span class="icon">👤</span> Team
    </a>
    <a href="activity.php" class="nav-item <?= $activePage==='activity'?'active':'' ?>">
        <span class="icon">📋</span> Activity Log
    </a>
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
    <span style="font-size:12px;color:var(--text3)"><?= date('D, M j') ?></span>
    <a href="search.php" id="header-search-btn" title="Search (press /)" style="display:flex;align-items:center;gap:6px;padding:7px 12px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text3);font-size:12.5px;text-decoration:none;transition:border-color .15s,color .15s;flex-shrink:0" onmouseover="this.style.borderColor='var(--orange)';this.style.color='var(--orange)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text3)'">
      🔍 <span style="display:none" id="hs-text">Search</span>
      <kbd style="font-size:10px;background:var(--bg4);border:1px solid var(--border);border-radius:3px;padding:1px 5px">/</kbd>
    </a>
    <!-- NOTIFICATION BELL -->
    <button id="notif-btn" onclick="toggleNotifPanel()" title="Notifications" aria-label="Notifications">
      🔔
      <span id="notif-badge"></span>
    </button>
    <!-- NOTIFICATION PANEL (rendered here, positioned absolute) -->
    <div id="notif-panel">
      <div class="notif-panel-head">
        <div class="notif-panel-head-row">
          <span class="notif-panel-title">🔔 Notifications</span>
          <div style="display:flex;gap:6px;align-items:center">
            <button onclick="markAllRead()" style="font-size:11.5px;background:none;border:none;cursor:pointer;color:var(--orange);font-weight:600" id="notif-mark-all">Mark all read</button>
            <button onclick="clearRead()" style="font-size:11.5px;background:none;border:none;cursor:pointer;color:var(--text3)">Clear read</button>
          </div>
        </div>
        <div class="notif-filters" id="notif-filters">
          <button class="nf-btn active" data-filter="all"     onclick="setFilter('all')">All</button>
          <button class="nf-btn"        data-filter="unread"  onclick="setFilter('unread')">Unread</button>
          <button class="nf-btn"        data-filter="task"    onclick="setFilter('task')">Tasks</button>
          <button class="nf-btn"        data-filter="project" onclick="setFilter('project')">Projects</button>
          <button class="nf-btn"        data-filter="lead"    onclick="setFilter('lead')">Leads</button>
          <button class="nf-btn"        data-filter="invoice" onclick="setFilter('invoice')">Invoices</button>
        </div>
      </div>
      <div class="notif-list" id="notif-list">
        <div class="notif-loading">Loading…</div>
      </div>
      <div class="notif-panel-foot">
        <span style="font-size:11.5px;color:var(--text3)" id="notif-foot-count"></span>
        <a href="emails.php?tab=notifications" style="font-size:12px;color:var(--orange);font-weight:600">View all →</a>
      </div>
    </div>
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

/* / key shortcut → search page */
document.addEventListener('keydown',function(e){
  if(e.key==='/'&&!['INPUT','TEXTAREA'].includes(document.activeElement.tagName)){
    e.preventDefault(); location.href='search.php';
  }
  if(e.key==='Escape'){closeNotifPanel();}
});

/* ══ NOTIFICATIONS ══ */
var _nFilter='all', _nPollTimer=null, _nPanelOpen=false, _nLoaded=false;

var NOTIF_ICONS={
  task_assigned:'📋',task_due:'⏰',invoice_sent:'🧾',invoice_paid:'✅',
  lead_update:'🎯',mention:'@',comment_added:'💬',project_update:'📁',
  info:'ℹ',task_due_today:'🔴',default:'🔔'
};
var NOTIF_COLORS={
  task_assigned:'rgba(99,102,241,.18)',task_due:'rgba(239,68,68,.15)',
  invoice_sent:'rgba(249,115,22,.15)',invoice_paid:'rgba(16,185,129,.15)',
  lead_update:'rgba(16,185,129,.15)',mention:'rgba(139,92,246,.15)',
  comment_added:'rgba(96,165,250,.15)',project_update:'rgba(99,102,241,.15)',
  default:'rgba(148,163,184,.12)'
};

function toggleNotifPanel(){
  _nPanelOpen=!_nPanelOpen;
  var p=document.getElementById('notif-panel');
  p.classList.toggle('open',_nPanelOpen);
  if(_nPanelOpen){
    if(!_nLoaded){loadNotifs(true);}
    document.addEventListener('click',_notifOutsideClick,{once:false});
  } else {
    document.removeEventListener('click',_notifOutsideClick);
  }
}
function closeNotifPanel(){
  _nPanelOpen=false;
  document.getElementById('notif-panel').classList.remove('open');
  document.removeEventListener('click',_notifOutsideClick);
}
function _notifOutsideClick(e){
  if(!e.target.closest('#notif-panel')&&!e.target.closest('#notif-btn')){closeNotifPanel();}
}

function setFilter(f){
  _nFilter=f;
  document.querySelectorAll('.nf-btn').forEach(function(b){b.classList.toggle('active',b.dataset.filter===f);});
  loadNotifs(true);
}

function loadNotifs(showLoader){
  var list=document.getElementById('notif-list');
  if(showLoader)list.innerHTML='<div class="notif-loading">Loading…</div>';
  fetch('notif_api.php?action=list&filter='+_nFilter+'&limit=30')
    .then(function(r){return r.json();})
    .then(function(d){
      if(!d.ok)return;
      _nLoaded=true;
      renderNotifs(d.notifications,d.unread,d.total);
    })
    .catch(function(){list.innerHTML='<div class="notif-loading" style="color:var(--red)">Failed to load</div>';});
}

function renderNotifs(items,unread,total){
  updateBadge(unread);
  var foot=document.getElementById('notif-foot-count');
  if(foot)foot.textContent=total+' notification'+(total!==1?'s':'')+' · '+unread+' unread';

  var list=document.getElementById('notif-list');
  if(!items||!items.length){
    list.innerHTML='<div class="notif-empty"><div class="notif-empty-icon">🔔</div><div style="font-size:13px;color:var(--text2);font-weight:600">All caught up!</div><div style="font-size:12px;margin-top:4px">No notifications</div></div>';
    return;
  }
  var html='';
  items.forEach(function(n){
    var ic=NOTIF_ICONS[n.type]||NOTIF_ICONS.default;
    var bg=NOTIF_COLORS[n.type]||NOTIF_COLORS.default;
    var url=n.link?n.link:'#';
    html+='<div class="notif-item'+(n.is_read?'':' unread')+'" data-id="'+n.id+'" data-url="'+escHtml(url)+'" onclick="notifClick(this)">'
      +'<div class="notif-icon" style="background:'+bg+'">'+ic+'</div>'
      +'<div class="notif-body">'
        +'<div class="notif-title">'+escHtml(n.title)+'</div>'
        +(n.body?'<div class="notif-msg">'+escHtml(n.body)+'</div>':'')
        +'<div class="notif-age">'+n.age+'</div>'
      +'</div>'
      +'<button class="notif-del" onclick="event.stopPropagation();deleteNotif(this,'+n.id+')" title="Dismiss">✕</button>'
      +(!n.is_read?'<div class="notif-unread-dot"></div>':'')
    +'</div>';
  });
  list.innerHTML=html;
}

function notifClick(el){
  var id=parseInt(el.dataset.id);
  var url=el.dataset.url;
  // Mark read via API
  fetch('notif_api.php?action=mark_read&id='+id,{method:'POST'});
  el.classList.remove('unread');
  el.querySelector('.notif-unread-dot')&&el.querySelector('.notif-unread-dot').remove();
  // Update badge
  var badge=document.getElementById('notif-badge');
  if(badge.textContent>0){var c=parseInt(badge.textContent)-1;updateBadge(c);}
  // Navigate
  if(url&&url!=='#'){closeNotifPanel();location.href=url;}
}

function deleteNotif(btn,id){
  fetch('notif_api.php?action=delete',{method:'POST',body:new URLSearchParams({id:id})});
  btn.closest('.notif-item').remove();
  // refresh count from remaining items
  var remaining=document.querySelectorAll('.notif-item.unread').length;
  updateBadge(remaining);
}

function markAllRead(){
  fetch('notif_api.php?action=mark_all',{method:'POST',body:new URLSearchParams({filter:_nFilter})})
    .then(function(r){return r.json();})
    .then(function(d){updateBadge(d.unread||0);loadNotifs(false);});
}

function clearRead(){
  fetch('notif_api.php?action=clear_read',{method:'POST'})
    .then(function(){loadNotifs(true);});
}

function updateBadge(count){
  var b=document.getElementById('notif-badge');
  if(!b)return;
  if(count>0){b.textContent=count>99?'99+':count;b.classList.add('show');}
  else{b.textContent='';b.classList.remove('show');}
}

function escHtml(s){
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── POLL every 30 seconds ──
function pollNotifCount(){
  fetch('notif_api.php?action=count')
    .then(function(r){return r.json();})
    .then(function(d){
      updateBadge(d.count||0);
      if(_nPanelOpen)loadNotifs(false);
    })
    .catch(function(){});
}
// Initial load of badge count
pollNotifCount();
// Poll every 30 seconds
_nPollTimer=setInterval(pollNotifCount,30000);
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