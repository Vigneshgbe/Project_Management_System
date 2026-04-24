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

    if ($action === 'save_account') {
        $platform = $_POST['platform'] ?? 'other';
        $name     = trim($_POST['acc_name'] ?? '');
        $handle   = trim($_POST['handle'] ?? '');
        $url      = trim($_POST['url'] ?? '');
        $followers= (int)($_POST['followers'] ?? 0);
        $notes    = trim($_POST['notes'] ?? '');
        $aid      = (int)($_POST['account_id'] ?? 0);
        if ($name) {
            if ($aid) {
                $stmt=$db->prepare("UPDATE social_accounts SET platform=?,name=?,handle=?,url=?,followers=?,notes=? WHERE id=$aid");
                $stmt->bind_param("ssssis",$platform,$name,$handle,$url,$followers,$notes);
            } else {
                $stmt=$db->prepare("INSERT INTO social_accounts (platform,name,handle,url,followers,notes,created_by) VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param("ssssisi",$platform,$name,$handle,$url,$followers,$notes,$uid);
            }
            $stmt->execute();
            flash('Account saved.','success');
        }
        ob_end_clean(); header('Location: social_media.php'); exit;
    }

    if ($action === 'save_post') {
        $account_id  = (int)($_POST['account_id'] ?? 0) ?: null;
        $title       = trim($_POST['title'] ?? '');
        $content     = trim($_POST['content'] ?? '');
        $platform    = $_POST['platform'] ?? '';
        $status      = $_POST['status'] ?? 'idea';
        $post_type   = $_POST['post_type'] ?? 'post';
        $tags        = trim($_POST['tags'] ?? '');
        $sched       = $_POST['scheduled_at'] ?: null;
        $likes       = (int)($_POST['likes'] ?? 0);
        $comments_c  = (int)($_POST['comments'] ?? 0);
        $reach       = (int)($_POST['reach'] ?? 0);
        $proj_id     = (int)($_POST['project_id'] ?? 0) ?: null;
        $post_id     = (int)($_POST['post_id'] ?? 0);
        $pub_at      = ($status === 'published' && !$post_id) ? date('Y-m-d H:i:s') : null;
        if ($title) {
            if ($post_id) {
                $stmt=$db->prepare("UPDATE social_posts SET account_id=?,title=?,content=?,platform=?,status=?,post_type=?,tags=?,scheduled_at=?,likes=?,comments=?,reach=?,project_id=? WHERE id=$post_id");
                $stmt->bind_param("isssssssiiis",$account_id,$title,$content,$platform,$status,$post_type,$tags,$sched,$likes,$comments_c,$reach,$proj_id);
            } else {
                $stmt=$db->prepare("INSERT INTO social_posts (account_id,title,content,platform,status,post_type,tags,scheduled_at,published_at,likes,comments,reach,project_id,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param("issssssssiiiii",$account_id,$title,$content,$platform,$status,$post_type,$tags,$sched,$pub_at,$likes,$comments_c,$reach,$proj_id,$uid);
            }
            $stmt->execute();
            logActivity('saved social post',$title,$db->insert_id);
            flash('Post saved.','success');
        }
        ob_end_clean(); header('Location: social_media.php'); exit;
    }

    if ($action === 'delete_post') {
        $pid=(int)($_POST['post_id']??0);
        $db->query("DELETE FROM social_posts WHERE id=$pid");
        flash('Post deleted.','success');
        ob_end_clean(); header('Location: social_media.php'); exit;
    }

    if ($action === 'delete_account') {
        $aid=(int)($_POST['account_id']??0);
        $db->query("UPDATE social_accounts SET is_active=0 WHERE id=$aid");
        flash('Account removed.','success');
        ob_end_clean(); header('Location: social_media.php'); exit;
    }
}
ob_end_clean();

$tab   = $_GET['tab'] ?? 'calendar';
$accounts = $db->query("SELECT * FROM social_accounts WHERE is_active=1 ORDER BY platform,name")->fetch_all(MYSQLI_ASSOC);
$posts = $db->query("
    SELECT sp.*, sa.name AS acc_name, sa.platform AS acc_platform
    FROM social_posts sp
    LEFT JOIN social_accounts sa ON sa.id=sp.account_id
    ORDER BY sp.scheduled_at IS NULL, sp.scheduled_at ASC, sp.created_at DESC
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

$projects = $db->query("SELECT id,title FROM projects WHERE status='active' ORDER BY title")->fetch_all(MYSQLI_ASSOC);

$platform_icons = ['facebook'=>'f','instagram'=>'📸','twitter'=>'🐦','linkedin'=>'💼','youtube'=>'▶️','tiktok'=>'🎵','other'=>'📱'];
$platform_colors= ['facebook'=>'#1877f2','instagram'=>'#e1306c','twitter'=>'#1da1f2','linkedin'=>'#0077b5','youtube'=>'#ff0000','tiktok'=>'#000000','other'=>'#64748b'];
$status_colors  = ['idea'=>'#94a3b8','draft'=>'#f59e0b','scheduled'=>'#6366f1','published'=>'#10b981','cancelled'=>'#ef4444'];
$post_types     = ['post'=>'Post','story'=>'Story','reel'=>'Reel','video'=>'Video','article'=>'Article'];

renderLayout('Social Media','social_media');
?>
<style>
.sm-tabs{display:flex;gap:2px;border-bottom:1px solid var(--border);margin-bottom:20px}
.sm-tab{padding:8px 18px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;border:none;background:none;border-bottom:2px solid transparent;transition:color .15s}
.sm-tab.active{color:var(--orange);border-bottom-color:var(--orange)}
.sm-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:18px}
.sm-acc-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;display:flex;flex-direction:column;gap:10px}
.sm-acc-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.sm-post-row{display:flex;align-items:center;gap:12px;padding:10px 14px;border-bottom:1px solid var(--border);transition:background .1s}
.sm-post-row:hover{background:var(--bg3)}
.sm-post-row:last-child{border-bottom:none}
.sm-status{font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:99px;flex-shrink:0}
.sm-platform-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px}
.cal-day{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);min-height:90px;padding:6px}
.cal-day-num{font-size:11px;font-weight:700;color:var(--text3);margin-bottom:4px}
.cal-day.today{border-color:var(--orange);background:var(--orange-bg)}
.cal-day.today .cal-day-num{color:var(--orange)}
.cal-post-chip{font-size:10px;font-weight:600;padding:2px 5px;border-radius:3px;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer}
.sm-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px}
.sm-stat{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:12px 14px;text-align:center}
.sm-stat-v{font-size:20px;font-weight:800;font-family:var(--font-display)}
.sm-stat-l{font-size:11px;color:var(--text3);text-transform:uppercase;letter-spacing:.05em}
@media(max-width:900px){.sm-grid{grid-template-columns:1fr 1fr}.cal-grid{grid-template-columns:repeat(4,1fr)}}
@media(max-width:600px){.sm-grid{grid-template-columns:1fr}.sm-stats{grid-template-columns:1fr 1fr}}
</style>

