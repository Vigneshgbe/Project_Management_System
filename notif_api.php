<?php
/**
 * notif_api.php — Unified Notifications API
 * Actions: list, count, mark_read, mark_all, delete
 */
require_once 'config.php';
requireLogin();
$db  = getCRMDB();
$uid = (int)currentUser()['id'];

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? 'list';

switch ($action) {

    // ── COUNT unread (used by poll) ──
    case 'count':
        $r = $db->query("SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc();
        echo json_encode(['count' => (int)$r['c']]);
        break;

    // ── LIST notifications (paginated, filterable) ──
    case 'list':
        $filter  = $_GET['filter'] ?? 'all';   // all | unread | task | project | lead | invoice | message
        $limit   = min((int)($_GET['limit'] ?? 30), 100);
        $offset  = (int)($_GET['offset'] ?? 0);

        $where = "user_id=$uid";
        if ($filter === 'unread') $where .= " AND is_read=0";
        elseif ($filter === 'task')    $where .= " AND entity_type='task'";
        elseif ($filter === 'project') $where .= " AND entity_type='project'";
        elseif ($filter === 'lead')    $where .= " AND entity_type='lead'";
        elseif ($filter === 'invoice') $where .= " AND entity_type='invoice'";
        elseif ($filter === 'message') $where .= " AND entity_type='message'";

        $rows = $db->query("
            SELECT id, type, entity_type, entity_id, title, body, link, is_read,
                   created_at,
                   TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_sec
            FROM notifications
            WHERE $where
            ORDER BY created_at DESC
            LIMIT $limit OFFSET $offset
        ")->fetch_all(MYSQLI_ASSOC);

        $unread = (int)$db->query("SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc()['c'];
        $total  = (int)$db->query("SELECT COUNT(*) AS c FROM notifications WHERE $where")->fetch_assoc()['c'];

        // Format age for display
        foreach ($rows as &$r) {
            $r['id']      = (int)$r['id'];
            $r['is_read'] = (bool)(int)$r['is_read'];
            $r['age']     = fmtAge((int)$r['age_sec']);
        }
        unset($r);

        echo json_encode(['ok'=>true,'notifications'=>$rows,'unread'=>$unread,'total'=>$total]);
        break;

    // ── MARK ONE as read + redirect ──
    case 'mark_read':
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id) $db->query("UPDATE notifications SET is_read=1 WHERE id=$id AND user_id=$uid");
        echo json_encode(['ok'=>true]);
        break;

    // ── MARK ALL as read ──
    case 'mark_all':
        $filter = $_POST['filter'] ?? 'all';
        $where  = "user_id=$uid AND is_read=0";
        if ($filter !== 'all') $where .= " AND entity_type='".$db->real_escape_string($filter)."'";
        $db->query("UPDATE notifications SET is_read=1 WHERE $where");
        $remaining = (int)$db->query("SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc()['c'];
        echo json_encode(['ok'=>true,'unread'=>$remaining]);
        break;

    // ── DELETE one ──
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id) $db->query("DELETE FROM notifications WHERE id=$id AND user_id=$uid");
        echo json_encode(['ok'=>true]);
        break;

    // ── DELETE ALL read ──
    case 'clear_read':
        $db->query("DELETE FROM notifications WHERE user_id=$uid AND is_read=1");
        echo json_encode(['ok'=>true]);
        break;

    default:
        echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}

function fmtAge(int $sec): string {
    if ($sec <  60)   return 'just now';
    if ($sec <  3600) return floor($sec/60).'m ago';
    if ($sec <  86400)return floor($sec/3600).'h ago';
    if ($sec <  604800)return floor($sec/86400).'d ago';
    return date('M j', time()-$sec);
}