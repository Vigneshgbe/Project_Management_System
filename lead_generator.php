<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];
renderLayout('Lead Generator', 'lead_generator');
?>
<style>
/* ── LAYOUT ── */
.lg-top{display:grid;grid-template-columns:260px 1fr 240px;gap:14px;margin-bottom:18px}
/* ── RING CARD ── */
.lg-ring-card{background:linear-gradient(135deg,#f97316,#ea580c);border-radius:var(--radius-lg);padding:18px;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:160px;border:none}
.lg-ring-wrap{position:relative;width:100px;height:100px;margin:8px auto}
.lg-ring-svg{transform:rotate(-90deg)}
.lg-ring-bg{fill:none;stroke:rgba(255,255,255,.18);stroke-width:8}
.lg-ring-fill{fill:none;stroke:#fff;stroke-width:8;stroke-linecap:round;transition:stroke-dashoffset .6s}
.lg-ring-inner{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
.lg-ring-pct{font-size:20px;font-weight:800;color:#fff}
.lg-ring-sub{font-size:10.5px;color:rgba(255,255,255,.65)}
.lg-ring-title{font-size:11px;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:.07em;margin-bottom:4px}
/* ── COST METER ── */
.lg-cost-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px}
.cost-bar{height:10px;border-radius:99px;background:var(--bg4);overflow:hidden;margin:8px 0 4px}
.cost-fill{height:100%;border-radius:99px;transition:width .5s}
/* ── SEARCH FORM ── */
.lg-search-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;margin-bottom:16px}
.lg-search-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;align-items:end}
.lg-input{padding:9px 12px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13.5px;font-family:var(--font);width:100%;transition:border-color .15s}
.lg-input:focus{outline:none;border-color:var(--orange)}
.lg-input::placeholder{color:var(--text3)}
.lg-gen-btn{padding:9px 22px;background:var(--orange);color:#fff;border:none;border-radius:var(--radius-sm);font-size:13.5px;font-weight:700;cursor:pointer;white-space:nowrap;transition:opacity .15s}
.lg-gen-btn:hover{opacity:.88}
.lg-gen-btn:disabled{opacity:.45;cursor:not-allowed}
/* ── RESULTS TABLE ── */
.lg-results-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:0;overflow:hidden}
.lg-results-head{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;background:var(--bg3)}
.lg-tbl{width:100%;border-collapse:collapse}
.lg-tbl th{font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;padding:9px 12px;border-bottom:2px solid var(--border);text-align:left;white-space:nowrap;background:var(--bg3)}
.lg-tbl td{padding:11px 12px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text2);vertical-align:middle}
.lg-tbl tr:last-child td{border-bottom:none}
.lg-tbl tr:hover td{background:var(--bg3)}
.lg-name{font-weight:700;color:var(--text);font-size:13.5px}
.lg-phone{font-family:monospace;font-size:13px;color:var(--text)}
.lg-addr{max-width:250px;font-size:12px;color:var(--text3)}
.lg-rating{color:#f59e0b;font-size:12px;font-weight:700}
.lg-web-yes{background:rgba(16,185,129,.1);color:#10b981;border:1px solid rgba(16,185,129,.25);padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap}
.lg-web-no{background:var(--bg4);color:var(--text3);padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700;white-space:nowrap}
.lg-call-btn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;background:#10b981;color:#fff;border-radius:50%;text-decoration:none;font-size:13px;border:none;cursor:pointer;flex-shrink:0;transition:opacity .15s}
.lg-call-btn:hover{opacity:.8}
.lg-imp-btn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;background:var(--orange);color:#fff;border-radius:50%;border:none;cursor:pointer;font-size:12px;flex-shrink:0;transition:opacity .15s}
.lg-imp-btn:hover{opacity:.8}
.lg-imp-btn:disabled{background:var(--bg4);color:var(--text3);cursor:default}
.lg-imp-done{background:rgba(16,185,129,.12);color:#10b981;border:1px solid rgba(16,185,129,.25);padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700;white-space:nowrap}
.lg-loading{text-align:center;padding:36px 20px;display:none}
.lg-spinner{display:inline-block;width:28px;height:28px;border:3px solid var(--border);border-top-color:var(--orange);border-radius:50%;animation:lgspin .7s linear infinite;margin-bottom:10px}
@keyframes lgspin{to{transform:rotate(360deg)}}
/* ── SETTINGS ── */
.lg-settings{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;margin-bottom:16px;display:none}
.lg-settings.open{display:block}
.lg-setup-step{display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)}
.lg-setup-step:last-child{border-bottom:none}
.lg-step-num{width:26px;height:26px;border-radius:50%;background:var(--orange);color:#fff;font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}
.lg-setup-step h4{font-size:13px;font-weight:700;color:var(--text);margin:0 0 3px}
.lg-setup-step p{font-size:12.5px;color:var(--text2);margin:0;line-height:1.6}
.lg-setup-step a{color:var(--orange);font-weight:600}
.lg-setup-step code{background:var(--bg2);padding:2px 6px;border-radius:4px;font-size:12px}
/* ── BUDGET ALERTS ── */
.budget-ok{background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.25);border-radius:var(--radius-sm);padding:10px 14px;font-size:12.5px;color:#10b981}
.budget-warn{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);border-radius:var(--radius-sm);padding:10px 14px;font-size:12.5px;color:#f59e0b}
.budget-danger{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);border-radius:var(--radius-sm);padding:10px 14px;font-size:12.5px;color:#ef4444}
/* ── RECENT ── */
.lg-act{display:flex;align-items:flex-start;gap:8px;padding:7px 0;border-bottom:1px solid var(--border)}
.lg-act:last-child{border-bottom:none}
.lg-act-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;margin-top:5px}
@media(max-width:1000px){.lg-top{grid-template-columns:1fr 1fr}}
@media(max-width:700px){.lg-top{grid-template-columns:1fr}.lg-search-row{grid-template-columns:1fr 1fr}}
@media(max-width:480px){.lg-search-row{grid-template-columns:1fr}}
/* ── MODE LABELS ── */
.mode-label{display:flex;align-items:flex-start;gap:8px;padding:9px 14px;background:var(--bg3);border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:all .15s}
#lm-no_website.mode-active{background:rgba(16,185,129,.08)!important;border-color:#10b981!important}
#lm-high_value.mode-active{background:rgba(249,115,22,.06)!important;border-color:var(--orange)!important}
#lm-all.mode-active{background:var(--bg4)!important;border-color:var(--text3)!important}

/* ── QUOTA MANAGEMENT ── */
.quota-role-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
.quota-role-box{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px}
.quota-role-box label{font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:6px}
.quota-role-box input{width:100%;padding:7px 10px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:14px;font-weight:700;text-align:center}
.quota-role-box input:focus{outline:none;border-color:var(--orange)}
.quota-role-box small{font-size:10px;color:var(--text3);display:block;margin-top:4px;text-align:center}
.quota-user-tbl{width:100%;border-collapse:collapse;font-size:13px}
.quota-user-tbl th{font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;padding:7px 10px;border-bottom:2px solid var(--border);text-align:left;background:var(--bg3)}
.quota-user-tbl td{padding:8px 10px;border-bottom:1px solid var(--border);vertical-align:middle}
.quota-user-tbl tr:last-child td{border-bottom:none}
.quota-user-tbl tr:hover td{background:var(--bg4)}
.qu-input{width:80px;padding:5px 8px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13px;text-align:center}
.qu-input:focus{outline:none;border-color:var(--orange)}
.qu-bar-wrap{width:80px;height:6px;background:var(--bg4);border-radius:99px;overflow:hidden;display:inline-block;vertical-align:middle}
.qu-bar{height:100%;border-radius:99px;transition:width .3s}
.badge-role{padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;text-transform:uppercase}
.badge-admin{background:rgba(239,68,68,.1);color:#ef4444}
.badge-manager{background:rgba(249,115,22,.1);color:var(--orange)}
.badge-user{background:rgba(148,163,184,.12);color:var(--text3)}
.badge-blocked{background:rgba(239,68,68,.1);color:#ef4444;font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px}
.badge-custom{background:rgba(99,102,241,.1);color:#6366f1;font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px}
/* ── QUOTA BANNER ── */
.lg-quota-banner{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:var(--radius-sm);margin-bottom:14px;font-size:13px}
.lg-quota-banner.ok{background:rgba(16,185,129,.07);border:1px solid rgba(16,185,129,.2);color:#10b981}
.lg-quota-banner.warn{background:rgba(249,115,22,.07);border:1px solid rgba(249,115,22,.2);color:var(--orange)}
.lg-quota-banner.danger{background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2);color:#ef4444}
.lg-quota-banner.blocked{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);color:#ef4444}
/* ── AUTOCOMPLETE DROPDOWN ── */
.lg-ac-drop{position:absolute;top:100%;left:0;right:0;background:var(--bg2);border:1px solid var(--orange);border-top:none;border-radius:0 0 var(--radius-sm) var(--radius-sm);z-index:999;max-height:260px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.15)}
.lg-ac-group{font-size:10px;font-weight:800;color:var(--text3);text-transform:uppercase;letter-spacing:.08em;padding:7px 12px 3px;background:var(--bg3);border-top:1px solid var(--border)}
.lg-ac-item{padding:8px 12px;font-size:13px;color:var(--text);cursor:pointer;display:flex;align-items:center;gap:8px;transition:background .1s}
.lg-ac-item:hover,.lg-ac-item.active{background:rgba(249,115,22,.08);color:var(--orange)}
.lg-ac-item .ac-flag{font-size:15px;flex-shrink:0}
.lg-ac-item .ac-label{font-weight:600}
.lg-ac-item .ac-sub{font-size:11px;color:var(--text3);margin-left:auto}
.lg-ac-item mark{background:rgba(249,115,22,.2);color:var(--orange);border-radius:2px;padding:0 1px}
</style>

<!-- TOP STATS ROW -->
<div class="lg-top" id="lg-top-row">
  <!-- Usage ring -->
  <div class="lg-ring-card">
    <div class="lg-ring-title">My Monthly Usage</div>
    <div class="lg-ring-wrap">
      <svg class="lg-ring-svg" viewBox="0 0 100 100" width="100" height="100">
        <circle class="lg-ring-bg" cx="50" cy="50" r="42"/>
        <circle class="lg-ring-fill" id="lg-ring" cx="50" cy="50" r="42"
          stroke-dasharray="264" stroke-dashoffset="264"/>
      </svg>
      <div class="lg-ring-inner">
        <div class="lg-ring-pct" id="lg-pct">0%</div>
        <div class="lg-ring-sub" id="lg-sub">0 / 300</div>
      </div>
    </div>
    <div class="lg-ring-src" id="lg-quota-src" style="font-size:10px;color:rgba(255,255,255,.6);margin-top:4px;text-align:center"></div>
  </div>

  <!-- Recent searches -->
  <div class="lg-cost-card">
    <div style="font-size:13px;font-weight:700;font-family:var(--font-display);margin-bottom:10px">📊 Recent Searches</div>
    <div id="lg-recent"><div style="color:var(--text3);font-size:12.5px">Loading...</div></div>
  </div>

  <!-- Cost meter -->
  <div class="lg-cost-card">
    <div style="font-size:13px;font-weight:700;font-family:var(--font-display);margin-bottom:10px">💰 API Budget</div>
    <div id="lg-budget-status"></div>
    <div class="cost-bar"><div class="cost-fill" id="lg-cost-fill" style="width:0%;background:#10b981"></div></div>
    <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text3)">
      <span>$<span id="lg-cost-used">0.00</span> used</span>
      <span>$<span id="lg-cost-limit">15.00</span> limit</span>
    </div>
    <div style="margin-top:10px;font-size:11.5px;color:var(--text3);line-height:1.7">
      <div>Cost per lead: ~$0.035</div>
      <div id="lg-rem-leads"></div>
      <div style="margin-top:4px;font-size:10.5px;color:var(--text3)">Google $200 free/month · You'll never hit it</div>
    </div>
  </div>
</div>

<!-- NO API BANNER -->
<div id="lg-no-api-banner" style="display:none;background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.3);border-radius:var(--radius-lg);padding:14px 18px;margin-bottom:16px;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
  <div style="display:flex;align-items:center;gap:10px">
    <span style="font-size:22px">🔑</span>
    <div><div style="font-size:13px;font-weight:700;color:var(--orange)">Google Places API key not configured</div>
    <div style="font-size:12px;color:var(--text2)">Takes 2 minutes to setup. Free trial credits cover thousands of searches.</div></div>
  </div>
  <?php if (isAdmin()): ?><button onclick="toggleSettings()" class="btn btn-sm" style="background:var(--orange);color:#fff;border:none">⚙ Configure Now</button><?php endif; ?>
</div>

<!-- BLOCKED BANNER -->
<div id="lg-blocked-banner" style="display:none;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);border-radius:var(--radius-lg);padding:14px 18px;margin-bottom:16px">
  <div style="font-size:13px;font-weight:700;color:#ef4444">🚫 Your account is not allowed to use Lead Generator</div>
  <div style="font-size:12px;color:var(--text2);margin-top:4px">Contact your admin to get lead generation credits assigned to your account.</div>
</div>

<!-- SETTINGS PANEL (admin only) -->
<?php if (isAdmin()): ?>
<div class="lg-settings" id="lg-settings-panel">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div style="font-size:13.5px;font-weight:700">⚙ Lead Generator Settings</div>
    <button onclick="toggleSettings()" class="btn btn-ghost btn-sm">✕ Close</button>
  </div>

  <!-- Tab nav -->
  <div style="display:flex;gap:4px;margin-bottom:16px;border-bottom:2px solid var(--border);padding-bottom:0">
    <button class="lg-stab active" onclick="switchSettingsTab('api')" id="stab-api"
      style="padding:8px 16px;background:none;border:none;border-bottom:2px solid var(--orange);margin-bottom:-2px;font-size:13px;font-weight:700;color:var(--orange);cursor:pointer">🔑 API Setup</button>
    <button class="lg-stab" onclick="switchSettingsTab('quota')" id="stab-quota"
      style="padding:8px 16px;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer">👥 Quota Management</button>
  </div>

  <!-- TAB: API Setup -->
  <div id="stab-content-api">
    <div style="background:var(--bg2);border-radius:var(--radius-sm);padding:14px;margin-bottom:16px">
      <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:10px">How to get your Google Places API key (already paid ₹1,000 — use that credit):</div>
      <div class="lg-setup-step"><div class="lg-step-num">1</div><div><h4>Go to Google Cloud Console</h4><p>Open <a href="https://console.cloud.google.com/" target="_blank">console.cloud.google.com</a> → Your project "Leads-Generator"</p></div></div>
      <div class="lg-setup-step"><div class="lg-step-num">2</div><div><h4>Enable Places API</h4><p>Left menu → <strong>APIs & Services</strong> → <strong>Library</strong> → Search <code>Places API</code> → Enable</p></div></div>
      <div class="lg-setup-step"><div class="lg-step-num">3</div><div><h4>Create API Key</h4><p>APIs & Services → <strong>Credentials</strong> → <strong>+ Create Credentials</strong> → <strong>API key</strong> → Copy</p></div></div>
      <div class="lg-setup-step"><div class="lg-step-num">4</div><div><h4>Restrict Key</h4><p>Click the key → <strong>API restrictions</strong> → Restrict to <strong>Places API</strong> only → Save</p></div></div>
      <div style="margin-top:10px;padding:10px 12px;background:rgba(249,115,22,.06);border:1px solid rgba(249,115,22,.2);border-radius:var(--radius-sm);font-size:12px;color:var(--orange)">
        ✅ Your ₹1,000 prepaid credit covers <strong>~4,500 leads</strong> at $0.035/lead. Monthly limits protect you automatically.
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 130px 130px auto;gap:10px;align-items:end">
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Google Places API Key</label>
        <input type="password" id="cfg-key" class="lg-input" placeholder="AIzaSy..." autocomplete="off">
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Global Monthly Cap</label>
        <input type="number" id="cfg-quota" class="lg-input" value="300" min="10" max="5000">
        <div style="font-size:10px;color:var(--text3);margin-top:2px">Total leads/month</div>
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Budget Cap ($)</label>
        <input type="number" id="cfg-budget" class="lg-input" value="15" min="1" max="180" step="0.5">
        <div style="font-size:10px;color:var(--text3);margin-top:2px">Max USD/month</div>
      </div>
      <div style="display:flex;gap:8px">
        <button onclick="saveSettings()" class="btn btn-primary">💾 Save</button>
        <button onclick="testKey()" class="btn btn-ghost btn-sm" id="cfg-test-btn">🔌 Test</button>
      </div>
    </div>
    <div id="cfg-test-result" style="margin-top:10px;display:none;padding:8px 12px;border-radius:var(--radius-sm);font-size:13px;white-space:pre-line"></div>
  </div>

  <!-- TAB: Quota Management -->
  <div id="stab-content-quota" style="display:none">
    <div style="font-size:13px;font-weight:700;margin-bottom:4px">👥 Lead Quota by Role</div>
    <div style="font-size:12px;color:var(--text3);margin-bottom:14px">Set monthly lead limits per role. User-level overrides take priority. Set <strong>0</strong> to block a user/role entirely.</div>
    <div class="quota-role-grid">
      <div class="quota-role-box">
        <label>🔴 Admin</label>
        <input type="number" id="qr-admin" value="300" min="0" max="5000" placeholder="300">
        <small>Full global quota (recommended)</small>
      </div>
      <div class="quota-role-box">
        <label>🟠 Manager</label>
        <input type="number" id="qr-manager" value="100" min="0" max="5000" placeholder="100">
        <small>Leads/month for managers</small>
      </div>
      <div class="quota-role-box">
        <label>⚪ Member / User</label>
        <input type="number" id="qr-user" value="30" min="0" max="5000" placeholder="30">
        <small>Leads/month for regular members</small>
      </div>
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
      <div style="font-size:13px;font-weight:700">Per-User Overrides</div>
      <div style="font-size:11px;color:var(--text3)">Blank = inherit role default &nbsp;|&nbsp; 0 = blocked</div>
    </div>
    <div style="overflow-x:auto;max-height:320px;overflow-y:auto">
      <table class="quota-user-tbl">
        <thead><tr><th>User</th><th>Role</th><th>This Month Used</th><th>Effective Quota</th><th>Override (leads/mo)</th></tr></thead>
        <tbody id="quota-users-tbody"><tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text3)">Loading users...</td></tr></tbody>
      </table>
    </div>
    <div style="display:flex;gap:10px;margin-top:14px;align-items:center">
      <button onclick="saveQuotaConfig()" class="btn btn-primary">💾 Save Quota Settings</button>
      <button onclick="loadQuotaConfig()" class="btn btn-ghost btn-sm">🔄 Refresh</button>
      <div id="quota-save-msg" style="font-size:12.5px;display:none"></div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- SEARCH FORM -->
<div class="lg-search-card">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px">
    <div style="font-size:14px;font-weight:700;font-family:var(--font-display)">🔍 Generate Business Leads</div>
    <div style="font-size:12px;color:var(--text3);margin-top:2px">Finds businesses · Fetches phone, email, website, rating &amp; opportunity score</div>
    <?php if (isAdmin()): ?>
    <button onclick="toggleSettings()" class="btn btn-ghost btn-sm" style="font-size:12px">⚙ Settings</button>
    <?php endif; ?>
  </div>

  <!-- Quota status banner -->
  <div id="lg-user-quota-banner" class="lg-quota-banner ok" style="display:none"></div>

  <!-- SEARCH MODE -->
  <div style="margin-bottom:14px">
    <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Search Mode</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <label id="lm-no_website" class="mode-label" onclick="setMode('no_website')">
        <div><div style="font-size:13px;font-weight:700;color:#10b981">🔥 No Website Only</div><div style="font-size:11px;color:var(--text3)">Prime prospects — need a website built</div></div>
      </label>
      <label id="lm-high_value" class="mode-label" onclick="setMode('high_value')">
        <div><div style="font-size:13px;font-weight:700;color:var(--orange)">💎 High Value</div><div style="font-size:11px;color:var(--text3)">Established — bigger budgets (₹40k+)</div></div>
      </label>
      <label id="lm-all" class="mode-label" onclick="setMode('all')">
        <div><div style="font-size:13px;font-weight:700;color:var(--text2)">📋 All Results</div><div style="font-size:11px;color:var(--text3)">All businesses sorted by score</div></div>
      </label>
    </div>
    <input type="hidden" id="lg-search-mode" value="no_website">
  </div>

  <!-- Cost estimate preview -->
  <div id="lg-cost-preview" style="margin-bottom:12px;font-size:12.5px;color:var(--text3)">
    Each search costs approximately <strong>$0.032</strong> (text search) + <strong>$0.003 × leads</strong> (phone/website lookup).
    At 5 leads: ~$0.047. Monthly limit protects you automatically.
  </div>

  <div class="lg-search-row">
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Country</label>
      <select id="lg-country" class="lg-input" onchange="onCountryChange(this.value)" style="cursor:pointer">
        <option value="">Select Country</option>
      </select>
    </div>
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">State / Province</label>
      <select id="lg-state" class="lg-input" onchange="onStateChange(this.value)" style="cursor:pointer" disabled>
        <option value="">Select State</option>
      </select>
    </div>
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">City / District / Town</label>
      <select id="lg-city" class="lg-input" onchange="onCityChange(this.value)" style="cursor:pointer" disabled>
        <option value="">Select City</option>
      </select>
    </div>
    <div style="position:relative">
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Custom Location <span style="font-weight:400;text-transform:none;font-size:10px;color:var(--text3)">(optional override)</span></label>
      <input type="text" id="lg-location" class="lg-input" placeholder="e.g. Trichy Old Town" autocomplete="off">
    </div>
    <div style="position:relative">
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Industry / Business Type</label>
      <input type="text" id="lg-industry" class="lg-input" placeholder="Type or select industry..." autocomplete="off"
        oninput="lgIndInput(this)" onkeydown="lgIndKey(event)" onfocus="lgIndInput(this)" onblur="setTimeout(lgIndHide,200)">
      <div id="lg-ind-drop" class="lg-ac-drop" style="display:none"></div>
    </div>
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Count</label>
      <input type="number" id="lg-count" class="lg-input" value="5" min="1" max="20">
    </div>
    <div>
      <label style="display:block;margin-bottom:4px;opacity:0">_</label>
      <button class="lg-gen-btn" id="lg-gen-btn" onclick="doSearch()">Generate</button>
    </div>
  </div>
  <div id="lg-quota-bar-row" style="margin-top:10px"></div>
</div>


<!-- LOADING STATE -->
<div class="lg-loading" id="lg-loading">
  <div class="lg-spinner"></div>
  <div style="font-size:14px;font-weight:600;color:var(--text2)" id="lg-load-text">Searching Google Maps...</div>
  <div style="font-size:12px;color:var(--text3);margin-top:4px" id="lg-load-sub"></div>
</div>

<!-- RESULTS TABLE -->
<div id="lg-results-section" style="display:none" class="lg-results-card">
  <div class="lg-results-head">
    <div>
      <div style="font-size:14px;font-weight:700;font-family:var(--font-display)">Generated Leads
        <span id="lg-res-label" style="font-size:12px;color:var(--text3);font-weight:400"></span>
      </div>
      <div id="lg-cost-summary" style="font-size:12px;color:var(--text3);margin-top:2px"></div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button onclick="importAll()" class="btn btn-sm" id="lg-imp-all-btn"
        style="background:var(--orange);color:#fff;border:none">⬇ Import All to CRM</button>
      <button onclick="exportCSV()" class="btn btn-ghost btn-sm">⬇ Download CSV</button>
    </div>
  </div>
  <div style="overflow-x:auto">
    <table class="lg-tbl">
      <thead>
        <tr>
          <th>Score</th>
          <th>#</th>
          <th>Business Name / Owner</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Address</th>
          <th>Website</th>
          <th>Rating</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="lg-tbody"></tbody>
    </table>
  </div>
</div>

<script>
var lgIds=[], lgConfigured=false, lgQuota=0, lgBudget=15, lgSearchMode='no_website';
var lgUsed=0, lgRemaining=0, lgBlocked=false, lgQuotaSrc='role', lgTotalStored=0;

document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    populateCountryDropdown();
    setMode('no_website');
    ['lg-location','lg-industry','lg-count'].forEach(function(id) {
        var el=document.getElementById(id);
        if (el) el.addEventListener('keydown', function(e){ if(e.key==='Enter') doSearch(); });
    });
    // Update cost preview on count change
    document.getElementById('lg-count')?.addEventListener('input', updateCostPreview);
});

function updateCostPreview() {
    var cnt = parseInt(document.getElementById('lg-count')?.value)||5;
    var cost = (0.032 + 0.003*cnt).toFixed(4);
    document.getElementById('lg-cost-preview').innerHTML =
        'This search: <strong>$'+cost+'</strong> (1 text search $0.032 + '+cnt+' detail lookups x $0.003). '+
        'Monthly limit of $'+lgBudget+' protects you automatically.';
}

function loadStats() {
    fetch('lead_generator_api.php?action=get_stats')
    .then(function(r){return r.json();})
    .then(function(d){
        if (!d.ok) return;
        lgConfigured=d.api_set; lgQuota=d.quota||0; lgBudget=d.budget||15;
        lgUsed=d.used||0; lgRemaining=d.remaining||0; lgBlocked=!!d.blocked; lgQuotaSrc=d.quota_source||'role';

        document.getElementById('lg-no-api-banner').style.display = d.api_set?'none':'flex';
        var bb=document.getElementById('lg-blocked-banner'); if(bb) bb.style.display=d.blocked?'block':'none';

        // Ring
        var pct=lgQuota>0?Math.min(100,Math.round(lgUsed/lgQuota*100)):0;
        document.getElementById('lg-pct').textContent=pct+'%';
        document.getElementById('lg-sub').textContent=lgUsed+' / '+lgQuota;
        var qs=document.getElementById('lg-quota-src');
        if(qs) qs.textContent=lgQuotaSrc==='user'?'(custom quota)':lgQuotaSrc==='global'?'(admin quota)':'(role default)';
        var circ=2*Math.PI*42;
        var fill=document.getElementById('lg-ring');
        fill.setAttribute('stroke-dasharray',circ.toFixed(1));
        fill.setAttribute('stroke-dashoffset',(circ-(pct/100*circ)).toFixed(1));
        fill.style.stroke=pct>95?'#ef4444':pct>80?'#fbbf24':'#fff';

        // Cost meter — global cost
        var displayCost=d.global_cost!==undefined?d.global_cost:d.cost;
        var cpct=lgBudget>0?Math.min(100,Math.round(displayCost/lgBudget*100)):0;
        document.getElementById('lg-cost-used').textContent=displayCost.toFixed(4);
        document.getElementById('lg-cost-limit').textContent=lgBudget.toFixed(2);
        var cf=document.getElementById('lg-cost-fill');
        cf.style.width=cpct+'%'; cf.style.background=cpct>80?'#ef4444':cpct>60?'#f59e0b':'var(--orange)';
        var remL=Math.floor((lgBudget-displayCost)/0.035);
        document.getElementById('lg-rem-leads').textContent='~'+Math.max(0,remL)+' leads left (system)';
        var bs=document.getElementById('lg-budget-status');
        if(bs){
            if(cpct>80) bs.innerHTML='<div class="budget-danger">Budget '+(cpct>95?'CRITICAL':'WARNING')+': '+cpct+'% used</div>';
            else if(cpct>50) bs.innerHTML='<div class="budget-warn">Budget '+cpct+'% used</div>';
            else bs.innerHTML='<div class="budget-ok">Budget OK - '+cpct+'% used</div>';
        }

        renderUserQuotaBanner(d);

        // Quota bar
        var qb=document.getElementById('lg-quota-bar-row');
        if(qb&&lgQuota>0){
            var remQ=Math.max(0,lgQuota-lgUsed);
            qb.innerHTML='<div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text3);margin-bottom:4px">'
                +'<span>Your quota: '+lgUsed+' / '+lgQuota+' leads used this month</span>'
                +'<span style="color:'+(remQ<5?'#ef4444':remQ<10?'var(--orange)':'var(--text3)')+'">'+remQ+' remaining</span></div>'
                +'<div style="height:6px;background:var(--bg4);border-radius:99px;overflow:hidden">'
                +'<div style="height:100%;border-radius:99px;background:'+(pct>80?'#ef4444':pct>50?'#f59e0b':'var(--orange)')+';width:'+pct+'%;transition:width .4s"></div></div>';
        }

        if(document.getElementById('cfg-quota')) document.getElementById('cfg-quota').value=lgQuota;
        if(document.getElementById('cfg-budget')) document.getElementById('cfg-budget').value=lgBudget;
        lgTotalStored=d.total_all||0;
        renderRecent(d.recent||[]);
        updateCostPreview();

        var gb=document.getElementById('lg-gen-btn');
        if(gb) gb.disabled=d.blocked||(lgRemaining<=0);
    })
    .catch(function(){});
}

function renderUserQuotaBanner(d) {
    var b=document.getElementById('lg-user-quota-banner'); if(!b) return;
    if(d.blocked){b.className='lg-quota-banner blocked';b.style.display='flex';b.innerHTML='🚫 <strong>You have no lead quota.</strong> Contact admin to get access.';return;}
    var pct=d.quota>0?Math.round(d.used/d.quota*100):0;
    var rem=d.remaining||0;
    if(rem<=0){b.className='lg-quota-banner danger';b.style.display='flex';b.innerHTML='Monthly quota exhausted. You have used all '+d.quota+' leads. Resets next month or ask admin to increase your limit.';}
    else if(pct>=70){b.className='lg-quota-banner warn';b.style.display='flex';b.innerHTML='<strong>'+rem+' leads remaining</strong> this month ('+pct+'% used). Use wisely!';}
    else{b.className='lg-quota-banner ok';b.style.display='flex';b.innerHTML='<strong>'+rem+' leads remaining</strong> this month &nbsp;·&nbsp; Quota: '+d.used+' / '+d.quota+(d.quota_source==='user'?' <span style="font-size:11px;opacity:.7">(custom)</span>':'');}
}


function renderRecent(data) {
    var el = document.getElementById('lg-recent');
    if (!el) return;
    if (!data.length) { el.innerHTML='<div style="color:var(--text3);font-size:12.5px">No searches yet</div>'; return; }
    var colors=['var(--orange)','#10b981','#8b5cf6','#f59e0b','#14b8a6'];
    el.innerHTML = data.map(function(r,i){
        var cost = parseFloat(r.estimated_cost||0).toFixed(4);
        return '<div class="lg-act" onclick="loadSearchHistory('+r.id+')" title="Click to view these leads"><div class="lg-act-dot" style="background:'+colors[i%colors.length]+'"></div>'
            +'<div><div style="font-size:12.5px;color:var(--text2);font-weight:600">'+esc(r.result_count)+' leads · '+esc(r.industry)+' in '+esc(r.location)+'</div>'
            +'<div style="font-size:11px;color:var(--text3)">$'+cost+' cost · '+fmtAgo(r.created_at)+'</div></div></div>';
    }).join('');
}

function doSearch() {
    if (lgBlocked||lgRemaining<=0) { toast('You have no leads remaining this month. Contact admin.','error'); return; }
    var loc=document.getElementById('lg-location').value.trim();
    var ind=document.getElementById('lg-industry').value.trim();
    var cnt=parseInt(document.getElementById('lg-count').value)||5;
    if (!loc) { toast('Enter a location','error'); document.getElementById('lg-location').focus(); return; }
    if (!ind) { toast('Enter an industry','error'); document.getElementById('lg-industry').focus(); return; }
    if (!lgConfigured) { toast('Configure Google API key first','error'); toggleSettings(); return; }
    if (cnt>lgRemaining) { toast('You only have '+lgRemaining+' leads remaining. Reduce count.','error'); document.getElementById('lg-count').value=lgRemaining; return; }

    var btn=document.getElementById('lg-gen-btn');
    var loading=document.getElementById('lg-loading');
    var results=document.getElementById('lg-results-section');
    btn.disabled=true; btn.textContent='Generating...';
    loading.style.display='block';
    if (results) results.style.display='none';
    document.getElementById('lg-load-text').textContent='Searching Google Maps for "'+ind+'" in "'+loc+'"...';
    document.getElementById('lg-load-sub').textContent='Fetching phone numbers & website info for each result...';

    var fd=new FormData();
    fd.append('action','search'); fd.append('location',loc);
    fd.append('industry',ind); fd.append('count',cnt);
    fd.append('search_mode', lgSearchMode||'no_website');
    fetch('lead_generator_api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        btn.disabled=false; btn.textContent='Generate';
        loading.style.display='none';
        if (!d.ok) { toast(d.error||'Search failed','error'); return; }
        if (!d.leads||!d.leads.length) { toast(d.message||'No results found. Try different keywords.','info'); return; }
        renderResults(d.leads,ind,loc);
        if (d.used!==undefined) {
            var remLeads=Math.floor((d.budget-d.cost)/0.035);
            document.getElementById('lg-rem-leads').textContent='~'+Math.max(0,remLeads)+' leads remaining';
            document.getElementById('lg-sub').textContent=d.used+' / '+d.quota;
        }
        loadStats();
    })
    .catch(function(e){
        btn.disabled=false; btn.textContent='Generate';
        loading.style.display='none';
        toast('Network error. Check console.','error'); console.error(e);
    });
}

function renderResults(leads,ind,loc) {
    lgIds=leads.map(function(l){return l.id;});
    var nowebCount=leads.filter(function(l){return !l.has_website;}).length;
    document.getElementById('lg-res-label').textContent='('+leads.length+' results — '+ind+', '+loc
        +(nowebCount?' - '+nowebCount+' without website':'')+')';
    var costEst=(0.032+0.003*leads.length).toFixed(4);
    document.getElementById('lg-cost-summary').textContent='API cost for this search: ~$'+costEst;

    document.getElementById('lg-tbody').innerHTML=leads.map(function(l,i){
        var phone_btn=l.phone
            ? '<a href="tel:'+esc(l.phone)+'" class="lg-call-btn" title="Call '+esc(l.phone)+'">📞</a>'
            : '';
        var email_btn=l.email
            ? '<a href="mailto:'+esc(l.email)+'" class="lg-mail-btn" title="Email '+esc(l.email)+'">✉</a>'
            : '';
        var web_badge=l.has_website
            ? (l.website_found_by_crawler
                ? '<a href="'+esc(l.website)+'" target="_blank" class="lg-web-yes" title="Website found by deep search (not in Google Places)">🔍 Found ↗</a>'
                : '<a href="'+esc(l.website)+'" target="_blank" class="lg-web-yes">✅ Yes ↗</a>')
            : '<span class="lg-web-no" style="font-weight:800;font-size:12px">🔥 No</span>';
        var imp_btn=l.imported
            ? '<span class="lg-imp-done">✓ In CRM</span>'
            : '<button class="lg-imp-btn" onclick="impOne('+l.id+',this)" title="Import to CRM">⬇</button>';
        var stars=l.rating?'⭐ '+l.rating:'—';
        var name_cell='<div class="lg-name">'+esc(l.name)+'</div>'
            +(l.owner_name?'<div class="lg-owner">👤 '+esc(l.owner_name)+'</div>':'');
        var email_cell=l.email
            ? '<a href="mailto:'+esc(l.email)+'" class="lg-email-badge" title="'+esc(l.email)+'">'+esc(l.email)+'</a>'
            : '<span class="lg-no-data">—</span>';
        var phone_cell=l.phone
            ? '<span class="lg-phone">'+esc(l.phone)+'</span>'
            : '<span class="lg-no-data">—</span>';

        // Opportunity score badge
        var score=l.opportunity_score||0;
        var scoreColor=score>=70?'#10b981':score>=40?'#f59e0b':'#94a3b8';
        var scoreBg=score>=70?'rgba(16,185,129,.12)':score>=40?'rgba(245,158,11,.12)':'rgba(148,163,184,.1)';
        var scoreLabel=score>=70?'🔥 HOT':score>=40?'👍 Good':'·';
        var scoreBadge='<div style="text-align:center"><div style="font-size:16px;font-weight:900;color:'+scoreColor+'">'+score+'</div>'
            +'<div style="font-size:10px;font-weight:700;background:'+scoreBg+';color:'+scoreColor+';padding:1px 5px;border-radius:99px;white-space:nowrap">'+scoreLabel+'</div></div>';

        // Row highlight for no-website leads
        var rowStyle=!l.has_website?'background:rgba(16,185,129,.04);':'';

        return '<tr id="lgr-'+l.id+'" style="'+rowStyle+'">'
            +'<td>'+scoreBadge+'</td>'
            +'<td style="color:var(--text3);font-size:12px">'+(i+1)+'</td>'
            +'<td>'+name_cell+(l.why?'<div style="font-size:10.5px;color:#6366f1;margin-top:2px" title="'+esc(l.why)+'">💡 '+esc(l.why.length>60?l.why.slice(0,57)+'...':l.why)+'</div>':'')+'</td>'
            +'<td>'+phone_cell+'</td>'
            +'<td>'+email_cell+'</td>'
            +'<td><div class="lg-addr" title="'+esc(l.address)+'">'+esc(l.address||'—')+'</div></td>'
            +'<td>'+web_badge+'</td>'
            +'<td class="lg-rating">'+stars+(l.ratings_total?'<div style="font-size:10px;color:var(--text3)">'+l.ratings_total+' reviews</div>':'')+'</td>'
            +'<td><div style="display:flex;gap:4px;align-items:center">'+phone_btn+email_btn+' '+imp_btn+'</div></td>'
            +'</tr>';
    }).join('');

    document.getElementById('lg-results-section').style.display='block';
    document.getElementById('lg-results-section').scrollIntoView({behavior:'smooth',block:'start'});
}

function impOne(id, btn) {
    btn.disabled=true; btn.textContent='...';
    var fd=new FormData(); fd.append('action','import_lead'); fd.append('result_id',id);
    fetch('lead_generator_api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        if(d.ok){btn.replaceWith(Object.assign(document.createElement('span'),{className:'lg-imp-done',textContent:'✓ In CRM'}));toast(d.message,'success');}
        else{btn.disabled=false;btn.textContent='⬇';toast(d.error||'Failed','error');}
    });
}

