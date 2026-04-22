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
/* ── LEAD GENERATOR STYLES ── */
.lg-grid{display:grid;grid-template-columns:300px 1fr 280px;gap:18px;margin-bottom:20px}
.lg-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px}

/* Usage ring */
.lg-ring-card{background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);border:none;color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:180px}
.lg-ring-wrap{position:relative;width:110px;height:110px;margin:10px auto}
.lg-ring-svg{transform:rotate(-90deg)}
.lg-ring-bg{fill:none;stroke:rgba(255,255,255,.2);stroke-width:8}
.lg-ring-fill{fill:none;stroke:#fff;stroke-width:8;stroke-linecap:round;transition:stroke-dashoffset .6s ease}
.lg-ring-text{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;line-height:1.2}
.lg-ring-pct{font-size:22px;font-weight:800;color:#fff}
.lg-ring-sub{font-size:11px;color:rgba(255,255,255,.7)}
.lg-ring-title{font-size:12px;color:rgba(255,255,255,.8);text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px}

/* Trend chart card */
.lg-chart-title{font-size:12.5px;font-weight:600;color:var(--text2);margin-bottom:12px}

/* Recent activity card */
.lg-act-item{display:flex;align-items:flex-start;gap:8px;padding:7px 0;border-bottom:1px solid var(--border)}
.lg-act-item:last-child{border-bottom:none}
.lg-act-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:5px}
.lg-act-text{font-size:12.5px;color:var(--text2);line-height:1.4}
.lg-act-time{font-size:10.5px;color:var(--text3);margin-top:2px}

/* Generator form */
.lg-form-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;margin-bottom:20px}
.lg-form-title{font-size:15px;font-weight:700;font-family:var(--font-display);margin-bottom:16px}
.lg-form-row{display:grid;grid-template-columns:1fr 1fr auto auto;gap:12px;align-items:end}
.lg-input{padding:10px 14px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13.5px;font-family:var(--font);transition:border-color .15s;width:100%}
.lg-input:focus{outline:none;border-color:var(--orange)}
.lg-input::placeholder{color:var(--text3)}
.lg-count-input{width:80px}
.lg-gen-btn{padding:10px 28px;background:var(--orange);color:#fff;border:none;border-radius:var(--radius-sm);font-size:13.5px;font-weight:700;cursor:pointer;white-space:nowrap;transition:opacity .15s}
.lg-gen-btn:hover{opacity:.88}
.lg-gen-btn:disabled{opacity:.5;cursor:not-allowed}

/* Results table */
.lg-results-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px}
.lg-results-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px}
.lg-results-title{font-size:14px;font-weight:700;font-family:var(--font-display)}
.lg-tbl{width:100%;border-collapse:collapse}
.lg-tbl th{font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;padding:8px 10px;border-bottom:2px solid var(--border);text-align:left;white-space:nowrap}
.lg-tbl td{padding:10px 10px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text2);vertical-align:middle}
.lg-tbl tr:last-child td{border-bottom:none}
.lg-tbl tr:hover td{background:var(--bg3)}
.lg-name{font-weight:600;color:var(--text)}
.lg-phone{color:var(--text2);font-family:monospace}
.lg-addr{color:var(--text3);font-size:12px;max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lg-rating{color:var(--yellow);font-size:12px}
.lg-actions{display:flex;gap:6px;align-items:center}
.lg-btn-call{display:flex;align-items:center;justify-content:center;width:32px;height:32px;background:#10b981;color:#fff;border-radius:50%;border:none;cursor:pointer;font-size:14px;text-decoration:none;flex-shrink:0;transition:opacity .15s}
.lg-btn-call:hover{opacity:.8}
.lg-btn-imp{display:flex;align-items:center;justify-content:center;width:32px;height:32px;background:var(--orange);color:#fff;border-radius:50%;border:none;cursor:pointer;font-size:12px;flex-shrink:0;transition:opacity .15s}
.lg-btn-imp:hover{opacity:.8}
.lg-btn-imp:disabled{background:var(--bg4);cursor:default;opacity:1}
.lg-imp-badge{font-size:10px;background:rgba(16,185,129,.15);color:#10b981;border:1px solid rgba(16,185,129,.3);border-radius:99px;padding:2px 8px;white-space:nowrap}
.lg-empty{text-align:center;padding:40px 20px;color:var(--text3)}
.lg-loading{text-align:center;padding:36px;color:var(--text3)}
.lg-spinner{display:inline-block;width:28px;height:28px;border:3px solid var(--border);border-top-color:var(--orange);border-radius:50%;animation:lgspin .7s linear infinite;margin-bottom:10px}
@keyframes lgspin{to{transform:rotate(360deg)}}

/* Settings panel */
.lg-settings{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:20px;display:none}
.lg-settings.open{display:block}
.lg-api-warn{background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.3);border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:14px;font-size:12.5px;color:var(--text2);line-height:1.6}
.lg-api-link{color:var(--orange);font-weight:600}

/* Quota bar */
.lg-quota-bar{height:6px;background:var(--bg4);border-radius:99px;overflow:hidden;margin-top:8px}
.lg-quota-fill{height:100%;background:var(--orange);border-radius:99px;transition:width .4s}

/* No-API placeholder */
.lg-noapi{text-align:center;padding:48px 24px}
.lg-noapi-icon{font-size:48px;margin-bottom:12px}
.lg-noapi h3{font-size:16px;font-weight:700;color:var(--text);margin-bottom:8px}
.lg-noapi p{font-size:13px;color:var(--text3);max-width:420px;margin:0 auto 16px;line-height:1.6}

@media(max-width:1100px){.lg-grid{grid-template-columns:1fr 1fr}}
@media(max-width:800px){.lg-grid{grid-template-columns:1fr}.lg-form-row{grid-template-columns:1fr 1fr}}
@media(max-width:560px){.lg-form-row{grid-template-columns:1fr}}
</style>

<!-- Stats cards row -->
<div class="lg-grid" id="lg-stats-row">
  <!-- Usage ring -->
  <div class="lg-card lg-ring-card">
    <div class="lg-ring-title">Usage Progress</div>
    <div class="lg-ring-wrap">
      <svg class="lg-ring-svg" viewBox="0 0 110 110" width="110" height="110">
        <circle class="lg-ring-bg" cx="55" cy="55" r="46"/>
        <circle class="lg-ring-fill" cx="55" cy="55" r="46" id="lg-ring-fill"
          stroke-dasharray="289"
          stroke-dashoffset="289"/>
      </svg>
      <div class="lg-ring-text">
        <div class="lg-ring-pct" id="lg-ring-pct">0%</div>
        <div class="lg-ring-sub" id="lg-ring-sub">0 / 200</div>
      </div>
    </div>
  </div>

  <!-- Monthly trend chart -->
  <div class="lg-card">
    <div class="lg-chart-title">Monthly Usage Trend</div>
    <div style="height:140px;position:relative"><canvas id="lg-trend-chart"></canvas></div>
  </div>

  <!-- Recent activity -->
  <div class="lg-card">
    <div class="lg-chart-title">Recent Searches</div>
    <div id="lg-recent-list">
      <div class="lg-act-item"><div style="color:var(--text3);font-size:12.5px;padding:8px 0">No searches yet</div></div>
    </div>
  </div>
</div>

<!-- API key warning banner (shown if not configured) -->
<div id="lg-noapi-banner" style="display:none;background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.3);border-radius:var(--radius);padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
  <div style="display:flex;align-items:center;gap:10px">
    <span style="font-size:20px">🔑</span>
    <div>
      <div style="font-size:13px;font-weight:700;color:var(--orange)">Google Places API key required</div>
      <div style="font-size:12px;color:var(--text2)">Configure your API key to start generating real business leads.</div>
    </div>
  </div>
  <button onclick="toggleSettings()" class="btn btn-sm" style="background:var(--orange);color:#fff;border:none;flex-shrink:0">⚙ Configure</button>
</div>

<!-- Settings panel (admin only) -->
<?php if (isAdmin()): ?>
<div class="lg-settings" id="lg-settings-panel">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
    <div style="font-size:13px;font-weight:700;color:var(--text)">⚙ Lead Generator Settings</div>
    <button onclick="toggleSettings()" class="btn btn-ghost btn-sm">✕ Close</button>
  </div>
  <!-- Provider selector -->
  <div style="margin-bottom:14px">
    <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:6px">API Provider</label>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 14px;background:var(--bg2);border:2px solid var(--orange);border-radius:var(--radius-sm);font-size:13px;font-weight:600">
        <input type="radio" name="lg-provider" id="lg-prov-fsq" value="foursquare" checked style="accent-color:var(--orange)"> 🟢 Foursquare <span style="font-size:11px;color:var(--green);font-weight:700">FREE · No credit card</span>
      </label>
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 14px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm);font-size:13px;font-weight:600">
        <input type="radio" name="lg-provider" id="lg-prov-goog" value="google" style="accent-color:var(--orange)"> 🌏 Google Places <span style="font-size:11px;color:var(--text3);font-weight:400">Paid · Better coverage</span>
      </label>
    </div>
  </div>

  <!-- Foursquare setup (shown by default) -->
  <div id="lg-fsq-setup">
    <div class="lg-api-warn" style="background:rgba(16,185,129,.06);border-color:rgba(16,185,129,.3)">
      <strong style="color:var(--green)">✅ Foursquare is 100% FREE — no credit card ever needed</strong><br>
      <strong>How to get your FREE Foursquare API key (exactly as shown in your dashboard):</strong><br>
      1. Go to <a href="https://foursquare.com/developer" target="_blank" class="lg-api-link">foursquare.com/developer</a> → Login to your account<br>
      2. Open your project (e.g. "Lead Generator") → Click <strong>Settings</strong> in left sidebar<br>
      3. Scroll to <strong>"Service API Key"</strong> section → you see a key named e.g. "Leads"<br>
      4. Click the key name <strong>"Leads"</strong> to reveal/copy the full key<br>
      &nbsp;&nbsp;&nbsp;⚠️ <strong>Do NOT use "Client Id" or "Client Secret"</strong> — those are OAuth keys, won't work here<br>
      &nbsp;&nbsp;&nbsp;✅ Use only the <strong>Service API Key</strong> (under "Service API Key Name" column)<br>
      5. Paste it below → Save. Done! ✅<br>
      <span style="color:var(--green);font-weight:600">Free limit: 1,000 searches/day · Works great for Sri Lanka &amp; India</span>
    </div>
    <div style="display:grid;grid-template-columns:1fr 110px auto;gap:10px;align-items:end">
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Foursquare API Key</label>
        <input type="password" id="lg-fsq-key" class="lg-input" placeholder="fsq3..." autocomplete="off">
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Monthly Quota</label>
        <input type="number" id="lg-quota" class="lg-input" value="500" min="10" max="50000">
      </div>
      <button onclick="saveSettings()" class="btn btn-primary">Save</button>
    </div>
  </div>

  <!-- Google setup (hidden by default) -->
  <div id="lg-goog-setup" style="display:none">
    <div class="lg-api-warn">
      <strong>⚠ Google Places requires a credit card for verification</strong><br>
      The ₹2 charges you saw are <strong>refundable verification holds</strong> — not actual charges.<br>
      Google gives $200 free credit/month but billing setup is mandatory.<br>
      <strong>Recommendation: Use Foursquare instead (completely free)</strong>
    </div>
    <div style="display:grid;grid-template-columns:1fr auto;gap:10px;align-items:end">
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Google Places API Key</label>
        <input type="password" id="lg-goog-key" class="lg-input" placeholder="AIzaSy..." autocomplete="off">
      </div>
      <button onclick="saveSettings()" class="btn btn-primary">Save</button>
    </div>
  </div>
</div>
<?php else: ?>
<div id="lg-settings-panel"></div>
<?php endif; ?>

<!-- Generator form -->
<div class="lg-form-card">
  <div class="lg-form-title">🔍 Generate Leads</div>
  <div class="lg-form-row">
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Location / City</label>
      <input type="text" id="lg-location" class="lg-input" placeholder="e.g. Colombo, Batticaloa, Chennai">
    </div>
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Industry / Service</label>
      <input type="text" id="lg-industry" class="lg-input" placeholder="e.g. Web development, Restaurant, Law firm">
    </div>
    <div>
      <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Count</label>
      <input type="number" id="lg-count" class="lg-input lg-count-input" value="5" min="1" max="20">
    </div>
    <div>
      <label style="display:block;margin-bottom:4px;opacity:0">&nbsp;</label>
      <button class="lg-gen-btn" id="lg-gen-btn" onclick="generateLeads()">Generate</button>
    </div>
  </div>
  <div id="lg-quota-info" style="margin-top:10px;font-size:12px;color:var(--text3)"></div>
</div>

<!-- Results table -->
<div class="lg-results-card" id="lg-results-section" style="display:none">
  <div class="lg-results-head">
    <div class="lg-results-title">Generated Leads <span id="lg-result-count" style="font-size:12px;color:var(--text3);font-weight:400"></span></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button onclick="importAll()" class="btn btn-sm" id="lg-import-all-btn"
        style="background:var(--orange);color:#fff;border:none">⬇ Import All to CRM</button>
      <button onclick="exportExcel()" class="btn btn-ghost btn-sm">⬇ Download CSV</button>
    </div>
  </div>
  <div style="overflow-x:auto">
    <table class="lg-tbl">
      <thead>
        <tr>
          <th>#</th><th>Business Name</th><th>Phone</th><th>Address</th><th>Rating</th><th>Actions</th>
        </tr>
      </thead>
      <tbody id="lg-results-body"></tbody>
    </table>
  </div>
</div>

<!-- Loading state -->
<div id="lg-loading" style="display:none" class="lg-form-card" style="text-align:center">
  <div class="lg-loading">
    <div class="lg-spinner"></div>
    <div style="font-size:13px;color:var(--text3)" id="lg-loading-text">Searching for businesses…</div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
var lgCurrentIds  = [];
var lgTrendChart  = null;
var lgApiConfigured = false;

// ── LOAD STATS ON PAGE LOAD ──
document.addEventListener('DOMContentLoaded', function() {
    loadStats();

    // Enter key on inputs
    ['lg-location','lg-industry','lg-count'].forEach(function(id) {
        document.getElementById(id)?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') generateLeads();
        });
    });
});

