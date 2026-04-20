<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
requireRole(['admin','manager']);
$db = getCRMDB();

// ── FILTERS ──
$period     = $_GET['period']     ?? '30';
$proj_filter= (int)($_GET['project_id'] ?? 0);

$date_cond = $period !== 'all' ? "AND created_at >= DATE_SUB(NOW(), INTERVAL {$period} DAY)" : '';
$date_cond_tasks = $period !== 'all' ? "AND t.created_at >= DATE_SUB(NOW(), INTERVAL {$period} DAY)" : '';
$proj_cond   = $proj_filter ? "AND project_id=$proj_filter" : '';
$proj_cond_t = $proj_filter ? "AND t.project_id=$proj_filter" : '';

// ── CORE STATS (existing) ──
$total_projects  = $db->query("SELECT COUNT(*) FROM projects")->fetch_row()[0];
$active_projects = $db->query("SELECT COUNT(*) FROM projects WHERE status='active'")->fetch_row()[0];
$total_tasks     = $db->query("SELECT COUNT(*) FROM tasks $proj_cond")->fetch_row()[0];
$done_tasks      = $db->query("SELECT COUNT(*) FROM tasks WHERE status='done' $proj_cond")->fetch_row()[0];
$overdue_tasks   = $db->query("SELECT COUNT(*) FROM tasks WHERE due_date < CURDATE() AND status!='done' $proj_cond")->fetch_row()[0];
$total_contacts  = $db->query("SELECT COUNT(*) FROM contacts")->fetch_row()[0];
$total_docs      = $db->query("SELECT COUNT(*) FROM documents")->fetch_row()[0];
$task_completion_rate = $total_tasks > 0 ? round($done_tasks/$total_tasks*100) : 0;

// ── SAFE QUERY HELPER ──
function safeQuery(mysqli $db, string $sql, $default = null) {
    $r = @$db->query($sql);
    if (!$r || $r === false) return $default;
    return is_bool($r) ? $default : $r;
}
function safeRow(mysqli $db, string $sql, array $default = []): array {
    $r = safeQuery($db, $sql);
    return ($r && !is_bool($r)) ? ($r->fetch_assoc() ?: $default) : $default;
}
function safeAll(mysqli $db, string $sql): array {
    $r = safeQuery($db, $sql);
    return ($r && !is_bool($r)) ? $r->fetch_all(MYSQLI_ASSOC) : [];
}
function safeVal(mysqli $db, string $sql, $default = 0) {
    $r = safeQuery($db, $sql);
    if (!$r || is_bool($r)) return $default;
    $row = $r->fetch_row();
    return $row ? $row[0] : $default;
}

