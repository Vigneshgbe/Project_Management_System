<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$user = currentUser();
$db   = getCRMDB();
$uid  = (int)$user['id'];

// ── STATS (role-aware) ──
$s = [];
$s['projects']  = $db->query("SELECT COUNT(*) FROM projects WHERE status NOT IN ('cancelled')")->fetch_row()[0];
$s['active_p']  = $db->query("SELECT COUNT(*) FROM projects WHERE status='active'")->fetch_row()[0];
$s['open_tasks']= $db->query("SELECT COUNT(*) FROM tasks WHERE status!='done'")->fetch_row()[0];
$s['my_tasks']  = $db->query("SELECT COUNT(*) FROM tasks WHERE assigned_to=$uid AND status!='done'")->fetch_row()[0];
$s['contacts']  = $db->query("SELECT COUNT(*) FROM contacts")->fetch_row()[0];
$s['docs']      = $db->query("SELECT COUNT(*) FROM documents")->fetch_row()[0] + $db->query("SELECT COUNT(*) FROM rich_docs")->fetch_row()[0];

if (isManager()) {
    $s['leads']      = $db->query("SELECT COUNT(*) FROM leads WHERE stage NOT IN ('won','lost')")->fetch_row()[0];
    $s['hot_leads']  = $db->query("SELECT COUNT(*) FROM leads WHERE stage IN ('proposal','negotiation')")->fetch_row()[0];
    // Current month expense total
    $cur_month = date('Y-m');
    $exp_row = $db->query("SELECT COALESCE(SUM(e.own_spend+e.office_spend),0) AS total FROM expense_entries e JOIN expense_months m ON m.id=e.month_id WHERE m.month_year='$cur_month'")->fetch_assoc();
    $s['month_expense'] = (float)($exp_row['total'] ?? 0);
}

// ── MY TASKS ──
$my_tasks = $db->query("
    SELECT t.*, p.title AS proj_title FROM tasks t
    LEFT JOIN projects p ON p.id=t.project_id
    WHERE t.assigned_to=$uid AND t.status!='done'
    ORDER BY FIELD(t.priority,'urgent','high','medium','low'), t.due_date ASC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// ── TASK STATUS COUNTS ──
$task_rows = $db->query("SELECT status, COUNT(*) AS cnt FROM tasks GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$tcounts = ['todo'=>0,'in_progress'=>0,'review'=>0,'done'=>0];
foreach ($task_rows as $r) $tcounts[$r['status']] = (int)$r['cnt'];
$total_t = array_sum($tcounts);

// ── RECENT PROJECTS (all roles) ──
$projects = $db->query("
    SELECT p.*, c.name AS client_name,
        (SELECT COUNT(*) FROM tasks WHERE project_id=p.id AND status='done') AS done_tasks,
        (SELECT COUNT(*) FROM tasks WHERE project_id=p.id) AS total_tasks,
        p.progress
    FROM projects p
    LEFT JOIN contacts c ON c.id=p.contact_id
    ORDER BY p.updated_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// ── RECENT ACTIVITY (admin/manager) ──
$activity = [];
if (isManager()) {
    $activity = $db->query("
        SELECT a.action, a.created_at, u.name AS uname
        FROM activity_log a LEFT JOIN users u ON u.id=a.user_id
        ORDER BY a.created_at DESC LIMIT 8
    ")->fetch_all(MYSQLI_ASSOC);
}

// ── OVERDUE TASKS ──
$overdue = $db->query("
    SELECT t.title, t.due_date, t.priority, u.name AS assignee
    FROM tasks t LEFT JOIN users u ON u.id=t.assigned_to
    WHERE t.due_date < CURDATE() AND t.status!='done'
    ORDER BY t.due_date ASC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// ── LEAD PIPELINE (manager+) ──
$pipeline = [];
if (isManager()) {
    $pipeline = $db->query("
        SELECT stage, COUNT(*) AS cnt, COALESCE(SUM(budget_est),0) AS val
        FROM leads WHERE stage NOT IN ('won','lost')
        GROUP BY stage ORDER BY FIELD(stage,'new','contacted','qualified','proposal','negotiation')
    ")->fetch_all(MYSQLI_ASSOC);
}

renderLayout('Dashboard', 'dashboard');
?>

<style>
.db-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}
.db-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;margin-bottom:18px}
.task-item{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:11px 13px;display:flex;align-items:flex-start;gap:10px;transition:border-color .15s}
.task-item:hover{border-color:var(--border2)}
.proj-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)}
.proj-row:last-child{border-bottom:none}
.act-row{display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border)}
.act-row:last-child{border-bottom:none}
.ov-item{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid var(--border)}
.ov-item:last-child{border-bottom:none}
.pipe-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)}
.pipe-row:last-child{border-bottom:none}
@media(max-width:900px){.db-grid,.db-grid-3{grid-template-columns:1fr}}
</style>

