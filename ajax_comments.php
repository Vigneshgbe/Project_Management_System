<?php
require_once 'config.php';
requireLogin();
$db = getCRMDB();
$user = currentUser();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = (int)($_POST['task_id'] ?? 0);
    $cmt = trim($_POST['comment'] ?? '');
    $uid = $user['id'];
    if ($tid && $cmt) {
        $stmt = $db->prepare("INSERT INTO task_comments (task_id,user_id,comment) VALUES (?,?,?)");
        $stmt->bind_param("iis",$tid,$uid,$cmt);
        $stmt->execute();
        echo json_encode(['ok'=>true,'id'=>$db->insert_id]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

$tid = (int)($_GET['task_id'] ?? 0);
if ($tid) {
    $rows = $db->query("SELECT tc.*,u.name FROM task_comments tc JOIN users u ON u.id=tc.user_id WHERE tc.task_id=$tid ORDER BY tc.created_at")->fetch_all(MYSQLI_ASSOC);
    $out = array_map(fn($r)=>['name'=>$r['name'],'comment'=>$r['comment'],'date'=>date('M j, g:ia',strtotime($r['created_at']))],$rows);
    echo json_encode($out);
} else {
    echo json_encode([]);
}