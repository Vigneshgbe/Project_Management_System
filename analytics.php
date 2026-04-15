<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
requireRole(['admin','manager']);
$db = getCRMDB();

// Helper functions if not defined elsewhere
if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('statusColor')) {
    function statusColor($status) {
        $colors = [
            'planning' => '#6366f1',
            'active' => '#10b981',
            'on_hold' => '#94a3b8',
            'completed' => '#f97316',
            'cancelled' => '#ef4444',
            'todo' => '#6366f1',
            'in_progress' => '#f59e0b',
            'review' => '#8b5cf6',
            'done' => '#10b981'
        ];
        return $colors[$status] ?? '#6366f1';
    }
}

if (!function_exists('fDate')) {
    function fDate($date) {
        if (!$date) return '-';
        return date('M j, Y', strtotime($date));
    }
}

// --- FILTERS ---
$period = $_GET['period'] ?? '30'; // days: 7, 30, 90, 365, all
$proj_filter = (int)($_GET['project_id'] ?? 0);

$date_cond = '';
if ($period !== 'all') {
    $days = (int)$period;
    $date_cond = "AND created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
}
$date_cond_tasks = '';
if ($period !== 'all') {
    $days = (int)$period;
    $date_cond_tasks = "AND t.created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
}
$proj_cond = $proj_filter ? "AND project_id={$proj_filter}" : '';
$proj_cond_t = $proj_filter ? "AND t.project_id={$proj_filter}" : '';

// --- OVERVIEW STATS ---
$total_projects   = (int)$db->query("SELECT COUNT(*) FROM projects")->fetch_row()[0];
$active_projects  = (int)$db->query("SELECT COUNT(*) FROM projects WHERE status='active'")->fetch_row()[0];
$completed_proj   = (int)$db->query("SELECT COUNT(*) FROM projects WHERE status='completed'")->fetch_row()[0];
$total_tasks      = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE 1=1 {$proj_cond}")->fetch_row()[0];
$done_tasks       = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE status='done' {$proj_cond}")->fetch_row()[0];
$overdue_tasks    = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE due_date < CURDATE() AND status != 'done' {$proj_cond}")->fetch_row()[0];
$total_contacts   = (int)$db->query("SELECT COUNT(*) FROM contacts")->fetch_row()[0];
$total_docs       = (int)$db->query("SELECT COUNT(*) FROM documents")->fetch_row()[0];
$total_users      = (int)$db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetch_row()[0];

$task_completion_rate = $total_tasks > 0 ? round($done_tasks / $total_tasks * 100) : 0;