function loadStats() {
    fetch('lead_generator_api.php?action=get_stats')
        .then(function(r){return r.json();})
        .then(function(d) {
            if (!d.ok) {
                if (d.error && d.error.includes('migration')) {
                    toast('Please run migration_v11.sql first', 'error');
                }
                return;
            }

            lgApiConfigured = d.api_set;

            // Show no-API banner if needed
            var banner = document.getElementById('lg-noapi-banner');
            if (!d.api_set && banner) banner.style.display = 'flex';
            else if (banner) banner.style.display = 'none';

            // Update ring
            var pct = d.quota > 0 ? Math.round(d.used / d.quota * 100) : 0;
            document.getElementById('lg-ring-pct').textContent = pct + '%';
            document.getElementById('lg-ring-sub').textContent = d.used + ' / ' + d.quota;
            var circ = 2 * Math.PI * 46; // r=46
            var offset = circ - (pct / 100 * circ);
            document.getElementById('lg-ring-fill').setAttribute('stroke-dasharray', circ.toFixed(1));
            document.getElementById('lg-ring-fill').setAttribute('stroke-dashoffset', offset.toFixed(1));

            // Quota info
            var qi = document.getElementById('lg-quota-info');
            if (qi) qi.textContent = 'Used this month: ' + d.used + ' / ' + d.quota + ' leads. ' +
                (d.quota - d.used) + ' remaining.';

            // Trend chart
            renderTrend(d.trend || []);

            // Recent activity
            renderRecent(d.recent || []);

            // Settings prefill (admin)
            if (d.api_key) document.getElementById('lg-api-key') && (document.getElementById('lg-api-key').value = '');
            if (d.quota)  document.getElementById('lg-quota')   && (document.getElementById('lg-quota').value   = d.quota);
        })
        .catch(function(e) { console.log('stats error', e); });
}

