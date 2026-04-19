<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db = getCRMDB();
$user = currentUser();

// ── PDF INLINE VIEW ──
if (isset($_GET['view_pdf'])) {
    $id = (int)$_GET['view_pdf'];
    $doc = $db->query("SELECT * FROM documents WHERE id=$id")->fetch_assoc();
    if ($doc) {
        $path = UPLOAD_DOC_DIR . $doc['filename'];
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

// ── FILE DOWNLOAD ──
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
            readfile($path); exit;
        }
    }
    die('File not found.');
}

// ── RICH DOC PRINT/EXPORT (full page HTML for print-to-PDF) ──
if (isset($_GET['print_doc'])) {
    $id = (int)$_GET['print_doc'];
    $rd = $db->query("SELECT r.*,u.name AS author FROM rich_docs r LEFT JOIN users u ON u.id=r.created_by WHERE r.id=$id")->fetch_assoc();
    if (!$rd) die('Document not found.');
    ?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <title><?= h($rd['title']) ?></title>
    <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Arial,sans-serif;font-size:13pt;color:#1a1a1a;background:#fff;padding:40px 60px;max-width:900px;margin:auto}
    h1{font-size:22pt;margin-bottom:6px;color:#1a1a1a}
    .meta{font-size:10pt;color:#666;margin-bottom:24px;padding-bottom:12px;border-bottom:1px solid #ddd}
    .content{line-height:1.7}
    .content h1,.content h2,.content h3{margin:16px 0 8px}
    .content p{margin-bottom:10px}
    .content table{border-collapse:collapse;width:100%;margin:12px 0}
    .content table td,.content table th{border:1px solid #ccc;padding:7px 10px}
    .content table th{background:#f5f5f5;font-weight:700}
    .content ul,.content ol{padding-left:24px;margin-bottom:10px}
    .content img{max-width:100%}
    @media print{body{padding:20px 30px}@page{margin:1.5cm}}
    </style></head><body>
    <h1><?= h($rd['title']) ?></h1>
    <div class="meta">By <?= h($rd['author']) ?> &nbsp;·&nbsp; <?= date('M j, Y g:ia',strtotime($rd['updated_at'])) ?> &nbsp;·&nbsp; <?= h($rd['category']) ?></div>
    <div class="content"><?= $rd['content'] ?></div>
    <script>window.onload=function(){window.print()}</script>
    </body></html><?php exit;
}

// ── POST HANDLERS ──
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── File upload (existing) ──
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
            $fsize = (int)$file['size'];
            $ftype = $file['type'];
            // FIXED bind_param: s s s s i s i i s s i
            $stmt = $db->prepare("INSERT INTO documents (title,description,filename,original_name,file_size,file_type,project_id,contact_id,category,access,uploaded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssisiissi",$title,$desc,$fname,$oname,$fsize,$ftype,$proj_id,$cont_id,$category,$access,$uid);
            $stmt->execute();
            logActivity('uploaded document',$title,$db->insert_id);
            flash('Document uploaded.','success');
        } else {
            flash('No file selected.','error');
        }
        ob_end_clean(); header('Location: documents.php?tab=files'); exit;
    }

    if ($action === 'delete_file') {
        $id = (int)$_POST['id'];
        $doc = $db->query("SELECT * FROM documents WHERE id=$id")->fetch_assoc();
        if ($doc && ($doc['uploaded_by'] == $user['id'] || isManager())) {
            @unlink(UPLOAD_DOC_DIR.$doc['filename']);
            $db->query("DELETE FROM documents WHERE id=$id");
            logActivity('deleted document',$doc['title'],$id);
            flash('Document deleted.','success');
        }
        ob_end_clean(); header('Location: documents.php?tab=files'); exit;
    }

    // ── Rich doc save (create/edit) ──
    if ($action === 'save_rich') {
        $rdid    = (int)($_POST['rdid'] ?? 0);
        $title   = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $cat     = trim($_POST['category'] ?? 'General');
        $proj_id = (int)($_POST['project_id'] ?? 0) ?: null;
        $status  = $_POST['status'] ?? 'draft';
        $uid     = $user['id'];
        if (!$title) { flash('Title required.','error'); ob_end_clean(); header('Location: documents.php?tab=editor'); exit; }
        if ($rdid) {
            $stmt = $db->prepare("UPDATE rich_docs SET title=?,content=?,category=?,project_id=?,status=?,updated_by=? WHERE id=?");
            $stmt->bind_param("ssssiii",$title,$content,$cat,$proj_id,$status,$uid,$rdid);
            $stmt->execute();
            flash('Document saved.','success');
            ob_end_clean(); header('Location: documents.php?tab=editor&edit='.$rdid); exit;
        } else {
            $stmt = $db->prepare("INSERT INTO rich_docs (title,content,category,project_id,status,created_by,updated_by) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssii",$title,$content,$cat,$proj_id,$status,$uid,$uid);
            $stmt->execute();
            $new_id = $db->insert_id;
            flash('Document created.','success');
            ob_end_clean(); header('Location: documents.php?tab=editor&edit='.$new_id); exit;
        }
    }

    if ($action === 'delete_rich') {
        $rdid = (int)$_POST['rdid'];
        $rd = $db->query("SELECT * FROM rich_docs WHERE id=$rdid")->fetch_assoc();
        if ($rd && ($rd['created_by'] == $user['id'] || isManager())) {
            $db->query("DELETE FROM rich_docs WHERE id=$rdid");
            flash('Document deleted.','success');
        }
        ob_end_clean(); header('Location: documents.php?tab=editor'); exit;
    }
}
ob_end_clean();

// ── FILTERS ──
$tab        = $_GET['tab'] ?? 'files';
$proj_filter= (int)($_GET['project_id'] ?? 0);
$cat_filter = $_GET['cat'] ?? '';
$search     = trim($_GET['q'] ?? '');
$new_mode   = isset($_GET['new']);
$edit_rdid  = (int)($_GET['edit'] ?? 0);

// Files query — FIXED: all filter params preserved in WHERE
$fw = "1=1";
if ($proj_filter) $fw .= " AND d.project_id=".(int)$proj_filter;
if ($cat_filter)  $fw .= " AND d.category='".$db->real_escape_string($cat_filter)."'";
if ($search)      $fw .= " AND (d.title LIKE '%".$db->real_escape_string($search)."%' OR d.original_name LIKE '%".$db->real_escape_string($search)."%')";
if (!isManager()) $fw .= " AND d.access='all'";

$docs = $db->query("
    SELECT d.*, u.name AS uploader, p.title AS proj_title
    FROM documents d
    LEFT JOIN users u ON u.id=d.uploaded_by
    LEFT JOIN projects p ON p.id=d.project_id
    WHERE $fw ORDER BY d.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$categories  = array_column($db->query("SELECT DISTINCT category FROM documents ORDER BY category")->fetch_all(MYSQLI_NUM), 0);
$projects    = $db->query("SELECT id,title FROM projects ORDER BY title")->fetch_all(MYSQLI_ASSOC);
$contacts    = $db->query("SELECT id,name FROM contacts ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Rich docs
$rw = "1=1";
if ($search && $tab==='editor') $rw .= " AND r.title LIKE '%".$db->real_escape_string($search)."%'";
$rich_docs = $db->query("
    SELECT r.*, u.name AS author, p.title AS proj_title
    FROM rich_docs r
    LEFT JOIN users u ON u.id=r.created_by
    LEFT JOIN projects p ON p.id=r.project_id
    WHERE $rw ORDER BY r.updated_at DESC
")->fetch_all(MYSQLI_ASSOC);

$edit_rd = null;
if ($edit_rdid) $edit_rd = $db->query("SELECT * FROM rich_docs WHERE id=$edit_rdid")->fetch_assoc();

// File icon helper
function docIcon(string $name): string {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return match(true) {
        $ext === 'pdf'                            => '📕',
        in_array($ext,['doc','docx'])             => '📘',
        in_array($ext,['xls','xlsx'])             => '📗',
        in_array($ext,['ppt','pptx'])             => '📙',
        in_array($ext,['jpg','jpeg','png','gif','webp']) => '🖼',
        in_array($ext,['zip','rar'])              => '🗜',
        $ext === 'txt'                            => '📝',
        default                                   => '📄'
    };
}

renderLayout('Documents', 'documents');
?>

<style>
/* ── DOCUMENT PAGE ── */
.doc-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px}
.doc-tab{padding:10px 20px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;text-decoration:none;white-space:nowrap}
.doc-tab:hover,.doc-tab.active{color:var(--orange);border-bottom-color:var(--orange)}

/* Filter bar */
.doc-filter{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.doc-filter-left{display:flex;gap:8px;flex-wrap:wrap;align-items:center;flex:1}

/* File cards grid */
.doc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(268px,1fr));gap:12px}
.doc-card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;display:flex;flex-direction:column;transition:border-color .15s,box-shadow .15s}
.doc-card:hover{border-color:var(--border2);box-shadow:0 2px 10px rgba(0,0,0,.18)}
.doc-card-top{display:flex;align-items:flex-start;gap:12px;margin-bottom:10px}
.doc-card-body{flex:1}
.doc-card-footer{display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border);padding-top:10px;margin-top:auto}