function importAll() {
    var pending=lgIds.filter(function(id){var b=document.querySelector('#lgr-'+id+' .lg-imp-btn');return b&&!b.disabled;});
    if(!pending.length){toast('All leads already imported','info');return;}
    if(!confirm('Import '+pending.length+' leads to CRM pipeline?'))return;
    var btn=document.getElementById('lg-imp-all-btn');
    btn.disabled=true; btn.textContent='Importing...';
    var fd=new FormData(); fd.append('action','import_all'); fd.append('ids',pending.join(','));
    fetch('lead_generator_api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        btn.disabled=false; btn.textContent='⬇ Import All to CRM';
        if(d.ok){
            toast(d.imported+' leads added to CRM! 🎉','success');
            pending.forEach(function(id){var b=document.querySelector('#lgr-'+id+' .lg-imp-btn');if(b)b.replaceWith(Object.assign(document.createElement('span'),{className:'lg-imp-done',textContent:'✓ In CRM'}));});
        } else toast(d.error||'Import failed','error');
    });
}

function exportCSV() {
    if(!lgIds.length){toast('No data to export','error');return;}
    var fd=new FormData(); fd.append('action','export_csv'); fd.append('ids',lgIds.join(','));
    fetch('lead_generator_api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        if(!d.ok||!d.csv){toast('Export failed','error');return;}
        var a=document.createElement('a');
        a.href=URL.createObjectURL(new Blob([d.csv],{type:'text/csv'}));
        a.download='leads_'+new Date().toISOString().slice(0,10)+'.csv';
        a.click();
    });
}

