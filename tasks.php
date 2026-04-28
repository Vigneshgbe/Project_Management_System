<?php
require_once 'config.php';
require_once 'includes/layout.php';
require_once 'includes/attach_widget.php';
requireLogin();
$db = getCRMDB();
$user = currentUser();

// ── Ensure new columns exist (safe, runs once effectively) ──────────────────
@$db->query("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS start_date DATE DEFAULT NULL AFTER due_date");
@$db->query("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS label VARCHAR(80) DEFAULT NULL AFTER start_date");
@$db->query("ALTER TABLE tasks ADD COLUMN IF NOT EXISTS sort_order INT DEFAULT 0 AFTER label");

ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $id         = (int)($_POST['id'] ?? 0);
        $title      = trim($_POST['title'] ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $proj       = (int)($_POST['project_id'] ?? 0) ?: null;
        $assign     = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $status     = $_POST['status'] ?? 'todo';
        $prio       = $_POST['priority'] ?? 'medium';
        $due        = $_POST['due_date'] ?: null;
        $start      = $_POST['start_date'] ?: null;
        $label      = trim($_POST['label'] ?? '') ?: null;
        $uid        = $user['id'];
        if (!$title) { flash('Title required.','error'); ob_end_clean(); header('Location: tasks.php'); exit; }
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO tasks (title,description,project_id,assigned_to,created_by,status,priority,due_date,start_date,label) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssiiisssss",$title,$desc,$proj,$assign,$uid,$status,$prio,$due,$start,$label);
            $stmt->execute();
            $new_id = $db->insert_id;
            logActivity('created task',$title,$new_id);
            if ($assign && $assign !== $user['id']) {
                @include_once 'includes/mailer.php';
                $proj_row = $proj ? $db->query("SELECT title FROM projects WHERE id=$proj")->fetch_assoc() : null;
                if (function_exists('pushNotification')) pushNotification([
                    'user_id'     => $assign,
                    'type'        => 'task_assigned',
                    'entity_type' => 'task',
                    'entity_id'   => $new_id,
                    'title'       => "New task assigned: $title",
                    'body'        => 'Assigned by '.$user['name'],
                    'link'        => 'tasks.php',
                    'vars'        => [
                        'task'     => $title,
                        'project'  => $proj_row['title'] ?? 'No Project',
                        'due_date' => $due ? date('M j, Y',strtotime($due)) : 'No deadline',
                    ],
                ], $db);
            }
            flash('Task created.','success');
        } else {
            $stmt = $db->prepare("UPDATE tasks SET title=?,description=?,project_id=?,assigned_to=?,status=?,priority=?,due_date=?,start_date=?,label=?,completed_at=IF(status='done',NOW(),NULL) WHERE id=?");
            $stmt->bind_param("ssiisssssi",$title,$desc,$proj,$assign,$status,$prio,$due,$start,$label,$id);
            $stmt->execute();
            logActivity('updated task',$title,$id);
            flash('Task updated.','success');
        }
        ob_end_clean();
        $redir = $proj ? "tasks.php?project_id=$proj" : 'tasks.php';
        header('Location: '.$redir); exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM tasks WHERE id=$id AND (created_by={$user['id']} OR ".($db->real_escape_string(isManager()?'1':'0')).")");
        flash('Task deleted.','success');
        ob_end_clean(); header('Location: tasks.php'); exit;
    }

    if ($action === 'quick_status') {
        $id = (int)$_POST['id'];
        $st = $_POST['status'];
        $allowed = ['todo','in_progress','review','done'];
        if (in_array($st,$allowed)) {
            $done = $st==='done'?'NOW()':'NULL';
            $db->query("UPDATE tasks SET status='$st',completed_at=$done WHERE id=$id AND (assigned_to={$user['id']} OR created_by={$user['id']} OR 1)");
        }
        ob_end_clean(); header('Location: '.$_SERVER['HTTP_REFERER']); exit;
    }

    // NEW: Bulk actions
    if ($action === 'bulk_status') {
        $ids  = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
        $st   = $_POST['status'] ?? '';
        $allowed = ['todo','in_progress','review','done'];
        if ($ids && in_array($st, $allowed)) {
            $id_str = implode(',', $ids);
            $done   = $st === 'done' ? 'NOW()' : 'NULL';
            $db->query("UPDATE tasks SET status='$st',completed_at=$done WHERE id IN($id_str)");
        }
        ob_end_clean(); header('Location: tasks.php'); exit;
    }

    if ($action === 'bulk_delete') {
        $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
        if ($ids && isManager()) {
            $id_str = implode(',', $ids);
            $db->query("DELETE FROM tasks WHERE id IN($id_str)");
        }
        ob_end_clean(); header('Location: tasks.php'); exit;
    }

    if ($action === 'inline_edit') {
        // AJAX inline title/label edit
        header('Content-Type: application/json');
        $id    = (int)($_POST['id'] ?? 0);
        $field = $_POST['field'] ?? '';
        $val   = trim($_POST['value'] ?? '');
        $allowed_fields = ['title','label','due_date','start_date','priority','status'];
        if ($id && in_array($field, $allowed_fields)) {
            $ve = $db->real_escape_string($val ?: '');
            $db->query("UPDATE tasks SET `$field`=".($val?"'$ve'":'NULL')." WHERE id=$id");
            echo json_encode(['ok'=>true]);
        } else {
            echo json_encode(['ok'=>false]);
        }
        ob_end_clean(); exit;
    }

    if ($action === 'comment') {
        $tid = (int)$_POST['task_id'];
        $cmt = trim($_POST['comment'] ?? '');
        $uid = $user['id'];
        if ($cmt) {
            $stmt = $db->prepare("INSERT INTO task_comments (task_id,user_id,comment) VALUES (?,?,?)");
            $stmt->bind_param("iis",$tid,$uid,$cmt);
            $stmt->execute();
        }
        ob_end_clean(); header('Location: tasks.php?edit='.$tid); exit;
    }
}
ob_end_clean();

