<?php
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
requireRole(['admin','manager']); // Members cannot access lead pipeline
$db = getCRMDB();
$user = currentUser();

$STAGES = ['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','proposal'=>'Proposal','negotiation'=>'Negotiation','won'=>'Won','lost'=>'Lost'];
$STAGE_COLORS = ['new'=>'#6366f1','contacted'=>'#f59e0b','qualified'=>'#8b5cf6','proposal'=>'#f97316','negotiation'=>'#14b8a6','won'=>'#10b981','lost'=>'#ef4444'];
$SOURCES = ['website'=>'Website','referral'=>'Referral','social'=>'Social Media','cold_outreach'=>'Cold Outreach','event'=>'Event','other'=>'Other'];

// ── POST HANDLERS ──
ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $company  = trim($_POST['company'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $source   = $_POST['source'] ?? 'other';
        $interest = trim($_POST['service_interest'] ?? '');
        $budget   = $_POST['budget_est'] !== '' ? (float)$_POST['budget_est'] : null;
        $bcur     = $_POST['budget_currency'] ?? 'LKR';
        $stage    = $_POST['stage'] ?? 'new';
        $prio     = $_POST['priority'] ?? 'medium';
        $close    = $_POST['expected_close'] ?: null;
        $last_c   = $_POST['last_contact'] ?: null;
        $notes    = trim($_POST['notes'] ?? '');
        $loss_r   = trim($_POST['loss_reason'] ?? '');
        $assign   = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $uid      = $user['id'];
        if (!$name) { flash('Name required.','error'); ob_end_clean(); header('Location: leads.php'); exit; }
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO leads (name,company,email,phone,source,service_interest,budget_est,budget_currency,stage,priority,expected_close,last_contact,notes,loss_reason,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssdssssssii",$name,$company,$email,$phone,$source,$interest,$budget,$bcur,$stage,$prio,$close,$last_c,$notes,$loss_r,$assign,$uid);
            $stmt->execute();
            $lid = $db->insert_id;
            logActivity('created lead',$name,$lid);
            flash('Lead created.','success');
        } else {
            $stmt = $db->prepare("UPDATE leads SET name=?,company=?,email=?,phone=?,source=?,service_interest=?,budget_est=?,budget_currency=?,stage=?,priority=?,expected_close=?,last_contact=?,notes=?,loss_reason=?,assigned_to=? WHERE id=?");
            $stmt->bind_param("ssssssdssssssii",$name,$company,$email,$phone,$source,$interest,$budget,$bcur,$stage,$prio,$close,$last_c,$notes,$loss_r,$assign,$id);
            $stmt->execute();
            logActivity('updated lead',$name,$id);
            flash('Lead updated.','success');
        }
        ob_end_clean(); header('Location: leads.php'); exit;
    }

    if ($action === 'stage_update') {
        $id    = (int)$_POST['id'];
        $stage = $db->real_escape_string($_POST['stage']);
        $db->query("UPDATE leads SET stage='$stage',updated_at=NOW() WHERE id=$id");
        ob_end_clean(); echo json_encode(['ok'=>true]); exit;
    }

    if ($action === 'add_activity') {
        header('Content-Type: application/json');
        $lid   = (int)$_POST['lead_id'];
        $type  = $db->real_escape_string($_POST['activity_type'] ?? 'note');
        $desc  = trim($_POST['description'] ?? '');
        $date  = $_POST['activity_date'] ?: date('Y-m-d H:i:s');
        $uid   = $user['id'];
        if ($lid && $desc) {
            $stmt = $db->prepare("INSERT INTO lead_activities (lead_id,user_id,activity_type,description,activity_date) VALUES (?,?,?,?,?)");
            $stmt->bind_param("iisss",$lid,$uid,$type,$desc,$date);
            $stmt->execute();
            ob_end_clean(); echo json_encode(['ok'=>true,'id'=>$db->insert_id]); exit;
        }
        ob_end_clean(); echo json_encode(['ok'=>false]); exit;
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->query("DELETE FROM leads WHERE id=$id");
        flash('Lead deleted.','success');
        ob_end_clean(); header('Location: leads.php'); exit;
    }
}
ob_end_clean();

