<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

// Ensure user is in #general
$general = $db->query("SELECT id FROM chat_channels WHERE type='general' AND name='general' LIMIT 1")->fetch_assoc();
if ($general) {
    $db->query("INSERT IGNORE INTO chat_members (channel_id,user_id) VALUES ({$general['id']},$uid)");
}

// Active channel from URL
$active_cid = (int)($_GET['c'] ?? $general['id'] ?? 0);
$thread_mid = (int)($_GET['t'] ?? 0); // open thread

// Channel info
$active_chan = null;
if ($active_cid) {
    $active_chan = $db->query("
        SELECT cc.*,
            CASE WHEN cc.type='direct' THEN (
                SELECT u.name FROM chat_members cm JOIN users u ON u.id=cm.user_id
                WHERE cm.channel_id=cc.id AND cm.user_id!=$uid LIMIT 1
            ) ELSE cc.name END AS display_name,
            p.title AS proj_title
        FROM chat_channels cc
        LEFT JOIN projects p ON p.id=cc.project_id
        WHERE cc.id=$active_cid
    ")->fetch_assoc();
    // Ensure member
    if ($active_chan) $db->query("INSERT IGNORE INTO chat_members (channel_id,user_id) VALUES ($active_cid,$uid)");
}

// All users for DM and mention autocomplete
$all_users = $db->query("SELECT id,name FROM users WHERE status='active' AND id!=$uid ORDER BY name")->fetch_all(MYSQLI_ASSOC);
// All projects for creating project channels
$projects  = $db->query("SELECT id,title FROM projects WHERE status NOT IN('cancelled') ORDER BY title")->fetch_all(MYSQLI_ASSOC);

// Thread message info
$thread_msg = null;
if ($thread_mid) {
    $thread_msg = $db->query("SELECT m.*,u.name AS user_name FROM chat_messages m JOIN users u ON u.id=m.user_id WHERE m.id=$thread_mid")->fetch_assoc();
}

renderLayout('Chat', 'chat');
?>

<style>
/* ── COMMUNICATION HUB ── */
#chat-wrap{display:flex;height:calc(100vh - var(--header-h) - 48px);gap:0;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}

/* SIDEBAR */
#chat-sidebar{width:240px;min-width:240px;flex-shrink:0;border-right:1px solid var(--border);display:flex;flex-direction:column;background:var(--bg2)}
#chat-sidebar-head{padding:14px 14px 10px;border-bottom:1px solid var(--border);flex-shrink:0}
#chat-sidebar-head h3{font-size:14px;font-weight:700;font-family:var(--font-display);margin-bottom:0}
#chan-list{flex:1;overflow-y:auto;padding:8px 6px}
.chan-section-lbl{font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.07em;padding:6px 8px 3px}
.chan-row{display:flex;align-items:center;gap:8px;padding:7px 8px;border-radius:var(--radius-sm);cursor:pointer;color:var(--text2);font-size:13px;transition:background .12s,color .12s;white-space:nowrap;overflow:hidden}
.chan-row:hover{background:var(--bg3);color:var(--text)}
.chan-row.active{background:var(--orange-bg);color:var(--orange);font-weight:600}
.chan-row .chan-name{flex:1;overflow:hidden;text-overflow:ellipsis}
.chan-row .chan-badge{background:var(--orange);color:#fff;font-size:9px;font-weight:800;padding:1px 5px;border-radius:99px;flex-shrink:0}

/* MAIN AREA */
#chat-main{flex:1;display:flex;flex-direction:column;min-width:0;position:relative}
#chat-header{padding:0 16px;height:48px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-shrink:0;background:var(--bg2)}
#chat-header-name{font-weight:700;font-size:14px;flex:1}
#chat-header-meta{font-size:11.5px;color:var(--text3)}

/* MESSAGES */
#msg-feed{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:2px;scroll-behavior:smooth}
.msg-date-divider{text-align:center;font-size:11px;color:var(--text3);margin:10px 0;position:relative}
.msg-date-divider::before,.msg-date-divider::after{content:'';position:absolute;top:50%;width:calc(50% - 40px);height:1px;background:var(--border)}
.msg-date-divider::before{left:0}.msg-date-divider::after{right:0}

.msg-row{display:flex;align-items:flex-start;gap:10px;padding:4px 8px;border-radius:8px;transition:background .1s;position:relative}
.msg-row:hover{background:var(--bg3)}
.msg-row:hover .msg-actions{opacity:1}
.msg-row.mine .msg-bubble{background:var(--orange-bg);border:1px solid rgba(249,115,22,.2)}
.msg-row.system{justify-content:center;padding:4px}

.msg-avatar{width:32px;height:32px;border-radius:50%;background:var(--bg4);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--text);flex-shrink:0;margin-top:2px}
.msg-body{flex:1;min-width:0}
.msg-meta{display:flex;align-items:baseline;gap:8px;margin-bottom:3px}
.msg-name{font-size:13px;font-weight:700;color:var(--text)}
.msg-time{font-size:10.5px;color:var(--text3)}
.msg-edited{font-size:10px;color:var(--text3);font-style:italic}
.msg-bubble{background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:9px 13px;font-size:13.5px;color:var(--text);line-height:1.55;word-break:break-word;display:inline-block;max-width:100%}
.msg-file{display:flex;align-items:center;gap:8px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 13px;margin-top:4px;max-width:340px}
.msg-file-icon{font-size:22px;flex-shrink:0}
.msg-file-info{flex:1;min-width:0}
.msg-file-name{font-size:12.5px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.msg-file-size{font-size:11px;color:var(--text3)}

/* REACTIONS */
.msg-reactions{display:flex;gap:4px;flex-wrap:wrap;margin-top:4px}
.reaction-pill{display:inline-flex;align-items:center;gap:3px;background:var(--bg3);border:1px solid var(--border);border-radius:99px;padding:2px 8px;font-size:12px;cursor:pointer;transition:background .1s}
.reaction-pill:hover,.reaction-pill.mine{background:var(--orange-bg);border-color:var(--orange)}
.reaction-pill .cnt{font-size:11px;font-weight:700;color:var(--text2)}

/* THREAD REPLY indicator */
.msg-thread-btn{font-size:11px;color:var(--text3);margin-top:3px;cursor:pointer;display:inline-flex;align-items:center;gap:4px}
.msg-thread-btn:hover{color:var(--orange)}

/* ACTION BUTTONS (hover) */
.msg-actions{position:absolute;right:8px;top:4px;display:flex;gap:3px;opacity:0;transition:opacity .15s;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:2px 4px}
.msg-act-btn{background:none;border:none;cursor:pointer;padding:3px 5px;border-radius:4px;font-size:13px;color:var(--text3);transition:background .1s,color .1s;line-height:1}
.msg-act-btn:hover{background:var(--bg3);color:var(--text)}

/* INPUT AREA */
#chat-input-area{padding:10px 14px;border-top:1px solid var(--border);flex-shrink:0;background:var(--bg2)}
.input-wrap{background:var(--bg3);border:1px solid var(--border);border-radius:12px;overflow:hidden;transition:border-color .15s}
.input-wrap:focus-within{border-color:var(--orange)}
.input-toolbar{display:flex;align-items:center;gap:4px;padding:6px 10px 0;border-bottom:1px solid var(--border)}
.inp-btn{background:none;border:none;cursor:pointer;padding:4px 6px;border-radius:4px;color:var(--text3);font-size:14px;transition:background .1s,color .1s}
.inp-btn:hover{background:var(--bg4);color:var(--text)}
#msg-input{width:100%;background:none;border:none;padding:10px 12px;color:var(--text);font-size:13.5px;font-family:var(--font);resize:none;outline:none;min-height:44px;max-height:140px;line-height:1.5}
.input-footer{display:flex;align-items:center;justify-content:space-between;padding:4px 10px 6px}
.input-hint{font-size:11px;color:var(--text3)}
#send-btn{background:var(--orange);color:#fff;border:none;border-radius:8px;padding:6px 16px;font-size:13px;font-weight:700;cursor:pointer;transition:opacity .15s}
#send-btn:hover{opacity:.9}
#send-btn:disabled{opacity:.4;cursor:not-allowed}

/* THREAD PANEL */
#thread-panel{width:320px;flex-shrink:0;border-left:1px solid var(--border);display:none;flex-direction:column;background:var(--bg2)}
#thread-panel.open{display:flex}
#thread-head{padding:12px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
#thread-feed{flex:1;overflow-y:auto;padding:12px}
#thread-input-area{padding:10px;border-top:1px solid var(--border);flex-shrink:0}

/* EMOJI PICKER (simple) */
.emoji-picker{position:absolute;bottom:100%;left:0;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:8px;display:none;z-index:200;box-shadow:var(--shadow-lg)}
.emoji-picker.open{display:flex;flex-wrap:wrap;gap:4px;width:220px}
.emoji-opt{font-size:18px;cursor:pointer;padding:3px;border-radius:4px;transition:background .1s}
.emoji-opt:hover{background:var(--bg3)}

/* @MENTION DROPDOWN */
#mention-dropdown{position:absolute;bottom:100%;left:0;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);z-index:300;min-width:180px;box-shadow:var(--shadow-lg);display:none}
#mention-dropdown.open{display:block}
.mention-opt{padding:8px 12px;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2)}
.mention-opt:hover,.mention-opt.selected{background:var(--bg3);color:var(--text)}

/* File preview */
#file-preview{display:none;align-items:center;gap:8px;padding:6px 12px;background:var(--bg4);border-bottom:1px solid var(--border);font-size:12px;color:var(--text2)}
#file-preview.show{display:flex}

/* MOBILE */
@media(max-width:900px){
  #chat-sidebar{width:200px;min-width:200px}
  #thread-panel{position:absolute;right:0;top:0;bottom:0;width:280px;z-index:50;box-shadow:var(--shadow-lg)}
}
@media(max-width:600px){
  #chat-sidebar{display:none}
  #chat-sidebar.mob-open{display:flex;position:absolute;left:0;top:0;bottom:0;z-index:60;box-shadow:var(--shadow-lg)}
  #mob-sidebar-toggle{display:flex}
}
#mob-sidebar-toggle{display:none;background:none;border:none;color:var(--text);font-size:18px;cursor:pointer;padding:4px}
</style>

<div id="chat-wrap">

  <!-- ══ SIDEBAR ══ -->
  <div id="chat-sidebar">
    <div id="chat-sidebar-head">
      <h3>💬 Messages</h3>
    </div>
    <div id="chan-list">
      <div class="chan-section-lbl">Channels</div>
      <div id="channel-list-general"></div>
      <div class="chan-section-lbl" style="margin-top:8px">Direct Messages</div>
      <div id="channel-list-dm"></div>
      <?php if (isManager()): ?>
      <div style="padding:6px 8px">
        <button class="btn btn-ghost btn-sm" style="width:100%;justify-content:flex-start;font-size:12px" onclick="openModal('modal-new-channel')">＋ New Channel</button>
      </div>
      <?php endif; ?>
      <div class="chan-section-lbl" style="margin-top:8px">Direct Message</div>
      <div style="padding:0 8px 6px">
        <select id="dm-target" class="form-control" style="font-size:12px;padding:5px 8px" onchange="startDM(this.value)">
          <option value="">Start DM with…</option>
          <?php foreach ($all_users as $u): ?>
          <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- ══ MAIN CHAT ══ -->
  <div id="chat-main">
    <!-- Header -->
    <div id="chat-header">
      <button id="mob-sidebar-toggle" onclick="document.getElementById('chat-sidebar').classList.toggle('mob-open')">☰</button>
      <div id="chat-header-name">Select a channel</div>
      <div id="chat-header-meta"></div>
    </div>

    <!-- Messages feed -->
    <div id="msg-feed">
      <div style="text-align:center;padding:40px 20px;color:var(--text3)">
        <div style="font-size:32px;margin-bottom:8px">💬</div>
        <div style="font-size:14px">Select a channel to start chatting</div>
      </div>
    </div>

    <!-- File preview bar -->
    <div id="file-preview">
      <span id="fp-icon">📎</span>
      <span id="fp-name"></span>
      <span id="fp-size" style="color:var(--text3)"></span>
      <button onclick="clearFile()" style="background:none;border:none;color:var(--red);cursor:pointer;margin-left:auto">✕</button>
    </div>

    <!-- Input area -->
    <div id="chat-input-area" style="display:none">
      <div class="input-wrap" style="position:relative">
        <div class="input-toolbar">
          <button class="inp-btn" onclick="toggleEmoji()" title="Emoji">😊</button>
          <label class="inp-btn" title="Attach file" style="cursor:pointer">
            📎<input type="file" id="file-input" style="display:none" onchange="handleFile(this)">
          </label>
          <button class="inp-btn" onclick="insertMd('**','**')" title="Bold"><b>B</b></button>
          <button class="inp-btn" onclick="insertMd('*','*')" title="Italic"><i>I</i></button>
          <button class="inp-btn" onclick="insertMd('`','`')" title="Code" style="font-family:monospace">{ }</button>
        </div>
        <!-- Mention dropdown -->
        <div id="mention-dropdown"></div>
        <!-- Emoji picker -->
        <div class="emoji-picker" id="emoji-picker">
          <?php foreach (['👍','❤️','😂','🎉','🔥','✅','⚡','💡','🤔','😮','👏','🙏','💯','🚀','⭐','😅'] as $em): ?>
          <span class="emoji-opt" onclick="insertEmoji('<?= $em ?>')"><?= $em ?></span>
          <?php endforeach; ?>
        </div>
        <textarea id="msg-input" placeholder="Message #general… (@ to mention)" rows="1"
          onkeydown="handleKey(event)"
          oninput="autoResize(this);handleMention(this)"></textarea>
        <div class="input-footer">
          <span class="input-hint">Enter to send · Shift+Enter for newline · @ to mention</span>
          <button id="send-btn" onclick="sendMessage()" disabled>Send ↑</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ THREAD PANEL ══ -->
  <div id="thread-panel" class="<?= $thread_mid?'open':'' ?>">
    <div id="thread-head">
      <div style="font-size:13px;font-weight:700">🧵 Thread</div>
      <button onclick="closeThread()" style="background:none;border:none;color:var(--text3);cursor:pointer;font-size:16px">✕</button>
    </div>
    <div id="thread-feed"></div>
    <div id="thread-input-area">
      <div class="input-wrap">
        <textarea id="thread-input" placeholder="Reply in thread…" rows="2" style="width:100%;background:none;border:none;padding:8px 10px;color:var(--text);font-family:var(--font);font-size:13px;resize:none;outline:none"
          onkeydown="handleThreadKey(event)"></textarea>
      </div>
      <button class="btn btn-primary btn-sm" style="margin-top:6px;width:100%" onclick="sendThreadReply()">Reply</button>
    </div>
  </div>
</div>

<!-- ══ NEW CHANNEL MODAL ══ -->
<div class="modal-overlay" id="modal-new-channel">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title">＋ New Channel</div>
      <button class="modal-close" onclick="closeModal('modal-new-channel')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Channel Name *</label>
        <input type="text" id="new-chan-name" class="form-control" placeholder="e.g. design-team, project-updates">
      </div>
      <div class="form-group">
        <label class="form-label">Members</label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:10px;max-height:160px;overflow-y:auto">
          <?php foreach ($all_users as $u): ?>
          <label style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--text2);cursor:pointer">
            <input type="checkbox" class="new-chan-member" value="<?= $u['id'] ?>" style="accent-color:var(--orange)">
            <?= htmlspecialchars($u['name']) ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-new-channel')">Cancel</button>
      <button class="btn btn-primary" onclick="createChannel()">Create Channel</button>
    </div>
  </div>
