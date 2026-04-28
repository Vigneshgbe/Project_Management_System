<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];
mysqli_report(MYSQLI_REPORT_OFF);

// ── ENSURE password column exists ──────────────────────────────────────────
@$db->query("ALTER TABLE social_accounts ADD COLUMN IF NOT EXISTS acc_password VARCHAR(500) NULL DEFAULT NULL AFTER followers");
@$db->query("ALTER TABLE social_accounts ADD COLUMN IF NOT EXISTS acc_email VARCHAR(300) NULL DEFAULT NULL AFTER acc_password");

ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

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
                $s = $db->prepare("UPDATE social_accounts SET platform=?,name=?,handle=?,url=?,followers=?,notes=?,acc_email=?,acc_password=? WHERE id=$aid");
                $s->bind_param("ssssiss s", $platform, $name, $handle, $url, $followers, $notes, $acc_email, $acc_pass);
                // fix spacing
                $s = $db->prepare("UPDATE social_accounts SET platform=?,name=?,handle=?,url=?,followers=?,notes=?,acc_email=?,acc_password=? WHERE id=$aid");
                $s->bind_param("ssssisss", $platform, $name, $handle, $url, $followers, $notes, $acc_email, $acc_pass);
            } else {
                $s = $db->prepare("INSERT INTO social_accounts (platform,name,handle,url,followers,notes,acc_email,acc_password,created_by) VALUES (?,?,?,?,?,?,?,?,?)");
                $s->bind_param("ssssisssi", $platform, $name, $handle, $url, $followers, $notes, $acc_email, $acc_pass, $uid);
            }
            $s->execute();
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

function pb($pl, $P) { $pc = $P[$pl] ?? $P['other']; return "<span style='display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:99px;font-size:11px;font-weight:700;background:{$pc['bg']};color:{$pc['color']}'>{$pc['icon']} {$pc['label']}</span>"; }
function sb($s, $S)  { $sc = $S[$s]  ?? $S['idea'];  return "<span style='display:inline-flex;padding:3px 8px;border-radius:99px;font-size:11px;font-weight:700;background:{$sc['bg']};color:{$sc['color']}'>{$sc['label']}</span>"; }

renderLayout('Social Media', 'social_media');
?>
<style>
/* ── CORE LAYOUT ── */
.sm-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px;overflow-x:auto}
.sm-tab{padding:10px 18px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;border:none;background:none;border-bottom:3px solid transparent;margin-bottom:-2px;white-space:nowrap;transition:color .15s,border-color .15s}
.sm-tab.active{color:var(--orange);border-bottom-color:var(--orange)}
.sm-tab:hover:not(.active){color:var(--text);border-bottom-color:var(--border2)}

/* ── STAT STRIP ── */
.sm-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px}
.sm-stat{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px 16px;display:flex;align-items:center;gap:12px;transition:border-color .15s}
.sm-stat:hover{border-color:var(--orange)}
.sm-stat-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.sm-stat-val{font-size:22px;font-weight:800;font-family:var(--font-display);line-height:1}
.sm-stat-lbl{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-top:2px}

