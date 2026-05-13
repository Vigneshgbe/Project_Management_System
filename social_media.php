<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid        = (int)$user['id'];
$is_manager = isManager();
mysqli_report(MYSQLI_REPORT_OFF);

// ── ENSURE columns exist ────────────────────────────────────────────────────
@$db->query("ALTER TABLE social_accounts ADD COLUMN IF NOT EXISTS acc_password VARCHAR(500) NULL DEFAULT NULL AFTER followers");
@$db->query("ALTER TABLE social_accounts ADD COLUMN IF NOT EXISTS acc_email    VARCHAR(300) NULL DEFAULT NULL AFTER acc_password");

ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    $write_actions = ['save_account','delete_account','save_template','delete_template'];
    if (!$is_manager && in_array($action, $write_actions)) {
        ob_end_clean(); header('Location: social_media.php'); exit;
    }

    if (!$is_manager && $action === 'quick_status') {
        $pid = (int)($_POST['post_id'] ?? 0);
        $own = $db->query("SELECT id FROM social_posts WHERE id=$pid AND assigned_to=$uid")->fetch_row();
        if (!$own) { ob_end_clean(); header('Location: social_media.php'); exit; }
    }

    if (!$is_manager && $action === 'save_post') {
        $post_id = (int)($_POST['post_id'] ?? 0);
        if ($post_id) {
            $own = $db->query("SELECT id FROM social_posts WHERE id=$post_id AND (assigned_to=$uid OR created_by=$uid)")->fetch_row();
            if (!$own) { ob_end_clean(); header('Location: social_media.php'); exit; }
        }
    }

    if (!$is_manager && $action === 'delete_post') {
        $pid = (int)($_POST['post_id'] ?? 0);
        $own = $db->query("SELECT id FROM social_posts WHERE id=$pid AND (assigned_to=$uid OR created_by=$uid)")->fetch_row();
        if (!$own) { ob_end_clean(); header('Location: social_media.php'); exit; }
    }

    // ── SAVE ACCOUNT (BUG FIXED: removed duplicate prepare + typo in bind_param) ──
    if ($action === 'save_account') {
        $platform  = $_POST['platform'] ?? 'other';
        $name      = trim($_POST['acc_name'] ?? '');
        $handle    = trim($_POST['handle'] ?? '');
        $url       = trim($_POST['url'] ?? '');
        $followers = (int)($_POST['followers'] ?? 0);
        $notes     = trim($_POST['notes'] ?? '');
        $acc_email = trim($_POST['acc_email'] ?? '');
        $acc_pass  = trim($_POST['acc_password'] ?? '');
        $aid       = (int)($_POST['account_id'] ?? 0);
        if ($name) {
            if ($aid) {
                // FIX: single prepare, correct bind_param format string "ssssisss"
                $s = $db->prepare("UPDATE social_accounts SET platform=?,name=?,handle=?,url=?,followers=?,notes=?,acc_email=?,acc_password=? WHERE id=?");
                $s->bind_param("ssssisssi", $platform, $name, $handle, $url, $followers, $notes, $acc_email, $acc_pass, $aid);
            } else {
                $s = $db->prepare("INSERT INTO social_accounts (platform,name,handle,url,followers,notes,acc_email,acc_password,created_by) VALUES (?,?,?,?,?,?,?,?,?)");
                $s->bind_param("ssssisssi", $platform, $name, $handle, $url, $followers, $notes, $acc_email, $acc_pass, $uid);
            }
            if (!$s->execute()) {
                // Surface the real MySQL error for debugging
                error_log("save_account error: " . $s->error);
                ob_end_clean();
                header('Location: social_media.php?section=accounts&err=db');
                exit;
            }
            flash('Account saved.', 'success');
        }
        ob_end_clean(); header('Location: social_media.php?section=accounts'); exit;
    }

    if ($action === 'delete_account') {
        $db->query("UPDATE social_accounts SET is_active=0 WHERE id=" . (int)($_POST['account_id'] ?? 0));
        flash('Account removed.', 'success');
        ob_end_clean(); header('Location: social_media.php?section=accounts'); exit;
    }

    if ($action === 'save_post') {
        $post_id   = (int)($_POST['post_id'] ?? 0);
        $account_id= (int)($_POST['account_id'] ?? 0) ?: null;
        $title     = trim($_POST['title'] ?? '');
        $content   = trim($_POST['content'] ?? '');
        $platform  = $_POST['platform'] ?? '';
        $status    = $_POST['status'] ?? 'idea';
        $post_type = $_POST['post_type'] ?? 'post';
        $tags      = trim($_POST['tags'] ?? '');
        $cap_notes = trim($_POST['caption_notes'] ?? '');
        $sched     = $_POST['scheduled_at'] ?: null;
        $proj_id   = (int)($_POST['project_id'] ?? 0) ?: null;
        $assigned  = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $back      = $_POST['back_to'] ?? 'pipeline';
        if ($title) {
            if ($post_id) {
                $s = $db->prepare("UPDATE social_posts SET account_id=?,title=?,content=?,platform=?,status=?,post_type=?,tags=?,caption_notes=?,scheduled_at=?,project_id=?,assigned_to=? WHERE id=$post_id");
                $s->bind_param("isssssssiii", $account_id, $title, $content, $platform, $status, $post_type, $tags, $cap_notes, $sched, $proj_id, $assigned);
                if ($status === 'published') $db->query("UPDATE social_posts SET published_at=NOW() WHERE id=$post_id AND published_at IS NULL");
            } else {
                $pub_at = $status === 'published' ? date('Y-m-d H:i:s') : null;
                $s = $db->prepare("INSERT INTO social_posts (account_id,title,content,platform,status,post_type,tags,caption_notes,scheduled_at,published_at,project_id,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $s->bind_param("isssssssssiiii", $account_id, $title, $content, $platform, $status, $post_type, $tags, $cap_notes, $sched, $pub_at, $proj_id, $assigned, $uid);
            }
            $s->execute();
            logActivity('social ' . $status, $title, $db->insert_id);
            flash('Post saved.', 'success');
        }
        ob_end_clean(); header("Location: social_media.php?section=$back"); exit;
    }

    if ($action === 'quick_status') {
        $pid = (int)($_POST['post_id'] ?? 0);
        $st  = $_POST['status'] ?? 'draft';
        if (in_array($st, ['idea', 'draft', 'scheduled', 'published', 'cancelled']))
            $db->query("UPDATE social_posts SET status='$st'" . ($st === 'published' ? ",published_at=NOW()" : "") . " WHERE id=$pid");
        ob_end_clean(); header('Content-Type: application/json'); echo json_encode(['ok' => true]); exit;
    }

    if ($action === 'delete_post') {
        $db->query("DELETE FROM social_posts WHERE id=" . (int)($_POST['post_id'] ?? 0));
        flash('Post deleted.', 'success');
        ob_end_clean(); header('Location: social_media.php?section=pipeline'); exit;
    }

    if ($action === 'save_template') {
        $name    = trim($_POST['tpl_name'] ?? '');
        $type    = $_POST['tpl_type'] ?? 'caption';
        $plat    = $_POST['tpl_platform'] ?? '';
        $content = trim($_POST['tpl_content'] ?? '');
        $tid     = (int)($_POST['template_id'] ?? 0);
        if ($name && $content) {
            if ($tid) { $s = $db->prepare("UPDATE social_templates SET name=?,type=?,platform=?,content=? WHERE id=$tid"); $s->bind_param("ssss", $name, $type, $plat, $content); }
            else { $s = $db->prepare("INSERT INTO social_templates (name,type,platform,content,created_by) VALUES (?,?,?,?,?)"); $s->bind_param("ssssi", $name, $type, $plat, $content, $uid); }
            $s->execute();
            flash('Template saved.', 'success');
        }
        ob_end_clean(); header('Location: social_media.php?section=compose'); exit;
    }

    if ($action === 'delete_template') {
        $db->query("DELETE FROM social_templates WHERE id=" . (int)($_POST['template_id'] ?? 0));
        flash('Template deleted.', 'success');
        ob_end_clean(); header('Location: social_media.php?section=compose'); exit;
    }
}
ob_end_clean();