</div>

<script>
// ══ STATE ══
var CID = <?= $active_cid ?: 'null' ?>;
var THREAD_OF = <?= $thread_mid ?: 'null' ?>;
var UID = <?= $uid ?>;
var serverTime = null;
var pollTimer  = null;
var msgIndex   = {};      // id -> DOM element
var mentionIdx = -1;
var pendingFile= null;

// ══ INIT ══
document.addEventListener('DOMContentLoaded', function() {
    loadChannels();
    if (CID) openChannel(CID);
    if (THREAD_OF) openThread(THREAD_OF);
    // Poll started by loadMessages after first channel load
    // If no CID, start poll anyway for unread badge updates
    if (!CID) { pollTimer = setInterval(poll, 4000); }
    // Enable send button
    document.getElementById('msg-input').addEventListener('input', function() {
        document.getElementById('send-btn').disabled = !this.value.trim() && !pendingFile;
    });
});

// ══ API CALL ══
function api(params, method, cb) {
    method = method || 'GET';
    var url = 'chat_api.php';
    var opts = { method: method };
    if (method === 'GET') {
        url += '?' + new URLSearchParams(params).toString();
    } else {
        var fd = new FormData();
        Object.keys(params).forEach(function(k) {
            // Don't append array values directly - they become "[object Object]"
            if (Array.isArray(params[k])) {
                params[k].forEach(function(v) { fd.append(k, v); });
            } else {
                fd.append(k, params[k] === null ? '' : params[k]);
            }
        });
        if (pendingFile && params.action === 'send') { fd.append('file', pendingFile); }
        opts.body = fd;
    }
    fetch(url, opts)
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(cb)
        .catch(function(e) { console.error('API error:', e); });
}

