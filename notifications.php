<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();

$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

// ── POST actions (mark read, delete) ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    ob_start();
    if ($act === 'mark_one') {
        $id = (int)$_POST['nid'];
        $db->query("UPDATE notifications SET is_read=1 WHERE id=$id AND user_id=$uid");
    } elseif ($act === 'mark_all') {
        $db->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");
        flash('All notifications marked as read.', 'success');
    } elseif ($act === 'delete_one') {
        $id = (int)$_POST['nid'];
        $db->query("DELETE FROM notifications WHERE id=$id AND user_id=$uid");
    } elseif ($act === 'clear_read') {
        $db->query("DELETE FROM notifications WHERE user_id=$uid AND is_read=1");
        flash('Read notifications cleared.', 'success');
    } elseif ($act === 'delete_all') {
        $db->query("DELETE FROM notifications WHERE user_id=$uid");
        flash('All notifications deleted.', 'success');
    }
    ob_end_clean();
    // Redirect back preserving filter
    $qs = http_build_query(array_filter([
        'filter' => $_POST['filter'] ?? '',
        'page'   => $_POST['page']   ?? '',
    ]));
    header('Location: notifications.php'.($qs?"?$qs":'')); exit;
}

// ── Params ────────────────────────────────────────────────────────────────
$filter  = $_GET['filter'] ?? 'all';
$page    = max(1, (int)($_GET['page'] ?? 1));
$per     = 20;
$offset  = ($page - 1) * $per;

$allowed_filters = ['all','unread','task','project','lead','meeting','document','message'];
if (!in_array($filter, $allowed_filters)) $filter = 'all';

// ── WHERE clause ──────────────────────────────────────────────────────────
$where = "user_id=$uid";
if ($filter === 'unread') $where .= " AND is_read=0";
elseif ($filter !== 'all') $where .= " AND entity_type='".$db->real_escape_string($filter)."'";