// ── INVOICE / REVENUE STATS ──
$inv_stats = safeRow($db, "
    SELECT
        COALESCE(SUM(CASE WHEN status NOT IN('cancelled','draft') THEN total ELSE 0 END),0)  AS total_invoiced,
        COALESCE(SUM(amount_paid),0)                                                          AS total_collected,
        COALESCE(SUM(CASE WHEN status='overdue' THEN total-amount_paid ELSE 0 END),0)        AS total_overdue,
        COALESCE(SUM(CASE WHEN status='paid' THEN total ELSE 0 END),0)                       AS total_paid,
        COUNT(CASE WHEN status NOT IN('cancelled','draft') THEN 1 END)                       AS inv_count,
        COUNT(CASE WHEN status='paid' THEN 1 END)                                            AS inv_paid,
        COUNT(CASE WHEN status='overdue' THEN 1 END)                                         AS inv_overdue
    FROM invoices
", ['total_invoiced'=>0,'total_collected'=>0,'total_overdue'=>0,'total_paid'=>0,'inv_count'=>0,'inv_paid'=>0,'inv_overdue'=>0]);

// Revenue collected per month (last 6 months)
$revenue_months = safeAll($db, "
    SELECT DATE_FORMAT(paid_at,'%b %Y') AS mo, DATE_FORMAT(paid_at,'%Y-%m') AS sort_mo,
           SUM(amount) AS collected
    FROM invoice_payments
    WHERE paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mo, sort_mo
    ORDER BY sort_mo ASC
");

// ── LEADS PIPELINE (new) ──
$lead_stages = safeAll($db, "
    SELECT stage, COUNT(*) AS cnt,
           COALESCE(SUM(budget_est),0) AS pipeline_val
    FROM leads
    GROUP BY stage
    ORDER BY FIELD(stage,'new','contacted','qualified','proposal','negotiation','won','lost')
");

$lead_total      = array_sum(array_column($lead_stages,'cnt'));
$lead_won    = safeRow($db,"SELECT COUNT(*) AS c, COALESCE(SUM(budget_est),0) AS v FROM leads WHERE stage='won'",['c'=>0,'v'=>0]);
$lead_lost   = safeVal($db,"SELECT COUNT(*) AS c FROM leads WHERE stage='lost'",0);
$lead_active = safeVal($db,"SELECT COUNT(*) FROM leads WHERE stage NOT IN('won','lost')",0);
$conv_rate       = ($lead_total > 0) ? round($lead_won['c']/$lead_total*100,1) : 0;

// Lead source breakdown
$lead_sources = safeAll($db, "
    SELECT source, COUNT(*) AS cnt,
           COALESCE(SUM(CASE WHEN stage='won' THEN 1 ELSE 0 END),0) AS won_cnt
    FROM leads GROUP BY source ORDER BY cnt DESC LIMIT 7
");

// ── EXPENSES (new) ──
$expense_stats = safeRow($db, "
    SELECT
        COALESCE(SUM(own_spend+office_spend),0) AS total_spend,
        COALESCE(SUM(own_spend),0)              AS own_spend,
        COALESCE(SUM(office_spend),0)           AS office_spend
    FROM expense_entries
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
", ['total_spend'=>0,'own_spend'=>0,'office_spend'=>0]);

$expense_by_cat = safeAll($db, "
    SELECT category, COALESCE(SUM(own_spend+office_spend),0) AS total
    FROM expense_entries
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
    GROUP BY category ORDER BY total DESC LIMIT 8
");

$expense_by_month = safeAll($db, "
    SELECT DATE_FORMAT(em.month_year,'%b %Y') AS mo,
           DATE_FORMAT(em.month_year,'%Y-%m') AS sort_mo,
           COALESCE(SUM(ee.own_spend+ee.office_spend),0) AS total,
           em.revenue
    FROM expense_months em
    LEFT JOIN expense_entries ee ON ee.month_id=em.id
    WHERE em.month_year >= DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 5 MONTH),'%Y-%m')
    GROUP BY em.id, mo, sort_mo, em.revenue
    ORDER BY sort_mo ASC
");

// ── TASK INTELLIGENCE (new) ──
// Avg completion time in days (tasks with due_date and completed_at)
$avg_complete = safeRow($db, "
    SELECT ROUND(AVG(DATEDIFF(completed_at, created_at)),1) AS avg_days
    FROM tasks WHERE status='done' AND completed_at IS NOT NULL AND created_at IS NOT NULL
    $proj_cond
", ['avg_days'=>null])['avg_days'] ?? '—';

// Task comments activity (most active tasks)
$comment_activity = safeAll($db, "
    SELECT t.title, COUNT(tc.id) AS cnt, MAX(tc.created_at) AS last_comment
    FROM task_comments tc
    JOIN tasks t ON t.id=tc.task_id
    $proj_cond_t
    GROUP BY tc.task_id, t.title
    ORDER BY cnt DESC LIMIT 5
");

// Tasks created vs completed over time (existing, refined)
$days_back = in_array($period,['7','30','90','365']) ? (int)$period : 30;
$created_map = array_column($db->query("
    SELECT DATE(created_at) AS d, COUNT(*) AS cnt FROM tasks
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {$days_back} DAY) $proj_cond
    GROUP BY DATE(created_at)
")->fetch_all(MYSQLI_ASSOC), 'cnt', 'd');
$done_map = array_column($db->query("
    SELECT DATE(completed_at) AS d, COUNT(*) AS cnt FROM tasks
    WHERE completed_at >= DATE_SUB(CURDATE(), INTERVAL {$days_back} DAY) AND status='done' $proj_cond
    GROUP BY DATE(completed_at)
")->fetch_all(MYSQLI_ASSOC), 'cnt', 'd');

$time_labels=$time_data_created=$time_data_done=[];
for ($i=$days_back-1;$i>=0;$i--) {
    $d = date('Y-m-d',strtotime("-$i days"));
    $time_labels[]      = $days_back<=30 ? date('M j',strtotime($d)) : date('M j',strtotime($d));
    $time_data_created[]= (int)($created_map[$d]??0);
    $time_data_done[]   = (int)($done_map[$d]??0);
}

// ── TASK STATUS / PRIORITY / PROJECTS (existing) ──
$task_status_rows = $db->query("SELECT status,COUNT(*) AS cnt FROM tasks WHERE 1=1 $proj_cond GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$task_status = ['todo'=>0,'in_progress'=>0,'review'=>0,'done'=>0];
foreach ($task_status_rows as $r) $task_status[$r['status']]=(int)$r['cnt'];

$task_prio_rows = $db->query("SELECT priority,COUNT(*) AS cnt FROM tasks WHERE 1=1 $proj_cond GROUP BY priority")->fetch_all(MYSQLI_ASSOC);
$task_prio = ['low'=>0,'medium'=>0,'high'=>0,'urgent'=>0];
foreach ($task_prio_rows as $r) $task_prio[$r['priority']]=(int)$r['cnt'];

$proj_status_rows = $db->query("SELECT status,COUNT(*) AS cnt FROM projects GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$proj_status = ['planning'=>0,'active'=>0,'on_hold'=>0,'completed'=>0,'cancelled'=>0];
foreach ($proj_status_rows as $r) $proj_status[$r['status']]=(int)$r['cnt'];

$member_tasks = $db->query("
    SELECT u.name,
        SUM(CASE WHEN t.status!='done' THEN 1 ELSE 0 END) AS open_cnt,
        SUM(CASE WHEN t.status='done' THEN 1 ELSE 0 END)  AS done_cnt
    FROM users u
    LEFT JOIN tasks t ON t.assigned_to=u.id $date_cond_tasks $proj_cond_t
    WHERE u.status='active'
    GROUP BY u.id,u.name
    HAVING (open_cnt+done_cnt)>0
    ORDER BY (open_cnt+done_cnt) DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

$contact_type_rows = $db->query("SELECT type,COUNT(*) AS cnt FROM contacts GROUP BY type")->fetch_all(MYSQLI_ASSOC);
$contact_types = ['client'=>0,'lead'=>0,'partner'=>0,'vendor'=>0];
foreach ($contact_type_rows as $r) $contact_types[$r['type']]=(int)$r['cnt'];

$top_projects = $db->query("
    SELECT p.title, COUNT(t.id) AS total, SUM(t.status='done') AS done,
           p.status, p.priority, p.due_date
    FROM projects p
    LEFT JOIN tasks t ON t.project_id=p.id
    GROUP BY p.id,p.title,p.status,p.priority,p.due_date
    HAVING total>0 ORDER BY done DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

$doc_cats = $db->query("SELECT category,COUNT(*) AS cnt FROM documents GROUP BY category ORDER BY cnt DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);

// ── EMAIL LOG STATS (new) ──
$email_stats = safeRow($db, "
    SELECT
        COUNT(*) AS total,
        SUM(status='sent') AS sent,
        SUM(status='failed') AS failed,
        SUM(opened_count>0) AS opened
    FROM email_log WHERE direction='out'
", ['total'=>0,'sent'=>0,'failed'=>0,'opened'=>0]);

$email_by_day = safeAll($db, "
    SELECT DATE(created_at) AS d, COUNT(*) AS total, SUM(status='sent') AS sent
    FROM email_log WHERE direction='out'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at) ORDER BY d ASC
");

// ── ACTIVITY HEATMAP DATA (new) ──
$activity_by_hour = $db->query("
    SELECT HOUR(created_at) AS hr, COUNT(*) AS cnt
    FROM activity_log
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY HOUR(created_at) ORDER BY hr
")->fetch_all(MYSQLI_ASSOC);
$hour_data = array_fill(0,24,0);
foreach ($activity_by_hour as $r) $hour_data[(int)$r['hr']]=(int)$r['cnt'];

// ── TEAM PRODUCTIVITY SCORE (new) ──
$team_productivity = safeAll($db, "
    SELECT u.name,
        SUM(t.status='done') AS done,
        SUM(t.status='done' AND t.due_date IS NOT NULL AND t.completed_at <= t.due_date) AS on_time,
        SUM(t.status!='done' AND t.due_date < CURDATE()) AS overdue_open
    FROM users u
    LEFT JOIN tasks t ON t.assigned_to=u.id
    WHERE u.status='active'
    GROUP BY u.id,u.name
    HAVING done>0
    ORDER BY done DESC LIMIT 8
");

$all_projects = $db->query("SELECT id,title FROM projects ORDER BY title")->fetch_all(MYSQLI_ASSOC);

renderLayout('Analytics', 'analytics');
?>
<style>
.an-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}
.an-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;margin-bottom:18px}
.an-grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:18px}
.chart-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px}
.chart-title{font-family:var(--font-display);font-weight:700;font-size:15px;margin-bottom:4px}
.chart-sub{font-size:11.5px;color:var(--text3);margin-bottom:16px}
.chart-wrap{position:relative}
.legend-row{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.legend-dot{width:10px;height:10px;border-radius:3px;flex-shrink:0}
.legend-lbl{font-size:12px;color:var(--text2);flex:1}
.legend-val{font-size:12px;font-weight:700;color:var(--text)}
.filter-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:20px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px}

/* Revenue KPI cards */
.rev-kpi{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 18px;display:flex;flex-direction:column;gap:4px}
.rev-kpi-val{font-size:22px;font-weight:800;font-family:var(--font-display)}
.rev-kpi-lbl{font-size:11px;color:var(--text3);text-transform:uppercase;letter-spacing:.05em}
.rev-kpi-sub{font-size:11.5px;color:var(--text3)}

/* Sales funnel */
.funnel-step{display:flex;align-items:center;gap:10px;margin-bottom:6px}
.funnel-bar-wrap{flex:1;background:var(--bg4);border-radius:4px;overflow:hidden;height:24px}
.funnel-bar-fill{height:100%;border-radius:4px;display:flex;align-items:center;padding-left:8px;font-size:11px;font-weight:700;color:#fff;white-space:nowrap;transition:width .4s}
.funnel-lbl{font-size:12px;font-weight:600;color:var(--text2);width:95px;flex-shrink:0;text-align:right}
.funnel-cnt{font-size:12px;font-weight:700;color:var(--text);width:28px;flex-shrink:0;text-align:right}

/* Productivity table */
.prod-row{display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid var(--border)}
.prod-row:last-child{border-bottom:none}
.prod-name{font-size:12.5px;font-weight:600;color:var(--text);width:120px;flex-shrink:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.prod-bar{flex:1;background:var(--bg4);border-radius:3px;height:8px;overflow:hidden}
.prod-fill{height:100%;border-radius:3px}
.prod-val{font-size:11.5px;color:var(--text3);width:60px;text-align:right;flex-shrink:0}

/* Hour heatmap */
.heat-grid{display:grid;grid-template-columns:repeat(24,1fr);gap:3px;margin-top:8px}
.heat-cell{height:28px;border-radius:3px;position:relative}
.heat-cell:hover::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 4px);left:50%;transform:translateX(-50%);background:var(--bg4);border:1px solid var(--border);border-radius:4px;padding:3px 7px;font-size:10px;color:var(--text);white-space:nowrap;z-index:20;pointer-events:none}

/* Section heading */
.section-heading{font-family:var(--font-display);font-size:13px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.07em;margin:24px 0 12px;display:flex;align-items:center;gap:8px}
.section-heading::after{content:'';flex:1;height:1px;background:var(--border)}

@media(max-width:1100px){.an-grid-4{grid-template-columns:1fr 1fr}}
@media(max-width:900px){.an-grid-2,.an-grid-3{grid-template-columns:1fr}.an-grid-4{grid-template-columns:1fr 1fr}}
@media(max-width:500px){.an-grid-4{grid-template-columns:1fr}}
</style>

<!-- FILTER BAR -->
<form method="GET" class="filter-bar">
  <span style="font-size:12px;font-weight:600;color:var(--text2)">Filter:</span>
  <select name="period" class="form-control" style="width:auto" onchange="this.form.submit()">
    <option value="7"   <?=$period==='7'?'selected':''?>>Last 7 days</option>
    <option value="30"  <?=$period==='30'?'selected':''?>>Last 30 days</option>
    <option value="90"  <?=$period==='90'?'selected':''?>>Last 90 days</option>
    <option value="365" <?=$period==='365'?'selected':''?>>Last Year</option>
    <option value="all" <?=$period==='all'?'selected':''?>>All Time</option>
  </select>
  <select name="project_id" class="form-control" style="width:auto" onchange="this.form.submit()">
    <option value="">All Projects</option>
    <?php foreach ($all_projects as $p): ?>
    <option value="<?=$p['id']?>" <?=$proj_filter==$p['id']?'selected':''?>><?=h($p['title'])?></option>
    <?php endforeach; ?>
  </select>
  <span style="font-size:11px;color:var(--text3);margin-left:auto">Auto-updates on change</span>
</form>

<!-- ══ SECTION: OVERVIEW ══ -->
<div class="section-heading">📊 Overview</div>
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(155px,1fr));margin-bottom:18px">
  <?php
  $kpis = [
    ['Total Projects',  $total_projects,                '#f97316','📁'],
    ['Active Projects', $active_projects,               '#10b981','🟢'],
    ['Total Tasks',     $total_tasks,                   '#6366f1','✅'],
    ['Overdue Tasks',   $overdue_tasks,                 '#ef4444','⚠'],
    ['Completion Rate', $task_completion_rate.'%',      '#8b5cf6','📈'],
    ['Active Leads',    $lead_active,                   '#f59e0b','🎯'],
    ['Won Leads',       (int)$lead_won['c'],            '#10b981','🏆'],
    ['Conversion',      $conv_rate.'%',                 '#6366f1','🔄'],
  ];
  foreach ($kpis as [$lbl,$val,$col,$icon]): ?>
  <div class="stat-card">
    <div class="stat-icon" style="background:<?=$col?>18"><?=$icon?></div>
    <div><div class="stat-val" style="font-size:20px;color:<?=$col?>"><?=$val?></div><div class="stat-lbl"><?=$lbl?></div></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ══ SECTION: REVENUE & BILLING ══ -->
<div class="section-heading">💰 Revenue & Billing</div>
<div class="an-grid-4" style="margin-bottom:18px">
  <?php
  $sym = 'Rs. ';
  $rev_kpis = [
    [$sym.number_format($inv_stats['total_invoiced'],0), 'Total Invoiced',  '#f97316', $inv_stats['inv_count'].' invoices'],
    [$sym.number_format($inv_stats['total_collected'],0),'Collected',       '#10b981', $inv_stats['inv_paid'].' paid'],
    [$sym.number_format($inv_stats['total_invoiced']-$inv_stats['total_collected'],0),'Outstanding','#f59e0b',$inv_stats['inv_count']-$inv_stats['inv_paid'].' pending'],
    [$sym.number_format($inv_stats['total_overdue'],0),  'Overdue',         '#ef4444', $inv_stats['inv_overdue'].' invoices'],
  ];
  foreach ($rev_kpis as [$val,$lbl,$col,$sub]): ?>
  <div class="rev-kpi" style="border-left:3px solid <?=$col?>">
    <div class="rev-kpi-val" style="color:<?=$col?>"><?=$val?></div>
    <div class="rev-kpi-lbl"><?=$lbl?></div>
    <div class="rev-kpi-sub"><?=$sub?></div>
  </div>
  <?php endforeach; ?>
</div>

<?php if ($revenue_months): ?>
<div class="chart-card" style="margin-bottom:18px">
  <div class="chart-title">Revenue Collected by Month</div>
  <div class="chart-sub">Payments received over the last 6 months</div>
  <div class="chart-wrap" style="height:200px">
    <canvas id="chartRevenue"></canvas>
  </div>
</div>
<?php endif; ?>

<!-- ══ SECTION: TASKS ══ -->
<div class="section-heading">✅ Task Performance</div>

<div class="chart-card" style="margin-bottom:18px">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:16px">
    <div>
      <div class="chart-title">Tasks Over Time</div>
      <div class="chart-sub">Created vs Completed — last <?=$period==='all'?'all time':$period.' days'?></div>
    </div>
    <?php if ($avg_complete !== '—'): ?>
    <div style="text-align:right;flex-shrink:0">
      <div style="font-size:22px;font-weight:800;font-family:var(--font-display);color:var(--blue)"><?=$avg_complete?> days</div>
      <div style="font-size:11px;color:var(--text3);text-transform:uppercase">Avg completion time</div>
    </div>
    <?php endif; ?>
  </div>
  <div class="chart-wrap" style="height:220px"><canvas id="chartTimeline"></canvas></div>
</div>

<div class="an-grid-3">
  <div class="chart-card">
    <div class="chart-title">By Status</div>
    <div class="chart-sub">Current distribution</div>
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <div style="width:130px;height:130px;flex-shrink:0"><canvas id="chartTaskStatus"></canvas></div>
      <div style="flex:1;min-width:100px">
        <?php $ts_labels=['To Do','In Progress','Review','Done'];$ts_colors=['#6366f1','#f59e0b','#8b5cf6','#10b981'];
        foreach (array_keys($task_status) as $i=>$k): ?>
        <div class="legend-row">
          <div class="legend-dot" style="background:<?=$ts_colors[$i]?>"></div>
          <span class="legend-lbl"><?=$ts_labels[$i]?></span>
          <span class="legend-val"><?=$task_status[$k]?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="chart-card">
    <div class="chart-title">By Priority</div>
    <div class="chart-sub">Urgency breakdown</div>
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <div style="width:130px;height:130px;flex-shrink:0"><canvas id="chartTaskPriority"></canvas></div>
      <div style="flex:1;min-width:100px">
        <?php $tp_labels=['Low','Medium','High','Urgent'];$tp_colors=['#10b981','#f59e0b','#f97316','#ef4444'];
        foreach (array_keys($task_prio) as $i=>$k): ?>
        <div class="legend-row">
          <div class="legend-dot" style="background:<?=$tp_colors[$i]?>"></div>
          <span class="legend-lbl"><?=$tp_labels[$i]?></span>
          <span class="legend-val"><?=$task_prio[$k]?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="chart-card">
    <div class="chart-title">Most Discussed Tasks</div>
    <div class="chart-sub">By comment count</div>
    <?php if ($comment_activity): foreach ($comment_activity as $ca): ?>
    <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border)">
      <div style="flex:1;min-width:0">
        <div style="font-size:12px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=h($ca['title'])?></div>
        <div style="font-size:10.5px;color:var(--text3)"><?=date('M j',strtotime($ca['last_comment']))?></div>
      </div>
      <span style="background:var(--orange-bg);color:var(--orange);font-size:11px;font-weight:800;padding:2px 7px;border-radius:99px;flex-shrink:0"><?=$ca['cnt']?> 💬</span>
    </div>
    <?php endforeach; else: ?>
    <div style="color:var(--text3);font-size:12.5px;padding:20px 0;text-align:center">No comments yet</div>
    <?php endif; ?>
  </div>
</div>

<!-- ══ SECTION: TEAM ══ -->
<div class="section-heading">👥 Team Performance</div>
<div class="an-grid-2">
  <div class="chart-card">
    <div class="chart-title">Workload Distribution</div>
    <div class="chart-sub">Open vs completed tasks per member</div>
    <div class="chart-wrap" style="height:<?=max(180,count($member_tasks)*36+20)?>px">
      <canvas id="chartTeam"></canvas>
    </div>
  </div>

  <div class="chart-card">
    <div class="chart-title">On-Time Performance</div>
    <div class="chart-sub">Tasks completed before deadline</div>
    <?php foreach ($team_productivity as $tp):
      $rate = $tp['done']>0 ? round($tp['on_time']/$tp['done']*100) : 0;
      $col  = $rate>=80?'#10b981':($rate>=50?'#f59e0b':'#ef4444');
    ?>
    <div class="prod-row">
      <div class="prod-name"><?=h($tp['name'])?></div>
      <div class="prod-bar"><div class="prod-fill" style="width:<?=$rate?>%;background:<?=$col?>"></div></div>
      <div class="prod-val" style="color:<?=$col?>"><?=$rate?>%</div>
      <div style="font-size:11px;color:var(--text3);width:50px;text-align:right;flex-shrink:0"><?=$tp['done']?> done</div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ══ SECTION: SALES PIPELINE ══ -->
<div class="section-heading">🎯 Sales Pipeline</div>
<div class="an-grid-2">
  <div class="chart-card">
    <div class="chart-title">Lead Funnel</div>
    <div class="chart-sub"><?=$lead_total?> total leads · <?=$conv_rate?>% conversion rate</div>
    <?php
    $funnel_stages = ['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified',
                      'proposal'=>'Proposal','negotiation'=>'Negotiating','won'=>'Won','lost'=>'Lost'];
    $funnel_colors = ['new'=>'#94a3b8','contacted'=>'#6366f1','qualified'=>'#3b82f6',
                      'proposal'=>'#f59e0b','negotiation'=>'#f97316','won'=>'#10b981','lost'=>'#ef4444'];
    $stage_map=[];foreach($lead_stages as $r) $stage_map[$r['stage']]=(int)$r['cnt'];
    $max_stage = max(array_values($stage_map) ?: [1]);
    foreach ($funnel_stages as $sk=>$sl):
      $cnt = $stage_map[$sk] ?? 0;
      $pct = $max_stage>0 ? round($cnt/$max_stage*100) : 0;
    ?>
    <div class="funnel-step">
      <div class="funnel-lbl"><?=$sl?></div>
      <div class="funnel-bar-wrap">
        <div class="funnel-bar-fill" style="width:<?=max($pct,2)?>%;background:<?=$funnel_colors[$sk]?>"><?=$cnt>0?$cnt:''?></div>
      </div>
      <div class="funnel-cnt"><?=$cnt?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="chart-card">
    <div class="chart-title">Lead Sources</div>
    <div class="chart-sub">Where leads come from · win rate per source</div>
    <div class="chart-wrap" style="height:220px"><canvas id="chartLeadSource"></canvas></div>
  </div>
</div>

<!-- ══ SECTION: FINANCE ══ -->
<?php if (!empty($expense_by_cat)): ?>
<div class="section-heading">💸 Expense Breakdown</div>
<div class="an-grid-2">
  <div class="chart-card">
    <div class="chart-title">Expenses by Category</div>
    <div class="chart-sub">Last 3 months · Total: Rs. <?=number_format($expense_stats['total_spend'],0)?></div>
    <div class="chart-wrap" style="height:200px"><canvas id="chartExpCat"></canvas></div>
  </div>
  <div class="chart-card">
    <div class="chart-title">Revenue vs Expenses</div>
    <div class="chart-sub">Monthly comparison (last 6 months)</div>
    <?php if ($expense_by_month): ?>
    <div class="chart-wrap" style="height:200px"><canvas id="chartRevExp"></canvas></div>
    <?php else: ?>
    <div style="text-align:center;padding:40px;color:var(--text3)">No monthly data yet</div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- ══ SECTION: PROJECTS ══ -->
<div class="section-heading">📁 Project Intelligence</div>
<div class="an-grid-2">
  <div class="chart-card">
    <div class="chart-title">Portfolio Status</div>
    <div class="chart-sub">All projects by stage</div>
    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
      <div style="width:150px;height:150px;flex-shrink:0"><canvas id="chartProjStatus"></canvas></div>
      <div style="flex:1;min-width:120px">
        <?php $ps_labels=['Planning','Active','On Hold','Completed','Cancelled'];$ps_colors=['#6366f1','#10b981','#94a3b8','#f97316','#ef4444'];
        foreach (array_keys($proj_status) as $i=>$k): ?>
        <div class="legend-row">
          <div class="legend-dot" style="background:<?=$ps_colors[$i]?>"></div>
          <span class="legend-lbl"><?=$ps_labels[$i]?></span>
          <span class="legend-val"><?=$proj_status[$k]?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="chart-card">
    <div class="chart-title">Contact Relationships</div>
    <div class="chart-sub">CRM contact type distribution</div>
    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
      <div style="width:150px;height:150px;flex-shrink:0"><canvas id="chartContacts"></canvas></div>
      <div style="flex:1;min-width:120px">
        <?php $ct_labels=['Client','Lead','Partner','Vendor'];$ct_colors=['#10b981','#f97316','#6366f1','#f59e0b'];
        foreach (array_keys($contact_types) as $i=>$k): ?>
        <div class="legend-row">
          <div class="legend-dot" style="background:<?=$ct_colors[$i]?>"></div>
          <span class="legend-lbl"><?=$ct_labels[$i]?></span>
          <span class="legend-val"><?=$contact_types[$k]?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Project progress table -->
<div class="chart-card" style="margin-bottom:18px">
  <div class="chart-title">Project Progress</div>
  <div class="chart-sub">Task completion per project</div>
  <?php if ($top_projects): ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Project</th><th>Status</th><th>Progress</th><th>Tasks</th><th>Due</th></tr></thead>
      <tbody>
        <?php foreach ($top_projects as $p):
          $pct=$p['total']>0?round($p['done']/$p['total']*100):0;
          $sc=statusColor($p['status']);
          $od=$p['due_date']&&$p['due_date']<date('Y-m-d')&&$p['status']!=='completed';
        ?>
        <tr>
          <td class="td-main"><?=h($p['title'])?></td>
          <td><span class="badge" style="background:<?=$sc?>20;color:<?=$sc?>"><?=h(str_replace('_',' ',$p['status']))?></span></td>
          <td style="min-width:160px">
            <div style="display:flex;align-items:center;gap:8px">
              <div class="progress-bar" style="flex:1"><div class="progress-fill" style="width:<?=$pct?>%;background:<?=$pct>=75?'#10b981':($pct>=40?'#f59e0b':'#ef4444')?>"></div></div>
              <span style="font-size:11px;color:var(--text3);width:32px;text-align:right"><?=$pct?>%</span>
            </div>
          </td>
          <td><span style="color:var(--green);font-weight:700"><?=$p['done']?></span><span style="color:var(--text3)"> / <?=$p['total']?></span></td>
          <td style="<?=$od?'color:var(--red)':''?>"><?=fDate($p['due_date'])?><?=$od?' ⚠':''?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ══ SECTION: ACTIVITY & EMAIL ══ -->
<div class="section-heading">⚡ Activity & Communications</div>
<div class="an-grid-2">
  <div class="chart-card">
    <div class="chart-title">Activity by Hour</div>
    <div class="chart-sub">Team activity heatmap — last 30 days (hover for count)</div>
    <div class="heat-grid" id="heatmap"></div>
    <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--text3);margin-top:6px;padding:0 1px">
      <span>12am</span><span>6am</span><span>12pm</span><span>6pm</span><span>11pm</span>
    </div>
  </div>

  <div class="chart-card">
    <div class="chart-title">Email Performance</div>
    <div class="chart-sub">Sent, failed, opened — all time</div>
    <?php if ((int)($email_stats['total']??0) > 0): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
      <?php foreach ([
        ['Sent',   $email_stats['sent'],   '#10b981'],
        ['Failed', $email_stats['failed'], '#ef4444'],
        ['Opened', $email_stats['opened'], '#6366f1'],
        ['Total',  $email_stats['total'],  '#f97316'],
      ] as [$l,$v,$c]): ?>
      <div style="background:var(--bg3);border-radius:var(--radius-sm);padding:10px 12px;border-left:3px solid <?=$c?>">
        <div style="font-size:18px;font-weight:800;color:<?=$c?>"><?=$v?></div>
        <div style="font-size:10.5px;color:var(--text3);text-transform:uppercase"><?=$l?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if ($email_by_day): ?>
    <div class="chart-wrap" style="height:100px"><canvas id="chartEmail"></canvas></div>
    <?php endif; ?>
    <?php else: ?>
    <div style="text-align:center;padding:30px;color:var(--text3)"><div style="font-size:28px;margin-bottom:8px">📧</div>No emails sent yet</div>
    <?php endif; ?>
  </div>
</div>

<!-- Documents by category -->
<?php if ($doc_cats): ?>
<div class="chart-card" style="margin-bottom:18px">
  <div class="chart-title">Documents by Category</div>
  <div class="chart-sub">File store distribution</div>
  <div class="chart-wrap" style="height:160px"><canvas id="chartDocs"></canvas></div>
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
function cssVar(v){return getComputedStyle(document.documentElement).getPropertyValue(v).trim()}
Chart.defaults.font.family="'Plus Jakarta Sans',sans-serif";
Chart.defaults.font.size=12;

function applyThemeToCharts(){
  Chart.helpers.each(Chart.instances,function(ch){
    if(ch.options.scales){
      Object.values(ch.options.scales).forEach(function(s){
        if(s.grid)s.grid.color=cssVar('--border');
        if(s.ticks)s.ticks.color=cssVar('--text2');
      });
    }
    if(ch.options.plugins&&ch.options.plugins.legend&&ch.options.plugins.legend.labels)
      ch.options.plugins.legend.labels.color=cssVar('--text2');
    ch.update('none');
  });
}
document.addEventListener('themeChanged',applyThemeToCharts);

var scaleDefaults={
  x:{grid:{color:cssVar('--border')},ticks:{color:cssVar('--text2')}},
  y:{grid:{color:cssVar('--border')},ticks:{color:cssVar('--text2'),precision:0},beginAtZero:true}
};

// ── TIMELINE ──
new Chart(document.getElementById('chartTimeline'),{
  type:'line',
  data:{
    labels:<?=json_encode($time_labels)?>,
    datasets:[
      {label:'Created',data:<?=json_encode($time_data_created)?>,borderColor:'#f97316',backgroundColor:'rgba(249,115,22,.1)',fill:true,tension:.4,pointRadius:<?=$days_back<=30?3:0?>,borderWidth:2},
      {label:'Completed',data:<?=json_encode($time_data_done)?>,borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.08)',fill:true,tension:.4,pointRadius:<?=$days_back<=30?3:0?>,borderWidth:2}
    ]
  },
  options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},
    plugins:{legend:{position:'top',labels:{boxWidth:10,padding:16,color:cssVar('--text2')}}},
    scales:{x:{grid:{color:cssVar('--border')},ticks:{color:cssVar('--text2'),maxTicksLimit:12}},
            y:{grid:{color:cssVar('--border')},ticks:{color:cssVar('--text2'),precision:0},beginAtZero:true}}}
});

// ── TASK STATUS DOUGHNUT ──
new Chart(document.getElementById('chartTaskStatus'),{type:'doughnut',
  data:{labels:['To Do','In Progress','Review','Done'],
    datasets:[{data:<?=json_encode(array_values($task_status))?>,backgroundColor:['#6366f1','#f59e0b','#8b5cf6','#10b981'],borderWidth:0,hoverOffset:4}]},
  options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false}}}
});

// ── TASK PRIORITY DOUGHNUT ──
new Chart(document.getElementById('chartTaskPriority'),{type:'doughnut',
  data:{labels:['Low','Medium','High','Urgent'],
    datasets:[{data:<?=json_encode(array_values($task_prio))?>,backgroundColor:['#10b981','#f59e0b','#f97316','#ef4444'],borderWidth:0,hoverOffset:4}]},
  options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false}}}
});

// ── TEAM WORKLOAD ──
<?php if($member_tasks):?>
new Chart(document.getElementById('chartTeam'),{type:'bar',
  data:{labels:<?=json_encode(array_column($member_tasks,'name'))?>,
    datasets:[
      {label:'Open',data:<?=json_encode(array_column($member_tasks,'open_cnt'))?>,backgroundColor:'#f97316',borderRadius:4},
      {label:'Done',data:<?=json_encode(array_column($member_tasks,'done_cnt'))?>,backgroundColor:'#10b981',borderRadius:4}
    ]},
  options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,
    plugins:{legend:{position:'top',labels:{boxWidth:10,padding:14,color:cssVar('--text2')}}},
    scales:{x:{grid:{color:cssVar('--border')},ticks:{color:cssVar('--text2'),precision:0},beginAtZero:true},
            y:{grid:{display:false},ticks:{color:cssVar('--text2')}}}}
});
<?php endif;?>

// ── PROJECT STATUS DOUGHNUT ──
new Chart(document.getElementById('chartProjStatus'),{type:'doughnut',
  data:{labels:['Planning','Active','On Hold','Completed','Cancelled'],
    datasets:[{data:<?=json_encode(array_values($proj_status))?>,backgroundColor:['#6366f1','#10b981','#94a3b8','#f97316','#ef4444'],borderWidth:0,hoverOffset:4}]},
  options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false}}}
});

