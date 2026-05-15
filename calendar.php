<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();

// FIX BUG 3: Load notify helper safely if present
if (function_exists('notify') === false) {
    $nhelper = __DIR__ . '/notify_helper.php';
    if (file_exists($nhelper)) require_once $nhelper;
}

$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

// ── TYPE CONFIG ──
$TYPES = [
    'meeting'     => ['label'=>'Meeting',     'color'=>'#6366f1','icon'=>'🤝'],
    'appointment' => ['label'=>'Appointment', 'color'=>'#f97316','icon'=>'📅'],
    'deadline'    => ['label'=>'Deadline',    'color'=>'#ef4444','icon'=>'⏰'],
    'milestone'   => ['label'=>'Milestone',   'color'=>'#10b981','icon'=>'🏁'],
    'reminder'    => ['label'=>'Reminder',    'color'=>'#f59e0b','icon'=>'🔔'],
    'other'       => ['label'=>'Other',       'color'=>'#8b5cf6','icon'=>'📌'],
];

// ── POST HANDLERS ──
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_event') {
        if (!isManager()) {
            flash('Only managers can create or edit events.', 'error');
            ob_end_clean(); header('Location: calendar.php'); exit;
        }

        $eid   = (int)($_POST['eid'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $type  = $_POST['event_type'] ?? 'other';
        $start = trim($_POST['start_datetime'] ?? '');   // never null for 's' bind_param
        $end   = trim($_POST['end_datetime'] ?? '');       // '' not null — PHP 8.1 safe for 's'
        $allday= isset($_POST['all_day']) ? 1 : 0;
        $loc   = trim($_POST['location'] ?? '');
        $color = $TYPES[$type]['color'] ?? '#f97316';
        // FIX BUG 2 & 7: cast to int (0 not null) so bind_param 'i' never gets null
        $proj  = (int)($_POST['project_id'] ?? 0);
        $task  = (int)($_POST['task_id']    ?? 0);
        $cont  = (int)($_POST['contact_id'] ?? 0);
        $recur = $_POST['recur']   ?? 'none';
        $stat  = $_POST['status']  ?? 'scheduled';
        $atts  = $_POST['attendees'] ?? [];

        if (!$title || !$start) {
            flash('Title and start date/time are required.', 'error');
            ob_end_clean(); header('Location: calendar.php'); exit;
        }

        $was_existing = (bool)$eid;

        if ($eid) {
            // FIX BUG 1: correct format string = 14 chars for 14 params
            // s(title) s(desc) s(type) s(start) s(end) i(allday) s(loc) s(color)
            // i(proj)  i(task) i(cont) s(recur) s(stat) i(eid)
            $s = $db->prepare(
                "UPDATE calendar_events
                 SET title=?,description=?,event_type=?,start_datetime=?,end_datetime=?,
                     all_day=?,location=?,color=?,project_id=?,task_id=?,contact_id=?,
                     recur=?,status=?
                 WHERE id=?"
            );
            if (!$s) { flash('DB prepare error: ' . $db->error, 'error'); ob_end_clean(); header('Location: calendar.php'); exit; }
            $s->bind_param(
                "sssssissiiissi",   // 14 chars, 14 vars ✓
                $title, $desc, $type, $start, $end,
                $allday, $loc, $color,
                $proj, $task, $cont,
                $recur, $stat,
                $eid
            );
            $s->execute();
            $db->query("DELETE FROM calendar_attendees WHERE event_id=$eid");
            logActivity('updated event', $title, $eid);
        } else {
            // FIX BUG 1: INSERT also had the same 15-vs-14 mismatch
            // s(title) s(desc) s(type) s(start) s(end) i(allday) s(loc) s(color)
            // i(proj)  i(task) i(cont) s(recur) s(stat) i(uid)
            $s = $db->prepare(
                "INSERT INTO calendar_events
                    (title,description,event_type,start_datetime,end_datetime,all_day,
                     location,color,project_id,task_id,contact_id,recur,status,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            if (!$s) { flash('DB prepare error: ' . $db->error, 'error'); ob_end_clean(); header('Location: calendar.php'); exit; }
            $s->bind_param(
                "sssssissiiissi",   // 14 chars, 14 vars ✓
                $title, $desc, $type, $start, $end,
                $allday, $loc, $color,
                $proj, $task, $cont,
                $recur, $stat,
                $uid
            );
            $s->execute();
            $eid = (int)$db->insert_id;
            logActivity('created event', $title, $eid);
        }

        // Save attendees — always include creator
        $atts[] = $uid;
        $atts   = array_unique(array_map('intval', $atts));
        $sa = $db->prepare("INSERT IGNORE INTO calendar_attendees (event_id,user_id) VALUES (?,?)");
        if (!$sa) { flash('Attendee error: ' . $db->error, 'error'); ob_end_clean(); header('Location: calendar.php'); exit; }
        foreach ($atts as $aid) {
            $sa->bind_param("ii", $eid, $aid);
            $sa->execute();
        }

        // FIX BUG 8: notify AFTER attendees are saved
        if (function_exists('notify') && function_exists('notifyMany')) {
            if ($was_existing) {
                // Notify existing attendees of the update
                $notify_ids = array_filter($atts, fn($id) => $id !== $uid);
                if (!empty($notify_ids)) {
                    notifyMany(
                        $db, array_values($notify_ids),
                        'calendar_updated', 'meeting', $eid,
                        'Event Updated: ' . mb_substr($title, 0, 60),
                        'Updated by ' . $user['name'],
                        'calendar.php?view=event&eid=' . $eid,
                        $uid
                    );
                }
            } else {
                // Notify new invitees
                foreach ($atts as $aid) {
                    if ($aid !== $uid) {
                        notify(
                            $db, $aid,
                            'calendar_invited', 'meeting', $eid,
                            'Event: ' . mb_substr($title, 0, 60),
                            'Invited by ' . $user['name'],
                            'calendar.php?view=event&eid=' . $eid,
                            $uid
                        );
                    }
                }
            }
        }

        flash('Event saved.', 'success');
        ob_end_clean();
        header('Location: calendar.php?y=' . date('Y', strtotime($start)) . '&m=' . date('m', strtotime($start)));
        exit;
    }

    if ($action === 'delete_event') {
        $eid = (int)$_POST['eid'];
        $ev  = $db->query("SELECT created_by,title FROM calendar_events WHERE id=$eid")->fetch_assoc();
        if ($ev && ($ev['created_by'] == $uid || isManager())) {
            $db->query("DELETE FROM calendar_events WHERE id=$eid");
            $db->query("DELETE FROM calendar_attendees WHERE event_id=$eid");
            logActivity('deleted event', $ev['title'], $eid);
            flash('Event deleted.', 'success');
        }
        ob_end_clean(); header('Location: calendar.php'); exit;
    }

    if ($action === 'rsvp') {
        $eid  = (int)$_POST['eid'];
        $rsvp = in_array($_POST['rsvp'] ?? '', ['accepted','declined','pending'])
                ? $_POST['rsvp'] : 'pending';
        $db->query("UPDATE calendar_attendees SET rsvp='$rsvp' WHERE event_id=$eid AND user_id=$uid");
        flash('Response saved.', 'success');
        ob_end_clean(); header('Location: calendar.php?view=event&eid=' . $eid); exit;
    }
}
ob_end_clean();

// ── VIEW PARAMS ──
$view    = $_GET['view']   ?? 'month';
$year    = (int)($_GET['y'] ?? date('Y'));
$month   = (int)($_GET['m'] ?? date('m'));
$week    = (int)($_GET['w'] ?? date('W'));
$edit_id = (int)($_GET['edit'] ?? 0);
$view_eid= (int)($_GET['eid']  ?? 0);
$type_f  = $_GET['type'] ?? '';

$month = max(1,  min(12,   $month));
$year  = max(2020, min(2099, $year));

// ── REFERENCE DATA ──
$all_users = $db->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$projects  = $db->query("SELECT id,title FROM projects WHERE status NOT IN ('cancelled') ORDER BY title")->fetch_all(MYSQLI_ASSOC);
$contacts  = $db->query("SELECT id,name FROM contacts ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$all_tasks = $db->query("SELECT id,title,due_date FROM tasks WHERE status!='done' ORDER BY title")->fetch_all(MYSQLI_ASSOC);

// ── DATE RANGES ──
$range_start = sprintf('%04d-%02d-01', $year, $month);
$range_end   = date('Y-m-t', strtotime($range_start));
$week_start  = date('Y-m-d', strtotime($year . 'W' . sprintf('%02d', $week) . '1'));
$week_end    = date('Y-m-d', strtotime($year . 'W' . sprintf('%02d', $week) . '7'));

$type_where = $type_f
    ? " AND ce.event_type='" . $db->real_escape_string($type_f) . "'"
    : '';

// ── EVENT LOADER ──
function loadEvents(mysqli $db, string $from, string $to, int $uid,
                    string $type_where = '', bool $is_manager = false): array {
    $scope = $is_manager
        ? "1=1"
        : "(ce.created_by=$uid OR EXISTS(SELECT 1 FROM calendar_attendees WHERE event_id=ce.id AND user_id=$uid))";
    return $db->query("
        SELECT ce.*, u.name AS creator_name,
               p.title AS proj_title, t.title AS task_title, c.name AS contact_name,
               (SELECT GROUP_CONCAT(us.name ORDER BY us.name SEPARATOR ', ')
                FROM calendar_attendees ca JOIN users us ON us.id=ca.user_id
                WHERE ca.event_id=ce.id) AS attendee_names,
               (SELECT rsvp FROM calendar_attendees WHERE event_id=ce.id AND user_id=$uid LIMIT 1) AS my_rsvp
        FROM calendar_events ce
        LEFT JOIN users    u ON u.id=ce.created_by
        LEFT JOIN projects p ON p.id=ce.project_id
        LEFT JOIN tasks    t ON t.id=ce.task_id
        LEFT JOIN contacts c ON c.id=ce.contact_id
        WHERE DATE(ce.start_datetime) BETWEEN '$from' AND '$to'
          AND ($scope)
          $type_where
        ORDER BY ce.start_datetime ASC
    ")->fetch_all(MYSQLI_ASSOC);
}

// ── TASK DEADLINES (pseudo-events) ──
function taskDeadlines(mysqli $db, string $from, string $to, int $uid): array {
    $rows = $db->query("
        SELECT t.id, t.title, t.due_date, t.priority, p.title AS proj_title
        FROM tasks t LEFT JOIN projects p ON p.id=t.project_id
        WHERE t.due_date BETWEEN '$from' AND '$to'
          AND t.status != 'done'
          AND (t.assigned_to=$uid OR t.created_by=$uid)
        ORDER BY t.due_date ASC
    ")->fetch_all(MYSQLI_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'             => 't' . $r['id'],
            'title'          => '⏰ ' . $r['title'],
            'event_type'     => 'deadline',
            'color'          => '#ef4444',
            'start_datetime' => $r['due_date'] . ' 00:00:00',
            'all_day'        => 1,
            'is_task'        => true,
            'task_id'        => $r['id'],
            'proj_title'     => $r['proj_title'],
        ];
    }
    return $out;
}

// ── PROJECT MILESTONES (pseudo-events) ──
function projMilestones(mysqli $db, string $from, string $to,
                        int $uid = 0, bool $is_manager = false): array {
    $scope = $is_manager
        ? "1=1"
        : "(created_by=$uid OR EXISTS(SELECT 1 FROM project_members WHERE project_id=projects.id AND user_id=$uid))";
    $rows = $db->query("
        SELECT id,title,due_date FROM projects
        WHERE due_date BETWEEN '$from' AND '$to'
          AND status NOT IN ('cancelled','completed')
          AND ($scope)
        ORDER BY due_date
    ")->fetch_all(MYSQLI_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'             => 'p' . $r['id'],
            'title'          => '🏁 ' . $r['title'],
            'event_type'     => 'milestone',
            'color'          => '#10b981',
            'start_datetime' => $r['due_date'] . ' 00:00:00',
            'all_day'        => 1,
            'is_project'     => true,
            'project_id'     => $r['id'],
        ];
    }
    return $out;
}

$is_mgr          = isManager();
$month_events    = loadEvents($db, $range_start, $range_end, $uid, $type_where, $is_mgr);
$week_events     = loadEvents($db, $week_start,  $week_end,  $uid, $type_where, $is_mgr);
$task_deadlines  = taskDeadlines($db, $range_start, $range_end, $uid);
$proj_milestones = projMilestones($db, $range_start, $range_end, $uid, $is_mgr);

$all_month = array_merge($month_events, $task_deadlines, $proj_milestones);
usort($all_month, fn($a, $b) => strcmp($a['start_datetime'], $b['start_datetime']));

$events_by_date = [];
foreach ($all_month as $e) {
    $d = substr($e['start_datetime'], 0, 10);
    $events_by_date[$d][] = $e;
}

// ── SINGLE EVENT VIEW ──
$single_event    = null;
$event_attendees = [];
if ($view === 'event' && $view_eid) {
    $single_event = $db->query("
        SELECT ce.*, u.name AS creator_name,
               p.title AS proj_title, t.title AS task_title, c.name AS contact_name
        FROM calendar_events ce
        LEFT JOIN users    u ON u.id=ce.created_by
        LEFT JOIN projects p ON p.id=ce.project_id
        LEFT JOIN tasks    t ON t.id=ce.task_id
        LEFT JOIN contacts c ON c.id=ce.contact_id
        WHERE ce.id=$view_eid
    ")->fetch_assoc();
    if ($single_event) {
        $event_attendees = $db->query("
            SELECT u.id,u.name,u.role,ca.rsvp
            FROM calendar_attendees ca JOIN users u ON u.id=ca.user_id
            WHERE ca.event_id=$view_eid ORDER BY u.name
        ")->fetch_all(MYSQLI_ASSOC);
    }
}

// ── EDIT MODE ──
$edit_event        = null;
$edit_attendee_ids = [];
if ($edit_id) {
    $edit_event        = $db->query("SELECT * FROM calendar_events WHERE id=$edit_id")->fetch_assoc();
    $ea                = $db->query("SELECT user_id FROM calendar_attendees WHERE event_id=$edit_id")->fetch_all(MYSQLI_ASSOC);
    $edit_attendee_ids = array_column($ea, 'user_id');
}

// ── LIST VIEW (next 60 days) ──
$list_events = loadEvents($db, date('Y-m-d'), date('Y-m-d', strtotime('+60 days')), $uid, $type_where, $is_mgr);
$list_tasks  = taskDeadlines($db, date('Y-m-d'), date('Y-m-d', strtotime('+60 days')), $uid);
$list_projs  = projMilestones($db, date('Y-m-d'), date('Y-m-d', strtotime('+60 days')), $uid, $is_mgr);
$list_all    = array_merge($list_events, $list_tasks, $list_projs);
usort($list_all, fn($a, $b) => strcmp($a['start_datetime'], $b['start_datetime']));

// ── NAV HELPERS ──
$prev_m = $month === 1  ? ['y' => $year-1, 'm' => 12] : ['y' => $year, 'm' => $month-1];
$next_m = $month === 12 ? ['y' => $year+1, 'm' => 1]  : ['y' => $year, 'm' => $month+1];

renderLayout('Calendar', 'calendar');
?>

<style>
/* ── CALENDAR PAGE ── */
.cal-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px;flex-wrap:wrap}
.cal-nav{display:flex;align-items:center;gap:8px}
.cal-title{font-family:var(--font-display);font-size:18px;font-weight:700;min-width:160px;text-align:center}
.view-tabs{display:flex;border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden}
.view-tabs a{padding:7px 14px;font-size:12.5px;font-weight:600;color:var(--text2);background:var(--bg3);text-decoration:none;transition:background .15s,color .15s;white-space:nowrap}
.view-tabs a.active{background:var(--orange);color:#fff}

/* MONTH GRID */
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);border-left:1px solid var(--border);border-top:1px solid var(--border)}
.cal-dow{background:var(--bg3);padding:8px 6px;text-align:center;font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;border-right:1px solid var(--border);border-bottom:1px solid var(--border)}
.cal-cell{min-height:100px;padding:5px;border-right:1px solid var(--border);border-bottom:1px solid var(--border);background:var(--bg2);transition:background .15s;vertical-align:top;cursor:pointer}
.cal-cell:hover{background:var(--bg3)}
.cal-cell.today{background:var(--orange-bg)}
.cal-cell.other-month{background:var(--bg);opacity:.55}
.cal-day-num{font-size:12px;font-weight:600;color:var(--text3);margin-bottom:3px;display:flex;align-items:center;justify-content:space-between}
.cal-day-num.today-num{color:var(--orange);font-weight:800}
.cal-event-pill{font-size:10.5px;font-weight:600;padding:2px 6px;border-radius:4px;margin-bottom:2px;cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;line-height:1.4;transition:opacity .1s;text-decoration:none}
.cal-event-pill:hover{opacity:.82}
.cal-more{font-size:10px;color:var(--text3);padding:1px 4px;cursor:pointer}
.cal-more:hover{color:var(--orange)}

/* WEEK VIEW */
.week-grid{display:grid;grid-template-columns:50px repeat(7,1fr);border-left:1px solid var(--border);border-top:1px solid var(--border)}
.week-dow{background:var(--bg3);padding:8px 4px;text-align:center;font-size:11px;border-right:1px solid var(--border);border-bottom:1px solid var(--border)}
.week-dow.today-col{background:var(--orange-bg)}
.week-hour{font-size:10px;color:var(--text3);text-align:right;padding:2px 4px;border-right:1px solid var(--border);border-bottom:1px solid var(--border);height:36px;vertical-align:top}
.week-slot{border-right:1px solid var(--border);border-bottom:1px solid var(--border);height:36px;position:relative}
.week-slot.today-col{background:rgba(249,115,22,.03)}

/* LIST VIEW */
.list-day-header{font-size:13px;font-weight:700;color:var(--text3);padding:14px 0 6px;border-bottom:1px solid var(--border);margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em}
.list-day-header.today-hdr{color:var(--orange)}
.list-event-row{display:flex;align-items:flex-start;gap:12px;padding:10px 14px;background:var(--bg2);border:1px solid var(--border);border-left:3px solid var(--ec,#f97316);border-radius:var(--radius);margin-bottom:7px;cursor:pointer;transition:border-color .15s,box-shadow .15s}
.list-event-row:hover{box-shadow:0 2px 8px rgba(0,0,0,.18);border-color:var(--border2)}
.list-event-time{font-size:11.5px;font-weight:600;color:var(--text3);min-width:52px;flex-shrink:0;padding-top:2px}
.list-event-body{flex:1;min-width:0}
.list-event-title{font-size:13.5px;font-weight:700;color:var(--text);margin-bottom:2px}
.list-event-meta{font-size:11.5px;color:var(--text3)}

/* SINGLE EVENT */
.ev-detail-grid{display:grid;grid-template-columns:1fr 260px;gap:18px;align-items:start}
.ev-meta-box{background:var(--bg3);border-radius:8px;padding:10px}
.ev-meta-lbl{font-size:10px;color:var(--text3);text-transform:uppercase;margin-bottom:3px;letter-spacing:.04em}
.ev-meta-val{font-size:13px;color:var(--text);font-weight:500}
.rsvp-btn{padding:7px 16px;border-radius:var(--radius-sm);font-size:12.5px;font-weight:600;border:1px solid var(--border);cursor:pointer;background:var(--bg3);color:var(--text2);transition:all .15s}
.rsvp-btn.accepted{background:rgba(16,185,129,.15);border-color:#10b981;color:#10b981}
.rsvp-btn.declined{background:rgba(239,68,68,.15);border-color:#ef4444;color:#ef4444}

/* TYPE FILTER */
.type-filter{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
.type-pill{padding:4px 12px;border-radius:99px;font-size:11.5px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:var(--bg3);color:var(--text2);text-decoration:none;transition:all .15s}
.type-pill:hover,.type-pill.active{border-color:var(--orange);color:var(--orange);background:var(--orange-bg)}

@media(max-width:900px){
  .ev-detail-grid{grid-template-columns:1fr}
  .cal-cell{min-height:60px}
  .cal-event-pill{display:none}
  .cal-cell.has-events .cal-day-num::after{content:'●';color:var(--orange);font-size:8px;margin-left:2px}
  .week-grid{grid-template-columns:30px repeat(7,1fr)}
}
@media(max-width:600px){
  .cal-grid{grid-template-columns:repeat(7,1fr)}
  .cal-dow{font-size:9px;padding:5px 2px}
  .cal-day-num{font-size:10px}
}
</style>

<?php if ($single_event && $view === 'event'): // ══ SINGLE EVENT VIEW ══ ?>

<div style="margin-bottom:14px">
  <a href="calendar.php?y=<?= date('Y',strtotime($single_event['start_datetime'])) ?>&m=<?= date('m',strtotime($single_event['start_datetime'])) ?>" style="color:var(--text3);font-size:13px">← Back to Calendar</a>
</div>

<div class="ev-detail-grid">
  <div>
    <div class="card" style="margin-bottom:16px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:16px;flex-wrap:wrap">
        <div>
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
            <div style="width:14px;height:14px;border-radius:3px;background:<?= h($single_event['color']) ?>;flex-shrink:0"></div>
            <h2 style="font-family:var(--font-display);font-size:20px;font-weight:700"><?= h($single_event['title']) ?></h2>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php $t=$TYPES[$single_event['event_type']]??$TYPES['other']; ?>
            <span class="badge" style="background:<?= $t['color'] ?>20;color:<?= $t['color'] ?>"><?= $t['icon'] ?> <?= $t['label'] ?></span>
            <?php $sc=['scheduled'=>'#10b981','completed'=>'#6366f1','cancelled'=>'#ef4444'][$single_event['status']]??'#94a3b8'; ?>
            <span class="badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><?= ucfirst($single_event['status']) ?></span>
            <?php if (($single_event['recur']??'none') !== 'none'): ?>
            <span class="badge" style="background:var(--bg3);color:var(--text2)">🔁 <?= ucfirst($single_event['recur']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div style="display:flex;gap:8px;flex-shrink:0">
          <?php if (isManager()): ?>
          <a href="calendar.php?edit=<?= $view_eid ?>" class="btn btn-ghost btn-sm">✎ Edit</a>
          <form method="POST" onsubmit="return confirm('Delete this event?')" style="display:inline">
            <input type="hidden" name="action" value="delete_event">
            <input type="hidden" name="eid" value="<?= $view_eid ?>">
            <button class="btn btn-danger btn-sm">🗑</button>
          </form>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($single_event['description']): ?>
      <p style="color:var(--text2);font-size:13.5px;line-height:1.7;margin-bottom:16px;padding:12px;background:var(--bg3);border-radius:8px"><?= nl2br(h($single_event['description'])) ?></p>
      <?php endif; ?>

      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px">
        <div class="ev-meta-box">
          <div class="ev-meta-lbl">Start</div>
          <div class="ev-meta-val"><?= date($single_event['all_day']?'M j, Y':'M j, Y g:ia', strtotime($single_event['start_datetime'])) ?></div>
        </div>
        <div class="ev-meta-box">
          <div class="ev-meta-lbl">End</div>
          <div class="ev-meta-val"><?= $single_event['end_datetime'] ? date($single_event['all_day']?'M j, Y':'M j, Y g:ia',strtotime($single_event['end_datetime'])) : '—' ?></div>
        </div>
        <div class="ev-meta-box">
          <div class="ev-meta-lbl">Location</div>
          <div class="ev-meta-val"><?= $single_event['location'] ? h($single_event['location']) : '—' ?></div>
        </div>
        <?php if ($single_event['proj_title']): ?>
        <div class="ev-meta-box">
          <div class="ev-meta-lbl">Project</div>
          <div class="ev-meta-val"><a href="projects.php?view=<?= $single_event['project_id'] ?>" style="color:var(--orange)"><?= h($single_event['proj_title']) ?></a></div>
        </div>
        <?php endif; ?>
        <?php if ($single_event['task_title']): ?>
        <div class="ev-meta-box">
          <div class="ev-meta-lbl">Task</div>
          <div class="ev-meta-val"><a href="tasks.php?edit=<?= $single_event['task_id'] ?>" style="color:var(--orange)"><?= h($single_event['task_title']) ?></a></div>
        </div>
        <?php endif; ?>
        <?php if ($single_event['contact_name']): ?>
        <div class="ev-meta-box">
          <div class="ev-meta-lbl">Contact</div>
          <div class="ev-meta-val"><?= h($single_event['contact_name']) ?></div>
        </div>
        <?php endif; ?>
      </div>

      <?php
        $my_rsvp_row = array_filter($event_attendees, fn($a) => $a['id'] == $uid);
        $my_rsvp     = $my_rsvp_row ? array_values($my_rsvp_row)[0]['rsvp'] : null;
      ?>
      <?php if ($my_rsvp !== null): ?>
      <div style="border-top:1px solid var(--border);padding-top:14px">
        <div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em">Your Response</div>
        <form method="POST" style="display:flex;gap:8px">
          <input type="hidden" name="action" value="rsvp">
          <input type="hidden" name="eid" value="<?= $view_eid ?>">
          <button name="rsvp" value="accepted" class="rsvp-btn <?= $my_rsvp==='accepted'?'accepted':'' ?>">✓ Accept</button>
          <button name="rsvp" value="declined" class="rsvp-btn <?= $my_rsvp==='declined'?'declined':'' ?>">✕ Decline</button>
          <button name="rsvp" value="pending"  class="rsvp-btn <?= $my_rsvp==='pending'?'active':'' ?>">? Maybe</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-title" style="margin-bottom:14px">Attendees (<?= count($event_attendees) ?>)</div>
    <?php if (empty($event_attendees)): ?>
    <p style="font-size:13px;color:var(--text3)">No attendees.</p>
    <?php else: ?>
    <?php foreach ($event_attendees as $a):
      $rc = ['accepted'=>'#10b981','declined'=>'#ef4444','pending'=>'#f59e0b'][$a['rsvp']] ?? '#94a3b8';
    ?>
    <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
      <div class="avatar" style="width:30px;height:30px;font-size:11px;background:var(--bg4);color:var(--text)"><?= strtoupper(substr($a['name'],0,1)) ?></div>
      <div style="flex:1">
        <div style="font-size:13px;font-weight:600;color:var(--text)"><?= h($a['name']) ?></div>
        <div style="font-size:11px;color:var(--text3)"><?= ucfirst($a['role']) ?></div>
      </div>
      <span class="badge" style="background:<?= $rc ?>20;color:<?= $rc ?>"><?= ucfirst($a['rsvp']) ?></span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
      <div style="font-size:11px;color:var(--text3)">Created by <?= h($single_event['creator_name']) ?></div>
    </div>
  </div>
</div>

<?php else: // ══ CALENDAR VIEWS ══ ?>

<!-- TOOLBAR -->
<div class="cal-toolbar">
  <div class="cal-nav">
    <?php if ($view==='month'): ?>
    <a href="calendar.php?view=month&y=<?= $prev_m['y'] ?>&m=<?= $prev_m['m'] ?>" class="btn btn-ghost btn-sm">‹</a>
    <span class="cal-title"><?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></span>
    <a href="calendar.php?view=month&y=<?= $next_m['y'] ?>&m=<?= $next_m['m'] ?>" class="btn btn-ghost btn-sm">›</a>
    <?php elseif ($view==='week'): ?>
    <a href="calendar.php?view=week&y=<?= $year ?>&w=<?= max(1,$week-1) ?>" class="btn btn-ghost btn-sm">‹</a>
    <span class="cal-title">Week <?= $week ?>, <?= $year ?></span>
    <a href="calendar.php?view=week&y=<?= $year ?>&w=<?= min(52,$week+1) ?>" class="btn btn-ghost btn-sm">›</a>
    <?php else: ?>
    <span class="cal-title">Upcoming Events</span>
    <?php endif; ?>
    <a href="calendar.php?view=<?= $view ?>&y=<?= date('Y') ?>&m=<?= date('m') ?>&w=<?= date('W') ?>" class="btn btn-ghost btn-sm" style="font-size:11.5px">Today</a>
  </div>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <div class="view-tabs">
      <a href="calendar.php?view=month&y=<?= $year ?>&m=<?= $month ?>" class="<?= $view==='month'?'active':'' ?>">Month</a>
      <a href="calendar.php?view=week&y=<?= $year ?>&w=<?= $week ?>"   class="<?= $view==='week'?'active':'' ?>">Week</a>
      <a href="calendar.php?view=list"                                  class="<?= $view==='list'?'active':'' ?>">List</a>
    </div>
    <?php if (isManager()): ?>
    <button class="btn btn-primary" onclick="openModal('modal-event')">＋ Event</button>
    <?php endif; ?>
  </div>
</div>

<!-- TYPE FILTERS -->
<div class="type-filter">
  <a href="calendar.php?view=<?= $view ?>&y=<?= $year ?>&m=<?= $month ?>" class="type-pill <?= !$type_f?'active':'' ?>">All</a>
  <?php foreach ($TYPES as $tk=>$tv): ?>
  <a href="calendar.php?view=<?= $view ?>&y=<?= $year ?>&m=<?= $month ?>&type=<?= $tk ?>"
     class="type-pill <?= $type_f===$tk?'active':'' ?>"
     style="<?= $type_f===$tk?"border-color:{$tv['color']};color:{$tv['color']};background:{$tv['color']}18":'' ?>">
    <?= $tv['icon'] ?> <?= $tv['label'] ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($view === 'month'): // ══ MONTH VIEW ══ ?>

<div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden">
  <div class="cal-grid">
    <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow_label): ?>
    <div class="cal-dow"><?= $dow_label ?></div>
    <?php endforeach; ?>

    <?php
    $first_day   = mktime(0,0,0,$month,1,$year);
    $days_in     = (int)date('t', $first_day);
    $dow_first   = (int)date('N', $first_day);   // 1=Mon..7=Sun
    $today_str   = date('Y-m-d');
    $total_cells = $dow_first - 1 + $days_in;
    $total_cells = $total_cells + (7 - ($total_cells % 7 ?: 7));

    // FIX BUG 6: don't use cal_days_in_month() — use date() instead
    $prev_days_count = (int)date('t', mktime(0,0,0,$prev_m['m'],1,$prev_m['y']));

    for ($cell = 0; $cell < $total_cells; $cell++):
        $day_offset = $cell - ($dow_first - 1) + 1;

        if ($day_offset < 1) {
            $actual_day  = $prev_days_count + $day_offset;
            $actual_date = sprintf('%04d-%02d-%02d', $prev_m['y'], $prev_m['m'], $actual_day);
            $other       = true;
        } elseif ($day_offset > $days_in) {
            $actual_day  = $day_offset - $days_in;
            $actual_date = sprintf('%04d-%02d-%02d', $next_m['y'], $next_m['m'], $actual_day);
            $other       = true;
        } else {
            $actual_day  = $day_offset;
            $actual_date = sprintf('%04d-%02d-%02d', $year, $month, $actual_day);
            $other       = false;
        }

        $is_today   = ($actual_date === $today_str);
        $day_events = $events_by_date[$actual_date] ?? [];
        $has_events = !empty($day_events);
        $classes    = 'cal-cell'
            . ($is_today   ? ' today'       : '')
            . ($other      ? ' other-month' : '')
            . ($has_events ? ' has-events'  : '');
    ?>
    <div class="<?= $classes ?>" onclick="dayClick('<?= $actual_date ?>')">
      <div class="cal-day-num <?= $is_today?'today-num':'' ?>">
        <span><?= $actual_day ?></span>
        <?php if ($is_today): ?><span style="font-size:9px;background:var(--orange);color:#fff;padding:1px 5px;border-radius:99px">Today</span><?php endif; ?>
      </div>
      <?php foreach (array_slice($day_events, 0, 3) as $e):
        $ec = $e['color'] ?? '#f97316';
        // FIX BUG 4: correct href for task/project pseudo-events
        if (!empty($e['is_task'])) {
            $pill_href = 'tasks.php?edit=' . $e['task_id'];
        } elseif (!empty($e['is_project'])) {
            $pill_href = 'projects.php?view=' . $e['project_id'];
        } else {
            $pill_href = 'calendar.php?view=event&eid=' . $e['id'];
        }
      ?>
      <a href="<?= $pill_href ?>"
         class="cal-event-pill"
         style="background:<?= $ec ?>22;color:<?= $ec ?>;border-left:2px solid <?= $ec ?>"
         onclick="event.stopPropagation()"
         title="<?= h($e['title']) ?>">
        <?= h(mb_substr($e['title'],0,22)) . (mb_strlen($e['title'])>22?'…':'') ?>
      </a>
      <?php endforeach; ?>
      <?php if (count($day_events) > 3): ?>
      <span class="cal-more">+<?= count($day_events)-3 ?> more</span>
      <?php endif; ?>
    </div>
    <?php endfor; ?>
  </div>
</div>

<?php elseif ($view === 'week'): // ══ WEEK VIEW ══ ?>

<?php
$week_events_by_day = [];
foreach ($week_events as $e) {
    $d = substr($e['start_datetime'],0,10);
    $week_events_by_day[$d][] = $e;
}
foreach (taskDeadlines($db, $week_start, $week_end, $uid) as $e) {
    $d = substr($e['start_datetime'],0,10);
    $week_events_by_day[$d][] = $e;
}
$hours = range(8, 20);
?>
<div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;overflow-x:auto">
  <div class="week-grid" style="min-width:600px">
    <div style="border-right:1px solid var(--border);border-bottom:1px solid var(--border);background:var(--bg3)"></div>
    <?php for ($d=0; $d<7; $d++):
      $day_date = date('Y-m-d', strtotime($week_start . " +$d days"));
      $is_today = ($day_date === date('Y-m-d'));
    ?>
    <div class="week-dow <?= $is_today?'today-col':'' ?>">
      <div style="font-size:10px;color:var(--text3);font-weight:700"><?= date('D',strtotime($day_date)) ?></div>
      <div style="font-size:14px;font-weight:800;color:<?= $is_today?'var(--orange)':'var(--text)' ?>"><?= date('j',strtotime($day_date)) ?></div>
      <?php foreach ($week_events_by_day[$day_date]??[] as $e):
        if (!$e['all_day']) continue; $ec=$e['color']??'#f97316';
      ?>
      <a href="<?= !empty($e['is_task'])?'tasks.php?edit='.$e['task_id']:(!empty($e['is_project'])?'projects.php?view='.$e['project_id']:'calendar.php?view=event&eid='.$e['id']) ?>" style="font-size:9px;display:block;background:<?= $ec ?>22;color:<?= $ec ?>;border-left:2px solid <?= $ec ?>;padding:1px 4px;border-radius:2px;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-decoration:none"><?= h(mb_substr($e['title'],0,14)) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endfor; ?>

    <?php foreach ($hours as $hr):
      $h_label = $hr . ':00';
    ?>
    <div class="week-hour"><?= $h_label ?></div>
    <?php for ($d=0; $d<7; $d++):
      $day_date   = date('Y-m-d', strtotime($week_start . " +$d days"));
      $is_today   = ($day_date === date('Y-m-d'));
      $slot_events = array_filter(
          $week_events_by_day[$day_date] ?? [],
          function($e) use ($hr) {
              return !$e['all_day'] && (int)date('G', strtotime($e['start_datetime'])) === $hr;
          }
      );
    ?>
    <div class="week-slot <?= $is_today?'today-col':'' ?>">
      <?php foreach ($slot_events as $e): $ec=$e['color']??'#f97316'; ?>
      <a href="calendar.php?view=event&eid=<?= $e['id'] ?>" style="display:block;font-size:9.5px;font-weight:600;background:<?= $ec ?>22;color:<?= $ec ?>;border-left:2px solid <?= $ec ?>;padding:1px 4px;border-radius:2px;text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:1px"><?= h(mb_substr($e['title'],0,16)) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endfor; ?>
    <?php endforeach; ?>
  </div>
</div>

<?php else: // ══ LIST VIEW ══ ?>

<?php if (empty($list_all)): ?>
<div class="card"><div class="empty-state"><div class="icon">📅</div><p>No upcoming events in the next 60 days.</p></div></div>
<?php else:
  $grouped = [];
  foreach ($list_all as $e) {
      $d = substr($e['start_datetime'],0,10);
      $grouped[$d][] = $e;
  }
  foreach ($grouped as $date => $evs):
    $is_today    = ($date === date('Y-m-d'));
    $is_tomorrow = ($date === date('Y-m-d', strtotime('+1 day')));
    $label = $is_today
        ? 'Today — '    . date('l, M j', strtotime($date))
        : ($is_tomorrow
            ? 'Tomorrow — ' . date('l, M j', strtotime($date))
            : date('l, M j, Y', strtotime($date)));
?>
<div class="list-day-header <?= $is_today?'today-hdr':'' ?>"><?= $label ?></div>
<?php foreach ($evs as $e):
  $ec       = $e['color'] ?? '#f97316';
  $is_sys   = !empty($e['is_task']) || !empty($e['is_project']);
  // FIX BUG 4: correct href for system pseudo-events in list view too
  if (!empty($e['is_task'])) {
      $href = 'tasks.php?edit=' . $e['task_id'];
  } elseif (!empty($e['is_project'])) {
      $href = 'projects.php?view=' . $e['project_id'];
  } else {
      $href = 'calendar.php?view=event&eid=' . $e['id'];
  }
  $tstr     = ($e['all_day'] ?? 0) ? 'All day' : date('g:ia', strtotime($e['start_datetime']));
  $type_cfg = $TYPES[$e['event_type']] ?? $TYPES['other'];
?>
<div class="list-event-row" style="--ec:<?= $ec ?>" onclick="location.href='<?= $href ?>'">
  <div class="list-event-time"><?= $tstr ?></div>
  <div class="list-event-body">
    <div class="list-event-title"><?= h($e['title']) ?></div>
    <div class="list-event-meta">
      <span><?= $type_cfg['icon'] ?> <?= $type_cfg['label'] ?></span>
      <?php if (!empty($e['proj_title'])): ?> · 📁 <?= h($e['proj_title']) ?><?php endif; ?>
      <?php if (!empty($e['location'])): ?>   · 📍 <?= h($e['location'])   ?><?php endif; ?>
      <?php if (!empty($e['attendee_names'])): ?> · 👥 <?= h($e['attendee_names']) ?><?php endif; ?>
    </div>
  </div>
  <div style="width:10px;height:10px;border-radius:50%;background:<?= $ec ?>;flex-shrink:0;margin-top:4px"></div>
</div>
<?php endforeach; ?>
<?php endforeach; endif; ?>
<?php endif; // end views ?>
<?php endif; // end single/list ?>

<!-- ══ CREATE / EDIT EVENT MODAL ══ -->
<div class="modal-overlay <?= $edit_id?'open':'' ?>" id="modal-event">
  <div class="modal" style="max-width:620px">
    <div class="modal-header">
      <div class="modal-title"><?= $edit_id ? 'Edit Event' : '＋ New Event' ?></div>
      <button class="modal-close" onclick="closeModal('modal-event');<?= $edit_id?"location.href='calendar.php'":'' ?>">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save_event">
      <input type="hidden" name="eid"    value="<?= $edit_id ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" required
            value="<?= h($edit_event['title'] ?? '') ?>" placeholder="e.g. Client Kickoff Meeting">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Event Type</label>
            <select name="event_type" class="form-control">
              <?php foreach ($TYPES as $tk=>$tv): ?>
              <option value="<?= $tk ?>" <?= ($edit_event['event_type']??'other')===$tk?'selected':'' ?>><?= $tv['icon'] ?> <?= $tv['label'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <?php foreach (['scheduled'=>'Scheduled','completed'=>'Completed','cancelled'=>'Cancelled'] as $sv=>$sl): ?>
              <option value="<?= $sv ?>" <?= ($edit_event['status']??'scheduled')===$sv?'selected':'' ?>><?= $sl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start *</label>
            <input type="datetime-local" name="start_datetime" class="form-control" required id="cal-start"
              value="<?= h($edit_event ? date('Y-m-d\TH:i',strtotime($edit_event['start_datetime'])) : date('Y-m-d\T').'09:00') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">End</label>
            <input type="datetime-local" name="end_datetime" class="form-control" id="cal-end"
              value="<?= h($edit_event && $edit_event['end_datetime'] ? date('Y-m-d\TH:i',strtotime($edit_event['end_datetime'])) : '') ?>">
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
          <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text2);cursor:pointer">
            <input type="checkbox" name="all_day" <?= ($edit_event['all_day']??0)?'checked':'' ?> style="accent-color:var(--orange)">
            All-day event
          </label>
          <div style="flex:1"></div>
          <div class="form-group" style="margin:0;min-width:140px">
            <select name="recur" class="form-control">
              <?php foreach (['none'=>'No repeat','daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly'] as $rv=>$rl): ?>
              <option value="<?= $rv ?>" <?= ($edit_event['recur']??'none')===$rv?'selected':'' ?>><?= $rl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control"
              value="<?= h($edit_event['location']??'') ?>" placeholder="Room, URL, or address">
          </div>
          <div class="form-group">
            <label class="form-label">Link to Project</label>
            <select name="project_id" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($projects as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($edit_event['project_id']??'')==$p['id']?'selected':'' ?>><?= h($p['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Link to Task</label>
            <select name="task_id" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($all_tasks as $t_item): ?>
              <option value="<?= $t_item['id'] ?>" <?= ($edit_event['task_id']??'')==$t_item['id']?'selected':'' ?>><?= h($t_item['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Link to Contact</label>
            <select name="contact_id" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($contacts as $c_item): ?>
              <option value="<?= $c_item['id'] ?>" <?= ($edit_event['contact_id']??'')==$c_item['id']?'selected':'' ?>><?= h($c_item['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" style="min-height:70px"
            placeholder="Agenda, notes, links…"><?= h($edit_event['description']??'') ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Invite Team Members</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:10px;max-height:160px;overflow-y:auto">
            <?php foreach ($all_users as $u_item): ?>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2);cursor:pointer;padding:3px">
              <input type="checkbox" name="attendees[]" value="<?= $u_item['id'] ?>"
                <?= (in_array($u_item['id'],$edit_attendee_ids) || $u_item['id']==$uid) ? 'checked' : '' ?>
                style="accent-color:var(--orange)">
              <?= h($u_item['name']) ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-event');<?= $edit_id?"location.href='calendar.php'":'' ?>">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= $edit_id ? 'Save Changes' : 'Create Event' ?></button>
      </div>
    </form>
  </div>
</div>

<script>
// Auto-fill end time (+1h) when start changes
document.getElementById('cal-start')?.addEventListener('change', function () {
    var endEl = document.getElementById('cal-end');
    if (endEl && !endEl.value) {
        var d = new Date(this.value);
        d.setHours(d.getHours() + 1);
        endEl.value = d.toISOString().slice(0, 16);
    }
});

// Pre-fill date when clicking a calendar cell
function dayClick(date) {
    var s = document.querySelector('[name="start_datetime"]');
    var e = document.querySelector('[name="end_datetime"]');
    if (s) s.value = date + 'T09:00';
    if (e) e.value = date + 'T10:00';
    openModal('modal-event');
}

<?php if ($edit_id): ?>
document.addEventListener('DOMContentLoaded', function () { openModal('modal-event'); });
<?php endif; ?>
</script>

<?php renderLayoutEnd(); ?>