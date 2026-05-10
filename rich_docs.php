<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

// ── RICH DOC PRINT (full page HTML → print to PDF) ───────────────────────────
if (isset($_GET['print_doc'])) {
    $id = (int)$_GET['print_doc'];
    $rd = $db->query("SELECT r.*,u.name AS author FROM rich_docs r LEFT JOIN users u ON u.id=r.created_by WHERE r.id=$id")->fetch_assoc();
    if (!$rd) die('Document not found.');
    ?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <title><?= h($rd['title']) ?></title>
    <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Arial,sans-serif;font-size:13pt;color:#1a1a1a;background:#fff;padding:40px 60px;max-width:900px;margin:auto}
    h1{font-size:22pt;margin-bottom:6px}
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

// ── POST HANDLERS ────────────────────────────────────────────────────────────
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Only managers can create/edit rich docs
    if ($action === 'save_rich' && !isManager()) {
        flash('Only managers can create or edit documents.', 'error');
        ob_end_clean(); header('Location: rich_docs.php'); exit;
    }

    if ($action === 'save_rich') {
        $rdid    = (int)($_POST['rdid'] ?? 0);
        $title   = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $cat     = trim($_POST['category'] ?? 'General');
        $proj_id = (int)($_POST['project_id'] ?? 0) ?: null;
        $status  = $_POST['status'] ?? 'draft';
        if (!$title) { flash('Title required.','error'); ob_end_clean(); header('Location: rich_docs.php?new_doc=1'); exit; }
        if ($rdid) {
            // Extra check: only creator or manager can edit
            $rd_check = $db->query("SELECT created_by FROM rich_docs WHERE id=$rdid")->fetch_assoc();
            if (!$rd_check || (!isManager() && $rd_check['created_by'] != $uid)) {
                flash('Access denied.','error'); ob_end_clean(); header('Location: rich_docs.php'); exit;
            }
            $stmt = $db->prepare("UPDATE rich_docs SET title=?,content=?,category=?,project_id=?,status=?,updated_by=? WHERE id=?");
            $stmt->bind_param("ssssiii",$title,$content,$cat,$proj_id,$status,$uid,$rdid);
            $stmt->execute();
            flash('Document saved.','success');
            ob_end_clean(); header('Location: rich_docs.php?edit='.$rdid); exit;
        } else {
            $stmt = $db->prepare("INSERT INTO rich_docs (title,content,category,project_id,status,created_by,updated_by) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssii",$title,$content,$cat,$proj_id,$status,$uid,$uid);
            $stmt->execute();
            $new_id = $db->insert_id;
            flash('Document created.','success');
            ob_end_clean(); header('Location: rich_docs.php?edit='.$new_id); exit;
        }
    }

    if ($action === 'delete_rich') {
        $rdid = (int)$_POST['rdid'];
        $rd   = $db->query("SELECT * FROM rich_docs WHERE id=$rdid")->fetch_assoc();
        if ($rd && ($rd['created_by'] == $uid || isManager())) {
            $db->query("DELETE FROM rich_docs WHERE id=$rdid");
            flash('Document deleted.','success');
        }
        ob_end_clean(); header('Location: rich_docs.php'); exit;
    }
}
ob_end_clean();

// ── PARAMS ───────────────────────────────────────────────────────────────────
$edit_rdid = (int)($_GET['edit'] ?? 0);
$search    = trim($_GET['q'] ?? '');

// Rich docs: managers see all; members see published docs OR their own
$rw = "1=1";
if (!isManager()) {
    $rw = "(r.status='published' OR r.created_by=$uid)";
}
if ($search) $rw .= " AND r.title LIKE '%".$db->real_escape_string($search)."%'";