// ══ LOAD CHANNELS ══
function loadChannels() {
    api({ action: 'get_channels' }, 'GET', function(d) {
        if (!d.ok) return;
        var gen = document.getElementById('channel-list-general');
        var dm  = document.getElementById('channel-list-dm');
        gen.innerHTML = ''; dm.innerHTML = '';
        d.channels.forEach(function(c) {
            var el = document.createElement('div');
            el.className = 'chan-row' + (c.id == CID ? ' active' : '');
            el.dataset.cid = c.id;
            var icon = c.type === 'direct' ? '💬' : '#';
            var badge = c.unread > 0 ? '<span class="chan-badge">'+c.unread+'</span>' : '';
            el.innerHTML = '<span style="flex-shrink:0">' + icon + '</span><span class="chan-name">' + escHtml(c.display_name || 'unnamed') + '</span>' + badge;
            el.onclick = function() { openChannel(c.id); };
            (c.type === 'direct' ? dm : gen).appendChild(el);
        });
    });
}

// ══ OPEN CHANNEL ══
function openChannel(cid) {
    CID = cid;
    THREAD_OF = null;
    closeThread();
    // Update active
    document.querySelectorAll('.chan-row').forEach(function(el) {
        el.classList.toggle('active', el.dataset.cid == cid);
    });
    // Update header
    api({ action: 'get_channels' }, 'GET', function(d) {
        if (!d.ok) return;
        var ch = d.channels.find(function(c) { return c.id == cid; });
        if (ch) {
            document.getElementById('chat-header-name').textContent =
                (ch.type === 'direct' ? '💬 ' : '# ') + (ch.display_name || 'unnamed');
            document.getElementById('chat-header-meta').textContent =
                ch.proj_title ? '📁 ' + ch.proj_title : '';
        }
    });
    document.getElementById('chat-input-area').style.display = '';
    document.getElementById('msg-input').placeholder = 'Message… (@ to mention)';
    // Clear feed immediately (synchronous) before async load
    var feed = document.getElementById('msg-feed');
    feed.innerHTML = '<div style="text-align:center;padding:30px;color:var(--text3)"><div style="font-size:20px;margin-bottom:6px">⏳</div><div style="font-size:13px">Loading messages…</div></div>';
    msgIndex = {};
    serverTime = null; // Reset poll timer for new channel
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } // Stop poll during load
    // Load messages
    loadMessages(cid, false);
    // Update URL
    history.replaceState(null, '', 'chat.php?c=' + cid);
}

