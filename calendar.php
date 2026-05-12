<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
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

// ── GOOGLE MEET HELPER ──
// Generates a Google Meet link using Google Meet's direct join URL pattern.
// We store the generated meet room ID in the event location field.
function generateMeetLink(): string {
    // Google Meet room codes follow the pattern: xxx-yyyy-zzz (10 chars + 2 dashes)
    $chars = 'abcdefghijklmnopqrstuvwxyz';
    $part1 = substr(str_shuffle($chars), 0, 3);
    $part2 = substr(str_shuffle($chars), 0, 4);
    $part3 = substr(str_shuffle($chars), 0, 3);
    return "https://meet.google.com/{$part1}-{$part2}-{$part3}";
}

function isMeetLink(string $str): bool {
    return (bool)preg_match('#^https://meet\.google\.com/[a-z]{3}-[a-z]{4}-[a-z]{3}$#', trim($str));
}

// ── POST HANDLERS ──
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_event') {
        // Only managers can create/edit events; members can only RSVP
        if (!isManager()) {
            flash('Only managers can create or edit events.', 'error');
            ob_end_clean(); header('Location: calendar.php'); exit;
        }
        $eid   = (int)($_POST['eid'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $type  = $_POST['event_type'] ?? 'other';
        $start = $_POST['start_datetime'] ?: null;
        $end   = $_POST['end_datetime'] ?: null;
        $allday= isset($_POST['all_day']) ? 1 : 0;
        $loc   = trim($_POST['location'] ?? '');
        $color = $TYPES[$type]['color'] ?? '#f97316';
        $proj  = (int)($_POST['project_id'] ?? 0) ?: null;
        $task  = (int)($_POST['task_id']    ?? 0) ?: null;
        $cont  = (int)($_POST['contact_id'] ?? 0) ?: null;
        $recur = $_POST['recur'] ?? 'none';
        $stat  = $_POST['status'] ?? 'scheduled';
        $atts  = $_POST['attendees'] ?? [];

        // Auto-generate Google Meet link for meeting type if requested
        if ($type === 'meeting' && isset($_POST['auto_meet']) && !$loc) {
            $loc = generateMeetLink();
        }

        if (!$title || !$start) {
            flash('Title and start date/time are required.', 'error');
            ob_end_clean(); header('Location: calendar.php'); exit;
        }

        if ($eid) {
            $s = $db->prepare("UPDATE calendar_events SET title=?,description=?,event_type=?,start_datetime=?,end_datetime=?,all_day=?,location=?,color=?,project_id=?,task_id=?,contact_id=?,recur=?,status=? WHERE id=?");
            $s->bind_param("sssssiissiiissi",$title,$desc,$type,$start,$end,$allday,$loc,$color,$proj,$task,$cont,$recur,$stat,$eid);
            $s->execute();
            $db->query("DELETE FROM calendar_attendees WHERE event_id=$eid");
            logActivity('updated event',$title,$eid);
        } else {
            $s = $db->prepare("INSERT INTO calendar_events (title,description,event_type,start_datetime,end_datetime,all_day,location,color,project_id,task_id,contact_id,recur,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $s->bind_param("sssssiissiiissi",$title,$desc,$type,$start,$end,$allday,$loc,$color,$proj,$task,$cont,$recur,$stat,$uid);
            $s->execute();
            $eid = $db->insert_id;
            logActivity('created event',$title,$eid);
        }
        // Save attendees + always include creator
        $atts[] = $uid;
        $atts = array_unique(array_map('intval', $atts));
        $sa = $db->prepare("INSERT IGNORE INTO calendar_attendees (event_id,user_id) VALUES (?,?)");
        foreach ($atts as $aid) { $sa->bind_param("ii",$eid,$aid); $sa->execute(); }
        flash('Event saved.','success');
        ob_end_clean(); header('Location: calendar.php?y='.date('Y',strtotime($start)).'&m='.date('m',strtotime($start))); exit;
    }

    // ── INSTANT MEET: create a quick meeting event with Google Meet link ──
    if ($action === 'instant_meet') {
        if (!isManager()) {
            flash('Only managers can create instant meetings.', 'error');
            ob_end_clean(); header('Location: calendar.php'); exit;
        }
        $title   = trim($_POST['meet_title'] ?? 'Instant Meeting');
        $meetLink= generateMeetLink();
        $now     = date('Y-m-d H:i:s');
        $end_dt  = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $atts    = $_POST['meet_attendees'] ?? [];

        $s = $db->prepare("INSERT INTO calendar_events (title,description,event_type,start_datetime,end_datetime,all_day,location,color,recur,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $desc = 'Instant Google Meet — click the link to join now.';
        $color = '#6366f1';
        $s->bind_param("ssssssisssi",$title,$desc,'meeting',$now,$end_dt,0,$meetLink,$color,'none','scheduled',$uid);
        $s->execute();
        $eid = $db->insert_id;
        logActivity('started instant meeting',$title,$eid);

        // Add attendees
        $atts[] = $uid;
        $atts = array_unique(array_map('intval', $atts));
        $sa = $db->prepare("INSERT IGNORE INTO calendar_attendees (event_id,user_id) VALUES (?,?)");
        foreach ($atts as $aid) { $sa->bind_param("ii",$eid,$aid); $sa->execute(); }

        flash('Instant meeting created! <a href="'.$meetLink.'" target="_blank" style="color:#6366f1;font-weight:700">Join Google Meet →</a>','success');
        ob_end_clean(); header('Location: calendar.php?view=event&eid='.$eid); exit;
    }

    if ($action === 'delete_event') {
        $eid = (int)$_POST['eid'];
        $ev  = $db->query("SELECT created_by,title FROM calendar_events WHERE id=$eid")->fetch_assoc();
        if ($ev && ($ev['created_by'] == $uid || isManager())) {
            $db->query("DELETE FROM calendar_events WHERE id=$eid");
            logActivity('deleted event',$ev['title'],$eid);
            flash('Event deleted.','success');
        }
        ob_end_clean(); header('Location: calendar.php'); exit;
    }

    if ($action === 'rsvp') {
        $eid  = (int)$_POST['eid'];
        $rsvp = $_POST['rsvp'] ?? 'pending';
        $db->query("UPDATE calendar_attendees SET rsvp='$rsvp' WHERE event_id=$eid AND user_id=$uid");
        flash('Response saved.','success');
        ob_end_clean(); header('Location: calendar.php?view=event&eid='.$eid); exit;
    }
}
ob_end_clean();

// ── VIEW PARAMS ──
$view   = $_GET['view']   ?? 'month';    // month | week | list | event | meetings
$year   = (int)($_GET['y'] ?? date('Y'));
$month  = (int)($_GET['m'] ?? date('m'));
$week   = (int)($_GET['w'] ?? date('W'));
$edit_id= (int)($_GET['edit'] ?? 0);
$view_eid=(int)($_GET['eid'] ?? 0);
$type_f = $_GET['type'] ?? '';

// Clamp
$month = max(1, min(12, $month));
$year  = max(2020, min(2099, $year));

// ── LOAD REFERENCE DATA ──
$all_users= $db->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$projects = $db->query("SELECT id,title FROM projects WHERE status NOT IN ('cancelled') ORDER BY title")->fetch_all(MYSQLI_ASSOC);
$contacts = $db->query("SELECT id,name FROM contacts ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$all_tasks= $db->query("SELECT id,title,due_date FROM tasks WHERE status!='done' ORDER BY title")->fetch_all(MYSQLI_ASSOC);

// ── LOAD EVENTS (month range + upcoming tasks/deadlines) ──
$range_start = sprintf('%04d-%02d-01', $year, $month);
$range_end   = date('Y-m-t', strtotime($range_start));

// For week view
$week_start = date('Y-m-d', strtotime($year.'W'.sprintf('%02d',$week).'1'));
$week_end   = date('Y-m-d', strtotime($year.'W'.sprintf('%02d',$week).'7'));

$type_where = $type_f ? " AND ce.event_type='".$db->real_escape_string($type_f)."'" : '';

// Fetch events visible to current user:
function loadEvents(mysqli $db, string $from, string $to, int $uid, string $type_where='', bool $is_manager=false): array {
    $scope = $is_manager
        ? "1=1"
        : "(ce.created_by=$uid OR EXISTS(SELECT 1 FROM calendar_attendees WHERE event_id=ce.id AND user_id=$uid))";
    return $db->query("
        SELECT ce.*, u.name AS creator_name,
            p.title AS proj_title, t.title AS task_title, c.name AS contact_name,
            (SELECT GROUP_CONCAT(us.name ORDER BY us.name SEPARATOR ', ')
             FROM calendar_attendees ca JOIN users us ON us.id=ca.user_id
             WHERE ca.event_id=ce.id) AS attendee_names,
            (SELECT rsvp FROM calendar_attendees WHERE event_id=ce.id AND user_id=$uid) AS my_rsvp
        FROM calendar_events ce
        LEFT JOIN users   u ON u.id=ce.created_by
        LEFT JOIN projects p ON p.id=ce.project_id
        LEFT JOIN tasks    t ON t.id=ce.task_id
        LEFT JOIN contacts c ON c.id=ce.contact_id
        WHERE DATE(ce.start_datetime) BETWEEN '$from' AND '$to'
          AND ($scope)
          $type_where
        ORDER BY ce.start_datetime ASC
    ")->fetch_all(MYSQLI_ASSOC);
}

$is_mgr       = isManager();
$month_events = loadEvents($db, $range_start, $range_end, $uid, $type_where, $is_mgr);
$week_events  = loadEvents($db, $week_start,  $week_end,  $uid, $type_where, $is_mgr);

// ── INJECT TASK DEADLINES ──
function taskDeadlines(mysqli $db, string $from, string $to, int $uid): array {
    $rows = $db->query("
        SELECT t.id, t.title, t.due_date, t.priority, t.status, p.title AS proj_title
        FROM tasks t LEFT JOIN projects p ON p.id=t.project_id
        WHERE t.due_date BETWEEN '$from' AND '$to'
          AND t.status != 'done'
          AND (t.assigned_to=$uid OR t.created_by=$uid)
        ORDER BY t.due_date ASC
    ")->fetch_all(MYSQLI_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'=>'t'.$r['id'], 'title'=>'⏰ '.$r['title'],
            'event_type'=>'deadline', 'color'=>'#ef4444',
            'start_datetime'=>$r['due_date'].' 00:00:00',
            'all_day'=>1, 'is_task'=>true,
            'proj_title'=>$r['proj_title'], 'priority'=>$r['priority'],
            'task_id'=>$r['id'],
        ];
    }
    return $out;
}

// ── PROJECT MILESTONES ──
function projMilestones(mysqli $db, string $from, string $to, int $uid=0, bool $is_manager=false): array {
    $scope = $is_manager
        ? "1=1"
        : "(created_by=$uid OR EXISTS(SELECT 1 FROM project_members WHERE project_id=projects.id AND user_id=$uid))";
    $rows = $db->query("
        SELECT id,title,due_date,status FROM projects
        WHERE due_date BETWEEN '$from' AND '$to'
          AND status NOT IN ('cancelled','completed')
          AND ($scope)
        ORDER BY due_date
    ")->fetch_all(MYSQLI_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'=>'p'.$r['id'], 'title'=>'🏁 '.$r['title'],
            'event_type'=>'milestone', 'color'=>'#10b981',
            'start_datetime'=>$r['due_date'].' 00:00:00', 'all_day'=>1,
            'is_project'=>true, 'project_id'=>$r['id'],
        ];
    }
    return $out;
}

$task_deadlines  = taskDeadlines($db, $range_start, $range_end, $uid);
$proj_milestones = projMilestones($db, $range_start, $range_end, $uid, $is_mgr);

$all_month       = array_merge($month_events, $task_deadlines, $proj_milestones);
usort($all_month, fn($a,$b)=>strcmp($a['start_datetime'],$b['start_datetime']));

// Group by date for calendar grid
$events_by_date = [];
foreach ($all_month as $e) {
    $d = substr($e['start_datetime'],0,10);
    $events_by_date[$d][] = $e;
}

// ── MEETINGS VIEW: load all meeting-type events ──
$meetings_scope = $is_mgr
    ? "1=1"
    : "(ce.created_by=$uid OR EXISTS(SELECT 1 FROM calendar_attendees WHERE event_id=ce.id AND user_id=$uid))";
$meetings_filter = $_GET['mfilt'] ?? 'upcoming'; // upcoming | past | all
$meetings_search = trim($_GET['msearch'] ?? '');
$meet_date_where = '';
if ($meetings_filter === 'upcoming') $meet_date_where = "AND ce.start_datetime >= NOW()";
elseif ($meetings_filter === 'past')  $meet_date_where = "AND ce.start_datetime < NOW()";
$meet_search_where = $meetings_search ? " AND ce.title LIKE '%".$db->real_escape_string($meetings_search)."%'" : '';

$all_meetings = $db->query("
    SELECT ce.*, u.name AS creator_name,
        p.title AS proj_title,
        (SELECT GROUP_CONCAT(us.name ORDER BY us.name SEPARATOR ', ')
         FROM calendar_attendees ca JOIN users us ON us.id=ca.user_id
         WHERE ca.event_id=ce.id) AS attendee_names,
        (SELECT COUNT(*) FROM calendar_attendees WHERE event_id=ce.id) AS attendee_count,
        (SELECT rsvp FROM calendar_attendees WHERE event_id=ce.id AND user_id=$uid) AS my_rsvp
    FROM calendar_events ce
    LEFT JOIN users u ON u.id=ce.created_by
    LEFT JOIN projects p ON p.id=ce.project_id
    WHERE ce.event_type='meeting'
      AND ($meetings_scope)
      $meet_date_where
      $meet_search_where
    ORDER BY ce.start_datetime " . ($meetings_filter==='past' ? 'DESC' : 'ASC') . "
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

// Single event view
$single_event = null;
$event_attendees = [];
if ($view === 'event' && $view_eid) {
    $single_event = $db->query("
        SELECT ce.*, u.name AS creator_name,
            p.title AS proj_title, t.title AS task_title, c.name AS contact_name
        FROM calendar_events ce
        LEFT JOIN users u ON u.id=ce.created_by
        LEFT JOIN projects p ON p.id=ce.project_id
        LEFT JOIN tasks    t ON t.id=ce.task_id
        LEFT JOIN contacts c ON c.id=ce.contact_id
        WHERE ce.id=$view_eid
    ")->fetch_assoc();
    if ($single_event) {
        $event_attendees = $db->query("
            SELECT u.id,u.name,u.role,ca.rsvp
            FROM calendar_attendees ca JOIN users u ON u.id=ca.user_id
            WHERE ca.event_id=$view_eid
            ORDER BY u.name
        ")->fetch_all(MYSQLI_ASSOC);
    }
}

// Edit mode
$edit_event = null;
$edit_attendee_ids = [];
if ($edit_id) {
    $edit_event = $db->query("SELECT * FROM calendar_events WHERE id=$edit_id")->fetch_assoc();
    $ea = $db->query("SELECT user_id FROM calendar_attendees WHERE event_id=$edit_id")->fetch_all(MYSQLI_ASSOC);
    $edit_attendee_ids = array_column($ea,'user_id');
}

// Upcoming events for list view (next 60 days)
$list_events = loadEvents($db, date('Y-m-d'), date('Y-m-d',strtotime('+60 days')), $uid, $type_where, $is_mgr);
$list_tasks  = taskDeadlines($db, date('Y-m-d'), date('Y-m-d',strtotime('+60 days')), $uid);
$list_all    = array_merge($list_events, $list_tasks, projMilestones($db, date('Y-m-d'), date('Y-m-d',strtotime('+60 days')), $uid, $is_mgr));
usort($list_all, fn($a,$b)=>strcmp($a['start_datetime'],$b['start_datetime']));

// Nav helpers
$prev_m = $month === 1  ? ['y'=>$year-1,'m'=>12] : ['y'=>$year,'m'=>$month-1];
$next_m = $month === 12 ? ['y'=>$year+1,'m'=>1]  : ['y'=>$year,'m'=>$month+1];

// Count upcoming meetings for badge
$upcoming_meeting_count = $db->query("
    SELECT COUNT(*) AS c FROM calendar_events ce
    WHERE ce.event_type='meeting' AND ce.start_datetime >= NOW()
      AND ($meetings_scope)
")->fetch_assoc()['c'] ?? 0;

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
.cal-event-pill{
  font-size:10.5px;font-weight:600;
  padding:2px 6px;border-radius:4px;
  margin-bottom:2px;cursor:pointer;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  display:block;line-height:1.4;
  transition:opacity .1s;
}
.cal-event-pill:hover{opacity:.85}
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
.list-event-row{display:flex;align-items:flex-start;gap:12px;padding:10px 14px;background:var(--bg2);border:1px solid var(--border);border-left:3px solid var(--ec);border-radius:var(--radius);margin-bottom:7px;cursor:pointer;transition:border-color .15s,box-shadow .15s}
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

/* ── MEETINGS VIEW ── */
.meetings-header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.meetings-search-bar{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.meetings-search-bar input{padding:7px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--bg2);color:var(--text);font-size:13px;width:220px}
.meetings-filter-tabs{display:flex;border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden}
.meetings-filter-tabs a{padding:6px 14px;font-size:12px;font-weight:600;color:var(--text2);background:var(--bg3);text-decoration:none;white-space:nowrap;transition:background .15s,color .15s}
.meetings-filter-tabs a.active{background:#6366f1;color:#fff}

.meeting-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 18px;margin-bottom:10px;display:flex;align-items:flex-start;gap:14px;transition:box-shadow .15s,border-color .15s;position:relative}
.meeting-card:hover{box-shadow:0 3px 12px rgba(99,102,241,.12);border-color:#6366f133}
.meeting-card .meet-time-col{min-width:90px;flex-shrink:0;text-align:center;background:var(--bg3);border-radius:8px;padding:8px 6px}
.meeting-card .meet-time-date{font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;margin-bottom:2px}
.meeting-card .meet-time-hour{font-size:14px;font-weight:800;color:var(--text)}
.meeting-card .meet-time-end{font-size:10.5px;color:var(--text3);margin-top:1px}
.meeting-card .meet-body{flex:1;min-width:0}
.meeting-card .meet-title{font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px;display:flex;align-items:center;gap:8px}
.meeting-card .meet-meta{font-size:12px;color:var(--text3);display:flex;gap:12px;flex-wrap:wrap;margin-bottom:8px}
.meeting-card .meet-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:8px}

/* Google Meet button */
.btn-gmeet{display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border-radius:var(--radius-sm);font-size:12.5px;font-weight:700;background:linear-gradient(135deg,#1a73e8,#4285f4);color:#fff;border:none;cursor:pointer;text-decoration:none;transition:opacity .15s,transform .1s;white-space:nowrap}
.btn-gmeet:hover{opacity:.9;transform:translateY(-1px)}
.btn-gmeet svg{width:16px;height:16px;flex-shrink:0}

/* Instant Meet modal */
.instant-meet-modal .modal{max-width:480px}
.meet-link-display{background:var(--bg3);border:1px solid #6366f133;border-radius:8px;padding:12px 14px;font-family:monospace;font-size:13px;color:#6366f1;font-weight:600;word-break:break-all;margin:10px 0}

/* Meeting status badges */
.meet-status-live{background:#10b98120;color:#10b981;border-radius:99px;padding:2px 10px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:4px}
.meet-status-live::before{content:'';width:6px;height:6px;border-radius:50%;background:#10b981;animation:pulse-dot 1.2s ease-in-out infinite}
.meet-status-upcoming{background:#6366f120;color:#6366f1;border-radius:99px;padding:2px 10px;font-size:11px;font-weight:700}
.meet-status-past{background:var(--bg3);color:var(--text3);border-radius:99px;padding:2px 10px;font-size:11px;font-weight:600}

@keyframes pulse-dot{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.6;transform:scale(1.3)}}

/* Meetings stats row */
.meetings-stats{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.meetings-stat-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px 18px;display:flex;align-items:center;gap:12px;flex:1;min-width:130px}
.meetings-stat-icon{font-size:22px}
.meetings-stat-val{font-size:20px;font-weight:800;color:var(--text)}
.meetings-stat-lbl{font-size:11.5px;color:var(--text3)}

/* Instant meet quick panel */
.instant-meet-panel{background:linear-gradient(135deg,#6366f115,#4f46e510);border:1px solid #6366f130;border-radius:var(--radius-lg);padding:16px 18px;margin-bottom:18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.instant-meet-panel .imp-icon{font-size:28px}
.instant-meet-panel .imp-text{flex:1}
.instant-meet-panel .imp-title{font-size:14px;font-weight:700;color:var(--text)}
.instant-meet-panel .imp-sub{font-size:12px;color:var(--text3)}

/* Meet link inside event detail */
.meet-link-box{background:linear-gradient(135deg,#1a73e810,#4285f408);border:1px solid #1a73e830;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px;margin-bottom:14px}
.meet-link-box .mlb-icon{font-size:24px}
.meet-link-box .mlb-body{flex:1;min-width:0}
.meet-link-box .mlb-label{font-size:11px;color:var(--text3);text-transform:uppercase;font-weight:700;letter-spacing:.04em;margin-bottom:2px}
.meet-link-box .mlb-url{font-size:12px;color:#1a73e8;font-weight:600;word-break:break-all}

@media(max-width:900px){
  .ev-detail-grid{grid-template-columns:1fr}
  .cal-cell{min-height:60px}
  .cal-event-pill{display:none}
  .cal-cell.has-events .cal-day-num::after{content:'●';color:var(--orange);font-size:8px;margin-left:2px}
  .week-grid{grid-template-columns:30px repeat(7,1fr)}
  .meeting-card{flex-wrap:wrap}
  .meeting-card .meet-time-col{min-width:auto;flex-basis:100%}
}
@media(max-width:600px){
  .cal-grid{grid-template-columns:repeat(7,1fr)}
  .cal-dow{font-size:9px;padding:5px 2px}
  .cal-day-num{font-size:10px}
  .meetings-stats{flex-wrap:wrap}
  .meetings-stat-card{flex-basis:calc(50% - 6px)}
}
</style>

<?php if ($single_event && $view === 'event'): // ══ SINGLE EVENT VIEW ══ ?>

<div style="margin-bottom:14px">
  <a href="calendar.php?y=<?= date('Y',strtotime($single_event['start_datetime'])) ?>&m=<?= date('m',strtotime($single_event['start_datetime'])) ?>" style="color:var(--text3);font-size:13px">← Back to Calendar</a>
</div>

<div class="ev-detail-grid">
  <div>
    <div class="card" style="margin-bottom:16px">
      <!-- Header -->
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
            <?php if ($single_event['recur'] !== 'none'): ?>
            <span class="badge" style="background:var(--bg3);color:var(--text2)">🔁 <?= ucfirst($single_event['recur']) ?></span>
            <?php endif; ?>
            <?php
              // Live meeting badge
              if ($single_event['event_type'] === 'meeting' && $single_event['status'] === 'scheduled') {
                  $s_ts = strtotime($single_event['start_datetime']);
                  $e_ts = $single_event['end_datetime'] ? strtotime($single_event['end_datetime']) : $s_ts + 3600;
                  $now_ts = time();
                  if ($now_ts >= $s_ts && $now_ts <= $e_ts) {
                      echo '<span class="meet-status-live">LIVE</span>';
                  }
              }
            ?>
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

      <!-- Google Meet Link Box (if meeting and location is a meet link) -->
      <?php if ($single_event['event_type'] === 'meeting' && $single_event['location'] && isMeetLink($single_event['location'])): ?>
      <div class="meet-link-box">
        <div class="mlb-icon">
          <svg viewBox="0 0 24 24" width="28" height="28" fill="none"><path d="M4 6h16v10a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" fill="#1a73e820" stroke="#1a73e8" stroke-width="1.5"/><path d="M16 10l4-3v10l-4-3" stroke="#1a73e8" stroke-width="1.5" stroke-linejoin="round"/></svg>
        </div>
        <div class="mlb-body">
          <div class="mlb-label">Google Meet Link</div>
          <div class="mlb-url"><?= h($single_event['location']) ?></div>
        </div>
        <a href="<?= h($single_event['location']) ?>" target="_blank" class="btn-gmeet">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.89L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
          Join Meet
        </a>
      </div>
      <?php endif; ?>

      <!-- Meta grid -->
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
          <div class="ev-meta-val">
            <?php if ($single_event['location'] && isMeetLink($single_event['location'])): ?>
              <span style="color:#1a73e8;font-size:12px">📹 Google Meet</span>
            <?php elseif ($single_event['location']): ?>
              <?= h($single_event['location']) ?>
            <?php else: ?>
              —
            <?php endif; ?>
          </div>
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

      <!-- RSVP (if attendee) -->
      <?php
        $my_rsvp_row = array_filter($event_attendees, fn($a)=>$a['id']==$uid);
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

  <!-- Right: Attendees -->
  <div class="card">
    <div class="card-title" style="margin-bottom:14px">Attendees (<?= count($event_attendees) ?>)</div>
    <?php if (empty($event_attendees)): ?>
    <p style="font-size:13px;color:var(--text3)">No attendees.</p>
    <?php else: ?>
    <?php foreach ($event_attendees as $a):
      $rc=['accepted'=>'#10b981','declined'=>'#ef4444','pending'=>'#f59e0b'][$a['rsvp']]??'#94a3b8';
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

<?php elseif ($view === 'meetings'): // ══ MEETINGS VIEW ══ ?>

<!-- Meetings Header -->
<div class="meetings-header">
  <div>
    <h2 style="font-family:var(--font-display);font-size:20px;font-weight:800;margin-bottom:2px">🤝 Meetings</h2>
    <div style="font-size:13px;color:var(--text3)">Manage and join all your meetings</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="calendar.php" class="btn btn-ghost btn-sm">← Calendar</a>
    <?php if (isManager()): ?>
    <button class="btn btn-ghost btn-sm" onclick="openModal('modal-instant-meet')" style="border-color:#6366f1;color:#6366f1">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><path d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.89L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
      Instant Meet
    </button>
    <button class="btn btn-primary btn-sm" onclick="openMeetingModal()">＋ Schedule Meeting</button>
    <?php endif; ?>
  </div>
</div>

<!-- Stats -->
<?php
$stat_total    = count($db->query("SELECT id FROM calendar_events WHERE event_type='meeting' AND ($meetings_scope)")->fetch_all());
$stat_today    = count($db->query("SELECT id FROM calendar_events WHERE event_type='meeting' AND DATE(start_datetime)=CURDATE() AND ($meetings_scope)")->fetch_all());
$stat_upcoming = $upcoming_meeting_count;
$stat_live     = count($db->query("SELECT id FROM calendar_events WHERE event_type='meeting' AND start_datetime<=NOW() AND (end_datetime IS NULL OR end_datetime>=NOW()) AND status='scheduled' AND ($meetings_scope)")->fetch_all());
?>
<div class="meetings-stats">
  <div class="meetings-stat-card">
    <div class="meetings-stat-icon">🔴</div>
    <div>
      <div class="meetings-stat-val"><?= $stat_live ?></div>
      <div class="meetings-stat-lbl">Live Now</div>
    </div>
  </div>
  <div class="meetings-stat-card">
    <div class="meetings-stat-icon">📅</div>
    <div>
      <div class="meetings-stat-val"><?= $stat_today ?></div>
      <div class="meetings-stat-lbl">Today</div>
    </div>
  </div>
  <div class="meetings-stat-card">
    <div class="meetings-stat-icon">🗓</div>
    <div>
      <div class="meetings-stat-val"><?= $stat_upcoming ?></div>
      <div class="meetings-stat-lbl">Upcoming</div>
    </div>
  </div>
  <div class="meetings-stat-card">
    <div class="meetings-stat-icon">📊</div>
    <div>
      <div class="meetings-stat-val"><?= $stat_total ?></div>
      <div class="meetings-stat-lbl">Total</div>
    </div>
  </div>
</div>

<!-- Instant Meet Panel (managers only) -->
<?php if (isManager()): ?>
<div class="instant-meet-panel">
  <div class="imp-icon">⚡</div>
  <div class="imp-text">
    <div class="imp-title">Start an Instant Meeting</div>
    <div class="imp-sub">Create a Google Meet link instantly and invite your team in seconds</div>
  </div>
  <button class="btn-gmeet" onclick="openModal('modal-instant-meet')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.89L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
    New Instant Meet
  </button>
</div>
<?php endif; ?>

<!-- Filter + Search -->
<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:16px;flex-wrap:wrap">
  <div class="meetings-filter-tabs">
    <a href="calendar.php?view=meetings&mfilt=upcoming<?= $meetings_search?"&msearch=".urlencode($meetings_search):'' ?>" class="<?= $meetings_filter==='upcoming'?'active':'' ?>">Upcoming</a>
    <a href="calendar.php?view=meetings&mfilt=past<?= $meetings_search?"&msearch=".urlencode($meetings_search):'' ?>"     class="<?= $meetings_filter==='past'?'active':'' ?>">Past</a>
    <a href="calendar.php?view=meetings&mfilt=all<?= $meetings_search?"&msearch=".urlencode($meetings_search):'' ?>"      class="<?= $meetings_filter==='all'?'active':'' ?>">All</a>
  </div>
  <form method="GET" class="meetings-search-bar">
    <input type="hidden" name="view" value="meetings">
    <input type="hidden" name="mfilt" value="<?= h($meetings_filter) ?>">
    <input type="text" name="msearch" placeholder="Search meetings…" value="<?= h($meetings_search) ?>">
    <button type="submit" class="btn btn-ghost btn-sm">🔍</button>
    <?php if ($meetings_search): ?><a href="calendar.php?view=meetings&mfilt=<?= $meetings_filter ?>" class="btn btn-ghost btn-sm">✕</a><?php endif; ?>
  </form>
</div>

<!-- Meeting Cards -->
<?php if (empty($all_meetings)): ?>
<div class="card"><div class="empty-state"><div class="icon">🤝</div><p>No meetings found<?= $meetings_filter==='upcoming'?' — nothing scheduled ahead':'' ?>.</p>
<?php if (isManager()): ?><button class="btn btn-primary" onclick="openMeetingModal()">Schedule First Meeting</button><?php endif; ?>
</div></div>
<?php else: ?>
<?php foreach ($all_meetings as $m):
  $m_start  = strtotime($m['start_datetime']);
  $m_end    = $m['end_datetime'] ? strtotime($m['end_datetime']) : $m_start + 3600;
  $now_ts   = time();
  $is_live  = ($now_ts >= $m_start && $now_ts <= $m_end && $m['status']==='scheduled');
  $is_past  = ($m_start < $now_ts && !$is_live);
  $has_meet = $m['location'] && isMeetLink($m['location']);
?>
<div class="meeting-card" style="<?= $is_live ? 'border-color:#10b98150;box-shadow:0 0 0 2px #10b98115' : '' ?>">
  <!-- Time Column -->
  <div class="meet-time-col">
    <div class="meet-time-date"><?= date('M j', $m_start) ?></div>
    <div class="meet-time-hour"><?= date('g:ia', $m_start) ?></div>
    <div class="meet-time-end"><?= date('g:ia', $m_end) ?></div>
    <div style="margin-top:5px">
      <?php if ($is_live): ?>
        <span class="meet-status-live">LIVE</span>
      <?php elseif ($is_past): ?>
        <span class="meet-status-past">Past</span>
      <?php else: ?>
        <span class="meet-status-upcoming">Scheduled</span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Body -->
  <div class="meet-body">
    <div class="meet-title">
      <?= h($m['title']) ?>
      <?php if ($has_meet): ?>
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" title="Google Meet"><path d="M4 6h16v10a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" fill="#1a73e820" stroke="#1a73e8" stroke-width="1.5"/><path d="M16 10l4-3v10l-4-3" stroke="#1a73e8" stroke-width="1.5" stroke-linejoin="round"/></svg>
      <?php endif; ?>
    </div>
    <div class="meet-meta">
      <?php if ($m['proj_title']): ?><span>📁 <?= h($m['proj_title']) ?></span><?php endif; ?>
      <?php if ($m['attendee_count'] > 0): ?><span>👥 <?= $m['attendee_count'] ?> attendee<?= $m['attendee_count']!==1?'s':'' ?></span><?php endif; ?>
      <?php if ($m['attendee_names']): ?><span style="color:var(--text2)"><?= h(mb_substr($m['attendee_names'],0,60)).(mb_strlen($m['attendee_names'])>60?'…':'') ?></span><?php endif; ?>
      <span>By <?= h($m['creator_name']) ?></span>
      <?php if ($m['my_rsvp']): ?>
        <?php $rc=['accepted'=>'#10b981','declined'=>'#ef4444','pending'=>'#f59e0b'][$m['my_rsvp']]??'#94a3b8'; ?>
        <span style="color:<?= $rc ?>">● <?= ucfirst($m['my_rsvp']) ?></span>
      <?php endif; ?>
    </div>

    <div class="meet-actions">
      <a href="calendar.php?view=event&eid=<?= $m['id'] ?>" class="btn btn-ghost btn-sm">View Details</a>
      <?php if ($has_meet): ?>
      <a href="<?= h($m['location']) ?>" target="_blank" class="btn-gmeet">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.89L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
        <?= $is_live ? 'Join Now' : 'Open Meet' ?>
      </a>
      <button class="btn btn-ghost btn-sm" onclick="copyMeetLink('<?= h($m['location']) ?>', this)" style="font-size:11px">📋 Copy Link</button>
      <?php elseif ($m['location']): ?>
      <span style="font-size:12px;color:var(--text3)">📍 <?= h($m['location']) ?></span>
      <?php endif; ?>
      <?php if (isManager()): ?>
      <a href="calendar.php?edit=<?= $m['id'] ?>" class="btn btn-ghost btn-sm" style="font-size:11px">✎ Edit</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php else: // ══ CALENDAR VIEWS (month/week/list) ══ ?>

<!-- TOOLBAR -->
<div class="cal-toolbar">
  <div class="cal-nav">
    <?php if ($view==='month'): ?>
    <a href="calendar.php?view=month&y=<?= $prev_m['y'] ?>&m=<?= $prev_m['m'] ?>" class="btn btn-ghost btn-sm">‹</a>
    <span class="cal-title"><?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></span>
    <a href="calendar.php?view=month&y=<?= $next_m['y'] ?>&m=<?= $next_m['m'] ?>" class="btn btn-ghost btn-sm">›</a>
    <?php elseif ($view==='week'): ?>
    <a href="calendar.php?view=week&y=<?= $year ?>&w=<?= $week-1 < 1 ? 52 : $week-1 ?>" class="btn btn-ghost btn-sm">‹</a>
    <span class="cal-title">Week <?= $week ?>, <?= $year ?></span>
    <a href="calendar.php?view=week&y=<?= $year ?>&w=<?= $week+1 > 52 ? 1 : $week+1 ?>" class="btn btn-ghost btn-sm">›</a>
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
      <a href="calendar.php?view=meetings"                              class="<?= $view==='meetings'?'active':'' ?>" style="<?= $upcoming_meeting_count>0?'position:relative':'' ?>">
        🤝 Meetings<?php if ($upcoming_meeting_count > 0): ?> <span style="background:#6366f1;color:#fff;border-radius:99px;font-size:10px;padding:0 5px;margin-left:3px"><?= $upcoming_meeting_count ?></span><?php endif; ?>
      </a>
    </div>
    <div style="display:flex;gap:6px">
      <?php if (isManager()): ?>
      <button class="btn btn-ghost btn-sm" onclick="openModal('modal-instant-meet')" style="border-color:#6366f1;color:#6366f1;font-size:12px" title="Start Instant Google Meet">
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:3px"><path d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.89L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
        Instant Meet
      </button>
      <button class="btn btn-primary" onclick="openModal('modal-event')">＋ Event</button>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- TYPE FILTER PILLS -->
<div class="type-filter">
  <a href="calendar.php?view=<?= $view ?>&y=<?= $year ?>&m=<?= $month ?>" class="type-pill <?= !$type_f?'active':'' ?>">All</a>
  <?php foreach ($TYPES as $tk=>$tv): ?>
  <a href="calendar.php?view=<?= $view ?>&y=<?= $year ?>&m=<?= $month ?>&type=<?= $tk ?>" class="type-pill <?= $type_f===$tk?'active':'' ?>" style="<?= $type_f===$tk?"border-color:{$tv['color']};color:{$tv['color']};background:{$tv['color']}18":'' ?>"><?= $tv['icon'] ?> <?= $tv['label'] ?></a>
  <?php endforeach; ?>
</div>

<?php if ($view === 'month'): // ══ MONTH VIEW ══ ?>

<div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden">
  <div class="cal-grid">
    <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow): ?>
    <div class="cal-dow"><?= $dow ?></div>
    <?php endforeach; ?>

    <?php
    $first_day = mktime(0,0,0,$month,1,$year);
    $days_in   = (int)date('t',$first_day);
    $dow_first = (int)date('N',$first_day); // 1=Mon..7=Sun
    $today     = date('Y-m-d');
    $total_cells = $dow_first - 1 + $days_in;
    $total_cells = $total_cells + (7 - ($total_cells % 7 ?: 7));

    for ($cell = 0; $cell < $total_cells; $cell++):
        $day_offset = $cell - ($dow_first - 1) + 1;
        if ($day_offset < 1) {
            $prev_days = cal_days_in_month(CAL_GREGORIAN, $prev_m['m'], $prev_m['y']);
            $actual_day = $prev_days + $day_offset;
            $actual_date = sprintf('%04d-%02d-%02d',$prev_m['y'],$prev_m['m'],$actual_day);
            $other = true;
        } elseif ($day_offset > $days_in) {
            $actual_day = $day_offset - $days_in;
            $actual_date = sprintf('%04d-%02d-%02d',$next_m['y'],$next_m['m'],$actual_day);
            $other = true;
        } else {
            $actual_day = $day_offset;
            $actual_date = sprintf('%04d-%02d-%02d',$year,$month,$actual_day);
            $other = false;
        }
        $is_today   = ($actual_date === $today);
        $day_events = $events_by_date[$actual_date] ?? [];
        $has_events = !empty($day_events);
        $classes    = 'cal-cell'.($is_today?' today':'').($other?' other-month':'').($has_events?' has-events':'');
    ?>
    <div class="<?= $classes ?>" onclick="dayClick('<?= $actual_date ?>')">
      <div class="cal-day-num <?= $is_today?'today-num':'' ?>">
        <span><?= $actual_day ?></span>
        <?php if ($is_today): ?><span style="font-size:9px;background:var(--orange);color:#fff;padding:1px 5px;border-radius:99px">Today</span><?php endif; ?>
      </div>
      <?php
        $shown = 0;
        foreach (array_slice($day_events, 0, 3) as $e):
          $shown++;
          $ec = $e['color'] ?? '#f97316';
          $is_task = !empty($e['is_task']) || !empty($e['is_project']);
          // Show meet camera icon for meetings with meet links
          $has_meet_icon = (!$is_task && ($e['event_type']??'')==='meeting' && !empty($e['location']) && isMeetLink($e['location'])) ? '📹 ' : '';
      ?>
      <a href="<?= $is_task ? 'calendar.php?view=event&eid=0&date='.$actual_date : 'calendar.php?view=event&eid='.$e['id'] ?>"
         class="cal-event-pill"
         style="background:<?= $ec ?>22;color:<?= $ec ?>;border-left:2px solid <?= $ec ?>"
         onclick="event.stopPropagation()"
         title="<?= h($e['title']) ?>">
        <?= $has_meet_icon ?><?= h(mb_substr($e['title'],0,22)).(mb_strlen($e['title'])>22?'…':'') ?>
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
$week_task_deadlines = taskDeadlines($db, $week_start, $week_end, $uid);
foreach ($week_task_deadlines as $e) {
    $d = substr($e['start_datetime'],0,10);
    $week_events_by_day[$d][] = $e;
}
$hours = range(8, 20);
?>
<div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;overflow-x:auto">
  <div class="week-grid" style="min-width:600px">
    <!-- Header row -->
    <div style="border-right:1px solid var(--border);border-bottom:1px solid var(--border);background:var(--bg3)"></div>
    <?php for ($d=0;$d<7;$d++):
      $day_date = date('Y-m-d', strtotime($week_start." +$d days"));
      $day_label= date('D j', strtotime($day_date));
      $is_today = ($day_date === date('Y-m-d'));
    ?>
    <div class="week-dow <?= $is_today?'today-col':'' ?>">
      <div style="font-size:10px;color:var(--text3);font-weight:700"><?= date('D',strtotime($day_date)) ?></div>
      <div style="font-size:14px;font-weight:800;color:<?= $is_today?'var(--orange)':'var(--text)' ?>"><?= date('j',strtotime($day_date)) ?></div>
      <!-- All-day events -->
      <?php foreach ($week_events_by_day[$day_date]??[] as $e):
        if (!$e['all_day']) continue;
        $ec=$e['color']??'#f97316';
      ?>
      <div style="font-size:9px;background:<?= $ec ?>22;color:<?= $ec ?>;border-left:2px solid <?= $ec ?>;padding:1px 4px;border-radius:2px;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h(mb_substr($e['title'],0,14)) ?></div>
      <?php endforeach; ?>
    </div>
    <?php endfor; ?>

    <!-- Hour rows -->
    <?php foreach ($hours as $h_val): ?>
    <div class="week-hour"><?= $h_val ?>:00</div>
    <?php for ($d=0;$d<7;$d++):
      $day_date = date('Y-m-d', strtotime($week_start." +$d days"));
      $is_today = ($day_date === date('Y-m-d'));
      $slot_events = array_filter($week_events_by_day[$day_date]??[], function($e) use($h_val){
          if ($e['all_day']) return false;
          $eh = (int)date('G',strtotime($e['start_datetime']));
          return $eh === $h_val;
      });
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
    $is_today = ($date === date('Y-m-d'));
    $is_tomorrow = ($date === date('Y-m-d',strtotime('+1 day')));
    $label = $is_today ? 'Today — '.date('l, M j', strtotime($date))
           : ($is_tomorrow ? 'Tomorrow — '.date('l, M j', strtotime($date))
           : date('l, M j, Y', strtotime($date)));
?>
<div class="list-day-header <?= $is_today?'today-hdr':'' ?>"><?= $label ?></div>
<?php foreach ($evs as $e):
  $ec     = $e['color'] ?? '#f97316';
  $is_sys = !empty($e['is_task']) || !empty($e['is_project']);
  $href   = $is_sys ? ($e['is_task']??false ? 'tasks.php?edit='.$e['task_id'] : 'projects.php?view='.$e['project_id'])
                    : 'calendar.php?view=event&eid='.$e['id'];
  $tstr   = $e['all_day'] ? 'All day' : date('g:ia', strtotime($e['start_datetime']));
  $type_cfg = $TYPES[$e['event_type']] ?? $TYPES['other'];
  $has_meet_link = (!$is_sys && ($e['event_type']??'')==='meeting' && !empty($e['location']) && isMeetLink($e['location']));
?>
<div class="list-event-row" style="--ec:<?= $ec ?>" onclick="location.href='<?= $href ?>'">
  <div class="list-event-time"><?= $tstr ?></div>
  <div class="list-event-body">
    <div class="list-event-title"><?= h($e['title']) ?></div>
    <div class="list-event-meta">
      <span><?= $type_cfg['icon'] ?> <?= $type_cfg['label'] ?></span>
      <?php if (!empty($e['proj_title'])): ?> · 📁 <?= h($e['proj_title']) ?><?php endif; ?>
      <?php if (!empty($e['location']) && !isMeetLink($e['location']??'')): ?> · 📍 <?= h($e['location']) ?><?php endif; ?>
      <?php if ($has_meet_link): ?> · <span style="color:#1a73e8;font-weight:600">📹 Google Meet</span><?php endif; ?>
      <?php if (!empty($e['attendee_names'])): ?> · 👥 <?= h($e['attendee_names']) ?><?php endif; ?>
    </div>
  </div>
  <?php if ($has_meet_link): ?>
  <a href="<?= h($e['location']) ?>" target="_blank" class="btn-gmeet" style="font-size:11px;padding:5px 10px" onclick="event.stopPropagation()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.89L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
    Join
  </a>
  <?php else: ?>
  <div style="width:10px;height:10px;border-radius:50%;background:<?= $ec ?>;flex-shrink:0;margin-top:4px"></div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; // end views ?>

<?php endif; // end single/meetings/list ?>

<!-- ══ CREATE / EDIT EVENT MODAL ══ -->
<div class="modal-overlay <?= ($edit_id)?'open':'' ?>" id="modal-event">
  <div class="modal" style="max-width:620px">
    <div class="modal-header">
      <div class="modal-title" id="modal-event-title"><?= $edit_id ? 'Edit Event' : '＋ New Event' ?></div>
      <button class="modal-close" onclick="closeModal('modal-event');<?= $edit_id?"location.href='calendar.php'":'' ?>">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save_event">
      <input type="hidden" name="eid"    value="<?= $edit_id ?>">
      <div class="modal-body">

        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" required
            value="<?= h($edit_event['title']??$_GET['prefill_title']??'') ?>"
            placeholder="e.g. Client Kickoff Meeting">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Event Type</label>
            <select name="event_type" class="form-control" id="ev-type" onchange="updateColor(this);toggleMeetOptions(this)">
              <?php foreach ($TYPES as $tk=>$tv): ?>
              <option value="<?= $tk ?>" data-color="<?= $tv['color'] ?>"
                <?= ($edit_event['event_type']??'other')===$tk?'selected':'' ?>><?= $tv['icon'] ?> <?= $tv['label'] ?></option>
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
            <input type="datetime-local" name="start_datetime" class="form-control" required
              value="<?= h($edit_event ? date('Y-m-d\TH:i',strtotime($edit_event['start_datetime'])) : (isset($_GET['date'])?$_GET['date'].'T09:00':date('Y-m-d\TH:i'))) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">End</label>
            <input type="datetime-local" name="end_datetime" class="form-control"
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

        <!-- Google Meet Options (shown only for Meeting type) -->
        <div id="meet-options-box" style="display:none;background:#6366f108;border:1px solid #6366f130;border-radius:10px;padding:12px 14px;margin-bottom:14px">
          <div style="font-size:12px;font-weight:700;color:#6366f1;margin-bottom:8px">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#6366f1" stroke-width="2" style="vertical-align:middle;margin-right:4px"><path d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.89L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
            Google Meet Options
          </div>
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2);cursor:pointer;margin-bottom:8px">
            <input type="checkbox" name="auto_meet" id="auto-meet-chk" style="accent-color:#6366f1" <?= (($edit_event['event_type']??'')==='meeting'&&empty($edit_event['location']))?'checked':'' ?>>
            <span>Auto-generate Google Meet link (only if location is empty)</span>
          </label>
          <?php
            $existing_meet = '';
            if ($edit_event && $edit_event['location'] && isMeetLink($edit_event['location'])) {
                $existing_meet = $edit_event['location'];
            }
          ?>
          <?php if ($existing_meet): ?>
          <div style="font-size:12px;color:var(--text3);margin-top:4px">
            Current link: <a href="<?= h($existing_meet) ?>" target="_blank" style="color:#1a73e8"><?= h($existing_meet) ?></a>
          </div>
          <?php endif; ?>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Location <span id="loc-or-meet" style="color:#1a73e8;font-size:11px"></span></label>
            <input type="text" name="location" class="form-control" id="loc-input"
              value="<?= h($edit_event['location']??'') ?>" placeholder="Room, URL, or leave blank for auto-Meet">
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
              <?php foreach ($all_tasks as $t): ?>
              <option value="<?= $t['id'] ?>" <?= ($edit_event['task_id']??'')==$t['id']?'selected':'' ?>><?= h($t['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Link to Contact</label>
            <select name="contact_id" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($contacts as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($edit_event['contact_id']??'')==$c['id']?'selected':'' ?>><?= h($c['name']) ?></option>
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
            <?php foreach ($all_users as $u): ?>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2);cursor:pointer;padding:3px">
              <input type="checkbox" name="attendees[]" value="<?= $u['id'] ?>"
                <?= in_array($u['id'],$edit_attendee_ids)||$u['id']==$uid?'checked':'' ?>
                style="accent-color:var(--orange)">
              <?= h($u['name']) ?>
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

<!-- ══ INSTANT MEET MODAL ══ -->
<div class="modal-overlay" id="modal-instant-meet">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <div class="modal-title">⚡ Start Instant Meeting</div>
      <button class="modal-close" onclick="closeModal('modal-instant-meet')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="instant_meet">
      <div class="modal-body">
        <div style="background:#6366f110;border:1px solid #6366f130;border-radius:8px;padding:12px;margin-bottom:16px;font-size:12.5px;color:var(--text2)">
          <strong style="color:#6366f1">📹 Google Meet</strong> — A unique meeting link will be generated instantly and the event will be created on the calendar.
        </div>
        <div class="form-group">
          <label class="form-label">Meeting Title</label>
          <input type="text" name="meet_title" class="form-control" value="Instant Meeting — <?= date('M j, g:ia') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Invite Team Members</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:10px;max-height:160px;overflow-y:auto">
            <?php foreach ($all_users as $u): ?>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2);cursor:pointer;padding:3px">
              <input type="checkbox" name="meet_attendees[]" value="<?= $u['id'] ?>"
                <?= $u['id']==$uid?'checked':'' ?>
                style="accent-color:#6366f1">
              <?= h($u['name']) ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div style="font-size:11.5px;color:var(--text3);padding:8px;background:var(--bg3);border-radius:6px">
          💡 The meeting will start now (1 hour duration) and appear on everyone's calendar. Share the Meet link to let attendees join.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-instant-meet')">Cancel</button>
        <button type="submit" class="btn-gmeet" style="padding:9px 18px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.89L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
          Start Meeting Now
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function dayClick(date) {
  document.querySelector('[name="start_datetime"]').value = date + 'T09:00';
  document.querySelector('[name="end_datetime"]').value   = date + 'T10:00';
  openModal('modal-event');
}

function updateColor(sel) {
  // Optional: could update a color preview
}

// Show/hide Google Meet options when event type changes
function toggleMeetOptions(sel) {
  var isMeeting = sel.value === 'meeting';
  var box = document.getElementById('meet-options-box');
  var locHint = document.getElementById('loc-or-meet');
  if (box) box.style.display = isMeeting ? 'block' : 'none';
  if (locHint) locHint.textContent = isMeeting ? '(auto-Meet if empty)' : '';
}

// Open meeting modal pre-filled as meeting type
function openMeetingModal() {
  var sel = document.getElementById('ev-type');
  if (sel) {
    sel.value = 'meeting';
    toggleMeetOptions(sel);
    var autoChk = document.getElementById('auto-meet-chk');
    if (autoChk) autoChk.checked = true;
  }
  openModal('modal-event');
}

// Copy meet link to clipboard
function copyMeetLink(link, btn) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(link).then(function() {
      var orig = btn.textContent;
      btn.textContent = '✓ Copied!';
      btn.style.color = '#10b981';
      setTimeout(function(){ btn.textContent = orig; btn.style.color = ''; }, 2000);
    });
  } else {
    var ta = document.createElement('textarea');
    ta.value = link; document.body.appendChild(ta); ta.select();
    document.execCommand('copy'); document.body.removeChild(ta);
    var orig = btn.textContent;
    btn.textContent = '✓ Copied!';
    setTimeout(function(){ btn.textContent = orig; }, 2000);
  }
}

// Initialize meet options on page load
document.addEventListener('DOMContentLoaded', function() {
  var sel = document.getElementById('ev-type');
  if (sel) toggleMeetOptions(sel);
  <?php if ($edit_id): ?>
  openModal('modal-event');
  <?php endif; ?>
});
</script>

<?php renderLayoutEnd(); ?>