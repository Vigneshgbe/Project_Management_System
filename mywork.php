<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

// ── POST HANDLERS ──
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Inline task status update ──
    if ($action === 'quick_status') {
        $id  = (int)$_POST['id'];
        $st  = $_POST['status'] ?? '';
        if (in_array($st, ['todo','in_progress','review','done'])) {
            $done = $st === 'done' ? 'NOW()' : 'NULL';
            $db->query("UPDATE tasks SET status='$st',completed_at=$done WHERE id=$id AND assigned_to=$uid");
            // Fire notification if done
            if ($st === 'done') logActivity('completed task', 'task', $id);
        }
        ob_end_clean();
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
        } else {
            header('Location: mywork.php');
        }
        exit;
    }

    // ── Quick create task ──
    if ($action === 'quick_task') {
        $title   = trim($_POST['title'] ?? '');
        $due     = $_POST['due_date'] ?: null;
        $proj    = (int)($_POST['project_id'] ?? 0) ?: null;
        $priority= $_POST['priority'] ?? 'medium';
        if ($title) {
            $stmt = $db->prepare("INSERT INTO tasks (title,project_id,assigned_to,created_by,status,priority,due_date) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("siiisss", $title,$proj,$uid,$uid,'todo',$priority,$due);
            $stmt->execute();
            logActivity('created task', 'task', $db->insert_id);
            flash('Task created.', 'success');
        }
        ob_end_clean(); header('Location: mywork.php'); exit;
    }

    // ── Quick add expense ──
    if ($action === 'quick_expense') {
        $desc    = trim($_POST['description'] ?? '');
        $amount  = (float)($_POST['amount'] ?? 0);
        $cat     = $_POST['category'] ?? 'Other';
        $curr    = $_POST['currency'] ?? 'LKR';
        $pdate   = $_POST['purchase_date'] ?: date('Y-m-d');
        if ($desc && $amount > 0) {
            $office = 0.0;
            // Find or create current month record
            $my = date('Y-m');
            $mon = $db->query("SELECT id FROM expense_months WHERE month_year='$my'")->fetch_assoc();
            if (!$mon) {
                $ml = date('F Y');
                $db->query("INSERT INTO expense_months (month_year,month_label,revenue,created_by) VALUES ('$my','$ml',0,$uid)");
                $mid = $db->insert_id;
            } else {
                $mid = (int)$mon['id'];
            }
            $stmt = $db->prepare("INSERT INTO expense_entries (month_id,category,description,own_spend,office_spend,currency,purchase_date,created_by) VALUES (?,?,?,?,0,?,?,?)");
            $stmt->bind_param("issddssi", $mid,$cat,$desc,$amount,$office,$curr,$pdate,$uid);
            $stmt->execute();
            logActivity('logged expense', 'expense', $db->insert_id);
            flash('Expense logged.', 'success');
        }
        ob_end_clean(); header('Location: mywork.php'); exit;
    }
}
ob_end_clean();

// ── DATA QUERIES ──