// ══ LOAD MESSAGES ══
function loadMessages(cid, append, before) {
    var params = { action: 'get_messages', channel_id: cid };
    if (before) params.before = before;
    api(params, 'GET', function(d) {
        if (!d.ok) {
            console.error('get_messages error:', d.error);
            var feed = document.getElementById('msg-feed');
            feed.innerHTML = '<div style="text-align:center;padding:30px;color:var(--red)">⚠ ' + (d.error || 'Failed to load messages') + '</div>';
            return;
        }
        var feed = document.getElementById('msg-feed');
        if (!append) {
            feed.innerHTML = '';
            msgIndex = {};
        }
        if (!d.messages.length && !append) {
            feed.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3)"><div style="font-size:28px;margin-bottom:8px">👋</div><div>No messages yet. Say hello!</div></div>';
            return;
        }
        var lastDate = '';
        d.messages.forEach(function(m) {
            if (m.date !== lastDate) {
                var div = document.createElement('div');
                div.className = 'msg-date-divider';
                div.textContent = m.date;
                feed.appendChild(div);
                lastDate = m.date;
            }
            appendMsg(feed, m);
        });
        if (!append) feed.scrollTop = feed.scrollHeight;
        // Set serverTime: use last message time, or current time if no messages
        if (d.messages.length) {
            serverTime = d.messages[d.messages.length-1].iso;
        } else if (!serverTime) {
            var now = new Date();
            serverTime = now.getFullYear()+'-'+
                String(now.getMonth()+1).padStart(2,'0')+'-'+
                String(now.getDate()).padStart(2,'0')+' '+
                String(now.getHours()).padStart(2,'0')+':'+
                String(now.getMinutes()).padStart(2,'0')+':'+
                String(now.getSeconds()).padStart(2,'0');
        }
        // Restart poll after messages loaded
        if (!pollTimer) { pollTimer = setInterval(poll, 4000); }
    });
}