<!-- Stats row -->
<?php
$total_posts = count($posts);
$published   = count(array_filter($posts, fn($p)=>$p['status']==='published'));
$scheduled   = count(array_filter($posts, fn($p)=>$p['status']==='scheduled'));
$total_accs  = count($accounts);
?>
<div class="sm-stats">
  <div class="sm-stat"><div class="sm-stat-v" style="color:var(--orange)"><?= $total_accs ?></div><div class="sm-stat-l">Accounts</div></div>
  <div class="sm-stat"><div class="sm-stat-v" style="color:var(--green)"><?= $published ?></div><div class="sm-stat-l">Published</div></div>
  <div class="sm-stat"><div class="sm-stat-v" style="color:var(--blue)"><?= $scheduled ?></div><div class="sm-stat-l">Scheduled</div></div>
  <div class="sm-stat"><div class="sm-stat-v" style="color:var(--text2)"><?= $total_posts ?></div><div class="sm-stat-l">Total Posts</div></div>
</div>

<div class="sm-tabs">
  <button class="sm-tab <?= $tab==='calendar'?'active':'' ?>" onclick="smTab('calendar')">📅 Content Calendar</button>
  <button class="sm-tab <?= $tab==='posts'?'active':'' ?>"    onclick="smTab('posts')">📋 All Posts</button>
  <button class="sm-tab <?= $tab==='create'?'active':'' ?>"   onclick="smTab('create')" id="sm-tab-create">✏️ New Post</button>
  <button class="sm-tab <?= $tab==='accounts'?'active':'' ?>" onclick="smTab('accounts')">📱 Accounts</button>