// ── CONTACTS DOUGHNUT ──
new Chart(document.getElementById('chartContacts'),{type:'doughnut',
  data:{labels:['Client','Lead','Partner','Vendor'],
    datasets:[{data:<?=json_encode(array_values($contact_types))?>,backgroundColor:['#10b981','#f97316','#6366f1','#f59e0b'],borderWidth:0,hoverOffset:4}]},
  options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false}}}
});

// ── DOCUMENTS BAR ──
<?php if($doc_cats):?>
new Chart(document.getElementById('chartDocs'),{type:'bar',
  data:{labels:<?=json_encode(array_column($doc_cats,'category'))?>,
    datasets:[{label:'Docs',data:<?=json_encode(array_column($doc_cats,'cnt'))?>,
      backgroundColor:['#f97316','#6366f1','#10b981','#f59e0b','#8b5cf6','#ef4444','#94a3b8','#14b8a6'],
      borderRadius:5,borderWidth:0}]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
    scales:{x:{grid:{display:false},ticks:{color:cssVar('--text2')}},
            y:{grid:{color:cssVar('--border')},ticks:{color:cssVar('--text2'),precision:0},beginAtZero:true}}}
});
<?php endif;?>

// ── REVENUE BAR ──
<?php if($revenue_months):?>
new Chart(document.getElementById('chartRevenue'),{type:'bar',
  data:{labels:<?=json_encode(array_column($revenue_months,'mo'))?>,
    datasets:[{label:'Collected (Rs.)',data:<?=json_encode(array_map(fn($r)=>(float)$r['collected'],$revenue_months))?>,
      backgroundColor:'rgba(16,185,129,.75)',borderRadius:6,borderWidth:0}]},
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false}},
    scales:{x:{grid:{display:false},ticks:{color:cssVar('--text2')}},
            y:{grid:{color:cssVar('--border')},ticks:{color:cssVar('--text2'),precision:0,callback:function(v){return'Rs.'+v.toLocaleString()}},beginAtZero:true}}}
});
<?php endif;?>