// --- TASKS BY STATUS ---
$task_status_rows = $db->query("SELECT status, COUNT(*) as cnt FROM tasks WHERE 1=1 {$proj_cond} GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$task_status = ['todo'=>0,'in_progress'=>0,'review'=>0,'done'=>0];
foreach ($task_status_rows as $r) {
    if (isset($task_status[$r['status']])) {
        $task_status[$r['status']] = (int)$r['cnt'];
    }
}

// --- TASKS BY PRIORITY ---
$task_prio_rows = $db->query("SELECT priority, COUNT(*) as cnt FROM tasks WHERE 1=1 {$proj_cond} GROUP BY priority")->fetch_all(MYSQLI_ASSOC);
$task_prio = ['low'=>0,'medium'=>0,'high'=>0,'urgent'=>0];
foreach ($task_prio_rows as $r) {
    if (isset($task_prio[$r['priority']])) {
        $task_prio[$r['priority']] = (int)$r['cnt'];
    }
}

// --- PROJECTS BY STATUS ---
$proj_status_rows = $db->query("SELECT status, COUNT(*) as cnt FROM projects GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$proj_status = ['planning'=>0,'active'=>0,'on_hold'=>0,'completed'=>0,'cancelled'=>0];
foreach ($proj_status_rows as $r) {
    if (isset($proj_status[$r['status']])) {
        $proj_status[$r['status']] = (int)$r['cnt'];
    }
}

// --- TASKS CREATED OVER TIME (last N days, grouped by day) ---
$days_back = in_array($period,['7','30','90','365']) ? (int)$period : 30;
$tasks_over_time = $db->query("
  SELECT DATE(created_at) as d, COUNT(*) as cnt
  FROM tasks
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {$days_back} DAY)
  {$proj_cond}
  GROUP BY DATE(created_at)
  ORDER BY d ASC
")->fetch_all(MYSQLI_ASSOC);

// For tasks done - use updated_at as proxy for completed_at since completed_at doesn't exist
$tasks_done_map = $db->query("
  SELECT DATE(updated_at) as d, COUNT(*) as cnt
  FROM tasks
  WHERE updated_at >= DATE_SUB(CURDATE(), INTERVAL {$days_back} DAY) AND status='done'
  {$proj_cond}
  GROUP BY DATE(updated_at)
  ORDER BY d ASC
")->fetch_all(MYSQLI_ASSOC);

// Fill in missing days
$time_labels = [];
$time_data_created = [];
$time_data_done = [];
$created_map = array_column($tasks_over_time, 'cnt', 'd');
$done_map    = array_column($tasks_done_map, 'cnt', 'd');
for ($i = $days_back - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $label = $days_back <= 30 ? date('M j', strtotime($d)) : date('M j', strtotime($d));
    $time_labels[] = $label;
    $time_data_created[] = (int)($created_map[$d] ?? 0);
    $time_data_done[]    = (int)($done_map[$d] ?? 0);
}

// --- TASKS PER MEMBER ---
$member_tasks = $db->query("
  SELECT u.name, 
    SUM(CASE WHEN t.status != 'done' THEN 1 ELSE 0 END) as open_cnt,
    SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) as done_cnt
  FROM users u
  LEFT JOIN tasks t ON t.assigned_to = u.id {$date_cond_tasks} {$proj_cond_t}
  WHERE u.status = 'active'
  GROUP BY u.id, u.name
  HAVING (open_cnt + done_cnt) > 0
  ORDER BY (open_cnt + done_cnt) DESC
  LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// --- CONTACTS BY TYPE ---
$contact_type_rows = $db->query("SELECT type, COUNT(*) as cnt FROM contacts GROUP BY type")->fetch_all(MYSQLI_ASSOC);
$contact_types = ['client'=>0,'lead'=>0,'partner'=>0,'vendor'=>0];
foreach ($contact_type_rows as $r) {
    if (isset($contact_types[$r['type']])) {
        $contact_types[$r['type']] = (int)$r['cnt'];
    }
}

// --- TOP PROJECTS BY TASK COMPLETION ---
$top_projects = $db->query("
  SELECT p.title,
    COUNT(t.id) as total,
    SUM(CASE WHEN t.status='done' THEN 1 ELSE 0 END) as done,
    p.status, p.priority, p.due_date
  FROM projects p
  LEFT JOIN tasks t ON t.project_id = p.id
  GROUP BY p.id, p.title, p.status, p.priority, p.due_date
  HAVING total > 0
  ORDER BY done DESC
  LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// --- DOCUMENTS BY CATEGORY ---
$doc_cats = $db->query("SELECT category, COUNT(*) as cnt FROM documents GROUP BY category ORDER BY cnt DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);

// --- RECENT ACTIVITY COUNT ---
$activity_7d = (int)$db->query("SELECT COUNT(*) FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_row()[0];

// All projects for filter
$all_projects = $db->query("SELECT id,title FROM projects ORDER BY title")->fetch_all(MYSQLI_ASSOC);

renderLayout('Analytics', 'analytics');
?>

<style>
.an-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}
.an-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;margin-bottom:18px}
.chart-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px}
.chart-title{font-family:var(--font-display);font-weight:700;font-size:15px;margin-bottom:4px}
.chart-sub{font-size:11.5px;color:var(--text3);margin-bottom:16px}
.chart-wrap{position:relative}
.kpi-ring{display:flex;align-items:center;justify-content:center;padding:10px 0 4px}
.legend-row{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.legend-dot{width:10px;height:10px;border-radius:3px;flex-shrink:0}
.legend-lbl{font-size:12px;color:var(--text2);flex:1}
.legend-val{font-size:12px;font-weight:700;color:var(--text)}
.filter-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:20px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px}
.rate-circle{width:110px;height:110px;flex-shrink:0}
@media(max-width:900px){
  .an-grid-2,.an-grid-3{grid-template-columns:1fr}
}
@media(max-width:600px){
  .an-grid-2{grid-template-columns:1fr}
}
</style>

<!-- FILTER BAR -->
<form method="GET" class="filter-bar">
  <span style="font-size:12px;font-weight:600;color:var(--text2)">Filter:</span>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;flex:1">
    <select name="period" class="form-control" style="width:auto" onchange="this.form.submit()">
      <option value="7"  <?= $period==='7'?'selected':'' ?>>Last 7 days</option>
      <option value="30" <?= $period==='30'?'selected':'' ?>>Last 30 days</option>
      <option value="90" <?= $period==='90'?'selected':'' ?>>Last 90 days</option>
      <option value="365"<?= $period==='365'?'selected':'' ?>>Last Year</option>
      <option value="all"<?= $period==='all'?'selected':'' ?>>All Time</option>
    </select>
    <select name="project_id" class="form-control" style="width:auto" onchange="this.form.submit()">
      <option value="">All Projects</option>
      <?php foreach ($all_projects as $p): ?>
      <option value="<?= $p['id'] ?>" <?= $proj_filter==$p['id']?'selected':'' ?>><?= h($p['title']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <span style="font-size:11px;color:var(--text3)">Auto-updates on change</span>
</form>

<!-- KPI ROW -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
  <?php
  $kpis = [
    ['Total Projects', $total_projects,    '📁', '#f97316'],
    ['Active Projects',$active_projects,   '🟢', '#10b981'],
    ['Total Tasks',    $total_tasks,        '✅', '#6366f1'],
    ['Done Tasks',     $done_tasks,         '🏁', '#10b981'],
    ['Overdue Tasks',  $overdue_tasks,      '⚠',  '#ef4444'],
    ['Completion Rate',$task_completion_rate.'%', '📊', '#8b5cf6'],
    ['CRM Contacts',   $total_contacts,     '👥', '#f97316'],
    ['Documents',      $total_docs,         '📄', '#f59e0b'],
  ];
  foreach ($kpis as [$lbl,$val,$icon,$col]):
  ?>
  <div class="stat-card">
    <div class="stat-icon" style="background:<?= $col ?>18"><?= $icon ?></div>
    <div>
      <div class="stat-val" style="font-size:22px"><?= $val ?></div>
      <div class="stat-lbl"><?= $lbl ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ROW 1: Tasks Over Time (full width) -->
<div class="chart-card" style="margin-bottom:18px">
  <div class="chart-title">Tasks Over Time</div>
  <div class="chart-sub">Created vs Completed — last <?= $period==='all'?'all time':$period.' days' ?></div>
  <div class="chart-wrap" style="height:220px">
    <canvas id="chartTimeline"></canvas>
  </div>
</div>

<!-- ROW 2: Task Status + Priority -->
<div class="an-grid-2">
  <div class="chart-card">
    <div class="chart-title">Tasks by Status</div>
    <div class="chart-sub">Current distribution</div>
    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
      <div class="chart-wrap" style="width:160px;height:160px;flex-shrink:0">
        <canvas id="chartTaskStatus"></canvas>
      </div>
      <div style="flex:1;min-width:120px">
        <?php
        $ts_labels = ['To Do','In Progress','Review','Done'];
        $ts_colors = ['#6366f1','#f59e0b','#8b5cf6','#10b981'];
        $ts_vals   = array_values($task_status);
        foreach (array_keys($task_status) as $i => $k):
        ?>
        <div class="legend-row">
          <div class="legend-dot" style="background:<?= $ts_colors[$i] ?>"></div>
          <span class="legend-lbl"><?= $ts_labels[$i] ?></span>
          <span class="legend-val"><?= $task_status[$k] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="chart-card">
    <div class="chart-title">Tasks by Priority</div>
    <div class="chart-sub">Urgency breakdown</div>
    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
      <div class="chart-wrap" style="width:160px;height:160px;flex-shrink:0">
        <canvas id="chartTaskPriority"></canvas>
      </div>
      <div style="flex:1;min-width:120px">
        <?php
        $tp_labels = ['Low','Medium','High','Urgent'];
        $tp_colors = ['#10b981','#f59e0b','#f97316','#ef4444'];
        foreach (array_keys($task_prio) as $i => $k):
        ?>
        <div class="legend-row">
          <div class="legend-dot" style="background:<?= $tp_colors[$i] ?>"></div>
          <span class="legend-lbl"><?= $tp_labels[$i] ?></span>
          <span class="legend-val"><?= $task_prio[$k] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- ROW 3: Team Workload + Project Status -->
<div class="an-grid-2">
  <div class="chart-card">
    <div class="chart-title">Team Workload</div>
    <div class="chart-sub">Open vs Completed tasks per member</div>
    <div class="chart-wrap" style="height:<?= max(180, count($member_tasks)*36+20) ?>px">
      <canvas id="chartTeam"></canvas>
    </div>
    <?php if (empty($member_tasks)): ?>
    <div class="empty-state" style="padding:24px"><p>No task data for this period.</p></div>
    <?php endif; ?>
  </div>

  <div class="chart-card">
    <div class="chart-title">Projects by Status</div>
    <div class="chart-sub">Portfolio health overview</div>
    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
      <div class="chart-wrap" style="width:160px;height:160px;flex-shrink:0">
        <canvas id="chartProjStatus"></canvas>
      </div>
      <div style="flex:1;min-width:120px">
        <?php
        $ps_labels = ['Planning','Active','On Hold','Completed','Cancelled'];
        $ps_colors = ['#6366f1','#10b981','#94a3b8','#f97316','#ef4444'];
        foreach (array_keys($proj_status) as $i => $k):
        ?>
        <div class="legend-row">
          <div class="legend-dot" style="background:<?= $ps_colors[$i] ?>"></div>
          <span class="legend-lbl"><?= $ps_labels[$i] ?></span>
          <span class="legend-val"><?= $proj_status[$k] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- ROW 4: CRM Contacts + Docs by Category -->
<div class="an-grid-2">
  <div class="chart-card">
    <div class="chart-title">CRM Contacts by Type</div>
    <div class="chart-sub">Relationship breakdown</div>
    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
      <div class="chart-wrap" style="width:160px;height:160px;flex-shrink:0">
        <canvas id="chartContacts"></canvas>
      </div>
      <div style="flex:1;min-width:120px">
        <?php
        $ct_labels = ['Client','Lead','Partner','Vendor'];
        $ct_colors = ['#10b981','#f97316','#6366f1','#f59e0b'];
        foreach (array_keys($contact_types) as $i => $k):
        ?>
        <div class="legend-row">
          <div class="legend-dot" style="background:<?= $ct_colors[$i] ?>"></div>
          <span class="legend-lbl"><?= $ct_labels[$i] ?></span>
          <span class="legend-val"><?= $contact_types[$k] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="chart-card">
    <div class="chart-title">Documents by Category</div>
    <div class="chart-sub">File store breakdown</div>
    <div class="chart-wrap" style="height:180px">
      <canvas id="chartDocs"></canvas>
    </div>
    <?php if (empty($doc_cats)): ?>
    <div class="empty-state" style="padding:24px"><p>No documents uploaded yet.</p></div>
    <?php endif; ?>
  </div>
</div>

<!-- ROW 5: Top Projects Table -->
<div class="chart-card" style="margin-bottom:18px">
  <div class="chart-title" style="margin-bottom:4px">Project Progress Overview</div>
  <div class="chart-sub">Task completion per project</div>
  <?php if (empty($top_projects)): ?>
  <div class="empty-state" style="padding:24px"><div class="icon">📁</div><p>No project data yet.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Project</th><th>Status</th><th>Progress</th><th>Tasks Done</th><th>Due</th>
      </tr></thead>
      <tbody>
        <?php foreach ($top_projects as $p):
          $pct = $p['total'] > 0 ? round($p['done']/$p['total']*100) : 0;
          $sc  = statusColor($p['status']);
          $today = date('Y-m-d');
          $overdue_proj = $p['due_date'] && $p['due_date'] < $today && $p['status'] !== 'completed';
        ?>
        <tr>
          <td class="td-main"><?= h($p['title']) ?></td>
          <td><span class="badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><?= h(str_replace('_',' ',$p['status'])) ?></span></td>
          <td style="min-width:160px">
            <div style="display:flex;align-items:center;gap:8px">
              <div class="progress-bar" style="flex:1"><div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $pct>=75?'#10b981':($pct>=40?'#f59e0b':'#ef4444') ?>"></div></div>
              <span style="font-size:11px;color:var(--text3);width:32px;text-align:right"><?= $pct ?>%</span>
            </div>
          </td>
          <td><span style="color:var(--green);font-weight:700"><?= $p['done'] ?></span><span style="color:var(--text3)"> / <?= $p['total'] ?></span></td>
          <td style="<?= $overdue_proj?'color:var(--red)':'' ?>"><?= fDate($p['due_date']) ?><?= $overdue_proj?' ⚠':'' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// Read CSS vars for chart theming
function cssVar(v){return getComputedStyle(document.documentElement).getPropertyValue(v).trim()}

const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
function getChartDefaults(){
  return {
    gridColor: cssVar('--border'),
    textColor: cssVar('--text2'),
    bg2: cssVar('--bg2'),
    bg3: cssVar('--bg3'),
  }
}

Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
Chart.defaults.font.size = 12;

function applyThemeToCharts(){
  const g = getChartDefaults();
  Chart.defaults.color = g.textColor;
  Chart.helpers.each(Chart.instances, chart => {
    if(chart.options.scales){
      Object.values(chart.options.scales).forEach(s=>{
        if(s.grid) s.grid.color = g.gridColor;
        if(s.ticks) s.ticks.color = g.textColor;
      });
    }
    if(chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = g.textColor;
    chart.update('none');
  });
}

// TIMELINE CHART
const ctxTimeline = document.getElementById('chartTimeline').getContext('2d');
new Chart(ctxTimeline, {
  type: 'line',
  data: {
    labels: <?= json_encode($time_labels) ?>,
    datasets: [
      {
        label: 'Tasks Created',
        data: <?= json_encode($time_data_created) ?>,
        borderColor: '#f97316',
        backgroundColor: 'rgba(249,115,22,0.1)',
        fill: true,
        tension: 0.4,
        pointRadius: <?= $days_back <= 30 ? 3 : 0 ?>,
        pointHoverRadius: 5,
        borderWidth: 2,
      },
      {
        label: 'Tasks Completed',
        data: <?= json_encode($time_data_done) ?>,
        borderColor: '#10b981',
        backgroundColor: 'rgba(16,185,129,0.08)',
        fill: true,
        tension: 0.4,
        pointRadius: <?= $days_back <= 30 ? 3 : 0 ?>,
        pointHoverRadius: 5,
        borderWidth: 2,
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { position: 'top', labels: { boxWidth: 10, padding: 16, color: cssVar('--text2') } }
    },
    scales: {
      x: { grid: { color: cssVar('--border'), drawBorder: false }, ticks: { color: cssVar('--text2'), maxTicksLimit: 12 } },
      y: { grid: { color: cssVar('--border') }, ticks: { color: cssVar('--text2'), precision: 0 }, beginAtZero: true }
    }
  }
});

// TASK STATUS DOUGHNUT
const ctxTS = document.getElementById('chartTaskStatus').getContext('2d');
new Chart(ctxTS, {
  type: 'doughnut',
  data: {
    labels: ['To Do','In Progress','Review','Done'],
    datasets: [{ data: <?= json_encode(array_values($task_status)) ?>, backgroundColor: ['#6366f1','#f59e0b','#8b5cf6','#10b981'], borderWidth: 0, hoverOffset: 4 }]
  },
  options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false } } }
});

// TASK PRIORITY DOUGHNUT
const ctxTP = document.getElementById('chartTaskPriority').getContext('2d');
new Chart(ctxTP, {
  type: 'doughnut',
  data: {
    labels: ['Low','Medium','High','Urgent'],
    datasets: [{ data: <?= json_encode(array_values($task_prio)) ?>, backgroundColor: ['#10b981','#f59e0b','#f97316','#ef4444'], borderWidth: 0, hoverOffset: 4 }]
  },
  options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false } } }
});