$edit_id       = (int)($_GET['edit'] ?? 0);
$proj_filter   = (int)($_GET['project_id'] ?? 0);
$status_filter = $_GET['status'] ?? '';
$assign_filter = $_GET['assigned'] ?? '';
$search        = trim($_GET['q'] ?? '');
$new_mode      = isset($_GET['new']);
$group_by      = $_GET['group'] ?? 'status'; // status|priority|project|label
$view_mode     = $_GET['view'] ?? 'table';   // table|board

$where = "1=1";
if ($proj_filter)               $where .= " AND t.project_id=$proj_filter";
if ($status_filter)             $where .= " AND t.status='".$db->real_escape_string($status_filter)."'";
if ($assign_filter === 'me')    $where .= " AND t.assigned_to=".(int)$user['id'];
elseif ((int)$assign_filter>0)  $where .= " AND t.assigned_to=".(int)$assign_filter;
if ($search)                    $where .= " AND t.title LIKE '%".$db->real_escape_string($search)."%'";

// Label filter (new)
$label_filter = trim($_GET['label'] ?? '');
if ($label_filter) $where .= " AND t.label='".$db->real_escape_string($label_filter)."'";

$tasks = $db->query("
  SELECT t.*, p.title AS proj_title, u.name AS assignee, c.name AS creator_name
  FROM tasks t
  LEFT JOIN projects p ON p.id=t.project_id
  LEFT JOIN users u ON u.id=t.assigned_to
  LEFT JOIN users c ON c.id=t.created_by
  WHERE $where
  ORDER BY FIELD(t.status,'in_progress','review','todo','done'),
           FIELD(t.priority,'urgent','high','medium','low'),
           t.due_date ASC
")->fetch_all(MYSQLI_ASSOC);

$projects  = $db->query("SELECT id,title FROM projects WHERE status NOT IN ('cancelled','completed') ORDER BY title")->fetch_all(MYSQLI_ASSOC);
$all_users = $db->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Distinct labels for filter dropdown
$label_rows = $db->query("SELECT DISTINCT label FROM tasks WHERE label IS NOT NULL AND label!='' ORDER BY label")->fetch_all(MYSQLI_ASSOC);
$all_labels = array_column($label_rows, 'label');

$edit_task     = null;
$task_comments = [];
if ($edit_id) {
    $edit_task     = $db->query("SELECT t.*,p.title AS proj_title FROM tasks t LEFT JOIN projects p ON p.id=t.project_id WHERE t.id=$edit_id")->fetch_assoc();
    $task_comments = $db->query("SELECT tc.*,u.name FROM task_comments tc JOIN users u ON u.id=tc.user_id WHERE tc.task_id=$edit_id ORDER BY tc.created_at")->fetch_all(MYSQLI_ASSOC);
}

// Group tasks helper
function groupTasks(array $tasks, string $by): array {
    $groups = [];
    foreach ($tasks as $t) {
        $key = match($by) {
            'priority' => $t['priority'] ?? 'medium',
            'project'  => $t['proj_title'] ?? '— No Project —',
            'label'    => $t['label'] ?: '— No Label —',
            default    => $t['status'],
        };
        $groups[$key][] = $t;
    }
    return $groups;
}

// Priority display labels (Notion-style)
$PRIO_LABELS = [
    'urgent' => '1st · Urgent',
    'high'   => '2nd · High',
    'medium' => '3rd · Medium',
    'low'    => '4th · Low',
];
$PRIO_COLORS = [
    'urgent' => '#ef4444',
    'high'   => '#f97316',
    'medium' => '#f59e0b',
    'low'    => '#10b981',
];
$STATUS_LABELS = [
    'todo'        => 'To Do',
    'in_progress' => 'In Progress',
    'review'      => 'Review',
    'done'        => 'Done',
];
$STATUS_COLORS = [
    'todo'        => '#6366f1',
    'in_progress' => '#f59e0b',
    'review'      => '#8b5cf6',
    'done'        => '#10b981',
];

renderLayout('Tasks', 'tasks');
?>
<style>
/* ── Task-page additions (no existing vars overridden) ── */
.tk-toolbar{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px;flex-wrap:wrap}
.tk-left{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.tk-right{display:flex;gap:8px;align-items:center;flex-wrap:wrap}

/* View toggle pills */
.tk-view-toggle{display:flex;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden}
.tk-view-toggle a{padding:5px 13px;font-size:12px;font-weight:600;color:var(--text3);text-decoration:none;transition:all .15s;white-space:nowrap}
.tk-view-toggle a.active,.tk-view-toggle a:hover{background:var(--orange);color:#fff}

/* Group-by selector */
.tk-groupby{display:flex;align-items:center;gap:5px;font-size:12px;color:var(--text3)}
.tk-groupby select{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:4px 8px;font-size:12px;color:var(--text2);cursor:pointer}

/* Status buckets */
.tk-buckets{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px}
.tk-bucket{background:var(--bg2);border:1px solid var(--border);border-top:3px solid var(--bc);border-radius:var(--radius);padding:12px 14px;display:block;text-decoration:none;transition:box-shadow .15s}
.tk-bucket:hover{box-shadow:0 0 0 2px var(--bc)}
.tk-bucket-num{font-size:22px;font-weight:800;color:var(--bc)}
.tk-bucket-lbl{font-size:11.5px;color:var(--text2);margin-top:2px}

/* Status pill */
.tk-status{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:700;white-space:nowrap}

/* Priority pill (Notion-style) */
.tk-prio{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;white-space:nowrap}

/* Label pill */
.tk-label{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;background:rgba(99,102,241,.12);color:#6366f1;white-space:nowrap;max-width:100px;overflow:hidden;text-overflow:ellipsis}

/* Table enhancements */
.tk-table th{font-size:10.5px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;user-select:none;cursor:pointer}
.tk-table th:hover{color:var(--orange)}
.tk-table th.sort-asc::after{content:' ↑';color:var(--orange)}
.tk-table th.sort-desc::after{content:' ↓';color:var(--orange)}
.tk-table td{vertical-align:middle}
.tk-check{width:16px;height:16px;cursor:pointer;accent-color:var(--orange)}
.tk-overdue{color:var(--red)!important}
.tk-overdue-badge{font-size:10px;background:rgba(239,68,68,.1);color:var(--red);padding:1px 5px;border-radius:4px;margin-left:6px}

/* Group header rows */
.tk-group-row td{background:var(--bg3);font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;padding:6px 12px;border-bottom:1px solid var(--border)}

/* Inline edit: hover to show edit affordance */
.tk-editable{cursor:text;border-bottom:1px dashed transparent;transition:border-color .15s;border-radius:2px}
.tk-editable:hover{border-bottom-color:var(--orange)}

/* Bulk action bar */
.tk-bulk-bar{display:none;align-items:center;gap:10px;padding:8px 14px;background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.3);border-radius:var(--radius);margin-bottom:10px;flex-wrap:wrap}
.tk-bulk-bar.visible{display:flex}
.tk-bulk-count{font-size:13px;font-weight:700;color:var(--orange)}

/* Board view (Kanban columns) */
.tk-board{display:flex;gap:14px;overflow-x:auto;padding-bottom:12px;min-height:300px;align-items:flex-start}
.tk-board::-webkit-scrollbar{height:6px}
.tk-board::-webkit-scrollbar-thumb{background:var(--border);border-radius:99px}
.tk-col{min-width:240px;width:240px;background:var(--bg2);border:1px solid var(--border);border-top:3px solid var(--cc);border-radius:var(--radius);flex-shrink:0}
.tk-col-head{padding:10px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.tk-col-title{font-size:13px;font-weight:700;color:var(--cc)}
.tk-col-badge{background:var(--cc);color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px}
.tk-col-body{padding:8px;display:flex;flex-direction:column;gap:7px;max-height:65vh;overflow-y:auto}
.tk-card{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 12px;cursor:pointer;transition:box-shadow .15s}
.tk-card:hover{box-shadow:0 2px 8px rgba(0,0,0,.12);border-color:var(--orange)}
.tk-card-title{font-size:13px;font-weight:600;color:var(--text);margin-bottom:5px;line-height:1.4}
.tk-card-meta{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:6px}
.tk-card-proj{font-size:11px;color:var(--orange);font-weight:500}

/* Dates display */
.tk-dates{font-size:11.5px;color:var(--text3);white-space:nowrap}

/* Empty state inside group */
.tk-group-empty{text-align:center;padding:14px;font-size:12px;color:var(--text3)}

@media(max-width:700px){
  .tk-buckets{grid-template-columns:repeat(2,1fr)}
  .tk-toolbar{flex-direction:column;align-items:stretch}
  .tk-board{flex-direction:column}
  .tk-col{min-width:100%;width:100%}
}
</style>

<?php
// ── Status bucket counts ─────────────────────────────────────────────────────
$buckets = [
    'todo'        => ['To Do',       '#6366f1'],
    'in_progress' => ['In Progress', '#f59e0b'],
    'review'      => ['Review',      '#8b5cf6'],
    'done'        => ['Done',        '#10b981'],
];
?>
<div class="tk-buckets">
<?php foreach ($buckets as $k => [$l, $c]):
    $cnt = count(array_filter($tasks, fn($t) => $t['status'] === $k));
    $active = ($status_filter === $k) ? "box-shadow:0 0 0 2px {$c}" : '';
    $href = '?status='.$k.($proj_filter?"&project_id=$proj_filter":'');
?>
  <a href="<?= $href ?>" class="tk-bucket" style="--bc:<?= $c ?>;<?= $active ?>">
    <div class="tk-bucket-num"><?= $cnt ?></div>
    <div class="tk-bucket-lbl"><?= $l ?></div>
  </a>
<?php endforeach; ?>
</div>

<!-- ── TOOLBAR ─────────────────────────────────────────────────────────────── -->
<div class="tk-toolbar">
  <form method="GET" class="tk-left" id="filter-form">
    <?php if ($proj_filter): ?>
    <input type="hidden" name="project_id" value="<?= $proj_filter ?>">
    <?php endif; ?>
    <input type="hidden" name="view"  value="<?= h($view_mode) ?>">
    <input type="hidden" name="group" value="<?= h($group_by) ?>">

    <div class="search-box">
      <span style="color:var(--text3)">🔍</span>
      <input type="text" name="q" placeholder="Search tasks…" value="<?= h($search) ?>">
    </div>

    <select name="status" class="form-control" style="width:auto" onchange="this.form.submit()">
      <option value="">All Status</option>
      <?php foreach ($STATUS_LABELS as $v => $l): ?>
      <option value="<?= $v ?>" <?= $status_filter===$v?'selected':'' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>

    <select name="assigned" class="form-control" style="width:auto" onchange="this.form.submit()">
      <option value="">All Members</option>
      <option value="me" <?= $assign_filter==='me'?'selected':'' ?>>My Tasks</option>
      <?php foreach ($all_users as $u): ?>
      <option value="<?= $u['id'] ?>" <?= $assign_filter==$u['id']?'selected':'' ?>><?= h($u['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <?php if ($all_labels): ?>
    <select name="label" class="form-control" style="width:auto" onchange="this.form.submit()">
      <option value="">All Labels</option>
      <?php foreach ($all_labels as $lb): ?>
      <option value="<?= h($lb) ?>" <?= $label_filter===$lb?'selected':'' ?>><?= h($lb) ?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
  </form>

  <div class="tk-right">
    <!-- Group by -->
    <div class="tk-groupby">
      <span>Group:</span>
      <select onchange="applyGroupBy(this.value)">
        <option value="status"   <?= $group_by==='status'  ?'selected':'' ?>>Status</option>
        <option value="priority" <?= $group_by==='priority'?'selected':'' ?>>Priority</option>
        <option value="project"  <?= $group_by==='project' ?'selected':'' ?>>Project</option>
        <option value="label"    <?= $group_by==='label'   ?'selected':'' ?>>Label</option>
      </select>
    </div>
    <!-- View toggle -->
    <?php
    // Strip 'new' so switching view never re-triggers the create-modal auto-open
    $vq = array_diff_key($_GET, ['new'=>1,'view'=>1]);
    ?>
    <div class="tk-view-toggle">
      <a href="?<?= http_build_query(array_merge($vq,['view'=>'table'])) ?>" class="<?= $view_mode==='table'?'active':'' ?>">≡ Table</a>
      <a href="?<?= http_build_query(array_merge($vq,['view'=>'board'])) ?>" class="<?= $view_mode==='board'?'active':'' ?>">⊞ Board</a>
    </div>
    <button class="btn btn-primary" onclick="openNewTask()">＋ New Task</button>
  </div>
</div>

<!-- ── BULK ACTION BAR ────────────────────────────────────────────────────── -->
<div class="tk-bulk-bar" id="bulk-bar">
  <span class="tk-bulk-count" id="bulk-count">0 selected</span>
  <form method="POST" id="bulk-form" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
    <input type="hidden" name="ids"    id="bulk-ids">
    <input type="hidden" name="action" id="bulk-action-field">
    <select name="status" class="form-control" style="width:auto;padding:4px 8px;font-size:12px" id="bulk-status">
      <?php foreach ($STATUS_LABELS as $v => $l): ?>
      <option value="<?= $v ?>"><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" class="btn btn-ghost btn-sm" onclick="doBulk('bulk_status')">Set Status</button>
    <?php if (isManager()): ?>
    <button type="button" class="btn btn-danger btn-sm" onclick="if(confirm('Delete selected tasks?'))doBulk('bulk_delete')">Delete</button>
    <?php endif; ?>
    <button type="button" class="btn btn-ghost btn-sm" onclick="clearSelection()">✕ Clear</button>
  </form>
</div>

<?php if (empty($tasks)): ?>
<div class="card"><div class="empty-state"><div class="icon">✅</div><p>No tasks found.</p></div></div>

<?php elseif ($view_mode === 'board'): ?>
<!-- ── BOARD VIEW ─────────────────────────────────────────────────────────── -->
<div class="tk-board">
<?php
$board_groups = groupTasks($tasks, $group_by);
$group_order = ($group_by === 'status')
    ? array_keys($STATUS_LABELS)
    : (($group_by === 'priority') ? array_keys($PRIO_LABELS) : array_keys($board_groups));
// Ensure all keys covered
foreach (array_keys($board_groups) as $k) { if (!in_array($k,$group_order)) $group_order[] = $k; }
foreach ($group_order as $gk):
    if (!isset($board_groups[$gk])) continue;
    $gcards = $board_groups[$gk];
    $cc = ($group_by==='status') ? ($STATUS_COLORS[$gk] ?? '#94a3b8')
        : (($group_by==='priority') ? ($PRIO_COLORS[$gk] ?? '#94a3b8') : '#6366f1');
    $glabel = ($group_by==='status') ? ($STATUS_LABELS[$gk] ?? $gk)
        : (($group_by==='priority') ? ($PRIO_LABELS[$gk] ?? $gk) : $gk);
?>
  <div class="tk-col" style="--cc:<?= $cc ?>">
    <div class="tk-col-head">
      <span class="tk-col-title"><?= h($glabel) ?></span>
      <span class="tk-col-badge"><?= count($gcards) ?></span>
    </div>
    <div class="tk-col-body">
      <?php if (empty($gcards)): ?>
      <div class="tk-group-empty">No tasks</div>
      <?php endif; ?>
      <?php foreach ($gcards as $t):
        $today   = date('Y-m-d');
        $overdue = $t['due_date'] && $t['due_date'] < $today && $t['status'] !== 'done';
        $sc      = $STATUS_COLORS[$t['status']] ?? '#94a3b8';
        $pc      = $PRIO_COLORS[$t['priority']] ?? '#94a3b8';
      ?>
      <div class="tk-card" onclick="openEditTask(<?= htmlspecialchars(json_encode($t)) ?>)">
        <div class="tk-card-title <?= $overdue?'tk-overdue':'' ?>"><?= h($t['title']) ?></div>
        <?php if ($t['proj_title']): ?>
        <div class="tk-card-proj">📁 <?= h($t['proj_title']) ?></div>
        <?php endif; ?>
        <div class="tk-card-meta">
          <span class="tk-status" style="background:<?= $sc ?>18;color:<?= $sc ?>"><?= $STATUS_LABELS[$t['status']] ?? $t['status'] ?></span>
          <span class="tk-prio" style="background:<?= $pc ?>18;color:<?= $pc ?>"><?= $PRIO_LABELS[$t['priority']] ?? ucfirst($t['priority'] ?? '') ?></span>
          <?php if ($t['due_date']): ?>
          <span class="tk-dates <?= $overdue?'tk-overdue':'' ?>">📅 <?= fDate($t['due_date']) ?></span>
          <?php endif; ?>
        </div>
        <?php if ($t['assignee']): ?>
        <div style="font-size:11px;color:var(--text3);margin-top:5px">👤 <?= h($t['assignee']) ?></div>
        <?php endif; ?>
        <?php if ($t['label']): ?>
        <div style="margin-top:5px"><span class="tk-label"><?= h($t['label']) ?></span></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php else: ?>
<!-- ── TABLE VIEW ────────────────────────────────────────────────────────── -->
<div class="card">
  <div class="table-wrap">
    <table id="tasks-table" class="tk-table">
      <thead>
        <tr>
          <th style="width:32px"><input type="checkbox" class="tk-check" id="check-all" onchange="toggleAll(this)"></th>
          <th style="width:28px"></th>
          <th onclick="sortTable(2)" id="th-2">Task</th>
          <th onclick="sortTable(3)" id="th-3">Project</th>
          <th onclick="sortTable(4)" id="th-4">Assigned To</th>
          <th onclick="sortTable(5)" id="th-5">Status</th>
          <th onclick="sortTable(6)" id="th-6">Priority</th>
          <th onclick="sortTable(7)" id="th-7">Label</th>
          <th onclick="sortTable(8)" id="th-8">Start</th>
          <th onclick="sortTable(9)" id="th-9">Due</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
<?php
$grouped = groupTasks($tasks, $group_by);
// Determine group display order
$order = ($group_by === 'status')
    ? array_keys($STATUS_LABELS)
    : (($group_by === 'priority') ? array_keys($PRIO_LABELS) : array_keys($grouped));
foreach (array_keys($grouped) as $k) { if (!in_array($k, $order)) $order[] = $k; }

foreach ($order as $gk):
    if (!isset($grouped[$gk])) continue;
    $gtasks = $grouped[$gk];
    $gc = ($group_by==='status') ? ($STATUS_COLORS[$gk] ?? '#94a3b8')
        : (($group_by==='priority') ? ($PRIO_COLORS[$gk] ?? '#94a3b8') : '#6366f1');
    $glabel = ($group_by==='status') ? ($STATUS_LABELS[$gk] ?? $gk)
        : (($group_by==='priority') ? ($PRIO_LABELS[$gk] ?? $gk) : $gk);
?>
        <tr class="tk-group-row" data-group="<?= h($gk) ?>">
          <td colspan="11">
            <span style="color:<?= $gc ?>;margin-right:6px">●</span>
            <?= h($glabel) ?>
            <span style="margin-left:6px;opacity:.5">(<?= count($gtasks) ?>)</span>
          </td>
        </tr>
<?php foreach ($gtasks as $t):
    $sc      = $STATUS_COLORS[$t['status']] ?? '#94a3b8';
    $pc      = $PRIO_COLORS[$t['priority']] ?? '#94a3b8';
    $today   = date('Y-m-d');
    $overdue = $t['due_date'] && $t['due_date'] < $today && $t['status'] !== 'done';
?>
        <tr data-id="<?= $t['id'] ?>" data-group="<?= h($gk) ?>">
          <td><input type="checkbox" class="tk-check row-check" value="<?= $t['id'] ?>" onchange="updBulk()"></td>
          <td><?= priorityIcon($t['priority']) ?></td>
          <td class="td-main">
            <span class="tk-editable" data-id="<?= $t['id'] ?>" data-field="title"
                  ondblclick="inlineEdit(this)"
                  style="<?= $overdue?'color:var(--red)':'' ?>"><?= h($t['title']) ?></span>
            <?php if ($overdue): ?><span class="tk-overdue-badge">Overdue</span><?php endif; ?>
            <?php if ($t['description']): ?>
            <div style="font-size:11px;color:var(--text3);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px"><?= h(mb_substr($t['description'],0,80)) ?><?= mb_strlen($t['description'])>80?'…':'' ?></div>
            <?php endif; ?>
          </td>
          <td><?= $t['proj_title'] ? '<a href="projects.php?view='.h($t['project_id']).'" style="color:var(--orange);font-size:12.5px">'.h($t['proj_title']).'</a>' : '<span style="color:var(--text3)">—</span>' ?></td>
          <td><?= h($t['assignee'] ?? '—') ?></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="quick_status">
              <input type="hidden" name="id"     value="<?= $t['id'] ?>">
              <select name="status" class="form-control"
                style="padding:3px 7px;font-size:11.5px;width:auto;background:<?= $sc ?>18;border-color:<?= $sc ?>40;color:<?= $sc ?>;border-radius:99px;font-weight:600"
                onchange="this.form.submit()">
                <?php foreach ($STATUS_LABELS as $v => $l): ?>
                <option value="<?= $v ?>" <?= $t['status']===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
          <td>
            <span class="tk-prio" style="background:<?= $pc ?>18;color:<?= $pc ?>">
              <?= $PRIO_LABELS[$t['priority']] ?? ucfirst($t['priority'] ?? '') ?>
            </span>
          </td>
          <td>
            <?php if ($t['label']): ?>
            <span class="tk-label tk-editable" data-id="<?= $t['id'] ?>" data-field="label" ondblclick="inlineEdit(this)"><?= h($t['label']) ?></span>
            <?php else: ?>
            <span style="color:var(--text3);font-size:11.5px;cursor:pointer" onclick="inlineEditById(<?= $t['id'] ?>,'label','')" title="Add label">+ label</span>
            <?php endif; ?>
          </td>
          <td class="tk-dates"><?= $t['start_date'] ? fDate($t['start_date']) : '<span style="color:var(--text3)">—</span>' ?></td>
          <td class="tk-dates <?= $overdue?'tk-overdue':'' ?>"><?= fDate($t['due_date']) ?></td>
          <td>
            <div style="display:flex;gap:5px">
              <button class="btn btn-ghost btn-sm btn-icon" onclick="openEditTask(<?= htmlspecialchars(json_encode($t)) ?>)" title="Edit">✎</button>
              <form method="POST" onsubmit="return confirm('Delete task?')" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= $t['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete">🗑</button>
              </form>
            </div>
          </td>
        </tr>
<?php endforeach; endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ── CREATE / EDIT MODAL ───────────────────────────────────────────────── -->
<div class="modal-overlay" id="modal-task">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="task-modal-title">New Task</div>
      <button class="modal-close" onclick="closeModal('modal-task')">✕</button>
    </div>
    <form method="POST" id="task-form">
      <input type="hidden" name="action" id="task-action" value="create">
      <input type="hidden" name="id"     id="task-id"     value="">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Task Title *</label>
          <input type="text" name="title" id="task-title" class="form-control" required placeholder="e.g. Design homepage mockup">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="task-desc" class="form-control" rows="3"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Project</label>
            <select name="project_id" id="task-project" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($projects as $p): ?>
              <option value="<?= $p['id'] ?>" <?= $proj_filter==$p['id']?'selected':'' ?>><?= h($p['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Assign To</label>
            <select name="assigned_to" id="task-assign" class="form-control">
              <option value="">— Unassigned —</option>
              <?php foreach ($all_users as $u): ?>
              <option value="<?= $u['id'] ?>"><?= h($u['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Priority</label>
            <select name="priority" id="task-priority" class="form-control">
              <?php foreach (['urgent'=>'1st · Urgent','high'=>'2nd · High','medium'=>'3rd · Medium','low'=>'4th · Low'] as $v=>$l): ?>
              <option value="<?= $v ?>"><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="task-status" class="form-control">
              <?php foreach ($STATUS_LABELS as $v=>$l): ?>
              <option value="<?= $v ?>"><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" id="task-start" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Due Date</label>
            <input type="date" name="due_date" id="task-due" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Label / Team <span style="font-size:10px;color:var(--text3)">(e.g. Human Resources, Product Design)</span></label>
          <input type="text" name="label" id="task-label" class="form-control" placeholder="e.g. Business Development" list="label-suggestions">
          <datalist id="label-suggestions">
            <?php foreach ($all_labels as $lb): ?><option value="<?= h($lb) ?>"><?php endforeach; ?>
          </datalist>
        </div>

        <!-- Comments (edit mode) -->
        <div id="comments-section" style="display:none;margin-top:16px;border-top:1px solid var(--border);padding-top:16px">
          <div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:8px;text-transform:uppercase">Comments</div>
          <div id="comments-list" style="margin-bottom:10px;max-height:180px;overflow-y:auto;display:flex;flex-direction:column;gap:8px"></div>
          <div style="display:flex;gap:8px">
            <input type="text" id="comment-input" placeholder="Add a comment…" class="form-control" style="flex:1">
            <button type="button" class="btn btn-ghost btn-sm" onclick="submitComment()">Send</button>
          </div>
        </div>

        <!-- Attachments (edit mode) -->
        <div id="task-attach-section" style="display:none">
          <?php if ($edit_id): renderAttachWidget('task', $edit_id); endif; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-task')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="task-submit-btn">Create Task</button>
      </div>
    </form>
  </div>
</div>

<?php if ($new_mode): ?>
<script>document.addEventListener('DOMContentLoaded',()=>openModal('modal-task'))</script>
<?php endif; ?>

<script>
// ── Modal helpers ───────────────────────────────────────────────────────────
let currentTaskId = 0;

function openNewTask() {
    document.getElementById('task-modal-title').textContent = 'New Task';
    document.getElementById('task-action').value = 'create';
    document.getElementById('task-id').value = '';
    document.getElementById('task-form').reset();
    document.getElementById('task-submit-btn').textContent = 'Create Task';
    document.getElementById('comments-section').style.display = 'none';
    document.getElementById('task-attach-section').style.display = 'none';
    openModal('modal-task');
}

function openEditTask(t) {
    document.getElementById('task-modal-title').textContent = 'Edit Task';
    document.getElementById('task-action').value = 'edit';
    document.getElementById('task-id').value    = t.id;
    document.getElementById('task-title').value  = t.title   || '';
    document.getElementById('task-desc').value   = t.description || '';
    document.getElementById('task-project').value = t.project_id || '';
    document.getElementById('task-assign').value  = t.assigned_to || '';
    document.getElementById('task-priority').value = t.priority || 'medium';
    document.getElementById('task-status').value   = t.status   || 'todo';
    document.getElementById('task-due').value      = t.due_date  || '';
    document.getElementById('task-start').value    = t.start_date || '';
    document.getElementById('task-label').value    = t.label || '';
    document.getElementById('task-submit-btn').textContent = 'Save Changes';
    currentTaskId = t.id;
    document.getElementById('comments-section').style.display = 'block';
    document.getElementById('task-attach-section').style.display = 'block';
    loadComments(t.id);
    openModal('modal-task');
}

// ── Comments ────────────────────────────────────────────────────────────────
function loadComments(tid) {
    fetch('ajax_comments.php?task_id=' + tid)
        .then(r => r.json())
        .then(data => {
            const cl = document.getElementById('comments-list');
            cl.innerHTML = data.map(c => `
                <div style="background:var(--bg3);border-radius:6px;padding:8px 10px">
                  <div style="font-size:11px;color:var(--orange);font-weight:600">${c.name}
                    <span style="color:var(--text3);font-weight:400">${c.date}</span></div>
                  <div style="font-size:13px;color:var(--text2);margin-top:2px">${c.comment}</div>
                </div>`).join('');
            cl.scrollTop = cl.scrollHeight;
        }).catch(() => {});
}
function submitComment() {
    const inp = document.getElementById('comment-input');
    const val = inp.value.trim();
    if (!val || !currentTaskId) return;
    fetch('ajax_comments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `task_id=${currentTaskId}&comment=${encodeURIComponent(val)}&action=add`
    }).then(r => r.json()).then(() => { inp.value = ''; loadComments(currentTaskId); }).catch(() => {});
}
document.getElementById('comment-input')?.addEventListener('keydown', e => { if (e.key === 'Enter') submitComment(); });

// ── Inline editing (double-click on title or label cell) ────────────────────
function inlineEdit(el) {
    const id    = el.dataset.id;
    const field = el.dataset.field;
    inlineEditById(id, field, el.textContent.trim(), el);
}
function inlineEditById(id, field, currentVal, el) {
    const input = document.createElement('input');
    input.type  = 'text';
    input.value = currentVal;
    input.className = 'form-control';
    input.style.cssText = 'font-size:13px;padding:2px 6px;height:26px;width:160px;display:inline-block';

    const container = el || document.querySelector(`[data-id="${id}"][data-field="${field}"]`);
    const orig = container ? container.textContent.trim() : currentVal;
    if (container) { container.style.display = 'none'; container.parentNode.insertBefore(input, container); }

    function save() {
        const newVal = input.value.trim();
        fetch('tasks.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=inline_edit&id=${id}&field=${field}&value=${encodeURIComponent(newVal)}`
        }).then(r => r.json()).then(d => {
            if (d.ok) {
                if (container) { container.textContent = newVal || orig; container.style.display = ''; }
                input.remove();
            }
        }).catch(() => { if (container) { container.style.display = ''; } input.remove(); });
    }
    input.addEventListener('blur', save);
    input.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); input.blur(); } if (e.key === 'Escape') { if (container) container.style.display = ''; input.remove(); }});
    input.focus();
    input.select();
}

// ── Bulk selection ──────────────────────────────────────────────────────────
function toggleAll(cb) {
    document.querySelectorAll('.row-check').forEach(c => c.checked = cb.checked);
    updBulk();
}
function updBulk() {
    const checked = [...document.querySelectorAll('.row-check:checked')];
    const bar     = document.getElementById('bulk-bar');
    document.getElementById('bulk-count').textContent = checked.length + ' selected';
    bar.classList.toggle('visible', checked.length > 0);
    const allCb = document.getElementById('check-all');
    const all   = document.querySelectorAll('.row-check').length;
    if (allCb) { allCb.checked = checked.length === all && all > 0; allCb.indeterminate = checked.length > 0 && checked.length < all; }
}
function clearSelection() {
    document.querySelectorAll('.row-check,#check-all').forEach(c => { c.checked = false; c.indeterminate = false; });
    updBulk();
}
function doBulk(action) {
    const ids = [...document.querySelectorAll('.row-check:checked')].map(c => c.value);
    if (!ids.length) return;
    document.getElementById('bulk-ids').value       = ids.join(',');
    document.getElementById('bulk-action-field').value = action;
    document.getElementById('bulk-form').submit();
}

// ── Client-side column sort ──────────────────────────────────────────────────
let sortCol = -1, sortDir = 1;
function sortTable(col) {
    const table = document.getElementById('tasks-table');
    if (!table) return;
    const tbody = table.tBodies[0];
    // Collect non-group rows
    const rows  = [...tbody.rows].filter(r => !r.classList.contains('tk-group-row'));
    sortDir = (sortCol === col) ? -sortDir : 1;
    sortCol = col;
    rows.sort((a, b) => {
        const at = (a.cells[col]?.textContent || '').trim();
        const bt = (b.cells[col]?.textContent || '').trim();
        return at.localeCompare(bt, undefined, {numeric:true}) * sortDir;
    });
    // Rebuild tbody: re-insert rows after their group header
    rows.forEach(r => tbody.appendChild(r));
    // Update sort indicators
    document.querySelectorAll('.tk-table th').forEach((th, i) => {
        th.classList.remove('sort-asc','sort-desc');
        if (i === col) th.classList.add(sortDir === 1 ? 'sort-asc' : 'sort-desc');
    });
}

// ── Group-by ────────────────────────────────────────────────────────────────
function applyGroupBy(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('group', val);
    window.location.href = url.toString();
}
</script>
<?php renderLayoutEnd(); ?>