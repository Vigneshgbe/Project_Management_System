<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

// ── PDF INLINE VIEW ──────────────────────────────────────────────────────────
if (isset($_GET['view_pdf'])) {
    $id  = (int)$_GET['view_pdf'];
    $doc = $db->query("SELECT * FROM documents WHERE id=$id")->fetch_assoc();
    if ($doc) {
        $path = UPLOAD_DOC_DIR.$doc['filename'];
        $ext  = strtolower(pathinfo($doc['original_name'], PATHINFO_EXTENSION));
        if (file_exists($path) && $ext === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="'.addslashes($doc['original_name']).'"');
            header('Content-Length: '.filesize($path));
            readfile($path); exit;
        }
    }
    die('PDF not found.');
}

// ── FILE DOWNLOAD ────────────────────────────────────────────────────────────
if (isset($_GET['download'])) {
    $id  = (int)$_GET['download'];
    $doc = $db->query("SELECT * FROM documents WHERE id=$id")->fetch_assoc();
    if ($doc) {
        $path = UPLOAD_DOC_DIR.$doc['filename'];
        if (file_exists($path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.addslashes($doc['original_name']).'"');
            header('Content-Length: '.filesize($path));
            readfile($path); exit;
        }
    }
    die('File not found.');
}

// ── POST HANDLERS ────────────────────────────────────────────────────────────
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Upload — all roles can upload (as per original design)
    if ($action === 'upload') {
        $title    = trim($_POST['title'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $proj_id  = (int)($_POST['project_id'] ?? 0) ?: null;
        $cont_id  = (int)($_POST['contact_id'] ?? 0) ?: null;
        $access   = $_POST['access'] ?? 'all';
        if (!$title) { flash('Title required.','error'); ob_end_clean(); header('Location: documents.php'); exit; }
        if (!empty($_FILES['file']['name'])) {
            $file = $_FILES['file'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_DOC_TYPES)) { flash('File type not allowed.','error'); ob_end_clean(); header('Location: documents.php'); exit; }
            if ($file['size'] > MAX_FILE_SIZE) { flash('File too large (max 20MB).','error'); ob_end_clean(); header('Location: documents.php'); exit; }
            $fname = uniqid('doc_',true).'.'.$ext;
            if (!is_dir(UPLOAD_DOC_DIR)) mkdir(UPLOAD_DOC_DIR, 0755, true);
            if (!move_uploaded_file($file['tmp_name'], UPLOAD_DOC_DIR.$fname)) { flash('Upload failed.','error'); ob_end_clean(); header('Location: documents.php'); exit; }
            $oname = basename($file['name']);
            $fsize = (int)$file['size'];
            $ftype = $file['type'];
            $stmt  = $db->prepare("INSERT INTO documents (title,description,filename,original_name,file_size,file_type,project_id,contact_id,category,access,uploaded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssisiissi",$title,$desc,$fname,$oname,$fsize,$ftype,$proj_id,$cont_id,$category,$access,$uid);
            $stmt->execute();
            logActivity('uploaded document',$title,$db->insert_id);
            flash('Document uploaded.','success');
        } else {
            flash('No file selected.','error');
        }
        ob_end_clean(); header('Location: documents.php'); exit;
    }

    if ($action === 'delete_file') {
        $id  = (int)$_POST['id'];
        $doc = $db->query("SELECT * FROM documents WHERE id=$id")->fetch_assoc();
        if ($doc && ($doc['uploaded_by'] == $uid || isManager())) {
            @unlink(UPLOAD_DOC_DIR.$doc['filename']);
            $db->query("DELETE FROM documents WHERE id=$id");
            logActivity('deleted document',$doc['title'],$id);
            flash('Document deleted.','success');
        }
        ob_end_clean(); header('Location: documents.php'); exit;
    }
}
ob_end_clean();

// ── FILTERS ──────────────────────────────────────────────────────────────────
$proj_filter = (int)($_GET['project_id'] ?? 0);
$cat_filter  = $_GET['cat'] ?? '';
$search      = trim($_GET['q'] ?? '');
$new_mode    = isset($_GET['new']);

// Role-scoped query:
// Managers see all docs (access any)
// Members see: docs with access='all' that they uploaded OR are linked to their projects
$fw = "1=1";
if ($proj_filter) $fw .= " AND d.project_id=".(int)$proj_filter;
if ($cat_filter)  $fw .= " AND d.category='".$db->real_escape_string($cat_filter)."'";
if ($search)      $fw .= " AND (d.title LIKE '%".$db->real_escape_string($search)."%' OR d.original_name LIKE '%".$db->real_escape_string($search)."%')";

if (!isManager()) {
    // Members see: docs they uploaded OR any access='all' document
    $fw .= " AND (d.uploaded_by=$uid OR d.access='all')";
} else {
    // Managers don't see attachment-only docs in the main list (already excluded via category)
}
// Never show raw attachment records in main documents page
$fw .= " AND d.category != 'Attachment'";