/* ── KANBAN ── */
.sm-kanban{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;align-items:start}
.sm-col-head{padding:9px 12px;border-radius:var(--radius-sm);margin-bottom:8px;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:space-between}
.sm-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:8px;transition:all .15s;cursor:pointer}
.sm-card:hover{transform:translateY(-2px);box-shadow:0 4px 18px rgba(0,0,0,.15);border-color:var(--border2)}
.sm-card-body{padding:10px 12px}
.sm-card-title{font-size:13px;font-weight:700;color:var(--text);margin-bottom:4px;line-height:1.35}
.sm-card-preview{font-size:11.5px;color:var(--text3);line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:7px}
.sm-card-meta{font-size:11px;color:var(--text3);display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.sm-card-foot{display:flex;gap:2px;padding:7px 10px;border-top:1px solid var(--border);background:var(--bg3)}
.sm-card-foot button,.sm-card-foot a{flex:1;padding:4px 0;font-size:11px;font-weight:600;border:none;background:none;cursor:pointer;color:var(--text3);border-radius:var(--radius-sm);transition:all .12s;text-align:center;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:3px}
.sm-card-foot button:hover,.sm-card-foot a:hover{background:var(--bg4);color:var(--text)}

/* ── COMPOSE ── */
.compose-wrap{display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start}
.sm-panel{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:14px}
.sm-panel-head{padding:11px 16px;border-bottom:1px solid var(--border);background:var(--bg3);font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:space-between;gap:8px}
.sm-panel-body{padding:14px 16px}
.char-badge{font-size:11px;font-weight:700;padding:2px 7px;border-radius:99px}
.char-ok{background:rgba(16,185,129,.12);color:#10b981}
.char-warn{background:rgba(245,158,11,.12);color:#f59e0b}
.char-over{background:rgba(239,68,68,.12);color:#ef4444}
.cap-preview{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;min-height:64px;font-size:13px;line-height:1.7;color:var(--text2);white-space:pre-wrap;word-break:break-word;font-family:Arial,sans-serif}
.tpl-chip{display:inline-block;background:var(--bg3);border:1px solid var(--border);border-radius:99px;padding:3px 10px;font-size:11.5px;cursor:pointer;margin:2px 2px 0 0;transition:all .12s;color:var(--text2)}
.tpl-chip:hover{background:var(--orange-bg);border-color:var(--orange);color:var(--orange)}

/* ── CALENDAR ── */
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:3px}
.cal-cell{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);min-height:78px;padding:6px}
.cal-cell.today{border-color:var(--orange);background:var(--orange-bg)}
.cal-num{font-size:11px;font-weight:700;color:var(--text3);margin-bottom:3px}
.cal-cell.today .cal-num{color:var(--orange)}
.cal-chip{font-size:10px;font-weight:600;padding:2px 5px;border-radius:3px;margin-bottom:2px;cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block}

/* ── ACCOUNTS ── */
.acc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.acc-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;display:flex;flex-direction:column;gap:10px;transition:all .15s}
.acc-card:hover{border-color:var(--orange);box-shadow:0 4px 14px rgba(0,0,0,.1)}
.pass-field{position:relative}
.pass-field input{padding-right:36px}
.pass-toggle{position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text3);font-size:14px;padding:2px 4px;transition:color .12s}
.pass-toggle:hover{color:var(--orange)}
.acc-pass-reveal{font-size:12px;background:var(--bg4);border:1px solid var(--border);border-radius:var(--radius-sm);padding:6px 10px;color:var(--text2);font-family:monospace;letter-spacing:.04em;display:flex;align-items:center;justify-content:space-between;gap:8px}
.acc-pass-reveal button{background:none;border:none;cursor:pointer;color:var(--text3);font-size:12px;padding:2px 5px;border-radius:3px;transition:all .12s}
.acc-pass-reveal button:hover{background:var(--bg3);color:var(--text)}

/* ── DASHBOARD ── */
.dash-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
.status-strip{display:grid;grid-template-columns:repeat(5,1fr);gap:8px}
.status-box{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px;cursor:pointer;transition:border-color .15s}
.status-box:hover{border-color:var(--orange)}

/* ── RESPONSIVE ── */
@media(max-width:1200px){.sm-stats{grid-template-columns:repeat(2,1fr)}.sm-kanban{grid-template-columns:repeat(3,1fr)}}
@media(max-width:900px){.sm-kanban{grid-template-columns:1fr 1fr}.compose-wrap{grid-template-columns:1fr}.acc-grid{grid-template-columns:1fr 1fr}.dash-grid{grid-template-columns:1fr}}
@media(max-width:600px){.sm-kanban{grid-template-columns:1fr}.sm-stats{grid-template-columns:repeat(2,1fr)}.acc-grid{grid-template-columns:1fr}.status-strip{grid-template-columns:repeat(2,1fr)}}
</style>