function renderTrend(data) {
    var ctx = document.getElementById('lg-trend-chart');
    if (!ctx) return;
    if (lgTrendChart) { lgTrendChart.destroy(); lgTrendChart = null; }

    var labels = data.map(function(r){return r.mo;});
    var vals   = data.map(function(r){return parseInt(r.cnt)||0;});

    // Fill last 6 months if empty
    if (!labels.length) {
        var months = ['Jan','Feb','Mar','Apr','May','Jun'];
        labels = months;
        vals   = [0,0,0,0,0,0];
    }

    lgTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                data: vals,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79,70,229,.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#4f46e5',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: 'rgba(148,163,184,.12)' }, ticks: { color: '#94a3b8', font:{size:11} } },
                y: { grid: { color: 'rgba(148,163,184,.12)' }, ticks: { color: '#94a3b8', precision: 0 }, beginAtZero: true }
            }
        }
    });
}

function renderRecent(data) {
    var list = document.getElementById('lg-recent-list');
    if (!list) return;
    if (!data.length) {
        list.innerHTML = '<div class="lg-act-item"><div style="color:var(--text3);font-size:12.5px;padding:8px 0">No searches yet</div></div>';
        return;
    }
    var colors = ['#4f46e5','#10b981','#8b5cf6','#f97316','#f59e0b','#14b8a6','#6366f1','#ef4444'];
    list.innerHTML = data.map(function(r, i) {
        var ago = fmtAgo(r.created_at);
        return '<div class="lg-act-item">' +
            '<div class="lg-act-dot" style="background:'+colors[i%colors.length]+'"></div>' +
            '<div>' +
                '<div class="lg-act-text">' + escH(r.result_count) + ' leads — ' + escH(r.industry) + ' in ' + escH(r.location) + '</div>' +
                '<div class="lg-act-time">' + ago + '</div>' +
            '</div>' +
        '</div>';
    }).join('');
}