/* Rich docs list */
.rdoc-row{display:flex;align-items:center;gap:14px;padding:13px 16px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:8px;transition:border-color .15s}
.rdoc-row:hover{border-color:var(--border2)}
.rdoc-icon{font-size:26px;flex-shrink:0}
.rdoc-info{flex:1;min-width:0}
.rdoc-title{font-size:14px;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:3px}
.rdoc-meta{font-size:11.5px;color:var(--text3)}
.rdoc-actions{display:flex;gap:6px;flex-shrink:0}

/* TinyMCE editor wrap */
.editor-wrap{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.editor-toolbar{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;background:var(--bg3)}
.editor-meta{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border);align-items:end;flex-wrap:wrap}
.editor-body{padding:0}

/* Status badge */
.sdraft{background:rgba(245,158,11,.15);color:#f59e0b}
.spublished{background:rgba(16,185,129,.15);color:#10b981}

@media(max-width:768px){
  .editor-meta{grid-template-columns:1fr 1fr}
  .doc-filter{flex-direction:column;align-items:stretch}
}
@media(max-width:480px){
  .editor-meta{grid-template-columns:1fr}
  .doc-grid{grid-template-columns:1fr}
}
</style>

<!-- TABS -->
<div class="doc-tabs">
  <a href="documents.php?tab=files<?= $proj_filter?"&project_id=$proj_filter":'' ?>" class="doc-tab <?= $tab==='files'?'active':'' ?>">📁 Files</a>
  <a href="documents.php?tab=editor" class="doc-tab <?= $tab==='editor'?'active':'' ?>">✍ Rich Documents</a>
</div>

<?php if ($tab === 'files'): // ══════════════ FILES TAB ══════════════ ?>

<div class="doc-filter">
  <form method="GET" class="doc-filter-left" id="filter-form">
    <input type="hidden" name="tab" value="files">
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
  <button class="btn btn-primary" onclick="openModal('modal-upload')" style="flex-shrink:0">↑ <span>Upload File</span></button>
</div>

<?php if (empty($docs)): ?>
<div class="card"><div class="empty-state"><div class="icon">📁</div><p>No documents found. <a href="#" onclick="openModal('modal-upload')" style="color:var(--orange)">Upload one</a></p></div></div>
<?php else: ?>
<div class="doc-grid">
  <?php foreach ($docs as $d):
    $ext = strtolower(pathinfo($d['original_name'], PATHINFO_EXTENSION));
    $is_pdf = $ext === 'pdf';
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
        <button class="btn btn-ghost btn-sm btn-icon" title="Preview PDF" onclick="openPdfViewer(<?= $d['id'] ?>,<?= htmlspecialchars(json_encode($d['title']), ENT_QUOTES) ?>)" style="color:var(--red)">👁</button>
        <?php endif; ?>
        <a href="documents.php?download=<?= $d['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Download">↓</a>
        <?php if ($d['uploaded_by'] == $user['id'] || isManager()): ?>
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

<?php else: // ══════════════ RICH EDITOR TAB ══════════════ ?>

<?php if ($edit_rd !== null || isset($_GET['new_doc'])): // ── EDITOR VIEW ── ?>

<?php $is_new = !$edit_rd; ?>
<div style="margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
  <a href="documents.php?tab=editor" style="color:var(--text3);font-size:13px">← Back to Documents</a>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php if ($edit_rd): ?>
    <div class="dropdown" style="display:inline-block">
      <button class="btn btn-ghost btn-sm" onclick="toggleDropdown('export-dd-top')">↓ Export ▾</button>
      <div class="dropdown-menu" id="export-dd-top">
        <a class="dropdown-item" href="export_doc.php?id=<?= $edit_rd['id'] ?>&format=pdf" target="_blank">📕 Export as PDF</a>
        <a class="dropdown-item" href="export_doc.php?id=<?= $edit_rd['id'] ?>&format=docx">📘 Export as DOCX</a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="documents.php?print_doc=<?= $edit_rd['id'] ?>" target="_blank">🖨 Print Preview</a>
      </div>
    </div>
    <?php if (isManager() || $edit_rd['created_by'] == $user['id']): ?>
    <form method="POST" onsubmit="return confirm('Delete this document?')" style="display:inline">
      <input type="hidden" name="action" value="delete_rich">
      <input type="hidden" name="rdid" value="<?= $edit_rd['id'] ?>">
      <button type="submit" class="btn btn-danger btn-sm">🗑 Delete</button>
    </form>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<form method="POST" id="editor-form">
  <input type="hidden" name="action" value="save_rich">
  <input type="hidden" name="rdid" value="<?= $edit_rd ? $edit_rd['id'] : 0 ?>">
  <input type="hidden" name="content" id="editor-content-hidden">

  <div class="editor-wrap">
    <!-- Toolbar -->
    <div class="editor-toolbar">
      <div style="font-family:var(--font-display);font-weight:700;font-size:15px">
        <?= $is_new ? '✍ New Document' : '✎ Editing Document' ?>
      </div>
      <div style="display:flex;gap:8px">
        <button type="button" class="btn btn-ghost btn-sm" onclick="saveDraft()">💾 Save Draft</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="savePublish()">✓ Save &amp; Publish</button>
      </div>
    </div>

    <!-- Meta fields -->
    <div class="editor-meta">
      <div class="form-group" style="margin:0">
        <label class="form-label">Document Title *</label>
        <input type="text" name="title" id="doc-title" class="form-control" required
          value="<?= h($edit_rd['title'] ?? '') ?>" placeholder="Enter document title…">
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Category</label>
        <input type="text" name="category" class="form-control" list="rcat-list"
          value="<?= h($edit_rd['category'] ?? 'General') ?>">
        <datalist id="rcat-list"><option>General</option><option>Design</option><option>Development</option><option>Finance</option><option>Legal</option><option>Marketing</option><option>HR</option><option>Reports</option><option>Proposals</option></datalist>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Link to Project</label>
        <select name="project_id" class="form-control">
          <option value="">— None —</option>
          <?php foreach ($projects as $p): ?>
          <option value="<?= $p['id'] ?>" <?= ($edit_rd['project_id'] ?? '') == $p['id'] ? 'selected':'' ?>><?= h($p['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label class="form-label">Status</label>
        <select name="status" id="doc-status" class="form-control">
          <option value="draft" <?= ($edit_rd['status'] ?? 'draft') === 'draft' ? 'selected':'' ?>>Draft</option>
          <option value="published" <?= ($edit_rd['status'] ?? '') === 'published' ? 'selected':'' ?>>Published</option>
        </select>
      </div>
    </div>

    <!-- TinyMCE body -->
    <div class="editor-body">
      <textarea id="tinymce-editor" name="content_raw"><?= htmlspecialchars($edit_rd['content'] ?? '') ?></textarea>
    </div>

    <!-- Footer save bar -->
    <div style="padding:12px 16px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;background:var(--bg3);flex-wrap:wrap;gap:8px">
      <span style="font-size:12px;color:var(--text3)" id="save-status">
        <?php if ($edit_rd && $edit_rd['updated_at']): ?>
          Last saved: <?= date('M j, Y g:ia', strtotime($edit_rd['updated_at'])) ?>
        <?php else: ?>
          Unsaved document
        <?php endif; ?>
      </span>
      <div style="display:flex;gap:8px">
        <button type="button" class="btn btn-ghost btn-sm" onclick="saveDraft()">💾 Save Draft</button>
        <button type="button" class="btn btn-primary" onclick="savePublish()">✓ Save &amp; Publish</button>
      </div>
    </div>
  </div>
</form>

<!-- TinyMCE CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js"></script>
<script>
// Detect theme
var isDark = document.documentElement.getAttribute('data-theme') !== 'light';

tinymce.init({
  selector: '#tinymce-editor',
  height: 560,
  menubar: 'file edit view insert format tools table',
  plugins: [
    'advlist','autolink','lists','link','image','charmap','preview','anchor','searchreplace',
    'visualblocks','code','fullscreen','insertdatetime','media','table','help','wordcount',
    'codesample','emoticons','pagebreak','nonbreaking','quickbars'
  ],
  toolbar: [
    'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor',
    'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link image media',
    'removeformat | codesample emoticons charmap | pagebreak | fullscreen preview code help'
  ],
  toolbar_mode: 'sliding',
  skin: isDark ? 'oxide-dark' : 'oxide',
  content_css: isDark ? 'dark' : 'default',
  content_style: 'body { font-family: Segoe UI, Arial, sans-serif; font-size: 13pt; line-height: 1.7; max-width: 860px; margin: 20px auto; padding: 0 20px; }',
  font_family_formats: 'Segoe UI=Segoe UI,sans-serif; Arial=arial,sans-serif; Georgia=georgia,serif; Courier New=courier new,monospace; Times New Roman=times new roman,serif',
  font_size_formats: '8pt 9pt 10pt 11pt 12pt 13pt 14pt 16pt 18pt 20pt 24pt 28pt 32pt 36pt',
  table_default_attributes: { border: '1' },
  table_default_styles: { 'border-collapse': 'collapse', 'width': '100%' },
  table_cell_styles: 'background-color forecolor border fontsize bold italic underline alignment',
  image_title: true,
  automatic_uploads: false,
  file_picker_types: 'image',
  file_picker_callback: function(cb, value, meta) {
    var input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');
    input.onchange = function() {
      var file = this.files[0];
      var reader = new FileReader();
      reader.onload = function() { cb(reader.result, { title: file.name }); };
      reader.readAsDataURL(file);
    };
    input.click();
  },
  promotion: false,
  branding: false,
  statusbar: true,
  resize: true,
  setup: function(editor) {
    // Auto-save draft every 60s
    var autoSaveTimer;
    editor.on('input keyup', function() {
      clearTimeout(autoSaveTimer);
      autoSaveTimer = setTimeout(function() {
        document.getElementById('save-status').textContent = 'Auto-saving…';
        saveDraft();
      }, 60000);
    });
    // Re-theme on toggle
    document.addEventListener('themeChanged', function(e) {
      // Reload TinyMCE with new skin
      var content = editor.getContent();
      tinymce.remove();
      isDark = e.detail.theme === 'dark';
      location.reload();
    });
  }
});

function saveDraft() {
  document.getElementById('doc-status').value = 'draft';
  submitEditorForm();
}
function savePublish() {
  document.getElementById('doc-status').value = 'published';
  submitEditorForm();
}
function submitEditorForm() {
  if (!document.getElementById('doc-title').value.trim()) {
    toast('Title is required.','error'); return;
  }
  document.getElementById('editor-content-hidden').value = tinymce.get('tinymce-editor').getContent();
  document.getElementById('editor-form').submit();
}
</script>

<?php else: // ── RICH DOCS LIST ── ?>

<div class="doc-filter">
  <form method="GET" class="doc-filter-left">
    <input type="hidden" name="tab" value="editor">
    <div class="search-box" style="min-width:180px;flex:1;max-width:260px">
      <span style="color:var(--text3)">🔍</span>
      <input type="text" name="q" placeholder="Search documents…" value="<?= h($search) ?>">
    </div>
  </form>
  <a href="documents.php?tab=editor&new_doc=1" class="btn btn-primary" style="flex-shrink:0;text-decoration:none">＋ <span>Create Document</span></a>
</div>

<?php if (empty($rich_docs)): ?>
<div class="card"><div class="empty-state"><div class="icon">✍</div><p>No rich documents yet. <a href="documents.php?tab=editor&new_doc=1" style="color:var(--orange)">Create your first document</a></p></div></div>
<?php else: ?>
<?php foreach ($rich_docs as $rd):
  $word_count = $rd['content'] ? str_word_count(strip_tags($rd['content'])) : 0;
  $sc_cls = $rd['status'] === 'published' ? 'spublished' : 'sdraft';
?>
<div class="rdoc-row">
  <span class="rdoc-icon">📝</span>
  <div class="rdoc-info">
    <div class="rdoc-title"><?= h($rd['title']) ?></div>
    <div class="rdoc-meta">
      <span class="badge <?= $sc_cls ?>" style="font-size:10px;padding:2px 7px;margin-right:6px"><?= ucfirst($rd['status']) ?></span>
      <?= h($rd['category']) ?>
      <?php if ($rd['proj_title']): ?> · 📁 <?= h($rd['proj_title']) ?><?php endif; ?>
      · <?= h($rd['author']) ?>
      · <?= number_format($word_count) ?> words
      · <?= date('M j, Y', strtotime($rd['updated_at'])) ?>
    </div>
  </div>
  <div class="rdoc-actions">
    <a href="documents.php?tab=editor&edit=<?= $rd['id'] ?>" class="btn btn-ghost btn-sm" title="Edit">✎ Edit</a>
    <div class="dropdown" style="display:inline-block">
      <button class="btn btn-ghost btn-sm" onclick="toggleDropdown('exp<?= $rd['id'] ?>')">↓ Export</button>
      <div class="dropdown-menu" id="exp<?= $rd['id'] ?>" style="right:0">
        <a class="dropdown-item" href="export_doc.php?id=<?= $rd['id'] ?>&format=pdf" target="_blank">📕 PDF</a>
        <a class="dropdown-item" href="export_doc.php?id=<?= $rd['id'] ?>&format=docx">📘 DOCX</a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="documents.php?print_doc=<?= $rd['id'] ?>" target="_blank">🖨 Print</a>
      </div>
    </div>
    <?php if (isManager() || $rd['created_by'] == $user['id']): ?>
    <form method="POST" onsubmit="return confirm('Delete document?')" style="display:inline">
      <input type="hidden" name="action" value="delete_rich">
      <input type="hidden" name="rdid" value="<?= $rd['id'] ?>">
      <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Delete">🗑</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; // end editor/list ?>
<?php endif; // end tabs ?>

<!-- ══ FILE UPLOAD MODAL ══ -->
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

<!-- ══ PDF VIEWER MODAL ══ -->
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