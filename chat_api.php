<?php
/**
 * chat_api.php — Padak CRM Communication Hub API
 * All responses: JSON
 */
require_once 'config.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

// ── helpers ──
function jsonOut(array $d): void { echo json_encode($d); exit; }
function err(string $m): void    { jsonOut(['ok'=>false,'error'=>$m]); }
function ok(array $d=[]): void   { jsonOut(array_merge(['ok'=>true],$d)); }

// ── parse @mentions from body ──
function parseMentions(string $body, mysqli $db): array {
    preg_match_all('/@(\w+(?:\s\w+)?)/u', $body, $m);
    $names = $m[1] ?? [];
    if (!$names) return [];
    $ids = [];
    foreach ($names as $n) {
        $n = $db->real_escape_string($n);
        $r = $db->query("SELECT id FROM users WHERE name LIKE '%$n%' AND status='active' LIMIT 1")->fetch_assoc();
        if ($r) $ids[] = (int)$r['id'];
    }
    return array_unique($ids);
}

// ── ensure user is member of channel ──
function isMember(int $channel_id, int $uid, mysqli $db): bool {
    return (bool)$db->query("SELECT 1 FROM chat_members WHERE channel_id=$channel_id AND user_id=$uid")->fetch_assoc();
}

// ── format message for output ──
function fmtMsg(array $r, int $me): array {
    return [
        'id'        => (int)$r['id'],
        'channel_id'=> (int)$r['channel_id'],
        'user_id'   => (int)$r['user_id'],
        'user_name' => $r['user_name'],
        'user_init' => strtoupper(substr($r['user_name'],0,1)),
        'parent_id' => $r['parent_id'] ? (int)$r['parent_id'] : null,
        'body'      => $r['deleted'] ? '_Message deleted_' : $r['body'],
        'body_html' => $r['deleted'] ? '<em style="opacity:.5">Message deleted</em>' : renderBody($r['body']),
        'file_url'  => $r['file_url'],
        'file_name' => $r['file_name'],
        'file_size' => $r['file_size'] ? (int)$r['file_size'] : null,
        'edited'    => (bool)$r['edited'],
        'deleted'   => (bool)$r['deleted'],
        'mine'      => ($r['user_id'] == $me),
        'time'      => date('g:ia', strtotime($r['created_at'])),
        'date'      => date('M j', strtotime($r['created_at'])),
        'iso'       => $r['created_at'],
        'reactions' => json_decode($r['reactions'] ?? '[]', true) ?: [],
        'reply_count'=> (int)($r['reply_count'] ?? 0),
    ];
}

// ── render markdown-lite body with @mentions ──
function renderBody(string $body): string {
    $body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
    // Bold **text**
    $body = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $body);
    // Italic *text*
    $body = preg_replace('/(?<!\*)\*([^*]+?)\*(?!\*)/s', '<em>$1</em>', $body);
    // Inline code `text`
    $body = preg_replace('/`([^`]+?)`/', '<code style="background:var(--bg4);padding:1px 5px;border-radius:3px;font-size:.9em">$1</code>', $body);
    // @mentions
    $body = preg_replace('/@([\w\s]+?)(?=\s|$|[^\w\s])/u', '<span style="color:var(--orange);font-weight:600">@$1</span>', $body);
    // URLs
    $body = preg_replace('/(https?:\/\/[^\s<]+)/i', '<a href="$1" target="_blank" style="color:var(--orange);text-decoration:underline">$1</a>', $body);
    // Newlines
    return nl2br($body);
}