// My tasks by status bucket
$my_tasks = $db->query("
    SELECT t.*, p.title AS proj_title,
        CASE
            WHEN t.status != 'done' AND t.due_date < CURDATE() THEN 'overdue'
            WHEN t.status = 'done' THEN 'done'
            ELSE t.status
        END AS bucket
    FROM tasks t
    LEFT JOIN projects p ON p.id = t.project_id
    WHERE t.assigned_to = $uid AND t.status != 'done'
    ORDER BY
        CASE WHEN t.due_date < CURDATE() AND t.status != 'done' THEN 0 ELSE 1 END,
        FIELD(t.priority,'urgent','high','medium','low'),
        t.due_date ASC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

// Group tasks
$tasks_todo     = array_values(array_filter($my_tasks, fn($t) => $t['bucket']==='todo'));
$tasks_inprog   = array_values(array_filter($my_tasks, fn($t) => $t['bucket']==='in_progress'));
$tasks_review   = array_values(array_filter($my_tasks, fn($t) => $t['bucket']==='review'));
$tasks_overdue  = array_values(array_filter($my_tasks, fn($t) => $t['bucket']==='overdue'));

$done_today = (int)$db->query("SELECT COUNT(*) AS c FROM tasks WHERE assigned_to=$uid AND DATE(completed_at)=CURDATE()")->fetch_assoc()['c'];

// Today's + this week calendar events
$week_start = date('Y-m-d');
$week_end   = date('Y-m-d', strtotime('+6 days'));
$today      = date('Y-m-d');

$cal_events = $db->query("
    SELECT ce.id, ce.title, ce.event_type, ce.start_datetime, ce.end_datetime,
           ce.all_day, ce.location, ce.color,
           p.title AS proj_title
    FROM calendar_events ce
    LEFT JOIN projects p ON p.id = ce.project_id
    LEFT JOIN calendar_attendees ca ON ca.event_id = ce.id AND ca.user_id = $uid
    WHERE (ce.created_by = $uid OR ca.user_id = $uid)
      AND DATE(ce.start_datetime) BETWEEN '$week_start' AND '$week_end'
    ORDER BY ce.start_datetime ASC
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

$today_events = array_values(array_filter($cal_events, fn($e) => date('Y-m-d', strtotime($e['start_datetime'])) === $today));
$week_events  = array_values(array_filter($cal_events, fn($e) => date('Y-m-d', strtotime($e['start_datetime'])) !== $today));

// Task deadlines this week as pseudo-events
$deadline_tasks = $db->query("
    SELECT t.id, t.title, t.due_date, t.priority, p.title AS proj_title
    FROM tasks t
    LEFT JOIN projects p ON p.id = t.project_id
    WHERE t.assigned_to = $uid AND t.status != 'done'
      AND t.due_date BETWEEN '$week_start' AND '$week_end'
    ORDER BY t.due_date ASC
")->fetch_all(MYSQLI_ASSOC);

// My active projects (member or creator)
$my_projects = $db->query("
    SELECT p.*, c.name AS client_name,
        (SELECT COUNT(*) FROM tasks t WHERE t.project_id=p.id AND t.assigned_to=$uid AND t.status!='done') AS my_open_tasks,
        (SELECT COUNT(*) FROM tasks t WHERE t.project_id=p.id AND t.assigned_to=$uid AND t.status='done') AS my_done_tasks,
        (SELECT COUNT(*) FROM tasks t WHERE t.project_id=p.id) AS total_tasks
    FROM projects p
    LEFT JOIN contacts c ON c.id = p.contact_id
    WHERE p.status = 'active'
      AND (p.created_by = $uid
           OR EXISTS(SELECT 1 FROM project_members pm WHERE pm.project_id=p.id AND pm.user_id=$uid))
    ORDER BY p.due_date ASC, p.created_at DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// My recent activity (last 5 actions by me)
$my_activity = $db->query("
    SELECT action, entity_type, entity_id, details, created_at
    FROM activity_log
    WHERE user_id = $uid
    ORDER BY created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// ── EXTRA PERSONAL DATA (no new tables) ──

// My assigned leads (active pipeline)
$my_leads = $db->query("
    SELECT id, name, company, stage, priority, budget_est, budget_currency, expected_close
    FROM leads
    WHERE assigned_to = $uid AND stage NOT IN ('won','lost')
    ORDER BY FIELD(priority,'urgent','high','medium','low'), expected_close ASC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// My expense spend this month
$month_start = date('Y-m-01');
$my_expense_month = $db->query("
    SELECT COALESCE(SUM(ee.own_spend),0) AS total, COUNT(*) AS count, ee.currency
    FROM expense_entries ee
    JOIN expense_months em ON em.id = ee.month_id
    WHERE ee.created_by = $uid AND em.month_year = '".date('Y-m')."'
    GROUP BY ee.currency ORDER BY total DESC LIMIT 1
")->fetch_assoc();

// My invoices summary (created by me)
$my_invoices = $db->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) AS paid_count,
        SUM(CASE WHEN status IN('sent','partial','overdue') THEN total-amount_paid ELSE 0 END) AS outstanding,
        currency
    FROM invoices
    WHERE created_by = $uid AND status != 'cancelled'
    GROUP BY currency ORDER BY outstanding DESC LIMIT 1
")->fetch_assoc();

// Tasks I created assigned to others (delegation view)
$delegated_tasks = $db->query("
    SELECT t.id, t.title, t.status, t.priority, t.due_date,
           u.name AS assignee_name, p.title AS proj_title
    FROM tasks t
    JOIN users u ON u.id = t.assigned_to
    LEFT JOIN projects p ON p.id = t.project_id
    WHERE t.created_by = $uid AND t.assigned_to != $uid AND t.status != 'done'
    ORDER BY FIELD(t.priority,'urgent','high','medium','low'), t.due_date ASC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Unread client messages on my projects
$unread_client_msgs = $db->query("
    SELECT cm.id, cm.subject, cm.body, cm.created_at,
           c.name AS client_name, p.title AS proj_title, p.id AS proj_id
    FROM client_messages cm
    JOIN contacts c ON c.id = cm.contact_id
    LEFT JOIN projects p ON p.id = cm.project_id
    WHERE cm.sender_type = 'client' AND cm.is_read = 0
      AND (p.created_by = $uid OR cm.project_id IN (
          SELECT project_id FROM project_members WHERE user_id = $uid
      ))
    ORDER BY cm.created_at DESC
    LIMIT 3
")->fetch_all(MYSQLI_ASSOC);

// My productivity: tasks completed this week vs last week
$week_done = (int)$db->query("SELECT COUNT(*) AS c FROM tasks WHERE assigned_to=$uid AND completed_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)")->fetch_assoc()['c'];
$prev_week_done = (int)$db->query("SELECT COUNT(*) AS c FROM tasks WHERE assigned_to=$uid AND completed_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE())+7 DAY) AND completed_at < DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)")->fetch_assoc()['c'];

// Quick stats
$stats = [
    'overdue'      => count($tasks_overdue),
    'todo'         => count($tasks_todo),
    'inprog'       => count($tasks_inprog),
    'done_today'   => $done_today,
    'projects'     => count($my_projects),
    'events_today' => count($today_events),
    'my_leads'     => count($my_leads),
    'delegated'    => count($delegated_tasks),
    'unread_client'=> count($unread_client_msgs),
    'week_done'    => $week_done,
];

// Projects for quick-task dropdown
$proj_list = $db->query("
    SELECT p.id, p.title FROM projects p
    WHERE p.status='active'
      AND (p.created_by=$uid OR EXISTS(SELECT 1 FROM project_members pm WHERE pm.project_id=p.id AND pm.user_id=$uid))
    ORDER BY p.title
")->fetch_all(MYSQLI_ASSOC);

renderLayout('My Work', 'mywork');
?>

<style>
/* ── MY WORK PAGE ── */
.mw-grid{display:grid;grid-template-columns:1fr 1fr 340px;gap:16px;align-items:start}

/* KPI strip */
.mw-kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:20px}
.mw-kpi{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:12px 14px;text-align:center;transition:border-color .15s}
.mw-kpi:hover{border-color:var(--border2)}
.mw-kpi-val{font-size:22px;font-weight:800;font-family:var(--font-display);line-height:1}
.mw-kpi-lbl{font-size:10.5px;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-top:4px}

/* Section cards */
.mw-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.mw-card-head{display:flex;align-items:center;justify-content:space-between;padding:13px 16px;border-bottom:1px solid var(--border);background:var(--bg3)}
.mw-card-title{font-size:13px;font-weight:700;font-family:var(--font-display);color:var(--text);display:flex;align-items:center;gap:7px}
.mw-card-body{padding:12px 14px}
.mw-card-empty{text-align:center;padding:24px 14px;color:var(--text3);font-size:12.5px}

/* Task bucket header */
.task-bucket{margin-bottom:14px}
.task-bucket-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px;display:flex;align-items:center;gap:6px}
.task-bucket-cnt{background:var(--bg4);border-radius:99px;padding:1px 7px;font-size:10px;font-weight:700}

/* Task row */
.mw-task{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:var(--radius-sm);border:1px solid var(--border);margin-bottom:5px;background:var(--bg3);transition:border-color .12s,background .12s;position:relative;cursor:default}
.mw-task:hover{border-color:var(--border2);background:var(--bg4)}
.mw-task.overdue{border-left:3px solid var(--red)}
.mw-task.urgent{border-left:3px solid var(--orange)}
.mw-task-check{width:18px;height:18px;border-radius:4px;border:2px solid var(--border);background:none;cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .12s;font-size:10px}
.mw-task-check:hover{border-color:var(--green);background:rgba(16,185,129,.12)}
.mw-task-body{flex:1;min-width:0}
.mw-task-title{font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mw-task-meta{display:flex;align-items:center;gap:6px;margin-top:2px;flex-wrap:wrap}
.mw-task-meta span{font-size:11px;color:var(--text3)}
.mw-task-meta .overdue-lbl{color:var(--red);font-weight:700}
.task-status-sel{border:none;background:var(--bg4);color:var(--text2);font-size:11px;border-radius:4px;padding:2px 5px;cursor:pointer;font-family:var(--font);flex-shrink:0}
.task-status-sel:focus{outline:1px solid var(--orange)}

/* Calendar events */
.mw-event{display:flex;align-items:flex-start;gap:10px;padding:8px 10px;border-radius:var(--radius-sm);background:var(--bg3);border:1px solid var(--border);margin-bottom:5px}
.mw-event-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;margin-top:4px}
.mw-event-body{flex:1;min-width:0}
.mw-event-title{font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mw-event-meta{font-size:11.5px;color:var(--text3);margin-top:1px}
.mw-deadline-row{display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:var(--radius-sm);background:rgba(249,115,22,.07);border:1px solid rgba(249,115,22,.2);margin-bottom:5px}

/* Day divider */
.day-divider{font-size:10.5px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;padding:8px 0 4px;display:flex;align-items:center;gap:8px}
.day-divider::after{content:'';flex:1;height:1px;background:var(--border)}

/* Project cards in right column */
.mw-proj{padding:12px 14px;border-bottom:1px solid var(--border);transition:background .1s;cursor:pointer}
.mw-proj:last-child{border-bottom:none}
.mw-proj:hover{background:var(--bg3)}
.mw-proj-title{font-size:13px;font-weight:700;color:var(--text);margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mw-proj-bar{height:5px;background:var(--bg4);border-radius:99px;margin:6px 0 4px;overflow:hidden}
.mw-proj-fill{height:100%;background:var(--orange);border-radius:99px;transition:width .3s}
.mw-proj-meta{display:flex;justify-content:space-between;font-size:11px;color:var(--text3)}

/* Activity feed */
.mw-act{display:flex;align-items:flex-start;gap:8px;padding:7px 0;border-bottom:1px solid var(--border)}
.mw-act:last-child{border-bottom:none}
.mw-act-icon{width:26px;height:26px;border-radius:50%;background:var(--bg4);display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;margin-top:1px}
.mw-act-body{flex:1;min-width:0}
.mw-act-text{font-size:12.5px;color:var(--text2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mw-act-time{font-size:11px;color:var(--text3)}

/* Quick action buttons */
.qa-row{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px}
.qa-btn{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:12px 6px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);cursor:pointer;font-size:12px;font-weight:600;color:var(--text2);transition:all .15s;text-decoration:none}
.qa-btn:hover{background:var(--orange-bg);border-color:var(--orange);color:var(--orange)}
.qa-btn span:first-child{font-size:20px}

/* Quick form panels */
.qa-form{background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:12px;margin-top:8px;display:none}
.qa-form.open{display:block}
.qa-form .form-group{margin-bottom:8px}
.qa-form .form-label{font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;margin-bottom:3px;display:block}
.qa-form .form-control{padding:7px 10px;font-size:12.5px}

/* Inline edit input */
.task-edit-input{background:var(--bg);border:1px solid var(--orange);border-radius:4px;color:var(--text);font-size:13px;font-family:var(--font);padding:2px 6px;width:100%;outline:none}

@media(max-width:1200px){.mw-grid{grid-template-columns:1fr 1fr}}
@media(max-width:800px){.mw-grid{grid-template-columns:1fr}.mw-kpis{grid-template-columns:repeat(3,1fr)}}
@media(max-width:480px){.mw-kpis{grid-template-columns:repeat(2,1fr)}}
</style>

<!-- ── KPI STRIP ── -->
<div class="mw-kpis">
  <?php
  $trend = $week_done > $prev_week_done ? '↑' : ($week_done < $prev_week_done ? '↓' : '→');
  $trend_c = $week_done > $prev_week_done ? 'var(--green)' : ($week_done < $prev_week_done ? 'var(--red)' : 'var(--text3)');
  $kpis = [
    [$stats['overdue'],   'Overdue',        $stats['overdue']>0?'var(--red)':'var(--text)',  $stats['overdue']>0?'rgba(239,68,68,.08)':'var(--bg2)'],
    [$stats['inprog'],    'In Progress',    'var(--yellow)',  'rgba(245,158,11,.08)'],
    [$stats['done_today'],'Done Today',     'var(--green)',   'rgba(16,185,129,.08)'],
    [$stats['projects'],  'My Projects',    'var(--orange)',  'var(--orange-bg)'],
    [$stats['events_today'],'Events Today', 'var(--purple)',  'rgba(139,92,246,.08)'],
  ];
  foreach ($kpis as [$v,$l,$c,$bg]):?>
  <div class="mw-kpi" style="background:<?= $bg ?>">
    <div class="mw-kpi-val" style="color:<?= $c ?>"><?= $v ?></div>
    <div class="mw-kpi-lbl"><?= $l ?></div>
  </div>
  <?php endforeach; ?>
  <!-- Productivity trend -->
  <div class="mw-kpi" style="background:rgba(99,102,241,.08);grid-column:span 2">
    <div class="mw-kpi-val" style="display:flex;align-items:baseline;gap:6px;justify-content:center">
      <span style="color:var(--blue)"><?= $week_done ?></span>
      <span style="font-size:14px;color:<?= $trend_c ?>"><?= $trend ?></span>
    </div>
    <div class="mw-kpi-lbl">Tasks done this week <span style="color:var(--text3);font-size:9px">(<?= $prev_week_done ?> last week)</span></div>
  </div>
  <div class="mw-kpi" style="background:rgba(249,115,22,.06)">
    <div class="mw-kpi-val" style="color:var(--orange)"><?= $stats['my_leads'] ?></div>
    <div class="mw-kpi-lbl">My Leads</div>
  </div>
</div>

<div class="mw-grid">

  <!-- ══ COLUMN 1: MY TASKS ══ -->
  <div>
    <div class="mw-card">
      <div class="mw-card-head">
        <div class="mw-card-title">✅ My Tasks</div>
        <a href="tasks.php" style="font-size:11.5px;color:var(--orange);font-weight:600">View all →</a>
      </div>
      <div class="mw-card-body" style="padding:14px 14px">

        <?php
        // Helper: render a task row
        function renderTaskRow(array $t, string $bucket): string {
            $overdue = $bucket === 'overdue';
            $picon   = priorityIcon($t['priority']);
            $due_str = '';
            if ($t['due_date']) {
                $days = (int)floor((strtotime($t['due_date']) - strtotime('today')) / 86400);
                if ($overdue)        $due_str = '<span class="overdue-lbl">'.abs($days).'d overdue</span>';
                elseif ($days === 0) $due_str = '<span style="color:var(--orange);font-weight:700">Due today</span>';
                elseif ($days === 1) $due_str = '<span style="color:var(--yellow)">Due tomorrow</span>';
                else                 $due_str = '<span>Due '.date('M j', strtotime($t['due_date'])).'</span>';
            }
            $proj_str = $t['proj_title'] ? '<span>📁 '.htmlspecialchars($t['proj_title'],ENT_QUOTES).'</span>' : '';
            $status_opts = '';
            foreach (['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'] as $sv=>$sl) {
                $sel = $t['status']===$sv ? 'selected' : '';
                $status_opts .= "<option value=\"$sv\" $sel>$sl</option>";
            }
            $row_cls = $overdue ? 'mw-task overdue' : ($t['priority']==='urgent'?'mw-task urgent':'mw-task');
            return '<div class="'.$row_cls.'" id="task-row-'.$t['id'].'">'
                .'<button class="mw-task-check" onclick="markDone('.$t['id'].',this)" title="Mark done">○</button>'
                .'<div class="mw-task-body" ondblclick="inlineEdit('.$t['id'].',this)">'
                    .'<div class="mw-task-title" id="ttitle-'.$t['id'].'">'.$picon.' '.htmlspecialchars($t['title'],ENT_QUOTES).'</div>'
                    .'<div class="mw-task-meta">'.$due_str.$proj_str.'</div>'
                .'</div>'
                .'<select class="task-status-sel" onchange="quickStatus('.$t['id'].',this.value)" title="Change status">'.$status_opts.'</select>'
                .'</div>';
        }
        ?>

        <?php if ($tasks_overdue): ?>
        <div class="task-bucket">
          <div class="task-bucket-lbl" style="color:var(--red)">🔴 Overdue <span class="task-bucket-cnt" style="background:rgba(239,68,68,.15);color:var(--red)"><?= count($tasks_overdue) ?></span></div>
          <?php foreach ($tasks_overdue as $t) echo renderTaskRow($t,'overdue'); ?>
        </div>
        <?php endif; ?>

        <?php if ($tasks_inprog): ?>
        <div class="task-bucket">
          <div class="task-bucket-lbl" style="color:var(--yellow)">🟡 In Progress <span class="task-bucket-cnt"><?= count($tasks_inprog) ?></span></div>
          <?php foreach ($tasks_inprog as $t) echo renderTaskRow($t,'in_progress'); ?>
        </div>
        <?php endif; ?>

        <?php if ($tasks_review): ?>
        <div class="task-bucket">
          <div class="task-bucket-lbl" style="color:var(--purple)">🟣 Review <span class="task-bucket-cnt"><?= count($tasks_review) ?></span></div>
          <?php foreach ($tasks_review as $t) echo renderTaskRow($t,'review'); ?>
        </div>
        <?php endif; ?>

        <?php if ($tasks_todo): ?>
        <div class="task-bucket">
          <div class="task-bucket-lbl" style="color:var(--blue)">🔵 To Do <span class="task-bucket-cnt"><?= count($tasks_todo) ?></span></div>
          <?php foreach ($tasks_todo as $t) echo renderTaskRow($t,'todo'); ?>
        </div>
        <?php endif; ?>

        <?php if (!$my_tasks): ?>
        <div class="mw-card-empty">
          <div style="font-size:28px;margin-bottom:6px">🎉</div>
          <div style="font-weight:700;color:var(--text2)">All clear!</div>
          <div>No pending tasks assigned to you.</div>
        </div>
        <?php endif; ?>

        <?php if ($done_today > 0): ?>
        <div style="margin-top:10px;padding:8px 10px;background:rgba(16,185,129,.08);border-radius:var(--radius-sm);font-size:12px;color:var(--green);font-weight:600">
          ✅ You completed <?= $done_today ?> task<?= $done_today>1?'s':'' ?> today!
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ══ COLUMN 2: CALENDAR + ACTIVITY ══ -->
  <div>
    <!-- Calendar section -->
    <div class="mw-card" style="margin-bottom:14px">
      <div class="mw-card-head">
        <div class="mw-card-title">📅 This Week</div>
        <a href="calendar.php" style="font-size:11.5px;color:var(--orange);font-weight:600">Calendar →</a>
      </div>
      <div class="mw-card-body">
        <?php
        // Merge calendar events + task deadlines for each day this week
        $days_map = [];
        for ($d = 0; $d < 7; $d++) {
            $dt = date('Y-m-d', strtotime("+$d days"));
            $days_map[$dt] = ['events'=>[], 'deadlines'=>[]];
        }
        foreach ($cal_events as $e) {
            $dt = date('Y-m-d', strtotime($e['start_datetime']));
            if (isset($days_map[$dt])) $days_map[$dt]['events'][] = $e;
        }
        foreach ($deadline_tasks as $t) {
            if (isset($days_map[$t['due_date']])) $days_map[$t['due_date']]['deadlines'][] = $t;
        }
        $has_anything = false;
        foreach ($days_map as $dt => $day):
            if (!$day['events'] && !$day['deadlines']) continue;
            $has_anything = true;
            $is_today = $dt === $today;
            $label    = $is_today ? 'Today' : date('D, M j', strtotime($dt));
        ?>
        <div class="day-divider" style="<?= $is_today ? 'color:var(--orange)' : '' ?>">
          <?= $label ?><?= $is_today ? ' 📍' : '' ?>
        </div>
        <?php foreach ($day['events'] as $e):
          $dot_col = $e['color'] ?: '#6366f1';
          $time_str = $e['all_day'] ? 'All day' : date('g:ia', strtotime($e['start_datetime']));
        ?>
        <div class="mw-event">
          <div class="mw-event-dot" style="background:<?= h($dot_col) ?>"></div>
          <div class="mw-event-body">
            <div class="mw-event-title"><?= h($e['title']) ?></div>
            <div class="mw-event-meta"><?= $time_str ?><?= $e['location'] ? ' · 📍'.h($e['location']) : '' ?><?= $e['proj_title'] ? ' · 📁'.h($e['proj_title']) : '' ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php foreach ($day['deadlines'] as $t): ?>
        <div class="mw-deadline-row">
          <span style="font-size:13px"><?= priorityIcon($t['priority']) ?></span>
          <div style="flex:1;min-width:0">
            <div style="font-size:12.5px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">⏰ <?= h($t['title']) ?></div>
            <div style="font-size:11px;color:var(--text3)">Task deadline<?= $t['proj_title'] ? ' · '.h($t['proj_title']) : '' ?></div>
          </div>
          <a href="tasks.php" style="font-size:11px;color:var(--orange)">View</a>
        </div>
        <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if (!$has_anything): ?>
        <div class="mw-card-empty">
          <div style="font-size:26px;margin-bottom:6px">📭</div>
          No events or deadlines this week.
          <div style="margin-top:6px"><a href="calendar.php" style="color:var(--orange);font-size:12px">Add an event →</a></div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="mw-card">
      <div class="mw-card-head">
        <div class="mw-card-title">🕐 My Recent Activity</div>
        <?php if (isAdmin()): ?><a href="activity.php" style="font-size:11.5px;color:var(--orange);font-weight:600">Full log →</a><?php endif; ?>
      </div>
      <div class="mw-card-body">
        <?php if (!$my_activity): ?>
        <div class="mw-card-empty">No activity recorded yet.</div>
        <?php else: ?>
        <?php
        $act_icons = [
            'created'=>'✚','updated'=>'✎','deleted'=>'🗑','uploaded'=>'↑',
            'completed'=>'✅','login'=>'🔑','mentioned'=>'@','replied'=>'💬',
            'sent'=>'📤','recorded'=>'💳','logged'=>'📝','viewed'=>'👁',
        ];
        foreach ($my_activity as $a):
            $word = strtolower(explode(' ',$a['action'])[0]);
            $icon = $act_icons[$word] ?? '•';
            $age  = '';
            $diff = time() - strtotime($a['created_at']);
            if ($diff < 60)      $age = 'just now';
            elseif ($diff < 3600) $age = floor($diff/60).'m ago';
            elseif ($diff < 86400)$age = floor($diff/3600).'h ago';
            else                  $age = date('M j', strtotime($a['created_at']));
        ?>
        <div class="mw-act">
          <div class="mw-act-icon"><?= $icon ?></div>
          <div class="mw-act-body">
            <div class="mw-act-text"><?= h(ucfirst($a['action'])) ?></div>
            <div class="mw-act-time"><?= $age ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── Delegated Tasks ── -->
    <?php if ($delegated_tasks): ?>
    <div class="mw-card" style="margin-top:14px">
      <div class="mw-card-head">
        <div class="mw-card-title">📤 Tasks I Delegated</div>
        <a href="tasks.php" style="font-size:11.5px;color:var(--orange);font-weight:600">All tasks →</a>
      </div>
      <div class="mw-card-body" style="padding:10px 14px">
        <?php foreach ($delegated_tasks as $t):
          $sc = statusColor($t['status']);
          $overdue_d = $t['due_date'] && $t['due_date'] < date('Y-m-d');
        ?>
        <div class="mw-task" style="margin-bottom:5px">
          <span style="font-size:13px"><?= priorityIcon($t['priority']) ?></span>
          <div class="mw-task-body">
            <div class="mw-task-title"><?= h($t['title']) ?></div>
            <div class="mw-task-meta">
              <span>→ <?= h($t['assignee_name']) ?></span>
              <?php if ($t['proj_title']): ?><span>📁 <?= h($t['proj_title']) ?></span><?php endif; ?>
              <?php if ($t['due_date']): ?>
              <span <?= $overdue_d?'style="color:var(--red);font-weight:700"':'' ?>><?= $overdue_d?'⚠ overdue':date('M j',strtotime($t['due_date'])) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <span style="font-size:11px;font-weight:700;padding:2px 7px;border-radius:99px;background:<?= $sc ?>20;color:<?= $sc ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Unread client messages ── -->
    <?php if ($unread_client_msgs): ?>
    <div class="mw-card" style="margin-top:14px;border-color:var(--orange)">
      <div class="mw-card-head" style="background:var(--orange-bg)">
        <div class="mw-card-title" style="color:var(--orange)">💬 Client Messages <span style="background:var(--red);color:#fff;font-size:9px;padding:1px 5px;border-radius:99px;margin-left:4px"><?= count($unread_client_msgs) ?></span></div>
        <a href="portal_admin.php?tab=messages" style="font-size:11.5px;color:var(--orange);font-weight:600">Reply →</a>
      </div>
      <div class="mw-card-body" style="padding:10px 14px">
        <?php foreach ($unread_client_msgs as $m): ?>
        <div style="padding:8px 10px;background:var(--bg3);border-radius:var(--radius-sm);margin-bottom:6px;cursor:pointer;border:1px solid var(--border)"
             onclick="location.href='portal_admin.php?tab=messages&cid=<?= $m['proj_id'] ?>'">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
            <span style="font-size:12.5px;font-weight:700;color:var(--text)"><?= h($m['client_name']) ?></span>
            <span style="font-size:10.5px;color:var(--text3)"><?= date('M j, g:ia',strtotime($m['created_at'])) ?></span>
          </div>
          <?php if ($m['subject']): ?><div style="font-size:11.5px;font-weight:600;color:var(--text2);margin-bottom:2px"><?= h($m['subject']) ?></div><?php endif; ?>
          <div style="font-size:11.5px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h(mb_substr(strip_tags($m['body']),0,70)) ?>…</div>
          <?php if ($m['proj_title']): ?><div style="font-size:10.5px;color:var(--orange);margin-top:2px">📁 <?= h($m['proj_title']) ?></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- ══ COLUMN 3: QUICK ACTIONS + MY PROJECTS ══ -->
  <div>
    <!-- Quick Actions -->
    <div class="mw-card" style="margin-bottom:14px">
      <div class="mw-card-head">
        <div class="mw-card-title">⚡ Quick Actions</div>
      </div>
      <div class="mw-card-body">
        <div class="qa-row">
          <button class="qa-btn" onclick="toggleQA('qa-task')"><span>✅</span><span>New Task</span></button>
          <button class="qa-btn" onclick="toggleQA('qa-expense')"><span>💰</span><span>Log Expense</span></button>
          <a href="calendar.php" class="qa-btn"><span>📅</span><span>Add Event</span></a>
          <a href="chat.php" class="qa-btn"><span>💬</span><span>Open Chat</span></a>
        </div>

        <!-- Quick Task Form -->
        <div class="qa-form" id="qa-task">
          <form method="POST">
            <input type="hidden" name="action" value="quick_task">
            <div class="form-group">
              <label class="qa-form .form-label" style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:3px">Task Title *</label>
              <input type="text" name="title" class="form-control" style="padding:7px 10px;font-size:12.5px" placeholder="What needs to be done?" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
              <div class="form-group">
                <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:3px">Due Date</label>
                <input type="date" name="due_date" class="form-control" style="padding:7px 10px;font-size:12px" value="<?= date('Y-m-d') ?>">
              </div>
              <div class="form-group">
                <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:3px">Priority</label>
                <select name="priority" class="form-control" style="padding:7px 10px;font-size:12px">
                  <option value="medium">🟡 Medium</option>
                  <option value="high">🟠 High</option>
                  <option value="urgent">🔴 Urgent</option>
                  <option value="low">🟢 Low</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:3px">Project</label>
              <select name="project_id" class="form-control" style="padding:7px 10px;font-size:12px">
                <option value="">— None —</option>
                <?php foreach ($proj_list as $p): ?><option value="<?= $p['id'] ?>"><?= h($p['title']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div style="display:flex;gap:6px;margin-top:2px">
              <button type="submit" class="btn btn-primary btn-sm" style="flex:1">Create Task</button>
              <button type="button" class="btn btn-ghost btn-sm" onclick="toggleQA('qa-task')">Cancel</button>
            </div>
          </form>
        </div>

        <!-- Quick Expense Form -->
        <div class="qa-form" id="qa-expense">
          <form method="POST">
            <input type="hidden" name="action" value="quick_expense">
            <div class="form-group">
              <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:3px">Description *</label>
              <input type="text" name="description" class="form-control" style="padding:7px 10px;font-size:12.5px" placeholder="What did you spend on?" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
              <div class="form-group">
                <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:3px">Amount *</label>
                <input type="number" name="amount" class="form-control" style="padding:7px 10px;font-size:12px" step="0.01" min="0.01" required placeholder="0.00">
              </div>
              <div class="form-group">
                <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:3px">Currency</label>
                <select name="currency" class="form-control" style="padding:7px 10px;font-size:12px">
                  <option value="LKR">LKR</option><option value="USD">USD</option><option value="INR">INR</option>
                </select>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
              <div class="form-group">
                <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:3px">Category</label>
                <select name="category" class="form-control" style="padding:7px 10px;font-size:12px">
                  <?php foreach (['Travel','Food','Software','Hardware','Office','Marketing','Other'] as $cat): ?>
                  <option value="<?= $cat ?>"><?= $cat ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label style="font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:3px">Date</label>
                <input type="date" name="purchase_date" class="form-control" style="padding:7px 10px;font-size:12px" value="<?= date('Y-m-d') ?>">
              </div>
            </div>
            <div style="display:flex;gap:6px;margin-top:2px">
              <button type="submit" class="btn btn-primary btn-sm" style="flex:1">Log Expense</button>
              <button type="button" class="btn btn-ghost btn-sm" onclick="toggleQA('qa-expense')">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- My Leads Pipeline -->
    <?php if ($my_leads): ?>
    <div class="mw-card" style="margin-bottom:14px">
      <div class="mw-card-head">
        <div class="mw-card-title">🎯 My Leads Pipeline</div>
        <a href="leads.php" style="font-size:11.5px;color:var(--orange);font-weight:600">Pipeline →</a>
      </div>
      <?php
      $stage_colors = [
        'new'=>'#94a3b8','contacted'=>'#6366f1','qualified'=>'#3b82f6',
        'proposal'=>'#f59e0b','negotiation'=>'#f97316','won'=>'#10b981','lost'=>'#ef4444'
      ];
      foreach ($my_leads as $lead):
        $sc = $stage_colors[$lead['stage']] ?? '#94a3b8';
        $ec = $lead['expected_close'] && $lead['expected_close'] < date('Y-m-d') ? 'var(--red)' : 'var(--text3)';
      ?>
      <div style="padding:9px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;cursor:pointer;transition:background .1s"
           onmouseenter="this.style.background='var(--bg3)'" onmouseleave="this.style.background=''"
           onclick="location.href='leads.php?view=<?= $lead['id'] ?>'">
        <span style="font-size:13px"><?= priorityIcon($lead['priority']) ?></span>
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($lead['name']) ?><?= $lead['company']?' <span style="color:var(--text3);font-size:11px">· '.h($lead['company']).'</span>':'' ?></div>
          <?php if ($lead['expected_close']): ?>
          <div style="font-size:11px;color:<?= $ec ?>">Close: <?= date('M j',strtotime($lead['expected_close'])) ?></div>
          <?php endif; ?>
        </div>
        <span style="font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:99px;background:<?= $sc ?>20;color:<?= $sc ?>;flex-shrink:0"><?= ucfirst($lead['stage']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Expense & Invoice Snapshot -->
    <?php if ($my_expense_month || $my_invoices): ?>
    <div class="mw-card" style="margin-bottom:14px">
      <div class="mw-card-head">
        <div class="mw-card-title">💰 Financial Snapshot</div>
      </div>
      <div class="mw-card-body" style="padding:12px 14px">
        <?php if ($my_expense_month && $my_expense_month['total'] > 0): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
          <span style="font-size:12.5px;color:var(--text2)">My spend this month</span>
          <span style="font-size:14px;font-weight:700;color:var(--text)"><?= h($my_expense_month['currency']) ?> <?= number_format($my_expense_month['total'],2) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($my_invoices && $my_invoices['outstanding'] > 0): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
          <span style="font-size:12.5px;color:var(--text2)">Outstanding to collect</span>
          <span style="font-size:14px;font-weight:700;color:var(--red)"><?= h($my_invoices['currency']??'') ?> <?= number_format($my_invoices['outstanding']??0,2) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($my_invoices): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0">
          <span style="font-size:12.5px;color:var(--text2)">Invoices paid / total</span>
          <span style="font-size:13px;font-weight:700;color:var(--green)"><?= (int)($my_invoices['paid_count']??0) ?> / <?= (int)($my_invoices['total']??0) ?></span>
        </div>
        <?php endif; ?>
        <div style="display:flex;gap:8px;margin-top:8px">
          <a href="expenses.php" style="flex:1;text-align:center;padding:7px;background:var(--bg3);border-radius:var(--radius-sm);font-size:12px;color:var(--text2);text-decoration:none;border:1px solid var(--border)">Expenses →</a>
          <a href="invoices.php" style="flex:1;text-align:center;padding:7px;background:var(--bg3);border-radius:var(--radius-sm);font-size:12px;color:var(--text2);text-decoration:none;border:1px solid var(--border)">Invoices →</a>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- My Projects -->
    <div class="mw-card">
      <div class="mw-card-head">
        <div class="mw-card-title">📁 My Active Projects</div>
        <a href="projects.php" style="font-size:11.5px;color:var(--orange);font-weight:600">All projects →</a>
      </div>
      <?php if (!$my_projects): ?>
      <div class="mw-card-empty">No active projects assigned to you.</div>
      <?php else: ?>
      <?php foreach ($my_projects as $p):
        $pct   = (int)$p['progress'];
        $due   = $p['due_date'] ? date('M j', strtotime($p['due_date'])) : null;
        $overdue_proj = $p['due_date'] && $p['due_date'] < date('Y-m-d');
        $my_open = (int)$p['my_open_tasks'];
      ?>
      <div class="mw-proj" onclick="location.href='projects.php?id=<?= $p['id'] ?>'">
        <div class="mw-proj-title"><?= h($p['title']) ?></div>
        <div class="mw-proj-bar"><div class="mw-proj-fill" style="width:<?= $pct ?>%"></div></div>
        <div class="mw-proj-meta">
          <span><?= $pct ?>% complete<?= $my_open ? ' · '.$my_open.' task'.($my_open>1?'s':'').' mine' : '' ?></span>
          <?php if ($due): ?><span style="<?= $overdue_proj?'color:var(--red);font-weight:700':'' ?>">📅 <?= $due ?></span><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div><!-- end mw-grid -->

<script>
// ── QUICK STATUS UPDATE (AJAX, no page reload) ──
function quickStatus(taskId, status) {
    var fd = new FormData();
    fd.append('action', 'quick_status');
    fd.append('id', taskId);
    fd.append('status', status);
    fetch('mywork.php', {method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){return r.json();})
        .then(function(d){
            if (d.ok) {
                if (status === 'done') {
                    var row = document.getElementById('task-row-'+taskId);
                    if (row) {
                        row.style.opacity = '0.4';
                        row.style.transition = 'opacity .3s';
                        setTimeout(function(){ row.remove(); }, 400);
                        toast('Task completed! ✅', 'success');
                    }
                } else {
                    toast('Status updated', 'success');
                }
            }
        })
        .catch(function(){});
}

// ── MARK DONE (circle button) ──
function markDone(taskId, btn) {
    btn.textContent = '✓';
    btn.style.borderColor = 'var(--green)';
    btn.style.background  = 'rgba(16,185,129,.2)';
    setTimeout(function(){ quickStatus(taskId, 'done'); }, 200);
}

// ── INLINE TITLE EDIT (double-click) ──
function inlineEdit(taskId, bodyEl) {
    var titleEl = document.getElementById('ttitle-'+taskId);
    if (!titleEl || titleEl.querySelector('input')) return;
    var orig = titleEl.textContent.trim();
    // Strip priority icon (first 2 chars typically emoji+space)
    var parts = orig.match(/^(.{2})\s(.+)$/);
    var icon  = parts ? parts[1] : '';
    var text  = parts ? parts[2] : orig;

    titleEl.innerHTML = icon + ' <input class="task-edit-input" id="tedit-'+taskId+'" value="'+escHtml(text)+'" style="width:calc(100% - 24px)">';
    var inp = document.getElementById('tedit-'+taskId);
    inp.focus();
    inp.select();

    function save() {
        var val = inp.value.trim();
        if (!val || val === text) { titleEl.textContent = orig; return; }
        var fd = new FormData();
        fd.append('action','quick_title');
        fd.append('id', taskId);
        fd.append('title', val);
        fetch('tasks.php', {method:'POST', body:fd})
            .then(function(){ titleEl.textContent = icon + ' ' + val; toast('Task renamed', 'success'); })
            .catch(function(){ titleEl.textContent = orig; });
    }
    inp.addEventListener('blur',  save);
    inp.addEventListener('keydown', function(e){
        if (e.key === 'Enter')  { e.preventDefault(); inp.blur(); }
        if (e.key === 'Escape') { titleEl.textContent = orig; }
    });
}

// ── QUICK ACTION PANEL TOGGLE ──
function toggleQA(id) {
    var panels = document.querySelectorAll('.qa-form');
    panels.forEach(function(p){
        if (p.id === id) { p.classList.toggle('open'); }
        else { p.classList.remove('open'); }
    });
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php renderLayoutEnd(); ?>