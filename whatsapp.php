<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_template') {
        $name = trim($_POST['name'] ?? '');
        $cat  = $_POST['category'] ?? 'custom';
        $msg  = trim($_POST['message'] ?? '');
        $vars = trim($_POST['variables'] ?? '');
        if ($name && $msg) {
            $tid = (int)($_POST['template_id'] ?? 0);
            if ($tid) {
                $stmt = $db->prepare("UPDATE whatsapp_templates SET name=?,category=?,message=?,variables=? WHERE id=$tid");
                $stmt->bind_param("ssss",$name,$cat,$msg,$vars);
            } else {
                $stmt = $db->prepare("INSERT INTO whatsapp_templates (name,category,message,variables,created_by) VALUES (?,?,?,?,?)");
                $stmt->bind_param("ssssi",$name,$cat,$msg,$vars,$uid);
            }
            $stmt->execute();
            flash('Template saved.','success');
        }
        ob_end_clean(); header('Location: whatsapp.php'); exit;
    }

    if ($action === 'delete_template') {
        $tid = (int)($_POST['template_id'] ?? 0);
        $db->query("DELETE FROM whatsapp_templates WHERE id=$tid AND is_active=1");
        flash('Template deleted.','success');
        ob_end_clean(); header('Location: whatsapp.php'); exit;
    }
}
ob_end_clean();

// Load templates
$templates = $db->query("SELECT * FROM whatsapp_templates WHERE is_active=1 ORDER BY category,name")->fetch_all(MYSQLI_ASSOC);

// Load contacts & leads with phone numbers
$contacts = $db->query("SELECT id,name,company,phone,type FROM contacts WHERE phone IS NOT NULL AND phone!='' ORDER BY name LIMIT 100")->fetch_all(MYSQLI_ASSOC);
$leads    = $db->query("SELECT id,name,company,phone FROM leads WHERE phone IS NOT NULL AND phone!='' AND stage NOT IN('won','lost') ORDER BY name LIMIT 100")->fetch_all(MYSQLI_ASSOC);

// Template categories
$cats = ['greeting'=>'👋 Greeting','follow_up'=>'🔄 Follow Up','invoice'=>'🧾 Invoice','proposal'=>'📄 Proposal','support'=>'🛠 Support','reminder'=>'⏰ Reminder','custom'=>'✏️ Custom'];

