<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
requireRole(['admin','manager']);
$db  = getCRMDB();

// ── DELETE HANDLERS (manager+ only) ──
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isManager()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_log') {
        $id = (int)($_POST['log_id'] ?? 0);
        if ($id) $db->query("DELETE FROM activity_log WHERE id=$id");
        flash('Log entry deleted.', 'success');
        ob_end_clean(); header('Location: activity.php?page='.($_GET['page']??1)); exit;
    }

    if ($action === 'bulk_delete') {
        $ids = array_filter(array_map('intval', explode(',', $_POST['log_ids'] ?? '')));
        if ($ids) { $db->query("DELETE FROM activity_log WHERE id IN(".implode(',',$ids).")"); }
        flash(count($ids).' log '.( count($ids)===1?'entry':'entries').' deleted.', 'success');
        ob_end_clean(); header('Location: activity.php?page='.($_GET['page']??1)); exit;
    }

    if ($action === 'delete_all') {
        $db->query("TRUNCATE TABLE activity_log");
        flash('Activity log cleared.', 'success');
        ob_end_clean(); header('Location: activity.php'); exit;
    }
}
ob_end_clean();

// ── PAGINATION ──
$page  = max(1, (int)($_GET['page'] ?? 1));
$per   = 50;
$off   = ($page - 1) * $per;
$total = (int)$db->query("SELECT COUNT(*) FROM activity_log")->fetch_row()[0];
$pages = (int)ceil($total / $per);

$logs = $db->query("
    SELECT a.*, u.name AS uname
    FROM activity_log a
    LEFT JOIN users u ON u.id = a.user_id
    ORDER BY a.created_at DESC
    LIMIT $per OFFSET $off
")->fetch_all(MYSQLI_ASSOC);

renderLayout('Activity Log', 'activity');
?>
<style>
.al-cb{width:15px;height:15px;accent-color:var(--orange);cursor:pointer}
#bulk-bar{display:none;align-items:center;gap:10px;padding:10px 14px;background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.25);border-radius:var(--radius-sm);margin-bottom:12px}
#bulk-bar.show{display:flex}
.del-btn{background:none;border:none;cursor:pointer;color:var(--text3);font-size:14px;padding:3px 6px;border-radius:4px;line-height:1;transition:color .15s,background .15s}
.del-btn:hover{color:var(--red);background:rgba(239,68,68,.1)}
</style>

<div class="card">
  <div class="card-header" style="margin-bottom:16px">
    <div class="card-title">Activity Log</div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <span style="font-size:12px;color:var(--text3)"><?= number_format($total) ?> total entries</span>
      <?php if (isManager()): ?>
      <button onclick="toggleAll()" id="sel-all-btn" class="btn btn-ghost btn-sm" style="font-size:12px">☐ Select all</button>
      <button onclick="confirmClearAll()" class="btn btn-sm" style="font-size:12px;background:rgba(239,68,68,.1);color:var(--red);border:1px solid rgba(239,68,68,.25)">🗑 Clear all</button>
      <?php endif; ?>
    </div>
  </div>

  <?php if (isManager()): ?>
  <div id="bulk-bar">
    <span id="bulk-count" style="font-size:13px;font-weight:600;color:var(--orange)">0 selected</span>
    <form method="POST" id="bulk-form">
      <input type="hidden" name="action"   value="bulk_delete">
      <input type="hidden" name="log_ids"  id="bulk-ids">
      <button type="button" onclick="confirmBulkDelete()" class="btn btn-sm" style="background:var(--red);color:#fff;border:none">🗑 Delete selected</button>
    </form>
    <button onclick="clearSel()" class="btn btn-ghost btn-sm" style="font-size:12px">✕ Cancel</button>
  </div>
  <?php endif; ?>

  <div class="table-wrap">
    <table>
      <thead><tr>
        <?php if (isManager()): ?><th style="width:32px"></th><?php endif; ?>
        <th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th>
        <?php if (isManager()): ?><th style="width:40px"></th><?php endif; ?>
      </tr></thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
        <tr id="row-<?= $l['id'] ?>">
          <?php if (isManager()): ?>
          <td><input type="checkbox" class="al-cb" value="<?= $l['id'] ?>" onchange="updateBar()"></td>
          <?php endif; ?>
          <td style="white-space:nowrap;font-size:12px;color:var(--text3)"><?= date('M j, Y g:ia', strtotime($l['created_at'])) ?></td>
          <td style="font-weight:600;color:var(--text);font-size:13px"><?= h($l['uname'] ?? 'System') ?></td>
          <td style="font-size:13px"><?= h($l['action']) ?></td>
          <td style="font-size:12.5px;color:var(--text2)"><?= h($l['entity_type'] ?? '') ?><?= $l['entity_id'] ? ' #'.$l['entity_id'] : '' ?></td>
          <td style="font-size:12px;color:var(--text3);max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($l['details'] ?? '') ?></td>
          <?php if (isManager()): ?>
          <td>
            <form method="POST" onsubmit="return confirm('Delete this entry?')" style="margin:0">
              <input type="hidden" name="action"  value="delete_log">
              <input type="hidden" name="log_id"  value="<?= $l['id'] ?>">
              <button type="submit" class="del-btn" title="Delete">🗑</button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
  <div style="display:flex;justify-content:center;gap:6px;padding-top:16px;flex-wrap:wrap">
    <?php for ($i=1;$i<=$pages;$i++): ?>
    <a href="?page=<?=$i?>" style="padding:5px 11px;border-radius:6px;font-size:13px;text-decoration:none;background:<?=$i===$page?'var(--orange)':'var(--bg3)'?>;color:<?=$i===$page?'#fff':'var(--text2)'?>;border:1px solid var(--border)"><?=$i?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php if (isManager()): ?>
<form method="POST" id="clear-all-form" style="display:none">
  <input type="hidden" name="action" value="delete_all">
</form>
<script>
function updateBar(){
  var cbs=document.querySelectorAll('.al-cb:checked');
  var all=document.querySelectorAll('.al-cb');
  var bar=document.getElementById('bulk-bar');
  document.getElementById('bulk-count').textContent=cbs.length+' selected';
  bar.classList.toggle('show',cbs.length>0);
  document.getElementById('sel-all-btn').textContent=(cbs.length===all.length?'☑':'☐')+' Select all';
}
function toggleAll(){
  var all=document.querySelectorAll('.al-cb');
  var sel=document.querySelectorAll('.al-cb:checked').length<all.length;
  all.forEach(function(c){c.checked=sel;}); updateBar();
}
function clearSel(){
  document.querySelectorAll('.al-cb').forEach(function(c){c.checked=false;}); updateBar();
}
function confirmBulkDelete(){
  var ids=Array.from(document.querySelectorAll('.al-cb:checked')).map(function(c){return c.value;});
  if(!ids.length)return;
  if(!confirm('Delete '+ids.length+' log '+(ids.length===1?'entry':'entries')+'? Cannot be undone.'))return;
  document.getElementById('bulk-ids').value=ids.join(',');
  document.getElementById('bulk-form').submit();
}
function confirmClearAll(){
  if(!confirm('Clear the ENTIRE activity log? This cannot be undone.'))return;
  document.getElementById('clear-all-form').submit();
}
</script>
<?php endif; ?>

<?php renderLayoutEnd(); ?>