</div>

<!-- CALENDAR TAB -->
<div id="sm-calendar" style="display:<?= $tab==='calendar'?'block':'none' ?>">
  <?php
  $year = (int)($_GET['y'] ?? date('Y'));
  $month= (int)($_GET['m'] ?? date('n'));
  $first_day = mktime(0,0,0,$month,1,$year);
  $days_in   = date('t',$first_day);
  $start_dow = (int)date('N',$first_day); // 1=Mon

  // Map posts to days
  $posts_by_day = [];
  foreach ($posts as $p) {
      $dt = $p['scheduled_at'] ?: $p['created_at'];
      if (date('Y-n',$dt?strtotime($dt):time()) === "$year-$month") {
          $d = (int)date('j',$dt?strtotime($dt):time());
          $posts_by_day[$d][] = $p;
      }
  }
  ?>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
    <a href="?tab=calendar&y=<?= $month===1?$year-1:$year ?>&m=<?= $month===1?12:$month-1 ?>" class="btn btn-ghost btn-sm">← Prev</a>
    <div style="font-size:15px;font-weight:700;font-family:var(--font-display)"><?= date('F Y',$first_day) ?></div>
    <a href="?tab=calendar&y=<?= $month===12?$year+1:$year ?>&m=<?= $month===12?1:$month+1 ?>" class="btn btn-ghost btn-sm">Next →</a>
  </div>
  <div class="cal-grid" style="margin-bottom:6px">
    <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
    <div style="text-align:center;font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;padding:4px 0"><?= $d ?></div>
    <?php endforeach; ?>
  </div>
  <div class="cal-grid">
    <?php
    // Empty cells before first day
    for ($i=1;$i<$start_dow;$i++) echo '<div style="min-height:90px"></div>';
    $today_num = date('j'); $today_month = date('n'); $today_year = date('Y');
    for ($d=1;$d<=$days_in;$d++) {
        $is_today = ($d==$today_num && $month==$today_month && $year==$today_year);
        echo '<div class="cal-day'.($is_today?' today':'').'">';
        echo '<div class="cal-day-num">'.$d.'</div>';
        if (!empty($posts_by_day[$d])) {
            foreach ($posts_by_day[$d] as $p) {
                $pc = $platform_colors[$p['platform']??'other'] ?? '#64748b';
                $sc = $status_colors[$p['status']] ?? '#94a3b8';
                echo '<div class="cal-post-chip" style="background:'.$pc.'18;color:'.$pc.'" title="'.h($p['title']).'">';
                echo h(mb_substr($p['title'],0,20));
                echo '</div>';
            }
        }
        echo '</div>';
    }
    ?>
  </div>
</div>

