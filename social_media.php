<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];
mysqli_report(MYSQLI_REPORT_OFF);

// ── ENSURE password columns exist on social_accounts ──────────────────────
@$db->query("ALTER TABLE social_accounts ADD COLUMN IF NOT EXISTS acc_password VARCHAR(500) NULL DEFAULT NULL");
@$db->query("ALTER TABLE social_accounts ADD COLUMN IF NOT EXISTS acc_email VARCHAR(300) NULL DEFAULT NULL");
@$db->query("ALTER TABLE social_accounts ADD COLUMN IF NOT EXISTS acc_role ENUM('admin','editor','viewer') NOT NULL DEFAULT 'admin'");
@$db->query("ALTER TABLE social_accounts ADD COLUMN IF NOT EXISTS acc_2fa VARCHAR(200) NULL DEFAULT NULL");

ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Account CRUD ──────────────────────────────────────────────────────
    if ($action === 'save_account') {
        $platform  = $_POST['platform']  ?? 'other';
        $name      = trim($_POST['acc_name']    ?? '');
        $handle    = trim($_POST['handle']      ?? '');
        $url       = trim($_POST['url']         ?? '');
        $followers = (int)($_POST['followers']  ?? 0);
        $notes     = trim($_POST['notes']       ?? '');
        $email     = trim($_POST['acc_email']   ?? '');
        $password  = trim($_POST['acc_password']?? '');
        $role      = in_array($_POST['acc_role']??'',['admin','editor','viewer']) ? $_POST['acc_role'] : 'admin';
        $twofa     = trim($_POST['acc_2fa']     ?? '');
        $aid       = (int)($_POST['account_id'] ?? 0);
        if ($name) {
            if ($aid) {
                $s = $db->prepare("UPDATE social_accounts SET platform=?,name=?,handle=?,url=?,followers=?,notes=?,acc_email=?,acc_password=?,acc_role=?,acc_2fa=? WHERE id=$aid");
                $s->bind_param("ssssississs", $platform,$name,$handle,$url,$followers,$notes,$email,$password,$role,$twofa);
            } else {
                $s = $db->prepare("INSERT INTO social_accounts (platform,name,handle,url,followers,notes,acc_email,acc_password,acc_role,acc_2fa,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $s->bind_param("ssssississsi", $platform,$name,$handle,$url,$followers,$notes,$email,$password,$role,$twofa,$uid);
            }
            $s->execute();
            flash('Account saved.','success');
        }
        ob_end_clean(); header('Location: social_media.php?section=accounts'); exit;
    }

    if ($action === 'delete_account') {
        $db->query("UPDATE social_accounts SET is_active=0 WHERE id=".(int)($_POST['account_id']??0));
        flash('Account removed.','success');
        ob_end_clean(); header('Location: social_media.php?section=accounts'); exit;
    }

    // ── Post CRUD ─────────────────────────────────────────────────────────
    if ($action === 'save_post') {
        $post_id    = (int)($_POST['post_id']    ?? 0);
        $account_id = (int)($_POST['account_id'] ?? 0) ?: null;
        $title      = trim($_POST['title']        ?? '');
        $content    = trim($_POST['content']      ?? '');
        $platform   = $_POST['platform']          ?? '';
        $status     = $_POST['status']            ?? 'idea';
        $post_type  = $_POST['post_type']         ?? 'post';
        $tags       = trim($_POST['tags']         ?? '');
        $cap_notes  = trim($_POST['caption_notes']?? '');
        $sched      = $_POST['scheduled_at']       ?: null;
        $proj_id    = (int)($_POST['project_id']  ?? 0) ?: null;
        $assigned   = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $back       = $_POST['back_to']            ?? 'pipeline';
        if ($title) {
            if ($post_id) {
                $s = $db->prepare("UPDATE social_posts SET account_id=?,title=?,content=?,platform=?,status=?,post_type=?,tags=?,caption_notes=?,scheduled_at=?,likes=0,comments=0,reach=0,project_id=?,assigned_to=? WHERE id=$post_id");
                $s->bind_param("issssssssii", $account_id,$title,$content,$platform,$status,$post_type,$tags,$cap_notes,$sched,$proj_id,$assigned);
                if ($status==='published') $db->query("UPDATE social_posts SET published_at=NOW() WHERE id=$post_id AND published_at IS NULL");
            } else {
                $pub_at = $status==='published' ? date('Y-m-d H:i:s') : null;
                $s = $db->prepare("INSERT INTO social_posts (account_id,title,content,platform,status,post_type,tags,caption_notes,scheduled_at,published_at,likes,comments,reach,project_id,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,0,0,0,?,?,?)");
                $s->bind_param("isssssssssiii", $account_id,$title,$content,$platform,$status,$post_type,$tags,$cap_notes,$sched,$pub_at,$proj_id,$assigned,$uid);
            }
            $s->execute();
            logActivity('social '.$status, $title, $db->insert_id);
            flash('Post saved.','success');
        }
        ob_end_clean(); header("Location: social_media.php?section=$back"); exit;
    }

    if ($action === 'quick_status') {
        $pid = (int)($_POST['post_id'] ?? 0);
        $st  = $_POST['status'] ?? 'draft';
        if (in_array($st,['idea','draft','scheduled','published','cancelled']))
            $db->query("UPDATE social_posts SET status='$st'".($st==='published'?",published_at=NOW()":'')." WHERE id=$pid");
        ob_end_clean(); header('Content-Type: application/json'); echo json_encode(['ok'=>true]); exit;
    }

    if ($action === 'delete_post') {
        $db->query("DELETE FROM social_posts WHERE id=".(int)($_POST['post_id']??0));
        flash('Post deleted.','success');
        ob_end_clean(); header('Location: social_media.php?section=pipeline'); exit;
    }
}
ob_end_clean();

