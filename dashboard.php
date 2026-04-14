<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$user = currentUser();
$db = getCRMDB();

// Stats
$stats = [];
$stats['projects']  = $db->query("SELECT COUNT(*) FROM projects WHERE status NOT IN ('cancelled')")->fetch_row()[0];
$stats['active_p']  = $db->query("SELECT COUNT(*) FROM projects WHERE status='active'")->fetch_row()[0];
$stats['tasks']     = $db->query("SELECT COUNT(*) FROM tasks WHERE status!='done'")->fetch_row()[0];
$stats['my_tasks']  = $db->query("SELECT COUNT(*) FROM tasks WHERE assigned_to=".(int)$user['id']." AND status!='done'")->fetch_row()[0];
$stats['contacts']  = $db->query("SELECT COUNT(*) FROM contacts")->fetch_row()[0];
$stats['docs']      = $db->query("SELECT COUNT(*) FROM documents")->fetch_row()[0];

// My tasks
$my_tasks = $db->query("
  SELECT t.*, p.title AS proj_title FROM tasks t
  LEFT JOIN projects p ON p.id=t.project_id
  WHERE t.assigned_to=".(int)$user['id']." AND t.status!='done'
  ORDER BY FIELD(t.priority,'urgent','high','medium','low'), t.due_date ASC
  LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Recent projects
$projects = $db->query("
  SELECT p.*, u.name AS creator, c.name AS client_name,
    (SELECT COUNT(*) FROM tasks WHERE project_id=p.id AND status='done') AS done_tasks,
    (SELECT COUNT(*) FROM tasks WHERE project_id=p.id) AS total_tasks
  FROM projects p
  LEFT JOIN users u ON u.id=p.created_by
  LEFT JOIN contacts c ON c.id=p.contact_id
  ORDER BY p.updated_at DESC LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// Recent activity
$activity = $db->query("
  SELECT a.*, u.name AS uname FROM activity_log a
  LEFT JOIN users u ON u.id=a.user_id
  ORDER BY a.created_at DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Tasks by status (for chart)
$task_chart = $db->query("SELECT status, COUNT(*) as cnt FROM tasks GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$chart_data = ['todo'=>0,'in_progress'=>0,'review'=>0,'done'=>0];
foreach($task_chart as $r) $chart_data[$r['status']] = (int)$r['cnt'];

renderLayout('Dashboard', 'dashboard');
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(249,115,22,.12)">📁</div>
    <div>
      <div class="stat-val"><?= $stats['projects'] ?></div>
      <div class="stat-lbl">Total Projects</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(16,185,129,.12)">🟢</div>
    <div>
      <div class="stat-val"><?= $stats['active_p'] ?></div>
      <div class="stat-lbl">Active Projects</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(99,102,241,.12)">✅</div>
    <div>
      <div class="stat-val"><?= $stats['tasks'] ?></div>
      <div class="stat-lbl">Open Tasks</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(139,92,246,.12)">🎯</div>
    <div>
      <div class="stat-val"><?= $stats['my_tasks'] ?></div>
      <div class="stat-lbl">My Open Tasks</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(249,115,22,.12)">👥</div>
    <div>
      <div class="stat-val"><?= $stats['contacts'] ?></div>
      <div class="stat-lbl">Contacts</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,158,11,.12)">📄</div>
    <div>
      <div class="stat-val"><?= $stats['docs'] ?></div>
      <div class="stat-lbl">Documents</div>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px">
  <!-- My Tasks -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">My Tasks</div>
      <a href="tasks.php" class="btn btn-ghost btn-sm">View all</a>
    </div>
    <?php if (empty($my_tasks)): ?>
      <div class="empty-state"><div class="icon">🎉</div><p>No open tasks assigned to you!</p></div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach ($my_tasks as $t): ?>
        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:12px;display:flex;align-items:flex-start;gap:10px">
          <span style="font-size:13px;margin-top:1px"><?= priorityIcon($t['priority']) ?></span>
          <div style="flex:1;min-width:0">
            <div style="font-size:13.5px;font-weight:600;color:var(--text);margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($t['title']) ?></div>
            <div style="font-size:11.5px;color:var(--text3)">
              <?= $t['proj_title'] ? h($t['proj_title']).' · ' : '' ?>
              <?php $c=statusColor($t['status']); ?>
              <span style="color:<?= $c ?>"><?= h(str_replace('_',' ',$t['status'])) ?></span>
              <?php if ($t['due_date']): ?>
                · Due <?= fDate($t['due_date']) ?>
              <?php endif; ?>
            </div>
          </div>
          <a href="tasks.php?edit=<?= $t['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit">✎</a>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Task Status Overview -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">Task Overview</div>
    </div>
    <div style="margin-bottom:20px">
      <?php
      $total_tasks = array_sum($chart_data);
      $labels = ['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'];
      $colors = ['todo'=>'#6366f1','in_progress'=>'#f59e0b','review'=>'#8b5cf6','done'=>'#10b981'];
      foreach ($chart_data as $k => $v):
        $pct = $total_tasks > 0 ? round($v/$total_tasks*100) : 0;
      ?>
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
          <span style="font-size:12.5px;color:var(--text2)"><?= $labels[$k] ?></span>
          <span style="font-size:12.5px;font-weight:600;color:var(--text)"><?= $v ?> <span style="color:var(--text3);font-weight:400">(<?= $pct ?>%)</span></span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $colors[$k] ?>"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="padding-top:14px;border-top:1px solid var(--border)">
      <div style="font-size:12px;color:var(--text3);margin-bottom:8px">RECENT ACTIVITY</div>
      <?php foreach (array_slice($activity, 0, 5) as $a): ?>
      <div style="display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid var(--border)">
        <div class="avatar" style="width:22px;height:22px;font-size:9px;flex-shrink:0;background:var(--bg4)"><?= h(strtoupper(substr($a['uname']??'?',0,1))) ?></div>
        <div style="flex:1;min-width:0">
          <span style="font-size:12px;color:var(--text2)"><?= h($a['uname']??'System') ?></span>
          <span style="font-size:12px;color:var(--text3)"> <?= h($a['action']) ?></span>
        </div>
        <span style="font-size:11px;color:var(--text3);white-space:nowrap"><?= date('M j',strtotime($a['created_at'])) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Recent Projects -->
<div class="card">
  <div class="card-header">
    <div class="card-title">Recent Projects</div>
    <a href="projects.php" class="btn btn-primary btn-sm"><span>＋</span> <span>New Project</span></a>
  </div>
  <?php if (empty($projects)): ?>
    <div class="empty-state"><div class="icon">📁</div><p>No projects yet. <a href="projects.php" style="color:var(--orange)">Create one</a></p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Project</th><th>Client</th><th>Status</th><th>Priority</th><th>Progress</th><th>Due Date</th>
      </tr></thead>
      <tbody>
        <?php foreach ($projects as $p):
          $progress = $p['total_tasks'] > 0 ? round($p['done_tasks']/$p['total_tasks']*100) : (int)$p['progress'];
          $sc = statusColor($p['status']); $pc = statusColor($p['priority']);
        ?>
        <tr onclick="location.href='projects.php?view=<?= $p['id'] ?>'" style="cursor:pointer">
          <td class="td-main"><?= h($p['title']) ?></td>
          <td><?= h($p['client_name'] ?? '—') ?></td>
          <td><span class="badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><?= h(str_replace('_',' ',$p['status'])) ?></span></td>
          <td><?= priorityIcon($p['priority']) ?> <span style="font-size:12px;color:var(--text2)"><?= h($p['priority']) ?></span></td>
          <td style="min-width:120px">
            <div style="display:flex;align-items:center;gap:8px">
              <div class="progress-bar" style="flex:1"><div class="progress-fill" style="width:<?= $progress ?>%"></div></div>
              <span style="font-size:11px;color:var(--text3);width:30px;text-align:right"><?= $progress ?>%</span>
            </div>
          </td>
          <td><?= fDate($p['due_date']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php renderLayoutEnd(); ?>