// ══ RENDER MESSAGE ══
function appendMsg(container, m) {
    if (msgIndex[m.id]) return; // already rendered
    var row = document.createElement('div');
    row.className = 'msg-row' + (m.mine ? ' mine' : '');
    row.id = 'msg-' + m.id;
    msgIndex[m.id] = true;

    var isImg = m.file_url && /\.(jpg|jpeg|png|gif|webp)$/i.test(m.file_url);
    var fileHtml = '';
    if (m.file_url) {
        if (isImg) {
            fileHtml = '<div style="margin-top:6px"><img src="'+escHtml(m.file_url)+'" style="max-width:260px;max-height:200px;border-radius:8px;display:block;border:1px solid var(--border)" loading="lazy"></div>';
        } else {
            var sizeStr = m.file_size ? ' · ' + Math.round(m.file_size/1024) + ' KB' : '';
            fileHtml = '<div class="msg-file"><span class="msg-file-icon">📎</span><div class="msg-file-info"><div class="msg-file-name">'+escHtml(m.file_name)+'</div><div class="msg-file-size">'+sizeStr+'</div></div><a href="'+escHtml(m.file_url)+'" download class="btn btn-ghost btn-sm btn-icon" style="flex-shrink:0">↓</a></div>';
        }
    }

    var reactHtml = '';
    if (m.reactions && m.reactions.length) {
        reactHtml = '<div class="msg-reactions">';
        m.reactions.forEach(function(r) {
            if (!r || !r.emoji) return;
            reactHtml += '<span class="reaction-pill'+(r.mine?' mine':'')+'" onclick="react('+m.id+',\''+r.emoji+'\')" title="React"><span>'+r.emoji+'</span><span class="cnt">'+r.cnt+'</span></span>';
        });
        reactHtml += '</div>';
    }

    var threadHtml = (!m.parent_id && m.reply_count > 0)
        ? '<div class="msg-thread-btn" onclick="openThread('+m.id+')">🧵 '+m.reply_count+' repl'+(m.reply_count===1?'y':'ies')+'</div>' : '';

    var actionsHtml = '<div class="msg-actions">'
        + '<button class="msg-act-btn" title="React 👍" onclick="react('+m.id+',\'👍\')">👍</button>'
        + '<button class="msg-act-btn" title="React ❤️" onclick="react('+m.id+',\'❤️\')">❤️</button>'
        + '<button class="msg-act-btn" title="Reply in thread" onclick="openThread('+m.id+')">🧵</button>'
        + (m.mine ? '<button class="msg-act-btn" title="Edit" onclick="editMsg('+m.id+',this)">✎</button>'
                  + '<button class="msg-act-btn" title="Delete" onclick="deleteMsg('+m.id+')">🗑</button>' : '')
        + '</div>';

    row.innerHTML =
        '<div class="msg-avatar">'+escHtml(m.user_init)+'</div>'
        + '<div class="msg-body">'
            + '<div class="msg-meta">'
                + '<span class="msg-name">'+escHtml(m.user_name)+'</span>'
                + '<span class="msg-time">'+escHtml(m.time)+'</span>'
                + (m.edited ? '<span class="msg-edited">(edited)</span>' : '')
            + '</div>'
            + (m.deleted ? '<span style="color:var(--text3);font-style:italic;font-size:12.5px">Message deleted</span>'
                         : '<div class="msg-bubble">'+m.body_html+'</div>')
            + fileHtml
            + reactHtml
            + threadHtml
        + '</div>'
        + actionsHtml;

    container.appendChild(row);
}