$docs = $db->query("
    SELECT d.*, u.name AS uploader, p.title AS proj_title
    FROM documents d
    LEFT JOIN users u ON u.id=d.uploaded_by
    LEFT JOIN projects p ON p.id=d.project_id
    WHERE $fw ORDER BY d.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$categories = array_column(
    $db->query("SELECT DISTINCT category FROM documents WHERE category != 'Attachment' ORDER BY category")->fetch_all(MYSQLI_NUM),
    0
);
$projects   = $db->query("SELECT id,title FROM projects ORDER BY title")->fetch_all(MYSQLI_ASSOC);
$contacts   = $db->query("SELECT id,name FROM contacts ORDER BY name")->fetch_all(MYSQLI_ASSOC);

function docIcon(string $name): string {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return match(true) {
        $ext === 'pdf'                                   => '📕',
        in_array($ext, ['doc','docx'])                   => '📘',
        in_array($ext, ['xls','xlsx'])                   => '📗',
        in_array($ext, ['ppt','pptx'])                   => '📙',
        in_array($ext, ['jpg','jpeg','png','gif','webp']) => '🖼',
        in_array($ext, ['zip','rar'])                    => '🗜',
        $ext === 'txt'                                   => '📝',
        default                                          => '📄',
    };
}

renderLayout('Documents', 'documents');
?>

<style>
/* ── DOCUMENT PAGE ── */
.doc-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px}
.doc-tab{padding:10px 20px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;text-decoration:none;white-space:nowrap}
.doc-tab:hover,.doc-tab.active{color:var(--orange);border-bottom-color:var(--orange)}
.doc-filter{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.doc-filter-left{display:flex;gap:8px;flex-wrap:wrap;align-items:center;flex:1}
.doc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(268px,1fr));gap:12px}
.doc-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;display:flex;flex-direction:column;transition:border-color .15s,box-shadow .15s}
.doc-card:hover{border-color:var(--border2);box-shadow:0 2px 10px rgba(0,0,0,.18)}
.doc-card-top{display:flex;align-items:flex-start;gap:12px;margin-bottom:10px}
.doc-card-body{flex:1}
.doc-card-footer{display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border);padding-top:10px;margin-top:auto}
@media(max-width:480px){.doc-grid{grid-template-columns:1fr}.doc-filter{flex-direction:column;align-items:stretch}}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
        <div style="font-family:var(--font-display);font-size:18px;font-weight:700">📁 Files</div>
        <div style="font-size:12px;color:var(--text3);margin-top:2px">Upload and manage documents · <a href="rich_docs.php" style="color:var(--orange)">Switch to Rich Docs →</a></div>
    </div>
</div>