<!-- POSTS TAB -->
<div id="sm-posts" style="display:<?= $tab==='posts'?'block':'none' ?>">
  <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    <button onclick="smTab('create')" class="btn btn-primary">✏️ New Post</button>
  </div>
  <?php if (!$posts): ?>
  <div class="empty-state"><div class="icon">📱</div><p>No posts yet. Create your first content.</p></div>
  <?php else: ?>
  <div class="card" style="padding:0">
    <?php foreach ($posts as $p):
      $pc = $platform_colors[$p['platform']??($p['acc_platform']??'other')] ?? '#64748b';
      $sc = $status_colors[$p['status']] ?? '#94a3b8';
      $plat = $p['platform'] ?: ($p['acc_platform'] ?: '');
    ?>
    <div class="sm-post-row">
      <div class="sm-platform-dot" style="background:<?= $pc ?>"></div>
      <div style="flex:1;min-width:0">
        <div style="font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($p['title']) ?></div>
        <div style="font-size:11.5px;color:var(--text3)">
          <?= h($p['acc_name']??ucfirst($plat)) ?>
          <?= $p['scheduled_at']?' · 📅 '.date('M j, g:ia',strtotime($p['scheduled_at'])):'' ?>
          <?= $p['tags']?' · 🏷 '.h(mb_substr($p['tags'],0,30)):'' ?>
        </div>
      </div>
      <?php if ($p['likes']||$p['comments']||$p['reach']): ?>
      <div style="font-size:11.5px;color:var(--text3);text-align:right;flex-shrink:0;margin-right:8px">
        <?= $p['likes']?'❤️ '.$p['likes']:'' ?> <?= $p['comments']?'💬 '.$p['comments']:'' ?> <?= $p['reach']?'👁 '.$p['reach']:'' ?>
      </div>
      <?php endif; ?>
      <span class="sm-status" style="background:<?= $sc ?>18;color:<?= $sc ?>"><?= ucfirst($p['status']) ?></span>
      <div style="display:flex;gap:5px;flex-shrink:0;margin-left:8px">
        <button onclick="editPost(<?= $p['id'] ?>)" class="btn btn-ghost btn-sm btn-icon" title="Edit">✎</button>
        <form method="POST" onsubmit="return confirm('Delete post?')" style="display:inline">
          <input type="hidden" name="action" value="delete_post">
          <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm btn-icon">🗑</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- CREATE POST TAB -->