$section  = (string)($_GET['section'] ?? 'dashboard');
$edit_pid = (int)($_GET['edit'] ?? 0);
$edit_aid = (int)($_GET['edit_acc'] ?? 0);
if ($edit_pid) $section = 'compose';
if ($edit_aid) $section = 'accounts';

$accounts  = @$db->query("SELECT * FROM social_accounts WHERE is_active=1 ORDER BY platform,name")->fetch_all(MYSQLI_ASSOC) ?: [];
$posts     = @$db->query("SELECT sp.*,sa.name acc_name,sa.platform acc_platform,u.name assigned_name FROM social_posts sp LEFT JOIN social_accounts sa ON sa.id=sp.account_id LEFT JOIN users u ON u.id=sp.assigned_to ORDER BY FIELD(sp.status,'scheduled','draft','idea','published','cancelled'),sp.scheduled_at ASC,sp.created_at DESC LIMIT 200")->fetch_all(MYSQLI_ASSOC) ?: [];
$projects  = @$db->query("SELECT id,title FROM projects WHERE status='active' ORDER BY title")->fetch_all(MYSQLI_ASSOC) ?: [];
$team      = @$db->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC) ?: [];
$templates = @$db->query("SELECT * FROM social_templates ORDER BY type,name")->fetch_all(MYSQLI_ASSOC) ?: [];
$ep        = $edit_pid ? @$db->query("SELECT * FROM social_posts WHERE id=$edit_pid")->fetch_assoc() : null;
$ea        = $edit_aid ? @$db->query("SELECT * FROM social_accounts WHERE id=$edit_aid")->fetch_assoc() : null;

$pub_count   = count(array_filter($posts, fn($p) => $p['status'] === 'published'));
$sched_count = count(array_filter($posts, fn($p) => $p['status'] === 'scheduled'));
$draft_count = count(array_filter($posts, fn($p) => $p['status'] === 'draft'));
$idea_count  = count(array_filter($posts, fn($p) => $p['status'] === 'idea'));

$PLATS = [
    'facebook'  => ['label' => 'Facebook',   'color' => '#1877f2', 'icon' => 'FB', 'bg' => '#1877f220'],
    'instagram' => ['label' => 'Instagram',  'color' => '#e1306c', 'icon' => 'IG', 'bg' => '#e1306c20'],
    'twitter'   => ['label' => 'Twitter/X',  'color' => '#1da1f2', 'icon' => 'X',  'bg' => '#1da1f220'],
    'linkedin'  => ['label' => 'LinkedIn',   'color' => '#0077b5', 'icon' => 'LI', 'bg' => '#0077b520'],
    'youtube'   => ['label' => 'YouTube',    'color' => '#ff0000', 'icon' => 'YT', 'bg' => '#ff000020'],
    'tiktok'    => ['label' => 'TikTok',     'color' => '#010101', 'icon' => 'TT', 'bg' => '#01010118'],
    'other'     => ['label' => 'Other',      'color' => '#64748b', 'icon' => '●',  'bg' => '#64748b15'],
];
$STATUS = [
    'idea'      => ['label' => '💡 Idea',      'color' => '#94a3b8', 'bg' => 'rgba(148,163,184,.12)'],
    'draft'     => ['label' => '📝 Draft',     'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.12)'],
    'scheduled' => ['label' => '📅 Scheduled', 'color' => '#6366f1', 'bg' => 'rgba(99,102,241,.12)'],
    'published' => ['label' => '✅ Published', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,.12)'],
    'cancelled' => ['label' => '❌ Cancelled', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,.12)'],
];
$TYPE_ICONS = ['post' => '📄', 'story' => '⭕', 'reel' => '🎬', 'video' => '▶️', 'article' => '📰'];
$CLIMITS    = ['twitter' => 280, 'instagram' => 2200, 'facebook' => 63206, 'linkedin' => 3000, 'tiktok' => 2200, 'youtube' => 5000, 'other' => 5000];
$PU         = ['facebook' => 'https://www.facebook.com/', 'instagram' => 'https://www.instagram.com/create/story', 'twitter' => 'https://twitter.com/compose/tweet', 'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/', 'youtube' => 'https://studio.youtube.com/', 'tiktok' => 'https://www.tiktok.com/upload'];

function pb($pl, $P) { $pc = $P[$pl] ?? $P['other']; return "<span class='plat-badge' style='background:{$pc['bg']};color:{$pc['color']}'>{$pc['icon']} {$pc['label']}</span>"; }
function sb($s, $S)  { $sc = $S[$s]  ?? $S['idea'];  return "<span class='status-badge' style='background:{$sc['bg']};color:{$sc['color']}'>{$sc['label']}</span>"; }

renderLayout('Social Media', 'social_media');
?>
<style>
/* ── DESIGN TOKENS ── */
:root {
  --sm-radius: 12px;
  --sm-radius-sm: 8px;
  --sm-shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
  --sm-shadow-hover: 0 4px 12px rgba(0,0,0,.12), 0 8px 32px rgba(0,0,0,.1);
  --sm-transition: .18s cubic-bezier(.4,0,.2,1);
}

/* ── BADGES ── */
.plat-badge,.status-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:700;white-space:nowrap}

/* ── TABS ── */
.sm-tabs{display:flex;gap:2px;border-bottom:2px solid var(--border);margin-bottom:24px;overflow-x:auto;padding-bottom:0}
.sm-tab{padding:9px 16px;font-size:12.5px;font-weight:600;color:var(--text3);cursor:pointer;border:none;background:none;border-bottom:3px solid transparent;margin-bottom:-2px;white-space:nowrap;transition:color var(--sm-transition),border-color var(--sm-transition);border-radius:var(--sm-radius-sm) var(--sm-radius-sm) 0 0}
.sm-tab.active{color:var(--orange);border-bottom-color:var(--orange);background:var(--orange-bg)}
.sm-tab:hover:not(.active){color:var(--text);background:var(--bg3)}

/* ── STAT STRIP ── */
.sm-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}
.sm-stat{background:var(--bg2);border:1px solid var(--border);border-radius:var(--sm-radius);padding:16px 18px;display:flex;align-items:center;gap:14px;transition:all var(--sm-transition);cursor:default}
.sm-stat:hover{border-color:var(--orange);box-shadow:var(--sm-shadow-hover);transform:translateY(-1px)}
.sm-stat-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.sm-stat-val{font-size:26px;font-weight:800;font-family:var(--font-display);line-height:1}
.sm-stat-lbl{font-size:11px;color:var(--text3);text-transform:uppercase;letter-spacing:.07em;margin-top:3px;font-weight:600}