// ══ SEND MESSAGE ══
function sendMessage() {
    var input = document.getElementById('msg-input');
    var body  = input.value.trim();
    if (!body && !pendingFile) return;
    if (!CID) return;

    var params = { action: 'send', channel_id: CID, body: body };
    api(params, 'POST', function(d) {
        if (!d.ok) { toast(d.error || 'Send failed', 'error'); return; }
        input.value = '';
        input.style.height = 'auto';
        document.getElementById('send-btn').disabled = true;
        clearFile();
        var feed = document.getElementById('msg-feed');
        if (d.message) {
            appendMsg(feed, d.message);
            feed.scrollTop = feed.scrollHeight;
            serverTime = d.message.iso;
        } else {
            // Fallback: reload all messages if no message returned
            loadMessages(CID, false);
        }
    });
}

// ══ THREAD ══
function openThread(mid) {
    THREAD_OF = mid;
    var panel = document.getElementById('thread-panel');
    panel.classList.add('open');
    loadThread(mid);
}
function closeThread() {
    THREAD_OF = null;
    document.getElementById('thread-panel').classList.remove('open');
    document.getElementById('thread-feed').innerHTML = '';
}
function loadThread(mid) {
    api({ action: 'get_messages', channel_id: CID, thread_of: mid }, 'GET', function(d) {
        if (!d.ok) return;
        var feed = document.getElementById('thread-feed');
        feed.innerHTML = '';
        d.messages.forEach(function(m) { appendMsg(feed, m); });
        feed.scrollTop = feed.scrollHeight;
    });
}
function sendThreadReply() {
    var input = document.getElementById('thread-input');
    var body  = input.value.trim();
    if (!body || !THREAD_OF || !CID) return;
    api({ action: 'send', channel_id: CID, body: body, parent_id: THREAD_OF }, 'POST', function(d) {
        if (!d.ok) { toast(d.error || 'Send failed', 'error'); return; }
        input.value = '';
        loadThread(THREAD_OF);
        // Update reply count on parent
        loadMessages(CID, false);
    });
}
function handleThreadKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendThreadReply(); }
}