// ── SECTION ROUTING ───────────────────────────────────────────────────────
$section  = (string)($_GET['section'] ?? 'dashboard');
$edit_pid = (int)($_GET['edit']     ?? 0);
$edit_aid = (int)($_GET['edit_acc'] ?? 0);
if ($edit_pid) $section = 'compose';
if ($edit_aid) $section = 'accounts';

// ── DATA LOAD ─────────────────────────────────────────────────────────────
$accounts  = @$db->query("SELECT * FROM social_accounts WHERE is_active=1 ORDER BY platform,name")->fetch_all(MYSQLI_ASSOC) ?: [];
$posts     = @$db->query("SELECT sp.*,sa.name acc_name,sa.platform acc_platform,u.name assigned_name
    FROM social_posts sp
    LEFT JOIN social_accounts sa ON sa.id=sp.account_id
    LEFT JOIN users u ON u.id=sp.assigned_to
    ORDER BY FIELD(sp.status,'scheduled','draft','idea','published','cancelled'),sp.scheduled_at ASC,sp.created_at DESC
    LIMIT 300")->fetch_all(MYSQLI_ASSOC) ?: [];
$projects  = @$db->query("SELECT id,title FROM projects WHERE status='active' ORDER BY title")->fetch_all(MYSQLI_ASSOC) ?: [];
$team      = @$db->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC) ?: [];
$ep        = $edit_pid ? @$db->query("SELECT * FROM social_posts WHERE id=$edit_pid")->fetch_assoc() : null;
$ea        = $edit_aid ? @$db->query("SELECT * FROM social_accounts WHERE id=$edit_aid")->fetch_assoc() : null;

$pub_count   = count(array_filter($posts, fn($p)=>$p['status']==='published'));
$sched_count = count(array_filter($posts, fn($p)=>$p['status']==='scheduled'));
$draft_count = count(array_filter($posts, fn($p)=>$p['status']==='draft'));
$idea_count  = count(array_filter($posts, fn($p)=>$p['status']==='idea'));

// ── CONFIG ────────────────────────────────────────────────────────────────
$PLATS = [
    'facebook'  => ['label'=>'Facebook',   'color'=>'#1877f2','icon'=>'FB','bg'=>'rgba(24,119,242,.12)'],
    'instagram' => ['label'=>'Instagram',  'color'=>'#e1306c','icon'=>'IG','bg'=>'rgba(225,48,108,.12)'],
    'twitter'   => ['label'=>'Twitter/X',  'color'=>'#1da1f2','icon'=>'X', 'bg'=>'rgba(29,161,242,.12)'],
    'linkedin'  => ['label'=>'LinkedIn',   'color'=>'#0077b5','icon'=>'LI','bg'=>'rgba(0,119,181,.12)'],
    'youtube'   => ['label'=>'YouTube',    'color'=>'#ff0000','icon'=>'YT','bg'=>'rgba(255,0,0,.12)'],
    'tiktok'    => ['label'=>'TikTok',     'color'=>'#69c9d0','icon'=>'TT','bg'=>'rgba(105,201,208,.12)'],
    'other'     => ['label'=>'Other',      'color'=>'#64748b','icon'=>'●', 'bg'=>'rgba(100,116,139,.12)'],
];
$STATUS = [
    'idea'      => ['label'=>'💡 Idea',       'color'=>'#94a3b8','bg'=>'rgba(148,163,184,.12)'],
    'draft'     => ['label'=>'📝 Draft',      'color'=>'#f59e0b','bg'=>'rgba(245,158,11,.12)'],
    'scheduled' => ['label'=>'📅 Scheduled',  'color'=>'#6366f1','bg'=>'rgba(99,102,241,.12)'],
    'published' => ['label'=>'✅ Published',  'color'=>'#10b981','bg'=>'rgba(16,185,129,.12)'],
    'cancelled' => ['label'=>'❌ Cancelled',  'color'=>'#ef4444','bg'=>'rgba(239,68,68,.12)'],
];
$TYPE_ICONS = ['post'=>'📄','story'=>'⭕','reel'=>'🎬','video'=>'▶️','article'=>'📰'];
$CLIMITS    = ['twitter'=>280,'instagram'=>2200,'facebook'=>63206,'linkedin'=>3000,'tiktok'=>2200,'youtube'=>5000,'other'=>5000];
$PU         = ['facebook'=>'https://www.facebook.com/','instagram'=>'https://www.instagram.com/create/story','twitter'=>'https://twitter.com/compose/tweet','linkedin'=>'https://www.linkedin.com/sharing/share-offsite/','youtube'=>'https://studio.youtube.com/','tiktok'=>'https://www.tiktok.com/upload'];
$ROLES      = ['admin'=>'Admin / Owner','editor'=>'Editor','viewer'=>'Viewer'];

function pb($pl,$P){$pc=$P[$pl]??$P['other'];return "<span style='display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700;background:{$pc['bg']};color:{$pc['color']}'>{$pc['icon']} {$pc['label']}</span>";}
function sb($s,$S){$sc=$S[$s]??$S['idea'];return "<span style='display:inline-flex;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700;background:{$sc['bg']};color:{$sc['color']}'>{$sc['label']}</span>";}

renderLayout('Social Media','social_media');
?>

<style>
/* ── THEME TOKENS ── */
:root{
  --sm-radius:12px;
  --sm-radius-sm:8px;
}

/* ── TOP STATS ── */
.sm-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}
.sm-stat{background:var(--bg2);border:1px solid var(--border);border-radius:var(--sm-radius);padding:16px 18px;display:flex;align-items:center;gap:14px;transition:box-shadow .15s}
.sm-stat:hover{box-shadow:0 4px 16px rgba(0,0,0,.08)}
.sm-stat-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.sm-stat-val{font-size:22px;font-weight:800;font-family:var(--font-display);line-height:1}
.sm-stat-lbl{font-size:10.5px;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-top:2px}

