<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();

$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

// ── CREATE TABLES IF NOT EXIST (this is why the page 500'd) ────────────────
$db->query("CREATE TABLE IF NOT EXISTS meetings (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(300)  NOT NULL,
    agenda          TEXT          DEFAULT NULL,
    start_datetime  DATETIME      NOT NULL,
    end_datetime    DATETIME      DEFAULT NULL,
    location        VARCHAR(300)  DEFAULT NULL,
    meet_link       VARCHAR(500)  DEFAULT NULL,
    project_id      INT           DEFAULT NULL,
    status          ENUM('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
    meeting_type    VARCHAR(50)   DEFAULT 'internal',
    notes           TEXT          DEFAULT NULL,
    created_by      INT           NOT NULL,
    created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_start (start_datetime),
    INDEX idx_created_by (created_by),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->query("CREATE TABLE IF NOT EXISTS meeting_attendees (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id  INT  NOT NULL,
    user_id     INT  NOT NULL,
    rsvp        ENUM('pending','accepted','declined') DEFAULT 'pending',
    UNIQUE KEY uniq_meet_user (meeting_id, user_id),
    INDEX idx_meeting (meeting_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── POST HANDLERS ──────────────────────────────────────────────────────────
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_meeting') {
        if (!isManager()) {
            flash('Only managers can create or edit meetings.', 'error');
            ob_end_clean(); header('Location: meetings.php'); exit;
        }
        $mid       = (int)($_POST['mid'] ?? 0);
        $title     = trim($_POST['title'] ?? '');
        $agenda    = trim($_POST['agenda'] ?? '');
        $start     = $_POST['start_datetime'] ?: null;
        $end       = ($_POST['end_datetime'] ?? '') ?: null;
        $location  = trim($_POST['location'] ?? '');
        $meet_link = trim($_POST['meet_link'] ?? '');
        $proj      = (int)($_POST['project_id'] ?? 0); // int, NOT null — fixes PHP 8 fatal
        $status    = $_POST['status'] ?? 'scheduled';
        $type      = $_POST['meeting_type'] ?? 'internal';
        $atts      = $_POST['attendees'] ?? [];

        if (!$title || !$start) {
            flash('Title and start date/time are required.', 'error');
            ob_end_clean(); header('Location: meetings.php'); exit;
        }

        if ($mid) {
            $s = $db->prepare("UPDATE meetings SET title=?,agenda=?,start_datetime=?,end_datetime=?,location=?,meet_link=?,project_id=?,status=?,meeting_type=? WHERE id=?");
            $s->bind_param("ssssssissi", $title,$agenda,$start,$end,$location,$meet_link,$proj,$status,$type,$mid);
            $s->execute();
            $db->query("DELETE FROM meeting_attendees WHERE meeting_id=$mid");
            logActivity('updated meeting', $title, $mid);
        } else {
            $s = $db->prepare("INSERT INTO meetings (title,agenda,start_datetime,end_datetime,location,meet_link,project_id,status,meeting_type,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $s->bind_param("ssssssissi", $title,$agenda,$start,$end,$location,$meet_link,$proj,$status,$type,$uid);
            $s->execute();
            $mid = (int)$db->insert_id;
            logActivity('created meeting', $title, $mid);
        }

        // Save attendees — always include creator
        $atts[] = $uid;
        $atts = array_unique(array_map('intval', $atts));
        $sa = $db->prepare("INSERT IGNORE INTO meeting_attendees (meeting_id, user_id) VALUES (?,?)");
        foreach ($atts as $aid) { $sa->bind_param("ii", $mid, $aid); $sa->execute(); }

        flash('Meeting saved.', 'success');
        ob_end_clean(); header('Location: meetings.php?view=detail&mid='.$mid); exit;
    }

    if ($action === 'delete_meeting') {
        if (!isManager()) {
            flash('Only managers can delete meetings.', 'error');
            ob_end_clean(); header('Location: meetings.php'); exit;
        }
        $mid = (int)$_POST['mid'];
        $ev  = $db->query("SELECT title,created_by FROM meetings WHERE id=$mid")->fetch_assoc();
        if ($ev && ($ev['created_by'] == $uid || isManager())) {
            $db->query("DELETE FROM meetings WHERE id=$mid");
            $db->query("DELETE FROM meeting_attendees WHERE meeting_id=$mid");
            logActivity('deleted meeting', $ev['title'], $mid);
            flash('Meeting deleted.', 'success');
        }
        ob_end_clean(); header('Location: meetings.php'); exit;
    }

    if ($action === 'rsvp') {
        $mid  = (int)$_POST['mid'];
        $rsvp = in_array($_POST['rsvp'] ?? '', ['accepted','declined','pending'])
                ? $_POST['rsvp'] : 'pending';
        $db->query("UPDATE meeting_attendees SET rsvp='$rsvp' WHERE meeting_id=$mid AND user_id=$uid");
        flash('Response saved.', 'success');
        ob_end_clean(); header('Location: meetings.php?view=detail&mid='.$mid); exit;
    }

    if ($action === 'save_notes') {
        $mid   = (int)$_POST['mid'];
        $notes = trim($_POST['notes'] ?? '');
        $is_att = $db->query("SELECT 1 FROM meeting_attendees WHERE meeting_id=$mid AND user_id=$uid")->num_rows > 0;
        if ($is_att || isManager()) {
            $upd = $db->prepare("UPDATE meetings SET notes=? WHERE id=?");
            $upd->bind_param("si", $notes, $mid);
            $upd->execute();
            flash('Notes saved.', 'success');
        }
        ob_end_clean(); header('Location: meetings.php?view=detail&mid='.$mid); exit;
    }
}
ob_end_clean();

// ── VIEW PARAMS ────────────────────────────────────────────────────────────
$view     = $_GET['view']   ?? 'list';
$mid_view = (int)($_GET['mid']  ?? 0);
$edit_id  = (int)($_GET['edit'] ?? 0);
$status_f = $_GET['status'] ?? '';
$search   = trim($_GET['q'] ?? '');

// ── REFERENCE DATA ─────────────────────────────────────────────────────────
$all_users = $db->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$projects  = $db->query("SELECT id,title FROM projects WHERE status NOT IN ('cancelled') ORDER BY title")->fetch_all(MYSQLI_ASSOC);

$MEETING_TYPES = [
    'internal' => ['label'=>'Internal',  'color'=>'#6366f1','icon'=>'🏢'],
    'client'   => ['label'=>'Client',    'color'=>'#f97316','icon'=>'🤝'],
    'standup'  => ['label'=>'Stand-up',  'color'=>'#10b981','icon'=>'☀️'],
    'review'   => ['label'=>'Review',    'color'=>'#3b82f6','icon'=>'🔍'],
    'planning' => ['label'=>'Planning',  'color'=>'#8b5cf6','icon'=>'🗺️'],
    'other'    => ['label'=>'Other',     'color'=>'#94a3b8','icon'=>'📌'],
];
$STATUS_CONFIG = [
    'scheduled'   => ['label'=>'Scheduled',   'color'=>'#3b82f6'],
    'in_progress' => ['label'=>'In Progress', 'color'=>'#f59e0b'],
    'completed'   => ['label'=>'Completed',   'color'=>'#10b981'],
    'cancelled'   => ['label'=>'Cancelled',   'color'=>'#ef4444'],
];

$is_mgr = isManager();

// ── LOAD MEETINGS ──────────────────────────────────────────────────────────
function loadMeetings(mysqli $db, int $uid, bool $is_manager,
                      string $status_f='', string $search='', string $range='all'): array {
    $scope = $is_manager
        ? "1=1"
        : "(m.created_by=$uid OR EXISTS(SELECT 1 FROM meeting_attendees WHERE meeting_id=m.id AND user_id=$uid))";

    $where = [];
    if ($status_f) $where[] = "m.status='".$db->real_escape_string($status_f)."'";
    if ($search)   $where[] = "m.title LIKE '%".$db->real_escape_string($search)."%'";
    if ($range === 'upcoming') $where[] = "m.start_datetime >= NOW()";
    if ($range === 'past')     $where[] = "m.start_datetime < NOW()";
    $extra = $where ? ' AND '.implode(' AND ',$where) : '';

    return $db->query("
        SELECT m.*,
               u.name AS creator_name,
               p.title AS proj_title,
               (SELECT COUNT(*) FROM meeting_attendees WHERE meeting_id=m.id) AS attendee_count,
               (SELECT GROUP_CONCAT(us.name ORDER BY us.name SEPARATOR ', ')
                FROM meeting_attendees ma JOIN users us ON us.id=ma.user_id
                WHERE ma.meeting_id=m.id) AS attendee_names,
               (SELECT rsvp FROM meeting_attendees
                WHERE meeting_id=m.id AND user_id=$uid LIMIT 1) AS my_rsvp
        FROM meetings m
        LEFT JOIN users    u ON u.id=m.created_by
        LEFT JOIN projects p ON p.id=m.project_id
        WHERE ($scope) $extra
        ORDER BY m.start_datetime DESC
    ")->fetch_all(MYSQLI_ASSOC);
}

$all_meetings      = loadMeetings($db, $uid, $is_mgr, $status_f, $search);
$upcoming_meetings = loadMeetings($db, $uid, $is_mgr, '', '', 'upcoming');
$today_meetings    = array_filter($upcoming_meetings,
    fn($m) => substr($m['start_datetime'],0,10) === date('Y-m-d'));

$total        = count($all_meetings);
$scheduled    = count(array_filter($all_meetings, fn($m)=>$m['status']==='scheduled'));
$completed    = count(array_filter($all_meetings, fn($m)=>$m['status']==='completed'));
$upcoming_cnt = count($upcoming_meetings);

// Single meeting detail
$single_meeting         = null;
$meeting_attendees_list = [];
if ($view === 'detail' && $mid_view) {
    $single_meeting = $db->query("
        SELECT m.*, u.name AS creator_name, p.title AS proj_title
        FROM meetings m
        LEFT JOIN users    u ON u.id=m.created_by
        LEFT JOIN projects p ON p.id=m.project_id
        WHERE m.id=$mid_view
    ")->fetch_assoc();
    if ($single_meeting) {
        $meeting_attendees_list = $db->query("
            SELECT u.id, u.name, u.role, ma.rsvp
            FROM meeting_attendees ma JOIN users u ON u.id=ma.user_id
            WHERE ma.meeting_id=$mid_view
            ORDER BY u.name
        ")->fetch_all(MYSQLI_ASSOC);
    }
}

// Edit mode
$edit_meeting = null;
$edit_att_ids = [];
if ($edit_id) {
    $edit_meeting = $db->query("SELECT * FROM meetings WHERE id=$edit_id")->fetch_assoc();
    $ea = $db->query("SELECT user_id FROM meeting_attendees WHERE meeting_id=$edit_id")->fetch_all(MYSQLI_ASSOC);
    $edit_att_ids = array_column($ea, 'user_id');
}

renderLayout('Meetings', 'meetings');
?>
<style>
.meet-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.meet-tabs{display:flex;border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden}
.meet-tabs a{padding:7px 16px;font-size:12.5px;font-weight:600;color:var(--text2);background:var(--bg3);text-decoration:none;transition:all .15s;white-space:nowrap}
.meet-tabs a.active{background:var(--orange);color:#fff}
.meet-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.meet-stat-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;display:flex;align-items:center;gap:12px}
.meet-stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.meet-stat-num{font-size:22px;font-weight:800;line-height:1;color:var(--text)}
.meet-stat-lbl{font-size:11.5px;color:var(--text3);margin-top:2px}
.today-banner{background:linear-gradient(135deg,var(--orange-bg),var(--bg3));border:1px solid var(--orange);border-radius:var(--radius);padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.today-banner-title{font-size:13px;font-weight:700;color:var(--orange);margin-bottom:6px}
.today-pill{display:inline-flex;align-items:center;gap:6px;background:var(--bg2);border:1px solid var(--border);border-radius:99px;padding:5px 12px;font-size:12.5px;font-weight:600;color:var(--text);cursor:pointer;text-decoration:none;transition:all .15s}
.today-pill:hover{border-color:var(--orange);color:var(--orange)}
.meet-card{background:var(--bg2);border:1px solid var(--border);border-left:3px solid var(--mc,#f97316);border-radius:var(--radius);padding:16px;margin-bottom:10px;cursor:pointer;transition:box-shadow .15s,border-color .15s;display:flex;align-items:flex-start;gap:14px}
.meet-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.15);border-color:var(--border2)}
.meet-card-time{flex-shrink:0;min-width:66px;text-align:center;background:var(--bg3);border-radius:8px;padding:8px 10px}
.meet-card-time-h{font-size:16px;font-weight:800;color:var(--text)}
.meet-card-time-d{font-size:10px;color:var(--text3);margin-top:2px}
.meet-card-body{flex:1;min-width:0}
.meet-card-title{font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px}
.meet-card-meta{font-size:12px;color:var(--text3);display:flex;flex-wrap:wrap;gap:10px}
.meet-card-badges{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px}
.meet-join-btn{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#1a73e8,#1558b0);color:#fff;border:none;border-radius:8px;padding:7px 14px;font-size:12.5px;font-weight:700;cursor:pointer;text-decoration:none;transition:opacity .15s;white-space:nowrap}
.meet-join-btn:hover{opacity:.88}
.meet-detail-grid{display:grid;grid-template-columns:1fr 280px;gap:18px;align-items:start}
.meet-meta-box{background:var(--bg3);border-radius:8px;padding:10px;margin-bottom:8px}
.meet-meta-lbl{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px}
.meet-meta-val{font-size:13px;color:var(--text);font-weight:500}
.rsvp-btn{padding:7px 16px;border-radius:var(--radius-sm);font-size:12.5px;font-weight:600;border:1px solid var(--border);cursor:pointer;background:var(--bg3);color:var(--text2);transition:all .15s}
.rsvp-btn.accepted{background:rgba(16,185,129,.15);border-color:#10b981;color:#10b981}
.rsvp-btn.declined{background:rgba(239,68,68,.15);border-color:#ef4444;color:#ef4444}
.instant-meet-box{background:linear-gradient(135deg,rgba(26,115,232,.12),rgba(21,88,176,.06));border:1px solid rgba(26,115,232,.3);border-radius:var(--radius);padding:18px;margin-bottom:20px}
.instant-meet-box h3{font-size:14px;font-weight:700;color:var(--text);margin-bottom:6px}
.instant-meet-box p{font-size:12.5px;color:var(--text3);margin-bottom:12px}
.gm-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.meet-filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;align-items:center}
.meet-filter-pill{padding:5px 13px;border-radius:99px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:var(--bg3);color:var(--text2);text-decoration:none;transition:all .15s}
.meet-filter-pill:hover,.meet-filter-pill.active{border-color:var(--orange);color:var(--orange);background:var(--orange-bg)}
.notes-area{width:100%;min-height:110px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:10px;font-size:13px;color:var(--text);font-family:inherit;resize:vertical;transition:border-color .15s}
.notes-area:focus{outline:none;border-color:var(--orange)}
.meet-empty{text-align:center;padding:60px 20px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius)}
.meet-empty .icon{font-size:48px;margin-bottom:12px}
.meet-empty p{color:var(--text3);font-size:14px}
.meet-search{display:flex;align-items:center;gap:8px;background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius-sm);padding:6px 12px;flex:1;max-width:280px}
.meet-search input{background:none;border:none;outline:none;font-size:13px;color:var(--text);width:100%}
.meet-search input::placeholder{color:var(--text3)}
@media(max-width:900px){.meet-stats{grid-template-columns:repeat(2,1fr)}.meet-detail-grid{grid-template-columns:1fr}}
@media(max-width:600px){.meet-stats{grid-template-columns:1fr 1fr}.meet-card{flex-direction:column}.meet-card-time{text-align:left;width:100%}}
</style>

<?php if ($view === 'detail' && $single_meeting): ?>

<div style="margin-bottom:14px">
    <a href="meetings.php" style="color:var(--text3);font-size:13px">← Back to Meetings</a>
</div>

<div class="meet-detail-grid">
    <div>
        <div class="card" style="margin-bottom:16px">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:18px;flex-wrap:wrap">
                <div>
                    <?php $mt=$MEETING_TYPES[$single_meeting['meeting_type']]??$MEETING_TYPES['other'];
                          $sc=$STATUS_CONFIG[$single_meeting['status']]??$STATUS_CONFIG['scheduled']; ?>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                        <span style="font-size:24px"><?= $mt['icon'] ?></span>
                        <h2 style="font-family:var(--font-display);font-size:20px;font-weight:700"><?= h($single_meeting['title']) ?></h2>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        <span class="badge" style="background:<?= $mt['color'] ?>20;color:<?= $mt['color'] ?>"><?= $mt['icon'] ?> <?= $mt['label'] ?></span>
                        <span class="badge" style="background:<?= $sc['color'] ?>20;color:<?= $sc['color'] ?>"><?= $sc['label'] ?></span>
                        <?php if ($single_meeting['proj_title']): ?>
                        <span class="badge" style="background:var(--orange-bg);color:var(--orange)">📁 <?= h($single_meeting['proj_title']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-shrink:0;flex-wrap:wrap;align-items:center">
                    <?php if ($single_meeting['meet_link']): ?>
                    <a href="<?= h($single_meeting['meet_link']) ?>" target="_blank" class="meet-join-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M15 8v8H5V8h10m2-2H3c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h14c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4V7c0-.55-.45-1-1-1z"/></svg>
                        Join Google Meet
                    </a>
                    <?php endif; ?>
                    <?php if (isManager()): ?>
                    <a href="meetings.php?edit=<?= $mid_view ?>" class="btn btn-ghost btn-sm">✎ Edit</a>
                    <form method="POST" onsubmit="return confirm('Delete this meeting?')" style="display:inline">
                        <input type="hidden" name="action" value="delete_meeting">
                        <input type="hidden" name="mid" value="<?= $mid_view ?>">
                        <button class="btn btn-danger btn-sm">🗑</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px">
                <div class="meet-meta-box">
                    <div class="meet-meta-lbl">Start</div>
                    <div class="meet-meta-val"><?= date('M j, Y g:ia', strtotime($single_meeting['start_datetime'])) ?></div>
                </div>
                <div class="meet-meta-box">
                    <div class="meet-meta-lbl">End</div>
                    <div class="meet-meta-val"><?= $single_meeting['end_datetime'] ? date('M j, Y g:ia', strtotime($single_meeting['end_datetime'])) : '—' ?></div>
                </div>
                <div class="meet-meta-box">
                    <div class="meet-meta-lbl">Location</div>
                    <div class="meet-meta-val"><?= $single_meeting['location'] ? h($single_meeting['location']) : '—' ?></div>
                </div>
                <?php if ($single_meeting['meet_link']): ?>
                <div class="meet-meta-box" style="grid-column:1/-1">
                    <div class="meet-meta-lbl">Google Meet Link</div>
                    <div class="meet-meta-val" style="display:flex;align-items:center;gap:8px">
                        <a href="<?= h($single_meeting['meet_link']) ?>" target="_blank" style="color:#1a73e8;word-break:break-all"><?= h($single_meeting['meet_link']) ?></a>
                        <button onclick="navigator.clipboard.writeText('<?= h($single_meeting['meet_link']) ?>');this.textContent='✓ Copied!';setTimeout(()=>this.textContent='Copy',2000)" class="btn btn-ghost btn-sm" style="flex-shrink:0">Copy</button>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($single_meeting['agenda']): ?>
            <div style="margin-bottom:16px">
                <div style="font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px">Agenda</div>
                <div style="background:var(--bg3);border-radius:8px;padding:12px;font-size:13.5px;color:var(--text2);line-height:1.7"><?= nl2br(h($single_meeting['agenda'])) ?></div>
            </div>
            <?php endif; ?>

            <?php
            $my_row  = array_filter($meeting_attendees_list, fn($a)=>$a['id']==$uid);
            $my_rsvp = $my_row ? array_values($my_row)[0]['rsvp'] : null;
            ?>
            <?php if ($my_rsvp !== null): ?>
            <div style="border-top:1px solid var(--border);padding-top:14px;margin-bottom:16px">
                <div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em">Your Response</div>
                <form method="POST" style="display:flex;gap:8px">
                    <input type="hidden" name="action" value="rsvp">
                    <input type="hidden" name="mid" value="<?= $mid_view ?>">
                    <button name="rsvp" value="accepted" class="rsvp-btn <?= $my_rsvp==='accepted'?'accepted':'' ?>">✓ Accept</button>
                    <button name="rsvp" value="declined" class="rsvp-btn <?= $my_rsvp==='declined'?'declined':'' ?>">✕ Decline</button>
                    <button name="rsvp" value="pending"  class="rsvp-btn <?= $my_rsvp==='pending'?'active':'' ?>">? Maybe</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <?php $can_notes = !empty(array_filter($meeting_attendees_list, fn($a)=>$a['id']==$uid)) || isManager(); ?>
        <div class="card">
            <div class="card-title" style="margin-bottom:14px">📝 Meeting Notes</div>
            <?php if ($can_notes): ?>
            <form method="POST">
                <input type="hidden" name="action" value="save_notes">
                <input type="hidden" name="mid" value="<?= $mid_view ?>">
                <textarea name="notes" class="notes-area" placeholder="Add agenda notes, action items, decisions…"><?= h($single_meeting['notes'] ?? '') ?></textarea>
                <div style="margin-top:10px;display:flex;justify-content:flex-end">
                    <button type="submit" class="btn btn-primary btn-sm">Save Notes</button>
                </div>
            </form>
            <?php elseif ($single_meeting['notes']): ?>
            <div style="background:var(--bg3);border-radius:8px;padding:12px;font-size:13.5px;color:var(--text2);line-height:1.7"><?= nl2br(h($single_meeting['notes'])) ?></div>
            <?php else: ?>
            <p style="color:var(--text3);font-size:13px">No notes yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="card" style="margin-bottom:14px">
            <div class="card-title" style="margin-bottom:14px">👥 Attendees (<?= count($meeting_attendees_list) ?>)</div>
            <?php if (empty($meeting_attendees_list)): ?>
            <p style="font-size:13px;color:var(--text3)">No attendees.</p>
            <?php else: ?>
            <?php foreach ($meeting_attendees_list as $a):
                $rc=['accepted'=>'#10b981','declined'=>'#ef4444','pending'=>'#f59e0b'][$a['rsvp']]??'#94a3b8';
            ?>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
                <div class="avatar" style="width:30px;height:30px;font-size:11px"><?= strtoupper(substr($a['name'],0,1)) ?></div>
                <div style="flex:1">
                    <div style="font-size:13px;font-weight:600;color:var(--text)"><?= h($a['name']) ?></div>
                    <div style="font-size:11px;color:var(--text3)"><?= ucfirst($a['role']) ?></div>
                </div>
                <span class="badge" style="background:<?= $rc ?>20;color:<?= $rc ?>"><?= ucfirst($a['rsvp']) ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border)">
                <div style="font-size:11px;color:var(--text3)">Created by <?= h($single_meeting['creator_name']) ?></div>
            </div>
        </div>

        <?php if ($single_meeting['meet_link']): ?>
        <div class="instant-meet-box">
            <h3>🎥 Google Meet</h3>
            <p>Click to join this meeting directly. The same link can be reused for all recurring sessions.</p>
            <a href="<?= h($single_meeting['meet_link']) ?>" target="_blank" class="meet-join-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M15 8v8H5V8h10m2-2H3c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h14c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4V7c0-.55-.45-1-1-1z"/></svg>
                Join Now
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: /* ══ LIST VIEW ══ */ ?>

<div class="meet-toolbar">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <h1 style="font-family:var(--font-display);font-size:20px;font-weight:800;margin:0">Meetings</h1>
        <div class="meet-tabs">
            <a href="meetings.php?view=list"     class="<?= $view==='list'?'active':'' ?>">All</a>
            <a href="meetings.php?view=upcoming" class="<?= $view==='upcoming'?'active':'' ?>">Upcoming</a>
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <form method="GET" style="display:flex;align-items:center">
            <input type="hidden" name="view" value="<?= h($view) ?>">
            <div class="meet-search">
                <span style="color:var(--text3);font-size:14px">🔍</span>
                <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search meetings…" onchange="this.form.submit()">
            </div>
        </form>
        <?php if (isManager()): ?>
        <button class="btn btn-primary" onclick="openModal('modal-meeting')">＋ New Meeting</button>
        <?php endif; ?>
    </div>
</div>

<div class="meet-stats">
    <div class="meet-stat-card">
        <div class="meet-stat-icon" style="background:rgba(99,102,241,.12)">📅</div>
        <div><div class="meet-stat-num"><?= $total ?></div><div class="meet-stat-lbl">Total</div></div>
    </div>
    <div class="meet-stat-card">
        <div class="meet-stat-icon" style="background:rgba(59,130,246,.12)">🗓️</div>
        <div><div class="meet-stat-num"><?= $upcoming_cnt ?></div><div class="meet-stat-lbl">Upcoming</div></div>
    </div>
    <div class="meet-stat-card">
        <div class="meet-stat-icon" style="background:rgba(249,115,22,.12)">⏳</div>
        <div><div class="meet-stat-num"><?= $scheduled ?></div><div class="meet-stat-lbl">Scheduled</div></div>
    </div>
    <div class="meet-stat-card">
        <div class="meet-stat-icon" style="background:rgba(16,185,129,.12)">✅</div>
        <div><div class="meet-stat-num"><?= $completed ?></div><div class="meet-stat-lbl">Completed</div></div>
    </div>
</div>

<?php if (!empty($today_meetings)): ?>
<div class="today-banner">
    <div>
        <div class="today-banner-title">📅 Today's Meetings (<?= count($today_meetings) ?>)</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php foreach ($today_meetings as $tm): ?>
            <a href="meetings.php?view=detail&mid=<?= $tm['id'] ?>" class="today-pill">
                <?= date('g:ia', strtotime($tm['start_datetime'])) ?> · <?= h(mb_substr($tm['title'],0,28)) ?>
                <?php if ($tm['meet_link']): ?><span style="font-size:10px;background:#1a73e8;color:#fff;border-radius:4px;padding:1px 5px">Meet</span><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (isManager()): ?>
<div class="instant-meet-box">
    <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap">
        <div style="flex:1;min-width:200px">
            <h3>🎥 Start an Instant Google Meet</h3>
            <p>Generate a Google Meet link instantly. The same link works for multiple sessions — paste it into any meeting.</p>
        </div>
        <div class="gm-row">
            <button class="meet-join-btn" onclick="startInstantMeet()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M15 8v8H5V8h10m2-2H3c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h14c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4V7c0-.55-.45-1-1-1z"/></svg>
                New Instant Meet
            </button>
            <button class="btn btn-ghost btn-sm" onclick="prefillMeetLink()">📋 Schedule with Link</button>
        </div>
    </div>
    <div id="instant-meet-result" style="display:none;margin-top:12px;background:var(--bg2);border:1px solid rgba(26,115,232,.3);border-radius:8px;padding:10px;align-items:center;gap:10px;flex-wrap:wrap">
        <span style="font-size:12px;color:var(--text3)">Meet link:</span>
        <a id="instant-meet-link" href="#" target="_blank" style="color:#1a73e8;font-size:13px;font-weight:600;word-break:break-all"></a>
        <button onclick="copyInstantLink(event)" class="btn btn-ghost btn-sm">Copy</button>
        <button onclick="saveMeetingFromInstant()" class="btn btn-primary btn-sm">＋ Save as Meeting</button>
    </div>
</div>
<?php endif; ?>

<div class="meet-filters">
    <a href="meetings.php?view=<?= $view ?>" class="meet-filter-pill <?= !$status_f?'active':'' ?>">All Status</a>
    <?php foreach ($STATUS_CONFIG as $sv=>$sc_cfg): ?>
    <a href="meetings.php?view=<?= $view ?>&status=<?= $sv ?><?= $search?"&q=".urlencode($search):'' ?>"
       class="meet-filter-pill <?= $status_f===$sv?'active':'' ?>"
       style="<?= $status_f===$sv?"border-color:{$sc_cfg['color']};color:{$sc_cfg['color']};background:{$sc_cfg['color']}15":'' ?>">
        <?= $sc_cfg['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<?php
$display = ($view === 'upcoming') ? $upcoming_meetings : $all_meetings;
if ($status_f) $display = array_filter($display, fn($m)=>$m['status']===$status_f);

if (empty($display)): ?>
<div class="meet-empty">
    <div class="icon">🤝</div>
    <p><?= $view==='upcoming' ? 'No upcoming meetings.' : ($search ? 'No meetings match "'.h($search).'".' : 'No meetings yet.') ?></p>
    <?php if (isManager()): ?>
    <button class="btn btn-primary" onclick="openModal('modal-meeting')" style="margin-top:14px">＋ Schedule First Meeting</button>
    <?php endif; ?>
</div>

<?php else:
    $grouped = [];
    foreach ($display as $m) { $d = substr($m['start_datetime'],0,10); $grouped[$d][] = $m; }
    if ($view === 'upcoming') ksort($grouped); else krsort($grouped);

    foreach ($grouped as $date => $meetings):
        $is_today    = ($date === date('Y-m-d'));
        $is_tomorrow = ($date === date('Y-m-d', strtotime('+1 day')));
        $is_past     = ($date < date('Y-m-d'));
        $label = $is_today ? 'Today — '.date('l, M j', strtotime($date))
               : ($is_tomorrow ? 'Tomorrow — '.date('l, M j', strtotime($date))
               : date('l, M j, Y', strtotime($date)));
?>
<div style="font-size:12.5px;font-weight:700;color:<?= $is_today?'var(--orange)':'var(--text3)' ?>;padding:14px 0 6px;border-bottom:1px solid var(--border);margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:8px">
    <?= $label ?>
    <?php if ($is_past && $view!=='upcoming'): ?>
    <span style="font-size:10px;background:var(--bg3);color:var(--text3);border-radius:4px;padding:1px 6px;text-transform:none;font-weight:500">past</span>
    <?php endif; ?>
</div>
<?php foreach ($meetings as $m):
    $mt  = $MEETING_TYPES[$m['meeting_type']] ?? $MEETING_TYPES['other'];
    $sc  = $STATUS_CONFIG[$m['status']]       ?? $STATUS_CONFIG['scheduled'];
    $dur = '';
    if ($m['end_datetime']) {
        $diff = (strtotime($m['end_datetime']) - strtotime($m['start_datetime'])) / 60;
        $dur  = $diff >= 60 ? round($diff/60,1).'h' : (int)$diff.'m';
    }
?>
<div class="meet-card" style="--mc:<?= $mt['color'] ?>" onclick="location.href='meetings.php?view=detail&mid=<?= $m['id'] ?>'">
    <div class="meet-card-time">
        <div class="meet-card-time-h"><?= date('g:ia', strtotime($m['start_datetime'])) ?></div>
        <div class="meet-card-time-d"><?= $dur ?: date('M j', strtotime($m['start_datetime'])) ?></div>
    </div>
    <div class="meet-card-body">
        <div class="meet-card-badges">
            <span class="badge" style="background:<?= $mt['color'] ?>20;color:<?= $mt['color'] ?>"><?= $mt['icon'] ?> <?= $mt['label'] ?></span>
            <span class="badge" style="background:<?= $sc['color'] ?>20;color:<?= $sc['color'] ?>"><?= $sc['label'] ?></span>
            <?php if ($m['my_rsvp'] === 'accepted'): ?>
            <span class="badge" style="background:#10b98115;color:#10b981">✓ Accepted</span>
            <?php elseif ($m['my_rsvp'] === 'declined'): ?>
            <span class="badge" style="background:#ef444415;color:#ef4444">✕ Declined</span>
            <?php endif; ?>
        </div>
        <div class="meet-card-title"><?= h($m['title']) ?></div>
        <div class="meet-card-meta">
            <?php if ($m['location']): ?><span>📍 <?= h($m['location']) ?></span><?php endif; ?>
            <?php if ($m['proj_title']): ?><span>📁 <?= h($m['proj_title']) ?></span><?php endif; ?>
            <span>👥 <?= $m['attendee_count'] ?> attendee<?= $m['attendee_count']!=1?'s':'' ?></span>
            <?php if ($m['attendee_names']): ?>
            <span><?= h(mb_substr($m['attendee_names'],0,60)).(mb_strlen($m['attendee_names'])>60?'…':'') ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end;flex-shrink:0">
        <?php if ($m['meet_link']): ?>
        <a href="<?= h($m['meet_link']) ?>" target="_blank" class="meet-join-btn" onclick="event.stopPropagation()" style="padding:5px 10px;font-size:11.5px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M15 8v8H5V8h10m2-2H3c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h14c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4V7c0-.55-.45-1-1-1z"/></svg>
            Join
        </a>
        <?php endif; ?>
        <?php if (isManager()): ?>
        <a href="meetings.php?edit=<?= $m['id'] ?>" onclick="event.stopPropagation()" class="btn btn-ghost btn-sm" style="font-size:11px">Edit</a>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endforeach; ?>
<?php endif; ?>

<?php endif; ?>

<!-- ══ MODAL ══ -->
<div class="modal-overlay <?= $edit_id?'open':'' ?>" id="modal-meeting">
    <div class="modal" style="max-width:640px">
        <div class="modal-header">
            <div class="modal-title"><?= $edit_id ? 'Edit Meeting' : '＋ Schedule Meeting' ?></div>
            <button class="modal-close" onclick="closeModal('modal-meeting');<?= $edit_id?"location.href='meetings.php'":'' ?>">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save_meeting">
            <input type="hidden" name="mid" value="<?= $edit_id ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required
                        value="<?= h($edit_meeting['title'] ?? '') ?>"
                        placeholder="e.g. Q3 Planning Session">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Meeting Type</label>
                        <select name="meeting_type" class="form-control">
                            <?php foreach ($MEETING_TYPES as $tk=>$tv): ?>
                            <option value="<?= $tk ?>" <?= ($edit_meeting['meeting_type']??'internal')===$tk?'selected':'' ?>><?= $tv['icon'] ?> <?= $tv['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <?php foreach ($STATUS_CONFIG as $sv=>$sc_cfg): ?>
                            <option value="<?= $sv ?>" <?= ($edit_meeting['status']??'scheduled')===$sv?'selected':'' ?>><?= $sc_cfg['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start *</label>
                        <input type="datetime-local" name="start_datetime" class="form-control" id="meet-start" required
                            value="<?= h($edit_meeting ? date('Y-m-d\TH:i', strtotime($edit_meeting['start_datetime'])) : date('Y-m-d\T').'09:00') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End</label>
                        <input type="datetime-local" name="end_datetime" class="form-control" id="meet-end"
                            value="<?= h($edit_meeting && $edit_meeting['end_datetime'] ? date('Y-m-d\TH:i', strtotime($edit_meeting['end_datetime'])) : '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Location / Room</label>
                        <input type="text" name="location" class="form-control"
                            value="<?= h($edit_meeting['location'] ?? '') ?>"
                            placeholder="Conference Room, Online, etc.">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Link to Project</label>
                        <select name="project_id" class="form-control">
                            <option value="0">— None —</option>
                            <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($edit_meeting['project_id']??0)==$p['id']?'selected':'' ?>><?= h($p['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="display:flex;align-items:center;justify-content:space-between">
                        <span>Google Meet Link</span>
                        <button type="button" onclick="generateMeetLink()" class="btn btn-ghost btn-sm" style="font-size:11px">🎥 Generate Link</button>
                    </label>
                    <input type="url" name="meet_link" id="meet-link-input" class="form-control"
                        value="<?= h($edit_meeting['meet_link'] ?? '') ?>"
                        placeholder="https://meet.google.com/xxx-xxxx-xxx">
                    <div style="font-size:11.5px;color:var(--text3);margin-top:4px">
                        ℹ️ The same Google Meet link can be reused across multiple sessions.
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Agenda</label>
                    <textarea name="agenda" class="form-control" style="min-height:80px"
                        placeholder="Meeting agenda, topics, or notes…"><?= h($edit_meeting['agenda'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Invite Team Members</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:10px;max-height:160px;overflow-y:auto">
                        <?php foreach ($all_users as $u): ?>
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2);cursor:pointer;padding:3px">
                            <input type="checkbox" name="attendees[]" value="<?= $u['id'] ?>"
                                <?= (in_array($u['id'],$edit_att_ids) || $u['id']==$uid) ? 'checked' : '' ?>
                                style="accent-color:var(--orange)">
                            <?= h($u['name']) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-meeting');<?= $edit_id?"location.href='meetings.php'":'' ?>">Cancel</button>
                <button type="submit" class="btn btn-primary"><?= $edit_id ? 'Save Changes' : 'Schedule Meeting' ?></button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('meet-start')?.addEventListener('change', function() {
    var end = document.getElementById('meet-end');
    if (end && !end.value) {
        var d = new Date(this.value);
        d.setHours(d.getHours() + 1);
        end.value = d.toISOString().slice(0,16);
    }
});

function generateMeetLink() {
    var c = 'abcdefghijklmnopqrstuvwxyz';
    var r = function(n){ var s=''; for(var i=0;i<n;i++) s+=c[Math.floor(Math.random()*c.length)]; return s; };
    document.getElementById('meet-link-input').value = 'https://meet.google.com/'+r(3)+'-'+r(4)+'-'+r(3);
}

var _instantLink = '';

function startInstantMeet() {
    var c = 'abcdefghijklmnopqrstuvwxyz';
    var r = function(n){ var s=''; for(var i=0;i<n;i++) s+=c[Math.floor(Math.random()*c.length)]; return s; };
    _instantLink = 'https://meet.google.com/'+r(3)+'-'+r(4)+'-'+r(3);
    var res = document.getElementById('instant-meet-result');
    var lnk = document.getElementById('instant-meet-link');
    lnk.href = _instantLink;
    lnk.textContent = _instantLink;
    res.style.display = 'flex';
    window.open(_instantLink, '_blank');
}

function copyInstantLink(e) {
    if (_instantLink) {
        navigator.clipboard.writeText(_instantLink).catch(function(){});
        var btn = e.target;
        btn.textContent = '✓ Copied!';
        setTimeout(function(){ btn.textContent = 'Copy'; }, 2000);
    }
}

function prefillMeetLink() {
    generateMeetLink();
    openModal('modal-meeting');
}

function saveMeetingFromInstant() {
    if (_instantLink) {
        document.getElementById('meet-link-input').value = _instantLink;
        openModal('modal-meeting');
    }
}

<?php if ($edit_id): ?>
document.addEventListener('DOMContentLoaded', function(){ openModal('modal-meeting'); });
<?php endif; ?>
</script>

<?php renderLayoutEnd(); ?>