// ══ EDIT / DELETE ══
function editMsg(id, btn) {
    var row    = document.getElementById('msg-' + id);
    var bubble = row.querySelector('.msg-bubble');
    var orig   = bubble.dataset.raw || bubble.textContent;
    bubble.dataset.raw = orig;
    bubble.innerHTML = '<textarea style="width:100%;background:var(--bg4);border:1px solid var(--orange);border-radius:6px;padding:6px;color:var(--text);font-family:var(--font);font-size:13px;resize:none" id="edit-'+id+'">'+escHtml(orig)+'</textarea>'
        + '<div style="display:flex;gap:6px;margin-top:4px">'
        + '<button class="btn btn-primary btn-sm" onclick="saveEdit('+id+')">Save</button>'
        + '<button class="btn btn-ghost btn-sm" onclick="cancelEdit('+id+',\''+orig.replace(/'/g,"\\'")+'\')" >Cancel</button></div>';
}
function saveEdit(id) {
    var val = document.getElementById('edit-'+id).value.trim();
    if (!val) return;
    api({ action: 'edit', id: id, body: val }, 'POST', function(d) {
        if (d.ok) { loadMessages(CID, false); }
    });
}
function cancelEdit(id, orig) {
    var bubble = document.getElementById('msg-'+id).querySelector('.msg-bubble');
    bubble.innerHTML = orig;
}
function deleteMsg(id) {
    if (!confirm('Delete this message?')) return;
    api({ action: 'delete', id: id }, 'POST', function(d) {
        if (d.ok) { loadMessages(CID, false); }
    });
}

// ══ REACTIONS ══
function react(mid, emoji) {
    api({ action: 'react', id: mid, emoji: emoji }, 'POST', function(d) {
        if (d.ok) setTimeout(function(){ loadMessages(CID, false); }, 200);
    });
}

// ══ POLL (new messages) ══
function poll() {
    if (!CID || !serverTime) return;
    api({ action: 'poll', channel_id: CID, since: serverTime }, 'GET', function(d) {
        if (!d.ok) return;
        if (d.server_time) serverTime = d.server_time;
        if (d.messages && d.messages.length) {
            var feed = document.getElementById('msg-feed');
            var atBottom = feed.scrollHeight - feed.clientHeight - feed.scrollTop < 60;
            d.messages.forEach(function(m) { appendMsg(feed, m); });
            if (atBottom) feed.scrollTop = feed.scrollHeight;
        }
        // Update unread badges
        if (d.unread) {
            Object.keys(d.unread).forEach(function(cid) {
                var row = document.querySelector('.chan-row[data-cid="'+cid+'"]');
                if (!row) return;
                var badge = row.querySelector('.chan-badge');
                var cnt = parseInt(d.unread[cid]) || 0;
                if (cnt > 0) {
                    if (!badge) { badge = document.createElement('span'); badge.className='chan-badge'; row.appendChild(badge); }
                    badge.textContent = cnt;
                } else if (badge) badge.remove();
            });
        }
    });
}

// ══ DM ══
function startDM(targetUid) {
    if (!targetUid) return;
    document.getElementById('dm-target').value = '';
    api({ action: 'create_dm', user_id: targetUid }, 'POST', function(d) {
        if (d.ok && d.channel_id) { loadChannels(); openChannel(d.channel_id); }
    });
}

// ══ CREATE CHANNEL ══
function createChannel() {
    var name = document.getElementById('new-chan-name').value.trim();
    if (!name) { toast('Name required', 'error'); return; }
    var members = Array.from(document.querySelectorAll('.new-chan-member:checked')).map(function(el){ return el.value; });
    // Build FormData manually to correctly send array values
    var fd = new FormData();
    fd.append('action', 'create_channel');
    fd.append('name', name);
    members.forEach(function(m) { fd.append('members[]', m); });
    fetch('chat_api.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.ok) { closeModal('modal-new-channel'); loadChannels(); openChannel(d.channel_id); }
            else toast(d.error || 'Failed', 'error');
        })
        .catch(function(e) { toast('Request failed', 'error'); });
}