/* ── TABS ── */
.sm-tabs{display:flex;gap:2px;border-bottom:2px solid var(--border);margin-bottom:24px;overflow-x:auto}
.sm-tab{padding:10px 18px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;border:none;background:none;border-bottom:2px solid transparent;margin-bottom:-2px;white-space:nowrap;transition:color .15s,border-color .15s}
.sm-tab.active{color:var(--orange);border-bottom-color:var(--orange)}
.sm-tab:hover:not(.active){color:var(--text);border-bottom-color:var(--border2)}

/* ── PANEL ── */
.sm-panel{background:var(--bg2);border:1px solid var(--border);border-radius:var(--sm-radius);overflow:hidden}
.sm-panel-head{padding:13px 16px;border-bottom:1px solid var(--border);background:var(--bg3);font-size:13px;font-weight:700;color:var(--text);display:flex;align-items:center;justify-content:space-between;gap:10px}
.sm-panel-body{padding:16px}

/* ── KANBAN ── */
.sm-kanban{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;align-items:start}
.sm-col-head{padding:9px 12px;border-radius:var(--sm-radius-sm);margin-bottom:8px;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:space-between}
.sm-post-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--sm-radius);overflow:hidden;margin-bottom:8px;transition:transform .15s,box-shadow .15s;cursor:pointer}
.sm-post-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.1)}
.sm-post-title{font-size:13px;font-weight:700;color:var(--text);margin-bottom:4px;line-height:1.4}
.sm-post-preview{font-size:11.5px;color:var(--text3);line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:8px}
.sm-post-meta{font-size:11px;color:var(--text3);display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.sm-post-actions{display:flex;gap:0;border-top:1px solid var(--border);background:var(--bg3)}
.sm-post-actions button,.sm-post-actions a{flex:1;padding:6px 4px;font-size:11px;font-weight:600;border:none;background:none;cursor:pointer;color:var(--text3);transition:all .12s;text-align:center;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:3px}
.sm-post-actions button:hover,.sm-post-actions a:hover{background:var(--bg4);color:var(--text)}

/* ── COMPOSE ── */
.compose-grid{display:grid;grid-template-columns:1fr 300px;gap:14px;align-items:start}
.char-counter{font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;margin-left:auto}
.char-ok{background:rgba(16,185,129,.12);color:#10b981}
.char-warn{background:rgba(245,158,11,.12);color:#f59e0b}
.char-over{background:rgba(239,68,68,.12);color:#ef4444}
.cap-preview{background:var(--bg3);border:1px solid var(--border);border-radius:var(--sm-radius-sm);padding:12px 14px;margin-top:10px;min-height:64px;font-size:13.5px;line-height:1.7;color:var(--text2);white-space:pre-wrap;word-break:break-word;font-family:Arial,sans-serif}

/* ── CALENDAR ── */
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:3px}
.cal-cell{background:var(--bg3);border:1px solid var(--border);border-radius:var(--sm-radius-sm);min-height:84px;padding:7px}
.cal-cell.today{border-color:var(--orange);background:var(--orange-bg)}
.cal-num{font-size:11px;font-weight:700;color:var(--text3);margin-bottom:4px}
.cal-cell.today .cal-num{color:var(--orange)}
.cal-chip{font-size:10px;font-weight:600;padding:2px 6px;border-radius:4px;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block}

/* ── ACCOUNTS ── */
.acc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.acc-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--sm-radius);padding:16px;display:flex;flex-direction:column;gap:12px;transition:transform .15s,box-shadow .15s}
.acc-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.08)}
.pwd-field{position:relative}
.pwd-field input{padding-right:34px}
.pwd-field button{position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text3);font-size:14px;padding:2px}
.pwd-field button:hover{color:var(--text)}
.acc-cred-row{display:flex;align-items:center;gap:8px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--sm-radius-sm);padding:8px 10px}
.acc-cred-lbl{font-size:10.5px;color:var(--text3);text-transform:uppercase;letter-spacing:.04em;min-width:70px}
.acc-cred-val{font-size:12.5px;color:var(--text);font-family:monospace;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.role-pill{display:inline-flex;align-items:center;padding:2px 9px;border-radius:99px;font-size:11px;font-weight:700}
.role-admin{background:rgba(249,115,22,.12);color:var(--orange)}
.role-editor{background:rgba(99,102,241,.12);color:#6366f1}
.role-viewer{background:rgba(100,116,139,.12);color:#64748b}

/* ── DASHBOARD ── */
.dash-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
.dash-status-row{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}
.dash-status-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--sm-radius);padding:14px;cursor:pointer;transition:border-color .15s,transform .15s}
.dash-status-card:hover{transform:translateY(-1px)}

/* ── RESPONSIVE ── */
@media(max-width:1200px){.sm-stats{grid-template-columns:repeat(2,1fr)}.sm-kanban{grid-template-columns:repeat(3,1fr)}.acc-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:900px){.sm-kanban{grid-template-columns:1fr 1fr}.compose-grid{grid-template-columns:1fr}.dash-grid{grid-template-columns:1fr}.acc-grid{grid-template-columns:1fr}}
@media(max-width:600px){.sm-kanban{grid-template-columns:1fr}.sm-stats{grid-template-columns:repeat(2,1fr)}.dash-status-row{grid-template-columns:repeat(2,1fr)}}
</style>

<!-- ── TOP STATS ── -->
<div class="sm-stats">
  <?php
  $stats_cfg = [
    ['icon'=>'📱','val'=>count($accounts),'lbl'=>'Accounts',  'color'=>'var(--orange)','bg'=>'rgba(249,115,22,.12)'],
    ['icon'=>'📝','val'=>$draft_count,    'lbl'=>'Drafts',    'color'=>'#f59e0b',      'bg'=>'rgba(245,158,11,.12)'],
    ['icon'=>'📅','val'=>$sched_count,    'lbl'=>'Scheduled', 'color'=>'#6366f1',      'bg'=>'rgba(99,102,241,.12)'],
    ['icon'=>'✅','val'=>$pub_count,      'lbl'=>'Published', 'color'=>'#10b981',      'bg'=>'rgba(16,185,129,.12)'],
  ];
  foreach ($stats_cfg as $s): ?>
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
  $tabs = [
    ['dashboard','📊 Dashboard'],
    ['pipeline', '📋 Pipeline'],
    ['compose',  '✏️ Compose'],
    ['calendar', '📅 Calendar'],
    ['accounts', '🔐 Accounts'],
  ];
  foreach ($tabs as [$sk,$sl]): ?>
  <button class="sm-tab <?=$section===$sk?'active':''?>" onclick="smSec('<?=$sk?>')"><?=$sl?></button>
  <?php endforeach; ?>
</div>

<!-- ════════════════════════════════════════════════
     DASHBOARD
════════════════════════════════════════════════ -->
<div id="smsec-dashboard" style="display:<?=$section==='dashboard'?'block':'none'?>">

  <div class="dash-grid">

    <!-- Upcoming Posts -->
    <div class="sm-panel">
      <div class="sm-panel-head">
        📅 Upcoming &amp; Scheduled
        <button onclick="smSec('compose')" class="btn btn-primary btn-sm">✏️ New Post</button>
      </div>
      <div style="padding:0">
        <?php
        $upcoming = array_slice(array_values(array_filter($posts, fn($p)=>in_array($p['status'],['scheduled','draft']))), 0, 8);
        if (!$upcoming): ?>
        <div style="padding:32px;text-align:center;color:var(--text3);font-size:13px">
          <div style="font-size:28px;margin-bottom:8px">📅</div>
          No upcoming posts. <button onclick="smSec('compose')" style="background:none;border:none;color:var(--orange);cursor:pointer;font-weight:600;font-size:13px">Create one →</button>
        </div>
        <?php else: foreach ($upcoming as $p):
            $pc  = $PLATS[$p['platform'] ?? 'other'] ?? $PLATS['other'];
            $late = $p['status']==='scheduled' && $p['scheduled_at'] && strtotime($p['scheduled_at'])<time();
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:11px 14px;border-bottom:1px solid var(--border);<?=$late?'background:rgba(239,68,68,.03)':''?>">
          <div style="width:3px;height:36px;border-radius:99px;background:<?=$pc['color']?>;flex-shrink:0"></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=h($p['title'])?></div>
            <div style="font-size:11px;color:var(--text3);margin-top:2px"><?=$pc['icon'].' '.$pc['label']?><?=$p['assigned_name']?' · 👤 '.h($p['assigned_name']):''?></div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="font-size:11px;font-weight:600;color:<?=$late?'#ef4444':'var(--text3)'?>"><?=$late?'⚠ Overdue':($p['scheduled_at']?date('M j g:ia',strtotime($p['scheduled_at'])):'—')?></div>
            <div style="margin-top:3px"><?=sb($p['status'],$STATUS)?></div>
          </div>
          <button onclick="location.href='social_media.php?edit=<?=$p['id']?>'" class="btn btn-ghost btn-sm btn-icon" style="flex-shrink:0">✎</button>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- Accounts Overview -->
    <div class="sm-panel">
      <div class="sm-panel-head">🏢 Accounts</div>
      <div style="padding:0">
        <?php if (!$accounts): ?>
        <div style="padding:20px;text-align:center;color:var(--text3);font-size:12.5px">No accounts yet. <button onclick="smSec('accounts')" style="background:none;border:none;color:var(--orange);cursor:pointer;font-weight:600">Add →</button></div>
        <?php else: foreach ($accounts as $a):
            $pc = $PLATS[$a['platform']] ?? $PLATS['other'];
            $ac = count(array_filter($posts, fn($p)=>$p['account_id']==$a['id']));
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border)">
          <div style="width:36px;height:36px;border-radius:9px;background:<?=$pc['bg']?>;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:<?=$pc['color']?>;flex-shrink:0"><?=$pc['icon']?></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600;color:var(--text)"><?=h($a['name'])?></div>
            <div style="font-size:11px;color:var(--text3)"><?=$a['handle']?h($a['handle']):$pc['label']?> · <?=$ac?> posts<?php if($a['acc_role']??''): ?> · <span class="role-pill role-<?=h($a['acc_role'])?>" style="font-size:10px"><?=h($ROLES[$a['acc_role']]??$a['acc_role'])?></span><?php endif; ?></div>
          </div>
          <?php if($a['url']): ?><a href="<?=h($a['url'])?>" target="_blank" style="color:var(--orange);font-size:15px;text-decoration:none">↗</a><?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
        <div style="padding:10px 14px"><button onclick="smSec('accounts')" class="btn btn-ghost btn-sm" style="width:100%">Manage Accounts →</button></div>
      </div>
    </div>
  </div>

  <!-- Status summary -->
  <div class="dash-status-row">
    <?php foreach ($STATUS as $sk=>$sv):
      $cnt = count(array_filter($posts, fn($p)=>$p['status']===$sk)); ?>
    <div class="dash-status-card" onclick="smSec('pipeline')" onmouseover="this.style.borderColor='<?=$sv['color']?>'" onmouseout="this.style.borderColor='var(--border)'">
      <div style="font-size:24px;font-weight:800;color:<?=$sv['color']?>"><?=$cnt?></div>
      <div style="font-size:12px;color:var(--text3);margin-top:3px"><?=$sv['label']?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ════════════════════════════════════════════════
     PIPELINE (KANBAN)