// TEAM WORKLOAD HORIZONTAL BAR
<?php if (!empty($member_tasks)): ?>
const ctxTeam = document.getElementById('chartTeam').getContext('2d');
new Chart(ctxTeam, {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($member_tasks,'name')) ?>,
    datasets: [
      { label: 'Open', data: <?= json_encode(array_column($member_tasks,'open_cnt')) ?>, backgroundColor: '#f97316', borderRadius: 4 },
      { label: 'Done', data: <?= json_encode(array_column($member_tasks,'done_cnt')) ?>, backgroundColor: '#10b981', borderRadius: 4 }
    ]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'top', labels: { boxWidth: 10, padding: 14, color: cssVar('--text2') } }
    },
    scales: {
      x: { stacked: false, grid: { color: cssVar('--border') }, ticks: { color: cssVar('--text2'), precision: 0 }, beginAtZero: true },
      y: { grid: { display: false }, ticks: { color: cssVar('--text2') } }
    }
  }
});
<?php endif; ?>

// PROJECT STATUS DOUGHNUT
const ctxPS = document.getElementById('chartProjStatus').getContext('2d');
new Chart(ctxPS, {
  type: 'doughnut',
  data: {
    labels: ['Planning','Active','On Hold','Completed','Cancelled'],
    datasets: [{ data: <?= json_encode(array_values($proj_status)) ?>, backgroundColor: ['#6366f1','#10b981','#94a3b8','#f97316','#ef4444'], borderWidth: 0, hoverOffset: 4 }]
  },
  options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false } } }
});