switch ($action) {

    // ══ GET CHANNELS (sidebar list) ══
    case 'get_channels':
        $rows = $db->query("
            SELECT cc.*, 
                CASE WHEN cc.type='direct' THEN (
                    SELECT u.name FROM chat_members cm2
                    JOIN users u ON u.id=cm2.user_id
                    WHERE cm2.channel_id=cc.id AND cm2.user_id!=$uid LIMIT 1
                ) ELSE cc.name END AS display_name,
                (SELECT COUNT(*) FROM chat_messages cm
                 WHERE cm.channel_id=cc.id AND cm.deleted=0
                 AND cm.created_at > COALESCE(
                     (SELECT last_read FROM chat_members WHERE channel_id=cc.id AND user_id=$uid),
                     '2000-01-01'
                 )) AS unread,
                (SELECT cm.body FROM chat_messages cm WHERE cm.channel_id=cc.id AND cm.deleted=0 ORDER BY cm.created_at DESC LIMIT 1) AS last_body,
                (SELECT cm.created_at FROM chat_messages cm WHERE cm.channel_id=cc.id AND cm.deleted=0 ORDER BY cm.created_at DESC LIMIT 1) AS last_at
            FROM chat_channels cc
            JOIN chat_members mb ON mb.channel_id=cc.id AND mb.user_id=$uid
            ORDER BY COALESCE(last_at,'2000-01-01') DESC
        ")->fetch_all(MYSQLI_ASSOC);
        ok(['channels' => $rows]);
        break;

    // ══ GET MESSAGES ══
    case 'get_messages':
        $cid    = (int)($_GET['channel_id'] ?? 0);
        $before = (int)($_GET['before'] ?? 0);  // pagination
        $thread = (int)($_GET['thread_of'] ?? 0); // thread view
        if (!$cid || !isMember($cid, $uid, $db)) err('Access denied');

        $where = $thread
            ? "m.channel_id=$cid AND m.parent_id=$thread AND m.deleted=0"
            : "m.channel_id=$cid AND m.parent_id IS NULL AND m.deleted=0";
        if ($before) $where .= " AND m.id < $before";

        $rows = $db->query("
            SELECT m.*, u.name AS user_name,
                (SELECT COUNT(*) FROM chat_messages r WHERE r.parent_id=m.id AND r.deleted=0) AS reply_count,
                (SELECT JSON_ARRAYAGG(JSON_OBJECT('emoji',emoji,'cnt',cnt,'mine',mine)) FROM (
                    SELECT cr.emoji,COUNT(*) AS cnt,
                        MAX(IF(cr.user_id=$uid,1,0)) AS mine
                    FROM chat_reactions cr WHERE cr.message_id=m.id
                    GROUP BY cr.emoji
                ) rx) AS reactions
            FROM chat_messages m
            JOIN users u ON u.id=m.user_id
            WHERE $where
            ORDER BY m.created_at DESC LIMIT 40
        ")->fetch_all(MYSQLI_ASSOC);

        // Mark as read
        $db->query("UPDATE chat_members SET last_read=NOW() WHERE channel_id=$cid AND user_id=$uid");

        ok(['messages' => array_map(fn($r)=>fmtMsg($r,$uid), array_reverse($rows))]);
        break;

    // ══ SEND MESSAGE ══
    case 'send':
        $cid      = (int)($_POST['channel_id'] ?? 0);
        $body     = trim($_POST['body'] ?? '');
        $parent   = (int)($_POST['parent_id'] ?? 0) ?: null;
        $file_url = null; $file_name = null; $file_size = null;

        if (!$cid || !isMember($cid, $uid, $db)) err('Access denied');
        if ($body === '' && empty($_FILES['file']['name'])) err('Empty message');

        // File upload
        if (!empty($_FILES['file']['name'])) {
            $f   = $_FILES['file'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $ok_types = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','txt','zip'];
            if (!in_array($ext, $ok_types)) err('File type not allowed');
            if ($f['size'] > 10*1024*1024) err('Max file size 10MB');
            $fname = 'chat_'.uniqid().'.'.$ext;
            $dir   = __DIR__.'/uploads/chat/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (!move_uploaded_file($f['tmp_name'], $dir.$fname)) err('Upload failed');
            $file_url  = 'uploads/chat/'.$fname;
            $file_name = basename($f['name']);
            $file_size = $f['size'];
            if ($body === '') $body = $file_name;
        }

        $stmt = $db->prepare("INSERT INTO chat_messages (channel_id,user_id,parent_id,body,file_url,file_name,file_size) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("iiisssi",$cid,$uid,$parent,$body,$file_url,$file_name,$file_size);
        $stmt->execute();
        $mid = $db->insert_id;

        // Log @mentions in activity
        $mentions = parseMentions($body, $db);
        if ($mentions) {
            foreach ($mentions as $muid) {
                $db->query("INSERT IGNORE INTO activity_log (user_id,action,entity_type,entity_id,details) VALUES ($uid,'mentioned','chat_message',$mid,'User $muid')");
            }
        }

        $row = $db->query("
            SELECT m.*, u.name AS user_name, 0 AS reply_count, NULL AS reactions
            FROM chat_messages m JOIN users u ON u.id=m.user_id WHERE m.id=$mid
        ")->fetch_assoc();
        ok(['message' => fmtMsg($row, $uid)]);
        break;

    // ══ EDIT MESSAGE ══
    case 'edit':
        $mid  = (int)($_POST['id'] ?? 0);
        $body = trim($_POST['body'] ?? '');
        if (!$body) err('Empty');
        $row = $db->query("SELECT user_id FROM chat_messages WHERE id=$mid")->fetch_assoc();
        if (!$row || $row['user_id'] != $uid) err('Not yours');
        $db->query("UPDATE chat_messages SET body='".($db->real_escape_string($body))."',edited=1 WHERE id=$mid");
        ok();
        break;

    // ══ DELETE MESSAGE ══
    case 'delete':
        $mid = (int)($_POST['id'] ?? 0);
        $row = $db->query("SELECT user_id,channel_id FROM chat_messages WHERE id=$mid")->fetch_assoc();
        if (!$row) err('Not found');
        $chan_owner = $db->query("SELECT created_by FROM chat_channels WHERE id={$row['channel_id']}")->fetch_assoc();
        if ($row['user_id'] != $uid && !isManager()) err('Not allowed');
        $db->query("UPDATE chat_messages SET deleted=1 WHERE id=$mid");
        ok();
        break;

    // ══ REACT ══
    case 'react':
        $mid   = (int)($_POST['id'] ?? 0);
        $emoji = trim($_POST['emoji'] ?? '');
        if (!$mid || !$emoji) err('Invalid');
        // Toggle
        $ex = $db->query("SELECT id FROM chat_reactions WHERE message_id=$mid AND user_id=$uid AND emoji='".$db->real_escape_string($emoji)."'")->fetch_assoc();
        if ($ex) {
            $db->query("DELETE FROM chat_reactions WHERE id={$ex['id']}");
        } else {
            $db->query("INSERT INTO chat_reactions (message_id,user_id,emoji) VALUES ($mid,$uid,'".$db->real_escape_string($emoji)."')");
        }
        ok();
        break;

    // ══ CREATE DM ══
    case 'create_dm':
        $target = (int)($_POST['user_id'] ?? 0);
        if (!$target || $target == $uid) err('Invalid user');
        // Check existing
        $ex = $db->query("
            SELECT cc.id FROM chat_channels cc
            WHERE cc.type='direct'
            AND (SELECT COUNT(*) FROM chat_members WHERE channel_id=cc.id AND user_id=$uid)=1
            AND (SELECT COUNT(*) FROM chat_members WHERE channel_id=cc.id AND user_id=$target)=1
            AND (SELECT COUNT(*) FROM chat_members WHERE channel_id=cc.id)=2
            LIMIT 1
        ")->fetch_assoc();
        if ($ex) { ok(['channel_id'=>$ex['id']]); }
        $db->query("INSERT INTO chat_channels (type,created_by) VALUES ('direct',$uid)");
        $cid = $db->insert_id;
        $db->query("INSERT INTO chat_members (channel_id,user_id) VALUES ($cid,$uid),($cid,$target)");
        ok(['channel_id'=>$cid]);
        break;

    // ══ CREATE CHANNEL ══
    case 'create_channel':
        if (!isManager()) err('Managers only');
        $name    = trim($_POST['name'] ?? '');
        $members = $_POST['members'] ?? [];
        if (!$name) err('Name required');
        $name_esc = $db->real_escape_string(strtolower(preg_replace('/[^a-z0-9\-]/i','-',$name)));
        $db->query("INSERT INTO chat_channels (type,name,created_by) VALUES ('general','$name_esc',$uid)");
        $cid = $db->insert_id;
        $members[] = $uid;
        foreach (array_unique(array_map('intval',$members)) as $m) {
            $db->query("INSERT IGNORE INTO chat_members (channel_id,user_id) VALUES ($cid,$m)");
        }
        ok(['channel_id'=>$cid,'name'=>$name_esc]);
        break;

    // ══ GET USERS (for DM / @mention autocomplete) ══
    case 'get_users':
        $q = $db->real_escape_string(trim($_GET['q'] ?? ''));
        $rows = $db->query("SELECT id,name FROM users WHERE status='active' AND name LIKE '%$q%' AND id!=$uid ORDER BY name LIMIT 10")->fetch_all(MYSQLI_ASSOC);
        ok(['users'=>$rows]);
        break;

    // ══ POLL (unread counts + new messages) ══
    case 'poll':
        $cid   = (int)($_GET['channel_id'] ?? 0);
        $since = $_GET['since'] ?? date('Y-m-d H:i:s', strtotime('-5 seconds'));
        if (!$cid || !isMember($cid,$uid,$db)) err('Access denied');
        $since_esc = $db->real_escape_string($since);
        $rows = $db->query("
            SELECT m.*, u.name AS user_name, 0 AS reply_count, NULL AS reactions
            FROM chat_messages m JOIN users u ON u.id=m.user_id
            WHERE m.channel_id=$cid AND m.created_at > '$since_esc' AND m.deleted=0 AND m.parent_id IS NULL
            ORDER BY m.created_at ASC
        ")->fetch_all(MYSQLI_ASSOC);
        $unread = $db->query("
            SELECT cc.id, COUNT(cm.id) AS cnt
            FROM chat_channels cc
            JOIN chat_members mb ON mb.channel_id=cc.id AND mb.user_id=$uid
            LEFT JOIN chat_messages cm ON cm.channel_id=cc.id AND cm.deleted=0
                AND cm.created_at > COALESCE(mb.last_read,'2000-01-01')
            GROUP BY cc.id
        ")->fetch_all(MYSQLI_ASSOC);
        ok([
            'messages' => array_map(fn($r)=>fmtMsg($r,$uid), $rows),
            'unread'   => array_column($unread,'cnt','id'),
            'server_time' => date('Y-m-d H:i:s'),
        ]);
        break;

    default:
        err('Unknown action');
}