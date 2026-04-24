<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];
renderLayout('AI Assistant', 'chatbot');
?>
<style>
/* ── CHATBOT LAYOUT ── */
.cb-wrap{display:grid;grid-template-columns:260px 1fr;gap:0;height:calc(100vh - var(--header-h) - 48px);min-height:500px;border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;background:var(--bg2)}

/* ── LEFT SIDEBAR: history ── */
.cb-sidebar{background:var(--bg3);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden}
.cb-sidebar-head{padding:14px 14px 10px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.cb-sidebar-title{font-size:13px;font-weight:700;color:var(--text);font-family:var(--font-display)}
.cb-new-btn{padding:5px 12px;background:var(--orange);color:#fff;border:none;border-radius:var(--radius-sm);font-size:12px;font-weight:700;cursor:pointer;transition:opacity .15s;white-space:nowrap}
.cb-new-btn:hover{opacity:.88}
.cb-sessions{flex:1;overflow-y:auto;padding:8px}
.cb-session-item{padding:9px 10px;border-radius:var(--radius-sm);cursor:pointer;transition:background .12s;margin-bottom:2px;border:1px solid transparent}
.cb-session-item:hover{background:var(--bg4)}
.cb-session-item.active{background:var(--orange-bg);border-color:rgba(249,115,22,.25)}
.cb-session-title{font-size:12.5px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cb-session-meta{font-size:11px;color:var(--text3);margin-top:2px}
.cb-session-del{float:right;background:none;border:none;color:var(--text3);cursor:pointer;font-size:14px;padding:0 2px;display:none;line-height:1}
.cb-session-item:hover .cb-session-del{display:inline}
/* ── USAGE METER ── */
.cb-quota-bar{padding:10px 14px;border-top:1px solid var(--border);background:var(--bg3);flex-shrink:0}
.cb-quota-label{font-size:10.5px;color:var(--text3);margin-bottom:5px;display:flex;justify-content:space-between}
.cb-qbar{height:5px;background:var(--bg4);border-radius:99px;overflow:hidden}
.cb-qfill{height:100%;border-radius:99px;background:#10b981;transition:width .4s}

/* ── MAIN CHAT AREA ── */
.cb-main{display:flex;flex-direction:column;overflow:hidden;background:var(--bg2)}
.cb-chat-header{padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;flex-shrink:0;background:var(--bg3)}
.cb-model-badge{padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;background:rgba(99,102,241,.12);color:#6366f1;border:1px solid rgba(99,102,241,.2)}
.cb-status{font-size:11.5px;color:var(--text3);margin-left:auto;display:flex;align-items:center;gap:5px}
.cb-status-dot{width:7px;height:7px;border-radius:50%;background:#10b981;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}

/* ── MESSAGES ── */
.cb-messages{flex:1;overflow-y:auto;padding:20px 18px;display:flex;flex-direction:column;gap:14px}
.cb-msg{display:flex;gap:10px;align-items:flex-start;max-width:88%;animation:msgIn .2s ease}
@keyframes msgIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
.cb-msg.user{align-self:flex-end;flex-direction:row-reverse}
.cb-msg.assistant{align-self:flex-start}
.cb-avatar{width:30px;height:30px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}
.cb-avatar.ai{background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff}
.cb-avatar.user{background:var(--orange);color:#fff}
.cb-bubble{padding:10px 14px;border-radius:12px;font-size:13.5px;line-height:1.65;max-width:100%;word-break:break-word}
.cb-msg.user .cb-bubble{background:var(--orange);color:#fff;border-radius:12px 2px 12px 12px}
.cb-msg.assistant .cb-bubble{background:var(--bg3);color:var(--text);border:1px solid var(--border);border-radius:2px 12px 12px 12px}
.cb-bubble pre{background:var(--bg4);border-radius:var(--radius-sm);padding:10px 12px;overflow-x:auto;font-size:12px;margin:8px 0;border:1px solid var(--border)}
.cb-bubble code{font-family:monospace;font-size:12px}
.cb-bubble p{margin-bottom:6px}
.cb-bubble p:last-child{margin-bottom:0}
.cb-bubble strong{color:var(--text);font-weight:700}
.cb-bubble ul,.cb-bubble ol{padding-left:18px;margin:6px 0}
.cb-bubble li{margin-bottom:3px}
.cb-time{font-size:10.5px;color:var(--text3);margin-top:4px;padding:0 2px}
.cb-msg.user .cb-time{text-align:right}

/* ── TYPING INDICATOR ── */
.cb-typing{display:none;align-self:flex-start;align-items:center;gap:10px}
.cb-typing.show{display:flex}
.cb-typing-bubble{background:var(--bg3);border:1px solid var(--border);border-radius:2px 12px 12px 12px;padding:10px 14px;display:flex;gap:5px;align-items:center}
.cb-dot{width:7px;height:7px;background:var(--text3);border-radius:50%;animation:dot .9s infinite}
.cb-dot:nth-child(2){animation-delay:.15s}
.cb-dot:nth-child(3){animation-delay:.3s}
@keyframes dot{0%,80%,100%{transform:scale(.7);opacity:.5}40%{transform:scale(1);opacity:1}}

/* ── INPUT AREA ── */
.cb-input-wrap{padding:14px 16px;border-top:1px solid var(--border);background:var(--bg3);flex-shrink:0}
.cb-no-api{padding:14px;text-align:center;font-size:13px;color:var(--text3);background:rgba(249,115,22,.06);border-radius:var(--radius-sm);border:1px solid rgba(249,115,22,.2);margin-bottom:10px;display:none}
.cb-no-api a{color:var(--orange);font-weight:700;cursor:pointer}
.cb-input-row{display:flex;gap:10px;align-items:flex-end}
.cb-textarea{flex:1;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:10px 14px;color:var(--text);font-size:13.5px;font-family:var(--font);resize:none;min-height:44px;max-height:140px;line-height:1.5;transition:border-color .15s}
.cb-textarea:focus{outline:none;border-color:var(--orange)}
.cb-textarea::placeholder{color:var(--text3)}
.cb-send{width:44px;height:44px;background:var(--orange);border:none;border-radius:var(--radius-sm);color:#fff;font-size:18px;cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:opacity .15s}
.cb-send:hover{opacity:.88}
.cb-send:disabled{opacity:.4;cursor:not-allowed}
.cb-input-hint{font-size:11px;color:var(--text3);margin-top:7px;display:flex;justify-content:space-between;align-items:center}
.cb-chars{font-size:11px;color:var(--text3)}
.cb-chars.warn{color:#f59e0b}

/* ── EMPTY STATE ── */
.cb-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:30px;text-align:center}
.cb-empty-icon{font-size:44px;margin-bottom:6px;opacity:.6}
.cb-empty h3{font-size:16px;font-weight:700;color:var(--text);font-family:var(--font-display)}
.cb-empty p{font-size:13px;color:var(--text3);max-width:320px;line-height:1.6}
.cb-starter-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px;width:100%;max-width:440px}
.cb-starter{padding:10px 12px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);font-size:12.5px;color:var(--text2);cursor:pointer;text-align:left;transition:background .12s,border-color .12s}
.cb-starter:hover{background:var(--bg4);border-color:var(--orange);color:var(--text)}

/* ── SETTINGS ── */
.cb-settings-btn{background:none;border:1px solid var(--border);color:var(--text3);border-radius:var(--radius-sm);padding:4px 9px;font-size:11px;cursor:pointer;transition:all .12s}
.cb-settings-btn:hover{border-color:var(--orange);color:var(--orange)}
.cb-settings-panel{display:none;position:absolute;bottom:calc(100% + 8px);left:0;right:0;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px;box-shadow:var(--shadow-lg);z-index:50}
.cb-settings-panel.open{display:block}
.cb-input-wrap{position:relative}

/* ── NO API BANNER ── */
#cb-api-banner{display:none;background:rgba(249,115,22,.06);border:1px solid rgba(249,115,22,.25);border-radius:var(--radius-sm);padding:10px 14px;margin-bottom:8px;font-size:12.5px;color:var(--text2)}

/* ── QUOTA ALERT ── */
.cb-quota-warn{background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:var(--radius-sm);padding:8px 12px;font-size:12px;color:#ef4444;margin-bottom:8px;display:none}

/* ── CONTEXT BADGE ── */
.cb-context-badge{padding:2px 8px;border-radius:99px;font-size:10.5px;font-weight:700;background:rgba(16,185,129,.1);color:#10b981;border:1px solid rgba(16,185,129,.2)}

/* ── RESPONSIVE ── */
@media(max-width:800px){
  .cb-wrap{grid-template-columns:1fr;height:auto}
  .cb-sidebar{display:none}
  .cb-main{height:calc(100vh - var(--header-h) - 48px)}
}
</style>

<!-- TOP BANNER: no API key -->
<div id="cb-api-banner">
  <strong>🔑 Gemini API key not configured.</strong>
  <?php if (isAdmin()): ?><a onclick="cbToggleSettings()" style="color:var(--orange);font-weight:700;cursor:pointer"> Click here to set it up (takes 2 minutes) →</a><?php else: ?> Ask your admin to configure it in Settings.<?php endif; ?>
</div>

<div class="cb-wrap" id="cb-wrap">
  <!-- ── LEFT: SESSION HISTORY ── -->
  <div class="cb-sidebar">
    <div class="cb-sidebar-head">
      <div class="cb-sidebar-title">💬 Conversations</div>
      <button class="cb-new-btn" onclick="cbNewSession()">+ New</button>
    </div>
    <div class="cb-sessions" id="cb-sessions">
      <div style="color:var(--text3);font-size:12px;padding:8px 4px">Loading...</div>
    </div>
    <!-- Daily usage meter -->
    <div class="cb-quota-bar">
      <div class="cb-quota-label">
        <span>Daily usage</span>
        <span id="cb-usage-text">0 / 200 msgs</span>
      </div>
      <div class="cb-qbar"><div class="cb-qfill" id="cb-qfill" style="width:0%"></div></div>
      <div style="font-size:10px;color:var(--text3);margin-top:5px" id="cb-quota-note">Gemini 2.5 Flash · Free tier</div>
    </div>
  </div>

  <!-- ── RIGHT: MAIN CHAT ── -->
  <div class="cb-main">
    <!-- Header -->
    <div class="cb-chat-header">
      <span style="font-size:16px">🤖</span>
      <div>
        <div style="font-size:13px;font-weight:700;color:var(--text)">Padak AI Assistant</div>
        <div style="font-size:11px;color:var(--text3)">Internal CRM helper · Knows your team context</div>
      </div>
      <span class="cb-model-badge">Gemini 2.5 Flash</span>
      <div class="cb-status">
        <div class="cb-status-dot" id="cb-status-dot"></div>
        <span id="cb-status-text">Ready</span>
      </div>
      <?php if (isAdmin()): ?>
      <button class="cb-settings-btn" onclick="cbToggleSettings()">⚙ Settings</button>
      <?php endif; ?>
    </div>

    <!-- Messages area -->
    <div class="cb-messages" id="cb-messages">
      <!-- Empty state shown by JS -->
      <div class="cb-empty" id="cb-empty">
        <div class="cb-empty-icon">🤖</div>
        <h3>Padak AI Assistant</h3>
        <p>I can help with CRM tasks, drafting emails, summarizing leads, writing reports, answering questions, and much more.</p>
        <div class="cb-starter-grid">
          <button class="cb-starter" onclick="cbUseStarter(this)">📊 Summarize what I should focus on today</button>
          <button class="cb-starter" onclick="cbUseStarter(this)">✉️ Draft a follow-up email for a cold lead</button>
          <button class="cb-starter" onclick="cbUseStarter(this)">📋 Help me write a project status update</button>
          <button class="cb-starter" onclick="cbUseStarter(this)">💡 Tips to improve lead conversion rate</button>
          <button class="cb-starter" onclick="cbUseStarter(this)">🔍 How should I qualify a new prospect?</button>
          <button class="cb-starter" onclick="cbUseStarter(this)">📝 Write an invoice follow-up message</button>
        </div>
      </div>
    </div>

    <!-- Typing indicator -->
    <div class="cb-typing" id="cb-typing" style="padding:0 18px 8px;display:none">
      <div class="cb-avatar ai">AI</div>
      <div class="cb-typing-bubble">
        <div class="cb-dot"></div>
        <div class="cb-dot"></div>
        <div class="cb-dot"></div>
      </div>
    </div>

    <!-- Input area -->
    <div class="cb-input-wrap">
      <!-- Settings panel (admin only) -->
      <?php if (isAdmin()): ?>
      <div class="cb-settings-panel" id="cb-settings-panel">
        <div style="font-size:13px;font-weight:700;margin-bottom:12px">⚙ AI Chatbot Settings</div>

        <!-- Setup guide -->
        <div style="background:var(--bg3);border-radius:var(--radius-sm);padding:12px;margin-bottom:12px;font-size:12px;line-height:1.7;color:var(--text2)">
          <div style="font-weight:700;color:var(--text);margin-bottom:6px">How to get your FREE Gemini API key (2 min setup):</div>
          <div><strong>1.</strong> Go to <a href="https://aistudio.google.com/apikey" target="_blank" style="color:var(--orange);font-weight:600">aistudio.google.com/apikey</a> — login with Google</div>
          <div><strong>2.</strong> Click <strong>"Create API key"</strong> → Select or create a new project</div>
          <div><strong>3.</strong> Copy the key (starts with <code style="background:var(--bg4);padding:1px 5px;border-radius:3px">AIzaSy...</code>)</div>
          <div><strong>4.</strong> Paste it below and click Save</div>
          <div style="margin-top:8px;padding:8px 10px;background:rgba(16,185,129,.07);border-radius:var(--radius-sm);color:#10b981">
            ✅ Gemini 2.5 Flash is 100% free — 250 requests/day, no credit card needed.<br>
            ⚠️ This is a SEPARATE key/project from Places API — use a different project to keep quotas separate!
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 120px auto;gap:8px;align-items:end">
          <div>
            <label style="font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Gemini API Key</label>
            <input type="password" id="cb-key-input" class="lg-input" style="padding:8px 11px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13px;font-family:var(--font);width:100%" placeholder="AIzaSy..." autocomplete="off">
          </div>
          <div>
            <label style="font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Daily Limit</label>
            <input type="number" id="cb-daily-limit" style="padding:8px 11px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13px;font-family:var(--font);width:100%" value="200" min="10" max="240">
            <div style="font-size:10px;color:var(--text3);margin-top:2px">Max: 240/day free</div>
          </div>
          <div style="display:flex;gap:6px">
            <button onclick="cbSaveSettings()" style="padding:7px 14px;background:var(--orange);color:#fff;border:none;border-radius:var(--radius-sm);font-size:12.5px;font-weight:700;cursor:pointer">💾 Save</button>
            <button onclick="cbTestKey()" id="cb-test-btn" style="padding:7px 12px;background:var(--bg3);border:1px solid var(--border);color:var(--text2);border-radius:var(--radius-sm);font-size:12px;cursor:pointer">🔌 Test</button>
          </div>
        </div>
        <div id="cb-test-result" style="display:none;margin-top:8px;padding:8px 10px;border-radius:var(--radius-sm);font-size:12.5px;white-space:pre-line"></div>

        <!-- Cost reality check -->
        <div style="margin-top:12px;padding:10px 12px;background:var(--bg3);border-radius:var(--radius-sm);font-size:11.5px;color:var(--text2);line-height:1.7;border:1px solid var(--border)">
          <div style="font-weight:700;color:var(--text);margin-bottom:4px">📊 Honest Free Tier Reality (as of April 2026):</div>
          <div>🟢 <strong>Gemini 2.5 Flash</strong> — still FREE, 10 RPM, 250 req/day per project</div>
          <div>🔴 Gemini 2.5 Pro — now paid-only (removed from free tier April 2026)</div>
          <div>⚠️ Flash-Lite — 1,000 req/day free but weaker reasoning</div>
          <div style="margin-top:6px">📍 <strong>Places API $200 credit + Gemini free tier are SEPARATE</strong> — different product families, different quotas. Using chatbot does NOT consume your Places API ₹1,000 credit. Safe!</div>
        </div>
        <div style="text-align:right;margin-top:8px"><button onclick="cbToggleSettings()" style="background:none;border:none;font-size:12px;color:var(--text3);cursor:pointer">✕ Close</button></div>
      </div>
      <?php endif; ?>

      <!-- Quota warning -->
      <div class="cb-quota-warn" id="cb-quota-warn">⚠ Approaching daily limit. <span id="cb-quota-warn-text"></span></div>

      <!-- No API warning -->
      <div id="cb-no-api-input" style="display:none;text-align:center;padding:10px;font-size:12.5px;color:var(--text3)">
        <?php if (isAdmin()): ?>API key not configured — <a onclick="cbToggleSettings()" style="color:var(--orange);cursor:pointer;font-weight:600">click here to set up →</a><?php else: ?>AI assistant not configured yet. Ask your admin.<?php endif; ?>
      </div>

      <div class="cb-input-row" id="cb-input-row">
        <textarea class="cb-textarea" id="cb-textarea"
          placeholder="Ask anything about your CRM, leads, emails, tasks..." rows="1"
          maxlength="4000" oninput="cbAutoResize(this);cbUpdateChars(this)"></textarea>
        <button class="cb-send" id="cb-send" onclick="cbSend()" title="Send (Ctrl+Enter)">➤</button>
      </div>
      <div class="cb-input-hint">
        <span style="color:var(--text3);font-size:11px">Ctrl+Enter to send · Messages auto-saved</span>
        <span class="cb-chars" id="cb-chars">0 / 4000</span>
      </div>
    </div>
  </div>
</div>

<script>
// ── STATE ──
var CB = {
  sessionId: null,
  messages: [],   // {role, content, ts}
  configured: false,
  dailyUsed: 0,
  dailyLimit: 200,
  sessions: []
};

document.addEventListener('DOMContentLoaded', function() {
  cbLoadStats();
  cbLoadSessions();
  // Ctrl+Enter to send
  document.getElementById('cb-textarea').addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); cbSend(); }
  });
  // Close settings on outside click
  document.addEventListener('click', function(e) {
    var panel = document.getElementById('cb-settings-panel');
    if (panel && panel.classList.contains('open') && !e.target.closest('.cb-input-wrap') && !e.target.closest('#cb-api-banner')) {
      panel.classList.remove('open');
    }
  });
});

function cbLoadStats() {
  fetch('chatbot_api.php?action=get_stats')
  .then(function(r){return r.json();})
  .then(function(d){
    if (!d.ok) return;
    CB.configured = d.configured;
    CB.dailyUsed  = d.daily_used;
    CB.dailyLimit = d.daily_limit;

    // Banner
    document.getElementById('cb-api-banner').style.display = d.configured ? 'none' : 'block';
    document.getElementById('cb-no-api-input').style.display = d.configured ? 'none' : 'block';
    document.getElementById('cb-input-row').style.display = d.configured ? 'flex' : 'none';

    // Quota bar
    var pct = CB.dailyLimit > 0 ? Math.min(100, Math.round(CB.dailyUsed / CB.dailyLimit * 100)) : 0;
    document.getElementById('cb-usage-text').textContent = CB.dailyUsed + ' / ' + CB.dailyLimit + ' msgs';
    document.getElementById('cb-qfill').style.width = pct + '%';
    document.getElementById('cb-qfill').style.background = pct > 80 ? '#ef4444' : pct > 60 ? '#f59e0b' : '#10b981';

    // Quota warning
    var rem = CB.dailyLimit - CB.dailyUsed;
    if (rem <= 30 && rem > 0) {
      document.getElementById('cb-quota-warn').style.display = 'block';
      document.getElementById('cb-quota-warn-text').textContent = rem + ' messages remaining today. Resets at midnight Pacific time.';
    }
    if (rem <= 0) {
      document.getElementById('cb-quota-warn').style.display = 'block';
      document.getElementById('cb-quota-warn-text').textContent = 'Daily limit reached. Resets tomorrow at midnight Pacific time.';
      document.getElementById('cb-send').disabled = true;
    }

    // Prefill admin settings
    if (document.getElementById('cb-daily-limit')) document.getElementById('cb-daily-limit').value = d.daily_limit;
  })
  .catch(function(){});
}

function cbLoadSessions() {
  fetch('chatbot_api.php?action=list_sessions')
  .then(function(r){return r.json();})
  .then(function(d){
    if (!d.ok) return;
    CB.sessions = d.sessions || [];
    cbRenderSessions();
  });
}

function cbRenderSessions() {
  var el = document.getElementById('cb-sessions');
  if (!CB.sessions.length) {
    el.innerHTML = '<div style="color:var(--text3);font-size:12px;padding:8px 4px;text-align:center">No conversations yet.<br>Start one below!</div>';
    return;
  }
  el.innerHTML = CB.sessions.map(function(s) {
    var active = (s.id == CB.sessionId) ? ' active' : '';
    return '<div class="cb-session-item'+active+'" onclick="cbLoadSession('+s.id+')" id="cbs-'+s.id+'">'
      +'<button class="cb-session-del" onclick="event.stopPropagation();cbDelSession('+s.id+')" title="Delete">✕</button>'
      +'<div class="cb-session-title">'+escHtml(s.title||'Conversation')+'</div>'
      +'<div class="cb-session-meta">'+s.msg_count+' msgs · '+fmtAgo(s.updated_at)+'</div>'
    +'</div>';
  }).join('');
}

function cbLoadSession(sid) {
  CB.sessionId = sid;
  fetch('chatbot_api.php?action=get_session&session_id='+sid)
  .then(function(r){return r.json();})
  .then(function(d){
    if (!d.ok) return;
    CB.messages = d.messages || [];
    cbRenderMessages();
    cbRenderSessions(); // refresh active state
  });
}

function cbRenderMessages() {
  var container = document.getElementById('cb-messages');
  var empty = document.getElementById('cb-empty');

  if (!CB.messages.length) {
    if (empty) empty.style.display = 'flex';
    // Remove non-empty bubbles
    container.querySelectorAll('.cb-msg').forEach(function(m){m.remove();});
    return;
  }
  if (empty) empty.style.display = 'none';
  container.querySelectorAll('.cb-msg').forEach(function(m){m.remove();});

  CB.messages.forEach(function(msg) {
    var el = document.createElement('div');
    el.className = 'cb-msg ' + msg.role;
    var initials = <?= json_encode(substr(implode('', array_map(fn($w) => strtoupper($w[0]), explode(' ', $user['name']))), 0, 2)) ?>;
    var avatarLabel = msg.role === 'user' ? initials : 'AI';
    el.innerHTML = '<div class="cb-avatar '+msg.role+'">'+avatarLabel+'</div>'
      +'<div><div class="cb-bubble">'+cbFormatContent(msg.content)+'</div>'
      +'<div class="cb-time">'+fmtTime(msg.ts)+'</div></div>';
    container.appendChild(el);
  });
  container.scrollTop = container.scrollHeight;
}

function cbAppendMessage(role, content) {
  var container = document.getElementById('cb-messages');
  var empty = document.getElementById('cb-empty');
  if (empty) empty.style.display = 'none';

  var el = document.createElement('div');
  el.className = 'cb-msg ' + role;
  var initials = <?= json_encode(substr(implode('', array_map(fn($w) => strtoupper($w[0]), explode(' ', $user['name']))), 0, 2)) ?>;
  var avatarLabel = role === 'user' ? initials : 'AI';
  el.innerHTML = '<div class="cb-avatar '+role+'">'+avatarLabel+'</div>'
    +'<div><div class="cb-bubble" id="cb-last-bubble">'+cbFormatContent(content)+'</div>'
    +'<div class="cb-time">just now</div></div>';
  container.appendChild(el);
  container.scrollTop = container.scrollHeight;

  CB.messages.push({role: role, content: content, ts: new Date().toISOString()});
  return el;
}

function cbSend() {
  var ta = document.getElementById('cb-textarea');
  var msg = ta.value.trim();
  if (!msg) return;
  if (!CB.configured) { toast('Configure Gemini API key first','error'); cbToggleSettings(); return; }
  if (CB.dailyUsed >= CB.dailyLimit) { toast('Daily message limit reached. Resets tomorrow.','error'); return; }

  ta.value = '';
  cbAutoResize(ta);
  cbUpdateChars(ta);

  // Show user message
  cbAppendMessage('user', msg);

  // Show typing
  var typing = document.getElementById('cb-typing');
  typing.style.display = 'flex';
  document.getElementById('cb-send').disabled = true;
  document.getElementById('cb-status-text').textContent = 'Thinking...';
  document.getElementById('cb-status-dot').style.background = '#f59e0b';

  var fd = new FormData();
  fd.append('action', 'chat');
  fd.append('message', msg);
  if (CB.sessionId) fd.append('session_id', CB.sessionId);

  fetch('chatbot_api.php', {method:'POST', body:fd})
  .then(function(r){return r.json();})
  .then(function(d){
    typing.style.display = 'none';
    document.getElementById('cb-send').disabled = false;
    document.getElementById('cb-status-text').textContent = 'Ready';
    document.getElementById('cb-status-dot').style.background = '#10b981';

    if (!d.ok) { toast(d.error || 'AI error', 'error'); return; }

    // Set session ID if new
    if (d.session_id) CB.sessionId = d.session_id;
    CB.dailyUsed = d.daily_used || (CB.dailyUsed + 1);

    // Show AI reply
    cbAppendMessage('assistant', d.reply);

    // Update usage meter
    var pct = CB.dailyLimit > 0 ? Math.min(100, Math.round(CB.dailyUsed/CB.dailyLimit*100)) : 0;
    document.getElementById('cb-usage-text').textContent = CB.dailyUsed + ' / ' + CB.dailyLimit + ' msgs';
    document.getElementById('cb-qfill').style.width = pct+'%';
    document.getElementById('cb-qfill').style.background = pct>80?'#ef4444':pct>60?'#f59e0b':'#10b981';

    // Refresh session list
    cbLoadSessions();

    // Check quota warning
    var rem = CB.dailyLimit - CB.dailyUsed;
    if (rem <= 20) {
      document.getElementById('cb-quota-warn').style.display = 'block';
      document.getElementById('cb-quota-warn-text').textContent = rem+' messages remaining today.';
    }
  })
  .catch(function(e){
    typing.style.display = 'none';
    document.getElementById('cb-send').disabled = false;
    document.getElementById('cb-status-text').textContent = 'Error';
    document.getElementById('cb-status-dot').style.background = '#ef4444';
    toast('Network error', 'error');
    console.error(e);
  });
}

function cbNewSession() {
  CB.sessionId = null;
  CB.messages = [];
  cbRenderMessages();
  cbRenderSessions();
  document.getElementById('cb-textarea').focus();
}

function cbDelSession(sid) {
  if (!confirm('Delete this conversation?')) return;
  var fd = new FormData(); fd.append('action','delete_session'); fd.append('session_id',sid);
  fetch('chatbot_api.php',{method:'POST',body:fd})
  .then(function(r){return r.json();})
  .then(function(d){
    if (d.ok) {
      if (CB.sessionId == sid) cbNewSession();
      cbLoadSessions();
    }
  });
}

function cbUseStarter(btn) {
  var text = btn.textContent.replace(/^[^\s]+\s/,'').trim();
  document.getElementById('cb-textarea').value = text;
  cbAutoResize(document.getElementById('cb-textarea'));
  cbUpdateChars(document.getElementById('cb-textarea'));
  document.getElementById('cb-textarea').focus();
}

function cbToggleSettings() {
  var p = document.getElementById('cb-settings-panel');
  if (p) p.classList.toggle('open');
}

function cbSaveSettings() {
  var key = (document.getElementById('cb-key-input')?.value||'').trim();
  var limit = parseInt(document.getElementById('cb-daily-limit')?.value||200);
  if (!key) { toast('Enter Gemini API key','error'); return; }
  var fd = new FormData();
  fd.append('action','save_settings');
  fd.append('gemini_key', key);
  fd.append('daily_limit', limit);
  fetch('chatbot_api.php',{method:'POST',body:fd})
  .then(function(r){return r.json();})
  .then(function(d){
    if (d.ok) {
      toast('Settings saved! Click "🔌 Test" to verify.','success');
      CB.configured = true;
      document.getElementById('cb-api-banner').style.display = 'none';
      document.getElementById('cb-no-api-input').style.display = 'none';
      document.getElementById('cb-input-row').style.display = 'flex';
      if (document.getElementById('cb-key-input')) document.getElementById('cb-key-input').value = '';
      cbLoadStats();
    } else toast(d.error||'Save failed','error');
  });
}

function cbTestKey() {
  var key = (document.getElementById('cb-key-input')?.value||'').trim();
  var btn = document.getElementById('cb-test-btn');
  var res = document.getElementById('cb-test-result');
  btn.disabled = true; btn.textContent = 'Testing...';
  if (res) res.style.display = 'none';
  var fd = new FormData();
  fd.append('action','test_key');
  if (key) fd.append('gemini_key', key);
  fetch('chatbot_api.php',{method:'POST',body:fd})
  .then(function(r){return r.json();})
  .then(function(d){
    btn.disabled = false; btn.textContent = '🔌 Test';
    if (res) {
      res.style.display = 'block';
      res.style.background = d.ok ? 'rgba(16,185,129,.08)' : 'rgba(239,68,68,.06)';
      res.style.border = d.ok ? '1px solid rgba(16,185,129,.25)' : '1px solid rgba(239,68,68,.2)';
      res.style.color = d.ok ? '#10b981' : '#ef4444';
      res.style.borderRadius = '6px';
      res.style.padding = '8px 10px';
      res.textContent = d.ok ? d.message : d.error;
    }
  })
  .catch(function(){btn.disabled=false;btn.textContent='🔌 Test';});
}

// ── HELPERS ──
function cbAutoResize(ta) {
  ta.style.height = 'auto';
  ta.style.height = Math.min(ta.scrollHeight, 140) + 'px';
}
function cbUpdateChars(ta) {
  var len = ta.value.length;
  var el = document.getElementById('cb-chars');
  el.textContent = len + ' / 4000';
  el.className = 'cb-chars' + (len > 3500 ? ' warn' : '');
}
function cbFormatContent(text) {
  // Basic markdown-like formatting
  text = escHtml(text);
  // Code blocks
  text = text.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
  // Inline code
  text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
  // Bold
  text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
  // Bullets
  text = text.replace(/\n- /g, '\n• ');
  // Line breaks
  text = text.replace(/\n/g, '<br>');
  return text;
}
function escHtml(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function fmtAgo(dt){if(!dt)return'';var d=Math.floor((Date.now()-new Date(dt).getTime())/1000);if(d<60)return'just now';if(d<3600)return Math.floor(d/60)+'m ago';if(d<86400)return Math.floor(d/3600)+'h ago';return Math.floor(d/86400)+'d ago';}
function fmtTime(dt){if(!dt)return'';try{return new Date(dt).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});}catch(e){return '';}}
</script>

<?php renderLayoutEnd(); ?>