<?php
// ── STAT CARDS (role-aware) ──
$kpis = [
    ['📁','Total Projects', $s['projects'],     'rgba(249,115,22,.12)',  'projects.php'],
    ['🟢','Active Projects',$s['active_p'],     'rgba(16,185,129,.12)',  'projects.php?status=active'],
    ['✅','Open Tasks',      $s['open_tasks'],   'rgba(99,102,241,.12)',  'tasks.php'],
    ['🎯','My Tasks',        $s['my_tasks'],     'rgba(139,92,246,.12)',  'tasks.php?assigned=me'],
    ['👥','Contacts',        $s['contacts'],     'rgba(249,115,22,.12)',  'contacts.php'],
    ['📄','Documents',       $s['docs'],         'rgba(245,158,11,.12)',  'documents.php'],
];
if (isManager()) {
    $kpis[] = ['🎯','Active Leads',  $s['leads'],         'rgba(99,102,241,.12)',  'leads.php'];
    $kpis[] = ['🔥','Hot Leads',     $s['hot_leads'],     'rgba(239,68,68,.12)',   'leads.php?stage=proposal'];
}
?>
<div class="stats-grid" style="margin-bottom:18px">
  <?php foreach ($kpis as [$icon,$lbl,$val,$bg,$href]): ?>
  <a href="<?= $href ?>" style="text-decoration:none">
    <div class="stat-card" style="cursor:pointer;transition:border-color .15s" onmouseover="this.style.borderColor='var(--border2)'" onmouseout="this.style.borderColor='var(--border)'">
      <div class="stat-icon" style="background:<?= $bg ?>"><?= $icon ?></div>
      <div>
        <div class="stat-val"><?= $val ?></div>
        <div class="stat-lbl"><?= $lbl ?></div>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>

<?php if (isManager() && $s['month_expense'] > 0): ?>
<!-- Expense banner for managers -->
<div style="background:var(--orange-bg);border:1px solid var(--orange);border-radius:var(--radius);padding:12px 18px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
  <div style="display:flex;align-items:center;gap:10px">
    <span style="font-size:20px">💰</span>
    <div>
      <div style="font-size:13px;font-weight:700;color:var(--orange)"><?= date('F Y') ?> Expenses</div>
      <div style="font-size:12px;color:var(--text2)">Total spend this month: <strong>INR <?= number_format($s['month_expense'],2) ?></strong></div>
    </div>
  </div>
  <a href="expenses.php" class="btn btn-ghost btn-sm" style="border-color:var(--orange);color:var(--orange)">View Expenses →</a>
</div>
<?php endif; ?>