// ══ FILE HANDLING ══
function handleFile(input) {
    if (!input.files[0]) return;
    pendingFile = input.files[0];
    var fp = document.getElementById('file-preview');
    fp.classList.add('show');
    document.getElementById('fp-name').textContent = pendingFile.name;
    document.getElementById('fp-size').textContent = Math.round(pendingFile.size/1024) + ' KB';
    document.getElementById('fp-icon').textContent = /image/i.test(pendingFile.type) ? '🖼' : '📎';
    document.getElementById('send-btn').disabled = false;
}
function clearFile() {
    pendingFile = null;
    document.getElementById('file-input').value = '';
    document.getElementById('file-preview').classList.remove('show');
}

// ══ INPUT HELPERS ══
function handleKey(e) {
    // Mention dropdown navigation
    var dd = document.getElementById('mention-dropdown');
    if (dd.classList.contains('open')) {
        var opts = dd.querySelectorAll('.mention-opt');
        if (e.key === 'ArrowDown') { e.preventDefault(); mentionIdx = Math.min(mentionIdx+1, opts.length-1); opts.forEach(function(o,i){o.classList.toggle('selected',i===mentionIdx);}); return; }
        if (e.key === 'ArrowUp')   { e.preventDefault(); mentionIdx = Math.max(mentionIdx-1, 0); opts.forEach(function(o,i){o.classList.toggle('selected',i===mentionIdx);}); return; }
        if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); if (opts[mentionIdx]) opts[mentionIdx].click(); return; }
        if (e.key === 'Escape') { dd.classList.remove('open'); return; }
    }
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
}
function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 140) + 'px';
}
function insertMd(pre, post) {
    var t = document.getElementById('msg-input');
    var s = t.selectionStart, e = t.selectionEnd;
    var sel = t.value.substring(s, e) || 'text';
    t.value = t.value.substring(0,s) + pre + sel + post + t.value.substring(e);
    t.focus();
    t.selectionStart = s + pre.length;
    t.selectionEnd   = s + pre.length + sel.length;
    document.getElementById('send-btn').disabled = !t.value.trim();
}
function insertEmoji(em) {
    var t = document.getElementById('msg-input');
    t.value += em;
    t.focus();
    document.getElementById('emoji-picker').classList.remove('open');
    document.getElementById('send-btn').disabled = false;
}
function toggleEmoji() {
    document.getElementById('emoji-picker').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('#emoji-picker') && !e.target.closest('.inp-btn'))
        document.getElementById('emoji-picker').classList.remove('open');
    if (!e.target.closest('#mention-dropdown') && !e.target.closest('#msg-input'))
        document.getElementById('mention-dropdown').classList.remove('open');
});

// ══ @MENTION AUTOCOMPLETE ══
var mentionQuery = null;
function handleMention(el) {
    var val   = el.value;
    var pos   = el.selectionStart;
    var atIdx = val.lastIndexOf('@', pos-1);
    if (atIdx === -1 || (atIdx > 0 && !/\s/.test(val[atIdx-1]))) {
        document.getElementById('mention-dropdown').classList.remove('open');
        mentionQuery = null; return;
    }
    var q = val.substring(atIdx+1, pos);
    if (mentionQuery === q) return;
    mentionQuery = q;
    api({ action: 'get_users', q: q }, 'GET', function(d) {
        if (!d.ok || !d.users.length) { document.getElementById('mention-dropdown').classList.remove('open'); return; }
        var dd = document.getElementById('mention-dropdown');
        dd.innerHTML = '';
        mentionIdx = 0;
        d.users.forEach(function(u, i) {
            var opt = document.createElement('div');
            opt.className = 'mention-opt' + (i===0?' selected':'');
            opt.innerHTML = '<div class="avatar" style="width:22px;height:22px;font-size:9px;background:var(--bg4)">'+escHtml(u.name[0].toUpperCase())+'</div>'+escHtml(u.name);
            opt.onclick = function() {
                var inp = document.getElementById('msg-input');
                var v = inp.value, p = inp.selectionStart;
                var ai = v.lastIndexOf('@', p-1);
                inp.value = v.substring(0,ai) + '@' + u.name + ' ' + v.substring(p);
                dd.classList.remove('open'); mentionQuery = null;
                inp.focus();
                document.getElementById('send-btn').disabled = !inp.value.trim();
            };
            dd.appendChild(opt);
        });
        dd.classList.add('open');
    });
}

// ══ UTILITY ══
function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php renderLayoutEnd(); ?>