// ── LEAD SOURCE ──
<?php if($lead_sources):?>
new Chart(document.getElementById('chartLeadSource'),{type:'bar',
  data:{labels:<?=json_encode(array_column($lead_sources,'source'))?>,
    datasets:[
      {label:'Total',data:<?=json_encode(array_map(fn($r)=>(int)$r['cnt'],$lead_sources))?>,backgroundColor:'rgba(99,102,241,.7)',borderRadius:4},
      {label:'Won',  data:<?=json_encode(array_map(fn($r)=>(int)$r['won_cnt'],$lead_sources))?>,backgroundColor:'rgba(16,185,129,.85)',borderRadius:4}
    ]},
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{position:'top',labels:{boxWidth:10,padding:12,color:cssVar('--text2')}}},
    scales:{x:{grid:{display:false},ticks:{color:cssVar('--text2')}},
            y:{grid:{color:cssVar('--border')},ticks:{color:cssVar('--text2'),precision:0},beginAtZero:true}}}
});
<?php endif;?>

// ── EXPENSE BY CATEGORY ──
<?php if($expense_by_cat):?>
new Chart(document.getElementById('chartExpCat'),{type:'doughnut',
  data:{labels:<?=json_encode(array_column($expense_by_cat,'category'))?>,
    datasets:[{data:<?=json_encode(array_map(fn($r)=>(float)$r['total'],$expense_by_cat))?>,
      backgroundColor:['#f97316','#6366f1','#10b981','#f59e0b','#8b5cf6','#ef4444','#94a3b8','#14b8a6'],
      borderWidth:2,borderColor:cssVar('--bg2'),hoverOffset:4}]},
  options:{responsive:true,maintainAspectRatio:false,cutout:'60%',
    plugins:{legend:{position:'right',labels:{boxWidth:10,padding:10,color:cssVar('--text2'),font:{size:11}}}}}
});
<?php endif;?>