/* ── PANELS ── */
.sm-panel{background:var(--bg2);border:1px solid var(--border);border-radius:var(--sm-radius);overflow:hidden;margin-bottom:16px;box-shadow:var(--sm-shadow)}
.sm-panel-head{padding:13px 18px;border-bottom:1px solid var(--border);background:var(--bg3);font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:space-between;gap:8px;letter-spacing:.01em}
.sm-panel-body{padding:16px 18px}

/* ── KANBAN ── */
.sm-kanban{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;align-items:start}
.sm-col-head{padding:10px 13px;border-radius:var(--sm-radius-sm);margin-bottom:10px;font-size:11.5px;font-weight:700;display:flex;align-items:center;justify-content:space-between;letter-spacing:.02em}
.sm-col-head .col-cnt{background:rgba(255,255,255,.2);padding:2px 8px;border-radius:99px;font-size:11px;backdrop-filter:blur(4px)}
.sm-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--sm-radius);overflow:hidden;margin-bottom:10px;transition:all var(--sm-transition);cursor:pointer;box-shadow:var(--sm-shadow)}
.sm-card:hover{transform:translateY(-3px);box-shadow:var(--sm-shadow-hover);border-color:var(--border2)}
.sm-card-body{padding:12px 14px}
.sm-card-title{font-size:13px;font-weight:700;color:var(--text);margin-bottom:5px;line-height:1.35}
.sm-card-preview{font-size:11.5px;color:var(--text3);line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:8px}
.sm-card-meta{font-size:11px;color:var(--text3);display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.sm-card-foot{display:flex;gap:2px;padding:7px 10px;border-top:1px solid var(--border);background:var(--bg3)}
.sm-card-foot button,.sm-card-foot a{flex:1;padding:5px 0;font-size:11px;font-weight:600;border:none;background:none;cursor:pointer;color:var(--text3);border-radius:6px;transition:all .12s;text-align:center;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:3px}
.sm-card-foot button:hover,.sm-card-foot a:hover{background:var(--bg4);color:var(--text)}
.sm-empty-col{text-align:center;padding:20px 8px;color:var(--text3);font-size:12px;background:var(--bg3);border:1px dashed var(--border);border-radius:var(--sm-radius-sm)}

/* ── COMPOSE ── */
.compose-wrap{display:grid;grid-template-columns:1fr 320px;gap:18px;align-items:start}
.char-badge{font-size:11px;font-weight:700;padding:3px 9px;border-radius:99px;transition:all .2s}
.char-ok{background:rgba(16,185,129,.12);color:#10b981}
.char-warn{background:rgba(245,158,11,.12);color:#f59e0b}
.char-over{background:rgba(239,68,68,.12);color:#ef4444}
.cap-preview{background:var(--bg3);border:1px solid var(--border);border-radius:var(--sm-radius-sm);padding:14px 16px;min-height:80px;font-size:13.5px;line-height:1.75;color:var(--text2);white-space:pre-wrap;word-break:break-word;font-family:Arial,sans-serif}
.tpl-chip{display:inline-block;background:var(--bg3);border:1px solid var(--border);border-radius:99px;padding:4px 11px;font-size:11.5px;cursor:pointer;margin:2px 2px 0 0;transition:all .12s;color:var(--text2)}
.tpl-chip:hover{background:var(--orange-bg);border-color:var(--orange);color:var(--orange)}

/* ── ACCOUNTS (REDESIGNED) ── */
.acc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
.acc-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--sm-radius);overflow:hidden;transition:all var(--sm-transition);box-shadow:var(--sm-shadow)}
.acc-card:hover{border-color:var(--border2);box-shadow:var(--sm-shadow-hover);transform:translateY(-2px)}
.acc-card-header{padding:16px 18px 14px;display:flex;align-items:center;gap:12px;border-bottom:1px solid var(--border)}
.acc-plat-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;flex-shrink:0}
.acc-card-name{font-size:15px;font-weight:700;color:var(--text);line-height:1.2}
.acc-card-handle{font-size:12px;color:var(--text3);margin-top:2px}
.acc-stats-row{display:grid;grid-template-columns:1fr 1fr;gap:1px;background:var(--border)}
.acc-stat-cell{background:var(--bg2);padding:12px 16px;text-align:center}
.acc-stat-val{font-size:20px;font-weight:800;font-family:var(--font-display);line-height:1}
.acc-stat-lbl{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-top:3px;font-weight:600}
.acc-creds{padding:14px 18px;border-top:1px solid var(--border);background:var(--bg3)}
.acc-creds-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--orange);margin-bottom:10px;display:flex;align-items:center;gap:5px}
.acc-cred-row{display:flex;align-items:center;gap:8px;padding:7px 10px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--sm-radius-sm);margin-bottom:6px}
.acc-cred-label{font-size:10px;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:.04em;min-width:16px}
.acc-cred-val{font-size:12.5px;color:var(--text2);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-family:monospace}
.acc-cred-actions{display:flex;gap:2px;flex-shrink:0}
.acc-cred-btn{background:none;border:none;cursor:pointer;color:var(--text3);font-size:13px;padding:3px 5px;border-radius:5px;transition:all .12s;line-height:1}
.acc-cred-btn:hover{background:var(--bg4);color:var(--text)}
.acc-card-footer{padding:12px 16px;display:flex;gap:8px;border-top:1px solid var(--border)}
.acc-notes{padding:10px 18px;font-size:12px;color:var(--text3);font-style:italic;border-top:1px solid var(--border);background:var(--bg3);line-height:1.5}

/* ── PASSWORD FIELD ── */
.pass-field{position:relative}
.pass-field input{padding-right:40px}
.pass-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text3);font-size:15px;padding:2px 4px;transition:color .12s;line-height:1}
.pass-toggle:hover{color:var(--orange)}

/* ── CALENDAR ── */
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px}
.cal-cell{background:var(--bg2);border:1px solid var(--border);border-radius:var(--sm-radius-sm);min-height:84px;padding:7px;transition:border-color var(--sm-transition)}
.cal-cell.today{border-color:var(--orange);background:var(--orange-bg)}
.cal-cell:hover:not(.today){border-color:var(--border2)}
.cal-num{font-size:11px;font-weight:700;color:var(--text3);margin-bottom:4px}
.cal-cell.today .cal-num{color:var(--orange)}
.cal-chip{font-size:10px;font-weight:600;padding:2px 6px;border-radius:4px;margin-bottom:2px;cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;transition:opacity .1s}
.cal-chip:hover{opacity:.8}

/* ── DASHBOARD ── */
.dash-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px}
.status-strip{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}
.status-box{background:var(--bg2);border:1px solid var(--border);border-radius:var(--sm-radius);padding:16px;cursor:pointer;transition:all var(--sm-transition);box-shadow:var(--sm-shadow)}
.status-box:hover{box-shadow:var(--sm-shadow-hover);transform:translateY(-2px)}

/* ── SECTION HEADER ── */
.sec-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px}
.sec-title{font-size:14px;font-weight:700;color:var(--text)}

/* ── FORM IMPROVEMENTS ── */
.cred-section{background:var(--bg3);border:1px solid var(--border);border-radius:var(--sm-radius-sm);padding:14px 16px;margin:14px 0}
.cred-section-title{font-size:10.5px;font-weight:700;color:var(--orange);text-transform:uppercase;letter-spacing:.07em;margin-bottom:12px;display:flex;align-items:center;gap:5px}