<!-- ── STAT STRIP ── -->
<div class="sm-stats">
<?php
$stat_items = [
    ['icon'=>'📱','val'=>count($accounts),'lbl'=>'Accounts','color'=>'var(--orange)','bg'=>'rgba(249,115,22,.12)'],
    ['icon'=>'📅','val'=>$sched_count,'lbl'=>'Scheduled','color'=>'#6366f1','bg'=>'rgba(99,102,241,.12)'],
    ['icon'=>'📝','val'=>$draft_count,'lbl'=>'Drafts','color'=>'#f59e0b','bg'=>'rgba(245,158,11,.12)'],
    ['icon'=>'✅','val'=>$pub_count,'lbl'=>'Published','color'=>'#10b981','bg'=>'rgba(16,185,129,.12)'],
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
    <!-- Upcoming posts -->
    <div class="sm-panel" style="margin-bottom:0">
      <div class="sm-panel-head">
        📅 Upcoming &amp; Scheduled
        <button onclick="smSec('compose')" class="btn btn-primary btn-sm">✏️ New Post</button>
      </div>
      <div style="padding:0">
        <?php
        $upcoming = array_slice(array_values(array_filter($posts, fn($p) => in_array($p['status'], ['scheduled', 'draft', 'idea']))), 0, 8);
        if (!$upcoming): ?>
        <div style="padding:28px;text-align:center;color:var(--text3);font-size:13px">
          No upcoming posts. <button onclick="smSec('compose')" style="background:none;border:none;color:var(--orange);cursor:pointer;font-weight:600;font-size:13px">Create one →</button>
        </div>
        <?php else: foreach ($upcoming as $p):
            $pc   = $PLATS[$p['platform'] ?? 'other'] ?? $PLATS['other'];
            $late = $p['status']==='scheduled' && $p['scheduled_at'] && strtotime($p['scheduled_at']) < time();
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border);<?=$late?'background:rgba(239,68,68,.04)':''?>" onclick="location.href='social_media.php?edit=<?=$p['id']?>'" style="cursor:pointer">
          <div style="width:3px;height:36px;border-radius:99px;background:<?=$pc['color']?>;flex-shrink:0"></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=h($p['title'])?></div>
            <div style="font-size:11px;color:var(--text3)"><?=$pc['icon'].' '.$pc['label']?><?=$p['assigned_name']?' · 👤 '.h($p['assigned_name']):''?></div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <?php if($p['scheduled_at']): ?><div style="font-size:11px;color:<?=$late?'#ef4444':'var(--text3)'?>"><?=$late?'⚠ Overdue':date('M j',strtotime($p['scheduled_at']))?></div><?php endif; ?>
            <?=sb($p['status'],$STATUS)?>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- Accounts overview -->
    <div class="sm-panel" style="margin-bottom:0">
      <div class="sm-panel-head">🏢 Accounts Overview</div>
      <div style="padding:0">
        <?php if (!$accounts): ?>
        <div style="padding:20px;text-align:center;color:var(--text3);font-size:12.5px">No accounts. <button onclick="smSec('accounts')" style="background:none;border:none;color:var(--orange);cursor:pointer;font-weight:600">Add →</button></div>
        <?php else: foreach ($accounts as $a):
            $pc = $PLATS[$a['platform']] ?? $PLATS['other'];
            $ac = count(array_filter($posts, fn($p) => $p['account_id'] == $a['id']));
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border)">
          <div style="width:32px;height:32px;border-radius:8px;background:<?=$pc['bg']?>;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:<?=$pc['color']?>;flex-shrink:0"><?=$pc['icon']?></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600;color:var(--text)"><?=h($a['name'])?></div>
            <div style="font-size:11px;color:var(--text3)"><?=$a['followers']?number_format($a['followers']).' followers':$pc['label']?> · <?=$ac?> posts</div>
          </div>
          <?php if($a['url']): ?><a href="<?=h($a['url'])?>" target="_blank" style="color:var(--orange);font-size:14px;text-decoration:none">↗</a><?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
        <div style="padding:10px 14px">
          <button onclick="smSec('accounts')" class="btn btn-ghost btn-sm" style="width:100%">Manage Accounts</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Status count strip -->
  <div class="status-strip">
    <?php foreach ($STATUS as $sk => $sv):
      $cnt = count(array_filter($posts, fn($p) => $p['status'] === $sk)); ?>
    <div class="status-box" onclick="smSec('pipeline')" onmouseover="this.style.borderColor='<?=$sv['color']?>'" onmouseout="this.style.borderColor='var(--border)'">
      <div style="font-size:24px;font-weight:800;color:<?=$sv['color']?>;font-family:var(--font-display)"><?=$cnt?></div>
      <div style="font-size:11.5px;color:var(--text3);margin-top:2px"><?=$sv['label']?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ════════════════ PIPELINE ════════════════ -->