var lgSearchMode='no_website';
function setMode(mode){
    lgSearchMode=mode;
    document.getElementById('lg-search-mode').value=mode;
    ['no_website','high_value','all'].forEach(function(m){
        var el=document.getElementById('lm-'+m);
        if(el) el.classList.toggle('mode-active',m===mode);
    });
}
function setIndustry(btn,val){
    var el=document.getElementById('lg-industry');
    if(el){el.value=val;el.focus();}
    // Highlight selected chip
    document.querySelectorAll('#industry-chips button,[onclick*="setIndustry"]').forEach(function(b){b.style.borderColor='var(--border)';b.style.color='var(--text2)';});
    if(btn){btn.style.borderColor='var(--orange)';btn.style.color='var(--orange)';}
}
// Activate no_website mode by default on load
document.addEventListener('DOMContentLoaded',function(){setMode('no_website');});
function toggleSettings(){
    var p=document.getElementById('lg-settings-panel'); if(!p)return;
    p.classList.toggle('open');
}

function saveSettings(){
    var key=document.getElementById('cfg-key')?.value.trim()||'';
    var quota=document.getElementById('cfg-quota')?.value||300;
    var budget=document.getElementById('cfg-budget')?.value||15;
    if (!key) { toast('Enter your Google API key','error'); return; }
    var fd=new FormData();
    fd.append('action','save_settings');fd.append('google_key',key);
    fd.append('quota',quota);fd.append('budget',budget);
    fetch('lead_generator_api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        if(d.ok){
            toast('Settings saved! Click "🔌 Test" to verify.','success');
            lgConfigured=true;
            document.getElementById('lg-no-api-banner').style.display='none';
            document.getElementById('cfg-key').value='';
            loadStats();
        } else toast(d.error||'Save failed','error');
    });
}