/* ── RESPONSIVE ── */
@media(max-width:1200px){.sm-stats{grid-template-columns:repeat(2,1fr)}.sm-kanban{grid-template-columns:repeat(3,1fr)}}
@media(max-width:900px){.sm-kanban{grid-template-columns:1fr 1fr}.compose-wrap{grid-template-columns:1fr}.dash-grid{grid-template-columns:1fr}}
@media(max-width:600px){.sm-kanban{grid-template-columns:1fr}.sm-stats{grid-template-columns:repeat(2,1fr)}.acc-grid{grid-template-columns:1fr}.status-strip{grid-template-columns:repeat(2,1fr)}}
</style>

<!-- ── STAT STRIP ── -->
<div class="sm-stats">
<?php
$stat_items = [
    ['icon'=>'📱','val'=>count($accounts),'lbl'=>'Accounts',  'color'=>'var(--orange)','bg'=>'rgba(249,115,22,.12)'],
    ['icon'=>'📅','val'=>$sched_count,    'lbl'=>'Scheduled', 'color'=>'#6366f1',      'bg'=>'rgba(99,102,241,.12)'],
    ['icon'=>'📝','val'=>$draft_count,    'lbl'=>'Drafts',    'color'=>'#f59e0b',      'bg'=>'rgba(245,158,11,.12)'],
    ['icon'=>'✅','val'=>$pub_count,      'lbl'=>'Published', 'color'=>'#10b981',      'bg'=>'rgba(16,185,129,.12)'],
];
foreach ($stat_items as $s): ?>
<div class="sm-stat">
  <div class="sm-stat-icon" style="background:<?=$s['bg']?>"><?=$s['icon']?></div>
  <div>
    <div class="sm-stat-val" style="color:<?=$s['color']?>"><?=$s['val']?></div>
    <div class="sm-stat-lbl"><?=$s['lbl']?></div>
  </div>
</div>
<?php endforeach; ?>
</div>

<!-- ── TABS ── -->
<div class="sm-tabs">
<?php
$stabs = [
    ['dashboard', '📊 Dashboard'],
    ['pipeline',  '📋 Pipeline'],
    ['compose',   '✏️ Compose'],
    ['calendar',  '📅 Calendar'],
    ['accounts',  '🔐 Accounts'],
];
foreach ($stabs as [$sk, $sl]): ?>
<button class="sm-tab <?=$section===$sk?'active':''?>" onclick="smSec('<?=$sk?>')"><?=$sl?></button>
<?php endforeach; ?>
</div>

<!-- ════════════════ DASHBOARD ════════════════ -->
<div id="smsec-dashboard" style="display:<?=$section==='dashboard'?'block':'none'?>">
  <div class="dash-grid">
    <div class="sm-panel" style="margin-bottom:0">
      <div class="sm-panel-head">
        📅 Upcoming &amp; Scheduled
        <button onclick="smSec('compose')" class="btn btn-primary btn-sm">✏️ <?=$is_manager?'New Post':'Draft Post'?></button>
      </div>
      <div style="padding:0">
        <?php
        $upcoming = array_slice(array_values(array_filter($posts, fn($p) => in_array($p['status'], ['scheduled','draft','idea']))), 0, 8);
        if (!$upcoming): ?>
        <div style="padding:32px;text-align:center;color:var(--text3);font-size:13px">
          No upcoming posts. <button onclick="smSec('compose')" style="background:none;border:none;color:var(--orange);cursor:pointer;font-weight:700;font-size:13px">Create one →</button>
        </div>
        <?php else: foreach ($upcoming as $p):
            $pc   = $PLATS[$p['platform'] ?? 'other'] ?? $PLATS['other'];
            $late = $p['status']==='scheduled' && $p['scheduled_at'] && strtotime($p['scheduled_at']) < time();
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:11px 16px;border-bottom:1px solid var(--border);cursor:pointer;transition:background .12s<?=$late?';background:rgba(239,68,68,.04)':''?>" onclick="location.href='social_media.php?edit=<?=$p['id']?>'" onmouseover="this.style.background='var(--bg3)'" onmouseout="this.style.background='<?=$late?'rgba(239,68,68,.04)':'transparent'?>'">
          <div style="width:4px;height:40px;border-radius:99px;background:<?=$pc['color']?>;flex-shrink:0"></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=h($p['title'])?></div>
            <div style="font-size:11px;color:var(--text3);margin-top:2px"><?=$pc['icon'].' '.$pc['label']?><?=$p['assigned_name']?' · 👤 '.h($p['assigned_name']):''?></div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <?php if($p['scheduled_at']): ?><div style="font-size:11px;color:<?=$late?'#ef4444':'var(--text3)'?>;margin-bottom:4px"><?=$late?'⚠ Overdue':date('M j',strtotime($p['scheduled_at']))?></div><?php endif; ?>
            <?=sb($p['status'],$STATUS)?>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <div class="sm-panel" style="margin-bottom:0">
      <div class="sm-panel-head">
        🏢 Accounts Overview
        <?php if ($is_manager): ?><button onclick="smSec('accounts')" class="btn btn-ghost btn-sm">Manage →</button><?php endif; ?>
      </div>
      <div style="padding:0">
        <?php if (!$accounts): ?>
        <div style="padding:24px;text-align:center;color:var(--text3);font-size:12.5px">No accounts yet. <button onclick="smSec('accounts')" style="background:none;border:none;color:var(--orange);cursor:pointer;font-weight:700">Add →</button></div>
        <?php else: foreach ($accounts as $a):
            $pc = $PLATS[$a['platform']] ?? $PLATS['other'];
            $ac = count(array_filter($posts, fn($p) => $p['account_id'] == $a['id']));
            $ap = count(array_filter($posts, fn($p) => $p['account_id'] == $a['id'] && $p['status'] === 'published'));
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:11px 16px;border-bottom:1px solid var(--border);transition:background .12s" onmouseover="this.style.background='var(--bg3)'" onmouseout="this.style.background='transparent'">
          <div style="width:36px;height:36px;border-radius:9px;background:<?=$pc['bg']?>;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:<?=$pc['color']?>;flex-shrink:0"><?=$pc['icon']?></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600;color:var(--text)"><?=h($a['name'])?></div>
            <div style="font-size:11px;color:var(--text3);margin-top:1px"><?=$a['followers']?number_format($a['followers']).' followers':$pc['label']?> · <?=$ac?> posts · <?=$ap?> published</div>
          </div>
          <?php if($a['url']): ?><a href="<?=h($a['url'])?>" target="_blank" style="color:var(--orange);font-size:13px;text-decoration:none;padding:4px 8px;background:var(--orange-bg);border-radius:6px;font-weight:700;white-space:nowrap">↗</a><?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

  <div class="status-strip">
    <?php foreach ($STATUS as $sk => $sv):
      $cnt = count(array_filter($posts, fn($p) => $p['status'] === $sk)); ?>
    <div class="status-box" onclick="smSec('pipeline')" onmouseover="this.style.borderColor='<?=$sv['color']?>'" onmouseout="this.style.borderColor='var(--border)'">
      <div style="font-size:28px;font-weight:800;color:<?=$sv['color']?>;font-family:var(--font-display)"><?=$cnt?></div>
      <div style="font-size:12px;color:var(--text3);margin-top:4px;font-weight:500"><?=$sv['label']?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ════════════════ PIPELINE ════════════════ -->