<div id="smsec-pipeline" style="display:<?=$section==='pipeline'?'block':'none'?>">
  <div style="display:flex;justify-content:flex-end;margin-bottom:14px">
    <button onclick="smSec('compose')" class="btn btn-primary">✏️ New Post</button>
  </div>
  <div class="sm-kanban">
  <?php foreach (['idea','draft','scheduled','published','cancelled'] as $st):
    $sc      = $STATUS[$st];
    $stposts = array_filter($posts, fn($p) => $p['status'] === $st);
  ?>
    <div>
      <div class="sm-col-head" style="background:<?=$sc['bg']?>;color:<?=$sc['color']?>">
        <?=$sc['label']?>
        <span style="background:rgba(255,255,255,.18);padding:2px 7px;border-radius:99px;font-size:10.5px"><?=count($stposts)?></span>
      </div>
      <?php if (!$stposts): ?>
      <div style="text-align:center;padding:18px 8px;color:var(--text3);font-size:12px;background:var(--bg3);border-radius:var(--radius-sm)">Empty</div>
      <?php endif; ?>
      <?php foreach ($stposts as $p):
        $pc   = $PLATS[$p['platform'] ?? ($p['acc_platform'] ?? 'other')] ?? $PLATS['other'];
        $late = $st==='scheduled' && $p['scheduled_at'] && strtotime($p['scheduled_at']) < time();
      ?>
      <div class="sm-card">
        <div style="height:3px;background:<?=$pc['color']?>"></div>
        <div class="sm-card-body" onclick="location.href='social_media.php?edit=<?=$p['id']?>'">
          <div style="display:flex;justify-content:space-between;gap:5px;margin-bottom:4px">
            <div class="sm-card-title"><?=h($p['title'])?></div>
            <span style="font-size:13px;flex-shrink:0"><?=$TYPE_ICONS[$p['post_type']??'post']??'📄'?></span>
          </div>
          <?php if($p['content']): ?><div class="sm-card-preview"><?=h(mb_substr($p['content'],0,80))?></div><?php endif; ?>
          <div class="sm-card-meta">
            <span style="color:<?=$pc['color']?>;font-weight:700"><?=$pc['icon'].' '.$pc['label']?></span>
            <?php if($late): ?><span style="color:#ef4444;font-weight:700">⚠ Overdue</span>
            <?php elseif($p['scheduled_at']): ?><span>📅 <?=date('M j',strtotime($p['scheduled_at']))?></span><?php endif; ?>
            <?php if($p['assigned_name']): ?><span>👤 <?=h($p['assigned_name'])?></span><?php endif; ?>
          </div>
        </div>
        <div class="sm-card-foot">
          <button onclick="location.href='social_media.php?edit=<?=$p['id']?>'">✎ Edit</button>
          <button onclick="quickCopy(<?=$p['id']?>)" title="Copy caption">📋 Copy</button>
          <?php if($st!=='published'): ?><button onclick="markPublished(<?=$p['id']?>)" style="color:#10b981">✅</button><?php endif; ?>
          <?php if(isset($PU[$p['platform']??''])): ?><a href="<?=h($PU[$p['platform']])?>" target="_blank" style="color:<?=$pc['color']?>">↗</a><?php endif; ?>
          <button onclick="delPost(<?=$p['id']?>)" style="color:var(--red)">🗑</button>
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

      <!-- LEFT: main content -->
      <div>
        <div class="sm-panel">
          <div class="sm-panel-head">
            ✏️ <?=$ep?'Edit: '.h(mb_substr($ep['title'],0,40)):'New Post'?>
            <div style="display:flex;gap:8px">
              <select name="platform" id="cmp-plat" class="form-control" style="width:140px;font-size:12px;height:32px;padding:0 8px" onchange="onPlatChange(this.value)">
                <option value="">— Platform —</option>
                <?php foreach($PLATS as $pk=>$pv): ?>
                <option value="<?=$pk?>" <?=($ep['platform']??'')===$pk?'selected':''?>><?=$pv['icon'].' '.$pv['label']?></option>
                <?php endforeach; ?>
              </select>
              <select name="post_type" class="form-control" style="width:110px;font-size:12px;height:32px;padding:0 8px">
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
              <label class="form-label" style="display:flex;justify-content:space-between">
                Caption / Content
                <span class="char-badge char-ok" id="char-cnt">0</span>
              </label>
              <textarea name="content" id="cmp-content" class="form-control" rows="7" placeholder="Write your caption here..." oninput="onInput()" style="font-family:Arial,sans-serif;font-size:13.5px;line-height:1.6"><?=h($ep['content']??'')?></textarea>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
              <span style="font-size:11.5px;font-weight:600;color:var(--text3)">Preview</span>
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

      <!-- RIGHT: settings sidebar -->
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
            <div class="form-group" style="margin-bottom:14px">
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
              <button type="submit" name="status" value="scheduled" class="btn btn-ghost" style="flex:1;color:#6366f1;border-color:#6366f1">📅 Schedule</button>
              <button type="submit" name="status" value="published" class="btn btn-primary" style="flex:1;background:#10b981;border-color:#10b981">✅ Publish</button>
            </div>
          </div>
        </div>

        <!-- Open on Platform shortcut -->
        <div class="sm-panel" id="open-plat-panel">
          <div class="sm-panel-body" style="padding:10px">
            <div id="open-plat-btn">
              <div style="text-align:center;font-size:12px;color:var(--text3);padding:4px">Select a platform to get the direct post link</div>
            </div>
            <div style="font-size:10.5px;color:var(--text3);text-align:center;margin-top:6px">Copy caption first, then paste on the platform</div>
          </div>
        </div>

        <!-- Quick-use templates (caption chips only) -->
        <?php
        $cap_t = array_filter($templates, fn($t) => $t['type'] === 'caption');
        $htg_t = array_filter($templates, fn($t) => $t['type'] === 'hashtag');
        $cta_t = array_filter($templates, fn($t) => $t['type'] === 'cta');
        if ($templates):
        ?>
        <div class="sm-panel">
          <div class="sm-panel-head">📚 Quick Templates <span style="font-size:11px;font-weight:400;color:var(--text3)">click to use</span></div>
          <div class="sm-panel-body">
            <?php if($cap_t): ?>
            <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;font-weight:700;margin-bottom:5px">Captions</div>
            <?php foreach($cap_t as $t): ?><div class="tpl-chip" onclick="insCap(<?=htmlspecialchars(json_encode($t['content']),ENT_QUOTES)?>)"><?=h($t['name'])?></div><?php endforeach; endif; ?>
            <?php if($htg_t): ?>
            <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;font-weight:700;margin:10px 0 5px">Hashtags</div>
            <?php foreach($htg_t as $t): ?><div class="tpl-chip" onclick="insTags(<?=htmlspecialchars(json_encode($t['content']),ENT_QUOTES)?>)"><?=h($t['name'])?></div><?php endforeach; endif; ?>
            <?php if($cta_t): ?>
            <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;font-weight:700;margin:10px 0 5px">CTA</div>
            <?php foreach($cta_t as $t): ?><div class="tpl-chip" onclick="insCap(<?=htmlspecialchars(json_encode($t['content']),ENT_QUOTES)?>)"><?=h($t['name'])?></div><?php endforeach; endif; ?>

            <!-- Manage templates inline -->
            <div style="margin-top:14px;border-top:1px solid var(--border);padding-top:12px">
              <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;font-weight:700;margin-bottom:8px">+ Add Template</div>
              <form method="POST">
                <input type="hidden" name="action" value="save_template">
                <div class="form-group"><input type="text" name="tpl_name" class="form-control" placeholder="Template name" required style="font-size:12px"></div>
                <div class="form-row" style="margin-bottom:8px">
                  <select name="tpl_type" class="form-control" style="font-size:12px"><option value="caption">📝 Caption</option><option value="hashtag">🏷 Hashtags</option><option value="cta">🎯 CTA</option></select>
                  <select name="tpl_platform" class="form-control" style="font-size:12px"><option value="">All</option><?php foreach($PLATS as $pk=>$pv): ?><option value="<?=$pk?>"><?=$pv['label']?></option><?php endforeach; ?></select>
                </div>
                <textarea name="tpl_content" class="form-control" rows="3" placeholder="Template content..." required style="font-size:12px"></textarea>
                <button type="submit" class="btn btn-ghost btn-sm" style="margin-top:8px;width:100%;font-size:12px">Save Template</button>
              </form>
            </div>
          </div>
        </div>
        <?php else: ?>
        <!-- No templates yet — show add form -->
        <div class="sm-panel">
          <div class="sm-panel-head">📚 Templates</div>
          <div class="sm-panel-body">
            <form method="POST">
              <input type="hidden" name="action" value="save_template">
              <div class="form-group"><input type="text" name="tpl_name" class="form-control" placeholder="Template name" required style="font-size:12px"></div>
              <div class="form-row" style="margin-bottom:8px">
                <select name="tpl_type" class="form-control" style="font-size:12px"><option value="caption">📝 Caption</option><option value="hashtag">🏷 Hashtags</option><option value="cta">🎯 CTA</option></select>
                <select name="tpl_platform" class="form-control" style="font-size:12px"><option value="">All</option><?php foreach($PLATS as $pk=>$pv): ?><option value="<?=$pk?>"><?=$pv['label']?></option><?php endforeach; ?></select>
              </div>
              <textarea name="tpl_content" class="form-control" rows="3" placeholder="Template content..." required style="font-size:12px"></textarea>
              <button type="submit" class="btn btn-ghost btn-sm" style="margin-top:8px;width:100%;font-size:12px">Save Template</button>
            </form>
          </div>
        </div>
        <?php endif; ?>
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
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px">
    <div style="display:flex;align-items:center;gap:12px">
      <a href="?section=calendar&cy=<?=$cm===1?$cy-1:$cy?>&cm=<?=$cm===1?12:$cm-1?>" class="btn btn-ghost btn-sm">← Prev</a>
      <div style="font-size:16px;font-weight:700;font-family:var(--font-display)"><?=date('F Y',$first)?></div>
      <a href="?section=calendar&cy=<?=$cm===12?$cy+1:$cy?>&cm=<?=$cm===12?1:$cm+1?>" class="btn btn-ghost btn-sm">Next →</a>
    </div>
    <button onclick="smSec('compose')" class="btn btn-primary">✏️ New Post</button>
  </div>
  <div class="cal-grid" style="margin-bottom:4px">
    <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
    <div style="text-align:center;font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;padding:4px"><?=$d?></div>
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

