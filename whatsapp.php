<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];
mysqli_report(MYSQLI_REPORT_OFF);

// ── HELPERS ──
function waGet(mysqli $db, string $key): string {
    $r = @$db->query("SELECT setting_val FROM whatsapp_settings WHERE setting_key='".$db->real_escape_string($key)."' LIMIT 1");
    if (!$r) return ''; $row = $r->fetch_assoc(); return trim((string)($row['setting_val'] ?? ''));
}
function waConfigured(mysqli $db): bool {
    return waGet($db,'twilio_sid') !== '' && waGet($db,'twilio_token') !== '';
}
function waSend(mysqli $db, string $to, string $body, int $uid): array {
    $sid   = waGet($db,'twilio_sid');
    $token = waGet($db,'twilio_token');
    $from  = waGet($db,'twilio_from') ?: 'whatsapp:+14155238886';

    if (!$sid || !$token) return ['ok'=>false,'error'=>'Twilio not configured'];

    $to_wa = 'whatsapp:' . preg_replace('/[^0-9+]/', '', $to);
    $url   = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
    $data  = http_build_query(['From'=>$from,'To'=>$to_wa,'Body'=>$body]);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_USERPWD        => "$sid:$token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $raw = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http'=>[
            'method'  => 'POST',
            'header'  => "Authorization: Basic ".base64_encode("$sid:$token")."\r\nContent-Type: application/x-www-form-urlencoded\r\n",
            'content' => $data,
            'timeout' => 15,
            'ignore_errors' => true,
        ],'ssl'=>['verify_peer'=>false]]);
        $raw = @file_get_contents($url, false, $ctx);
        $http_code = 0;
    }

    $resp = json_decode($raw ?: '{}', true);

    if (!empty($resp['sid'])) {
        return ['ok'=>true,'sid'=>$resp['sid'],'status'=>$resp['status']??'queued'];
    }
    $err = $resp['message'] ?? ($resp['error_message'] ?? 'Send failed (HTTP '.$http_code.')');
    return ['ok'=>false,'error'=>$err];
}