<!-- FILTER BAR -->
<div class="doc-filter">
    <form method="GET" class="doc-filter-left" id="filter-form">
        <?php if ($proj_filter): ?><input type="hidden" name="project_id" value="<?= $proj_filter ?>"><?php endif; ?>
        <div class="search-box" style="min-width:180px;flex:1;max-width:260px">
            <span style="color:var(--text3)">🔍</span>
            <input type="text" name="q" placeholder="Search documents…" value="<?= h($search) ?>">
        </div>
        <select name="cat" class="form-control" style="width:auto" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= h($c) ?>" <?= $cat_filter===$c?'selected':'' ?>><?= h($c) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!$proj_filter): ?>
        <select name="project_id" class="form-control" style="width:auto" onchange="this.form.submit()">
            <option value="">All Projects</option>
            <?php foreach ($projects as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $proj_filter==$p['id']?'selected':'' ?>><?= h($p['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </form>
    <!-- All roles can upload files -->
    <button class="btn btn-primary" onclick="openModal('modal-upload')" style="flex-shrink:0">↑ <span>Upload File</span></button>
</div>

<?php if (empty($docs)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">📁</div>
        <p>No documents found. <a href="#" onclick="openModal('modal-upload')" style="color:var(--orange)">Upload one</a></p>
    </div>
</div>
<?php else: ?>
<div class="doc-grid">
    <?php foreach ($docs as $d):
        $ext    = strtolower(pathinfo($d['original_name'], PATHINFO_EXTENSION));
        $is_pdf = $ext === 'pdf';
        $can_delete = ($d['uploaded_by'] == $uid || isManager());
    ?>
    <div class="doc-card">
        <div class="doc-card-top">
            <span style="font-size:30px;flex-shrink:0"><?= docIcon($d['original_name']) ?></span>
            <div class="doc-card-body">
                <div style="font-size:13.5px;font-weight:700;color:var(--text);margin-bottom:2px;word-break:break-word"><?= h($d['title']) ?></div>
                <div style="font-size:11px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= h($d['original_name']) ?>"><?= h($d['original_name']) ?></div>
            </div>
        </div>
        <?php if (!empty($d['description'])): ?>
        <p style="font-size:12px;color:var(--text2);margin-bottom:8px;line-height:1.5"><?= h(mb_substr($d['description'],0,80)).(mb_strlen($d['description'])>80?'…':'') ?></p>
        <?php endif; ?>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
            <span class="badge" style="background:var(--bg4);color:var(--text3)"><?= h($d['category']) ?></span>
            <?php if ($d['proj_title']): ?>
            <span class="badge" style="background:var(--orange-bg);color:var(--orange)"><?= h($d['proj_title']) ?></span>
            <?php endif; ?>
        </div>
        <div class="doc-card-footer">
            <div>
                <div style="font-size:11px;color:var(--text3)"><?= h($d['uploader']) ?> · <?= formatSize((int)$d['file_size']) ?></div>
                <div style="font-size:10px;color:var(--text3)"><?= fDate($d['created_at'],'M j, Y') ?></div>
            </div>
            <div style="display:flex;gap:5px">
                <?php if ($is_pdf): ?>
                <button class="btn btn-ghost btn-sm btn-icon" title="Preview PDF"
                    onclick="openPdfViewer(<?= $d['id'] ?>,<?= htmlspecialchars(json_encode($d['title']), ENT_QUOTES) ?>)"
                    style="color:var(--red)">👁</button>
                <?php endif; ?>
                <a href="documents.php?download=<?= $d['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Download">↓</a>
                <?php if ($can_delete): ?>
                <form method="POST" onsubmit="return confirm('Delete this document?')" style="display:inline">
                    <input type="hidden" name="action" value="delete_file">
                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete">🗑</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── UPLOAD MODAL ── -->
<div class="modal-overlay <?= $new_mode?'open':'' ?>" id="modal-upload">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Upload File</div>
            <button class="modal-close" onclick="closeModal('modal-upload')">✕</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Document Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Q3 Brand Report">
                </div>
                <div class="form-group">
                    <label class="form-label">File *</label>
                    <input type="file" name="file" class="form-control" required
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.png,.jpg,.jpeg,.gif,.zip,.rar"
                        onchange="showFileName(this)">
                    <div id="file-name" style="font-size:11px;color:var(--text3);margin-top:4px"></div>
                    <div style="font-size:11px;color:var(--text3);margin-top:2px">PDF, DOC, XLS, PPT, Images, ZIP · Max 20MB</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" placeholder="What is this document?"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" class="form-control" value="General" list="cat-list">
                        <datalist id="cat-list"><option>General</option><option>Design</option><option>Development</option><option>Finance</option><option>Legal</option><option>Marketing</option><option>HR</option><option>Reports</option></datalist>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Access</label>
                        <select name="access" class="form-control">
                            <option value="all">All Members</option>
                            <?php if (isManager()): ?>
                            <option value="manager">Managers+</option>
                            <option value="admin">Admin Only</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Link to Project</label>
                        <select name="project_id" class="form-control">
                            <option value="">— None —</option>
                            <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $proj_filter==$p['id']?'selected':'' ?>><?= h($p['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Link to Contact</label>
                        <select name="contact_id" class="form-control">
                            <option value="">— None —</option>
                            <?php foreach ($contacts as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-upload')">Cancel</button>
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </form>
    </div>
</div>

<!-- ── PDF VIEWER MODAL ── -->
<div class="modal-overlay" id="modal-pdf" style="padding:0">
    <div style="background:var(--bg2);width:100%;max-width:960px;margin:auto;display:flex;flex-direction:column;height:100vh">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-bottom:1px solid var(--border);flex-shrink:0">
            <div style="font-family:var(--font-display);font-weight:700;font-size:15px" id="pdf-title">Document</div>
            <div style="display:flex;gap:8px">
                <a id="pdf-download-btn" href="#" class="btn btn-ghost btn-sm">↓ Download</a>
                <button class="btn btn-danger btn-sm" onclick="closeModal('modal-pdf');document.getElementById('pdf-frame').src=''">✕ Close</button>
            </div>
        </div>
        <iframe id="pdf-frame" src="" style="flex:1;border:none;width:100%;background:#525659"></iframe>
    </div>
</div>

<script>
function openPdfViewer(id, title) {
    document.getElementById('pdf-title').textContent = title;
    document.getElementById('pdf-frame').src = 'documents.php?view_pdf=' + id;
    document.getElementById('pdf-download-btn').href = 'documents.php?download=' + id;
    openModal('modal-pdf');
}
function showFileName(inp) {
    var fn = document.getElementById('file-name');
    if (inp.files[0]) fn.textContent = 'Selected: ' + inp.files[0].name + ' (' + Math.round(inp.files[0].size/1024) + ' KB)';
}
</script>
<?php renderLayoutEnd(); ?>