// CONTACTS DOUGHNUT
const ctxCon = document.getElementById('chartContacts').getContext('2d');
new Chart(ctxCon, {
  type: 'doughnut',
  data: {
    labels: ['Client','Lead','Partner','Vendor'],
    datasets: [{ data: <?= json_encode(array_values($contact_types)) ?>, backgroundColor: ['#10b981','#f97316','#6366f1','#f59e0b'], borderWidth: 0, hoverOffset: 4 }]
  },
  options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false } } }
});

// DOCS BAR
<?php if (!empty($doc_cats)): ?>
const ctxDocs = document.getElementById('chartDocs').getContext('2d');
new Chart(ctxDocs, {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($doc_cats,'category')) ?>,
    datasets: [{
      label: 'Documents',
      data: <?= json_encode(array_column($doc_cats,'cnt')) ?>,
      backgroundColor: ['#f97316','#6366f1','#10b981','#f59e0b','#8b5cf6','#ef4444','#94a3b8','#14b8a6'],
      borderRadius: 5,
      borderWidth: 0
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false }, ticks: { color: cssVar('--text2') } },
      y: { grid: { color: cssVar('--border') }, ticks: { color: cssVar('--text2'), precision: 0 }, beginAtZero: true }
    }
  }
});
<?php endif; ?>

// Re-theme charts when theme changes
document.addEventListener('themeChanged', applyThemeToCharts);
</script>

<?php renderLayoutEnd(); ?>