<!-- ════════════════ ACCOUNTS ════════════════ -->
<div id="smsec-accounts" style="display:<?=$section==='accounts'?'block':'none'?>">
  <div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">
    <!-- Account cards -->
    <div>
      <div class="acc-grid">
        <?php foreach($accounts as $a):
          $pc = $PLATS[$a['platform']] ?? $PLATS['other'];
          $ac = count(array_filter($posts, fn($p) => $p['account_id'] == $a['id']));
          $ap = count(array_filter($posts, fn($p) => $p['account_id'] == $a['id'] && $p['status'] === 'published'));
          $has_pass = !empty($a['acc_password']);
        ?>
        <div class="acc-card">
          <!-- Header -->
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:42px;height:42px;border-radius:11px;background:<?=$pc['bg']?>;display:flex;align-items:center;justify-content:center;font-size:17px;font-weight:800;color:<?=$pc['color']?>;flex-shrink:0"><?=$pc['icon']?></div>
            <div style="flex:1;min-width:0">
              <div style="font-size:14px;font-weight:700;color:var(--text)"><?=h($a['name'])?></div>
              <div style="font-size:12px;color:var(--text3)"><?=$a['handle']?h($a['handle']):$pc['label']?></div>
            </div>
            <?php if($a['url']): ?><a href="<?=h($a['url'])?>" target="_blank" class="btn btn-ghost btn-sm">↗ Open</a><?php endif; ?>
          </div>

          <!-- Stats -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
            <div style="background:var(--bg3);border-radius:var(--radius-sm);padding:8px;text-align:center">
              <div style="font-size:17px;font-weight:800;color:<?=$pc['color']?>"><?=$a['followers']?number_format($a['followers']):'—'?></div>
              <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.04em">Followers</div>
            </div>
            <div style="background:var(--bg3);border-radius:var(--radius-sm);padding:8px;text-align:center">
              <div style="font-size:17px;font-weight:800;color:var(--text)"><?=$ac?></div>
              <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.04em"><?=$ap?> published</div>
            </div>
          </div>

          <!-- Password vault (role-based: admin/manager can see) -->
          <?php if(in_array($user['role']??'member', ['admin','manager'])): ?>
          <div>
            <div style="font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px">🔐 Login Credentials</div>
            <?php if($a['acc_email']): ?>
            <div style="font-size:12px;color:var(--text2);margin-bottom:4px;display:flex;gap:6px;align-items:center">
              <span style="color:var(--text3)">📧</span> <?=h($a['acc_email'])?>
            </div>
            <?php endif; ?>
            <?php if($has_pass): ?>
            <div class="acc-pass-reveal">
              <span id="pass-display-<?=$a['id']?>">••••••••</span>
              <div style="display:flex;gap:4px">
                <button type="button" onclick="togglePass(<?=$a['id']?>, <?=htmlspecialchars(json_encode($a['acc_password']),ENT_QUOTES)?>)" title="Show/hide password">👁</button>
                <button type="button" onclick="copyPass(<?=htmlspecialchars(json_encode($a['acc_password']),ENT_QUOTES)?>)" title="Copy password">📋</button>
              </div>
            </div>
            <?php else: ?>
            <div style="font-size:11.5px;color:var(--text3)">No password saved. <a href="?section=accounts&edit_acc=<?=$a['id']?>" style="color:var(--orange)">Add →</a></div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <?php if($a['notes']): ?>
          <div style="font-size:12px;color:var(--text3);font-style:italic"><?=h($a['notes'])?></div>
          <?php endif; ?>

          <!-- Actions -->
          <div style="display:flex;gap:6px">
            <a href="?section=accounts&edit_acc=<?=$a['id']?>" class="btn btn-ghost btn-sm" style="flex:1;text-align:center">✎ Edit</a>
            <form method="POST" onsubmit="return confirm('Remove this account?')" style="display:inline">
              <input type="hidden" name="action" value="delete_account">
              <input type="hidden" name="account_id" value="<?=$a['id']?>">
              <button type="submit" class="btn btn-danger btn-sm btn-icon">🗑</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if(!$accounts): ?>
        <div class="empty-state" style="grid-column:1/-1">
          <div class="icon">📱</div>
          <p>No accounts yet. Add one using the form →</p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Add/Edit form -->
    <div class="sm-panel">
      <div class="sm-panel-head"><?=$ea?'✎ Edit Account':'➕ Add Account'?></div>
      <div class="sm-panel-body">
        <form method="POST">
          <input type="hidden" name="action" value="save_account">
          <?php if($ea): ?><input type="hidden" name="account_id" value="<?=$ea['id']?>"><?php endif; ?>

          <div class="form-group">
            <label class="form-label">Platform</label>
            <select name="platform" class="form-control">
              <?php foreach($PLATS as $pk=>$pv): ?>
              <option value="<?=$pk?>" <?=($ea['platform']??'')===$pk?'selected':''?>><?=$pv['icon'].' '.$pv['label']?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Account Name *</label>
            <input type="text" name="acc_name" class="form-control" required value="<?=h($ea['name']??'')?>" placeholder="e.g. Padak Facebook Page">
          </div>
          <div class="form-group">
            <label class="form-label">Handle</label>
            <input type="text" name="handle" class="form-control" value="<?=h($ea['handle']??'')?>" placeholder="@handle">
          </div>
          <div class="form-group">
            <label class="form-label">Profile URL</label>
            <input type="url" name="url" class="form-control" value="<?=h($ea['url']??'')?>">
          </div>
          <div class="form-group">
            <label class="form-label">Followers</label>
            <input type="number" name="followers" class="form-control" value="<?=$ea['followers']??0?>" min="0">
          </div>

          <!-- Credentials vault -->
          <div style="border-top:1px solid var(--border);padding-top:12px;margin-top:4px;margin-bottom:12px">
            <div style="font-size:11px;font-weight:700;color:var(--orange);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px">🔐 Login Credentials</div>
            <div class="form-group">
              <label class="form-label">Email / Username</label>
              <input type="text" name="acc_email" class="form-control" value="<?=h($ea['acc_email']??'')?>" placeholder="login@example.com or @username">
            </div>
            <div class="form-group">
              <label class="form-label">Password</label>
              <div class="pass-field">
                <input type="password" name="acc_password" id="acc-pass-input" class="form-control" value="<?=h($ea['acc_password']??'')?>" placeholder="Account password" autocomplete="new-password">
                <button type="button" class="pass-toggle" onclick="togglePassInput()">👁</button>
              </div>
              <div style="font-size:10.5px;color:var(--text3);margin-top:4px">⚠ Only visible to Admin &amp; Manager roles</div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2"><?=h($ea['notes']??'')?></textarea>
          </div>
          <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary" style="flex:1">Save Account</button>
            <?php if($ea): ?><a href="?section=accounts" class="btn btn-ghost">Cancel</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
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
    if(n==='compose') onInput();
}