// ── Counts ────────────────────────────────────────────────────────────────
$total_rows  = (int)$db->query("SELECT COUNT(*) AS c FROM notifications WHERE $where")->fetch_assoc()['c'];
$total_pages = max(1, (int)ceil($total_rows / $per));
$unread_all  = (int)$db->query("SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc()['c'];

// Per-type unread counts for filter badges
$type_counts = [];
foreach (['task','project','lead','meeting','document','message'] as $et) {
    $type_counts[$et] = (int)$db->query("SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid AND is_read=0 AND entity_type='$et'")->fetch_assoc()['c'];
}

// ── Load page ─────────────────────────────────────────────────────────────
$rows = $db->query("
    SELECT id, type, entity_type, entity_id, title, body, link, is_read, created_at,
           TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_sec
    FROM notifications
    WHERE $where
    ORDER BY created_at DESC
    LIMIT $per OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

// ── Type config ───────────────────────────────────────────────────────────
$TYPE_CFG = [
    'task'     => ['icon'=>'✅','color'=>'#6366f1','label'=>'Tasks'],
    'project'  => ['icon'=>'📁','color'=>'#f97316','label'=>'Projects'],
    'lead'     => ['icon'=>'🎯','color'=>'#10b981','label'=>'Leads'],
    'meeting'  => ['icon'=>'🤝','color'=>'#3b82f6','label'=>'Meetings'],
    'document' => ['icon'=>'📄','color'=>'#8b5cf6','label'=>'Docs'],
    'message'  => ['icon'=>'💬','color'=>'#f59e0b','label'=>'Messages'],
];

// Notification type → icon map (the 'type' column, not entity_type)
$NOTIF_ICONS = [
    'task_assigned'      => ['icon'=>'👤','color'=>'#6366f1'],
    'task_due_soon'      => ['icon'=>'⏰','color'=>'#f59e0b'],
    'task_overdue'       => ['icon'=>'🔴','color'=>'#ef4444'],
    'task_completed'     => ['icon'=>'✅','color'=>'#10b981'],
    'task_comment'       => ['icon'=>'💬','color'=>'#6366f1'],
    'task_status'        => ['icon'=>'🔄','color'=>'#6366f1'],
    'project_assigned'   => ['icon'=>'📁','color'=>'#f97316'],
    'project_due_soon'   => ['icon'=>'⏰','color'=>'#f97316'],
    'project_milestone'  => ['icon'=>'🏁','color'=>'#10b981'],
    'project_status'     => ['icon'=>'🔄','color'=>'#f97316'],
    'lead_assigned'      => ['icon'=>'🎯','color'=>'#10b981'],
    'lead_status'        => ['icon'=>'🔄','color'=>'#10b981'],
    'meeting_invited'    => ['icon'=>'🤝','color'=>'#3b82f6'],
    'meeting_updated'    => ['icon'=>'✏️','color'=>'#3b82f6'],
    'meeting_reminder'   => ['icon'=>'🔔','color'=>'#3b82f6'],
    'meeting_cancelled'  => ['icon'=>'❌','color'=>'#ef4444'],
    'document_shared'    => ['icon'=>'📄','color'=>'#8b5cf6'],
    'calendar_invited'   => ['icon'=>'📅','color'=>'#10b981'],
];

function fmtAge(int $sec): string {
    if ($sec < 60)     return 'Just now';
    if ($sec < 3600)   return floor($sec/60).'m ago';
    if ($sec < 86400)  return floor($sec/3600).'h ago';
    if ($sec < 604800) return floor($sec/86400).'d ago';
    return date('M j', time()-$sec);
}

renderLayout('Notifications', 'notifications');
?>
<style>
.notif-page-wrap{max-width:860px;margin:0 auto}
.notif-header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.notif-header h1{font-family:var(--font-display);font-size:22px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
.notif-header h1 .badge-count{background:var(--orange);color:#fff;font-size:12px;font-weight:700;padding:2px 8px;border-radius:99px}
.notif-actions{display:flex;gap:8px;flex-wrap:wrap}

/* Filter tabs */
.notif-filters{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px}
.nf-pill{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:99px;font-size:12.5px;font-weight:600;border:1px solid var(--border);background:var(--bg3);color:var(--text2);text-decoration:none;transition:all .15s;cursor:pointer}
.nf-pill:hover{border-color:var(--orange);color:var(--orange)}
.nf-pill.active{background:var(--orange);border-color:var(--orange);color:#fff}
.nf-pill .cnt{background:rgba(0,0,0,.15);color:inherit;border-radius:99px;padding:0px 6px;font-size:11px;font-weight:700}
.nf-pill.active .cnt{background:rgba(255,255,255,.3)}

/* Notification rows */
.notif-list{display:flex;flex-direction:column;gap:2px}
.notif-row{display:flex;align-items:flex-start;gap:13px;padding:13px 16px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);cursor:pointer;transition:background .12s,box-shadow .12s;position:relative;text-decoration:none}
.notif-row:hover{background:var(--bg3);box-shadow:0 2px 8px rgba(0,0,0,.1)}
.notif-row.unread{background:var(--bg2);border-left:3px solid var(--nc,#f97316)}
.notif-row.unread::before{content:'';position:absolute;top:16px;right:16px;width:7px;height:7px;border-radius:50%;background:var(--nc,#f97316)}
.notif-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;border:1px solid var(--border)}
.notif-body{flex:1;min-width:0}
.notif-title{font-size:13.5px;font-weight:700;color:var(--text);margin-bottom:3px;line-height:1.4}
.notif-body-txt{font-size:12.5px;color:var(--text2);margin-bottom:4px;line-height:1.5}
.notif-meta{font-size:11.5px;color:var(--text3);display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.notif-row-actions{display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0}
.na-btn{font-size:11px;padding:3px 8px;border-radius:4px;border:1px solid var(--border);background:var(--bg3);color:var(--text3);cursor:pointer;white-space:nowrap;transition:all .12s;line-height:1.4}
.na-btn:hover{border-color:var(--orange);color:var(--orange)}

/* Group header */
.notif-group-hdr{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text3);padding:14px 4px 6px;border-bottom:1px solid var(--border);margin-bottom:8px}

/* Stats strip */
.notif-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px}
.nstat{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;text-align:center}
.nstat-n{font-size:20px;font-weight:800;color:var(--text)}
.nstat-l{font-size:11px;color:var(--text3);margin-top:2px}

/* Empty */
.notif-empty{text-align:center;padding:60px 20px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius)}
.notif-empty .icon{font-size:52px;margin-bottom:12px}

/* Pagination */
.npag{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:20px;flex-wrap:wrap}
.npag a,.npag span{padding:6px 12px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--bg3);color:var(--text2);font-size:12.5px;font-weight:600;text-decoration:none;transition:all .12s}
.npag a:hover{border-color:var(--orange);color:var(--orange)}
.npag span.cur{background:var(--orange);border-color:var(--orange);color:#fff}
.npag span.dots{background:none;border:none;color:var(--text3)}

@media(max-width:600px){
  .notif-stats{grid-template-columns:repeat(2,1fr)}
  .notif-row{flex-wrap:wrap}
}
</style>

<div class="notif-page-wrap">

  <!-- Header -->
  <div class="notif-header">
    <h1>
      🔔 Notifications
      <?php if ($unread_all): ?>
      <span class="badge-count"><?= $unread_all ?> unread</span>
      <?php endif; ?>
    </h1>
    <div class="notif-actions">
      <?php if ($unread_all): ?>
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="mark_all">
        <input type="hidden" name="filter" value="<?= h($filter) ?>">
        <button class="btn btn-ghost btn-sm">✓ Mark all read</button>
      </form>
      <?php endif; ?>
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="clear_read">
        <input type="hidden" name="filter" value="<?= h($filter) ?>">
        <button class="btn btn-ghost btn-sm" style="color:var(--text3)">🗑 Clear read</button>
      </form>
      <?php if (isAdmin()): ?>
      <form method="POST" style="display:inline" onsubmit="return confirm('Delete ALL your notifications?')">
        <input type="hidden" name="action" value="delete_all">
        <button class="btn btn-danger btn-sm">Delete All</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats strip -->
  <div class="notif-stats">
    <div class="nstat">
      <div class="nstat-n"><?= $total_rows ?></div>
      <div class="nstat-l">Total</div>
    </div>
    <div class="nstat">
      <div class="nstat-n" style="color:var(--orange)"><?= $unread_all ?></div>
      <div class="nstat-l">Unread</div>
    </div>
    <div class="nstat">
      <div class="nstat-n"><?= $total_rows - $unread_all ?></div>
      <div class="nstat-l">Read</div>
    </div>
    <div class="nstat">
      <div class="nstat-n"><?= $total_pages ?></div>
      <div class="nstat-l">Pages</div>
    </div>
  </div>

  <!-- Filter pills -->
  <div class="notif-filters">
    <?php
    $f_items = [
      'all'      => ['label'=>'All',      'icon'=>'🔔', 'count'=>$unread_all],
      'unread'   => ['label'=>'Unread',   'icon'=>'🔵', 'count'=>$unread_all],
      'task'     => ['label'=>'Tasks',    'icon'=>'✅', 'count'=>$type_counts['task']],
      'project'  => ['label'=>'Projects', 'icon'=>'📁', 'count'=>$type_counts['project']],
      'lead'     => ['label'=>'Leads',    'icon'=>'🎯', 'count'=>$type_counts['lead']],
      'meeting'  => ['label'=>'Meetings', 'icon'=>'🤝', 'count'=>$type_counts['meeting']],
      'document' => ['label'=>'Docs',     'icon'=>'📄', 'count'=>$type_counts['document']],
    ];
    foreach ($f_items as $fk => $fv):
    ?>
    <a href="notifications.php?filter=<?= $fk ?>"
       class="nf-pill <?= $filter===$fk?'active':'' ?>">
      <?= $fv['icon'] ?> <?= $fv['label'] ?>
      <?php if ($fv['count']): ?>
      <span class="cnt"><?= $fv['count'] ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- List -->
  <?php if (empty($rows)): ?>
  <div class="notif-empty">
    <div class="icon">🔔</div>
    <p style="color:var(--text3);font-size:14px;margin-bottom:4px">
      <?= $filter === 'unread' ? 'All caught up! No unread notifications.' : 'No notifications yet.' ?>
    </p>
    <p style="color:var(--text3);font-size:12.5px">You'll be notified when tasks are assigned, meetings are scheduled, and more.</p>
  </div>

  <?php else:
    // Group by date
    $grouped = [];
    foreach ($rows as $r) {
        $d = substr($r['created_at'], 0, 10);
        $grouped[$d][] = $r;
    }
  ?>
  <div class="notif-list">
  <?php foreach ($grouped as $date => $notifs):
      $today     = date('Y-m-d');
      $yesterday = date('Y-m-d', strtotime('-1 day'));
      $grp_label = ($date === $today) ? 'Today'
                 : ($date === $yesterday ? 'Yesterday'
                 : date('l, M j Y', strtotime($date)));
  ?>
    <div class="notif-group-hdr"><?= $grp_label ?></div>
    <?php foreach ($notifs as $n):
        $ni    = $NOTIF_ICONS[$n['type']] ?? ($TYPE_CFG[$n['entity_type']] ?? ['icon'=>'🔔','color'=>'#94a3b8']);
        $nc    = $ni['color'] ?? '#94a3b8';
        $nicon = $ni['icon'] ?? '🔔';
        $age   = fmtAge((int)$n['age_sec']);
        $et    = $TYPE_CFG[$n['entity_type']] ?? ['label'=>ucfirst($n['entity_type']),'color'=>'#94a3b8'];
        $link  = $n['link'] ?: '#';
        $is_unread = !$n['is_read'];
    ?>
    <div class="notif-row <?= $is_unread?'unread':'' ?>"
         style="--nc:<?= $nc ?>"
         onclick="markAndGo(<?= $n['id'] ?>, '<?= addslashes($link) ?>', this)">
      <div class="notif-icon" style="background:<?= $nc ?>15">
        <?= $nicon ?>
      </div>
      <div class="notif-body">
        <div class="notif-title"><?= h($n['title']) ?></div>
        <?php if ($n['body']): ?>
        <div class="notif-body-txt"><?= h($n['body']) ?></div>
        <?php endif; ?>
        <div class="notif-meta">
          <span style="background:<?= $nc ?>15;color:<?= $nc ?>;padding:1px 7px;border-radius:4px;font-size:11px;font-weight:600"><?= $et['label'] ?? ucfirst($n['entity_type']) ?></span>
          <span><?= $age ?></span>
          <?php if ($is_unread): ?>
          <span style="color:var(--orange);font-weight:600">● Unread</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="notif-row-actions" onclick="event.stopPropagation()">
        <?php if ($is_unread): ?>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="mark_one">
          <input type="hidden" name="nid" value="<?= $n['id'] ?>">
          <input type="hidden" name="filter" value="<?= h($filter) ?>">
          <input type="hidden" name="page" value="<?= $page ?>">
          <button class="na-btn">✓ Read</button>
        </form>
        <?php endif; ?>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="delete_one">
          <input type="hidden" name="nid" value="<?= $n['id'] ?>">
          <input type="hidden" name="filter" value="<?= h($filter) ?>">
          <input type="hidden" name="page" value="<?= $page ?>">
          <button class="na-btn" style="color:var(--text3)" onclick="return confirm('Delete this notification?')">🗑</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <div class="npag">
    <?php if ($page > 1): ?>
    <a href="notifications.php?filter=<?= $filter ?>&page=<?= $page-1 ?>">‹ Prev</a>
    <?php endif; ?>
    <?php
    for ($p = 1; $p <= $total_pages; $p++):
        if ($p === 1 || $p === $total_pages || abs($p - $page) <= 1): ?>
    <?php if ($p === $page): ?>
    <span class="cur"><?= $p ?></span>
    <?php else: ?>
    <a href="notifications.php?filter=<?= $filter ?>&page=<?= $p ?>"><?= $p ?></a>
    <?php endif; ?>
    <?php elseif (abs($p - $page) === 2): ?>
    <span class="dots">…</span>
    <?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $total_pages): ?>
    <a href="notifications.php?filter=<?= $filter ?>&page=<?= $page+1 ?>">Next ›</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div style="text-align:center;margin-top:14px;font-size:12px;color:var(--text3)">
    Showing <?= count($rows) ?> of <?= $total_rows ?> notifications
  </div>

  <?php endif; ?>
</div>

<script>
// Mark as read then navigate to link
function markAndGo(id, link, el) {
    // Optimistic UI: remove unread styling
    el.classList.remove('unread');
    // Fire mark-read silently
    fetch('notif_api.php?action=mark_read&id='+id, {method:'GET'}).catch(function(){});
    // Navigate
    if (link && link !== '#') {
        setTimeout(function(){ window.location.href = link; }, 80);
    }
}
</script>

<?php renderLayoutEnd(); ?>