<div id="smsec-pipeline" style="display:<?=$section==='pipeline'?'block':'none'?>">
  <div class="sec-header">
    <div class="sec-title">Content Pipeline</div>
    <?php if ($is_manager): ?>
    <button onclick="smSec('compose')" class="btn btn-primary">✏️ New Post</button>
    <?php else: ?>
    <button onclick="smSec('compose')" class="btn btn-ghost">✏️ Draft Post</button>
    <?php endif; ?>
  </div>
  <div class="sm-kanban">
  <?php foreach (['idea','draft','scheduled','published','cancelled'] as $st):
    $sc      = $STATUS[$st];
    $stposts = array_filter($posts, fn($p) => $p['status'] === $st);
  ?>
    <div>
      <div class="sm-col-head" style="background:<?=$sc['bg']?>;color:<?=$sc['color']?>">
        <span><?=$sc['label']?></span>
        <span class="col-cnt"><?=count($stposts)?></span>
      </div>
      <?php if (!$stposts): ?>
      <div class="sm-empty-col">No posts here</div>
      <?php endif; ?>
      <?php foreach ($stposts as $p):
        $pc   = $PLATS[$p['platform'] ?? ($p['acc_platform'] ?? 'other')] ?? $PLATS['other'];
        $late = $st==='scheduled' && $p['scheduled_at'] && strtotime($p['scheduled_at']) < time();
        $can_edit_post = $is_manager || ($p['assigned_to'] == $uid) || ($p['created_by'] == $uid);
      ?>
      <div class="sm-card">
        <div style="height:3px;background:<?=$pc['color']?>"></div>
        <div class="sm-card-body" onclick="location.href='social_media.php?edit=<?=$p['id']?>'">
          <div style="display:flex;justify-content:space-between;gap:5px;margin-bottom:5px">
            <div class="sm-card-title"><?=h($p['title'])?></div>
            <span style="font-size:14px;flex-shrink:0;opacity:.7"><?=$TYPE_ICONS[$p['post_type']??'post']??'📄'?></span>
          </div>
          <?php if($p['content']): ?><div class="sm-card-preview"><?=h(mb_substr($p['content'],0,90))?></div><?php endif; ?>
          <div class="sm-card-meta">
            <span style="color:<?=$pc['color']?>;font-weight:700"><?=$pc['icon'].' '.$pc['label']?></span>
            <?php if($late): ?><span style="color:#ef4444;font-weight:700">⚠ Overdue</span>
            <?php elseif($p['scheduled_at']): ?><span>📅 <?=date('M j',strtotime($p['scheduled_at']))?></span><?php endif; ?>
            <?php if($p['assigned_name']): ?><span>👤 <?=h($p['assigned_name'])?></span><?php endif; ?>
          </div>
        </div>
        <div class="sm-card-foot">
          <?php if ($can_edit_post): ?><button onclick="location.href='social_media.php?edit=<?=$p['id']?>'">✎ Edit</button><?php endif; ?>
          <button onclick="quickCopy(<?=$p['id']?>)" title="Copy caption">📋 Copy</button>
          <?php if($is_manager && $st!=='published'): ?><button onclick="markPublished(<?=$p['id']?>)" style="color:#10b981">✅</button><?php endif; ?>
          <?php if(isset($PU[$p['platform']??''])): ?><a href="<?=h($PU[$p['platform']])?>" target="_blank" style="color:<?=$pc['color']?>">↗</a><?php endif; ?>
          <?php if ($can_edit_post): ?><button onclick="delPost(<?=$p['id']?>)" style="color:var(--red)">🗑</button><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
  </div>
</div>

