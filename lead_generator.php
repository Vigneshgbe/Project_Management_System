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
.lg-ring-card{background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:var(--radius-lg);padding:18px;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:160px;border:none}
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
.lg-search-row{display:grid;grid-template-columns:1fr 1fr 80px auto;gap:10px;align-items:end}
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
#lm-no_website.mode-active{background:rgba(16,185,129,.08)!important;border-color:#10b981!important}
#lm-high_value.mode-active{background:rgba(249,115,22,.06)!important;border-color:var(--orange)!important}
#lm-all.mode-active{background:var(--bg4)!important;border-color:var(--text3)!important}
/* ── MANAGE SECTION ── */
.lg-manage-section{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;margin-bottom:16px}
.lg-filter-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--border)}
.lg-filter-input{padding:7px 12px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13px;min-width:160px}
.lg-filter-select{padding:7px 12px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13px;cursor:pointer}
.lg-owner{font-size:12px;color:var(--text3);margin-top:2px}
.lg-email-badge{background:rgba(99,102,241,.1);color:#6366f1;border:1px solid rgba(99,102,241,.25);padding:2px 8px;border-radius:99px;font-size:11px;font-weight:600;white-space:nowrap;text-decoration:none}
.lg-mail-btn{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;background:#6366f1;color:#fff;border-radius:50%;text-decoration:none;font-size:12px;border:none;cursor:pointer;flex-shrink:0;transition:opacity .15s}
.lg-mail-btn:hover{opacity:.8}
.lg-no-data{color:var(--text3);font-size:12px;font-style:italic}
</style>

<!-- TOP STATS ROW -->
<div class="lg-top" id="lg-top-row">
  <!-- Usage ring -->
  <div class="lg-ring-card">
    <div class="lg-ring-title">Monthly Usage</div>
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

<!-- SETTINGS PANEL (admin only) -->
<?php if (isAdmin()): ?>
<div class="lg-settings" id="lg-settings-panel">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div style="font-size:13.5px;font-weight:700">⚙ Lead Generator Settings</div>
    <button onclick="toggleSettings()" class="btn btn-ghost btn-sm">✕ Close</button>
  </div>

  <!-- Setup guide -->
  <div style="background:var(--bg2);border-radius:var(--radius-sm);padding:14px;margin-bottom:16px">
    <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:10px">How to get your Google Places API key (already paid ₹1,000 — use that credit):</div>
    <div class="lg-setup-step">
      <div class="lg-step-num">1</div>
      <div><h4>Go to Google Cloud Console</h4><p>Open <a href="https://console.cloud.google.com/" target="_blank">console.cloud.google.com</a> → Your project "Leads-Generator"</p></div>
    </div>
    <div class="lg-setup-step">
      <div class="lg-step-num">2</div>
      <div><h4>Enable Places API</h4><p>Left menu → <strong>APIs & Services</strong> → <strong>Library</strong> → Search <code>Places API</code> → Click it → Click <strong>Enable</strong></p></div>
    </div>
    <div class="lg-setup-step">
      <div class="lg-step-num">3</div>
      <div><h4>Create API Key</h4><p>APIs & Services → <strong>Credentials</strong> → <strong>+ Create Credentials</strong> → <strong>API key</strong> → Copy the key shown</p></div>
    </div>
    <div class="lg-setup-step">
      <div class="lg-step-num">4</div>
      <div><h4>Restrict Key (important!)</h4><p>Click the key → <strong>API restrictions</strong> → Restrict to <strong>Places API</strong> only → Save. This prevents accidental usage of other APIs.</p></div>
    </div>
    <div style="margin-top:10px;padding:10px 12px;background:rgba(16,185,129,.08);border-radius:var(--radius-sm);font-size:12px;color:#10b981">
      ✅ Your ₹1,000 prepaid = ~₹16,000 equivalent free credit. At $0.035/lead, you can generate <strong>~4,500 leads</strong> before the credit runs out. Our monthly limit protects you from ever exceeding it.
    </div>
  </div>

  <!-- Settings form -->
  <div style="display:grid;grid-template-columns:1fr 130px 130px auto;gap:10px;align-items:end">
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Google Places API Key</label>
      <input type="password" id="cfg-key" class="lg-input" placeholder="AIzaSy..." autocomplete="off">
    </div>
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Monthly Quota</label>
      <input type="number" id="cfg-quota" class="lg-input" value="300" min="10" max="5000">
      <div style="font-size:10px;color:var(--text3);margin-top:2px">Max leads/month</div>
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

  <!-- SEARCH MODE -->
  <div style="margin-bottom:14px">
    <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Search Mode</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <label id="lm-no_website" onclick="setMode('no_website')" style="display:flex;align-items:flex-start;gap:8px;padding:9px 14px;background:rgba(16,185,129,.08);border:2px solid #10b981;border-radius:var(--radius-sm);cursor:pointer;transition:all .15s">
        <div><div style="font-size:13px;font-weight:700;color:#10b981">🔥 No Website Only</div><div style="font-size:11px;color:var(--text3)">Prime prospects — need a website built</div></div>
      </label>
      <label id="lm-high_value" onclick="setMode('high_value')" style="display:flex;align-items:flex-start;gap:8px;padding:9px 14px;background:var(--bg3);border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:all .15s">
        <div><div style="font-size:13px;font-weight:700;color:var(--orange)">💎 High Value</div><div style="font-size:11px;color:var(--text3)">Established — bigger budgets (₹40k+)</div></div>
      </label>
      <label id="lm-all" onclick="setMode('all')" style="display:flex;align-items:flex-start;gap:8px;padding:9px 14px;background:var(--bg3);border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:all .15s">
        <div><div style="font-size:13px;font-weight:700;color:var(--text2)">📋 All Results</div><div style="font-size:11px;color:var(--text3)">All businesses sorted by score</div></div>
      </label>
    </div>
    <input type="hidden" id="lg-search-mode" value="no_website">
  </div>

  <!-- HOT INDUSTRY SHORTCUTS -->
  <div style="margin-bottom:14px">
    <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:7px">🎯 Quick Targets <span style="text-transform:none;letter-spacing:0;font-weight:400;color:var(--text3)">(high-value clients — click to fill)</span></div>
    <div style="display:flex;gap:5px;flex-wrap:wrap">
      <?php foreach (['Hotel','Restaurant chain','Hospital','Private school','Manufacturing company','Real estate agency','Supermarket chain','Automobile dealer','Jewellery store','Event hall','Fitness center','Pharmacy chain','Construction company','Catering service','Interior design firm','Architecture firm'] as $hi): ?>
      <button type="button" onclick="setIndustry(this,'<?= h($hi) ?>')"
        style="padding:4px 10px;background:var(--bg3);border:1px solid var(--border);border-radius:99px;font-size:12px;cursor:pointer;color:var(--text2);transition:all .12s"
        onmouseover="this.style.borderColor='var(--orange)';this.style.color='var(--orange)'"
        onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text2)'"><?= h($hi) ?></button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Cost estimate preview -->
  <div id="lg-cost-preview" style="margin-bottom:12px;font-size:12.5px;color:var(--text3)">
    Each search costs approximately <strong>$0.032</strong> (text search) + <strong>$0.003 × leads</strong> (phone/website lookup).
    At 5 leads: ~$0.047. Monthly limit protects you automatically.
  </div>

  <div class="lg-search-row">
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">City / Location</label>
      <input type="text" id="lg-location" class="lg-input" placeholder="e.g. Colombo, Batticaloa, Chennai, London">
    </div>
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Industry / Business Type</label>
      <input type="text" id="lg-industry" class="lg-input" placeholder="e.g. Web development, Restaurant, Law firm">
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

<!-- MANAGE ALL STORED LEADS -->
<?php if (isManager()): ?>
<div class="lg-manage-section" id="lg-manage-section" style="display:none">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
    <div>
      <div style="font-size:14px;font-weight:700;font-family:var(--font-display)">📚 All Stored Leads</div>
      <div style="font-size:12px;color:var(--text3);margin-top:2px" id="lg-manage-count">Loading...</div>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="bulkDeleteSelected()" class="btn btn-danger btn-sm" id="lg-bulk-delete-btn" disabled>🗑 Delete Selected</button>
      <button onclick="toggleManageSection()" class="btn btn-ghost btn-sm">✕ Close</button>
    </div>
  </div>
  <div class="lg-filter-bar">
    <input type="text" id="filter-search" class="lg-filter-input" placeholder="Search name, email, phone..." onkeyup="filterStoredLeads()">
    <select id="filter-location" class="lg-filter-select" onchange="filterStoredLeads()"><option value="">All Locations</option></select>
    <select id="filter-industry" class="lg-filter-select" onchange="filterStoredLeads()"><option value="">All Industries</option></select>
    <select id="filter-website" class="lg-filter-select" onchange="filterStoredLeads()">
      <option value="">All</option><option value="0">🔥 No Website</option><option value="1">Has Website</option>
    </select>
    <select id="filter-imported" class="lg-filter-select" onchange="filterStoredLeads()">
      <option value="">All</option><option value="0">Not Imported</option><option value="1">Imported to CRM</option>
    </select>
    <button onclick="resetFilters()" class="btn btn-ghost btn-sm">🔄 Reset</button>
  </div>
  <div style="overflow-x:auto">
    <table class="lg-tbl">
      <thead>
        <tr>
          <th><input type="checkbox" id="select-all-leads" onchange="toggleSelectAll(this)"></th>
          <th>Score</th><th>Business Name / Owner</th><th>Phone</th><th>Email</th>
          <th>Location</th><th>Industry</th><th>Website</th><th>Rating</th><th>Date</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody id="lg-stored-tbody">
        <tr><td colspan="12" style="text-align:center;padding:24px;color:var(--text3)">Loading...</td></tr>
      </tbody>
    </table>
  </div>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
    <div style="font-size:12px;color:var(--text3)" id="lg-pagination-info"></div>
    <div style="display:flex;gap:4px;margin-left:auto" id="lg-pagination-btns"></div>
  </div>
</div>
<div style="margin-bottom:16px">
  <button onclick="toggleManageSection()" class="btn btn-ghost" id="lg-show-manage-btn">
    📚 View All Stored Leads (<span id="lg-total-stored">0</span>)
  </button>
</div>
<?php endif; ?>

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
var lgIds=[], lgConfigured=false, lgQuota=300, lgBudget=15;

document.addEventListener('DOMContentLoaded', function() {
    loadStats();
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
        'This search: <strong>$'+cost+'</strong> (1 text search $0.032 + '+cnt+' detail lookups × $0.003). '+
        'Monthly limit of $'+lgBudget+' protects you automatically.';
}

function loadStats() {
    fetch('lead_generator_api.php?action=get_stats')
    .then(function(r){return r.json();})
    .then(function(d){
        if (!d.ok) return;
        lgConfigured = d.api_set;
        lgQuota      = d.quota || 300;
        lgBudget     = d.budget || 15;

        // No-API banner
        var banner = document.getElementById('lg-no-api-banner');
        if (banner) banner.style.display = d.api_set ? 'none' : 'flex';

        // Ring
        var pct = lgQuota > 0 ? Math.min(100, Math.round(d.used/lgQuota*100)) : 0;
        document.getElementById('lg-pct').textContent = pct+'%';
        document.getElementById('lg-sub').textContent = d.used+' / '+lgQuota;
        var circ=2*Math.PI*42;
        var fill=document.getElementById('lg-ring');
        fill.setAttribute('stroke-dasharray', circ.toFixed(1));
        fill.setAttribute('stroke-dashoffset', (circ-(pct/100*circ)).toFixed(1));
        // Ring color by usage
        fill.style.stroke = pct>80?'#f59e0b':pct>95?'#ef4444':'#fff';

        // Cost meter
        var cpct = lgBudget>0 ? Math.min(100,Math.round(d.cost/lgBudget*100)) : 0;
        document.getElementById('lg-cost-used').textContent  = d.cost.toFixed(4);
        document.getElementById('lg-cost-limit').textContent = lgBudget.toFixed(2);
        document.getElementById('lg-cost-fill').style.width  = cpct+'%';
        document.getElementById('lg-cost-fill').style.background =
            cpct>80 ? '#ef4444' : cpct>60 ? '#f59e0b' : '#10b981';

        var remLeads = Math.floor((lgBudget - d.cost) / 0.035);
        document.getElementById('lg-rem-leads').textContent = '~'+Math.max(0,remLeads)+' leads remaining this month';

        // Budget status
        var bs = document.getElementById('lg-budget-status');
        if (bs) {
            if (cpct > 80)      bs.innerHTML='<div class="budget-danger">⚠ Budget '+(cpct>95?'CRITICAL':'WARNING')+': '+cpct+'% used</div>';
            else if (cpct > 50) bs.innerHTML='<div class="budget-warn">📊 Budget '+cpct+'% used — OK</div>';
            else                bs.innerHTML='<div class="budget-ok">✅ Budget healthy — '+cpct+'% used</div>';
        }

        // Quota bar
        var qb=document.getElementById('lg-quota-bar-row');
        if (qb) {
            qb.innerHTML='<div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text3);margin-bottom:4px"><span>Monthly quota: '+d.used+' / '+lgQuota+' leads used</span><span style="color:'+((lgQuota-d.used)<20?'#ef4444':'var(--text3)')+'">'+Math.max(0,lgQuota-d.used)+' remaining</span></div>'
                +'<div style="height:6px;background:var(--bg4);border-radius:99px;overflow:hidden"><div style="height:100%;border-radius:99px;background:'+(pct>80?'#ef4444':pct>50?'#f59e0b':'var(--orange)')+';width:'+pct+'%;transition:width .4s"></div></div>';
        }

        // Prefill settings if admin
        if (document.getElementById('cfg-quota')) document.getElementById('cfg-quota').value = lgQuota;
        if (document.getElementById('cfg-budget')) document.getElementById('cfg-budget').value = lgBudget;

        lgTotalStored = d.total_all||0;
        var ts = document.getElementById('lg-total-stored');
        if (ts) ts.textContent = lgTotalStored;

        renderRecent(d.recent||[]);
        updateCostPreview();
    })
    .catch(function(){});
}

function renderRecent(data) {
    var el = document.getElementById('lg-recent');
    if (!el) return;
    if (!data.length) { el.innerHTML='<div style="color:var(--text3);font-size:12.5px">No searches yet</div>'; return; }
    var colors=['#4f46e5','#10b981','#f97316','#8b5cf6','#f59e0b','#14b8a6','#6366f1','#ef4444'];
    el.innerHTML = data.map(function(r,i){
        var cost = parseFloat(r.estimated_cost||0).toFixed(4);
        return '<div class="lg-act" onclick="loadSearchHistory('+r.id+')" title="Click to view these leads"><div class="lg-act-dot" style="background:'+colors[i%colors.length]+'"></div>'
            +'<div><div style="font-size:12.5px;color:var(--text2);font-weight:600">'+esc(r.result_count)+' leads · '+esc(r.industry)+' in '+esc(r.location)+'</div>'
            +'<div style="font-size:11px;color:var(--text3)">$'+cost+' cost · '+fmtAgo(r.created_at)+'</div></div></div>';
    }).join('');
}

function doSearch() {
    var loc=document.getElementById('lg-location').value.trim();
    var ind=document.getElementById('lg-industry').value.trim();
    var cnt=parseInt(document.getElementById('lg-count').value)||5;
    if (!loc) { toast('Enter a location','error'); document.getElementById('lg-location').focus(); return; }
    if (!ind) { toast('Enter an industry','error'); document.getElementById('lg-industry').focus(); return; }
    if (!lgConfigured) { toast('Configure Google API key first','error'); toggleSettings(); return; }

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
        +(nowebCount?' · 🔥 '+nowebCount+' without website':'')+')';
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
            ? '<a href="'+esc(l.website)+'" target="_blank" class="lg-web-yes">✅ Yes ↗</a>'
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

// ── MANAGE ALL STORED LEADS ──
var lgCurrentPage=1, lgPerPage=50, lgTotalStored=0;

function toggleManageSection(){
    var sec=document.getElementById('lg-manage-section'); if(!sec) return;
    var btn=document.getElementById('lg-show-manage-btn');
    if(sec.style.display==='none'||sec.style.display===''){
        sec.style.display='block'; loadAllStoredLeads(1);
        if(btn) btn.textContent='✕ Close Stored Leads';
    } else {
        sec.style.display='none';
        if(btn) btn.textContent='📚 View All Stored Leads ('+lgTotalStored+')';
    }
}

function loadAllStoredLeads(page){
    page=page||1; lgCurrentPage=page;
    var search=document.getElementById('filter-search')?.value||'';
    var location=document.getElementById('filter-location')?.value||'';
    var industry=document.getElementById('filter-industry')?.value||'';
    var website=document.getElementById('filter-website')?.value||'';
    var imported=document.getElementById('filter-imported')?.value||'';
    var url='lead_generator_api.php?action=get_all_stored&page='+page+'&per_page='+lgPerPage;
    if(search)   url+='&search='+encodeURIComponent(search);
    if(location) url+='&location='+encodeURIComponent(location);
    if(industry) url+='&industry='+encodeURIComponent(industry);
    if(website)  url+='&website='+website;
    if(imported) url+='&imported='+imported;
    var tbody=document.getElementById('lg-stored-tbody');
    if(tbody) tbody.innerHTML='<tr><td colspan="12" style="text-align:center;padding:24px;color:var(--text3)">Loading...</td></tr>';
    fetch(url)
    .then(function(r){return r.json();})
    .then(function(d){
        if(!d.ok){toast(d.error||'Failed','error');return;}
        renderStoredLeadsTable(d.leads);
        renderPagination(d.total,d.page,d.per_page);
        populateFilters(d.locations,d.industries);
        var c=document.getElementById('lg-manage-count');
        if(c) c.textContent='Total: '+d.total+' leads stored';
        lgTotalStored=d.total;
        var ts=document.getElementById('lg-total-stored'); if(ts) ts.textContent=d.total;
    })
    .catch(function(e){toast('Network error: '+e.message,'error');});
}

function renderStoredLeadsTable(leads){
    var tbody=document.getElementById('lg-stored-tbody'); if(!tbody) return;
    if(!leads||!leads.length){
        tbody.innerHTML='<tr><td colspan="12" style="text-align:center;padding:36px"><div style="font-size:28px;margin-bottom:8px">📭</div><div style="color:var(--text3)">No leads found</div></td></tr>';
        return;
    }
    tbody.innerHTML=leads.map(function(l,idx){
        var rowNum=((lgCurrentPage-1)*lgPerPage)+idx+1;
        var score=l.opportunity_score||0;
        var sc=score>=70?'#10b981':score>=40?'#f59e0b':'#94a3b8';
        var scoreBadge='<div style="text-align:center"><div style="font-size:15px;font-weight:800;color:'+sc+'">'+score+'</div></div>';
        var web_badge=l.has_website
            ? '<a href="'+esc(l.website)+'" target="_blank" class="lg-web-yes" style="font-size:10px">✅ Yes ↗</a>'
            : '<span class="lg-web-no" style="font-size:10px;font-weight:800">🔥 No</span>';
        var status=l.imported
            ? '<span class="lg-imp-done" style="font-size:10px">✓ Imported</span>'
            : '<span style="font-size:10px;color:var(--text3)">Not imported</span>';
        var stars=l.rating?'⭐ '+l.rating:'—';
        var email_cell=l.email
            ? '<a href="mailto:'+esc(l.email)+'" style="font-size:11px;color:#6366f1" title="'+esc(l.email)+'">'+esc(l.email)+'</a>'
            : '<span style="color:var(--text3);font-size:11px">—</span>';
        var name_cell='<div style="font-weight:700;font-size:13px;color:var(--text)">'+esc(l.name)+'</div>'
            +(l.owner_name?'<div style="font-size:11px;color:var(--text3)">👤 '+esc(l.owner_name)+'</div>':'');
        return '<tr style="'+(l.has_website?'':'background:rgba(16,185,129,.03)')+'">'
            +'<td><input type="checkbox" class="lead-select" data-id="'+l.id+'"></td>'
            +'<td>'+scoreBadge+'</td>'
            +'<td>'+name_cell+'</td>'
            +'<td style="font-size:12px">'+(l.phone?esc(l.phone):'<span style="color:var(--text3)">—</span>')+'</td>'
            +'<td>'+email_cell+'</td>'
            +'<td style="font-size:12px;color:var(--text3)">'+esc(l.location||'—')+'</td>'
            +'<td style="font-size:12px;color:var(--text3)">'+esc(l.industry||'—')+'</td>'
            +'<td>'+web_badge+'</td>'
            +'<td style="font-size:12px">'+stars+(l.ratings_total?'<div style="font-size:10px;color:var(--text3)">'+l.ratings_total+'</div>':'')+'</td>'
            +'<td style="font-size:11px;color:var(--text3)">'+fmtDate(l.created_at)+'</td>'
            +'<td>'+status+'</td>'
            +'<td><div style="display:flex;gap:4px">'
                +(l.imported?''
                    :'<button onclick="impOne('+l.id+',this)" class="lg-imp-btn" style="width:24px;height:24px;font-size:10px" title="Import">⬇</button>')
                +'<button onclick="deleteSingleLead('+l.id+')" class="btn btn-ghost btn-sm btn-icon" style="width:24px;height:24px;padding:4px;font-size:11px" title="Delete">🗑</button>'
            +'</div></td>'
            +'</tr>';
    }).join('');
    updateSelectAllState();
}

function renderPagination(total,currentPage,perPage){
    var totalPages=Math.ceil(total/perPage);
    var info=document.getElementById('lg-pagination-info');
    var btns=document.getElementById('lg-pagination-btns');
    if(!info||!btns) return;
    var start=((currentPage-1)*perPage)+1, end=Math.min(currentPage*perPage,total);
    info.textContent='Showing '+start+'–'+end+' of '+total;
    if(totalPages<=1){btns.innerHTML='';return;}
    var html='';
    if(currentPage>1) html+='<button onclick="loadAllStoredLeads('+(currentPage-1)+')" class="btn btn-ghost btn-sm">← Prev</button>';
    var s=Math.max(1,currentPage-2),e=Math.min(totalPages,currentPage+2);
    if(s>1){html+='<button onclick="loadAllStoredLeads(1)" class="btn btn-ghost btn-sm">1</button>';if(s>2) html+='<span style="padding:5px 8px;color:var(--text3)">...</span>';}
    for(var i=s;i<=e;i++){
        if(i===currentPage) html+='<button class="btn btn-sm" style="background:var(--orange);color:#fff">'+i+'</button>';
        else html+='<button onclick="loadAllStoredLeads('+i+')" class="btn btn-ghost btn-sm">'+i+'</button>';
    }
    if(e<totalPages){if(e<totalPages-1) html+='<span style="padding:5px 8px;color:var(--text3)">...</span>';html+='<button onclick="loadAllStoredLeads('+totalPages+')" class="btn btn-ghost btn-sm">'+totalPages+'</button>';}
    if(currentPage<totalPages) html+='<button onclick="loadAllStoredLeads('+(currentPage+1)+')" class="btn btn-ghost btn-sm">Next →</button>';
    btns.innerHTML=html;
}

function populateFilters(locations,industries){
    var locSel=document.getElementById('filter-location');
    var indSel=document.getElementById('filter-industry');
    if(locSel&&locations&&locations.length){var cv=locSel.value;locSel.innerHTML='<option value="">All Locations</option>'+locations.map(function(l){return'<option value="'+esc(l)+'">'+esc(l)+'</option>';}).join('');locSel.value=cv;}
    if(indSel&&industries&&industries.length){var cv=indSel.value;indSel.innerHTML='<option value="">All Industries</option>'+industries.map(function(i){return'<option value="'+esc(i)+'">'+esc(i)+'</option>';}).join('');indSel.value=cv;}
}

function filterStoredLeads(){loadAllStoredLeads(1);}
function resetFilters(){
    ['filter-search','filter-location','filter-industry','filter-website','filter-imported'].forEach(function(id){var el=document.getElementById(id);if(el)el.value='';});
    loadAllStoredLeads(1);
}

function toggleSelectAll(cb){
    document.querySelectorAll('.lead-select').forEach(function(c){c.checked=cb.checked;});
    updateBulkDeleteButton();
}
function updateSelectAllState(){
    var sa=document.getElementById('select-all-leads');
    var all=document.querySelectorAll('.lead-select');
    var chk=document.querySelectorAll('.lead-select:checked').length;
    if(!sa) return;
    sa.checked=chk>0&&chk===all.length; sa.indeterminate=chk>0&&chk<all.length;
    updateBulkDeleteButton();
}
function updateBulkDeleteButton(){
    var btn=document.getElementById('lg-bulk-delete-btn'); if(!btn) return;
    var sel=document.querySelectorAll('.lead-select:checked');
    btn.disabled=sel.length===0;
    btn.textContent='🗑 Delete Selected'+(sel.length>0?' ('+sel.length+')':'');
}
document.addEventListener('change',function(e){if(e.target.classList.contains('lead-select')) updateSelectAllState();});

function bulkDeleteSelected(){
    var sel=Array.from(document.querySelectorAll('.lead-select:checked')).map(function(cb){return cb.dataset.id;});
    if(!sel.length){toast('No leads selected','info');return;}
    if(!confirm('Delete '+sel.length+' lead(s)? Cannot be undone.')) return;
    var btn=document.getElementById('lg-bulk-delete-btn');
    btn.disabled=true; btn.textContent='Deleting...';
    var fd=new FormData(); fd.append('action','bulk_delete'); fd.append('ids',sel.join(','));
    fetch('lead_generator_api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        if(d.ok){toast(d.deleted+' lead(s) deleted','success');loadAllStoredLeads(lgCurrentPage);loadStats();}
        else{toast(d.error||'Delete failed','error');btn.disabled=false;updateBulkDeleteButton();}
    })
    .catch(function(e){toast('Network error','error');btn.disabled=false;updateBulkDeleteButton();});
}

function deleteSingleLead(id){
    if(!confirm('Delete this lead?')) return;
    var fd=new FormData(); fd.append('action','bulk_delete'); fd.append('ids',id);
    fetch('lead_generator_api.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        if(d.ok){toast('Lead deleted','success');loadAllStoredLeads(lgCurrentPage);loadStats();}
        else toast(d.error||'Delete failed','error');
    });
}
</script>

<?php renderLayoutEnd(); ?>