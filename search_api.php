<?php
/**
 * search_api.php — Padak CRM Advanced Search API
 * All responses: JSON
 */
require_once 'config.php';
requireLogin();
$db  = getCRMDB();
$uid = (int)currentUser()['id'];

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? 'suggest';
$q      = trim($_REQUEST['q'] ?? '');
$type   = $_REQUEST['type'] ?? 'all';

function jout(array $d): void { echo json_encode($d); exit; }

// ── Sanitize for LIKE
function like(string $s, mysqli $db): string {
    return '%'.$db->real_escape_string($s).'%';
}

// ── Fulltext search helper (falls back to LIKE if FT unavailable)
function ftSearch(mysqli $db, string $table, string $cols, string $q, string $extra_where, int $limit, array $select_cols): array {
    $qe = $db->real_escape_string($q);
    // Try FULLTEXT first
    $cols_arr = array_map('trim', explode(',', $cols));
    $like_parts = array_map(fn($c)=>"$c LIKE '%$qe%'", $cols_arr);
    $like_where = '('.implode(' OR ', $like_parts).')';
    $where = $extra_where ? "($extra_where) AND $like_where" : $like_where;
    $sel = implode(',', $select_cols);
    $rows = $db->query("SELECT $sel FROM $table WHERE $where ORDER BY updated_at DESC LIMIT $limit");
    if (!$rows) return [];
    return $rows->fetch_all(MYSQLI_ASSOC);
}