════════════════════════════════════════════════ -->
<div id="smsec-pipeline" style="display:<?=$section==='pipeline'?'block':'none'?>">
  <div style="display:flex;justify-content:flex-end;margin-bottom:14px">
    <button onclick="smSec('compose')" class="btn btn-primary">✏️ New Post</button>
  </div>
  <div class="sm-kanban">
  <?php foreach (['idea','draft','scheduled','published','cancelled'] as $st):
    $sc     = $STATUS[$st];
    $stposts = array_filter($posts, fn($p)=>$p['status']===$st); ?>
    <div>
      <div class="sm-col-head" style="background:<?=$sc['bg']?>;color:<?=$sc['color']?>">
        <?=$sc['label']?>
        <span style="background:rgba(0,0,0,.08);padding:2px 7px;border-radius:99px;font-size:10px"><?=count($stposts)?></span>
      </div>
      <?php if (!$stposts): ?><div style="text-align:center;padding:18px 8px;color:var(--text3);font-size:12px;background:var(--bg3);border-radius:var(--sm-radius-sm)">Empty</div><?php endif; ?>
      <?php foreach ($stposts as $p):
        $pc   = $PLATS[$p['platform'] ?? ($p['acc_platform'] ?? 'other')] ?? $PLATS['other'];
        $late = ($st==='scheduled' && $p['scheduled_at'] && strtotime($p['scheduled_at'])<time());
      ?>
      <div class="sm-post-card" onclick="location.href='social_media.php?edit=<?=$p['id']?>'">
        <div style="height:3px;background:<?=$pc['color']?>"></div>
        <div style="padding:10px 12px">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:5px;margin-bottom:4px">
            <div class="sm-post-title"><?=h($p['title'])?></div>
            <span style="font-size:13px;flex-shrink:0"><?=$TYPE_ICONS[$p['post_type']??'post']??'📄'?></span>
          </div>
          <?php if($p['content']): ?><div class="sm-post-preview"><?=h(mb_substr($p['content'],0,80))?></div><?php endif; ?>
          <div class="sm-post-meta">
            <span style="color:<?=$pc['color']?>;font-weight:700;font-size:11px"><?=$pc['icon'].' '.$pc['label']?></span>
            <?php if($late): ?><span style="color:#ef4444;font-weight:700">⚠</span><?php elseif($p['scheduled_at']): ?><span>📅 <?=date('M j',strtotime($p['scheduled_at']))?></span><?php endif; ?>
            <?php if($p['assigned_name']): ?><span>· <?=h($p['assigned_name'])?></span><?php endif; ?>
          </div>
        </div>
        <div class="sm-post-actions" onclick="event.stopPropagation()">
          <button onclick="location.href='social_media.php?edit=<?=$p['id']?>'" title="Edit">✎ Edit</button>
          <button onclick="quickCopy(<?=$p['id']?>)" title="Copy caption">📋 Copy</button>
          <?php if($st!=='published'): ?><button onclick="markPublished(<?=$p['id']?>)" style="color:#10b981" title="Mark published">✅</button><?php endif; ?>
          <?php if(isset($PU[$p['platform']??''])): ?><a href="<?=h($PU[$p['platform']])?>" target="_blank" style="color:<?=$pc['color']?>" title="Open platform" onclick="event.stopPropagation()">↗</a><?php endif; ?>
          <button onclick="delPost(<?=$p['id']?>)" style="color:var(--red)" title="Delete">🗑</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
  </div>