// ── POST HANDLERS ──
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    header('Content-Type: application/json');

    // ── SEND MESSAGE ──
    if ($action === 'send') {
        $phone      = trim($_POST['phone'] ?? '');
        $body       = trim($_POST['body'] ?? '');
        $contact_id = (int)($_POST['contact_id'] ?? 0) ?: null;
        $lead_id    = (int)($_POST['lead_id'] ?? 0) ?: null;
        $cname      = trim($_POST['contact_name'] ?? $phone);

        if (!$phone || !$body) { ob_end_clean(); echo json_encode(['ok'=>false,'error'=>'Phone and message required']); exit; }

        $result = waSend($db, $phone, $body, $uid);

        $status  = $result['ok'] ? ($result['status']??'queued') : 'failed';
        $err_msg = $result['ok'] ? '' : ($result['error']??'');
        $twsid   = $result['ok'] ? ($result['sid']??'') : '';

        $pe  = $db->real_escape_string(preg_replace('/[^0-9+]/', '', $phone));
        $be  = $db->real_escape_string($body);
        $se  = $db->real_escape_string($status);
        $ee  = $db->real_escape_string($err_msg);
        $te  = $db->real_escape_string($twsid);
        $ne  = $db->real_escape_string($cname);
        $cid = $contact_id ?: 'NULL';
        $lid = $lead_id    ?: 'NULL';

        $db->query("INSERT INTO whatsapp_messages
            (contact_id,lead_id,direction,phone,contact_name,body,twilio_sid,status,error_msg,sent_by)
            VALUES ($cid,$lid,'out','$pe','$ne','$be','$te','$se','$ee',$uid)");
        $msg_id = (int)$db->insert_id;

        if ($result['ok']) logActivity('sent WhatsApp', $cname, $msg_id);

        ob_end_clean();
        echo json_encode([
            'ok'     => $result['ok'],
            'error'  => $result['ok'] ? null : $result['error'],
            'msg'    => ['id'=>$msg_id,'body'=>$body,'status'=>$status,'direction'=>'out',
                         'time'=>date('g:ia'),'sent_by'=>$user['name']],
        ]);
        exit;
    }

    // ── LOAD CONVERSATION ──
    if ($action === 'load_conversation') {
        $phone = preg_replace('/[^0-9+]/', '', $_POST['phone'] ?? '');
        if (!$phone) { ob_end_clean(); echo json_encode(['messages'=>[]]); exit; }
        $pe    = $db->real_escape_string($phone);
        $msgs  = @$db->query("SELECT id,direction,body,status,error_msg,contact_name,created_at,sent_by,
            (SELECT name FROM users WHERE id=sent_by) AS sender_name
            FROM whatsapp_messages WHERE phone LIKE '%".substr($phone,-9)."%'
            ORDER BY created_at ASC LIMIT 100");
        $rows = $msgs ? $msgs->fetch_all(MYSQLI_ASSOC) : [];
        ob_end_clean();
        echo json_encode(['ok'=>true,'messages'=>$rows]);
        exit;
    }

    // ── GET UNREAD COUNT ──
    if ($action === 'unread_count') {
        $count = (int)(@$db->query("SELECT COUNT(*) FROM whatsapp_messages WHERE direction='in' AND status='received'")->fetch_row()[0] ?? 0);
        ob_end_clean();
        echo json_encode(['count'=>$count]);
        exit;
    }

    // ── SAVE SETTINGS ──
    if ($action === 'save_settings' && isAdmin()) {
        $keys = ['twilio_sid','twilio_token','twilio_from','sandbox_word'];
        foreach ($keys as $k) {
            $v = trim($_POST[$k] ?? '');
            if ($v !== '') {
                $ve = $db->real_escape_string($v);
                $db->query("UPDATE whatsapp_settings SET setting_val='$ve',updated_by=$uid WHERE setting_key='$k'");
            }
        }
        ob_end_clean();
        echo json_encode(['ok'=>true,'message'=>'Settings saved']);
        exit;
    }

    // ── SAVE TEMPLATE ──
    if ($action === 'save_template') {
        $name = trim($_POST['name'] ?? '');
        $cat  = $_POST['category'] ?? 'custom';
        $msg  = trim($_POST['message'] ?? '');
        $tid  = (int)($_POST['template_id'] ?? 0);
        if ($name && $msg) {
            if ($tid) {
                $stmt = $db->prepare("UPDATE whatsapp_templates SET name=?,category=?,message=? WHERE id=$tid");
                $stmt->bind_param("sss",$name,$cat,$msg);
            } else {
                $stmt = $db->prepare("INSERT INTO whatsapp_templates (name,category,message,created_by) VALUES (?,?,?,?)");
                $stmt->bind_param("sssi",$name,$cat,$msg,$uid);
            }
            $stmt->execute();
        }
        ob_end_clean();
        echo json_encode(['ok'=>true]);
        exit;
    }

    // ── DELETE TEMPLATE ──
    if ($action === 'delete_template') {
        $tid = (int)($_POST['template_id'] ?? 0);
        $db->query("DELETE FROM whatsapp_templates WHERE id=$tid");
        ob_end_clean();
        echo json_encode(['ok'=>true]);
        exit;
    }
}
ob_end_clean();

// ── DATA ──
$configured = waConfigured($db);
$sandbox_word = waGet($db,'sandbox_word');
$twilio_from  = waGet($db,'twilio_from') ?: 'whatsapp:+14155238886';
$sandbox_number = str_replace('whatsapp:','',$twilio_from);

// Recent conversations (distinct phones with last message)
$conversations = @$db->query("
    SELECT m.phone, m.contact_name,
        MAX(m.created_at) AS last_msg,
        SUM(m.direction='in' AND m.status='received') AS unread,
        (SELECT body FROM whatsapp_messages WHERE phone=m.phone ORDER BY created_at DESC LIMIT 1) AS preview,
        (SELECT direction FROM whatsapp_messages WHERE phone=m.phone ORDER BY created_at DESC LIMIT 1) AS preview_dir,
        m.contact_id, m.lead_id
    FROM whatsapp_messages m
    GROUP BY m.phone, m.contact_name, m.contact_id, m.lead_id
    ORDER BY last_msg DESC LIMIT 50
");
$convs = $conversations ? $conversations->fetch_all(MYSQLI_ASSOC) : [];

// Contacts and leads with phones
$contacts = @$db->query("SELECT id,name,company,phone FROM contacts WHERE phone IS NOT NULL AND phone!='' ORDER BY name LIMIT 200");
$contacts = $contacts ? $contacts->fetch_all(MYSQLI_ASSOC) : [];
$leads    = @$db->query("SELECT id,name,company,phone FROM leads WHERE phone IS NOT NULL AND phone!='' AND stage NOT IN('won','lost') ORDER BY name LIMIT 100");
$leads    = $leads ? $leads->fetch_all(MYSQLI_ASSOC) : [];

// Templates
$templates = @$db->query("SELECT * FROM whatsapp_templates ORDER BY category,name");
$templates = $templates ? $templates->fetch_all(MYSQLI_ASSOC) : [];

// Settings (for admin panel)
$settings = [];
if (isAdmin()) {
    $sr = @$db->query("SELECT setting_key,setting_val FROM whatsapp_settings");
    if ($sr) while ($r=$sr->fetch_assoc()) $settings[$r['setting_key']] = $r['setting_val'];
}

renderLayout('WhatsApp', 'whatsapp');
?>
<style>
/* ── LAYOUT ── */
.wa-layout{display:grid;grid-template-columns:300px 1fr;height:calc(100vh - 70px);gap:0;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}

/* ── SIDEBAR ── */
.wa-sidebar{border-right:1px solid var(--border);display:flex;flex-direction:column;background:var(--bg3)}
.wa-sidebar-head{padding:12px 14px;border-bottom:1px solid var(--border);background:var(--bg2)}
.wa-sidebar-title{font-size:14px;font-weight:700;font-family:var(--font-display);display:flex;align-items:center;gap:8px;margin-bottom:8px}
.wa-search{width:100%;padding:7px 10px;background:var(--bg3);border:1px solid var(--border);border-radius:99px;font-size:12.5px;color:var(--text);font-family:var(--font)}
.wa-search::placeholder{color:var(--text3)}
.wa-search:focus{outline:none;border-color:var(--orange)}
.wa-conv-tabs{display:flex;border-bottom:1px solid var(--border)}
.wa-conv-tab{flex:1;padding:7px;font-size:12px;font-weight:600;color:var(--text3);background:none;border:none;cursor:pointer;border-bottom:2px solid transparent;transition:color .12s}
.wa-conv-tab.active{color:#25d366;border-bottom-color:#25d366}
.wa-conv-list{flex:1;overflow-y:auto}
.wa-conv-item{display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border);cursor:pointer;transition:background .1s}
.wa-conv-item:hover{background:var(--bg2)}
.wa-conv-item.active{background:rgba(37,211,102,.08)}
.wa-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#25d366,#128c7e);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;flex-shrink:0}
.wa-conv-name{font-size:13px;font-weight:600;color:var(--text)}
.wa-conv-preview{font-size:11.5px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px}
.wa-conv-time{font-size:10.5px;color:var(--text3);flex-shrink:0}
.wa-unread-badge{background:#25d366;color:#fff;font-size:10px;font-weight:700;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.wa-new-btn{margin:10px 12px;padding:8px;background:#25d366;color:#fff;border:none;border-radius:var(--radius-sm);font-size:12.5px;font-weight:700;cursor:pointer;width:calc(100% - 24px);transition:opacity .15s}
.wa-new-btn:hover{opacity:.88}

/* ── CHAT PANEL ── */
.wa-chat{display:flex;flex-direction:column;height:100%}
.wa-chat-head{padding:12px 16px;border-bottom:1px solid var(--border);background:var(--bg2);display:flex;align-items:center;gap:12px;flex-shrink:0}
.wa-chat-avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#25d366,#128c7e);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;flex-shrink:0}
.wa-chat-name{font-size:14px;font-weight:700;color:var(--text)}
.wa-chat-phone{font-size:12px;color:var(--text3)}
.wa-messages{flex:1;overflow-y:auto;padding:16px;background:#e5ddd5;display:flex;flex-direction:column;gap:6px}
[data-theme="dark"] .wa-messages{background:#0d1117}
.wa-msg{display:flex;flex-direction:column;max-width:70%}
.wa-msg.out{align-self:flex-end;align-items:flex-end}
.wa-msg.in{align-self:flex-start;align-items:flex-start}
.wa-bubble{padding:8px 12px;border-radius:8px;font-size:13.5px;line-height:1.5;word-break:break-word;font-family:Arial,sans-serif;position:relative}
.wa-msg.out .wa-bubble{background:#d9fdd3;color:#1a1a1a;border-radius:8px 8px 0 8px}
.wa-msg.in  .wa-bubble{background:#fff;color:#1a1a1a;border-radius:8px 8px 8px 0}
[data-theme="dark"] .wa-msg.out .wa-bubble{background:#005c4b;color:#e9f5e9}
[data-theme="dark"] .wa-msg.in  .wa-bubble{background:#1f2c34;color:#e9edef}
.wa-msg-meta{font-size:10.5px;color:#667781;margin-top:2px;display:flex;align-items:center;gap:4px}
.wa-msg.out .wa-msg-meta{color:#667781}
.wa-tick{font-size:12px}
.wa-tick.sent{color:#667781}
.wa-tick.delivered{color:#53bdeb}
.wa-tick.read{color:#53bdeb}
.wa-tick.failed{color:#ef4444}
.wa-date-divider{text-align:center;font-size:11px;color:#667781;background:rgba(255,255,255,.6);border-radius:8px;padding:3px 12px;align-self:center;margin:6px 0}
[data-theme="dark"] .wa-date-divider{background:rgba(0,0,0,.3);color:#8696a0}

/* ── INPUT BAR ── */
.wa-input-bar{padding:10px 14px;border-top:1px solid var(--border);background:var(--bg2);flex-shrink:0}
.wa-input-row{display:flex;gap:8px;align-items:flex-end}
.wa-tpl-row{display:flex;gap:6px;margin-bottom:8px;flex-wrap:wrap}
.wa-tpl-chip{padding:4px 10px;background:var(--bg3);border:1px solid var(--border);border-radius:99px;font-size:11.5px;cursor:pointer;color:var(--text2);white-space:nowrap;transition:all .12s}
.wa-tpl-chip:hover{background:rgba(37,211,102,.1);border-color:#25d366;color:#25d366}
.wa-textarea{flex:1;resize:none;padding:9px 12px;background:var(--bg3);border:1px solid var(--border);border-radius:20px;font-size:13.5px;color:var(--text);font-family:Arial,sans-serif;min-height:40px;max-height:120px;transition:border-color .15s}
.wa-textarea:focus{outline:none;border-color:#25d366}
.wa-send{width:42px;height:42px;background:#25d366;border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:opacity .15s}
.wa-send:hover{opacity:.88}
.wa-send:disabled{background:var(--bg4);cursor:not-allowed}
.wa-char-hint{font-size:11px;color:var(--text3);margin-top:4px;text-align:right}

/* ── EMPTY STATE ── */
.wa-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;background:#e5ddd5;padding:40px}
[data-theme="dark"] .wa-empty{background:#0d1117}
.wa-empty-icon{font-size:64px}
.wa-empty-title{font-size:16px;font-weight:700;color:#667781}
.wa-empty-sub{font-size:13px;color:#8696a0;text-align:center;max-width:300px;line-height:1.6}

/* ── SETUP PANEL ── */
.wa-setup{padding:24px;max-width:560px;margin:0 auto}
.wa-setup-step{display:flex;gap:14px;padding:14px 0;border-bottom:1px solid var(--border)}
.wa-setup-num{width:28px;height:28px;border-radius:50%;background:#25d366;color:#fff;font-weight:800;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px}
.wa-setup-body h4{font-size:13.5px;font-weight:700;color:var(--text);margin-bottom:4px}
.wa-setup-body p{font-size:12.5px;color:var(--text2);line-height:1.6}
.wa-setup-body a{color:#25d366;font-weight:600}
.wa-setup-body code{background:var(--bg3);padding:2px 6px;border-radius:4px;font-size:12px}

/* ── NEW CHAT MODAL ── */
.wa-contact-picker{max-height:320px;overflow-y:auto}
.wa-picker-item{display:flex;align-items:center;gap:10px;padding:9px 14px;border-bottom:1px solid var(--border);cursor:pointer;transition:background .1s}
.wa-picker-item:hover{background:var(--bg3)}
.wa-picker-item:last-child{border-bottom:none}

@media(max-width:800px){.wa-layout{grid-template-columns:1fr;height:auto}}
</style>

<?php if (!$configured): ?>
<!-- ══ SETUP PANEL ══ -->
<div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden">
  <div style="background:linear-gradient(135deg,#25d366,#128c7e);padding:20px 24px;display:flex;align-items:center;gap:14px">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    <div>
      <div style="color:#fff;font-size:18px;font-weight:800">WhatsApp Integration</div>
      <div style="color:rgba(255,255,255,.8);font-size:13px">Send real WhatsApp messages directly from Padak CRM</div>
    </div>
  </div>

  <div class="wa-setup">
    <div style="background:rgba(37,211,102,.08);border:1px solid rgba(37,211,102,.3);border-radius:var(--radius-sm);padding:12px 16px;margin-bottom:20px;font-size:13px;color:var(--text2)">
      <strong style="color:#25d366">How it works:</strong> We use Twilio's WhatsApp Sandbox — a free service that lets you send real WhatsApp messages. Recipients receive actual messages in their WhatsApp app. No number ban risk, no Node.js needed.
    </div>

    <div class="wa-setup-step">
      <div class="wa-setup-num">1</div>
      <div class="wa-setup-body">
        <h4>Create a free Twilio account</h4>
        <p>Go to <a href="https://www.twilio.com/try-twilio" target="_blank">twilio.com/try-twilio</a> → Sign up free (no credit card for trial). You get $15 free credit.</p>
      </div>
    </div>
    <div class="wa-setup-step">
      <div class="wa-setup-num">2</div>
      <div class="wa-setup-body">
        <h4>Enable WhatsApp Sandbox</h4>
        <p>In Twilio Console → Messaging → Try it out → <strong>Send a WhatsApp message</strong>. This activates the sandbox number <code>+1 415 523 8886</code>.</p>
      </div>
    </div>
    <div class="wa-setup-step">
      <div class="wa-setup-num">3</div>
      <div class="wa-setup-body">
        <h4>Recipients must opt-in once</h4>
        <p>Each recipient sends a WhatsApp message to <code>+14155238886</code> saying <code>join &lt;sandbox-word&gt;</code> (shown in Twilio console). After that, you can message them from CRM.</p>
      </div>
    </div>
    <div class="wa-setup-step" style="border-bottom:none">
      <div class="wa-setup-num">4</div>
      <div class="wa-setup-body">
        <h4>Get your Account SID and Auth Token</h4>
        <p>From Twilio Console dashboard → copy <strong>Account SID</strong> and <strong>Auth Token</strong> → paste below.</p>
      </div>
    </div>

    <?php if (isAdmin()): ?>
    <div style="margin-top:20px;background:var(--bg3);border-radius:var(--radius-lg);padding:18px">
      <div style="font-size:13px;font-weight:700;margin-bottom:14px">⚙ Configure Twilio Credentials</div>
      <div class="form-group">
        <label class="form-label">Account SID <span style="color:var(--red)">*</span></label>
        <input type="text" id="cfg-sid" class="form-control" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" value="<?= h($settings['twilio_sid']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Auth Token <span style="color:var(--red)">*</span></label>
        <input type="password" id="cfg-token" class="form-control" placeholder="Your auth token" value="">
      </div>
      <div class="form-group">
        <label class="form-label">Sandbox From Number</label>
        <input type="text" id="cfg-from" class="form-control" value="whatsapp:+14155238886" placeholder="whatsapp:+14155238886">
        <div style="font-size:11px;color:var(--text3);margin-top:3px">Default Twilio sandbox number. Change only if you have a dedicated WhatsApp number.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Sandbox Join Word</label>
        <input type="text" id="cfg-word" class="form-control" placeholder="e.g. fancy-lion (from your Twilio console)" value="<?= h($settings['sandbox_word']??'') ?>">
        <div style="font-size:11px;color:var(--text3);margin-top:3px">Recipients must send: <strong>join &lt;word&gt;</strong> to <?= h(str_replace('whatsapp:','',$twilio_from)) ?></div>
      </div>
      <button onclick="saveSettings()" class="btn btn-primary" style="background:#25d366;border-color:#25d366">✅ Save & Activate</button>
    </div>
    <?php else: ?>
    <div style="margin-top:20px;padding:16px;background:var(--bg3);border-radius:var(--radius-sm);font-size:13px;color:var(--text3)">Ask your admin to configure Twilio credentials.</div>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<!-- ══ MAIN WHATSAPP INTERFACE ══ -->
<div class="wa-layout" id="wa-main">
  <!-- SIDEBAR -->
  <div class="wa-sidebar">
    <div class="wa-sidebar-head">
      <div class="wa-sidebar-title">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="#25d366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        WhatsApp
        <?php if ($sandbox_word): ?><span style="font-size:10px;background:rgba(37,211,102,.15);color:#25d366;padding:2px 7px;border-radius:99px">Sandbox</span><?php endif; ?>
      </div>
      <input type="text" class="wa-search" id="conv-search" placeholder="Search conversations..." oninput="filterConvs(this.value)">
    </div>
    <div class="wa-conv-tabs">
      <button class="wa-conv-tab active" onclick="showConvTab('all',this)">All</button>
      <button class="wa-conv-tab" onclick="showConvTab('unread',this)">Unread</button>
    </div>
    <div class="wa-conv-list" id="conv-list">
      <?php if ($convs): foreach ($convs as $c):
        $initial = strtoupper(substr($c['contact_name']??'?',0,1));
        $is_in = $c['preview_dir']==='in';
        $preview = ($is_in ? '' : 'You: ') . mb_substr($c['preview']??'',0,35);
        $time_str = date(date('Y-m-d',strtotime($c['last_msg']))==date('Y-m-d') ? 'g:ia' : 'M j', strtotime($c['last_msg']));
      ?>
      <div class="wa-conv-item" data-phone="<?= h($c['phone']) ?>"
           data-name="<?= h($c['contact_name']) ?>"
           data-cid="<?= (int)$c['contact_id'] ?>"
           data-lid="<?= (int)$c['lead_id'] ?>"
           data-unread="<?= (int)$c['unread'] ?>"
           onclick="openConv(this)">
        <div class="wa-avatar"><?= $initial ?></div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:4px">
            <span class="wa-conv-name"><?= h($c['contact_name']??$c['phone']) ?></span>
            <span class="wa-conv-time"><?= $time_str ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span class="wa-conv-preview"><?= h($preview) ?></span>
            <?php if ($c['unread'] > 0): ?><span class="wa-unread-badge"><?= $c['unread'] ?></span><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; else: ?>
      <div style="padding:24px;text-align:center;color:var(--text3);font-size:12.5px">No conversations yet.<br>Click ＋ New Chat to start.</div>
      <?php endif; ?>
    </div>
    <button class="wa-new-btn" onclick="openModal('modal-new-chat')">＋ New Chat</button>
    <?php if (isAdmin()): ?>
    <button onclick="openModal('modal-wa-settings')" style="margin:-2px 12px 10px;padding:6px;background:none;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:12px;color:var(--text3);cursor:pointer;width:calc(100% - 24px)">⚙ Settings</button>
    <?php endif; ?>
  </div>

  <!-- CHAT AREA -->
  <div class="wa-chat" id="chat-area">
    <div class="wa-empty" id="chat-empty">
      <div class="wa-empty-icon">💬</div>
      <div class="wa-empty-title">WhatsApp Messages</div>
      <div class="wa-empty-sub">Select a conversation or start a new chat with your contacts and leads.</div>
      <?php if ($sandbox_word): ?>
      <div style="background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.3);border-radius:var(--radius-sm);padding:10px 16px;font-size:12.5px;color:var(--text2);text-align:center;max-width:320px">
        📱 Ask recipients to send <strong>join <?= h($sandbox_word) ?></strong> to <strong><?= h($sandbox_number) ?></strong> on WhatsApp first
      </div>
      <?php endif; ?>
    </div>

    <div id="chat-active" style="display:none;flex-direction:column;height:100%">
      <!-- Header -->
      <div class="wa-chat-head">
        <div class="wa-chat-avatar" id="chat-avatar">?</div>
        <div style="flex:1">
          <div class="wa-chat-name" id="chat-name">-</div>
          <div class="wa-chat-phone" id="chat-phone">-</div>
        </div>
        <div style="display:flex;gap:6px">
          <button onclick="showTemplateBar()" class="btn btn-ghost btn-sm" title="Templates">📝</button>
          <a id="chat-crm-link" href="#" class="btn btn-ghost btn-sm" style="font-size:12px">View in CRM</a>
        </div>
      </div>

      <!-- Messages -->
      <div class="wa-messages" id="chat-messages">
        <div class="wa-date-divider">TODAY</div>
      </div>

      <!-- Input bar -->
      <div class="wa-input-bar">
        <div id="tpl-bar" style="display:none;margin-bottom:8px">
          <div style="font-size:11px;color:var(--text3);margin-bottom:6px;font-weight:600">QUICK TEMPLATES</div>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <?php foreach ($templates as $t): ?>
            <button class="wa-tpl-chip" onclick="applyTpl(<?= $t['id'] ?>)" title="<?= h($t['message']) ?>"><?= h($t['name']) ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="wa-input-row">
          <textarea class="wa-textarea" id="msg-input" rows="1" placeholder="Type a message..."
            oninput="autoResize(this)"
            onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage();}"></textarea>
          <button class="wa-send" id="send-btn" onclick="sendMessage()" disabled title="Send">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
          </button>
        </div>
        <div class="wa-char-hint" id="char-hint"></div>
      </div>
    </div>
  </div>
</div>

<!-- NEW CHAT MODAL -->
<div class="modal-overlay" id="modal-new-chat">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title">New Chat</div>
      <button class="modal-close" onclick="closeModal('modal-new-chat')">✕</button>
    </div>
    <div class="modal-body" style="padding:0">
      <div style="padding:12px 16px;border-bottom:1px solid var(--border)">
        <input type="text" id="picker-search" class="form-control" placeholder="Search contacts and leads..." oninput="filterPicker(this.value)" style="font-size:13px">
      </div>
      <div class="wa-contact-picker" id="contact-picker">
        <?php if ($contacts): ?>
        <div style="padding:6px 14px;background:var(--bg3);font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase">Contacts</div>
        <?php foreach ($contacts as $c): ?>
        <div class="wa-picker-item" data-name="<?= h($c['name']) ?>" data-phone="<?= h($c['phone']) ?>" data-cid="<?= $c['id'] ?>" data-lid="0" onclick="startChat(this)">
          <div class="wa-avatar" style="width:34px;height:34px;font-size:13px"><?= strtoupper(substr($c['name'],0,1)) ?></div>
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--text)"><?= h($c['name']) ?></div>
            <div style="font-size:11.5px;color:var(--text3)"><?= h($c['company']??'') ?> · <?= h($c['phone']) ?></div>
          </div>
        </div>
        <?php endforeach; endif; ?>
        <?php if ($leads): ?>
        <div style="padding:6px 14px;background:var(--bg3);font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase">Active Leads</div>
        <?php foreach ($leads as $l): ?>
        <div class="wa-picker-item" data-name="<?= h($l['name']) ?>" data-phone="<?= h($l['phone']) ?>" data-cid="0" data-lid="<?= $l['id'] ?>" onclick="startChat(this)">
          <div class="wa-avatar" style="width:34px;height:34px;font-size:13px;background:linear-gradient(135deg,#f97316,#ea580c)"><?= strtoupper(substr($l['name'],0,1)) ?></div>
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--text)"><?= h($l['name']) ?></div>
            <div style="font-size:11.5px;color:var(--text3)"><?= h($l['company']??'') ?> · <?= h($l['phone']) ?></div>
          </div>
        </div>
        <?php endforeach; endif; ?>
        <!-- Manual entry -->
        <div style="padding:12px 16px;border-top:1px solid var(--border)">
          <div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:8px">Or enter a number manually:</div>
          <div style="display:flex;gap:8px">
            <input type="text" id="manual-phone" class="form-control" placeholder="+94771234567" style="font-size:13px">
            <input type="text" id="manual-name"  class="form-control" placeholder="Name" style="font-size:13px;width:140px">
            <button onclick="startManual()" class="btn btn-primary btn-sm" style="white-space:nowrap;background:#25d366;border-color:#25d366">Start</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- SETTINGS MODAL -->
<?php if (isAdmin()): ?>
<div class="modal-overlay" id="modal-wa-settings">
  <div class="modal" style="max-width:500px">
    <div class="modal-header">
      <div class="modal-title">⚙ WhatsApp Settings</div>
      <button class="modal-close" onclick="closeModal('modal-wa-settings')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Account SID</label>
        <input type="text" id="cfg-sid" class="form-control" value="<?= h($settings['twilio_sid']??'') ?>" placeholder="ACxxxxxxxx...">
      </div>
      <div class="form-group">
        <label class="form-label">Auth Token</label>
        <input type="password" id="cfg-token" class="form-control" placeholder="Leave blank to keep current">
      </div>
      <div class="form-group">
        <label class="form-label">From Number</label>
        <input type="text" id="cfg-from" class="form-control" value="<?= h($settings['twilio_from']??'whatsapp:+14155238886') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Sandbox Join Word</label>
        <input type="text" id="cfg-word" class="form-control" value="<?= h($settings['sandbox_word']??'') ?>" placeholder="e.g. fancy-lion">
      </div>
      <div style="background:var(--bg3);border-radius:var(--radius-sm);padding:10px 12px;font-size:12px;color:var(--text2);margin-bottom:14px">
        <strong>Webhook URL for incoming messages:</strong><br>
        <code style="font-size:11px;word-break:break-all"><?= rtrim(BASE_URL,'/') ?>/whatsapp_webhook.php</code><br>
        Set this in Twilio Console → WhatsApp Sandbox → "When a message comes in"
      </div>
      <button onclick="saveSettings()" class="btn btn-primary" style="background:#25d366;border-color:#25d366;width:100%">Save Settings</button>
    </div>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
var waPhone='', waName='', waCid=0, waLid=0;
var waTpls=<?= json_encode(array_column($templates,null,'id')) ?>;

// ── SETUP ──
function saveSettings(){
    var fd=new FormData();
    fd.append('action','save_settings');
    fd.append('twilio_sid',   document.getElementById('cfg-sid')?.value.trim()||'');
    fd.append('twilio_token', document.getElementById('cfg-token')?.value.trim()||'');
    fd.append('twilio_from',  document.getElementById('cfg-from')?.value.trim()||'whatsapp:+14155238886');
    fd.append('sandbox_word', document.getElementById('cfg-word')?.value.trim()||'');
    fetch('whatsapp.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        if(d.ok){ toast('Settings saved! Reloading...','success'); setTimeout(function(){location.reload();},1000); }
        else toast(d.error||'Save failed','error');
    });
}

// ── OPEN CONVERSATION ──
function openConv(el){
    document.querySelectorAll('.wa-conv-item').forEach(function(i){i.classList.remove('active');});
    el.classList.add('active');
    waPhone = el.dataset.phone;
    waName  = el.dataset.name || waPhone;
    waCid   = parseInt(el.dataset.cid)||0;
    waLid   = parseInt(el.dataset.lid)||0;
    loadConversation();
}

function startChat(el){
    closeModal('modal-new-chat');
    waPhone = el.dataset.phone;
    waName  = el.dataset.name || waPhone;
    waCid   = parseInt(el.dataset.cid)||0;
    waLid   = parseInt(el.dataset.lid)||0;
    showChatUI();
    loadConversation();
}

function startManual(){
    var ph = document.getElementById('manual-phone').value.trim();
    var nm = document.getElementById('manual-name').value.trim()||ph;
    if(!ph){ toast('Enter a phone number','error'); return; }
    closeModal('modal-new-chat');
    waPhone=ph; waName=nm; waCid=0; waLid=0;
    showChatUI();
    loadConversation();
}

function showChatUI(){
    document.getElementById('chat-empty').style.display='none';
    var active=document.getElementById('chat-active');
    active.style.display='flex';
    document.getElementById('chat-avatar').textContent=waName.charAt(0).toUpperCase();
    document.getElementById('chat-name').textContent=waName;
    document.getElementById('chat-phone').textContent=waPhone;
    var crmLink=document.getElementById('chat-crm-link');
    if(waCid) crmLink.href='contacts.php?edit='+waCid;
    else if(waLid) crmLink.href='leads.php?view='+waLid;
    else crmLink.style.display='none';
    document.getElementById('send-btn').disabled=false;
    document.getElementById('msg-input').focus();
}

function loadConversation(){
    var msgs=document.getElementById('chat-messages');
    msgs.innerHTML='<div style="text-align:center;padding:20px;color:#8696a0;font-size:12px">Loading...</div>';
    var fd=new FormData();
    fd.append('action','load_conversation');
    fd.append('phone',waPhone);
    fetch('whatsapp.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        msgs.innerHTML='';
        if(!d.messages||!d.messages.length){
            msgs.innerHTML='<div class="wa-date-divider">No messages yet</div>';
            return;
        }
        var lastDate='';
        d.messages.forEach(function(m){
            var msgDate=m.created_at?m.created_at.slice(0,10):'';
            if(msgDate&&msgDate!==lastDate){
                lastDate=msgDate;
                var today=new Date().toISOString().slice(0,10);
                var label=msgDate===today?'TODAY':new Date(msgDate+'T00:00:00').toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
                msgs.innerHTML+='<div class="wa-date-divider">'+label+'</div>';
            }
            msgs.innerHTML+=renderMsg(m);
        });
        msgs.scrollTop=msgs.scrollHeight;
    });
}

function renderMsg(m){
    var dir=m.direction||'out';
    var time=m.created_at?new Date(m.created_at).toLocaleTimeString('en-US',{hour:'numeric',minute:'2-digit',hour12:true}):'';
    var tick='';
    if(dir==='out'){
        var tmap={'queued':'✓','sent':'✓✓','delivered':'✓✓','read':'✓✓','failed':'!'};
        var tc={'queued':'sent','sent':'sent','delivered':'delivered','read':'read','failed':'failed'};
        tick='<span class="wa-tick '+(tc[m.status]||'sent')+'">'+(tmap[m.status]||'✓')+'</span>';
    }
    var err=m.status==='failed'&&m.error_msg?'<div style="font-size:11px;color:#ef4444;margin-top:3px">'+esc(m.error_msg)+'</div>':'';
    return '<div class="wa-msg '+dir+'">'
        +'<div class="wa-bubble">'+esc(m.body)+'</div>'
        +err
        +'<div class="wa-msg-meta">'+time+' '+tick+'</div>'
        +'</div>';
}

function sendMessage(){
    var body=document.getElementById('msg-input').value.trim();
    if(!body||!waPhone) return;
    var btn=document.getElementById('send-btn');
    btn.disabled=true;
    var fd=new FormData();
    fd.append('action','send');
    fd.append('phone',waPhone);
    fd.append('body',body);
    fd.append('contact_id',waCid);
    fd.append('lead_id',waLid);
    fd.append('contact_name',waName);
    document.getElementById('msg-input').value='';
    document.getElementById('char-hint').textContent='';
    autoResize(document.getElementById('msg-input'));
    fetch('whatsapp.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        btn.disabled=false;
        if(d.msg){
            var msgs=document.getElementById('chat-messages');
            msgs.innerHTML+=renderMsg(Object.assign(d.msg,{created_at:new Date().toISOString()}));
            msgs.scrollTop=msgs.scrollHeight;
        }
        if(!d.ok) toast(d.error||'Send failed','error');
    })
    .catch(function(){
        btn.disabled=false;
        toast('Network error','error');
    });
}

function applyTpl(id){
    var t=waTpls[id];if(!t)return;
    var msg=t.message;
    msg=msg.replace(/\{\{name\}\}/gi,waName);
    document.getElementById('msg-input').value=msg;
    autoResize(document.getElementById('msg-input'));
    document.getElementById('msg-input').focus();
    updateCharHint();
}

function showTemplateBar(){
    var b=document.getElementById('tpl-bar');
    b.style.display=b.style.display==='none'?'block':'none';
}

function autoResize(ta){
    ta.style.height='auto';
    ta.style.height=Math.min(ta.scrollHeight,120)+'px';
    updateCharHint();
}

function updateCharHint(){
    var v=document.getElementById('msg-input').value;
    var h=document.getElementById('char-hint');
    if(v.length>0) h.textContent=v.length+' characters';
    else h.textContent='';
}

function filterConvs(q){
    q=q.toLowerCase();
    document.querySelectorAll('.wa-conv-item').forEach(function(el){
        var n=el.dataset.name.toLowerCase();
        var p=el.dataset.phone.toLowerCase();
        el.style.display=(n.includes(q)||p.includes(q))?'':'none';
    });
}

function filterPicker(q){
    q=q.toLowerCase();
    document.querySelectorAll('.wa-picker-item').forEach(function(el){
        var n=el.dataset.name.toLowerCase();
        var p=el.dataset.phone.toLowerCase();
        el.style.display=(n.includes(q)||p.includes(q))?'':'none';
    });
}

function showConvTab(t,btn){
    document.querySelectorAll('.wa-conv-tab').forEach(function(b){b.classList.remove('active');});
    btn.classList.add('active');
    document.querySelectorAll('.wa-conv-item').forEach(function(el){
        if(t==='unread') el.style.display=parseInt(el.dataset.unread)>0?'':'none';
        else el.style.display='';
    });
}

function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
</script>

<?php renderLayoutEnd(); ?>