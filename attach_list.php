<?php
/**
 * attach_list.php — Returns attachments for a given entity as JSON
 */
require_once 'config.php';
requireLogin();
$db = getCRMDB();

header('Content-Type: application/json');

$entity = $_GET['entity']    ?? '';
$eid    = (int)($_GET['id'] ?? 0);

if (!$entity || !$eid) { echo json_encode([]); exit; }

$col_map = [
    'task'    => 'task_id',
    'project' => 'project_id',
    'contact' => 'contact_id',
    'lead'    => 'lead_id',
];
$col = $col_map[$entity] ?? null;
if (!$col) { echo json_encode([]); exit; }

$rows = $db->query("
    SELECT d.id, d.original_name, d.file_size, d.file_type, d.created_at,
           u.name AS uploader
    FROM documents d
    LEFT JOIN users u ON u.id = d.uploaded_by
    WHERE d.$col = $eid AND d.category = 'Attachment'
    ORDER BY d.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$out = [];
foreach ($rows as $r) {
    $ext = strtolower(pathinfo($r['original_name'], PATHINFO_EXTENSION));
    $out[] = [
        'id'   => (int)$r['id'],
        'name' => $r['original_name'],
        'size' => (int)$r['file_size'],
        'ext'  => $ext,
        'url'  => 'documents.php?download='.$r['id'],
        'by'   => $r['uploader'] ?? 'Unknown',
        'date' => date('M j, g:ia', strtotime($r['created_at'])),
    ];
}
echo json_encode($out);