renderLayout('WhatsApp', 'whatsapp');
?>
<style>
.wa-grid{display:grid;grid-template-columns:340px 1fr;gap:18px;align-items:start}
.wa-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.wa-card-head{padding:12px 16px;border-bottom:1px solid var(--border);background:var(--bg3);display:flex;align-items:center;justify-content:space-between}
.wa-card-title{font-size:13px;font-weight:700;font-family:var(--font-display);display:flex;align-items:center;gap:7px}
.wa-contact-item{display:flex;align-items:center;gap:10px;padding:9px 14px;border-bottom:1px solid var(--border);cursor:pointer;transition:background .1s}
.wa-contact-item:hover{background:var(--bg3)}
.wa-contact-item:last-child{border-bottom:none}
.wa-contact-item.selected{background:rgba(37,211,102,.08);border-left:3px solid #25d366}
.wa-avatar{width:36px;height:36px;border-radius:50%;background:var(--bg4);display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.wa-name{font-size:13px;font-weight:600;color:var(--text)}
.wa-meta{font-size:11.5px;color:var(--text3)}
.wa-phone{font-size:12px;color:var(--text2);font-family:monospace}
.wa-tpl-item{padding:12px 14px;border-bottom:1px solid var(--border);cursor:pointer;transition:background .1s}
.wa-tpl-item:hover{background:var(--bg3)}
.wa-tpl-item:last-child{border-bottom:none}
.wa-tpl-name{font-size:13px;font-weight:600;color:var(--text);margin-bottom:3px}
.wa-tpl-preview{font-size:12px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.wa-cat-badge{font-size:10px;padding:2px 7px;border-radius:99px;background:var(--bg4);color:var(--text3);margin-left:6px}
.wa-composer{background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:14px;margin-top:14px}
.wa-bubble{background:#d9fdd3;border-radius:12px 12px 0 12px;padding:10px 14px;font-size:13.5px;color:#1a1a1a;max-width:100%;word-break:break-word;line-height:1.5;box-shadow:0 1px 2px rgba(0,0,0,.1);font-family:Arial,sans-serif}
.wa-send-btn{background:#25d366;color:#fff;border:none;border-radius:var(--radius-sm);padding:10px 20px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;transition:opacity .15s}
.wa-send-btn:hover{opacity:.88}
.wa-send-btn.copy-mode{background:var(--orange)}
.var-chip{display:inline-block;background:var(--orange-bg);color:var(--orange);border:1px solid rgba(249,115,22,.3);border-radius:99px;padding:2px 8px;font-size:11px;font-weight:600;cursor:pointer;margin:2px}
.var-chip:hover{background:rgba(249,115,22,.2)}
@media(max-width:900px){.wa-grid{grid-template-columns:1fr}}
</style>

<div class="wa-grid">
  <!-- LEFT: Contacts list -->
  <div>
    <div class="wa-card" style="margin-bottom:14px">
      <div class="wa-card-head">
        <div class="wa-card-title">👥 Contacts</div>
        <input type="text" id="contact-search" placeholder="Search..." style="padding:4px 8px;font-size:12px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);width:130px" oninput="filterContacts(this.value)">
      </div>
      <div id="contact-list" style="max-height:280px;overflow-y:auto">
        <?php if ($contacts): foreach ($contacts as $c): ?>
        <div class="wa-contact-item" data-name="<?= h($c['name']) ?>" data-phone="<?= h($c['phone']) ?>" data-company="<?= h($c['company']??'') ?>" onclick="selectContact(this)">
          <div class="wa-avatar">👤</div>
          <div style="flex:1;min-width:0">
            <div class="wa-name"><?= h($c['name']) ?></div>
            <div class="wa-meta"><?= h($c['company']??$c['type']) ?></div>
          </div>
          <div class="wa-phone"><?= h($c['phone']) ?></div>
        </div>
        <?php endforeach; else: ?>
        <div style="padding:20px;text-align:center;color:var(--text3);font-size:12.5px">No contacts with phone numbers</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Leads with phone -->
    <?php if ($leads): ?>
    <div class="wa-card">
      <div class="wa-card-head"><div class="wa-card-title">🎯 Active Leads</div></div>
      <div style="max-height:200px;overflow-y:auto">
        <?php foreach ($leads as $l): ?>
        <div class="wa-contact-item" data-name="<?= h($l['name']) ?>" data-phone="<?= h($l['phone']) ?>" data-company="<?= h($l['company']??'') ?>" onclick="selectContact(this)">
          <div class="wa-avatar">🎯</div>
          <div style="flex:1;min-width:0">
            <div class="wa-name"><?= h($l['name']) ?></div>
            <div class="wa-meta"><?= h($l['company']??'') ?></div>
          </div>
          <div class="wa-phone"><?= h($l['phone']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- RIGHT: Composer + templates -->
  <div>
    <!-- Message Composer -->
    <div class="wa-card" style="margin-bottom:14px">
      <div class="wa-card-head">
        <div class="wa-card-title">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="#25d366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          Compose Message
        </div>
        <span id="selected-contact-name" style="font-size:12px;color:var(--text3)">No contact selected</span>
      </div>
      <div style="padding:16px">
        <!-- Recipient -->
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="tel" id="wa-phone" class="form-control" placeholder="+94771234567 or 0771234567" oninput="updatePreview()">
        </div>

        <!-- Template select -->
        <div class="form-group">
          <label class="form-label">Use Template</label>
          <select id="wa-tpl-select" class="form-control" onchange="applyTemplate(this)">
            <option value="">— Custom message —</option>
            <?php foreach ($templates as $t): ?>
            <option value="<?= $t['id'] ?>" data-msg="<?= h($t['message']) ?>" data-vars="<?= h($t['variables']??'') ?>"><?= h($cats[$t['category']]??$t['category']) ?> — <?= h($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Variable substitution chips -->
        <div id="var-chips" style="margin-bottom:10px;display:none">
          <div style="font-size:11px;color:var(--text3);margin-bottom:5px">Click to fill variables:</div>
          <div id="var-chips-list"></div>
        </div>

        <!-- Message textarea -->
        <div class="form-group">
          <label class="form-label">Message</label>
          <textarea id="wa-message" class="form-control" rows="5" placeholder="Type your message here..." oninput="updatePreview()" style="resize:vertical"></textarea>
          <div style="display:flex;justify-content:flex-end;margin-top:4px"><span id="wa-char-count" style="font-size:11px;color:var(--text3)">0 characters</span></div>
        </div>

        <!-- Preview bubble -->
        <div style="background:#e5ddd5;border-radius:var(--radius-sm);padding:14px;margin-bottom:14px">
          <div style="font-size:10px;color:#54656f;margin-bottom:8px;font-weight:600">PREVIEW</div>
          <div class="wa-bubble" id="wa-preview">Your message will appear here...</div>
          <div style="font-size:10px;color:#54656f;text-align:right;margin-top:4px">✓✓</div>
        </div>

        <!-- Action buttons -->
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <button class="wa-send-btn" onclick="openWhatsApp(false)" id="btn-open">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Open in WhatsApp
          </button>
          <button class="wa-send-btn copy-mode" onclick="copyMessage()">📋 Copy Message</button>
        </div>

        <div style="margin-top:10px;padding:8px 12px;background:var(--bg3);border-radius:var(--radius-sm);font-size:11.5px;color:var(--text3)">
          💡 <strong>How it works:</strong> Click "Open in WhatsApp" → WhatsApp opens with pre-filled message on web/app. No API needed.
        </div>
      </div>
    </div>

    <!-- Template Manager -->
    <div class="wa-card">
      <div class="wa-card-head">
        <div class="wa-card-title">📝 Message Templates</div>
        <button onclick="toggleNewTpl()" class="btn btn-ghost btn-sm">＋ New</button>
      </div>

      <!-- New template form -->
      <div id="new-tpl-form" style="display:none;padding:16px;border-bottom:1px solid var(--border)">
        <form method="POST">
          <input type="hidden" name="action" value="save_template">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Template Name *</label>
              <input type="text" name="name" class="form-control" required placeholder="e.g. Project Update">
            </div>
            <div class="form-group">
              <label class="form-label">Category</label>
              <select name="category" class="form-control">
                <?php foreach ($cats as $k=>$v): ?><option value="<?=$k?>"><?=$v?></option><?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Message <span style="font-size:11px;color:var(--text3)">Use {{name}}, {{company}}, {{amount}} etc as variables</span></label>
            <textarea name="message" class="form-control" rows="4" required placeholder="Hi {{name}}, ..."></textarea>
          </div>
          <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary btn-sm">Save Template</button>
            <button type="button" onclick="toggleNewTpl()" class="btn btn-ghost btn-sm">Cancel</button>
          </div>
        </form>
      </div>

      <!-- Template list -->
      <div style="max-height:320px;overflow-y:auto">
        <?php
        $grouped = [];
        foreach ($templates as $t) $grouped[$t['category']][] = $t;
        foreach ($grouped as $cat => $tpls):
        ?>
        <div style="padding:6px 14px;background:var(--bg3);font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid var(--border)"><?= $cats[$cat]??ucfirst($cat) ?></div>
        <?php foreach ($tpls as $t): ?>
        <div class="wa-tpl-item" onclick="useTemplate(<?= $t['id'] ?>)" id="tpl-<?= $t['id'] ?>">
          <div style="display:flex;align-items:center;justify-content:space-between">
            <div class="wa-tpl-name"><?= h($t['name']) ?></div>
            <form method="POST" onsubmit="return confirm('Delete?');event.stopPropagation();" style="display:inline">
              <input type="hidden" name="action" value="delete_template">
              <input type="hidden" name="template_id" value="<?= $t['id'] ?>">
              <button type="submit" class="btn btn-ghost btn-sm btn-icon" style="font-size:11px;padding:2px 6px" onclick="event.stopPropagation()">🗑</button>
            </form>
          </div>
          <div class="wa-tpl-preview"><?= h(mb_substr($t['message'],0,70)) ?>...</div>
        </div>
        <?php endforeach; endforeach; ?>
        <?php if (!$templates): ?>
        <div style="padding:24px;text-align:center;color:var(--text3);font-size:12.5px">No templates. Click ＋ New to create one.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
var waTemplates = <?= json_encode(array_column($templates, null, 'id')) ?>;
var selectedPhone = '', selectedName = '', selectedCompany = '';

function selectContact(el) {
    document.querySelectorAll('.wa-contact-item').forEach(function(i){i.classList.remove('selected');});
    el.classList.add('selected');
    selectedPhone   = el.dataset.phone || '';
    selectedName    = el.dataset.name  || '';
    selectedCompany = el.dataset.company || '';
    document.getElementById('wa-phone').value = formatPhone(selectedPhone);
    document.getElementById('selected-contact-name').textContent = selectedName;
    // Auto-fill template variables if template is selected
    var msg = document.getElementById('wa-message').value;
    if (msg) {
        msg = msg.replace(/\{\{name\}\}/gi, selectedName)
                 .replace(/\{\{company\}\}/gi, selectedCompany);
        document.getElementById('wa-message').value = msg;
    }
    updatePreview();
}

function formatPhone(p) {
    // Remove spaces/dashes, ensure + prefix for international
    p = p.replace(/[\s\-\(\)]/g,'');
    if (p.startsWith('0') && p.length >= 9) p = '+94' + p.slice(1); // Sri Lanka
    return p;
}

function applyTemplate(sel) {
    var opt = sel.options[sel.selectedIndex];
    if (!opt.value) { document.getElementById('var-chips').style.display='none'; return; }
    var msg = opt.dataset.msg || '';
    var vars_raw = opt.dataset.vars || '';
    // Auto-fill known variables
    if (selectedName)    msg = msg.replace(/\{\{name\}\}/gi, selectedName);
    if (selectedCompany) msg = msg.replace(/\{\{company\}\}/gi, selectedCompany);
    document.getElementById('wa-message').value = msg;
    updatePreview();
    // Show remaining variable chips
    var remaining = (msg.match(/\{\{\w+\}\}/g) || []);
    if (remaining.length) {
        var chips = remaining.filter(function(v,i,a){return a.indexOf(v)===i;})
            .map(function(v){return '<span class="var-chip" onclick="fillVar(\''+v+'\')">'+v+'</span>';}).join('');
        document.getElementById('var-chips-list').innerHTML = chips;
        document.getElementById('var-chips').style.display = 'block';
    } else {
        document.getElementById('var-chips').style.display = 'none';
    }
}

function fillVar(v) {
    var val = prompt('Value for ' + v + ':','');
    if (val === null) return;
    var msg = document.getElementById('wa-message').value;
    document.getElementById('wa-message').value = msg.replace(new RegExp(v.replace(/[{}]/g,'\\$&'),'gi'), val);
    updatePreview();
}

function useTemplate(id) {
    var t = waTemplates[id];
    if (!t) return;
    // Set the dropdown
    var sel = document.getElementById('wa-tpl-select');
    for (var i=0;i<sel.options.length;i++) { if (sel.options[i].value == id) { sel.selectedIndex=i; break; } }
    applyTemplate(sel);
}

function updatePreview() {
    var msg = document.getElementById('wa-message').value || 'Your message will appear here...';
    document.getElementById('wa-preview').textContent = msg;
    document.getElementById('wa-char-count').textContent = document.getElementById('wa-message').value.length + ' characters';
}

function openWhatsApp(web) {
    var phone = document.getElementById('wa-phone').value.replace(/[\s\-\(\)]/g,'');
    var msg   = document.getElementById('wa-message').value.trim();
    if (!phone) { toast('Enter a phone number first','error'); return; }
    if (!msg)   { toast('Type a message first','error'); return; }
    var encoded = encodeURIComponent(msg);
    var url = 'https://wa.me/' + phone.replace('+','') + '?text=' + encoded;
    window.open(url, '_blank');
}

function copyMessage() {
    var msg = document.getElementById('wa-message').value.trim();
    if (!msg) { toast('Type a message first','error'); return; }
    navigator.clipboard.writeText(msg).then(function(){toast('Message copied to clipboard!','success');}).catch(function(){
        // Fallback
        var ta = document.createElement('textarea');
        ta.value = msg; document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta);
        toast('Copied!','success');
    });
}

function filterContacts(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.wa-contact-item').forEach(function(el){
        var name = el.dataset.name.toLowerCase();
        var company = el.dataset.company.toLowerCase();
        el.style.display = (name.includes(q)||company.includes(q)) ? '' : 'none';
    });
}

function toggleNewTpl() {
    var f = document.getElementById('new-tpl-form');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php renderLayoutEnd(); ?>