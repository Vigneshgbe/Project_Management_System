<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db = getCRMDB();
$user = currentUser();

ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $id      = (int)($_POST['id'] ?? 0);
        $title   = trim($_POST['title'] ?? '');
        $desc    = trim($_POST['description'] ?? '');
        $proj    = (int)($_POST['project_id'] ?? 0) ?: null;
        $assign  = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $status  = $_POST['status'] ?? 'todo';
        $prio    = $_POST['priority'] ?? 'medium';
        $due     = $_POST['due_date'] ?: null;
        $uid     = $user['id'];
        if (!$title) { flash('Title required.','error'); ob_end_clean(); header('Location: tasks.php'); exit; }
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO tasks (title,description,project_id,assigned_to,created_by,status,priority,due_date) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssiiiiss",$title,$desc,$proj,$assign,$uid,$status,$prio,$due);
            $stmt->execute();
            logActivity('created task',$title,$db->insert_id);
            flash('Task created.','success');
        } else {
            $stmt = $db->prepare("UPDATE tasks SET title=?,description=?,project_id=?,assigned_to=?,status=?,priority=?,due_date=?,completed_at=IF(status='done',NOW(),NULL) WHERE id=?");
            $stmt->bind_param("ssiisssi",$title,$desc,$proj,$assign,$status,$prio,$due,$id);
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

$edit_id = (int)($_GET['edit'] ?? 0);
$proj_filter = (int)($_GET['project_id'] ?? 0);
$status_filter = $_GET['status'] ?? '';
$assign_filter = $_GET['assigned'] ?? '';
$search = trim($_GET['q'] ?? '');
$new_mode = isset($_GET['new']);

$where = "1=1";
if ($proj_filter) $where .= " AND t.project_id=$proj_filter";
if ($status_filter) $where .= " AND t.status='".$db->real_escape_string($status_filter)."'";
if ($assign_filter === 'me') $where .= " AND t.assigned_to=".(int)$user['id'];
elseif ($assign_filter > 0) $where .= " AND t.assigned_to=".(int)$assign_filter;
if ($search) $where .= " AND t.title LIKE '%".$db->real_escape_string($search)."%'";

$tasks = $db->query("
  SELECT t.*, p.title AS proj_title, u.name AS assignee, c.name AS creator_name
  FROM tasks t
  LEFT JOIN projects p ON p.id=t.project_id
  LEFT JOIN users u ON u.id=t.assigned_to
  LEFT JOIN users c ON c.id=t.created_by
  WHERE $where
  ORDER BY FIELD(t.status,'in_progress','review','todo','done'), FIELD(t.priority,'urgent','high','medium','low'), t.due_date ASC
")->fetch_all(MYSQLI_ASSOC);

$projects = $db->query("SELECT id,title FROM projects WHERE status NOT IN ('cancelled','completed') ORDER BY title")->fetch_all(MYSQLI_ASSOC);
$all_users = $db->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$edit_task = null;
$task_comments = [];
if ($edit_id) {
    $edit_task = $db->query("SELECT t.*,p.title AS proj_title FROM tasks t LEFT JOIN projects p ON p.id=t.project_id WHERE t.id=$edit_id")->fetch_assoc();
    $task_comments = $db->query("SELECT tc.*,u.name FROM task_comments tc JOIN users u ON u.id=tc.user_id WHERE tc.task_id=$edit_id ORDER BY tc.created_at")->fetch_all(MYSQLI_ASSOC);
}

renderLayout('Tasks', 'tasks');
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <?php if ($proj_filter): ?><input type="hidden" name="project_id" value="<?= $proj_filter ?>"><?php endif; ?>
    <div class="search-box">
      <span style="color:var(--text3)">🔍</span>
      <input type="text" name="q" placeholder="Search tasks…" value="<?= h($search) ?>">
    </div>
    <select name="status" class="form-control" style="width:auto" onchange="this.form.submit()">
      <option value="">All Status</option>
      <?php foreach (['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'] as $v=>$l): ?>
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
  </form>
  <button class="btn btn-primary" onclick="openModal('modal-task')">＋ <span>New Task</span></button>
</div>

<!-- Kanban-style status summary -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px">
  <?php
  $buckets = ['todo'=>['To Do','#6366f1'],'in_progress'=>['In Progress','#f59e0b'],'review'=>['Review','#8b5cf6'],'done'=>['Done','#10b981']];
  foreach ($buckets as $k=>[$l,$c]):
    $cnt = count(array_filter($tasks,fn($t)=>$t['status']===$k));
  ?>
  <a href="?status=<?= $k ?><?= $proj_filter?"&project_id=$proj_filter":'' ?>" style="background:var(--bg2);border:1px solid var(--border);border-top:3px solid <?= $c ?>;border-radius:var(--radius);padding:12px 14px;display:block;transition:border-color .15s" onmouseover="this.style.borderColor='<?= $c ?>'" onmouseout="this.style.borderColor='var(--border)'">
    <div style="font-size:20px;font-weight:700;color:<?= $c ?>"><?= $cnt ?></div>
    <div style="font-size:11.5px;color:var(--text2)"><?= $l ?></div>
  </a>
  <?php endforeach; ?>
</div>

<?php if (empty($tasks)): ?>
<div class="card"><div class="empty-state"><div class="icon">✅</div><p>No tasks found.</p></div></div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th style="width:36px"></th>
        <th>Task</th><th>Project</th><th>Assigned To</th><th>Status</th><th>Due</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach ($tasks as $t):
          $sc=statusColor($t['status']);
          $today = date('Y-m-d');
          $overdue = $t['due_date'] && $t['due_date'] < $today && $t['status']!=='done';
        ?>
        <tr>
          <td><?= priorityIcon($t['priority']) ?></td>
          <td class="td-main" style="<?= $overdue?"color:var(--red)":'' ?>">
            <?= h($t['title']) ?>
            <?php if ($overdue): ?><span style="font-size:10px;background:rgba(239,68,68,.1);color:var(--red);padding:1px 5px;border-radius:4px;margin-left:6px">Overdue</span><?php endif; ?>
          </td>
          <td><?= $t['proj_title'] ? '<a href="projects.php?view='.($t['project_id']).'" style="color:var(--orange);font-size:12.5px">'.h($t['proj_title']).'</a>' : '<span style="color:var(--text3)">—</span>' ?></td>
          <td><?= h($t['assignee'] ?? '—') ?></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="quick_status">
              <input type="hidden" name="id" value="<?= $t['id'] ?>">
              <select name="status" class="form-control" style="padding:4px 8px;font-size:11.5px;width:auto;background:<?= $sc ?>20;border-color:<?= $sc ?>40;color:<?= $sc ?>" onchange="this.form.submit()">
                <?php foreach (['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $t['status']===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
          <td style="<?= $overdue?'color:var(--red)':'' ?>"><?= fDate($t['due_date']) ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <button class="btn btn-ghost btn-sm btn-icon" onclick="openEditTask(<?= htmlspecialchars(json_encode($t)) ?>)" title="Edit">✎</button>
              <form method="POST" onsubmit="return confirm('Delete task?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete">🗑</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- CREATE MODAL -->
<div class="modal-overlay" id="modal-task">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="task-modal-title">New Task</div>
      <button class="modal-close" onclick="closeModal('modal-task')">✕</button>
    </div>
    <form method="POST" id="task-form">
      <input type="hidden" name="action" id="task-action" value="create">
      <input type="hidden" name="id" id="task-id" value="">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Task Title *</label>
          <input type="text" name="title" id="task-title" class="form-control" required placeholder="e.g. Design homepage mockup">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="task-desc" class="form-control"></textarea>
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
              <?php foreach (['low','medium','high','urgent'] as $p): ?>
              <option value="<?= $p ?>"><?= ucfirst($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="task-status" class="form-control">
              <?php foreach (['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'] as $v=>$l): ?>
              <option value="<?= $v ?>"><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Due Date</label>
          <input type="date" name="due_date" id="task-due" class="form-control">
        </div>
        <!-- Comments section (edit mode only) -->
        <div id="comments-section" style="display:none;margin-top:16px;border-top:1px solid var(--border);padding-top:16px">
          <div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:8px;text-transform:uppercase">Comments</div>
          <div id="comments-list" style="margin-bottom:10px;max-height:180px;overflow-y:auto;display:flex;flex-direction:column;gap:8px"></div>
          <div style="display:flex;gap:8px">
            <input type="text" id="comment-input" placeholder="Add a comment…" class="form-control" style="flex:1">
            <button type="button" class="btn btn-ghost btn-sm" onclick="submitComment()">Send</button>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-task')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="task-submit-btn">Create Task</button>
      </div>
    </form>
  </div>
</div>

<?php if ($new_mode || $proj_filter): ?>
<script>document.addEventListener('DOMContentLoaded',()=>openModal('modal-task'))</script>
<?php endif; ?>

<script>
const taskCommentData = <?= json_encode(array_map(fn($c)=>['name'=>$c['name'],'comment'=>$c['comment'],'date'=>$c['created_at']],$task_comments)) ?>;
let currentTaskId = 0;

function openEditTask(t){
  document.getElementById('task-modal-title').textContent='Edit Task';
  document.getElementById('task-action').value='edit';
  document.getElementById('task-id').value=t.id;
  document.getElementById('task-title').value=t.title||'';
  document.getElementById('task-desc').value=t.description||'';
  document.getElementById('task-project').value=t.project_id||'';
  document.getElementById('task-assign').value=t.assigned_to||'';
  document.getElementById('task-priority').value=t.priority||'medium';
  document.getElementById('task-status').value=t.status||'todo';
  document.getElementById('task-due').value=t.due_date||'';
  document.getElementById('task-submit-btn').textContent='Save Changes';
  currentTaskId=t.id;
  // Load comments via fetch
  document.getElementById('comments-section').style.display='block';
  loadComments(t.id);
  openModal('modal-task');
}
function resetTaskModal(){
  document.getElementById('task-modal-title').textContent='New Task';
  document.getElementById('task-action').value='create';
  document.getElementById('task-id').value='';
  document.getElementById('task-form').reset();
  document.getElementById('task-submit-btn').textContent='Create Task';
  document.getElementById('comments-section').style.display='none';
}
document.querySelector('[onclick="openModal(\'modal-task\')"]')?.addEventListener('click',resetTaskModal);

function loadComments(tid){
  fetch('ajax_comments.php?task_id='+tid)
    .then(r=>r.json()).then(data=>{
      const cl=document.getElementById('comments-list');
      cl.innerHTML=data.map(c=>`
        <div style="background:var(--bg3);border-radius:6px;padding:8px 10px">
          <div style="font-size:11px;color:var(--orange);font-weight:600">${c.name} <span style="color:var(--text3);font-weight:400">${c.date}</span></div>
          <div style="font-size:13px;color:var(--text2);margin-top:2px">${c.comment}</div>
        </div>`).join('');
      cl.scrollTop=cl.scrollHeight;
    }).catch(()=>{});
}
function submitComment(){
  const inp=document.getElementById('comment-input');
  const val=inp.value.trim();
  if(!val||!currentTaskId)return;
  fetch('ajax_comments.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`task_id=${currentTaskId}&comment=${encodeURIComponent(val)}&action=add`})
    .then(r=>r.json()).then(()=>{inp.value='';loadComments(currentTaskId);})
    .catch(()=>{});
}
document.getElementById('comment-input')?.addEventListener('keydown',e=>{if(e.key==='Enter')submitComment()});
</script>
<?php renderLayoutEnd(); ?>
