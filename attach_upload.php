<?php
/**
 * attach_upload.php — Shared file attachment handler
 * Handles upload + delete for tasks, projects, contacts, leads
 * Uses the existing documents table + upload directory
 */
require_once 'config.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── UPLOAD ──
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $entity = $_POST['entity'] ?? '';   // task | project | contact | lead
    $eid    = (int)($_POST['entity_id'] ?? 0);

    if (!$entity || !$eid) { echo json_encode(['ok'=>false,'error'=>'Missing entity']); exit; }

    $allowed_entities = ['task','project','contact','lead'];
    if (!in_array($entity, $allowed_entities)) { echo json_encode(['ok'=>false,'error'=>'Invalid entity']); exit; }

    if (empty($_FILES['file']['name'])) { echo json_encode(['ok'=>false,'error'=>'No file selected']); exit; }

    $f    = $_FILES['file'];
    $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_DOC_TYPES)) {
        echo json_encode(['ok'=>false,'error'=>'File type not allowed. Allowed: '.implode(', ', ALLOWED_DOC_TYPES)]);
        exit;
    }
    if ($f['size'] > MAX_FILE_SIZE) {
        echo json_encode(['ok'=>false,'error'=>'File too large (max 20MB)']);
        exit;
    }

    if (!is_dir(UPLOAD_DOC_DIR)) mkdir(UPLOAD_DOC_DIR, 0755, true);

    $fname = uniqid('att_', true).'.'.$ext;
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DOC_DIR.$fname)) {
        echo json_encode(['ok'=>false,'error'=>'Upload failed']); exit;
    }

    $oname    = basename($f['name']);
    $fsize    = (int)$f['size'];
    $ftype    = $f['type'];
    $title    = $oname;  // use filename as title
    $category = 'Attachment';
    $access   = 'all';

    // Map entity to column
    $proj_id = $cont_id = $task_id = $lead_id = null;
    if ($entity === 'project') $proj_id = $eid;
    if ($entity === 'contact') $cont_id = $eid;
    if ($entity === 'task')    $task_id = $eid;
    if ($entity === 'lead')    $lead_id = $eid;

    $stmt = $db->prepare("INSERT INTO documents
        (title,description,filename,original_name,file_size,file_type,project_id,contact_id,task_id,lead_id,category,access,uploaded_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssissiiiisi",
        $title, $category, $fname, $oname, $fsize, $ftype,
        $proj_id, $cont_id, $task_id, $lead_id, $category, $access, $uid);
    $stmt->execute();
    $doc_id = $db->insert_id;

    logActivity('attached file', $oname, $doc_id);

    // Return the new attachment row for JS to render immediately
    echo json_encode([
        'ok'    => true,
        'id'    => $doc_id,
        'name'  => $oname,
        'size'  => $fsize,
        'ext'   => $ext,
        'url'   => 'documents.php?download='.$doc_id,
        'by'    => $user['name'],
        'date'  => date('M j, g:ia'),
    ]);
    exit;
}

// ── DELETE ──
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id  = (int)($_POST['id'] ?? 0);
    $doc = $db->query("SELECT * FROM documents WHERE id=$id")->fetch_assoc();
    if (!$doc) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }
    // Only uploader or manager can delete
    if ($doc['uploaded_by'] != $uid && !isManager()) {
        echo json_encode(['ok'=>false,'error'=>'Permission denied']); exit;
    }
    @unlink(UPLOAD_DOC_DIR.$doc['filename']);
    $db->query("DELETE FROM documents WHERE id=$id");
    logActivity('deleted attachment', $doc['original_name'], $id);
    echo json_encode(['ok'=>true]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Unknown action']);