$rich_docs = $db->query("
    SELECT r.*, u.name AS author, p.title AS proj_title
    FROM rich_docs r
    LEFT JOIN users u ON u.id=r.created_by
    LEFT JOIN projects p ON p.id=r.project_id
    WHERE $rw ORDER BY r.updated_at DESC
")->fetch_all(MYSQLI_ASSOC);

$projects = $db->query("SELECT id,title FROM projects ORDER BY title")->fetch_all(MYSQLI_ASSOC);

$edit_rd = null;
$edit_attendee_ids = [];
if ($edit_rdid) {
    $edit_rd = $db->query("SELECT * FROM rich_docs WHERE id=$edit_rdid")->fetch_assoc();
    // Members can only open their own docs in editor
    if ($edit_rd && !isManager() && $edit_rd['created_by'] != $uid) {
        $edit_rd = null; // deny — will show list instead
        flash('You can only edit your own documents.','error');
    }
}

renderLayout('Rich Documents', 'documents');
?>

<style>
/* ── RICH DOCS PAGE ── */
.doc-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px}
.doc-tab{padding:10px 20px;font-size:13px;font-weight:600;color:var(--text3);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;text-decoration:none;white-space:nowrap}
.doc-tab:hover,.doc-tab.active{color:var(--orange);border-bottom-color:var(--orange)}
.doc-filter{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.doc-filter-left{display:flex;gap:8px;flex-wrap:wrap;align-items:center;flex:1}
.rdoc-row{display:flex;align-items:center;gap:14px;padding:13px 16px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:8px;transition:border-color .15s}
.rdoc-row:hover{border-color:var(--border2)}
.rdoc-icon{font-size:26px;flex-shrink:0}
.rdoc-info{flex:1;min-width:0}
.rdoc-title{font-size:14px;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:3px}
.rdoc-meta{font-size:11.5px;color:var(--text3)}
.rdoc-actions{display:flex;gap:6px;flex-shrink:0}
.sdraft{background:rgba(245,158,11,.15);color:#f59e0b}
.spublished{background:rgba(16,185,129,.15);color:#10b981}
.editor-wrap{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.editor-toolbar{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;background:var(--bg3)}
.editor-meta{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border);align-items:end}
.editor-body{padding:0}
@media(max-width:768px){.editor-meta{grid-template-columns:1fr 1fr}.doc-filter{flex-direction:column;align-items:stretch}}
@media(max-width:480px){.editor-meta{grid-template-columns:1fr}}
</style>

<!-- TABS: Files | Rich Documents (active) -->
<div class="doc-tabs">
    <a href="documents.php" class="doc-tab">📁 Files</a>
    <a href="rich_docs.php" class="doc-tab active">✍ Rich Documents</a>
</div>

<?php if ($edit_rd !== null || isset($_GET['new_doc'])): // ── EDITOR VIEW ── ?>

<?php
// Only managers can reach the editor
if (!isManager()) {
    flash('Only managers can create or edit rich documents.', 'error');
    header('Location: rich_docs.php'); exit;
}
$is_new = !$edit_rd;
?>

<div style="margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <a href="rich_docs.php" style="color:var(--text3);font-size:13px">← Back to Documents</a>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <?php if ($edit_rd): ?>
        <div class="dropdown" style="display:inline-block">
            <button class="btn btn-ghost btn-sm" onclick="toggleDropdown('export-dd-top')">↓ Export ▾</button>
            <div class="dropdown-menu" id="export-dd-top">
                <a class="dropdown-item" href="export_doc.php?id=<?= $edit_rd['id'] ?>&format=pdf" target="_blank">📕 Export as PDF</a>
                <a class="dropdown-item" href="export_doc.php?id=<?= $edit_rd['id'] ?>&format=docx">📘 Export as DOCX</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="rich_docs.php?print_doc=<?= $edit_rd['id'] ?>" target="_blank">🖨 Print Preview</a>
            </div>
        </div>
        <form method="POST" onsubmit="return confirm('Delete this document?')" style="display:inline">
            <input type="hidden" name="action" value="delete_rich">
            <input type="hidden" name="rdid" value="<?= $edit_rd['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">🗑 Delete</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<form method="POST" id="editor-form">
    <input type="hidden" name="action" value="save_rich">
    <input type="hidden" name="rdid" value="<?= $edit_rd ? $edit_rd['id'] : 0 ?>">
    <input type="hidden" name="content" id="editor-content-hidden">

    <div class="editor-wrap">
        <div class="editor-toolbar">
            <div style="font-family:var(--font-display);font-weight:700;font-size:15px">
                <?= $is_new ? '✍ New Document' : '✎ Editing Document' ?>
            </div>
            <div style="display:flex;gap:8px">
                <button type="button" class="btn btn-ghost btn-sm" onclick="saveDraft()">💾 Save Draft</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="savePublish()">✓ Save &amp; Publish</button>
            </div>
        </div>
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
        <div class="editor-body">
            <textarea id="tinymce-editor" name="content_raw"><?= htmlspecialchars($edit_rd['content'] ?? '') ?></textarea>
        </div>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js"></script>
<script>
var isDark = document.documentElement.getAttribute('data-theme') !== 'light';
tinymce.init({
    selector: '#tinymce-editor',
    height: 560,
    menubar: 'file edit view insert format tools table',
    plugins: ['advlist','autolink','lists','link','image','charmap','preview','anchor','searchreplace','visualblocks','code','fullscreen','insertdatetime','media','table','help','wordcount','codesample','emoticons','pagebreak','nonbreaking','quickbars'],
    toolbar: ['undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor','alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link image media','removeformat | codesample emoticons charmap | pagebreak | fullscreen preview code help'],
    toolbar_mode: 'sliding',
    skin: isDark ? 'oxide-dark' : 'oxide',
    content_css: isDark ? 'dark' : 'default',
    content_style: 'body { font-family: Segoe UI, Arial, sans-serif; font-size: 13pt; line-height: 1.7; max-width: 860px; margin: 20px auto; padding: 0 20px; }',
    font_family_formats: 'Segoe UI=Segoe UI,sans-serif; Arial=arial,sans-serif; Georgia=georgia,serif; Courier New=courier new,monospace',
    font_size_formats: '8pt 9pt 10pt 11pt 12pt 13pt 14pt 16pt 18pt 20pt 24pt 28pt 32pt 36pt',
    table_default_attributes: { border: '1' },
    table_default_styles: { 'border-collapse': 'collapse', 'width': '100%' },
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
    promotion: false, branding: false, statusbar: true, resize: true,
    setup: function(editor) {
        var autoSaveTimer;
        editor.on('input keyup', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                document.getElementById('save-status').textContent = 'Auto-saving…';
                saveDraft();
            }, 60000);
        });
        document.addEventListener('themeChanged', function(e) {
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
    if (!document.getElementById('doc-title').value.trim()) { toast('Title is required.','error'); return; }
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
    <?php if (isManager()): ?>
    <a href="rich_docs.php?new_doc=1" class="btn btn-primary" style="flex-shrink:0;text-decoration:none">＋ <span>Create Document</span></a>
    <?php endif; ?>
</div>

<?php if (empty($rich_docs)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon">✍</div>
        <p><?= isManager() ? '<a href="rich_docs.php?new_doc=1" style="color:var(--orange)">Create your first document</a>' : 'No published documents yet.' ?></p>
    </div>
</div>
<?php else: ?>
<?php foreach ($rich_docs as $rd):
    $word_count  = $rd['content'] ? str_word_count(strip_tags($rd['content'])) : 0;
    $sc_cls      = $rd['status'] === 'published' ? 'spublished' : 'sdraft';
    $can_edit    = isManager() || $rd['created_by'] == $uid;
    $can_delete  = isManager() || $rd['created_by'] == $uid;
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
        <?php if ($can_edit): ?>
        <a href="rich_docs.php?edit=<?= $rd['id'] ?>" class="btn btn-ghost btn-sm" title="Edit">✎ Edit</a>
        <?php else: ?>
        <!-- Members can only read published docs — no edit button -->
        <span class="btn btn-ghost btn-sm" style="opacity:.5;cursor:default">👁 Read Only</span>
        <?php endif; ?>
        <div class="dropdown" style="display:inline-block">
            <button class="btn btn-ghost btn-sm" onclick="toggleDropdown('exp<?= $rd['id'] ?>')">↓ Export</button>
            <div class="dropdown-menu" id="exp<?= $rd['id'] ?>" style="right:0">
                <a class="dropdown-item" href="export_doc.php?id=<?= $rd['id'] ?>&format=pdf" target="_blank">📕 PDF</a>
                <a class="dropdown-item" href="export_doc.php?id=<?= $rd['id'] ?>&format=docx">📘 DOCX</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="rich_docs.php?print_doc=<?= $rd['id'] ?>" target="_blank">🖨 Print</a>
            </div>
        </div>
        <?php if ($can_delete): ?>
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
<?php renderLayoutEnd(); ?>