<div id="sm-create" style="display:<?= $tab==='create'?'block':'none' ?>">
  <div style="display:grid;grid-template-columns:1fr 360px;gap:18px">
    <form method="POST" id="post-form">
      <input type="hidden" name="action" value="save_post">
      <input type="hidden" name="post_id" id="edit-post-id" value="">
      <div class="card" style="padding:20px">
        <div class="form-group">
          <label class="form-label">Post Title / Caption *</label>
          <input type="text" name="title" id="pt-title" class="form-control" required placeholder="What's this post about?">
        </div>
        <div class="form-group">
          <label class="form-label">Content <span style="font-size:11px;color:var(--text3)" id="pt-char-count"></span></label>
          <textarea name="content" id="pt-content" class="form-control" rows="6" placeholder="Write your post content, caption, or script here..." oninput="countChars()"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Platform</label>
            <select name="platform" id="pt-platform" class="form-control">
              <option value="">— Select platform —</option>
              <?php foreach ($platform_icons as $k=>$v): ?>
              <option value="<?= $k ?>"><?= $v ?> <?= ucfirst($k) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Account</label>
            <select name="account_id" id="pt-account" class="form-control">
              <option value="">— Select account —</option>
              <?php foreach ($accounts as $a): ?>
              <option value="<?= $a['id'] ?>"><?= $platform_icons[$a['platform']]??'📱' ?> <?= h($a['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Post Type</label>
            <select name="post_type" id="pt-type" class="form-control">
              <?php foreach ($post_types as $k=>$v): ?>
              <option value="<?= $k ?>"><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="pt-status" class="form-control">
              <option value="idea">💡 Idea</option>
              <option value="draft">📝 Draft</option>
              <option value="scheduled">📅 Scheduled</option>
              <option value="published">✅ Published</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Scheduled Date & Time</label>
            <input type="datetime-local" name="scheduled_at" id="pt-sched" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Project (optional)</label>
            <select name="project_id" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($projects as $pr): ?>
              <option value="<?= $pr['id'] ?>"><?= h($pr['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Tags / Hashtags</label>
          <input type="text" name="tags" id="pt-tags" class="form-control" placeholder="#marketing #padak #seo">
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Likes</label><input type="number" name="likes" id="pt-likes" class="form-control" value="0" min="0"></div>
          <div class="form-group"><label class="form-label">Comments</label><input type="number" name="comments" id="pt-comments" class="form-control" value="0" min="0"></div>
          <div class="form-group"><label class="form-label">Reach</label><input type="number" name="reach" id="pt-reach" class="form-control" value="0" min="0"></div>
        </div>
        <div style="display:flex;gap:10px">
          <button type="submit" class="btn btn-primary" style="flex:1">💾 Save Post</button>
          <button type="button" onclick="resetPostForm()" class="btn btn-ghost">✕ Clear</button>
        </div>
      </div>
    </form>

    <!-- Accounts sidebar -->
    <div>
      <div class="card" style="padding:16px">
        <div style="font-size:13px;font-weight:700;margin-bottom:12px">📱 Your Accounts</div>
        <?php foreach ($accounts as $a): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
          <div style="width:32px;height:32px;border-radius:8px;background:<?= $platform_colors[$a['platform']]??'#64748b' ?>20;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0"><?= $platform_icons[$a['platform']]??'📱' ?></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12.5px;font-weight:600;color:var(--text)"><?= h($a['name']) ?></div>
            <div style="font-size:11px;color:var(--text3)"><?= $a['followers']?number_format($a['followers']).' followers':ucfirst($a['platform']) ?></div>
          </div>
          <?php if ($a['url']): ?><a href="<?= h($a['url']) ?>" target="_blank" style="font-size:11px;color:var(--orange)">↗</a><?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if (!$accounts): ?><div style="color:var(--text3);font-size:12.5px;padding:8px 0">No accounts. Add one in the Accounts tab.</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ACCOUNTS TAB -->
<div id="sm-accounts" style="display:<?= $tab==='accounts'?'block':'none' ?>">
  <div style="display:grid;grid-template-columns:1fr 360px;gap:18px">
    <!-- Account list -->
    <div>
      <div class="sm-grid">
        <?php foreach ($accounts as $a): ?>
        <div class="sm-acc-card">
          <div style="display:flex;align-items:center;gap:10px">
            <div class="sm-acc-icon" style="background:<?= $platform_colors[$a['platform']]??'#64748b' ?>20"><?= $platform_icons[$a['platform']]??'📱' ?></div>
            <div style="flex:1;min-width:0">
              <div style="font-size:13.5px;font-weight:700;color:var(--text)"><?= h($a['name']) ?></div>
              <div style="font-size:12px;color:var(--text3)"><?= $a['handle']?h($a['handle']):ucfirst($a['platform']) ?></div>
            </div>
          </div>
          <?php if ($a['followers']): ?>
          <div style="font-size:14px;font-weight:700;color:var(--text)"><?= number_format($a['followers']) ?> <span style="font-size:11px;color:var(--text3);font-weight:400">followers</span></div>
          <?php endif; ?>
          <?php if ($a['url']): ?><a href="<?= h($a['url']) ?>" target="_blank" style="font-size:12px;color:var(--orange)">🔗 Open Profile</a><?php endif; ?>
          <div style="display:flex;gap:6px">
            <button onclick="editAccount(<?= $a['id'] ?>)" class="btn btn-ghost btn-sm">✎ Edit</button>
            <form method="POST" onsubmit="return confirm('Remove account?')" style="display:inline">
              <input type="hidden" name="action" value="delete_account">
              <input type="hidden" name="account_id" value="<?= $a['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm btn-icon">🗑</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$accounts): ?>
        <div class="empty-state" style="grid-column:1/-1"><div class="icon">📱</div><p>No social accounts yet.</p></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Add/Edit account form -->
    <div class="card" style="padding:18px" id="account-form-card">
      <div style="font-size:13px;font-weight:700;margin-bottom:14px" id="account-form-title">➕ Add Account</div>
      <form method="POST">
        <input type="hidden" name="action" value="save_account">
        <input type="hidden" name="account_id" id="edit-acc-id" value="">
        <div class="form-group">
          <label class="form-label">Platform</label>
          <select name="platform" id="acc-platform" class="form-control">
            <?php foreach ($platform_icons as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?> <?= ucfirst($k) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Account Name *</label>
          <input type="text" name="acc_name" id="acc-name" class="form-control" required placeholder="e.g. Padak Facebook Page">
        </div>
        <div class="form-group">
          <label class="form-label">Handle / Username</label>
          <input type="text" name="handle" id="acc-handle" class="form-control" placeholder="@thepadak">
        </div>
        <div class="form-group">
          <label class="form-label">Profile URL</label>
          <input type="url" name="url" id="acc-url" class="form-control" placeholder="https://facebook.com/...">
        </div>
        <div class="form-group">
          <label class="form-label">Follower Count</label>
          <input type="number" name="followers" id="acc-followers" class="form-control" value="0" min="0">
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" id="acc-notes" class="form-control" rows="2"></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">Save Account</button>
      </form>
    </div>
  </div>
</div>

<script>
var smPostsData = <?= json_encode(array_column($posts, null, 'id')) ?>;
var smAccounts  = <?= json_encode(array_column($accounts, null, 'id')) ?>;

function smTab(t) {
    ['calendar','posts','create','accounts'].forEach(function(n){
        document.getElementById('sm-'+n).style.display = n===t?'block':'none';
    });
    document.querySelectorAll('.sm-tab').forEach(function(b,i){
        b.classList.toggle('active', ['calendar','posts','create','accounts'][i]===t);
    });
}

function countChars() {
    var l = document.getElementById('pt-content').value.length;
    var cc = document.getElementById('pt-char-count');
    if (cc) cc.textContent = '(' + l + ' chars)';
}

function editPost(id) {
    var p = smPostsData[id];
    if (!p) return;
    document.getElementById('edit-post-id').value  = id;
    document.getElementById('pt-title').value       = p.title || '';
    document.getElementById('pt-content').value     = p.content || '';
    document.getElementById('pt-platform').value    = p.platform || '';
    document.getElementById('pt-account').value     = p.account_id || '';
    document.getElementById('pt-type').value        = p.post_type || 'post';
    document.getElementById('pt-status').value      = p.status || 'idea';
    document.getElementById('pt-tags').value        = p.tags || '';
    document.getElementById('pt-likes').value       = p.likes || 0;
    document.getElementById('pt-comments').value    = p.comments || 0;
    document.getElementById('pt-reach').value       = p.reach || 0;
    if (p.scheduled_at) {
        var dt = p.scheduled_at.replace(' ','T').slice(0,16);
        document.getElementById('pt-sched').value = dt;
    }
    smTab('create');
    countChars();
}

function resetPostForm() {
    document.getElementById('post-form').reset();
    document.getElementById('edit-post-id').value = '';
    countChars();
}

function editAccount(id) {
    var a = smAccounts[id];
    if (!a) return;
    document.getElementById('edit-acc-id').value    = id;
    document.getElementById('acc-platform').value   = a.platform || 'other';
    document.getElementById('acc-name').value       = a.name || '';
    document.getElementById('acc-handle').value     = a.handle || '';
    document.getElementById('acc-url').value        = a.url || '';
    document.getElementById('acc-followers').value  = a.followers || 0;
    document.getElementById('acc-notes').value      = a.notes || '';
    document.getElementById('account-form-title').textContent = '✎ Edit Account';
    smTab('accounts');
}
</script>

<?php renderLayoutEnd(); ?>