<!-- ════════════════ COMPOSE ════════════════ -->
<div id="smsec-compose" style="display:<?=$section==='compose'?'block':'none'?>">
  <form method="POST" id="cmp-form">
    <input type="hidden" name="action" value="save_post">
    <input type="hidden" name="back_to" value="pipeline">
    <?php if($ep): ?><input type="hidden" name="post_id" value="<?=$ep['id']?>"><?php endif; ?>
    <div class="compose-wrap">
      <div>
        <div class="sm-panel">
          <div class="sm-panel-head">
            ✏️ <?=$ep?'Editing: '.h(mb_substr($ep['title'],0,40)):'New Post'?>
            <div style="display:flex;gap:8px">
              <select name="platform" id="cmp-plat" class="form-control" style="width:150px;font-size:12px;height:32px;padding:0 8px" onchange="onPlatChange(this.value)">
                <option value="">— Platform —</option>
                <?php foreach($PLATS as $pk=>$pv): ?>
                <option value="<?=$pk?>" <?=($ep['platform']??'')===$pk?'selected':''?>><?=$pv['icon'].' '.$pv['label']?></option>
                <?php endforeach; ?>
              </select>
              <select name="post_type" class="form-control" style="width:120px;font-size:12px;height:32px;padding:0 8px">
                <?php foreach(['post'=>'📄 Post','story'=>'⭕ Story','reel'=>'🎬 Reel','video'=>'▶️ Video','article'=>'📰 Article'] as $tv=>$tl): ?>
                <option value="<?=$tv?>" <?=($ep['post_type']??'post')===$tv?'selected':''?>><?=$tl?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="sm-panel-body">
            <div class="form-group">
              <label class="form-label">Title / Reference *</label>
              <input type="text" name="title" class="form-control" required placeholder="Internal reference name" value="<?=h($ep['title']??'')?>">
            </div>
            <div class="form-group">
              <label class="form-label" style="display:flex;justify-content:space-between;align-items:center">
                Caption / Content
                <span class="char-badge char-ok" id="char-cnt">0</span>
              </label>
              <textarea name="content" id="cmp-content" class="form-control" rows="7" placeholder="Write your caption here..." oninput="onInput()" style="font-family:Arial,sans-serif;font-size:13.5px;line-height:1.65;resize:vertical"><?=h($ep['content']??'')?></textarea>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
              <span style="font-size:11.5px;font-weight:600;color:var(--text3)">Live Preview</span>
              <button type="button" onclick="copyCaption()" class="btn btn-ghost btn-sm" style="font-size:11px">📋 Copy Caption</button>
            </div>
            <div class="cap-preview" id="cmp-preview">Caption preview appears here...</div>
          </div>
        </div>

        <div class="sm-panel">
          <div class="sm-panel-head">🏷️ Hashtags &amp; Notes</div>
          <div class="sm-panel-body">
            <div class="form-group">
              <label class="form-label">Hashtags</label>
              <input type="text" name="tags" id="cmp-tags" class="form-control" placeholder="#marketing #digital #brand" value="<?=h($ep['tags']??'')?>" oninput="onInput()">
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">Internal Notes <span style="font-size:11px;color:var(--text3)">(not published)</span></label>
              <textarea name="caption_notes" class="form-control" rows="2" placeholder="e.g. Use product photo, post at 7pm, tag @partner"><?=h($ep['caption_notes']??'')?></textarea>
            </div>
          </div>
        </div>
      </div>

      <div>
        <div class="sm-panel">
          <div class="sm-panel-head">⚙️ Settings</div>
          <div class="sm-panel-body">
            <div class="form-group">
              <label class="form-label">Account</label>
              <select name="account_id" class="form-control" onchange="syncAccPlat(this)">
                <option value="">— Select account —</option>
                <?php foreach($accounts as $a): $pc=$PLATS[$a['platform']]??$PLATS['other']; ?>
                <option value="<?=$a['id']?>" data-platform="<?=$a['platform']?>" <?=($ep['account_id']??'')==$a['id']?'selected':''?>><?=$pc['icon'].' '.h($a['name'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                  <?php foreach($STATUS as $sv=>$sc): ?>
                  <option value="<?=$sv?>" <?=($ep['status']??'idea')===$sv?'selected':''?>><?=$sc['label']?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Assign To</label>
                <select name="assigned_to" class="form-control">
                  <option value="">Anyone</option>
                  <?php foreach($team as $tm): ?>
                  <option value="<?=$tm['id']?>" <?=($ep['assigned_to']??'')==$tm['id']?'selected':''?>><?=h($tm['name'])?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Schedule Date &amp; Time</label>
              <input type="datetime-local" name="scheduled_at" class="form-control" value="<?=$ep['scheduled_at']?date('Y-m-d\TH:i',strtotime($ep['scheduled_at'])):''?>">
            </div>
            <div class="form-group" style="margin-bottom:16px">
              <label class="form-label">Linked Project</label>
              <select name="project_id" class="form-control">
                <option value="">— None —</option>
                <?php foreach($projects as $pr): ?>
                <option value="<?=$pr['id']?>" <?=($ep['project_id']??'')==$pr['id']?'selected':''?>><?=h($pr['title'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <button type="submit" name="status" value="draft" class="btn btn-ghost" style="flex:1">💾 Draft</button>
              <?php if ($is_manager): ?>
              <button type="submit" name="status" value="scheduled" class="btn btn-ghost" style="flex:1;color:#6366f1;border-color:#6366f1">📅 Schedule</button>
              <button type="submit" name="status" value="published" class="btn btn-primary" style="flex:1;background:#10b981;border-color:#10b981">✅ Publish</button>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="sm-panel" id="open-plat-panel">
          <div class="sm-panel-body" style="padding:12px">
            <div id="open-plat-btn">
              <div style="text-align:center;font-size:12px;color:var(--text3);padding:4px">Select a platform to open it directly</div>
            </div>
            <div style="font-size:10.5px;color:var(--text3);text-align:center;margin-top:6px">Copy caption first, then paste on platform</div>
          </div>
        </div>

        <?php
        $cap_t = array_filter($templates, fn($t) => $t['type'] === 'caption');
        $htg_t = array_filter($templates, fn($t) => $t['type'] === 'hashtag');
        $cta_t = array_filter($templates, fn($t) => $t['type'] === 'cta');
        ?>
        <div class="sm-panel">
          <div class="sm-panel-head">📚 Templates <span style="font-size:11px;font-weight:400;color:var(--text3);margin-left:4px">click to insert</span></div>
          <div class="sm-panel-body">
            <?php if ($templates): ?>
            <?php if($cap_t): ?>
            <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.07em;font-weight:700;margin-bottom:6px">Captions</div>
            <div style="margin-bottom:10px"><?php foreach($cap_t as $t): ?><div class="tpl-chip" onclick="insCap(<?=htmlspecialchars(json_encode($t['content']),ENT_QUOTES)?>)"><?=h($t['name'])?></div><?php endforeach; ?></div>
            <?php endif; if($htg_t): ?>
            <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.07em;font-weight:700;margin-bottom:6px">Hashtags</div>
            <div style="margin-bottom:10px"><?php foreach($htg_t as $t): ?><div class="tpl-chip" onclick="insTags(<?=htmlspecialchars(json_encode($t['content']),ENT_QUOTES)?>)"><?=h($t['name'])?></div><?php endforeach; ?></div>
            <?php endif; if($cta_t): ?>
            <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.07em;font-weight:700;margin-bottom:6px">CTAs</div>
            <div style="margin-bottom:10px"><?php foreach($cta_t as $t): ?><div class="tpl-chip" onclick="insCap(<?=htmlspecialchars(json_encode($t['content']),ENT_QUOTES)?>)"><?=h($t['name'])?></div><?php endforeach; ?></div>
            <?php endif; ?>
            <div style="border-top:1px solid var(--border);padding-top:12px;margin-top:4px">
            <?php endif; ?>
            <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.07em;font-weight:700;margin-bottom:8px">+ Add Template</div>
            <form method="POST">
              <input type="hidden" name="action" value="save_template">
              <div class="form-group"><input type="text" name="tpl_name" class="form-control" placeholder="Template name" required style="font-size:12px"></div>
              <div class="form-row" style="margin-bottom:8px">
                <select name="tpl_type" class="form-control" style="font-size:12px"><option value="caption">📝 Caption</option><option value="hashtag">🏷 Hashtags</option><option value="cta">🎯 CTA</option></select>
                <select name="tpl_platform" class="form-control" style="font-size:12px"><option value="">All platforms</option><?php foreach($PLATS as $pk=>$pv): ?><option value="<?=$pk?>"><?=$pv['label']?></option><?php endforeach; ?></select>
              </div>
              <textarea name="tpl_content" class="form-control" rows="3" placeholder="Template content..." required style="font-size:12px"></textarea>
              <button type="submit" class="btn btn-ghost btn-sm" style="margin-top:8px;width:100%;font-size:12px">Save Template</button>
            </form>
            <?php if ($templates): ?></div><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- ════════════════ CALENDAR ════════════════ -->
<div id="smsec-calendar" style="display:<?=$section==='calendar'?'block':'none'?>">
  <?php
  $cy    = (int)($_GET['cy'] ?? date('Y'));
  $cm    = (int)($_GET['cm'] ?? date('n'));
  $first = mktime(0,0,0,$cm,1,$cy);
  $days  = (int)date('t',$first);
  $start = (int)date('N',$first);
  $pbd   = [];
  foreach($posts as $p){
      $dt = $p['scheduled_at'] ?: $p['created_at'];
      if(!$dt) continue;
      if(date('Y-n',strtotime($dt)) === "$cy-$cm") $pbd[(int)date('j',strtotime($dt))][] = $p;
  }
  ?>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px">
    <div style="display:flex;align-items:center;gap:12px">
      <a href="?section=calendar&cy=<?=$cm===1?$cy-1:$cy?>&cm=<?=$cm===1?12:$cm-1?>" class="btn btn-ghost btn-sm">← Prev</a>
      <div style="font-size:16px;font-weight:700;font-family:var(--font-display)"><?=date('F Y',$first)?></div>
      <a href="?section=calendar&cy=<?=$cm===12?$cy+1:$cy?>&cm=<?=$cm===12?1:$cm+1?>" class="btn btn-ghost btn-sm">Next →</a>
    </div>
    <button onclick="smSec('compose')" class="btn btn-primary">✏️ <?=$is_manager?'New Post':'Draft Post'?></button>
  </div>
  <div class="cal-grid" style="margin-bottom:5px">
    <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
    <div style="text-align:center;font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;padding:5px"><?=$d?></div>
    <?php endforeach; ?>
  </div>
  <div class="cal-grid">
    <?php
    for($i=1;$i<$start;$i++) echo '<div></div>';
    for($d=1;$d<=$days;$d++){
        $today=($d==date('j')&&$cm==date('n')&&$cy==date('Y'));
        echo '<div class="cal-cell'.($today?' today':'').'">';
        echo '<div class="cal-num">'.$d.'</div>';
        if(!empty($pbd[$d])) foreach($pbd[$d] as $p){
            $pc=$PLATS[$p['platform']??'other']??$PLATS['other'];
            echo '<div class="cal-chip" onclick="location.href=\'social_media.php?edit='.$p['id'].'\'" style="background:'.$pc['color'].'22;color:'.$pc['color'].'" title="'.h($p['title']).'">'.h(mb_substr($p['title'],0,14)).'</div>';
        }
        echo '</div>';
    }
    ?>
  </div>
</div>

<!-- ════════════════ ACCOUNTS (REDESIGNED) ════════════════ -->
<div id="smsec-accounts" style="display:<?=$section==='accounts'?'block':'none'?>">

  <?php if (isset($_GET['err']) && $_GET['err']==='db'): ?>
  <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:var(--sm-radius);padding:12px 16px;margin-bottom:16px;color:#ef4444;font-size:13px;font-weight:600">
    ⚠ Database error while saving. Please check that all required fields are filled correctly.
  </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:<?= $is_manager ? '1fr 360px' : '1fr' ?>;gap:20px;align-items:start">

    <!-- Account cards -->
    <div>
      <div class="sec-header">
        <div class="sec-title">Connected Accounts <span style="color:var(--text3);font-weight:400">(<?=count($accounts)?>)</span></div>
        <?php if ($is_manager): ?><button onclick="document.getElementById('acc-form-panel').scrollIntoView({behavior:'smooth'})" class="btn btn-primary btn-sm">+ Add Account</button><?php endif; ?>
      </div>
      <div class="acc-grid">
        <?php foreach($accounts as $a):
          $pc      = $PLATS[$a['platform']] ?? $PLATS['other'];
          $ac      = count(array_filter($posts, fn($p) => $p['account_id'] == $a['id']));
          $ap      = count(array_filter($posts, fn($p) => $p['account_id'] == $a['id'] && $p['status'] === 'published'));
          $has_pass = !empty($a['acc_password']);
          $has_email= !empty($a['acc_email']);
          $can_see_creds = in_array($user['role']??'member', ['admin','manager']);
        ?>
        <div class="acc-card">
          <!-- Header -->
          <div class="acc-card-header">
            <div class="acc-plat-icon" style="background:<?=$pc['bg']?>;color:<?=$pc['color']?>"><?=$pc['icon']?></div>
            <div style="flex:1;min-width:0">
              <div class="acc-card-name"><?=h($a['name'])?></div>
              <div class="acc-card-handle"><?=$a['handle']?h('@'.ltrim($a['handle'],'@')):$pc['label']?></div>
            </div>
            <div style="display:flex;gap:4px;flex-shrink:0">
              <?php if($a['url']): ?><a href="<?=h($a['url'])?>" target="_blank" class="btn btn-ghost btn-sm" style="font-size:11px;padding:4px 8px">↗ Open</a><?php endif; ?>
            </div>
          </div>

          <!-- Stats -->
          <div class="acc-stats-row">
            <div class="acc-stat-cell">
              <div class="acc-stat-val" style="color:<?=$pc['color']?>"><?=$a['followers']?number_format($a['followers']):'—'?></div>
              <div class="acc-stat-lbl">Followers</div>
            </div>
            <div class="acc-stat-cell">
              <div class="acc-stat-val"><?=$ac?></div>
              <div class="acc-stat-lbl"><?=$ap?> Published</div>
            </div>
          </div>

          <!-- Credentials — admin/manager only -->
          <?php if($can_see_creds && ($has_email || $has_pass)): ?>
          <div class="acc-creds">
            <div class="acc-creds-title">🔐 Login Credentials</div>
            <?php if($has_email): ?>
            <div class="acc-cred-row">
              <span class="acc-cred-label">📧</span>
              <span class="acc-cred-val"><?=h($a['acc_email'])?></span>
              <div class="acc-cred-actions">
                <button type="button" class="acc-cred-btn" onclick="copyText(<?=htmlspecialchars(json_encode($a['acc_email']),ENT_QUOTES)?>,'Email')" title="Copy email">📋</button>
              </div>
            </div>
            <?php endif; ?>
            <?php if($has_pass): ?>
            <div class="acc-cred-row">
              <span class="acc-cred-label">🔑</span>
              <span class="acc-cred-val" id="pass-display-<?=$a['id']?>">••••••••</span>
              <div class="acc-cred-actions">
                <button type="button" class="acc-cred-btn" onclick="togglePass(<?=$a['id']?>, <?=htmlspecialchars(json_encode($a['acc_password']),ENT_QUOTES)?>)" title="Show/hide">👁</button>
                <button type="button" class="acc-cred-btn" onclick="copyText(<?=htmlspecialchars(json_encode($a['acc_password']),ENT_QUOTES)?>,'Password')" title="Copy">📋</button>
              </div>
            </div>
            <?php endif; ?>
          </div>
          <?php elseif($can_see_creds): ?>
          <div class="acc-creds">
            <div class="acc-creds-title">🔐 Login Credentials</div>
            <div style="font-size:12px;color:var(--text3)">No credentials saved. <a href="?section=accounts&edit_acc=<?=$a['id']?>" style="color:var(--orange);font-weight:600">Add →</a></div>
          </div>
          <?php endif; ?>

          <?php if($a['notes']): ?>
          <div class="acc-notes"><?=h($a['notes'])?></div>
          <?php endif; ?>

          <!-- Actions -->
          <div class="acc-card-footer">
            <?php if ($is_manager): ?>
            <a href="?section=accounts&edit_acc=<?=$a['id']?>" class="btn btn-ghost btn-sm" style="flex:1;text-align:center">✎ Edit Account</a>
            <form method="POST" onsubmit="return confirm('Remove this account?')" style="display:inline">
              <input type="hidden" name="action" value="delete_account">
              <input type="hidden" name="account_id" value="<?=$a['id']?>">
              <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Remove account">🗑</button>
            </form>
            <?php else: ?>
            <span style="font-size:12px;color:var(--text3);padding:4px">View only</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if(!$accounts): ?>
        <div class="empty-state" style="grid-column:1/-1">
          <div class="icon">📱</div>
          <p>No accounts yet.<?php if($is_manager): ?> Use the form to add one.<?php endif; ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Add/Edit form — managers only -->
    <?php if ($is_manager): ?>
    <div class="sm-panel" id="acc-form-panel">
      <div class="sm-panel-head">
        <?=$ea?'✎ Edit Account':'➕ Add New Account'?>
        <?php if($ea): ?><a href="?section=accounts" class="btn btn-ghost btn-sm">← Back</a><?php endif; ?>
      </div>
      <div class="sm-panel-body">
        <form method="POST" autocomplete="off">
          <input type="hidden" name="action" value="save_account">
          <?php if($ea): ?><input type="hidden" name="account_id" value="<?=$ea['id']?>"><?php endif; ?>

          <div class="form-group">
            <label class="form-label">Platform</label>
            <select name="platform" class="form-control">
              <?php foreach($PLATS as $pk=>$pv): ?>
              <option value="<?=$pk?>" <?=($ea['platform']??'facebook')===$pk?'selected':''?>><?=$pv['icon'].' '.$pv['label']?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Account Name *</label>
            <input type="text" name="acc_name" class="form-control" required value="<?=h($ea['name']??'')?>" placeholder="e.g. Company Facebook Page">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Handle</label>
              <input type="text" name="handle" class="form-control" value="<?=h($ea['handle']??'')?>" placeholder="@handle">
            </div>
            <div class="form-group">
              <label class="form-label">Followers</label>
              <input type="number" name="followers" class="form-control" value="<?=$ea['followers']??0?>" min="0">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Profile URL</label>
            <input type="url" name="url" class="form-control" value="<?=h($ea['url']??'')?>" placeholder="https://...">
          </div>

          <!-- Credentials section -->
          <div class="cred-section">
            <div class="cred-section-title">🔐 Login Credentials <span style="font-size:10px;font-weight:400;color:var(--text3)">(visible to managers only)</span></div>
            <div class="form-group">
              <label class="form-label">Email / Username</label>
              <input type="text" name="acc_email" class="form-control" value="<?=h($ea['acc_email']??'')?>" placeholder="login@example.com or @username" autocomplete="off">
            </div>
            <div class="form-group" style="margin-bottom:4px">
              <label class="form-label">Password</label>
              <div class="pass-field">
                <input type="password" name="acc_password" id="acc-pass-input" class="form-control" value="<?=h($ea['acc_password']??'')?>" placeholder="Account password" autocomplete="new-password">
                <button type="button" class="pass-toggle" onclick="togglePassInput()" title="Toggle visibility">👁</button>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Any notes about this account..."><?=h($ea['notes']??'')?></textarea>
          </div>
          <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary" style="flex:1">
              <?=$ea?'💾 Update Account':'➕ Add Account'?>
            </button>
            <?php if($ea): ?><a href="?section=accounts" class="btn btn-ghost">Cancel</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Hidden data for JS -->
<div id="posts-json" style="display:none"><?=htmlspecialchars(json_encode(array_values($posts)),ENT_QUOTES)?></div>
<div id="plat-urls" style="display:none"><?=htmlspecialchars(json_encode($PU),ENT_QUOTES)?></div>
<div id="plat-cfg"  style="display:none"><?=htmlspecialchars(json_encode($PLATS),ENT_QUOTES)?></div>

<script>
var smPosts   = JSON.parse(document.getElementById('posts-json').textContent);
var platUrls  = JSON.parse(document.getElementById('plat-urls').textContent);
var platCfg   = JSON.parse(document.getElementById('plat-cfg').textContent);
var charLimits= {twitter:280,instagram:2200,facebook:63206,linkedin:3000,tiktok:2200,youtube:5000,other:5000};

function smSec(n) {
    ['dashboard','pipeline','compose','calendar','accounts'].forEach(function(s){
        var el = document.getElementById('smsec-'+s);
        if(el) el.style.display = s===n?'block':'none';
    });
    document.querySelectorAll('.sm-tab').forEach(function(b,i){
        b.classList.toggle('active',['dashboard','pipeline','compose','calendar','accounts'][i]===n);
    });
    if(n==='compose') setTimeout(onInput, 50);
}

function onInput() {
    var c    = document.getElementById('cmp-content')?.value||'';
    var t    = document.getElementById('cmp-tags')?.value||'';
    var full = c + (t?'\n\n'+t:'');
    var pv   = document.getElementById('cmp-preview');
    if(pv) pv.textContent = full||'Caption preview appears here...';
    var plat  = document.getElementById('cmp-plat')?.value||'other';
    var limit = charLimits[plat]||5000;
    var cnt   = document.getElementById('char-cnt');
    if(cnt){
        cnt.textContent = c.length+' / '+limit;
        var pct = c.length/limit;
        cnt.className = 'char-badge '+(pct>1?'char-over':pct>0.85?'char-warn':'char-ok');
    }
}

function onPlatChange(plat) {
    onInput();
    var pb  = document.getElementById('open-plat-btn');
    if(!pb) return;
    var url = platUrls[plat];
    var pc  = platCfg[plat]||platCfg['other'];
    if(url) pb.innerHTML = '<a href="'+url+'" target="_blank" class="btn btn-primary" style="width:100%;text-align:center;background:'+pc.color+';border-color:'+pc.color+'">'+pc.icon+' Open '+pc.label+' →</a>';
    else    pb.innerHTML = '<div style="text-align:center;font-size:12px;color:var(--text3);padding:4px">Select a platform to open it directly</div>';
}

function syncAccPlat(sel) {
    var opt  = sel.options[sel.selectedIndex];
    var plat = opt?.dataset?.platform||'';
    var pp   = document.getElementById('cmp-plat');
    if(pp && plat) pp.value = plat;
    onPlatChange(plat);
}

function copyCaption() {
    var c    = document.getElementById('cmp-content')?.value||'';
    var t    = document.getElementById('cmp-tags')?.value||'';
    var full = c+(t?'\n\n'+t:'');
    if(!full){toast('Nothing to copy','error');return;}
    navigator.clipboard.writeText(full).then(function(){
        toast('Caption copied! Open the platform and paste 🚀','success');
    }).catch(function(){
        var ta=document.createElement('textarea');ta.value=full;document.body.appendChild(ta);ta.select();document.execCommand('copy');document.body.removeChild(ta);toast('Copied!','success');
    });
}

function quickCopy(id) {
    var p = smPosts.find(function(x){return x.id==id;});
    if(!p) return;
    var full = (p.content||'')+(p.tags?'\n\n'+p.tags:'');
    if(!full){toast('No content to copy','error');return;}
    navigator.clipboard.writeText(full).then(function(){toast('Caption copied 🚀','success');}).catch(function(){toast('Could not copy','error');});
}

function insCap(c)  { var ta=document.getElementById('cmp-content'); if(!ta){smSec('compose');setTimeout(function(){insCap(c);},300);return;} ta.value=c; onInput(); ta.focus(); toast('Template inserted','success'); }
function insTags(c) { var ti=document.getElementById('cmp-tags');    if(!ti){smSec('compose');setTimeout(function(){insTags(c);},300);return;} ti.value=c; onInput(); ti.focus(); toast('Hashtags inserted','success'); }

function markPublished(id) {
    if(!confirm('Mark this post as published?'))return;
    var fd=new FormData(); fd.append('action','quick_status'); fd.append('post_id',id); fd.append('status','published');
    fetch('social_media.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){if(d.ok){toast('Marked as published ✅','success');location.reload();}});
}

function delPost(id) {
    if(!confirm('Delete this post permanently?'))return;
    var fd=new FormData(); fd.append('action','delete_post'); fd.append('post_id',id);
    fetch('social_media.php',{method:'POST',body:fd}).then(function(){location.reload();});
}

/* ── Password Vault ── */
var passVisible = {};
function togglePass(id, pass) {
    var el = document.getElementById('pass-display-'+id);
    if(!el) return;
    passVisible[id] = !passVisible[id];
    el.textContent = passVisible[id] ? pass : '••••••••';
    el.style.letterSpacing = passVisible[id] ? '.04em' : 'normal';
}

function copyText(text, label) {
    navigator.clipboard.writeText(text).then(function(){
        toast((label||'Text')+' copied! 🔑','success');
    }).catch(function(){
        var ta=document.createElement('textarea');ta.value=text;document.body.appendChild(ta);ta.select();document.execCommand('copy');document.body.removeChild(ta);
        toast('Copied!','success');
    });
}

// Legacy alias
function copyPass(pass) { copyText(pass, 'Password'); }

function togglePassInput() {
    var inp = document.getElementById('acc-pass-input');
    if(!inp) return;
    inp.type = inp.type==='password' ? 'text' : 'password';
}

document.addEventListener('DOMContentLoaded', function(){
    onInput();
    var plt = document.getElementById('cmp-plat');
    if(plt) onPlatChange(plt.value||'');
});
</script>
<?php renderLayoutEnd(); ?>