$view_id  = (int)($_GET['view'] ?? 0);
$edit_id  = (int)($_GET['edit'] ?? 0);
$stage_f  = $_GET['stage'] ?? '';
$search   = trim($_GET['q'] ?? '');
$view_mode = $_GET['mode'] ?? 'kanban'; // kanban or list

$where = "1=1";
if ($stage_f) $where .= " AND l.stage='".$db->real_escape_string($stage_f)."'";
if ($search) $where .= " AND (l.name LIKE '%".$db->real_escape_string($search)."%' OR l.company LIKE '%".$db->real_escape_string($search)."%')";

$leads = $db->query("
    SELECT l.*, u.name AS assignee_name
    FROM leads l
    LEFT JOIN users u ON u.id=l.assigned_to
    WHERE $where ORDER BY FIELD(l.priority,'urgent','high','medium','low'), l.updated_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Stage counts
$stage_counts = [];
foreach ($STAGES as $k=>$v) {
    $stage_counts[$k] = $db->query("SELECT COUNT(*) FROM leads WHERE stage='$k'")->fetch_row()[0];
}

// Pipeline value
$pipeline_val = $db->query("SELECT COALESCE(SUM(budget_est),0) FROM leads WHERE stage NOT IN ('won','lost')")->fetch_row()[0];
$won_val      = $db->query("SELECT COALESCE(SUM(budget_est),0) FROM leads WHERE stage='won'")->fetch_row()[0];
$total_leads  = array_sum($stage_counts);

$all_users = $db->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$edit_lead = null;
if ($edit_id) $edit_lead = $db->query("SELECT * FROM leads WHERE id=$edit_id")->fetch_assoc();

// Single lead view
$single = null; $activities = [];
if ($view_id) {
    $single = $db->query("SELECT l.*,u.name AS assignee_name FROM leads l LEFT JOIN users u ON u.id=l.assigned_to WHERE l.id=$view_id")->fetch_assoc();
    if ($single) {
        $activities = $db->query("SELECT la.*,u.name AS uname FROM lead_activities la JOIN users u ON u.id=la.user_id WHERE la.lead_id=$view_id ORDER BY la.activity_date DESC")->fetch_all(MYSQLI_ASSOC);
    }
}

renderLayout('Leads Pipeline', 'leads');
?>

<style>
/* ── LEADS PAGE ── */
.leads-controls{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.leads-controls-left{display:flex;gap:8px;flex-wrap:wrap;align-items:center;flex:1;min-width:0}
.view-toggle{display:flex;border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden}
.view-toggle a{padding:6px 14px;font-size:12.5px;font-weight:600;color:var(--text2);background:var(--bg3);transition:background .15s,color .15s;white-space:nowrap}
.view-toggle a.active{background:var(--orange);color:#fff}

/* ── KANBAN ── */
.kanban-wrap{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;padding-bottom:16px}
.kanban-board{display:flex;gap:12px;min-width:max-content;align-items:flex-start}
.kanban-col{
  width:220px;flex-shrink:0;
  background:var(--bg2);border:1px solid var(--border);
  border-top:3px solid var(--kcol);border-radius:var(--radius);
  display:flex;flex-direction:column;
}
.kanban-col-header{
  padding:10px 12px;border-bottom:1px solid var(--border);
  display:flex;justify-content:space-between;align-items:center
}
.kanban-col-title{font-size:12.5px;font-weight:700;color:var(--kcol)}
.kanban-col-badge{
  background:var(--kcol);color:#fff;
  font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px
}
.kanban-col-body{
  padding:8px;display:flex;flex-direction:column;gap:7px;
  min-height:120px;max-height:calc(100vh - 320px);overflow-y:auto
}
.kanban-empty{
  text-align:center;padding:22px 8px;font-size:12px;
  color:var(--text3);border:1px dashed var(--border);
  border-radius:8px;margin:0
}
.kanban-col-footer{
  padding:7px 12px;border-top:1px solid var(--border);
  font-size:11px;color:var(--text3);text-align:right
}

/* ── LEAD CARD ── */
.kcard{
  background:var(--bg3);border:1px solid var(--border);
  border-radius:8px;padding:11px 12px;cursor:pointer;
  transition:border-color .15s,transform .1s;
}
.kcard:hover{border-color:var(--border2);transform:translateY(-1px)}
.kcard:active{transform:scale(.98)}
.kcard-name{font-weight:700;font-size:13px;color:var(--text);margin-bottom:2px;line-height:1.3}
.kcard-company{font-size:11.5px;color:var(--text2);margin-bottom:6px}
.kcard-divider{height:1px;background:var(--border);margin:7px 0}
.kcard-footer{display:flex;align-items:center;justify-content:space-between;gap:4px;flex-wrap:wrap}
.kcard-prio{font-size:10px;font-weight:600;padding:2px 6px;border-radius:4px}
.kcard-budget{font-size:10px;font-weight:700;color:var(--green)}
.kcard-close{font-size:10px;color:var(--text3)}
.kcard-assignee{font-size:10.5px;color:var(--text3);margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* ── ACTIVITY ── */
.activity-item{background:var(--bg3);border-radius:8px;padding:10px 12px;margin-bottom:8px}
.act-type{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--orange)}

/* ── SINGLE LEAD ── */
.lead-detail-grid{display:grid;grid-template-columns:2fr 1fr;gap:18px;align-items:start}
.lead-meta-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
.lead-meta-box{background:var(--bg3);border-radius:8px;padding:10px}
.lead-meta-lbl{font-size:10px;color:var(--text3);text-transform:uppercase;margin-bottom:3px}
.lead-meta-val{font-size:13px;color:var(--text)}

/* ── MOBILE ── */
@media(max-width:1100px){
  .kanban-col{width:200px}
}
@media(max-width:900px){
  /* On mobile, kanban scrolls horizontally — cols stay fixed width */
  .kanban-col{width:180px}
  .lead-detail-grid{grid-template-columns:1fr}
  .lead-meta-grid{grid-template-columns:1fr 1fr}
  .leads-controls{flex-direction:column;align-items:stretch}
  .leads-controls-left{justify-content:flex-start}
}
@media(max-width:600px){
  .kanban-col{width:160px}
  .lead-meta-grid{grid-template-columns:1fr 1fr}
}
@media(max-width:480px){
  .kanban-col{width:150px}
  .kcard-name{font-size:12px}
}
/* Mobile: stack kanban vertically if user prefers */
@media(max-width:480px){
  .kanban-board{min-width:unset;flex-direction:column}
  .kanban-col{width:100%}
  .kanban-col-body{max-height:none}
}
</style>

<?php if ($single): // ═══════════════════════════════ SINGLE LEAD VIEW ?>

<div style="margin-bottom:16px"><a href="leads.php" style="color:var(--text3);font-size:13px">← Back to Pipeline</a></div>

<div class="lead-detail-grid">
  <div>
    <div class="card" style="margin-bottom:16px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px">
        <div>
          <div style="font-family:var(--font-display);font-size:20px;font-weight:700;margin-bottom:6px"><?= h($single['name']) ?></div>
          <?php if ($single['company']): ?><div style="font-size:13px;color:var(--text2);margin-bottom:8px">🏢 <?= h($single['company']) ?></div><?php endif; ?>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php $sc=$STAGE_COLORS[$single['stage']]??'#94a3b8'; ?>
            <span class="badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><?= $STAGES[$single['stage']] ?></span>
            <span class="badge" style="background:var(--bg3);color:var(--text2)"><?= $SOURCES[$single['source']]??$single['source'] ?></span>
            <span style="font-size:12px"><?= priorityIcon($single['priority']) ?> <?= ucfirst($single['priority']) ?></span>
          </div>
        </div>
        <div style="display:flex;gap:8px">
          <a href="leads.php?edit=<?= $single['id'] ?>" class="btn btn-ghost btn-sm">✎ Edit</a>
          <form method="POST" onsubmit="return confirm('Delete lead?')">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $single['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">🗑</button>
          </form>
        </div>
      </div>

      <div class="lead-meta-grid">
        <?php foreach ([
          ['Email',$single['email']?'<a href="mailto:'.h($single['email']).'">'.h($single['email']).'</a>':'—'],
          ['Phone',$single['phone']?h($single['phone']):'—'],
          ['Budget',$single['budget_est']?h($single['budget_currency']).' '.number_format($single['budget_est'],0):'Not specified'],
          ['Assigned',h($single['assignee_name']??'Unassigned')],
          ['Expected Close',$single['expected_close']?fDate($single['expected_close']):'—'],
          ['Last Contact',$single['last_contact']?fDate($single['last_contact']):'—'],
        ] as [$lbl,$val]): ?>
        <div class="lead-meta-box">
          <div class="lead-meta-lbl"><?= $lbl ?></div>
          <div class="lead-meta-val"><?= $val ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if ($single['service_interest']): ?>
      <div style="margin-bottom:12px">
        <div style="font-size:11px;font-weight:600;color:var(--text3);margin-bottom:4px;text-transform:uppercase">Service Interest</div>
        <div style="font-size:13.5px;color:var(--text2)"><?= h($single['service_interest']) ?></div>
      </div>
      <?php endif; ?>
      <?php if ($single['notes']): ?>
      <div style="margin-bottom:12px">
        <div style="font-size:11px;font-weight:600;color:var(--text3);margin-bottom:4px;text-transform:uppercase">Notes</div>
        <div style="font-size:13.5px;color:var(--text2);line-height:1.6"><?= nl2br(h($single['notes'])) ?></div>
      </div>
      <?php endif; ?>
      <?php if ($single['stage']==='lost' && $single['loss_reason']): ?>
      <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:12px">
        <div style="font-size:11px;font-weight:600;color:var(--red);margin-bottom:3px">LOSS REASON</div>
        <div style="font-size:13px;color:var(--text2)"><?= nl2br(h($single['loss_reason'])) ?></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Activities -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Activity Log</div>
        <button class="btn btn-primary btn-sm" onclick="openModal('modal-activity')">＋ Log Activity</button>
      </div>
      <?php if (empty($activities)): ?>
      <div class="empty-state"><div class="icon">📋</div><p>No activities yet.</p></div>
      <?php else: ?>
      <?php
      $act_icons=['call'=>'📞','email'=>'📧','meeting'=>'🤝','note'=>'📝','proposal'=>'📄','follow_up'=>'🔔'];
      foreach ($activities as $a): ?>
      <div class="activity-item">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:5px">
          <div>
            <span class="act-type"><?= $act_icons[$a['activity_type']]??'📌' ?> <?= h(str_replace('_',' ',$a['activity_type'])) ?></span>
            <span style="font-size:12px;color:var(--text2);margin-left:8px">by <?= h($a['uname']) ?></span>
          </div>
          <span style="font-size:11px;color:var(--text3)"><?= date('M j, Y g:ia',strtotime($a['activity_date'])) ?></span>
        </div>
        <div style="font-size:13px;color:var(--text);line-height:1.5"><?= nl2br(h($a['description'])) ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right: Quick stage update -->
  <div class="card">
    <div class="card-title" style="margin-bottom:14px">Update Stage</div>
    <div style="display:flex;flex-direction:column;gap:6px">
      <?php foreach ($STAGES as $k=>$v):
        $active = $single['stage']===$k;
        $sc = $STAGE_COLORS[$k];
      ?>
      <form method="POST" style="display:block">
        <input type="hidden" name="action" value="create"><!-- reuse edit -->
        <button type="button" class="btn <?= $active?'btn-primary':'btn-ghost' ?>" style="width:100%;justify-content:flex-start;<?= $active?"background:{$sc};border-color:{$sc}":'' ?>"
          onclick="updateStage(<?= $single['id'] ?>,'<?= $k ?>')">
          <?= $v ?>
          <?php if ($active): ?> ✓<?php endif; ?>
        </button>
      </form>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Activity Modal -->
<div class="modal-overlay" id="modal-activity">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title">Log Activity</div>
      <button class="modal-close" onclick="closeModal('modal-activity')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Activity Type</label>
        <select id="act-type" class="form-control">
          <option value="call">📞 Call</option>
          <option value="email">📧 Email</option>
          <option value="meeting">🤝 Meeting</option>
          <option value="note" selected>📝 Note</option>
          <option value="proposal">📄 Proposal Sent</option>
          <option value="follow_up">🔔 Follow Up</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Date & Time</label>
        <input type="datetime-local" id="act-date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Description *</label>
        <textarea id="act-desc" class="form-control" placeholder="What happened? Key points discussed…"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-ghost" onclick="closeModal('modal-activity')">Cancel</button>
      <button type="button" class="btn btn-primary" onclick="saveActivity(<?= $single['id'] ?>)">Log Activity</button>
    </div>
  </div>
</div>

<script>
function updateStage(id, stage){
  fetch('leads.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=stage_update&id=${id}&stage=${stage}`})
    .then(r=>r.json()).then(()=>{toast('Stage updated','success');setTimeout(()=>location.reload(),600)});
}
function saveActivity(lid){
  const desc=document.getElementById('act-desc').value.trim();
  if(!desc){toast('Description required','error');return;}
  const type=document.getElementById('act-type').value;
  const date=document.getElementById('act-date').value;
  fetch('leads.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=add_activity&lead_id=${lid}&activity_type=${type}&description=${encodeURIComponent(desc)}&activity_date=${encodeURIComponent(date)}`
  }).then(r=>r.json()).then(d=>{if(d.ok){closeModal('modal-activity');toast('Activity logged','success');setTimeout(()=>location.reload(),700)}});
}
</script>

<?php else: // ═══════════════════════════════════════ PIPELINE LIST/KANBAN VIEW ?>

<!-- KPI strip -->
<div class="stats-grid" style="margin-bottom:18px">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(99,102,241,.12)">🎯</div>
    <div><div class="stat-val"><?= $total_leads ?></div><div class="stat-lbl">Total Leads</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(16,185,129,.12)">🏆</div>
    <div><div class="stat-val"><?= $stage_counts['won'] ?></div><div class="stat-lbl">Won</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(239,68,68,.12)">❌</div>
    <div><div class="stat-val"><?= $stage_counts['lost'] ?></div><div class="stat-lbl">Lost</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,158,11,.12)">🔥</div>
    <div><div class="stat-val"><?= ($stage_counts['proposal']??0)+($stage_counts['negotiation']??0) ?></div><div class="stat-lbl">Hot Leads</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(249,115,22,.12)">💰</div>
    <div>
      <div class="stat-val"><?= $pipeline_val>0?number_format($pipeline_val/1000,1).'K':'0' ?></div>
      <div class="stat-lbl">Pipeline Value</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(16,185,129,.12)">✅</div>
    <div>
      <div class="stat-val"><?= $won_val>0?number_format($won_val/1000,1).'K':'0' ?></div>
      <div class="stat-lbl">Won Value</div>
    </div>
  </div>
</div>

<!-- Controls -->
<div class="leads-controls">
  <form method="GET" class="leads-controls-left" id="leads-filter-form">
    <div class="search-box" style="min-width:180px;flex:1;max-width:280px">
      <span style="color:var(--text3);font-size:13px">🔍</span>
      <input type="text" name="q" placeholder="Search leads…" value="<?= h($search) ?>">
    </div>
    <select name="stage" class="form-control" style="width:auto;max-width:160px" onchange="this.form.submit()">
      <option value="">All Stages</option>
      <?php foreach ($STAGES as $k=>$v): ?>
      <option value="<?= $k ?>" <?= $stage_f===$k?'selected':'' ?>><?= $v ?> (<?= $stage_counts[$k] ?>)</option>
      <?php endforeach; ?>
    </select>
    <div class="view-toggle">
      <a href="?mode=kanban<?= $stage_f?"&stage=$stage_f":'' ?><?= $search?"&q=".urlencode($search):'' ?>"
         class="<?= $view_mode==='kanban'?'active':'' ?>">Kanban</a>
      <a href="?mode=list<?= $stage_f?"&stage=$stage_f":'' ?><?= $search?"&q=".urlencode($search):'' ?>"
         class="<?= $view_mode==='list'?'active':'' ?>">List</a>
    </div>
  </form>
  <button class="btn btn-primary" onclick="openModal('modal-lead')" style="white-space:nowrap">
    ＋ <span>New Lead</span>
  </button>
</div>

<?php if ($view_mode === 'kanban'): // ── KANBAN ── ?>
<div class="kanban-wrap">
<div class="kanban-board">
  <?php foreach ($STAGES as $stage_key=>$stage_label):
    $col_leads = array_values(array_filter($leads, fn($l)=>$l['stage']===$stage_key));
    $sc = $STAGE_COLORS[$stage_key];
    $col_val = array_sum(array_column($col_leads,'budget_est'));
  ?>
  <div class="kanban-col" style="--kcol:<?= $sc ?>">
    <div class="kanban-col-header">
      <span class="kanban-col-title"><?= $stage_label ?></span>
      <span class="kanban-col-badge"><?= count($col_leads) ?></span>
    </div>
    <div class="kanban-col-body">
      <?php if (empty($col_leads)): ?>
      <div class="kanban-empty">No leads</div>
      <?php endif; ?>
      <?php foreach ($col_leads as $l):
        $prio_colors=['urgent'=>'rgba(239,68,68,.15)','high'=>'rgba(249,115,22,.15)','medium'=>'rgba(245,158,11,.12)','low'=>'rgba(16,185,129,.12)'];
        $prio_text=['urgent'=>'#ef4444','high'=>'#f97316','medium'=>'#f59e0b','low'=>'#10b981'];
        $pc_bg = $prio_colors[$l['priority']]??'rgba(99,102,241,.1)';
        $pc_tx = $prio_text[$l['priority']]??'#6366f1';
      ?>
      <div class="kcard" onclick="location.href='leads.php?view=<?= $l['id'] ?>'">
        <div class="kcard-name"><?= h($l['name']) ?></div>
        <?php if ($l['company']): ?>
        <div class="kcard-company">🏢 <?= h($l['company']) ?></div>
        <?php endif; ?>
        <div class="kcard-divider"></div>
        <div class="kcard-footer">
          <span class="kcard-prio" style="background:<?= $pc_bg ?>;color:<?= $pc_tx ?>">
            <?= priorityIcon($l['priority']) ?> <?= ucfirst($l['priority']) ?>
          </span>
          <?php if ($l['budget_est']): ?>
          <span class="kcard-budget">💰<?= number_format($l['budget_est'],0) ?></span>
          <?php endif; ?>
        </div>
        <?php if ($l['expected_close']): ?>
        <div class="kcard-close" style="margin-top:5px">📅 <?= fDate($l['expected_close'],'M j, Y') ?></div>
        <?php endif; ?>
        <?php if ($l['assignee_name']): ?>
        <div class="kcard-assignee">👤 <?= h($l['assignee_name']) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if ($col_val > 0): ?>
    <div class="kanban-col-footer">💰 <?= number_format($col_val,2) ?></div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
</div>

<?php else: // ── LIST VIEW ── ?>
<?php if (empty($leads)): ?>
<div class="card"><div class="empty-state"><div class="icon">🎯</div><p>No leads found.</p></div></div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>Name</th><th>Company</th><th>Source</th><th>Stage</th><th>Priority</th>
        <th>Budget</th><th>Close Date</th><th>Assigned</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach ($leads as $l):
          $sc=$STAGE_COLORS[$l['stage']]??'#94a3b8';
        ?>
        <tr>
          <td class="td-main" onclick="location.href='leads.php?view=<?= $l['id'] ?>'" style="cursor:pointer"><?= h($l['name']) ?></td>
          <td><?= h($l['company']??'—') ?></td>
          <td style="font-size:12px"><?= $SOURCES[$l['source']]??h($l['source']) ?></td>
          <td><span class="badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><?= $STAGES[$l['stage']] ?></span></td>
          <td><?= priorityIcon($l['priority']) ?> <?= ucfirst($l['priority']) ?></td>
          <td style="text-align:right"><?= $l['budget_est'] ? h($l['budget_currency']).' '.number_format($l['budget_est'],0) : '—' ?></td>
          <td style="font-size:12px"><?= fDate($l['expected_close']) ?></td>
          <td style="font-size:12.5px"><?= h($l['assignee_name']??'—') ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="leads.php?view=<?= $l['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="View">👁</a>
              <a href="leads.php?edit=<?= $l['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit">✎</a>
              <form method="POST" onsubmit="return confirm('Delete?')">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $l['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon">✕</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php endif; // end kanban/list ?>

<!-- CREATE/EDIT LEAD MODAL -->
<div class="modal-overlay <?= $edit_id?'open':'' ?>" id="modal-lead">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><?= $edit_id?'Edit Lead':'New Lead' ?></div>
      <button class="modal-close" onclick="closeModal('modal-lead');<?= $edit_id?"location.href='leads.php'":'' ?>">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="<?= $edit_id?'edit':'create' ?>">
      <?php if ($edit_id): ?><input type="hidden" name="id" value="<?= $edit_id ?>"><?php endif; ?>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= h($edit_lead['name']??'') ?>" placeholder="Contact person name">
          </div>
          <div class="form-group">
            <label class="form-label">Company</label>
            <input type="text" name="company" class="form-control" value="<?= h($edit_lead['company']??'') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= h($edit_lead['email']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= h($edit_lead['phone']??'') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Source</label>
            <select name="source" class="form-control">
              <?php foreach ($SOURCES as $k=>$v): ?>
              <option value="<?= $k ?>" <?= ($edit_lead['source']??'other')===$k?'selected':'' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Stage</label>
            <select name="stage" class="form-control">
              <?php foreach ($STAGES as $k=>$v): ?>
              <option value="<?= $k ?>" <?= ($edit_lead['stage']??'new')===$k?'selected':'' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Service Interest</label>
          <input type="text" name="service_interest" class="form-control" value="<?= h($edit_lead['service_interest']??'') ?>" placeholder="e.g. Website, Branding, Digital Marketing">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Budget Estimate</label>
            <input type="number" name="budget_est" step="0.01" class="form-control" value="<?= h($edit_lead['budget_est']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Currency</label>
            <select name="budget_currency" class="form-control">
              <?php foreach (['LKR','INR','USD','EUR'] as $c): ?>
              <option <?= ($edit_lead['budget_currency']??'LKR')===$c?'selected':'' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Priority</label>
            <select name="priority" class="form-control">
              <?php foreach (['low','medium','high','urgent'] as $p): ?>
              <option <?= ($edit_lead['priority']??'medium')===$p?'selected':'' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Assign To</label>
            <select name="assigned_to" class="form-control">
              <option value="">— Unassigned —</option>
              <?php foreach ($all_users as $u): ?>
              <option value="<?= $u['id'] ?>" <?= ($edit_lead['assigned_to']??'')==$u['id']?'selected':'' ?>><?= h($u['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Expected Close</label>
            <input type="date" name="expected_close" class="form-control" value="<?= h($edit_lead['expected_close']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Last Contact</label>
            <input type="date" name="last_contact" class="form-control" value="<?= h($edit_lead['last_contact']??'') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control"><?= h($edit_lead['notes']??'') ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Loss Reason (if lost)</label>
          <textarea name="loss_reason" class="form-control" style="min-height:60px"><?= h($edit_lead['loss_reason']??'') ?></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-lead');<?= $edit_id?"location.href='leads.php'":'' ?>">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= $edit_id?'Save Changes':'Create Lead' ?></button>
      </div>
    </form>
  </div>
</div>
<?php if ($edit_id): ?><script>document.addEventListener('DOMContentLoaded',()=>openModal('modal-lead'))</script><?php endif; ?>

<?php endif; // end pipeline view ?>
<?php renderLayoutEnd(); ?>