function onInput() {
    var c    = document.getElementById('cmp-content')?.value||'';
    var t    = document.getElementById('cmp-tags')?.value||'';
    var full = c + (t?'\n\n'+t:'');
    var pv   = document.getElementById('cmp-preview');
    if(pv) pv.textContent = full||'Caption preview appears here...';
    var plat  = document.getElementById('cmp-plat')?.value||'';
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
    if(url) pb.innerHTML = '<a href="'+url+'" target="_blank" class="btn btn-primary" style="width:100%;text-align:center;background:'+pc.color+';border-color:'+pc.color+'">'+pc.icon+' Open '+pc.label+' to Post</a>';
    else    pb.innerHTML = '<div style="text-align:center;font-size:12px;color:var(--text3);padding:4px">Select a platform to get the direct post link</div>';
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
        toast('Caption copied! Open the platform and paste it 🚀','success');
    }).catch(function(){
        var ta=document.createElement('textarea');ta.value=full;document.body.appendChild(ta);ta.select();document.execCommand('copy');document.body.removeChild(ta);toast('Copied!','success');
    });
}

function quickCopy(id) {
    var p = smPosts.find(function(x){return x.id==id;});
    if(!p) return;
    var full = (p.content||'')+(p.tags?'\n\n'+p.tags:'');
    if(!full){toast('No content','error');return;}
    navigator.clipboard.writeText(full).then(function(){toast('Caption copied 🚀','success');}).catch(function(){toast('Could not copy','error');});
}