</div>

<!-- ════════════════════════════════════════════════
     COMPOSE
════════════════════════════════════════════════ -->
<div id="smsec-compose" style="display:<?=$section==='compose'?'block':'none'?>">
  <form method="POST" id="cmp-form">
    <input type="hidden" name="action" value="save_post">
    <input type="hidden" name="back_to" value="pipeline">
    <?php if($ep): ?><input type="hidden" name="post_id" value="<?=$ep['id']?>"><?php endif; ?>

    <div class="compose-grid">

      <!-- LEFT: Content -->
      <div>

        <!-- Title + platform bar -->
        <div class="sm-panel" style="margin-bottom:12px">
          <div class="sm-panel-head">
            ✏️ <?=$ep ? 'Edit: '.h(mb_substr($ep['title'],0,40)) : 'New Post'?>
            <div style="display:flex;gap:8px;align-items:center">
              <select name="platform" id="cmp-plat" class="form-control" style="width:145px;font-size:12.5px;height:32px;padding:0 8px" onchange="onPlatChange(this.value)">
                <option value="">— Platform —</option>
                <?php foreach ($PLATS as $pk=>$pv): ?><option value="<?=$pk?>" <?=($ep['platform']??'')===$pk?'selected':''?>><?=$pv['icon'].' '.$pv['label']?></option><?php endforeach; ?>
              </select>
              <select name="post_type" class="form-control" style="width:115px;font-size:12.5px;height:32px;padding:0 8px">
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
              <label class="form-label" style="display:flex;align-items:center;gap:6px">
                Caption / Content
                <span class="char-counter char-ok" id="char-cnt">0</span>
              </label>
              <textarea name="content" id="cmp-content" class="form-control" rows="8" placeholder="Write your caption here..." oninput="onInput()" style="font-family:Arial,sans-serif;font-size:13.5px;line-height:1.65;resize:vertical"><?=h($ep['content']??'')?></textarea>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
              <span style="font-size:11.5px;color:var(--text3)">Preview</span>
              <button type="button" onclick="copyCaption()" class="btn btn-ghost btn-sm" style="font-size:11.5px">📋 Copy Caption</button>
            </div>
            <div class="cap-preview" id="cmp-preview">Caption preview appears here...</div>
          </div>
        </div>

        <!-- Hashtags & Notes -->
        <div class="sm-panel">
          <div class="sm-panel-head">🏷 Hashtags &amp; Notes</div>
          <div class="sm-panel-body">
            <div class="form-group">
              <label class="form-label">Hashtags</label>
              <input type="text" name="tags" id="cmp-tags" class="form-control" placeholder="#marketing #business #digital" value="<?=h($ep['tags']??'')?>" oninput="onInput()">
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">Internal Notes <span style="font-size:11px;color:var(--text3)">(not published)</span></label>
              <textarea name="caption_notes" class="form-control" rows="2" placeholder="e.g. Use product photo, post at 7pm, tag @partner"><?=h($ep['caption_notes']??'')?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT: Settings sidebar -->
      <div>

        <!-- Settings -->
        <div class="sm-panel" style="margin-bottom:12px">
          <div class="sm-panel-head">⚙ Settings</div>
          <div class="sm-panel-body">
            <div class="form-group">
              <label class="form-label">Account</label>
              <select name="account_id" class="form-control" onchange="syncAccPlat(this)">
                <option value="">— Select account —</option>
                <?php foreach ($accounts as $a): $pc=$PLATS[$a['platform']]??$PLATS['other']; ?>
                <option value="<?=$a['id']?>" data-platform="<?=$a['platform']?>" <?=($ep['account_id']??'')==$a['id']?'selected':''?>><?=$pc['icon'].' '.h($a['name'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                  <?php foreach ($STATUS as $sv=>$sc): ?><option value="<?=$sv?>" <?=($ep['status']??'idea')===$sv?'selected':''?>><?=$sc['label']?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Assign To</label>
                <select name="assigned_to" class="form-control">
                  <option value="">Anyone</option>
                  <?php foreach ($team as $tm): ?><option value="<?=$tm['id']?>" <?=($ep['assigned_to']??'')==$tm['id']?'selected':''?>><?=h($tm['name'])?></option><?php endforeach; ?>
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
                <?php foreach ($projects as $pr): ?><option value="<?=$pr['id']?>" <?=($ep['project_id']??'')==$pr['id']?'selected':''?>><?=h($pr['title'])?></option><?php endforeach; ?>
              </select>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <button type="submit" name="status" value="draft" class="btn btn-ghost" style="flex:1;font-size:12px">💾 Draft</button>
              <button type="submit" name="status" value="scheduled" class="btn btn-ghost" style="flex:1;color:#6366f1;border-color:#6366f1;font-size:12px">📅 Schedule</button>
              <button type="submit" name="status" value="published" class="btn btn-primary" style="flex:1;background:#10b981;border-color:#10b981;font-size:12px">✅ Publish</button>
            </div>
          </div>
        </div>

        <!-- Open on platform -->
        <div class="sm-panel" id="open-plat-panel">
          <div class="sm-panel-body" style="padding:12px">
            <div id="open-plat-btn">
              <div style="padding:6px;text-align:center;font-size:12px;color:var(--text3)">Select a platform to get the direct post link</div>
            </div>
            <div style="font-size:11px;color:var(--text3);text-align:center;margin-top:6px">Copy caption first, then paste on the platform</div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- ════════════════════════════════════════════════
     CALENDAR
════════════════════════════════════════════════ -->
<div id="smsec-calendar" style="display:<?=$section==='calendar'?'block':'none'?>">
  <?php
  $cy    = (int)($_GET['cy'] ?? date('Y'));
  $cm    = (int)($_GET['cm'] ?? date('n'));
  $first = mktime(0,0,0,$cm,1,$cy);
  $days  = (int)date('t',$first);
  $start = (int)date('N',$first);
  $pbd   = [];
  foreach ($posts as $p) {
      $dt = $p['scheduled_at'] ?: $p['created_at'];
      if (!$dt) continue;
      if (date('Y-n',strtotime($dt)) === "$cy-$cm") $pbd[(int)date('j',strtotime($dt))][] = $p;
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
    <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
    <div style="text-align:center;font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;padding:5px"><?=$d?></div>
    <?php endforeach; ?>
  </div>
  <div class="cal-grid">
    <?php
    for ($i=1;$i<$start;$i++) echo '<div></div>';
    for ($d=1;$d<=$days;$d++) {
        $today = ($d==date('j') && $cm==date('n') && $cy==date('Y'));
        echo '<div class="cal-cell'.($today?' today':'').'">';
        echo '<div class="cal-num">'.$d.'</div>';
        if (!empty($pbd[$d])) foreach ($pbd[$d] as $p) {
            $pc = $PLATS[$p['platform']??'other'] ?? $PLATS['other'];
            echo '<div class="cal-chip" onclick="location.href=\'social_media.php?edit='.$p['id'].'\'" style="background:'.$pc['color'].'22;color:'.$pc['color'].'" title="'.h($p['title']).'">'.h(mb_substr($p['title'],0,14)).'</div>';
        }
        echo '</div>';
    }
    ?>
  </div>
</div>

<!-- ════════════════════════════════════════════════
     ACCOUNTS  (with Password Manager)
════════════════════════════════════════════════ -->
<div id="smsec-accounts" style="display:<?=$section==='accounts'?'block':'none'?>">
  <div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">

    <!-- Account cards -->
    <div>
      <div class="acc-grid">
        <?php foreach ($accounts as $a):
          $pc  = $PLATS[$a['platform']] ?? $PLATS['other'];
          $ac  = count(array_filter($posts, fn($p)=>$p['account_id']==$a['id']));
          $ap  = count(array_filter($posts, fn($p)=>$p['account_id']==$a['id'] && $p['status']==='published'));
          $role = $a['acc_role'] ?? 'admin';
        ?>
        <div class="acc-card">

          <!-- Header -->
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:44px;height:44px;border-radius:12px;background:<?=$pc['bg']?>;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:<?=$pc['color']?>;flex-shrink:0"><?=$pc['icon']?></div>
            <div style="flex:1;min-width:0">
              <div style="font-size:14px;font-weight:700;color:var(--text)"><?=h($a['name'])?></div>
              <div style="font-size:11.5px;color:var(--text3)"><?=$a['handle']?h($a['handle']):'@—'?></div>
            </div>
            <span class="role-pill role-<?=$role?>"><?=h($ROLES[$role]??$role)?></span>
          </div>

          <!-- Stats -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div style="background:var(--bg3);border-radius:var(--sm-radius-sm);padding:8px 10px;text-align:center">
              <div style="font-size:17px;font-weight:800;color:<?=$pc['color']?>"><?=$a['followers']?number_format($a['followers']):'—'?></div>
              <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.04em">Followers</div>
            </div>
            <div style="background:var(--bg3);border-radius:var(--sm-radius-sm);padding:8px 10px;text-align:center">
              <div style="font-size:17px;font-weight:800;color:var(--text)"><?=$ac?></div>
              <div style="font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.04em"><?=$ap?> Published</div>
            </div>
          </div>

          <!-- Credentials (masked) -->
          <?php if ($a['acc_email'] ?? ''): ?>
          <div class="acc-cred-row">
            <span class="acc-cred-lbl">📧 Email</span>
            <span class="acc-cred-val"><?=h($a['acc_email'])?></span>
            <button type="button" onclick="navigator.clipboard.writeText('<?=addslashes($a['acc_email'])?>').then(()=>toast('Copied!','success'))" class="btn btn-ghost btn-sm btn-icon" title="Copy">📋</button>
          </div>
          <?php endif; ?>
          <?php if ($a['acc_password'] ?? ''): ?>
          <div class="acc-cred-row">
            <span class="acc-cred-lbl">🔑 Password</span>
            <span class="acc-cred-val" id="pwdval-<?=$a['id']?>" style="filter:blur(4px);cursor:pointer" onclick="togglePwd(<?=$a['id']?>,'<?=addslashes($a['acc_password'])?>')" title="Click to reveal">••••••••</span>
            <button type="button" onclick="togglePwd(<?=$a['id']?>,'<?=addslashes($a['acc_password'])?>')" class="btn btn-ghost btn-sm btn-icon" title="Show/Hide">👁</button>
            <button type="button" onclick="navigator.clipboard.writeText('<?=addslashes($a['acc_password'])?>').then(()=>toast('Password copied!','success'))" class="btn btn-ghost btn-sm btn-icon" title="Copy">📋</button>
          </div>
          <?php endif; ?>
          <?php if ($a['acc_2fa'] ?? ''): ?>
          <div class="acc-cred-row">
            <span class="acc-cred-lbl">🛡 2FA</span>
            <span class="acc-cred-val" style="font-size:11.5px"><?=h($a['acc_2fa'])?></span>
          </div>
          <?php endif; ?>
          <?php if ($a['notes']??''): ?>
          <div style="font-size:12px;color:var(--text3);background:var(--bg3);border-radius:var(--sm-radius-sm);padding:7px 10px"><?=h($a['notes'])?></div>
          <?php endif; ?>

          <!-- Actions -->
          <div style="display:flex;gap:6px">
            <?php if($a['url']): ?><a href="<?=h($a['url'])?>" target="_blank" class="btn btn-ghost btn-sm" style="flex:1;text-align:center">↗ Open</a><?php endif; ?>
            <a href="?section=accounts&edit_acc=<?=$a['id']?>" class="btn btn-ghost btn-sm">✎ Edit</a>
            <form method="POST" onsubmit="return confirm('Remove this account?')" style="display:inline">
              <input type="hidden" name="action" value="delete_account">
              <input type="hidden" name="account_id" value="<?=$a['id']?>">
              <button type="submit" class="btn btn-danger btn-sm btn-icon">🗑</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$accounts): ?>
        <div class="empty-state" style="grid-column:1/-1"><div class="icon">📱</div><p>No accounts yet. Add one →</p></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Add / Edit Account form -->
    <div class="sm-panel">
      <div class="sm-panel-head"><?=$ea?'✎ Edit Account':'➕ Add Account'?></div>
      <div class="sm-panel-body">
        <form method="POST">
          <input type="hidden" name="action" value="save_account">
          <?php if($ea): ?><input type="hidden" name="account_id" value="<?=$ea['id']?>"><?php endif; ?>

          <div class="form-group">
            <label class="form-label">Platform</label>
            <select name="platform" class="form-control">
              <?php foreach ($PLATS as $pk=>$pv): ?><option value="<?=$pk?>" <?=($ea['platform']??'')===$pk?'selected':''?>><?=$pv['icon'].' '.$pv['label']?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Account Name *</label>
            <input type="text" name="acc_name" class="form-control" required value="<?=h($ea['name']??'')?>" placeholder="e.g. Padak Facebook Page">
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

          <!-- ── CREDENTIALS ── -->
          <div style="border-top:1px solid var(--border);padding-top:12px;margin-top:4px;margin-bottom:12px">
            <div style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">🔐 Login Credentials</div>
            <div class="form-group">
              <label class="form-label">Login Email / Username</label>
              <input type="text" name="acc_email" class="form-control" value="<?=h($ea['acc_email']??'')?>" placeholder="email@example.com or @username" autocomplete="off">
            </div>
            <div class="form-group">
              <label class="form-label">Password</label>
              <div class="pwd-field">
                <input type="password" name="acc_password" id="form-pwd" class="form-control" value="<?=h($ea['acc_password']??'')?>" placeholder="Account password" autocomplete="new-password">
                <button type="button" onclick="toggleFormPwd()">👁</button>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Role / Access Level</label>
              <select name="acc_role" class="form-control">
                <?php foreach ($ROLES as $rv=>$rl): ?><option value="<?=$rv?>" <?=($ea['acc_role']??'admin')===$rv?'selected':''?>><?=$rl?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">2FA / Recovery Note</label>
              <input type="text" name="acc_2fa" class="form-control" value="<?=h($ea['acc_2fa']??'')?>" placeholder="Backup code, authenticator app, etc." autocomplete="off">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Any extra info..."><?=h($ea['notes']??'')?></textarea>
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

<!-- ── JSON DATA for JS ── -->
<div id="posts-json" style="display:none"><?=htmlspecialchars(json_encode(array_values($posts)),ENT_QUOTES)?></div>
<div id="plat-urls" style="display:none"><?=htmlspecialchars(json_encode($PU),ENT_QUOTES)?></div>
<div id="plat-cfg" style="display:none"><?=htmlspecialchars(json_encode($PLATS),ENT_QUOTES)?></div>

<script>
var smPosts   = JSON.parse(document.getElementById('posts-json').textContent);
var platUrls  = JSON.parse(document.getElementById('plat-urls').textContent);
var platCfg   = JSON.parse(document.getElementById('plat-cfg').textContent);
var charLimits= {twitter:280,instagram:2200,facebook:63206,linkedin:3000,tiktok:2200,youtube:5000,other:5000};
var pwdVisible= {};

// ── SECTION SWITCH ─────────────────────────────────────────────────────────
function smSec(n){
    ['dashboard','pipeline','compose','calendar','accounts'].forEach(function(s){
        var el=document.getElementById('smsec-'+s);
        if(el) el.style.display=(s===n?'block':'none');
    });
    document.querySelectorAll('.sm-tab').forEach(function(b,i){
        b.classList.toggle('active',['dashboard','pipeline','compose','calendar','accounts'][i]===n);
    });
    if(n==='compose') onInput();
}

// ── COMPOSE: char counter + preview ───────────────────────────────────────
function onInput(){
    var c  = document.getElementById('cmp-content')?.value||'';
    var t  = document.getElementById('cmp-tags')?.value||'';
    var full = c+(t?'\n\n'+t:'');
    var pv = document.getElementById('cmp-preview');
    if(pv) pv.textContent = full||'Caption preview appears here...';
    var plat  = document.getElementById('cmp-plat')?.value||'';
    var limit = charLimits[plat]||5000;
    var cnt   = document.getElementById('char-cnt');
    if(cnt){
        cnt.textContent = c.length+' / '+limit;
        var pct = c.length/limit;
        cnt.className = 'char-counter '+(pct>1?'char-over':pct>0.85?'char-warn':'char-ok');
    }
}

function onPlatChange(plat){
    onInput();
    var pb = document.getElementById('open-plat-btn');
    if(!pb) return;
    var url= platUrls[plat];
    var pc = platCfg[plat]||platCfg['other'];
    if(url) pb.innerHTML='<a href="'+url+'" target="_blank" class="btn btn-primary" style="width:100%;text-align:center;justify-content:center;background:'+pc.color+';border-color:'+pc.color+'">'+pc.icon+' Open '+pc.label+' to Post</a>';
    else    pb.innerHTML='<div style="padding:6px;text-align:center;font-size:12px;color:var(--text3)">Select a platform to get the direct post link</div>';
}

function syncAccPlat(sel){
    var opt  = sel.options[sel.selectedIndex];
    var plat = opt?.dataset?.platform||'';
    var pp   = document.getElementById('cmp-plat');
    if(pp && plat) pp.value = plat;
    onPlatChange(plat);
}

function copyCaption(){
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

function quickCopy(id){
    var p=smPosts.find(function(x){return x.id==id;});
    if(!p) return;
    var full=(p.content||'')+(p.tags?'\n\n'+p.tags:'');
    if(!full){toast('No content','error');return;}
    navigator.clipboard.writeText(full).then(function(){toast('Caption copied 🚀','success');}).catch(function(){toast('Could not copy','error');});
}

// ── PIPELINE ACTIONS ───────────────────────────────────────────────────────
function markPublished(id){
    if(!confirm('Mark as published?'))return;
    var fd=new FormData();fd.append('action','quick_status');fd.append('post_id',id);fd.append('status','published');
    fetch('social_media.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){if(d.ok){toast('Published ✅','success');location.reload();}});
}

function delPost(id){
    if(!confirm('Delete this post permanently?'))return;
    var fd=new FormData();fd.append('action','delete_post');fd.append('post_id',id);
    fetch('social_media.php',{method:'POST',body:fd}).then(function(){location.reload();});
}

// ── PASSWORD TOGGLE ────────────────────────────────────────────────────────
function togglePwd(id, pwd){
    var el=document.getElementById('pwdval-'+id);
    if(!el) return;
    if(pwdVisible[id]){
        el.textContent='••••••••';
        el.style.filter='blur(4px)';
        pwdVisible[id]=false;
    } else {
        el.textContent=pwd;
        el.style.filter='none';
        pwdVisible[id]=true;
        // Auto-hide after 8 seconds
        setTimeout(function(){if(pwdVisible[id]){el.textContent='••••••••';el.style.filter='blur(4px)';pwdVisible[id]=false;}},8000);
    }
}

function toggleFormPwd(){
    var inp=document.getElementById('form-pwd');
    if(!inp) return;
    inp.type = inp.type==='password' ? 'text' : 'password';
}

// ── INIT ───────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded',function(){
    onInput();
    onPlatChange(document.getElementById('cmp-plat')?.value||'');
});
</script>
<?php renderLayoutEnd(); ?>