function testKey(){
    var btn=document.getElementById('cfg-test-btn');
    var res=document.getElementById('cfg-test-result');
    var key=document.getElementById('cfg-key')?.value.trim()||'';
    btn.disabled=true; btn.textContent='Testing...';
    if(res) res.style.display='none';
    var fd=new FormData(); fd.append('action','test_key'); if(key) fd.append('google_key',key);
    fetch('lead_generator_api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        btn.disabled=false; btn.textContent='🔌 Test';
        if(res){
            res.style.display='block';
            res.style.background=d.ok?'rgba(16,185,129,.08)':'rgba(239,68,68,.06)';
            res.style.border=d.ok?'1px solid rgba(16,185,129,.25)':'1px solid rgba(239,68,68,.2)';
            res.style.color=d.ok?'#10b981':'#ef4444';
            res.textContent=d.ok?d.message:d.error;
        }
    })
    .catch(function(){btn.disabled=false;btn.textContent='🔌 Test';});
}

function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function fmtAgo(dt){if(!dt)return'';var d=Math.floor((Date.now()-new Date(dt).getTime())/1000);if(d<60)return'just now';if(d<3600)return Math.floor(d/60)+'m ago';if(d<86400)return Math.floor(d/3600)+'h ago';return Math.floor(d/86400)+'d ago';}
function fmtDate(dt){if(!dt)return'';var d=new Date(dt);return['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][d.getMonth()]+' '+d.getDate()+', '+d.getFullYear();}

// ── LOAD SEARCH HISTORY (click recent search) ──
function loadSearchHistory(usageId){
    var loading=document.getElementById('lg-loading');
    var results=document.getElementById('lg-results-section');
    loading.style.display='block'; if(results) results.style.display='none';
    document.getElementById('lg-load-text').textContent='Loading previous search results...';
    document.getElementById('lg-load-sub').textContent='';
    fetch('lead_generator_api.php?action=get_search_history&usage_id='+usageId)
    .then(function(r){return r.json();})
    .then(function(d){
        loading.style.display='none';
        if(!d.ok){toast(d.error||'Failed to load','error');return;}
        if(!d.leads||!d.leads.length){toast('No leads found for this search','info');return;}
        renderResults(d.leads,d.industry,d.location);
        document.getElementById('lg-results-section').scrollIntoView({behavior:'smooth',block:'start'});
    })
    .catch(function(e){loading.style.display='none';toast('Network error','error');console.error(e);});
}

// ── SETTINGS TABS ──
function switchSettingsTab(tab) {
    ['api','quota'].forEach(function(t){
        var btn=document.getElementById('stab-'+t);
        var con=document.getElementById('stab-content-'+t);
        var active=t===tab;
        if(btn){btn.style.color=active?'var(--orange)':'var(--text3)';btn.style.borderBottomColor=active?'var(--orange)':'transparent';}
        if(con) con.style.display=active?'block':'none';
    });
    if(tab==='quota') loadQuotaConfig();
}
function loadQuotaConfig() {
    var tbody=document.getElementById('quota-users-tbody');
    if(tbody) tbody.innerHTML='<tr><td colspan="5" style="text-align:center;padding:16px;color:var(--text3)">Loading...</td></tr>';
    fetch('lead_generator_api.php?action=get_quota_config')
    .then(function(r){return r.json();})
    .then(function(d){
        if(!d.ok){if(tbody)tbody.innerHTML='<tr><td colspan="5" style="color:var(--text3);padding:12px">Failed</td></tr>';return;}
        var roles=d.roles||{};
        if(document.getElementById('qr-admin'))   document.getElementById('qr-admin').value   = roles.admin   ??300;
        if(document.getElementById('qr-manager')) document.getElementById('qr-manager').value = roles.manager ??100;
        if(document.getElementById('qr-user'))    document.getElementById('qr-user').value    = roles.user    ??30;
        renderQuotaUsers(d.users||[]);
    }).catch(function(){});
}
function renderQuotaUsers(users) {
    var tbody=document.getElementById('quota-users-tbody'); if(!tbody) return;
    if(!users.length){tbody.innerHTML='<tr><td colspan="5" style="text-align:center;padding:16px;color:var(--text3)">No users found</td></tr>';return;}
    tbody.innerHTML=users.map(function(u){
        var used=parseInt(u.used_leads)||0; var eq=parseInt(u.effective_quota)||0;
        var pct=eq>0?Math.min(100,Math.round(used/eq*100)):0;
        var barColor=pct>80?'#ef4444':pct>50?'#f59e0b':'var(--orange)';
        var roleBadge='<span class="badge-role badge-'+(u.role||'user')+'">'+esc(u.role||'user')+'</span>';
        var overrideVal=u.user_override!==null&&u.user_override!==undefined?u.user_override:'';
        var srcBadge=u.blocked?'<span class="badge-blocked">Blocked</span>'
            :u.quota_source==='user'?'<span class="badge-custom">Custom: '+eq+'</span>'
            :'<span style="font-size:11px;color:var(--text3)">Role: '+eq+'</span>';
        return '<tr>'
            +'<td><div style="font-weight:600;font-size:13px;color:var(--text)">'+esc(u.name||'')+'</div>'
                +'<div style="font-size:11px;color:var(--text3)">'+esc(u.email||'')+'</div></td>'
            +'<td>'+roleBadge+'</td>'
            +'<td><span style="font-weight:700;color:var(--text)">'+used+'</span> leads'
                +'<div class="qu-bar-wrap" style="margin-left:6px"><div class="qu-bar" style="width:'+pct+'%;background:'+barColor+'"></div></div></td>'
            +'<td>'+srcBadge+'</td>'
            +'<td><input type="number" name="user_quota['+u.id+']" class="qu-input" value="'+overrideVal
                +'" min="-1" max="5000" placeholder="inherit" title="Blank=role default, 0=block"></td>'
            +'</tr>';
    }).join('');
}
function saveQuotaConfig() {
    var fd=new FormData();
    fd.append('action','save_quota_config');
    fd.append('role_admin',  document.getElementById('qr-admin')?.value   ||300);
    fd.append('role_manager',document.getElementById('qr-manager')?.value ||100);
    fd.append('role_user',   document.getElementById('qr-user')?.value    ||30);
    document.querySelectorAll('input[name^="user_quota"]').forEach(function(inp){
        var m=inp.name.match(/\[(\d+)\]/); if(!m) return;
        fd.append('user_quota['+m[1]+']',inp.value);
    });
    var msg=document.getElementById('quota-save-msg');
    fetch('lead_generator_api.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        if(msg){msg.style.display='block';msg.style.color=d.ok?'#10b981':'#ef4444';msg.textContent=d.ok?'Saved!':d.error||'Failed';setTimeout(function(){msg.style.display='none';},3000);}
        if(d.ok){loadStats();loadQuotaConfig();toast('Quota settings saved','success');}
        else toast(d.error||'Save failed','error');
    }).catch(function(){toast('Network error','error');});
}

// ── COUNTRY / STATE / CITY CASCADE ──
var lgCountryData = {
  // ── SOUTH ASIA ──
  'India': {
    'Andhra Pradesh': ['Visakhapatnam','Vijayawada','Guntur','Nellore','Kurnool','Rajahmundry','Tirupati','Kadapa','Eluru','Ongole','Anantapur','Vizianagaram','Kakinada','Bhimavaram','Machilipatnam'],
    'Arunachal Pradesh': ['Itanagar','Naharlagun','Pasighat','Tezpur','Ziro'],
    'Assam': ['Guwahati','Silchar','Dibrugarh','Jorhat','Nagaon','Tezpur','Lakhimpur','Dhubri','Bongaigaon','Sivasagar','Tinsukia'],
    'Bihar': ['Patna','Gaya','Muzaffarpur','Bhagalpur','Darbhanga','Purnia','Arrah','Begusarai','Katihar','Bihar Sharif','Chapra','Sasaram','Hajipur','Siwan','Motihari'],
    'Chhattisgarh': ['Raipur','Bhilai','Bilaspur','Korba','Durg','Rajnandgaon','Raigarh','Jagdalpur','Ambikapur','Dhamtari'],
    'Goa': ['Panaji','Margao','Vasco da Gama','Mapusa','Ponda','Bicholim','Calangute','Candolim'],
    'Gujarat': ['Ahmedabad','Surat','Vadodara','Rajkot','Bhavnagar','Jamnagar','Junagadh','Gandhinagar','Anand','Navsari','Morbi','Mehsana','Surendranagar','Bharuch','Porbandar','Amreli','Valsad','Patan'],
    'Haryana': ['Faridabad','Gurgaon','Panipat','Ambala','Yamunanagar','Rohtak','Hisar','Karnal','Sonipat','Panchkula','Bhiwani','Bahadurgarh','Rewari','Sirsa','Palwal'],
    'Himachal Pradesh': ['Shimla','Dharamsala','Solan','Mandi','Kullu','Palampur','Baddi','Nahan','Kangra','Hamirpur'],
    'Jharkhand': ['Ranchi','Jamshedpur','Dhanbad','Bokaro','Deoghar','Phusro','Hazaribagh','Giridih','Ramgarh','Medininagar','Chaibasa'],
    'Karnataka': ['Bangalore','Mysore','Hubballi','Mangalore','Belgaum','Davanagere','Bellary','Shimoga','Tumkur','Bijapur','Gulbarga','Raichur','Bidar','Udupi','Chikkamagaluru','Hassan','Mandya','Kolar'],
    'Kerala': ['Thiruvananthapuram','Kochi','Kozhikode','Thrissur','Kollam','Malappuram','Palakkad','Alappuzha','Kannur','Kasaragod','Kottayam','Idukki','Wayanad','Pathanamthitta','Ernakulam'],
    'Madhya Pradesh': ['Bhopal','Indore','Jabalpur','Gwalior','Ujjain','Sagar','Ratlam','Satna','Dewas','Murwara','Chhindwara','Rewa','Singrauli','Burhanpur','Vidisha','Shivpuri'],
    'Maharashtra': ['Mumbai','Pune','Nagpur','Thane','Nashik','Aurangabad','Solapur','Kolhapur','Amravati','Nanded','Sangli','Akola','Jalgaon','Latur','Dhule','Ahmednagar','Malegaon','Chandrapur','Parbhani','Osmanabad','Ratnagiri'],
    'Manipur': ['Imphal','Thoubal','Bishnupur','Churachandpur','Ukhrul'],
    'Meghalaya': ['Shillong','Tura','Nongstoin','Jowai','Williamnagar'],
    'Mizoram': ['Aizawl','Lunglei','Saiha','Champhai'],
    'Nagaland': ['Kohima','Dimapur','Mokokchung','Tuensang'],
    'Odisha': ['Bhubaneswar','Cuttack','Rourkela','Brahmapur','Sambalpur','Puri','Balasore','Bhadrak','Baripada','Jharsuguda','Rayagada'],
    'Punjab': ['Ludhiana','Amritsar','Jalandhar','Patiala','Bathinda','Mohali','Hoshiarpur','Pathankot','Moga','Firozpur','Sangrur','Barnala'],
    'Rajasthan': ['Jaipur','Jodhpur','Kota','Bikaner','Ajmer','Udaipur','Bhilwara','Alwar','Sikar','Pali','Sri Ganganagar','Bharatpur','Chittorgarh','Jhunjhunu','Hanumangarh','Tonk'],
    'Sikkim': ['Gangtok','Namchi','Gyalshing','Mangan'],
    'Tamil Nadu': ['Chennai','Coimbatore','Madurai','Tiruchirappalli','Trichy','Salem','Tirunelveli','Erode','Vellore','Thoothukudi','Dindigul','Tiruppur','Thanjavur','Ranipet','Sivakasi','Karur','Namakkal','Kanchipuram','Kumbakonam','Nagapattinam','Lalgudi','Ariyalur','Chidambaram','Cuddalore','Dharmapuri','Krishnagiri','Perambalur','Villupuram','Kallakurichi','Nagercoil','Ooty','Kodaikanal','Tiruvarur','Mayiladuthurai','Virudhunagar','Sivaganga','Ramanathapuram','Pudukottai','Tirupattur','Tenkasi','Batticola','Srirangam','Palayamkottai','Ambattur','Avadi','Tambaram','Tiruvallur'],
    'Telangana': ['Hyderabad','Warangal','Nizamabad','Karimnagar','Ramagundam','Khammam','Mahbubnagar','Nalgonda','Adilabad','Suryapet','Siddipet','Mancherial'],
    'Tripura': ['Agartala','Dharmanagar','Udaipur','Kailasahar','Belonia'],
    'Uttar Pradesh': ['Lucknow','Kanpur','Agra','Varanasi','Meerut','Allahabad','Bareilly','Aligarh','Moradabad','Saharanpur','Gorakhpur','Noida','Ghaziabad','Firozabad','Mathura','Jhansi','Muzaffarnagar','Shahjahanpur','Rampur','Hapur','Bijnor','Etawah','Mainpuri','Rae Bareli','Sultanpur','Faizabad','Ayodhya','Sitapur','Hardoi','Lakhimpur'],
    'Uttarakhand': ['Dehradun','Haridwar','Roorkee','Haldwani','Kashipur','Rishikesh','Nainital','Mussoorie','Rudrapur','Pithoragarh'],
    'West Bengal': ['Kolkata','Asansol','Siliguri','Durgapur','Bardhaman','Malda','Baharampur','Habra','Kharagpur','Shantipur','Raiganj','Bally','Panihati','Kamarhati','Hugli'],
    'Delhi': ['New Delhi','Central Delhi','North Delhi','South Delhi','East Delhi','West Delhi','Dwarka','Rohini','Janakpuri','Laxmi Nagar','Saket','Karol Bagh','Connaught Place','Nehru Place'],
    'Chandigarh': ['Chandigarh','Sector 17','Sector 22','Sector 35','Mohali Industrial Area'],
    'Puducherry': ['Puducherry','Pondicherry','Karaikal','Mahe','Yanam'],
    'Jammu & Kashmir': ['Srinagar','Jammu','Anantnag','Baramulla','Sopore','Leh','Udhampur','Kathua'],
    'Ladakh': ['Leh','Kargil','Nubra'],
    'Andaman & Nicobar': ['Port Blair','Car Nicobar'],
    'Lakshadweep': ['Kavaratti','Agatti'],
    'Dadra & Nagar Haveli': ['Silvassa','Amli'],
    'Daman & Diu': ['Daman','Diu']
  },
  'Sri Lanka': {
    'Western Province': ['Colombo','Negombo','Kalutara','Panadura','Gampaha','Kelaniya','Dehiwala','Moratuwa','Homagama','Kaduwela','Wattala','Ja-Ela','Ragama'],
    'Central Province': ['Kandy','Matale','Nuwara Eliya','Gampola','Hatton','Dambulla','Nawalapitiya'],
    'Southern Province': ['Galle','Matara','Hambantota','Tangalle','Weligama','Ambalangoda','Hikkaduwa'],
    'Northern Province': ['Jaffna','Vavuniya','Kilinochchi','Mannar','Mullaitivu','Point Pedro','Chavakachcheri'],
    'Eastern Province': ['Batticaloa','Trincomalee','Ampara','Kalmunai','Akkaraipattu','Chenkalady','Valaichchenai'],
    'North Western Province': ['Kurunegala','Puttalam','Chilaw','Kuliyapitiya','Wariyapola'],
    'North Central Province': ['Anuradhapura','Polonnaruwa','Medirigiriya'],
    'Uva Province': ['Badulla','Monaragala','Bandarawela','Welimada'],
    'Sabaragamuwa Province': ['Ratnapura','Kegalle','Balangoda','Embilipitiya']
  },
  'Pakistan': {
    'Punjab': ['Lahore','Faisalabad','Rawalpindi','Gujranwala','Multan','Sialkot','Bahawalpur','Sargodha','Sheikhupura','Jhang','Rahim Yar Khan','Gujrat','Kasur','Okara','Wah Cantonment'],
    'Sindh': ['Karachi','Hyderabad','Sukkur','Larkana','Nawabshah','Mirpur Khas','Jacobabad','Shikarpur'],
    'Khyber Pakhtunkhwa': ['Peshawar','Mardan','Mingora','Abbottabad','Kohat','Bannu','Mansehra'],
    'Balochistan': ['Quetta','Turbat','Gwadar','Khuzdar','Hub'],
    'Azad Kashmir': ['Muzaffarabad','Mirpur','Rawalakot'],
    'Islamabad Capital Territory': ['Islamabad','F-6 Markaz','F-7 Markaz','G-9 Markaz','Blue Area']
  },
  'Bangladesh': {
    'Dhaka Division': ['Dhaka','Narayanganj','Gazipur','Mymensingh','Faridpur','Tangail','Manikganj','Munshiganj'],
    'Chittagong Division': ['Chittagong','Comilla','Coxs Bazar','Brahmanbaria','Feni','Noakhali','Chandpur','Lakshmipur'],
    'Rajshahi Division': ['Rajshahi','Bogura','Pabna','Natore','Naogaon','Sirajganj','Joypurhat'],
    'Sylhet Division': ['Sylhet','Moulvibazar','Habiganj','Sunamganj'],
    'Khulna Division': ['Khulna','Jessore','Satkhira','Bagerhat','Narail','Magura','Meherpur'],
    'Barisal Division': ['Barisal','Patuakhali','Bhola','Pirojpur','Jhalakati'],
    'Rangpur Division': ['Rangpur','Dinajpur','Kurigram','Gaibandha','Lalmonirhat','Nilphamari'],
    'Mymensingh Division': ['Mymensingh','Netrokona','Jamalpur','Sherpur']
  },
  'Nepal': {
    'Bagmati Province': ['Kathmandu','Lalitpur','Bhaktapur','Hetauda','Chitwan'],
    'Gandaki Province': ['Pokhara','Baglung','Gorkha','Kaski'],
    'Province No. 1': ['Biratnagar','Dharan','Itahari','Damak'],
    'Madhesh Province': ['Birgunj','Janakpur','Rajbiraj','Lahan'],
    'Lumbini Province': ['Butwal','Bhairahawa','Nepalgunj','Tulsipur'],
    'Karnali Province': ['Birendranagar','Jumla','Narayan'],
    'Sudurpashchim Province': ['Dhangadhi','Mahendranagar','Tikapur']
  },
  // ── SOUTHEAST ASIA ──
  'Singapore': {
    'Singapore': ['Orchard','Marina Bay','Raffles Place','City Hall','Clarke Quay','Chinatown','Little India','Bugis','Jurong East','Jurong West','Tampines','Woodlands','Yishun','Ang Mo Kio','Bedok','Bishan','Clementi','Toa Payoh','Geylang','Buona Vista','one-north','Changi','Pasir Ris','Punggol','Sengkang','Serangoon','Hougang']
  },
  'Malaysia': {
    'Selangor': ['Petaling Jaya','Shah Alam','Subang Jaya','Klang','Ampang','Sepang','Hulu Langat','Rawang','Banting','Semenyih','Puchong','Cyberjaya'],
    'Kuala Lumpur': ['KLCC','Bukit Bintang','Chow Kit','Bangsar','Mont Kiara','Cheras','Kepong','Wangsa Maju','Setapak','Titiwangsa','Desa Petaling','Sri Petaling'],
    'Penang': ['George Town','Butterworth','Bayan Lepas','Seberang Perai','Balik Pulau','Teluk Intan'],
    'Johor': ['Johor Bahru','Skudai','Pasir Gudang','Batu Pahat','Muar','Kluang','Segamat','Pontian'],
    'Perak': ['Ipoh','Taiping','Teluk Intan','Sitiawan','Lumut','Manjung'],
    'Negeri Sembilan': ['Seremban','Port Dickson','Nilai','Bahau'],
    'Pahang': ['Kuantan','Temerloh','Bentong','Raub','Mentakab'],
    'Kedah': ['Alor Setar','Sungai Petani','Kulim','Langkawi'],
    'Kelantan': ['Kota Bharu','Gua Musang','Pasir Mas'],
    'Terengganu': ['Kuala Terengganu','Kemaman','Dungun'],
    'Melaka': ['Melaka City','Alor Gajah','Jasin'],
    'Sabah': ['Kota Kinabalu','Sandakan','Tawau','Lahad Datu','Keningau'],
    'Sarawak': ['Kuching','Miri','Sibu','Bintulu','Limbang','Sarikei']
  },
  'Indonesia': {
    'Jakarta': ['Central Jakarta','North Jakarta','East Jakarta','South Jakarta','West Jakarta','Tangerang','Bekasi','Depok'],
    'West Java': ['Bandung','Bogor','Bekasi','Depok','Cimahi','Tasikmalaya','Cirebon','Sukabumi','Karawang'],
    'East Java': ['Surabaya','Malang','Pasuruan','Mojokerto','Madiun','Kediri','Blitar','Probolinggo'],
    'Central Java': ['Semarang','Solo','Yogyakarta','Magelang','Pekalongan','Tegal','Salatiga'],
    'Bali': ['Denpasar','Kuta','Seminyak','Ubud','Sanur','Nusa Dua','Singaraja','Gianyar'],
    'North Sumatra': ['Medan','Pematang Siantar','Binjai','Tebing Tinggi'],
    'South Sumatra': ['Palembang','Lubuklinggau','Prabumulih'],
    'Other Provinces': ['Makassar South Sulawesi','Manado North Sulawesi','Balikpapan Kalimantan','Samarinda Kalimantan']
  },
  'Thailand': {
    'Bangkok': ['Bangkok CBD','Sukhumvit','Silom','Siam','Chatuchak','Lat Phrao','Bang Na','Thonburi','Nonthaburi','Samut Prakan'],
    'Chiang Mai Province': ['Chiang Mai','Chiang Rai','Lamphun','Mae Rim'],
    'Phuket Province': ['Phuket Town','Patong','Kata','Karon','Rawai'],
    'Other Provinces': ['Pattaya Chonburi','Ayutthaya','Korat Nakhon Ratchasima','Khon Kaen','Udon Thani','Hat Yai Songkhla']
  },
  'Vietnam': {
    'Hanoi': ['Hoan Kiem','Ba Dinh','Dong Da','Hai Ba Trung','Tay Ho','Cau Giay','Thanh Xuan','Hoang Mai'],
    'Ho Chi Minh City': ['District 1','District 3','District 5','District 7','Binh Thanh','Thu Duc','Tan Binh','Go Vap'],
    'Da Nang': ['Hai Chau','Son Tra','Ngu Hanh Son','Cam Le','Thanh Khe'],
    'Other Cities': ['Can Tho','Hai Phong','Bien Hoa','Hue','Nha Trang','Da Lat','Vung Tau','Buon Ma Thuot']
  },
  'Philippines': {
    'Metro Manila': ['Makati','Taguig','Quezon City','Manila','Pasig','Mandaluyong','Marikina','Muntinlupa','Parañaque'],
    'Cebu': ['Cebu City','Mandaue','Lapu-Lapu','Talisay'],
    'Davao': ['Davao City','Tagum','Panabo'],
    'Other Regions': ['Iloilo City','Bacolod','Cagayan de Oro','General Santos','Angeles City','Antipolo']
  },
  'Cambodia': {
    'Phnom Penh': ['BKK1','Toul Kork','Daun Penh','Chamkarmon','Sen Sok','Meanchey'],
    'Siem Reap': ['Siem Reap City','Angkor Wat Area','Pub Street Area'],
    'Other Provinces': ['Sihanoukville','Battambang','Kampong Cham','Preah Vihear']
  },
  'Myanmar': {
    'Yangon Region': ['Yangon','Insein','Hlaing Tharyar','Dagon','Tamwe','Sanchaung'],
    'Mandalay Region': ['Mandalay','Pyin Oo Lwin','Meiktila'],
    'Other Regions': ['Naypyidaw','Bago','Mawlamyine','Pathein']
  },
  // ── MIDDLE EAST ──
  'UAE': {
    'Dubai': ['Dubai Marina','JBR','Deira','Bur Dubai','Jumeirah','Business Bay','Downtown Dubai','DIFC','Al Quoz','Al Barsha','Mirdif','International City','Jebel Ali','Dubai Silicon Oasis','Motor City','The Greens','JLT','TECOM','Al Karama','Oud Metha'],
    'Abu Dhabi': ['Abu Dhabi City','Al Ain','Mussafah','Khalifa City','Reem Island','Yas Island','Saadiyat Island','Al Raha','Mohammed Bin Zayed City'],
    'Sharjah': ['Sharjah City','Al Qasimia','Al Nahda','Al Majaz','Al Khan','Al Taawun','Muwaileh'],
    'Ajman': ['Ajman City','Al Nuaimia','Al Rashidiya'],
    'Ras Al Khaimah': ['RAK City','Al Hamra','Al Nakheel','Dafan Al Nakheel'],
    'Fujairah': ['Fujairah City','Dibba Al Fujairah'],
    'Umm Al Quwain': ['UAQ City','Falaj Al Mualla']
  },
  'Saudi Arabia': {
    'Riyadh Region': ['Riyadh','Al Kharj','Diriyah','Al Dawadmi','Zulfi'],
    'Makkah Region': ['Jeddah','Mecca','Taif','Rabigh','Al Jumum'],
    'Medina Region': ['Medina','Yanbu','Al Ula'],
    'Eastern Province': ['Dammam','Al Khobar','Dhahran','Qatif','Jubail','Hafr Al Batin'],
    'Other Regions': ['Tabuk','Abha','Hail','Buraidah','Najran','Jizan','Al Baha']
  },
  'Qatar': {
    'Doha': ['Doha CBD','West Bay','The Pearl','Al Sadd','Msheireb','Lusail','Al Wakrah'],
    'Other Areas': ['Al Rayyan','Al Khor','Al Wakrah','Dukhan','Mesaieed']
  },
  'Kuwait': {
    'Kuwait Governorate': ['Kuwait City','Salmiya','Hawalli','Rumaithiya'],
    'Farwaniya': ['Farwaniya','Khaitan','Reggae'],
    'Ahmadi': ['Ahmadi','Mangaf','Abu Halifa','Fahaheel'],
    'Mubarak Al-Kabeer': ['Sabah Al Salem','Mubarak Al Kabeer'],
    'Jahra': ['Jahra City','Sulaibikhat']
  },
  'Bahrain': {
    'Capital Governorate': ['Manama','Hoora','Seef','Juffair','Adliya'],
    'Northern Governorate': ['Muharraq','Hamad Town','Budaiya','Saar'],
    'Southern Governorate': ['Riffa','Zallaq','Awali']
  },
  'Oman': {
    'Muscat Governorate': ['Muscat','Ruwi','Muttrah','Qurum','Al Khuwair','Madinat Sultan Qaboos','Seeb','Al Amerat'],
    'Dhofar Governorate': ['Salalah'],
    'Other Governorates': ['Sohar','Nizwa','Sur','Ibri','Buraimi']
  },
  'Jordan': {
    'Amman Governorate': ['Amman','Zarqa','Madaba','Sahab'],
    'Irbid Governorate': ['Irbid','Ramtha'],
    'Other Governorates': ['Aqaba','Karak','Mafraq','Jerash']
  },
  'Lebanon': {
    'Beirut': ['Beirut','Achrafieh','Hamra','Verdun','Solidere'],
    'Mount Lebanon': ['Jounieh','Jdeideh','Metn','Baabda'],
    'Other Governorates': ['Tripoli','Sidon','Tyre','Zahle']
  },
  'Israel': {
    'Tel Aviv District': ['Tel Aviv','Ramat Gan','Givatayim','Petah Tikva','Bnei Brak'],
    'Jerusalem District': ['Jerusalem','Bethlehem Area'],
    'Haifa District': ['Haifa','Netanya','Caesarea'],
    'Other Districts': ['Beer Sheva','Eilat','Ashkelon','Herzliya','Kfar Saba']
  },
  'Turkey': {
    'Istanbul': ['Besiktas','Kadikoy','Sisli','Fatih','Uskudar','Beyoglu','Bakirkoy','Umraniye','Maltepe'],
    'Ankara': ['Cankaya','Kecioren','Mamak','Etimesgut'],
    'Other Cities': ['Izmir','Bursa','Antalya','Adana','Gaziantep','Konya','Kayseri','Mersin','Trabzon','Eskisehir']
  },
  // ── AFRICA ──
  'South Africa': {
    'Gauteng': ['Johannesburg','Pretoria','Sandton','Midrand','Centurion','Soweto','Ekurhuleni','Tembisa','Alberton'],
    'Western Cape': ['Cape Town','Stellenbosch','Paarl','George','Knysna','Hermanus'],
    'KwaZulu-Natal': ['Durban','Pietermaritzburg','Richards Bay','Newcastle','Pinetown'],
    'Eastern Cape': ['Port Elizabeth','East London','Queenstown','Mthatha'],
    'Other Provinces': ['Bloemfontein Free State','Polokwane Limpopo','Nelspruit Mpumalanga','Kimberley Northern Cape']
  },
  'Nigeria': {
    'Lagos State': ['Lagos Island','Victoria Island','Lekki','Ikeja','Surulere','Yaba','Apapa','Oshodi','Ikorodu'],
    'Abuja FCT': ['Wuse','Garki','Maitama','Asokoro','Gwarinpa','Kubwa'],
    'Kano State': ['Kano City','Fagge','Dala','Nassarawa'],
    'Other States': ['Ibadan Oyo','Port Harcourt Rivers','Enugu','Benin City Edo','Onitsha Anambra','Aba Abia','Kaduna','Zaria']
  },
  'Kenya': {
    'Nairobi County': ['Nairobi CBD','Westlands','Karen','Kilimani','Ngong Road','Thika Road','Eastleigh','South B','South C','Kasarani'],
    'Mombasa County': ['Mombasa','Nyali','Bamburi','Diani'],
    'Other Counties': ['Kisumu','Nakuru','Eldoret','Thika','Machakos','Meru','Kisii']
  },
  'Ethiopia': {
    'Addis Ababa': ['Bole','Kirkos','Lideta','Nifas Silk-Lafto','Yeka','Arada','Gulele'],
    'Other Regions': ['Dire Dawa','Mekelle','Gondar','Bahir Dar','Hawassa','Adama']
  },
  'Ghana': {
    'Greater Accra': ['Accra','Tema','Madina','Spintex','East Legon','Osu','Labadi','La'],
    'Ashanti Region': ['Kumasi','Obuasi','Ejisu'],
    'Other Regions': ['Takoradi Western','Cape Coast Central','Tamale Northern','Sunyani Brong Ahafo']
  },
  'Egypt': {
    'Cairo Governorate': ['Cairo','Heliopolis','Nasr City','Maadi','New Cairo','Zamalek','Dokki','Mohandessin'],
    'Giza Governorate': ['Giza','6th of October City','Sheikh Zayed','Haram'],
    'Other Governorates': ['Alexandria','Luxor','Aswan','Sharm El Sheikh','Hurghada','Mansoura','Tanta']
  },
  'Tanzania': {
    'Dar es Salaam': ['Ilala','Kinondoni','Temeke','Kigamboni','Ubungo'],
    'Other Regions': ['Mwanza','Arusha','Moshi','Dodoma','Zanzibar','Tanga','Mbeya']
  },
  'Uganda': {
    'Central Region': ['Kampala','Entebbe','Wakiso','Mukono','Jinja'],
    'Other Regions': ['Gulu','Mbarara','Mbale','Masaka','Fort Portal']
  },
  // ── EUROPE ──
  'United Kingdom': {
    'England': ['London','Manchester','Birmingham','Liverpool','Leeds','Sheffield','Bristol','Newcastle','Nottingham','Southampton','Leicester','Coventry','Bradford','Plymouth','Derby','Reading','Luton','Portsmouth','Norwich','Swindon','Bournemouth','Exeter','Stoke-on-Trent','Wolverhampton','Kingston upon Hull','Middlesbrough','Oxford','Cambridge','Bath','Brighton','Milton Keynes'],
    'Scotland': ['Edinburgh','Glasgow','Aberdeen','Dundee','Inverness','Perth','Stirling','St Andrews'],
    'Wales': ['Cardiff','Swansea','Newport','Wrexham','Bangor','St Davids'],
    'Northern Ireland': ['Belfast','Derry','Lisburn','Newry','Armagh','Omagh']
  },
  'Germany': {
    'Bavaria': ['Munich','Nuremberg','Augsburg','Regensburg','Ingolstadt','Erlangen','Fuerth','Wuerzburg'],
    'Berlin': ['Berlin Mitte','Charlottenburg','Prenzlauer Berg','Kreuzberg','Friedrichshain','Schoeneberg','Tempelhof'],
    'Hamburg': ['Hamburg City','Altona','Eimsbüttel','Barmbek','Blankenese'],
    'North Rhine-Westphalia': ['Cologne','Dusseldorf','Dortmund','Essen','Bochum','Wuppertal','Bielefeld','Bonn','Muenster','Duisburg','Oberhausen','Aachen'],
    'Baden-Württemberg': ['Stuttgart','Mannheim','Karlsruhe','Freiburg','Heidelberg','Ulm','Heilbronn','Pforzheim'],
    'Other States': ['Frankfurt Hesse','Leipzig Saxony','Dresden Saxony','Hannover Lower Saxony','Bremen','Magdeburg Saxony-Anhalt','Erfurt Thuringia','Kiel Schleswig-Holstein','Mainz Rhineland-Palatinate','Saarbruecken Saarland']
  },
  'France': {
    'Île-de-France': ['Paris 1st','Paris 8th','Paris 16th','Boulogne-Billancourt','Saint-Denis','Argenteuil','Montreuil','Vincennes','Neuilly-sur-Seine','Levallois-Perret','Nanterre','La Défense'],
    'Auvergne-Rhône-Alpes': ['Lyon','Grenoble','Saint-Etienne','Villeurbanne','Clermont-Ferrand','Annecy','Chambéry'],
    'Provence-Alpes-Côte dAzur': ['Marseille','Nice','Aix-en-Provence','Toulon','Antibes','Cannes','Monaco'],
    'Other Regions': ['Toulouse Occitanie','Bordeaux Nouvelle-Aquitaine','Nantes Loire','Strasbourg Alsace','Lille Nord','Rennes Brittany','Montpellier']
  },
  'Spain': {
    'Community of Madrid': ['Madrid','Alcalá de Henares','Leganés','Getafe','Alcorcón','Móstoles','Fuenlabrada'],
    'Catalonia': ['Barcelona','Hospitalet de Llobregat','Badalona','Sabadell','Terrassa','Girona'],
    'Andalusia': ['Seville','Málaga','Córdoba','Granada','Almería','Cádiz','Huelva','Jaén'],
    'Other Regions': ['Valencia','Bilbao Basque Country','Zaragoza Aragon','Palma Mallorca','Las Palmas Gran Canaria','Murcia']
  },
  'Italy': {
    'Lombardy': ['Milan','Bergamo','Brescia','Monza','Como','Pavia','Lecco','Cremona'],
    'Lazio': ['Rome','Viterbo','Frosinone','Latina','Rieti'],
    'Campania': ['Naples','Salerno','Caserta','Benevento','Avellino'],
    'Other Regions': ['Turin Piedmont','Florence Tuscany','Bologna Emilia-Romagna','Palermo Sicily','Catania Sicily','Bari Puglia','Venice Veneto','Genoa Liguria','Verona','Padua']
  },
  'Netherlands': {
    'North Holland': ['Amsterdam','Haarlem','Zaandam','Purmerend','Alkmaar'],
    'South Holland': ['Rotterdam','The Hague','Leiden','Delft','Dordrecht'],
    'Other Provinces': ['Utrecht','Eindhoven North Brabant','Groningen','Maastricht Limburg','Arnhem Gelderland','Enschede Overijssel','Tilburg North Brabant']
  },
  'Belgium': {
    'Brussels': ['Brussels','Ixelles','Saint-Gilles','Schaerbeek','Anderlecht'],
    'Flanders': ['Antwerp','Ghent','Bruges','Leuven','Mechelen','Hasselt'],
    'Wallonia': ['Liège','Namur','Charleroi','Mons','Tournai']
  },
  'Switzerland': {
    'Zurich Canton': ['Zurich','Winterthur','Uster','Dübendorf'],
    'Geneva Canton': ['Geneva','Carouge','Vernier'],
    'Other Cantons': ['Basel','Bern','Lausanne','Lucerne','St. Gallen','Lugano']
  },
  'Sweden': {
    'Stockholm County': ['Stockholm','Solna','Sundbyberg','Huddinge','Nacka','Täby'],
    'Other Counties': ['Gothenburg Västra Götaland','Malmö Skåne','Uppsala','Linköping','Örebro','Helsingborg','Norrköping']
  },
  'Norway': {
    'Oslo': ['Oslo','Bærum','Asker','Lillestrøm'],
    'Other Counties': ['Bergen Vestland','Trondheim Trøndelag','Stavanger Rogaland','Tromsø','Fredrikstad']
  },
  'Denmark': {
    'Capital Region': ['Copenhagen','Frederiksberg','Gentofte','Gladsaxe'],
    'Other Regions': ['Aarhus','Odense','Aalborg','Esbjerg','Randers']
  },
  'Finland': {
    'Uusimaa': ['Helsinki','Espoo','Vantaa','Tampere Area'],
    'Other Regions': ['Tampere','Turku','Oulu','Lahti','Kuopio','Jyväskylä']
  },
  'Poland': {
    'Masovian Voivodeship': ['Warsaw','Radom','Płock','Siedlce'],
    'Lesser Poland': ['Krakow','Tarnów','Nowy Sącz'],
    'Other Voivodeships': ['Wroclaw','Lodz','Poznan','Gdansk','Szczecin','Bydgoszcz','Katowice','Lublin','Rzeszow','Bialystok']
  },
  'Portugal': {
    'Lisbon District': ['Lisbon','Sintra','Cascais','Oeiras','Amadora'],
    'Porto District': ['Porto','Gaia','Matosinhos','Braga'],
    'Other Districts': ['Faro Algarve','Coimbra','Aveiro','Setúbal','Funchal Madeira']
  },
  'Greece': {
    'Attica': ['Athens','Piraeus','Peristeri','Kallithea','Glyfada','Kifissia'],
    'Central Macedonia': ['Thessaloniki','Kavala','Serres'],
    'Other Regions': ['Heraklion Crete','Patras','Larissa','Volos','Ioannina','Rhodes']
  },
  'Russia': {
    'Moscow': ['Moscow City','Central Administrative Okrug','Northern','Eastern','Western','South-Western'],
    'Saint Petersburg': ['Saint Petersburg','Petrodvorets','Pushkin','Kolpino'],
    'Other Regions': ['Novosibirsk','Yekaterinburg','Nizhny Novgorod','Kazan','Chelyabinsk','Samara','Omsk','Rostov-on-Don','Ufa','Krasnoyarsk','Perm','Voronezh']
  },
  'Ukraine': {
    'Kyiv City': ['Kyiv','Brovary','Boryspil','Irpin'],
    'Kharkiv Oblast': ['Kharkiv'],
    'Other Oblasts': ['Lviv','Odessa','Dnipro','Donetsk','Zaporizhzhia','Mykolaiv','Cherkasy','Poltava','Chernihiv']
  },
  // ── EAST ASIA ──
  'China': {
    'Beijing': ['Beijing CBD','Chaoyang','Haidian','Xicheng','Dongcheng','Shunyi','Tongzhou'],
    'Shanghai': ['Pudong','Jingan','Huangpu','Xuhui','Changning','Minhang','Songjiang','Qingpu'],
    'Guangdong': ['Guangzhou','Shenzhen','Dongguan','Foshan','Zhuhai','Zhongshan','Huizhou','Jiangmen'],
    'Jiangsu': ['Nanjing','Suzhou','Wuxi','Changzhou','Nantong','Xuzhou'],
    'Zhejiang': ['Hangzhou','Ningbo','Wenzhou','Jinhua','Shaoxing'],
    'Other Provinces': ['Chengdu Sichuan','Wuhan Hubei','Xian Shaanxi','Chongqing','Shenyang Liaoning','Dalian Liaoning','Tianjin','Qingdao Shandong','Jinan Shandong','Zhengzhou Henan','Kunming Yunnan','Nanchang Jiangxi','Changsha Hunan','Fuzhou Fujian','Xiamen Fujian','Harbin Heilongjiang','Changchun Jilin']
  },
  'Japan': {
    'Tokyo Metropolis': ['Shinjuku','Shibuya','Minato','Chiyoda','Chuo','Akihabara','Harajuku','Roppongi','Asakusa','Ikebukuro','Shinagawa','Shiodome','Ginza','Odaiba'],
    'Osaka Prefecture': ['Osaka','Namba','Umeda','Shinsaibashi','Tennoji','Sakai','Higashiosaka'],
    'Kanagawa Prefecture': ['Yokohama','Kawasaki','Sagamihara','Kamakura','Fujisawa'],
    'Aichi Prefecture': ['Nagoya','Toyohashi','Toyota','Okazaki'],
    'Other Prefectures': ['Sapporo Hokkaido','Fukuoka','Kobe Hyogo','Kyoto','Sendai Miyagi','Hiroshima','Okayama','Matsuyama Ehime','Takamatsu Kagawa']
  },
  'South Korea': {
    'Seoul': ['Gangnam','Mapo','Jongno','Jung','Yongsan','Songpa','Seodaemun','Seocho','Nowon','Dobong'],
    'Gyeonggi-do': ['Suwon','Seongnam','Goyang','Bucheon','Ansan','Anyang','Yongin','Hwaseong'],
    'Other Cities': ['Busan','Incheon','Daegu','Daejeon','Gwangju','Ulsan','Jeju']
  },
  'Taiwan': {
    'Taipei City': ['Zhongzheng','Daan','Xinyi','Zhongshan','Songshan','Neihu','Beitou','Nangang'],
    'New Taipei City': ['Banqiao','Xinzhuang','Zhonghe','Yonghe','Tucheng'],
    'Other Cities': ['Taichung','Kaohsiung','Tainan','Taoyuan','Hsinchu','Keelung']
  },
  'Hong Kong': {
    'Hong Kong Island': ['Central','Admiralty','Wan Chai','Causeway Bay','Happy Valley','North Point','Quarry Bay','Chai Wan','Aberdeen','Repulse Bay'],
    'Kowloon': ['Tsim Sha Tsui','Mong Kok','Yau Ma Tei','Jordan','Kowloon City','Wong Tai Sin','Kwun Tong'],
    'New Territories': ['Sha Tin','Tuen Mun','Yuen Long','Tai Po','Fan Ling','Sai Kung','Tseung Kwan O']
  },
  // ── NORTH AMERICA ──
  'United States': {
    'California': ['Los Angeles','San Francisco','San Diego','San Jose','Sacramento','Fresno','Long Beach','Oakland','Bakersfield','Anaheim','Santa Ana','Riverside','Stockton','Chula Vista','Irvine','Fremont','San Bernardino','Modesto','Fontana','Moreno Valley','Santa Clarita','Glendale','Oxnard','Huntington Beach','Garden Grove','Silicon Valley','Palo Alto','Santa Monica','Beverly Hills'],
    'Texas': ['Houston','Dallas','San Antonio','Austin','Fort Worth','El Paso','Arlington','Corpus Christi','Plano','Laredo','Lubbock','Garland','Irving','Amarillo','Grand Prairie','McKinney','Frisco','Mesquite','Pasadena TX','Killeen'],
    'New York': ['New York City','Manhattan','Brooklyn','Queens','Bronx','Staten Island','Buffalo','Rochester','Yonkers','Syracuse','Albany','New Rochelle','Mount Vernon','Schenectady'],
    'Florida': ['Miami','Orlando','Tampa','Jacksonville','St. Petersburg','Tallahassee','Fort Lauderdale','Hialeah','Pembroke Pines','Hollywood','Miramar','Gainesville','Coral Springs','Cape Coral','Miami Beach','Clearwater','Palm Bay','West Palm Beach','Lakeland','Pompano Beach'],
    'Illinois': ['Chicago','Aurora','Rockford','Joliet','Naperville','Springfield','Peoria','Elgin','Waukegan','Champaign','Bloomington','Decatur'],
    'Pennsylvania': ['Philadelphia','Pittsburgh','Allentown','Erie','Reading','Scranton','Bethlehem'],
    'Ohio': ['Columbus','Cleveland','Cincinnati','Toledo','Akron','Dayton','Parma'],
    'Georgia': ['Atlanta','Augusta','Columbus GA','Savannah','Athens','Sandy Springs','Roswell','Macon'],
    'North Carolina': ['Charlotte','Raleigh','Greensboro','Durham','Winston-Salem','Fayetteville','Cary'],
    'Michigan': ['Detroit','Grand Rapids','Warren','Sterling Heights','Ann Arbor','Lansing','Flint'],
    'Other States': ['Seattle WA','Denver CO','Nashville TN','Boston MA','Las Vegas NV','Portland OR','Phoenix AZ','Minneapolis MN','Baltimore MD','Washington DC','Louisville KY','Memphis TN','Virginia Beach VA','Albuquerque NM','Tucson AZ','Oklahoma City OK','Kansas City MO','Omaha NE','Colorado Springs CO','New Orleans LA','Honolulu HI','Anchorage AK','Salt Lake City UT','Reno NV','Boise ID']
  },
  'Canada': {
    'Ontario': ['Toronto','Ottawa','Mississauga','Brampton','Hamilton','London ON','Markham','Vaughan','Kitchener','Windsor','Waterloo','Richmond Hill','Oakville','Burlington','Oshawa','Barrie'],
    'Quebec': ['Montreal','Quebec City','Laval','Gatineau','Longueuil','Sherbrooke','Saguenay'],
    'British Columbia': ['Vancouver','Surrey','Burnaby','Richmond','Kelowna','Abbotsford','Kamloops','Victoria','Langley','Delta'],
    'Alberta': ['Calgary','Edmonton','Red Deer','Lethbridge','Airdrie','St. Albert','Medicine Hat'],
    'Other Provinces': ['Winnipeg Manitoba','Halifax Nova Scotia','Saskatoon Saskatchewan','Regina Saskatchewan','St. Johns Newfoundland','Fredericton New Brunswick','Moncton New Brunswick','Charlottetown PEI']
  },
  'Mexico': {
    'Mexico City (CDMX)': ['Polanco','Condesa','Roma Norte','Santa Fe','Coyoacán','Xochimilco','Tlalpan','Iztapalapa'],
    'Jalisco': ['Guadalajara','Zapopan','Tlaquepaque','Tonalá','Puerto Vallarta'],
    'Nuevo León': ['Monterrey','San Pedro Garza García','San Nicolás','Apodaca','Escobedo'],
    'Other States': ['Puebla','Tijuana BC','Cancún Quintana Roo','Mérida Yucatán','Querétaro','León Guanajuato','San Luis Potosí','Aguascalientes','Hermosillo Sonora','Chihuahua','Culiacán Sinaloa','Acapulco Guerrero','Veracruz','Oaxaca']
  },
  // ── SOUTH AMERICA ──
  'Brazil': {
    'São Paulo State': ['São Paulo','Guarulhos','Campinas','São Bernardo do Campo','Santo André','Osasco','Sorocaba','Mauá','Ribeirão Preto','Santos'],
    'Rio de Janeiro State': ['Rio de Janeiro','São Gonçalo','Duque de Caxias','Nova Iguaçu','Niterói','Petrópolis'],
    'Minas Gerais': ['Belo Horizonte','Uberlândia','Contagem','Juiz de Fora','Betim','Montes Claros'],
    'Other States': ['Salvador Bahia','Fortaleza Ceará','Curitiba Paraná','Manaus Amazonas','Recife Pernambuco','Porto Alegre RS','Belém Pará','Goiânia Goiás','Florianópolis SC','Natal RN','Teresina Piauí','Campo Grande MS','Cuiabá MT','Maceió AL','Aracaju SE']
  },
  'Argentina': {
    'Buenos Aires': ['Buenos Aires CBD','Palermo','Recoleta','San Isidro','Tigre','La Plata','Quilmes','Lanús','Lomas de Zamora'],
    'Córdoba Province': ['Córdoba','Villa Carlos Paz','Río Cuarto'],
    'Other Provinces': ['Rosario Santa Fe','Mendoza','Tucumán','Mar del Plata','Salta','San Juan','Neuquén','Resistencia Chaco']
  },
  'Colombia': {
    'Bogotá': ['Bogotá CBD','Chapinero','Usaquén','Suba','Engativá','Bosa','Kennedy'],
    'Antioquia': ['Medellín','El Poblado','Laureles','Envigado','Bello','Itagüí'],
    'Other Departments': ['Cali Valle del Cauca','Barranquilla Atlántico','Cartagena Bolivar','Bucaramanga','Manizales Caldas','Pereira Risaralda','Cúcuta Norte de Santander']
  },
  'Chile': {
    'Santiago Metropolitan': ['Santiago Centro','Las Condes','Providencia','Vitacura','Ñuñoa','La Florida','Maipú','Pudahuel'],
    'Other Regions': ['Valparaíso','Concepción','La Serena','Antofagasta','Temuco','Iquique','Rancagua','Talca']
  },
  'Peru': {
    'Lima Province': ['Lima Centro','Miraflores','San Isidro','Surco','La Molina','San Miguel','Barranco'],
    'Other Regions': ['Arequipa','Trujillo','Chiclayo','Piura','Iquitos','Cusco']
  },
  // ── OCEANIA ──
  'Australia': {
    'New South Wales': ['Sydney CBD','North Sydney','Parramatta','Newcastle','Wollongong','Penrith','Liverpool','Blacktown','Campbelltown','Gosford','Central Coast','Wagga Wagga','Albury','Port Macquarie'],
    'Victoria': ['Melbourne CBD','St Kilda','Fitzroy','Prahran','Geelong','Ballarat','Bendigo','Shepparton','Melton','Dandenong','Frankston','Knox'],
    'Queensland': ['Brisbane CBD','Fortitude Valley','South Bank','Gold Coast','Sunshine Coast','Townsville','Cairns','Toowoomba','Rockhampton','Mackay','Bundaberg','Hervey Bay'],
    'Western Australia': ['Perth CBD','Fremantle','Joondalup','Stirling','Swan','Mandurah','Bunbury','Geraldton','Kalgoorlie'],
    'South Australia': ['Adelaide CBD','Norwood','Unley','Marion','Onkaparinga','Mount Gambier','Whyalla'],
    'Tasmania': ['Hobart','Launceston','Devonport','Burnie'],
    'ACT': ['Canberra','Civic','Kingston','Manuka','Belconnen'],
    'Northern Territory': ['Darwin','Palmerston','Alice Springs']
  },
  'New Zealand': {
    'Auckland Region': ['Auckland CBD','North Shore','Waitakere','Manukau','Papakura','East Auckland','West Auckland'],
    'Wellington Region': ['Wellington','Porirua','Hutt Valley','Upper Hutt'],
    'Other Regions': ['Christchurch Canterbury','Hamilton Waikato','Tauranga Bay of Plenty','Napier-Hastings Hawkes Bay','Dunedin Otago','Palmerston North Manawatū','Rotorua Bay of Plenty']
  }
};
function populateCountryDropdown() {
    var sel = document.getElementById('lg-country'); if (!sel) return;
    var priority = ['India','Sri Lanka','UAE','Singapore','Malaysia','United Kingdom','Australia','United States','Canada','Germany','Bangladesh','Pakistan','Saudi Arabia','Qatar','Kuwait','Bahrain','Oman','Thailand','Indonesia','Philippines','Nigeria','South Africa','Kenya'];
    var all = Object.keys(lgCountryData).sort();
    var others = all.filter(function(c){return priority.indexOf(c)===-1;});
    sel.innerHTML = '<option value="">Select Country</option>';
    sel.innerHTML += '<optgroup label="Commonly Used">' + priority.filter(function(c){return lgCountryData[c];}).map(function(c){return '<option value="'+esc(c)+'">'+esc(c)+'</option>';}).join('') + '</optgroup>';
    if(others.length) sel.innerHTML += '<optgroup label="All Countries">' + others.map(function(c){return '<option value="'+esc(c)+'">'+esc(c)+'</option>';}).join('') + '</optgroup>';
}
function onCountryChange(country) {
    var stateSel=document.getElementById('lg-state');
    var citySel=document.getElementById('lg-city');
    stateSel.innerHTML='<option value="">Select State / Province</option>';
    citySel.innerHTML='<option value="">Select City / District</option>';
    stateSel.disabled=true; citySel.disabled=true;
    if(country&&lgCountryData[country]) {
        Object.keys(lgCountryData[country]).sort().forEach(function(s){
            var o=document.createElement('option'); o.value=s; o.textContent=s; stateSel.appendChild(o);
        });
        stateSel.disabled=false;
    }
    syncLocation();
}
function onStateChange(state) {
    var country=document.getElementById('lg-country').value;
    var citySel=document.getElementById('lg-city');
    citySel.innerHTML='<option value="">Select City / District / Town</option>';
    citySel.disabled=true;
    if(state&&country&&lgCountryData[country]&&lgCountryData[country][state]) {
        lgCountryData[country][state].forEach(function(c){
            var o=document.createElement('option'); o.value=c; o.textContent=c; citySel.appendChild(o);
        });
        citySel.disabled=false;
    }
    syncLocation();
}
function onCityChange() { syncLocation(); }
function syncLocation() {
    var city    = document.getElementById('lg-city')?.value    || '';
    var state   = document.getElementById('lg-state')?.value   || '';
    var country = document.getElementById('lg-country')?.value || '';
    var custom  = document.getElementById('lg-location');
    if (!custom) return;
    if (custom.dataset.manualEdit === '1') return; // user typed manually — don't override
    var parts = [];
    if (city)         parts.push(city);
    else if (state)   parts.push(state);
    if (country)      parts.push(country);
    custom.value = parts.join(', ');
}

// ── INDUSTRY AUTOCOMPLETE ──
var lgIndData = [
    {g:'Web & Technology', items:['Web development','Mobile app development','Software company','IT services','Digital marketing agency','E-commerce store','Cybersecurity firm','Cloud services','AI company','SEO agency','App development','Website design','IT consulting','Data analytics','ERP software','SaaS company','EdTech company','FinTech company']},
    {g:'Hospitality & Food', items:['Hotel','Boutique hotel','Resort','Guest house','Restaurant','Restaurant chain','Fine dining restaurant','Fast food restaurant','Cafe','Coffee shop','Bar and lounge','Bakery','Catering service','Food delivery','Cloud kitchen','Ice cream parlor','Sweet shop','Dhaba','Canteen']},
    {g:'Healthcare & Medical', items:['Hospital','Private hospital','Private clinic','Dental clinic','Eye clinic','Skin clinic','Orthopedic clinic','Pediatric clinic','Pharmacy','Pharmacy chain','Medical equipment dealer','Diagnostic lab','Pathology lab','Physiotherapy center','Ayurvedic clinic','Nursing home','Medical college']},
    {g:'Education & Training', items:['Private school','International school','CBSE school','Montessori school','College','Engineering college','Medical college','University','Coaching center','Tuition center','IIT coaching','NEET coaching','MBA coaching','Language institute','Driving school','Vocational training','Daycare center','Yoga institute','Music school']},
    {g:'Retail & Shopping', items:['Supermarket chain','Grocery store','Departmental store','Clothing store','Fashion boutique','Saree shop','Jewellery store','Gold jewellery shop','Furniture store','Electronics store','Mobile phone store','Computer shop','Hardware store','Book store','Stationery shop','Gift shop','Toy store','Sports shop','Shoe store','Cosmetics store']},
    {g:'Professional Services', items:['Law firm','Lawyer','Chartered accountant','Accounting firm','Tax consultant','Financial advisor','Insurance agency','Real estate agency','Property dealer','Architecture firm','Interior design firm','Civil engineering firm','Consulting firm','HR consultancy','Recruitment agency','PR agency','Translation services']},
    {g:'Construction & Real Estate', items:['Construction company','Building contractor','Civil contractor','Real estate developer','Apartment builder','Interior contractor','Renovation services','Plumbing services','Electrical contractor','Painting services','Waterproofing company','Roofing company','Flooring company','Fabrication works']},
    {g:'Manufacturing & Industry', items:['Manufacturing company','Textile company','Garment factory','Food processing company','Packaging company','Printing press','Chemical company','Pharmaceutical manufacturer','Plastic products','Metal fabrication','Steel company','Rubber products','Paper products','Furniture manufacturer','Handicrafts','Export company','Trading company']},
    {g:'Automotive', items:['Car workshop','Automobile dealer','Car dealer','Two-wheeler dealer','Truck dealer','Used car dealer','Driving school','Car rental','Auto parts store','Tire shop','Car wash','Fuel station','Car accessories']},
    {g:'Finance & Banking', items:['Bank','Private bank','Microfinance company','Money exchange','Investment firm','Stock broker','Loan agency','Chit fund','Financial planning','Insurance company']},
    {g:'Logistics & Transport', items:['Logistics company','Courier service','Freight company','Cargo company','Transport company','Packers and movers','Warehousing','Cold storage','Taxi service','Travel agency','Tour operator']},
    {g:'Fitness & Wellness', items:['Fitness center','Gym','Ladies gym','Yoga studio','Spa','Massage center','Beauty salon','Hair salon','Barbershop','Nail salon','Wellness center','Slimming center','Swimming pool']},
    {g:'Events & Entertainment', items:['Event management','Wedding planner','Birthday party planner','Photography studio','Wedding photographer','Videography','DJ service','Sound and light rental','Tent house','Flower decoration','Band and orchestra']},
    {g:'Media & Advertising', items:['Advertising agency','Branding agency','Graphic design studio','Video production','Animation studio','Printing and signage','Social media agency','Content writing','Photography','Radio station']},
    {g:'Agriculture & Farm', items:['Farm','Organic farm','Dairy farm','Poultry farm','Fish farm','Agri equipment dealer','Seed company','Pesticide dealer','Fertilizer dealer','Rice mill','Flour mill','Oil mill','Spice company']},
    {g:'Small Business', items:['Small Businesses','Retail shop','Local store','Family business','Micro enterprise','Home business','Freelancer','Self employed','Cottage industry']}
];
var lgIndFlat = [];
lgIndData.forEach(function(g){g.items.forEach(function(item){lgIndFlat.push({group:g.g,label:item,search:item.toLowerCase()});});});

function lgIndInput(inp) {
    var q=inp.value.trim().toLowerCase();
    var drop=document.getElementById('lg-ind-drop'); if(!drop) return;
    if(!q||q.length<1) {
        var html='<div class="lg-ac-group">Popular Categories</div>';
        ['Web development','Hotel','Restaurant chain','Hospital','Private school','Real estate agency','Manufacturing company','Automobile dealer','Fitness center','Construction company','Pharmacy chain','Jewellery store'].forEach(function(item,i){
            html+='<div class="lg-ac-item" onclick="lgIndSelect(\''+esc(item)+'\')" data-idx="'+i+'"><span class="ac-label">'+esc(item)+'</span></div>';
        });
        drop.innerHTML=html; drop.style.display='block'; lgIndIdx=-1; return;
    }
    var matches=lgIndFlat.filter(function(x){return x.search.indexOf(q)!==-1;});
    if(!matches.length){drop.style.display='none';return;}
    var grouped={};
    matches.slice(0,30).forEach(function(m){if(!grouped[m.group])grouped[m.group]=[];grouped[m.group].push(m);});
    var html=''; var gi=0;
    Object.keys(grouped).forEach(function(g){
        html+='<div class="lg-ac-group">'+esc(g)+'</div>';
        grouped[g].forEach(function(m){html+='<div class="lg-ac-item" onclick="lgIndSelect(\''+esc(m.label)+'\')" data-idx="'+gi+'"><span class="ac-label">'+lgHighlight(m.label,q)+'</span></div>';gi++;});
    });
    drop.innerHTML=html; drop.style.display='block'; lgIndIdx=-1;
}
function lgIndSelect(val){document.getElementById('lg-industry').value=val;lgIndHide();}
function lgIndHide(){var d=document.getElementById('lg-ind-drop');if(d)d.style.display='none';}
var lgIndIdx=-1;
function lgIndKey(e){
    var drop=document.getElementById('lg-ind-drop');if(!drop||drop.style.display==='none')return;
    var items=drop.querySelectorAll('.lg-ac-item');if(!items.length)return;
    if(e.key==='ArrowDown'){e.preventDefault();lgIndIdx=Math.min(lgIndIdx+1,items.length-1);}
    else if(e.key==='ArrowUp'){e.preventDefault();lgIndIdx=Math.max(lgIndIdx-1,0);}
    else if(e.key==='Enter'&&lgIndIdx>=0){e.preventDefault();items[lgIndIdx].click();return;}
    else if(e.key==='Escape'){lgIndHide();return;}
    items.forEach(function(it,i){it.classList.toggle('active',i===lgIndIdx);});
    if(lgIndIdx>=0)items[lgIndIdx].scrollIntoView({block:'nearest'});
}
function lgHighlight(text,q){
    var idx=text.toLowerCase().indexOf(q); if(idx<0)return esc(text);
    return esc(text.slice(0,idx))+'<mark>'+esc(text.slice(idx,idx+q.length))+'</mark>'+esc(text.slice(idx+q.length));
}
document.addEventListener('click',function(e){
    if(!e.target.closest||(!e.target.closest('#lg-industry')&&!e.target.closest('#lg-ind-drop')))lgIndHide();
});

// lg-location manual edit flag
document.addEventListener('DOMContentLoaded',function(){
    var locInp=document.getElementById('lg-location');
    if(locInp){
        locInp.addEventListener('input',function(){
            this.dataset.manualEdit=this.value?'1':'0';
        });
        locInp.addEventListener('focus',function(){
            // Clear flag when they clear the field
            if(!this.value)this.dataset.manualEdit='0';
        });
    }
});

var lgTotalStored=0, lgCurrentPage=1, lgPerPage=50;
</script>

<?php renderLayoutEnd(); ?>