// ── GENERATE LEADS ──
function generateLeads() {
    var location = document.getElementById('lg-location').value.trim();
    var industry = document.getElementById('lg-industry').value.trim();
    var count    = parseInt(document.getElementById('lg-count').value) || 5;

    if (!location) { toast('Please enter a location', 'error'); document.getElementById('lg-location').focus(); return; }
    if (!industry) { toast('Please enter an industry', 'error'); document.getElementById('lg-industry').focus(); return; }

    if (!lgApiConfigured) {
        toast('Google API key not configured. Click Configure.', 'error');
        toggleSettings();
        return;
    }

    var btn = document.getElementById('lg-gen-btn');
    var loading = document.getElementById('lg-loading');
    var results = document.getElementById('lg-results-section');
    btn.disabled = true;
    btn.textContent = 'Generating…';
    loading.style.display = 'block';
    if (results) results.style.display = 'none';

    document.getElementById('lg-loading-text').textContent =
        'Searching for ' + industry + ' businesses in ' + location + '…';

    var fd = new FormData();
    fd.append('action', 'search');
    fd.append('location', location);
    fd.append('industry', industry);
    fd.append('count', count);

    fetch('lead_generator_api.php', { method: 'POST', body: fd })
        .then(function(r){return r.json();})
        .then(function(d) {
            btn.disabled = false;
            btn.textContent = 'Generate';
            loading.style.display = 'none';

            if (!d.ok) {
                toast(d.error || 'Generation failed', 'error');
                return;
            }

            if (d.message && (!d.leads || !d.leads.length)) {
                toast(d.message, 'info');
                return;
            }

            renderResults(d.leads, location, industry);
            loadStats(); // refresh ring + trend

            // Update quota info
            if (d.used !== undefined) {
                document.getElementById('lg-quota-info').textContent =
                    'Used this month: ' + d.used + ' / ' + d.quota + ' leads. ' + (d.quota - d.used) + ' remaining.';
            }
        })
        .catch(function(e) {
            btn.disabled = false;
            btn.textContent = 'Generate';
            loading.style.display = 'none';
            toast('Network error. Check console.', 'error');
            console.error(e);
        });
}

