<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db = getCRMDB();
$user = currentUser();

// Handle download
if (isset($_GET['download'])) {
    $id = (int)$_GET['download'];
    $doc = $db->query("SELECT * FROM documents WHERE id=$id")->fetch_assoc();
    if ($doc) {
        $path = UPLOAD_DOC_DIR . $doc['filename'];
        if (file_exists($path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.addslashes($doc['original_name']).'"');
            header('Content-Length: '.filesize($path));
            readfile($path);
            exit;
        }
    }
    die('File not found.');
}

ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $title    = trim($_POST['title'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $proj_id  = (int)($_POST['project_id'] ?? 0) ?: null;
        $cont_id  = (int)($_POST['contact_id'] ?? 0) ?: null;
        $access   = $_POST['access'] ?? 'all';
        $uid      = $user['id'];

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
            $fsize = $file['size'];
            $ftype = $file['type'];
            $stmt = $db->prepare("INSERT INTO documents (title,description,filename,original_name,file_size,file_type,project_id,contact_id,category,access,uploaded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssiissssi",$title,$desc,$fname,$oname,$fsize,$ftype,$proj_id,$cont_id,$category,$access,$uid);
            $stmt->execute();
            logActivity('uploaded document',$title,$db->insert_id);
            flash('Document uploaded.','success');
        } else {
            flash('No file selected.','error');
        }
        ob_end_clean(); header('Location: documents.php'); exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $doc = $db->query("SELECT * FROM documents WHERE id=$id")->fetch_assoc();
        if ($doc && ($doc['uploaded_by'] == $user['id'] || isManager())) {
            @unlink(UPLOAD_DOC_DIR.$doc['filename']);
            $db->query("DELETE FROM documents WHERE id=$id");
            logActivity('deleted document',$doc['title'],$id);
            flash('Document deleted.','success');
        }
        ob_end_clean(); header('Location: documents.php'); exit;
    }
}
ob_end_clean();

$proj_filter = (int)($_GET['project_id'] ?? 0);
$cat_filter  = $_GET['cat'] ?? '';
$search      = trim($_GET['q'] ?? '');
$new_mode    = isset($_GET['new']);

$where = "1=1";
if ($proj_filter) $where .= " AND d.project_id=$proj_filter";
if ($cat_filter) $where .= " AND d.category='".$db->real_escape_string($cat_filter)."'";
if ($search) $where .= " AND (d.title LIKE '%".$db->real_escape_string($search)."%' OR d.original_name LIKE '%".$db->real_escape_string($search)."%')";
// Access control
if (!isManager()) $where .= " AND d.access='all'";

$docs = $db->query("
  SELECT d.*, u.name AS uploader, p.title AS proj_title, c.name AS contact_name
  FROM documents d
  LEFT JOIN users u ON u.id=d.uploaded_by
  LEFT JOIN projects p ON p.id=d.project_id
  LEFT JOIN contacts c ON c.id=d.contact_id
  WHERE $where ORDER BY d.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$categories = $db->query("SELECT DISTINCT category FROM documents ORDER BY category")->fetch_all(MYSQLI_NUM);
$categories = array_column($categories, 0);
$projects   = $db->query("SELECT id,title FROM projects ORDER BY title")->fetch_all(MYSQLI_ASSOC);
$contacts   = $db->query("SELECT id,name FROM contacts ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// File type icons
function docIcon(string $name): string {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return match(true) {
        in_array($ext,['pdf']) => '📕',
        in_array($ext,['doc','docx']) => '📘',
        in_array($ext,['xls','xlsx']) => '📗',
        in_array($ext,['ppt','pptx']) => '📙',
        in_array($ext,['jpg','jpeg','png','gif','webp']) => '🖼',
        in_array($ext,['zip','rar']) => '🗜',
        in_array($ext,['txt']) => '📝',
        default => '📄'
    };
}

renderLayout('Documents', 'documents');
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <?php if ($proj_filter): ?><input type="hidden" name="project_id" value="<?= $proj_filter ?>"><?php endif; ?>
    <div class="search-box">
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
      <option value="<?= $p['id'] ?>"><?= h($p['title']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
  </form>
  <button class="btn btn-primary" onclick="openModal('modal-upload')">↑ <span>Upload</span></button>
</div>

<?php if (empty($docs)): ?>
<div class="card"><div class="empty-state"><div class="icon">📁</div><p>No documents yet. <a href="#" onclick="openModal('modal-upload')" style="color:var(--orange)">Upload the first one</a></p></div></div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px">
  <?php foreach ($docs as $d): ?>
  <div class="card" style="padding:16px">
    <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:10px">
      <span style="font-size:28px;flex-shrink:0"><?= docIcon($d['original_name']) ?></span>
      <div style="flex:1;min-width:0">
        <div style="font-size:13.5px;font-weight:700;color:var(--text);margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($d['title']) ?></div>
        <div style="font-size:11px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($d['original_name']) ?></div>
      </div>
    </div>
    <?php if ($d['description']): ?>
    <p style="font-size:12px;color:var(--text2);margin-bottom:10px;line-height:1.5"><?= h(mb_substr($d['description'],0,80)).(mb_strlen($d['description'])>80?'…':'') ?></p>
    <?php endif; ?>
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
      <span class="badge" style="background:var(--bg4);color:var(--text3)"><?= h($d['category']) ?></span>
      <?php if ($d['proj_title']): ?>
      <span class="badge" style="background:var(--orange-bg);color:var(--orange)"><?= h($d['proj_title']) ?></span>
      <?php endif; ?>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border);padding-top:10px">
      <div>
        <div style="font-size:11px;color:var(--text3)"><?= h($d['uploader']) ?> · <?= formatSize($d['file_size']) ?></div>
        <div style="font-size:10px;color:var(--text3)"><?= fDate($d['created_at'],'M j, Y') ?></div>
      </div>
      <div style="display:flex;gap:6px">
        <a href="documents.php?download=<?= $d['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Download">↓</a>
        <?php if ($d['uploaded_by'] == $user['id'] || isManager()): ?>
        <form method="POST" onsubmit="return confirm('Delete document?')">
          <input type="hidden" name="action" value="delete">
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

<!-- UPLOAD MODAL -->
<div class="modal-overlay <?= $new_mode?'open':'' ?>" id="modal-upload">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Upload Document</div>
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
          <input type="file" name="file" class="form-control" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.png,.jpg,.jpeg,.gif,.zip,.rar" onchange="showFileName(this)">
          <div id="file-name" style="font-size:11px;color:var(--text3);margin-top:4px"></div>
          <div style="font-size:11px;color:var(--text3);margin-top:2px">Allowed: PDF, DOC, XLS, PPT, Images, ZIP · Max 20MB</div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" placeholder="What is this document?"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category</label>
            <input type="text" name="category" class="form-control" value="General" list="cat-list">
            <datalist id="cat-list">
              <option>General</option><option>Design</option><option>Development</option>
              <option>Finance</option><option>Legal</option><option>Marketing</option>
              <option>HR</option><option>Reports</option>
            </datalist>
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
        <button type="submit" class="btn btn-primary">Upload Document</button>
      </div>
    </form>
  </div>
</div>

<script>
function showFileName(inp){
  const fn=document.getElementById('file-name');
  if(inp.files[0]) fn.textContent='Selected: '+inp.files[0].name+' ('+Math.round(inp.files[0].size/1024)+' KB)';
}
</script>
<?php renderLayoutEnd(); ?>