<!-- ROW 1: My Tasks + Task Overview -->
<div class="db-grid">
  <!-- My Tasks -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">My Tasks <span style="font-size:12px;color:var(--text3);font-weight:400">(<?= count($my_tasks) ?>)</span></div>
      <a href="tasks.php?assigned=me" class="btn btn-ghost btn-sm">View all</a>
    </div>
    <?php if (empty($my_tasks)): ?>
      <div class="empty-state" style="padding:28px 20px"><div class="icon">🎉</div><p>All clear — no open tasks!</p></div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:7px">
        <?php foreach ($my_tasks as $t):
          $tc = statusColor($t['status']);
          $overdue_t = $t['due_date'] && $t['due_date'] < date('Y-m-d');
        ?>
        <div class="task-item">
          <span style="font-size:13px;flex-shrink:0;margin-top:1px"><?= priorityIcon($t['priority']) ?></span>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($t['title']) ?></div>
            <div style="font-size:11.5px;color:var(--text3)">
              <?php if ($t['proj_title']): ?><span style="color:var(--orange)"><?= h($t['proj_title']) ?></span> · <?php endif; ?>
              <span style="color:<?= $tc ?>"><?= h(str_replace('_',' ',$t['status'])) ?></span>
              <?php if ($t['due_date']): ?>
                · <span style="<?= $overdue_t?'color:var(--red)':'' ?>">Due <?= fDate($t['due_date'],'M j') ?><?= $overdue_t?' ⚠':'' ?></span>
              <?php endif; ?>
            </div>
          </div>
          <a href="tasks.php?edit=<?= $t['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit">✎</a>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Task Status + Activity -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">Task Overview</div>
      <a href="tasks.php" class="btn btn-ghost btn-sm">All tasks</a>
    </div>
    <?php
    $tc_labels = ['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'];
    $tc_colors = ['todo'=>'#6366f1','in_progress'=>'#f59e0b','review'=>'#8b5cf6','done'=>'#10b981'];
    foreach ($tcounts as $k=>$v):
      $pct = $total_t > 0 ? round($v/$total_t*100) : 0;
    ?>
    <div style="margin-bottom:11px">
      <div style="display:flex;justify-content:space-between;margin-bottom:4px">
        <span style="font-size:12.5px;color:var(--text2)"><?= $tc_labels[$k] ?></span>
        <span style="font-size:12.5px;font-weight:600;color:var(--text)"><?= $v ?> <span style="color:var(--text3);font-weight:400">(<?= $pct ?>%)</span></span>
      </div>
      <div class="progress-bar">
        <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $tc_colors[$k] ?>"></div>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if (!empty($overdue)): ?>
    <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
      <div style="font-size:11px;font-weight:700;color:var(--red);margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em">⚠ Overdue (<?= count($overdue) ?>)</div>
      <?php foreach ($overdue as $o): ?>
      <div class="ov-item">
        <div style="flex:1;min-width:0">
          <div style="font-size:12.5px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($o['title']) ?></div>
          <div style="font-size:11px;color:var(--text3)"><?= h($o['assignee']??'Unassigned') ?> · <?= fDate($o['due_date'],'M j') ?></div>
        </div>
        <span><?= priorityIcon($o['priority']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ROW 2: Recent Projects + (Lead Pipeline if manager, else Activity) -->
<div class="db-grid">
  <!-- Recent Projects -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">Recent Projects</div>
      <a href="projects.php" class="btn btn-primary btn-sm">＋ New</a>
    </div>
    <?php if (empty($projects)): ?>
      <div class="empty-state" style="padding:28px 20px"><div class="icon">📁</div><p>No projects yet.</p></div>
    <?php else: ?>
      <?php foreach ($projects as $p):
        $prog = (int)$p['progress'];
        $sc   = statusColor($p['status']);
        $pcol = $prog >= 75 ? '#10b981' : ($prog >= 40 ? '#f59e0b' : '#f97316');
      ?>
      <div class="proj-row" onclick="location.href='projects.php?view=<?= $p['id'] ?>'" style="cursor:pointer">
        <div style="width:8px;height:8px;border-radius:50%;background:<?= $sc ?>;flex-shrink:0"></div>
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($p['title']) ?></div>
          <div style="font-size:11px;color:var(--text3)"><?= h($p['client_name']??'No client') ?> · <?= h(str_replace('_',' ',$p['status'])) ?></div>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <div style="font-size:12px;font-weight:700;color:<?= $pcol ?>"><?= $prog ?>%</div>
          <div style="width:60px;height:4px;background:var(--bg4);border-radius:99px;overflow:hidden;margin-top:3px">
            <div style="height:100%;width:<?= $prog ?>%;background:<?= $pcol ?>;border-radius:99px"></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Lead Pipeline (manager+) OR Activity (members) -->
  <?php if (isManager() && !empty($pipeline)): ?>
  <div class="card">
    <div class="card-header">
      <div class="card-title">Lead Pipeline</div>
      <a href="leads.php" class="btn btn-ghost btn-sm">View all</a>
    </div>
    <?php
    $stage_labels = ['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','proposal'=>'Proposal','negotiation'=>'Negotiation'];
    $stage_colors = ['new'=>'#6366f1','contacted'=>'#f59e0b','qualified'=>'#8b5cf6','proposal'=>'#f97316','negotiation'=>'#14b8a6'];
    foreach ($pipeline as $pl):
      $sc2 = $stage_colors[$pl['stage']] ?? '#94a3b8';
      $sl  = $stage_labels[$pl['stage']] ?? ucfirst($pl['stage']);
    ?>
    <div class="pipe-row">
      <div style="width:8px;height:8px;border-radius:50%;background:<?= $sc2 ?>;flex-shrink:0"></div>
      <div style="flex:1">
        <span style="font-size:13px;font-weight:600;color:var(--text)"><?= $sl ?></span>
      </div>
      <span class="badge" style="background:<?= $sc2 ?>20;color:<?= $sc2 ?>"><?= $pl['cnt'] ?> leads</span>
      <?php if ($pl['val'] > 0): ?>
      <span style="font-size:12px;color:var(--green);font-weight:600;min-width:70px;text-align:right"><?= number_format($pl['val'],0) ?></span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php
    $won  = $db->query("SELECT COUNT(*),COALESCE(SUM(budget_est),0) FROM leads WHERE stage='won'")->fetch_row();
    $lost = $db->query("SELECT COUNT(*) FROM leads WHERE stage='lost'")->fetch_row()[0];
    ?>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border);display:flex;gap:16px">
      <div style="text-align:center">
        <div style="font-size:18px;font-weight:700;color:var(--green)"><?= $won[0] ?></div>
        <div style="font-size:11px;color:var(--text3)">Won</div>
      </div>
      <div style="text-align:center">
        <div style="font-size:18px;font-weight:700;color:var(--red)"><?= $lost ?></div>
        <div style="font-size:11px;color:var(--text3)">Lost</div>
      </div>
      <?php if ($won[1] > 0): ?>
      <div style="text-align:center">
        <div style="font-size:18px;font-weight:700;color:var(--green)"><?= number_format($won[1]/1000,1) ?>K</div>
        <div style="font-size:11px;color:var(--text3)">Won Value</div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php elseif (!empty($activity)): ?>
  <div class="card">
    <div class="card-header">
      <div class="card-title">Recent Activity</div>
      <?php if (isAdmin()): ?><a href="activity.php" class="btn btn-ghost btn-sm">View log</a><?php endif; ?>
    </div>
    <?php foreach ($activity as $a): ?>
    <div class="act-row">
      <div class="avatar" style="width:24px;height:24px;font-size:9px;flex-shrink:0;background:var(--bg4);color:var(--text2)"><?= strtoupper(substr($a['uname']??'?',0,1)) ?></div>
      <div style="flex:1;min-width:0">
        <span style="font-size:12.5px;color:var(--text2);font-weight:600"><?= h($a['uname']??'System') ?></span>
        <span style="font-size:12px;color:var(--text3)"> <?= h($a['action']) ?></span>
      </div>
      <span style="font-size:10.5px;color:var(--text3);white-space:nowrap"><?= date('M j',strtotime($a['created_at'])) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="card">
    <div class="card-header"><div class="card-title">Quick Links</div></div>
    <?php foreach ([
      ['📁','Projects','projects.php'],['✅','My Tasks','tasks.php?assigned=me'],
      ['📄','Documents','documents.php'],['👥','Contacts','contacts.php'],
    ] as [$ic,$lb,$hr]): ?>
    <a href="<?= $hr ?>" style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border);color:var(--text2);font-size:13px;transition:color .15s" onmouseover="this.style.color='var(--orange)'" onmouseout="this.style.color='var(--text2)'">
      <span><?= $ic ?></span><span><?= $lb ?></span><span style="margin-left:auto">→</span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php renderLayoutEnd(); ?>