switch ($action) {

    // ══ QUICK SUGGESTIONS (header search dropdown) ══
    case 'suggest':
        if (strlen($q) < 2) jout(['suggestions'=>[]]);
        $results = [];
        $qe = $db->real_escape_string($q);
        $lq = '%'.$qe.'%';

        // Projects
        $rows = $db->query("SELECT id,'project' AS type,title AS label,status AS sub FROM projects WHERE (title LIKE '$lq' OR description LIKE '$lq') AND status NOT IN('cancelled') LIMIT 4")->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $r) $results[] = ['type'=>'project','icon'=>'📁','label'=>$r['label'],'sub'=>ucfirst(str_replace('_',' ',$r['sub'])),'id'=>$r['id'],'url'=>'projects.php?view='.$r['id']];

        // Tasks
        $rows = $db->query("SELECT id,'task' AS type,title AS label,status AS sub FROM tasks WHERE (title LIKE '$lq' OR description LIKE '$lq') AND status!='done' LIMIT 4")->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $r) $results[] = ['type'=>'task','icon'=>'✅','label'=>$r['label'],'sub'=>ucfirst(str_replace('_',' ',$r['sub'])),'id'=>$r['id'],'url'=>'tasks.php?edit='.$r['id']];

        // Contacts
        $rows = $db->query("SELECT id,name AS label,company AS sub FROM contacts WHERE name LIKE '$lq' OR company LIKE '$lq' OR email LIKE '$lq' LIMIT 3")->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $r) $results[] = ['type'=>'contact','icon'=>'👥','label'=>$r['label'],'sub'=>$r['sub']??'Contact','id'=>$r['id'],'url'=>'contacts.php?edit='.$r['id']];

        // Documents
        $rows = $db->query("SELECT id,title AS label,'Document' AS sub FROM documents WHERE title LIKE '$lq' OR original_name LIKE '$lq' LIMIT 3")->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $r) $results[] = ['type'=>'document','icon'=>'📄','label'=>$r['label'],'sub'=>$r['sub'],'id'=>$r['id'],'url'=>'documents.php?q='.urlencode($q)];

        // Leads
        $rows = $db->query("SELECT id,name AS label,company AS sub FROM leads WHERE name LIKE '$lq' OR company LIKE '$lq' LIMIT 3")->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $r) $results[] = ['type'=>'lead','icon'=>'🎯','label'=>$r['label'],'sub'=>$r['sub']??'Lead','id'=>$r['id'],'url'=>'leads.php?view='.$r['id']];

        // People
        $rows = $db->query("SELECT id,name AS label,department AS sub FROM users WHERE (name LIKE '$lq' OR email LIKE '$lq') AND status='active' LIMIT 2")->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $r) $results[] = ['type'=>'user','icon'=>'👤','label'=>$r['label'],'sub'=>$r['sub']??'Team member','id'=>$r['id'],'url'=>'users.php'];

        jout(['suggestions'=>array_slice($results,0,12),'query'=>$q]);
        break;

    // ══ FULL SEARCH (search.php results) ══
    case 'search':
        if ($q === '' && $type === 'all') jout(['results'=>[],'total'=>0]);

        $qe      = $db->real_escape_string($q);
        $lq      = '%'.$qe.'%';
        $results = [];

        // Extra filters
        $status_f   = $_REQUEST['status'] ?? '';
        $assignee_f = (int)($_REQUEST['assignee'] ?? 0);
        $date_from  = $_REQUEST['date_from'] ?? '';
        $date_to    = $_REQUEST['date_to']   ?? '';
        $priority_f = $_REQUEST['priority']  ?? '';

        // ── Projects ──
        if ($type === 'all' || $type === 'projects') {
            $w = $q ? "(title LIKE '$lq' OR description LIKE '$lq')" : "1=1";
            if ($status_f)   $w .= " AND status='".$db->real_escape_string($status_f)."'";
            if ($date_from)  $w .= " AND due_date >= '".$db->real_escape_string($date_from)."'";
            if ($date_to)    $w .= " AND due_date <= '".$db->real_escape_string($date_to)."'";
            $rows = $db->query("SELECT id,title,description,status,priority,due_date,updated_at,'project' AS _type FROM projects WHERE $w ORDER BY updated_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as $r) {
                $r['icon']='📁'; $r['url']='projects.php?view='.$r['id'];
                $r['snippet'] = $r['description'] ? mb_substr(strip_tags($r['description']),0,120) : '';
                $r['badge']   = ucfirst(str_replace('_',' ',$r['status']));
                $r['badge_color'] = '#10b981';
                $results[] = $r;
            }
        }

        // ── Tasks ──
        if ($type === 'all' || $type === 'tasks') {
            $w = $q ? "(t.title LIKE '$lq' OR t.description LIKE '$lq')" : "1=1";
            if ($status_f)   $w .= " AND t.status='".$db->real_escape_string($status_f)."'";
            if ($priority_f) $w .= " AND t.priority='".$db->real_escape_string($priority_f)."'";
            if ($assignee_f) $w .= " AND t.assigned_to=$assignee_f";
            if ($date_from)  $w .= " AND t.due_date >= '".$db->real_escape_string($date_from)."'";
            if ($date_to)    $w .= " AND t.due_date <= '".$db->real_escape_string($date_to)."'";
            $rows = $db->query("
                SELECT t.id,t.title,t.description,t.status,t.priority,t.due_date,t.updated_at,
                    u.name AS assignee_name, p.title AS project_name,'task' AS _type
                FROM tasks t
                LEFT JOIN users u ON u.id=t.assigned_to
                LEFT JOIN projects p ON p.id=t.project_id
                WHERE $w ORDER BY t.updated_at DESC LIMIT 20
            ")->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as $r) {
                $r['icon']='✅'; $r['url']='tasks.php?edit='.$r['id'];
                $r['snippet'] = $r['assignee_name'] ? 'Assigned to '.$r['assignee_name'] : '';
                if ($r['project_name']) $r['snippet'] .= ($r['snippet']?' · ':'').'📁 '.$r['project_name'];
                $r['badge']   = ucfirst(str_replace('_',' ',$r['status']));
                $r['badge_color'] = ['done'=>'#10b981','in_progress'=>'#f59e0b','review'=>'#8b5cf6','todo'=>'#6366f1'][$r['status']]??'#94a3b8';
                $results[] = $r;
            }
        }

        // ── Contacts ──
        if ($type === 'all' || $type === 'contacts') {
            $w = $q ? "(name LIKE '$lq' OR company LIKE '$lq' OR email LIKE '$lq' OR phone LIKE '$lq' OR notes LIKE '$lq')" : "1=1";
            if ($status_f) $w .= " AND status='".$db->real_escape_string($status_f)."'";
            $rows = $db->query("SELECT id,name AS title,company AS description,email,phone,type AS status,updated_at,'contact' AS _type FROM contacts WHERE $w ORDER BY updated_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as $r) {
                $r['icon']='👥'; $r['url']='contacts.php?edit='.$r['id'];
                $r['snippet'] = trim(($r['email']??'').($r['phone']?' · '.$r['phone']:''));
                $r['badge']   = ucfirst($r['status']);
                $r['badge_color']='#f97316';
                $results[] = $r;
            }
        }

        // ── Documents ──
        if ($type === 'all' || $type === 'documents') {
            $w = $q ? "(d.title LIKE '$lq' OR d.original_name LIKE '$lq' OR d.description LIKE '$lq' OR d.category LIKE '$lq')" : "1=1";
            if ($date_from) $w .= " AND d.created_at >= '".$db->real_escape_string($date_from)."'";
            if ($date_to)   $w .= " AND d.created_at <= '".$db->real_escape_string($date_to)."'";
            if (!isManager()) $w .= " AND d.access='all'";
            $rows = $db->query("
                SELECT d.id,d.title,d.description,d.category AS status,d.original_name,d.file_size,d.created_at AS updated_at,
                    u.name AS uploader,'document' AS _type
                FROM documents d LEFT JOIN users u ON u.id=d.uploaded_by
                WHERE $w ORDER BY d.created_at DESC LIMIT 20
            ")->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as $r) {
                $r['icon']='📄'; $r['url']='documents.php?q='.urlencode($q).'&tab=files';
                $r['snippet'] = ($r['uploader']??'').' · '.$r['original_name'].' · '.round($r['file_size']/1024).' KB';
                $r['badge']   = $r['status'];
                $r['badge_color']='#f59e0b';
                $results[] = $r;
            }
            // Rich docs
            $rows = $db->query("
                SELECT r.id,r.title,'rich_doc' AS _type,r.category AS status,r.updated_at,
                    u.name AS uploader, LEFT(REGEXP_REPLACE(r.content,'<[^>]+>',''),150) AS snippet_raw
                FROM rich_docs r LEFT JOIN users u ON u.id=r.created_by
                WHERE r.title LIKE '$lq' OR r.content LIKE '$lq'
                ORDER BY r.updated_at DESC LIMIT 10
            ")->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as $r) {
                $r['icon']='📝'; $r['url']='documents.php?tab=editor&edit='.$r['id'];
                $r['description']=$r['title']; $r['snippet']=$r['snippet_raw']??'';
                $r['badge']=$r['status']; $r['badge_color']='#8b5cf6';
                $results[] = $r;
            }
        }

        // ── Leads ──
        if (isManager() && ($type === 'all' || $type === 'leads')) {
            $w = $q ? "(name LIKE '$lq' OR company LIKE '$lq' OR service_interest LIKE '$lq' OR notes LIKE '$lq')" : "1=1";
            if ($status_f) $w .= " AND stage='".$db->real_escape_string($status_f)."'";
            $rows = $db->query("SELECT id,name AS title,company AS description,stage AS status,updated_at,'lead' AS _type FROM leads WHERE $w ORDER BY updated_at DESC LIMIT 15")->fetch_all(MYSQLI_ASSOC);
            foreach ($rows as $r) {
                $r['icon']='🎯'; $r['url']='leads.php?view='.$r['id'];
                $r['snippet']=$r['description']??'';
                $r['badge']=ucfirst($r['status']); $r['badge_color']='#6366f1';
                $results[] = $r;
            }
        }

        // Sort: exact title matches first, then by updated_at
        usort($results, function($a,$b) use($q) {
            $am = stripos($a['title'],$q)===0 ? 0 : 1;
            $bm = stripos($b['title'],$q)===0 ? 0 : 1;
            if ($am !== $bm) return $am - $bm;
            return strcmp($b['updated_at']??'',$a['updated_at']??'');
        });

        // Save to recent searches
        if ($q) {
            $cnt = count($results);
            $qe2 = $db->real_escape_string($q);
            $db->query("DELETE FROM recent_searches WHERE user_id=$uid AND query='$qe2'");
            $db->query("INSERT INTO recent_searches (user_id,query,result_count) VALUES ($uid,'$qe2',$cnt)");
            // Keep only last 20
            $db->query("DELETE FROM recent_searches WHERE user_id=$uid AND id NOT IN (SELECT id FROM (SELECT id FROM recent_searches WHERE user_id=$uid ORDER BY searched_at DESC LIMIT 20) t)");
        }

        jout(['results'=>$results,'total'=>count($results),'query'=>$q]);
        break;

    // ══ GET RECENT SEARCHES ══
    case 'recent':
        $rows = $db->query("SELECT id,query,result_count,searched_at FROM recent_searches WHERE user_id=$uid ORDER BY searched_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
        jout(['recent'=>$rows]);
        break;

    // ══ DELETE RECENT ══
    case 'delete_recent':
        $id = (int)($_POST['id'] ?? 0);
        $id ? $db->query("DELETE FROM recent_searches WHERE id=$id AND user_id=$uid")
            : $db->query("DELETE FROM recent_searches WHERE user_id=$uid");
        jout(['ok'=>true]);
        break;

    // ══ SAVE SEARCH ══
    case 'save_search':
        $label   = trim($_POST['label'] ?? '');
        $query   = trim($_POST['query'] ?? '');
        $filters = trim($_POST['filters'] ?? '{}');
        if (!$label) jout(['ok'=>false,'error'=>'Label required']);
        $le = $db->real_escape_string($label);
        $qe = $db->real_escape_string($query);
        $fe = $db->real_escape_string($filters);
        $db->query("INSERT INTO saved_searches (user_id,label,query,filters) VALUES ($uid,'$le','$qe','$fe')");
        jout(['ok'=>true,'id'=>$db->insert_id]);
        break;

    // ══ GET SAVED SEARCHES ══
    case 'saved':
        $rows = $db->query("SELECT * FROM saved_searches WHERE user_id=$uid ORDER BY is_pinned DESC, use_count DESC, updated_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);
        jout(['saved'=>$rows]);
        break;

    // ══ DELETE / PIN SAVED ══
    case 'delete_saved':
        $db->query("DELETE FROM saved_searches WHERE id=".(int)$_POST['id']." AND user_id=$uid");
        jout(['ok'=>true]);
        break;
    case 'pin_saved':
        $id = (int)$_POST['id'];
        $db->query("UPDATE saved_searches SET is_pinned = NOT is_pinned WHERE id=$id AND user_id=$uid");
        jout(['ok'=>true]);
        break;

    default:
        jout(['error'=>'Unknown action']);
}