function insCap(c)  { var ta=document.getElementById('cmp-content'); if(!ta){smSec('compose');setTimeout(function(){insCap(c);},300);return;} ta.value=c; onInput(); ta.focus(); toast('Template inserted','success'); }
function insTags(c) { var ti=document.getElementById('cmp-tags');    if(!ti){smSec('compose');setTimeout(function(){insTags(c);},300);return;} ti.value=c; onInput(); ti.focus(); toast('Hashtags inserted','success'); }

function markPublished(id) {
    if(!confirm('Mark as published?'))return;
    var fd=new FormData(); fd.append('action','quick_status'); fd.append('post_id',id); fd.append('status','published');
    fetch('social_media.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){if(d.ok){toast('Published ✅','success');location.reload();}});
}

function delPost(id) {
    if(!confirm('Delete this post?'))return;
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
}
function copyPass(pass) {
    navigator.clipboard.writeText(pass).then(function(){toast('Password copied 🔑','success');}).catch(function(){toast('Could not copy','error');});
}
function togglePassInput() {
    var inp = document.getElementById('acc-pass-input');
    if(!inp) return;
    inp.type = inp.type==='password' ? 'text' : 'password';
}

document.addEventListener('DOMContentLoaded', function(){
    onInput();
    onPlatChange(document.getElementById('cmp-plat')?.value||'');
});
</script>
<?php renderLayoutEnd(); ?>