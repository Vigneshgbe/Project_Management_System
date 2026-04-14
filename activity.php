<?php
// activity.php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
requireRole(['admin']);
$db = getCRMDB();

$page = max(1,(int)($_GET['page']??1));
$per  = 50;
$off  = ($page-1)*$per;
$total = $db->query("SELECT COUNT(*) FROM activity_log")->fetch_row()[0];
$pages = ceil($total/$per);

$logs = $db->query("
  SELECT a.*, u.name AS uname FROM activity_log a
  LEFT JOIN users u ON u.id=a.user_id
  ORDER BY a.created_at DESC LIMIT $per OFFSET $off
")->fetch_all(MYSQLI_ASSOC);

renderLayout('Activity Log', 'activity');
?>
<div class="card">
  <div class="card-header" style="margin-bottom:16px">
    <div class="card-title">Activity Log</div>
    <span style="font-size:12px;color:var(--text3)"><?= number_format($total) ?> total entries</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td style="white-space:nowrap;font-size:12px;color:var(--text3)"><?= date('M j, Y g:ia',strtotime($l['created_at'])) ?></td>
          <td style="font-weight:600;color:var(--text);font-size:13px"><?= h($l['uname']??'System') ?></td>
          <td style="font-size:13px"><?= h($l['action']) ?></td>
          <td style="font-size:12.5px;color:var(--text2)"><?= h($l['entity_type']??'') ?> <?= $l['entity_id']?'#'.$l['entity_id']:'' ?></td>
          <td style="font-size:12px;color:var(--text3);max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($l['details']??'') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <div style="display:flex;justify-content:center;gap:6px;padding-top:16px">
    <?php for ($i=1;$i<=$pages;$i++): ?>
    <a href="?page=<?= $i ?>" style="padding:5px 11px;border-radius:6px;font-size:13px;background:<?= $i===$page?'var(--orange)':'var(--bg3)' ?>;color:<?= $i===$page?'#fff':'var(--text2)' ?>;border:1px solid var(--border)"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
<?php renderLayoutEnd(); ?>