// ── REVENUE vs EXPENSES ──
<?php if($expense_by_month):?>
new Chart(document.getElementById('chartRevExp'),{type:'bar',
  data:{
    labels:<?=json_encode(array_column($expense_by_month,'mo'))?>,
    datasets:[
      {label:'Revenue',data:<?=json_encode(array_map(fn($r)=>(float)$r['revenue'],$expense_by_month))?>,backgroundColor:'rgba(16,185,129,.7)',borderRadius:4},
      {label:'Expenses',data:<?=json_encode(array_map(fn($r)=>(float)$r['total'],$expense_by_month))?>,backgroundColor:'rgba(239,68,68,.65)',borderRadius:4}
    ]},
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{position:'top',labels:{boxWidth:10,padding:10,color:cssVar('--text2')}}},
    scales:{x:{grid:{display:false},ticks:{color:cssVar('--text2')}},
            y:{grid:{color:cssVar('--border')},ticks:{color:cssVar('--text2'),precision:0},beginAtZero:true}}}
});
<?php endif;?>

// ── EMAIL SPARKLINE ──
<?php if(!empty($email_by_day)):?>
new Chart(document.getElementById('chartEmail'),{type:'line',
  data:{labels:<?=json_encode(array_map(fn($r)=>date('M j',strtotime($r['d'])),$email_by_day))?>,
    datasets:[
      {label:'Total', data:<?=json_encode(array_map(fn($r)=>(int)$r['total'],$email_by_day))?>,borderColor:'#f97316',fill:false,tension:.4,pointRadius:2,borderWidth:1.5},
      {label:'Sent',  data:<?=json_encode(array_map(fn($r)=>(int)$r['sent'],$email_by_day))?>, borderColor:'#10b981',fill:false,tension:.4,pointRadius:2,borderWidth:1.5}
    ]},
  options:{responsive:true,maintainAspectRatio:false,
    plugins:{legend:{position:'top',labels:{boxWidth:8,padding:10,color:cssVar('--text2'),font:{size:10}}}},
    scales:{x:{grid:{display:false},ticks:{color:cssVar('--text2'),font:{size:9}}},
            y:{grid:{color:cssVar('--border')},ticks:{color:cssVar('--text2'),precision:0},beginAtZero:true}}}
});
<?php endif;?>

// ── ACTIVITY HEATMAP ──
(function(){
  var data=<?=json_encode($hour_data)?>;
  var max=Math.max.apply(null,data)||1;
  var heat=document.getElementById('heatmap');
  if(!heat)return;
  for(var i=0;i<24;i++){
    var cell=document.createElement('div');
    cell.className='heat-cell';
    var intensity=data[i]/max;
    var alpha=0.1+intensity*0.85;
    cell.style.background='rgba(249,115,22,'+alpha.toFixed(2)+')';
    cell.dataset.tip=i+':00 — '+data[i]+' actions';
    heat.appendChild(cell);
  }
})();
</script>

<?php renderLayoutEnd();?>