function renderResults(leads, location, industry) {
    var section = document.getElementById('lg-results-section');
    var body    = document.getElementById('lg-results-body');
    var count   = document.getElementById('lg-result-count');

    lgCurrentIds = leads.map(function(l){return l.id;});

    if (count) count.textContent = '(' + leads.length + ' found in ' + industry + ', ' + location + ')';

    body.innerHTML = leads.map(function(lead, i) {
        var phone_link = lead.phone ? '<a href="tel:' + escH(lead.phone) + '" class="lg-btn-call" title="Call ' + escH(lead.name) + '">📞</a>' : '<span style="width:32px;height:32px;display:inline-block"></span>';
        var imp_btn = lead.imported
            ? '<span class="lg-imp-badge">✓ Imported</span>'
            : '<button class="lg-btn-imp" onclick="importLead('+lead.id+',this)" title="Import to CRM leads">⬇</button>';
        var stars = lead.rating ? '⭐ ' + lead.rating : '';
        var website_link = lead.website ? '<div style="font-size:11px;margin-top:2px"><a href="'+escH(lead.website)+'" target="_blank" style="color:var(--orange)">🌐 Website</a></div>' : '';

        return '<tr id="lg-row-'+lead.id+'">' +
            '<td style="color:var(--text3);font-size:12px">'+(i+1)+'</td>' +
            '<td><div class="lg-name">'+escH(lead.name)+'</div>'+website_link+'</td>' +
            '<td class="lg-phone">'+(lead.phone ? escH(lead.phone) : '<span style="color:var(--text3)">—</span>')+'</td>' +
            '<td><div class="lg-addr" title="'+escH(lead.address||'')+'">'+escH(lead.address||'—')+'</div></td>' +
            '<td class="lg-rating">'+stars+'</td>' +
            '<td><div class="lg-actions">'+phone_link+'&nbsp;'+imp_btn+'</div></td>' +
        '</tr>';
    }).join('');

    section.style.display = 'block';
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ── IMPORT SINGLE LEAD ──
function importLead(resultId, btn) {
    btn.disabled = true;
    btn.textContent = '…';

    var fd = new FormData();
    fd.append('action', 'import_lead');
    fd.append('result_id', resultId);

    fetch('lead_generator_api.php', { method: 'POST', body: fd })
        .then(function(r){return r.json();})
        .then(function(d) {
            if (d.ok) {
                btn.replaceWith(Object.assign(document.createElement('span'), {
                    className: 'lg-imp-badge', textContent: '✓ Imported'
                }));
                toast(d.message || 'Imported!', 'success');
            } else {
                btn.disabled = false;
                btn.textContent = '⬇';
                toast(d.error || 'Import failed', 'error');
            }
        });
}

// ── IMPORT ALL ──
function importAll() {
    var pending = lgCurrentIds.filter(function(id) {
        var btn = document.querySelector('#lg-row-' + id + ' .lg-btn-imp');
        return btn && !btn.disabled;
    });

    if (!pending.length) { toast('All leads already imported', 'info'); return; }
    if (!confirm('Import ' + pending.length + ' leads to CRM pipeline?')) return;

    var btn = document.getElementById('lg-import-all-btn');
    btn.disabled = true; btn.textContent = 'Importing…';

    var fd = new FormData();
    fd.append('action', 'import_all');
    fd.append('ids', pending.join(','));

    fetch('lead_generator_api.php', { method: 'POST', body: fd })
        .then(function(r){return r.json();})
        .then(function(d) {
            btn.disabled = false; btn.textContent = '⬇ Import All to CRM';
            if (d.ok) {
                toast(d.imported + ' leads imported to CRM!', 'success');
                // Update UI — mark all as imported
                pending.forEach(function(id) {
                    var b = document.querySelector('#lg-row-' + id + ' .lg-btn-imp');
                    if (b) b.replaceWith(Object.assign(document.createElement('span'), {
                        className: 'lg-imp-badge', textContent: '✓ Imported'
                    }));
                });
            } else {
                toast(d.error || 'Import failed', 'error');
            }
        });
}

// ── EXPORT CSV ──
function exportExcel() {
    if (!lgCurrentIds.length) { toast('No data to export', 'error'); return; }
    var fd = new FormData();
    fd.append('action', 'export_excel');
    fd.append('ids', lgCurrentIds.join(','));

    fetch('lead_generator_api.php', { method: 'POST', body: fd })
        .then(function(r){return r.json();})
        .then(function(d) {
            if (!d.ok || !d.csv) { toast('Export failed', 'error'); return; }
            var blob = new Blob([d.csv], { type: 'text/csv' });
            var url  = URL.createObjectURL(blob);
            var a    = document.createElement('a');
            a.href = url;
            a.download = 'leads_' + new Date().toISOString().slice(0,10) + '.csv';
            a.click();
            URL.revokeObjectURL(url);
        });
}

// ── SETTINGS ──
function toggleSettings() {
    var p = document.getElementById('lg-settings-panel');
    if (!p) return;
    p.classList.toggle('open');
}

// Toggle provider panels
document.querySelectorAll('input[name="lg-provider"]').forEach(function(r) {
    r.addEventListener('change', function() {
        var isFsq = this.value === 'foursquare';
        var fsqSetup  = document.getElementById('lg-fsq-setup');
        var googSetup = document.getElementById('lg-goog-setup');
        var fsqLabel  = document.getElementById('lg-prov-fsq')?.closest('label');
        var gLabel    = document.getElementById('lg-prov-goog')?.closest('label');
        if (fsqSetup)  fsqSetup.style.display  = isFsq ? 'block' : 'none';
        if (googSetup) googSetup.style.display  = isFsq ? 'none'  : 'block';
        if (fsqLabel)  fsqLabel.style.borderColor  = isFsq ? 'var(--orange)' : 'var(--border)';
        if (gLabel)    gLabel.style.borderColor     = isFsq ? 'var(--border)' : 'var(--orange)';
    });
});

function saveSettings() {
    var provider = document.querySelector('input[name="lg-provider"]:checked')?.value || 'foursquare';
    var fsq_key  = document.getElementById('lg-fsq-key')?.value.trim() || '';
    var goog_key = document.getElementById('lg-goog-key')?.value.trim() || '';
    var quota    = document.getElementById('lg-quota')?.value || 500;

    if (provider === 'foursquare' && !fsq_key) { toast('Enter your Foursquare API key', 'error'); return; }
    if (provider === 'google' && !goog_key)    { toast('Enter your Google API key', 'error'); return; }

    var fd = new FormData();
    fd.append('action', 'save_settings');
    fd.append('provider', provider);
    fd.append('foursquare_key', fsq_key);
    fd.append('google_key', goog_key);
    fd.append('quota', quota);

    fetch('lead_generator_api.php', { method: 'POST', body: fd })
        .then(function(r){return r.json();})
        .then(function(d) {
            if (d.ok) {
                toast('Settings saved! Lead Generator is ready.', 'success');
                lgApiConfigured = true;
                document.getElementById('lg-noapi-banner').style.display = 'none';
                document.getElementById('lg-settings-panel').classList.remove('open');
                document.getElementById('lg-fsq-key') && (document.getElementById('lg-fsq-key').value = '');
                document.getElementById('lg-goog-key') && (document.getElementById('lg-goog-key').value = '');
                loadStats();
            } else {
                toast(d.error || 'Save failed', 'error');
            }
        });
}

// ── HELPERS ──
function escH(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmtAgo(dt) {
    if (!dt) return '';
    var diff = Math.floor((Date.now() - new Date(dt).getTime()) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff/60) + 'm ago';
    if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
    return Math.floor(diff/86400) + 'd ago';
}
</script>

<?php renderLayoutEnd(); ?>