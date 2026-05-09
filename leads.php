<?php
require_once 'config.php';
require_once 'includes/layout.php';
require_once 'includes/attach_widget.php';
requireLogin();
// requireRole(['admin','manager']);

// Tele-caller interns + all general members/managers/admins can access leads
if (!isManager() && !deptCan(['tele_caller','general'])) {
    header('Location: mywork.php'); exit;
}

$db = getCRMDB();
$user = currentUser();

$STAGES = ['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','proposal'=>'Proposal','negotiation'=>'Negotiation','won'=>'Won','lost'=>'Lost'];
$STAGE_COLORS = ['new'=>'#6366f1','contacted'=>'#f59e0b','qualified'=>'#8b5cf6','proposal'=>'#f97316','negotiation'=>'#14b8a6','won'=>'#10b981','lost'=>'#ef4444'];
$SOURCES = ['website'=>'Website','referral'=>'Referral','social'=>'Social Media','cold_outreach'=>'Cold Outreach','event'=>'Event','other'=>'Other'];

ob_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';
    $intern_allowed = ['stage_update', 'add_activity'];
    if (!isManager()) {
        // Interns can edit only their own assigned leads; block create/delete
        if ($post_action === 'edit') {
            $check_id  = (int)($_POST['id'] ?? 0);
            $uid_check = (int)$user['id'];
            $owned = $db->query("SELECT id FROM leads WHERE id=$check_id AND assigned_to=$uid_check")->fetch_row();
            if (!$owned) { ob_end_clean(); header('Location: leads.php'); exit; }
            // Allow edit to proceed — fall through to the edit handler below
        } elseif (!in_array($post_action, $intern_allowed)) {
            ob_end_clean(); header('Location: leads.php'); exit;
        }
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'create' || $action === 'edit') {
        $id=$n=$co=$em=$ph=$so=$in=$bu=$bc=$st=$pr=$cl=$lc=$no=$lr=$as=null;
        $id=(int)($_POST['id']??0);$n=trim($_POST['name']??'');$co=trim($_POST['company']??'');$em=trim($_POST['email']??'');$ph=trim($_POST['phone']??'');$so=$_POST['source']??'other';$in=trim($_POST['service_interest']??'');$bu=$_POST['budget_est']!==''?(float)$_POST['budget_est']:null;$bc=$_POST['budget_currency']??'LKR';$st=$_POST['stage']??'new';$pr=$_POST['priority']??'medium';$cl=$_POST['expected_close']?:null;$lc=$_POST['last_contact']?:null;$no=trim($_POST['notes']??'');$lr=trim($_POST['loss_reason']??'');$as=(int)($_POST['assigned_to']??0)?:null;$uid=$user['id'];
        if(!$n){flash('Name required.','error');ob_end_clean();header('Location: leads.php');exit;}
        if($action==='create'){$s=$db->prepare("INSERT INTO leads (name,company,email,phone,source,service_interest,budget_est,budget_currency,stage,priority,expected_close,last_contact,notes,loss_reason,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");$s->bind_param("ssssssdsssssssii",$n,$co,$em,$ph,$so,$in,$bu,$bc,$st,$pr,$cl,$lc,$no,$lr,$as,$uid);$s->execute();logActivity('created lead',$n,$db->insert_id);flash('Lead created.','success');}
        else{$s=$db->prepare("UPDATE leads SET name=?,company=?,email=?,phone=?,source=?,service_interest=?,budget_est=?,budget_currency=?,stage=?,priority=?,expected_close=?,last_contact=?,notes=?,loss_reason=?,assigned_to=? WHERE id=?");$s->bind_param("ssssssdsssssssii",$n,$co,$em,$ph,$so,$in,$bu,$bc,$st,$pr,$cl,$lc,$no,$lr,$as,$id);$s->execute();logActivity('updated lead',$n,$id);flash('Lead updated.','success');}
        ob_end_clean();header('Location: leads.php');exit;
    }
    if($action==='stage_update'){$id=(int)$_POST['id'];$st=$db->real_escape_string($_POST['stage']);$db->query("UPDATE leads SET stage='$st',updated_at=NOW() WHERE id=$id");ob_end_clean();echo json_encode(['ok'=>true]);exit;}
    if($action==='add_activity'){header('Content-Type: application/json');$lid=(int)$_POST['lead_id'];$ty=$db->real_escape_string($_POST['activity_type']??'note');$de=trim($_POST['description']??'');$da=$_POST['activity_date']?:date('Y-m-d H:i:s');$uid=$user['id'];if($lid&&$de){$s=$db->prepare("INSERT INTO lead_activities (lead_id,user_id,activity_type,description,activity_date) VALUES (?,?,?,?,?)");$s->bind_param("iisss",$lid,$uid,$ty,$de,$da);$s->execute();ob_end_clean();echo json_encode(['ok'=>true,'id'=>$db->insert_id]);exit;}ob_end_clean();echo json_encode(['ok'=>false]);exit;}
    if($action==='delete'){$id=(int)$_POST['id'];$db->query("DELETE FROM leads WHERE id=$id");flash('Lead deleted.','success');ob_end_clean();header('Location: leads.php');exit;}
}
ob_end_clean();

$view_id=(int)($_GET['view']??0);$edit_id=(int)($_GET['edit']??0);$stage_f=$_GET['stage']??'';$search=trim($_GET['q']??'');$view_mode=$_GET['mode']??'kanban';
$w="1=1";if($stage_f)$w.=" AND l.stage='".$db->real_escape_string($stage_f)."'";if($search)$w.=" AND (l.name LIKE '%".$db->real_escape_string($search)."%' OR l.company LIKE '%".$db->real_escape_string($search)."%')";
// Interns see only their assigned leads OR unassigned leads — managers see all
if (!isManager()) {
    $uid_safe = (int)$user['id'];
    $w .= " AND (l.assigned_to = $uid_safe OR l.assigned_to IS NULL)";
}
$leads=$db->query("SELECT l.*,u.name AS assignee_name FROM leads l LEFT JOIN users u ON u.id=l.assigned_to WHERE $w ORDER BY FIELD(l.priority,'urgent','high','medium','low'),l.updated_at DESC")->fetch_all(MYSQLI_ASSOC);

// Stats and leads scoped by role — managers see all, interns see only their assigned
$uid_safe = (int)$user['id'];
$stats_scope = isManager() ? "1=1" : "assigned_to = $uid_safe";

$sc_counts = [];
foreach ($STAGES as $k => $v)
    $sc_counts[$k] = $db->query("SELECT COUNT(*) FROM leads WHERE stage='$k' AND ($stats_scope)")->fetch_row()[0];

$pipeline_val = $db->query("SELECT COALESCE(SUM(budget_est),0) FROM leads WHERE stage NOT IN ('won','lost') AND ($stats_scope)")->fetch_row()[0];
$won_val      = $db->query("SELECT COALESCE(SUM(budget_est),0) FROM leads WHERE stage='won' AND ($stats_scope)")->fetch_row()[0];
$total_leads  = array_sum($sc_counts);

$all_users=$db->query("SELECT id,name FROM users WHERE status='active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$edit_lead=null;if($edit_id)$edit_lead=$db->query("SELECT * FROM leads WHERE id=$edit_id")->fetch_assoc();
$single=null;$activities=[];
if($view_id){$single=$db->query("SELECT l.*,u.name AS assignee_name FROM leads l LEFT JOIN users u ON u.id=l.assigned_to WHERE l.id=$view_id")->fetch_assoc();if($single)$activities=$db->query("SELECT la.*,u.name AS uname FROM lead_activities la JOIN users u ON u.id=la.user_id WHERE la.lead_id=$view_id ORDER BY la.activity_date DESC")->fetch_all(MYSQLI_ASSOC);}

renderLayout('Leads Pipeline', 'leads');
?>

<style>
/* ── LEADS PIPELINE PAGE ── */
.lp-bar{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:18px;flex-wrap:wrap}
.lp-bar-left{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.vt{display:flex;border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;flex-shrink:0}
.vt a{display:block;padding:7px 14px;font-size:12.5px;font-weight:600;color:var(--text2);background:var(--bg3);text-decoration:none;transition:background .15s,color .15s}
.vt a.on{background:var(--orange);color:#fff}

.kb-scroll{
  width:calc(100vw - var(--sidebar-w) - 48px);
  overflow-x:auto;
  overflow-y:visible;
  -webkit-overflow-scrolling:touch;
  padding-bottom:12px;
  scrollbar-width:thin;
  scrollbar-color:var(--border2) var(--bg3);
}
.kb-scroll::-webkit-scrollbar{height:6px}
.kb-scroll::-webkit-scrollbar-track{background:var(--bg3);border-radius:99px}
.kb-scroll::-webkit-scrollbar-thumb{background:var(--border2);border-radius:99px}
@media(max-width:900px){.kb-scroll{width:calc(100vw - 32px)}}

.kb-board{display:flex;gap:12px;align-items:flex-start}

.kb-col{
  width:235px;min-width:235px;flex-shrink:0;
  background:var(--bg2);border:1px solid var(--border);
  border-top:3px solid var(--kc);border-radius:var(--radius);
  display:flex;flex-direction:column;
  transition:box-shadow .2s;
}
/* drag-over highlight on column */
.kb-col.drag-over{
  box-shadow:0 0 0 2px var(--kc),0 4px 20px rgba(0,0,0,.18);
  background:var(--bg3);
}

.kb-hd{padding:10px 13px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:8px}
.kb-title{font-size:12.5px;font-weight:700;color:var(--kc);white-space:nowrap}
.kb-badge{background:var(--kc);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;flex-shrink:0}
.kb-body{
  padding:8px;display:flex;flex-direction:column;gap:7px;
  min-height:100px;max-height:calc(100vh - 300px);
  overflow-y:auto;overflow-x:hidden;
}
/* drop zone glow when dragging over body */
.kb-body.drag-active{
  background:rgba(249,115,22,.04);
  border-radius:var(--radius-sm);
  outline:2px dashed rgba(249,115,22,.35);
  outline-offset:-2px;
}
.kb-foot{padding:6px 13px;border-top:1px solid var(--border);font-size:11px;color:var(--text3);text-align:right}
.kb-empty{text-align:center;padding:20px 8px;font-size:12px;color:var(--text3);border:1px dashed var(--border);border-radius:7px}

/* Lead card */
.kc{
  background:var(--bg3);border:1px solid var(--border);
  border-radius:8px;padding:11px 12px;
  cursor:grab;
  transition:border-color .15s,box-shadow .15s,opacity .15s,transform .15s;
  user-select:none;
}
.kc:hover{border-color:var(--border2);box-shadow:0 2px 8px rgba(0,0,0,.22)}
.kc.dragging{opacity:.45;transform:scale(.97);cursor:grabbing;box-shadow:0 6px 24px rgba(0,0,0,.35)}
.kc-name{font-size:13px;font-weight:700;color:var(--text);line-height:1.3;margin-bottom:2px}
.kc-co{font-size:11px;color:var(--text2);margin-bottom:6px}
.kc-sep{height:1px;background:var(--border);margin:6px 0}
.kc-row{display:flex;align-items:center;justify-content:space-between;gap:4px;flex-wrap:wrap}
.kc-p{font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px;white-space:nowrap}
.kc-b{font-size:10.5px;font-weight:700;color:var(--green)}
.kc-d{font-size:10px;color:var(--text3);margin-top:4px}
.kc-w{font-size:10px;color:var(--text3);margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* drag placeholder */
.kc-placeholder{
  border:2px dashed var(--orange);border-radius:8px;
  background:var(--orange-bg);height:72px;
  flex-shrink:0;pointer-events:none;
}

/* Single lead detail — unchanged */
.ld-grid{display:grid;grid-template-columns:1fr 260px;gap:18px;align-items:start}
.ld-meta{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
.ld-box{background:var(--bg3);border-radius:8px;padding:10px}
.ld-lbl{font-size:10px;color:var(--text3);text-transform:uppercase;margin-bottom:3px;letter-spacing:.04em}
.ld-val{font-size:13px;color:var(--text);word-break:break-word}
.act-item{background:var(--bg3);border-radius:8px;padding:10px 12px;margin-bottom:8px}
.act-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--orange)}

@media(max-width:960px){.ld-grid{grid-template-columns:1fr}.ld-meta{grid-template-columns:1fr 1fr}}
@media(max-width:768px){.lp-bar{flex-direction:column;align-items:stretch}.kb-col{width:200px;min-width:200px}}
@media(max-width:480px){.kb-col{width:175px;min-width:175px}.ld-meta{grid-template-columns:1fr 1fr}}
</style>

<?php if ($single): ?>

<div style="margin-bottom:14px">
  <a href="leads.php" style="color:var(--text3);font-size:13px">← Back to Pipeline</a>
</div>

<div class="ld-grid">
  <div>
    <div class="card" style="margin-bottom:16px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px;flex-wrap:wrap">
        <div>
          <div style="font-family:var(--font-display);font-size:20px;font-weight:700;margin-bottom:6px"><?= h($single['name']) ?></div>
          <?php if ($single['company']): ?><div style="font-size:13px;color:var(--text2);margin-bottom:8px">🏢 <?= h($single['company']) ?></div><?php endif; ?>
          <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <?php $sc=$STAGE_COLORS[$single['stage']]??'#94a3b8'; ?>
            <span class="badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><?= $STAGES[$single['stage']] ?></span>
            <span class="badge" style="background:var(--bg3);color:var(--text2)"><?= $SOURCES[$single['source']]??h($single['source']) ?></span>
            <span style="font-size:12px"><?= priorityIcon($single['priority']) ?> <?= ucfirst($single['priority']) ?></span>
          </div>
        </div>
        <div style="display:flex;gap:8px;flex-shrink:0">
          <a href="leads.php?edit=<?= $single['id'] ?>" class="btn btn-ghost btn-sm">✎ Edit</a>
          <form method="POST" onsubmit="return confirm('Delete this lead?')" style="display:inline">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $single['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">🗑</button>
          </form>
        </div>
      </div>
      <div class="ld-meta">
        <?php foreach ([
          ['Email',$single['email']?'<a href="mailto:'.h($single['email']).'">'.h($single['email']).'</a>':'—'],
          ['Phone',h($single['phone']?:'—')],
          ['Budget',$single['budget_est']?h($single['budget_currency']).' '.number_format($single['budget_est'],0):'Not set'],
          ['Assigned',h($single['assignee_name']?:'Unassigned')],
          ['Close Date',$single['expected_close']?fDate($single['expected_close']):'—'],
          ['Last Contact',$single['last_contact']?fDate($single['last_contact']):'—'],
        ] as [$l,$v]): ?>
        <div class="ld-box"><div class="ld-lbl"><?= $l ?></div><div class="ld-val"><?= $v ?></div></div>
        <?php endforeach; ?>
      </div>
      <?php renderAttachWidget('lead', $single['id']); ?>
      <?php if ($single['service_interest']): ?>
      <div style="margin-bottom:12px"><div style="font-size:11px;font-weight:600;color:var(--text3);margin-bottom:4px;text-transform:uppercase">Service Interest</div><div style="font-size:13.5px;color:var(--text2)"><?= h($single['service_interest']) ?></div></div>
      <?php endif; ?>
      <?php if ($single['notes']): ?>
      <div style="margin-bottom:12px"><div style="font-size:11px;font-weight:600;color:var(--text3);margin-bottom:4px;text-transform:uppercase">Notes</div><div style="font-size:13.5px;color:var(--text2);line-height:1.6"><?= nl2br(h($single['notes'])) ?></div></div>
      <?php endif; ?>
      <?php if ($single['stage']==='lost' && $single['loss_reason']): ?>
      <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:12px"><div style="font-size:11px;font-weight:700;color:var(--red);margin-bottom:3px;text-transform:uppercase">Loss Reason</div><div style="font-size:13px;color:var(--text2)"><?= nl2br(h($single['loss_reason'])) ?></div></div>
      <?php endif; ?>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">Activity Log</div><button class="btn btn-primary btn-sm" onclick="openModal('modal-activity')">＋ Log Activity</button></div>
      <?php if (empty($activities)): ?>
        <div class="empty-state"><div class="icon">📋</div><p>No activities yet.</p></div>
      <?php else: ?>
        <?php $ai=['call'=>'📞','email'=>'📧','meeting'=>'🤝','note'=>'📝','proposal'=>'📄','follow_up'=>'🔔']; ?>
        <?php foreach ($activities as $a): ?>
        <div class="act-item">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px;flex-wrap:wrap;gap:4px">
            <div><span class="act-lbl"><?= ($ai[$a['activity_type']]??'📌').' '.h(str_replace('_',' ',$a['activity_type'])) ?></span><span style="font-size:12px;color:var(--text2);margin-left:8px">by <?= h($a['uname']) ?></span></div>
            <span style="font-size:11px;color:var(--text3)"><?= date('M j, Y g:ia',strtotime($a['activity_date'])) ?></span>
          </div>
          <div style="font-size:13px;color:var(--text);line-height:1.5"><?= nl2br(h($a['description'])) ?></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-title" style="margin-bottom:14px">Update Stage</div>
    <div style="display:flex;flex-direction:column;gap:6px">
      <?php foreach ($STAGES as $k=>$v): $active=$single['stage']===$k; $sc=$STAGE_COLORS[$k]; ?>
      <button type="button" class="btn <?= $active?'btn-primary':'btn-ghost' ?>" style="width:100%;justify-content:flex-start;<?= $active?"background:$sc;border-color:$sc":'' ?>" onclick="updateStage(<?= $single['id'] ?>,'<?= $k ?>')">
        <?= $v ?><?= $active?' ✓':'' ?>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-activity">
  <div class="modal" style="max-width:460px">
    <div class="modal-header"><div class="modal-title">Log Activity</div><button class="modal-close" onclick="closeModal('modal-activity')">✕</button></div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Type</label><select id="act-type" class="form-control"><option value="call">📞 Call</option><option value="email">📧 Email</option><option value="meeting">🤝 Meeting</option><option value="note" selected>📝 Note</option><option value="proposal">📄 Proposal</option><option value="follow_up">🔔 Follow Up</option></select></div>
      <div class="form-group"><label class="form-label">Date & Time</label><input type="datetime-local" id="act-date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>"></div>
      <div class="form-group"><label class="form-label">Description *</label><textarea id="act-desc" class="form-control" placeholder="What happened?"></textarea></div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-ghost" onclick="closeModal('modal-activity')">Cancel</button>
      <button type="button" class="btn btn-primary" onclick="saveActivity(<?= $single['id'] ?>)">Save</button>
    </div>
  </div>
</div>
<script>
function updateStage(id,stage){fetch('leads.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=stage_update&id='+id+'&stage='+stage}).then(function(r){return r.json()}).then(function(){toast('Stage updated','success');setTimeout(function(){location.reload()},500)})}
function saveActivity(lid){var desc=document.getElementById('act-desc').value.trim();if(!desc){toast('Description required','error');return;}fetch('leads.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=add_activity&lead_id='+lid+'&activity_type='+document.getElementById('act-type').value+'&description='+encodeURIComponent(desc)+'&activity_date='+encodeURIComponent(document.getElementById('act-date').value)}).then(function(r){return r.json()}).then(function(d){if(d.ok){closeModal('modal-activity');toast('Logged','success');setTimeout(function(){location.reload()},600)}})}
</script>

<?php else: ?>

<div class="stats-grid" style="margin-bottom:18px">
  <div class="stat-card"><div class="stat-icon" style="background:rgba(99,102,241,.12)">🎯</div><div><div class="stat-val"><?= $total_leads ?></div><div class="stat-lbl">Total Leads</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:rgba(16,185,129,.12)">🏆</div><div><div class="stat-val"><?= $sc_counts['won'] ?></div><div class="stat-lbl">Won</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:rgba(239,68,68,.12)">❌</div><div><div class="stat-val"><?= $sc_counts['lost'] ?></div><div class="stat-lbl">Lost</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:rgba(245,158,11,.12)">🔥</div><div><div class="stat-val"><?= ($sc_counts['proposal']??0)+($sc_counts['negotiation']??0) ?></div><div class="stat-lbl">Hot Leads</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:rgba(249,115,22,.12)">💰</div><div><div class="stat-val"><?= $pipeline_val>0?number_format($pipeline_val/1000,1).'K':'0' ?></div><div class="stat-lbl">Pipeline Value</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:rgba(16,185,129,.12)">✅</div><div><div class="stat-val"><?= $won_val>0?number_format($won_val/1000,1).'K':'0' ?></div><div class="stat-lbl">Won Value</div></div></div>
</div>

<div class="lp-bar">
  <form method="GET" class="lp-bar-left" id="lf">
    <div class="search-box" style="min-width:160px;flex:1;max-width:260px">
      <span style="color:var(--text3)">🔍</span>
      <input type="text" name="q" placeholder="Search leads…" value="<?= h($search) ?>">
    </div>
    <select name="stage" class="form-control" style="width:auto" onchange="this.form.submit()">
      <option value="">All Stages</option>
      <?php foreach ($STAGES as $k=>$v): ?>
      <option value="<?= $k ?>" <?= $stage_f===$k?'selected':'' ?>><?= $v ?> (<?= $sc_counts[$k] ?>)</option>
      <?php endforeach; ?>
    </select>
    <div class="vt">
      <a href="?mode=kanban<?= $stage_f?"&stage=$stage_f":'' ?><?= $search?"&q=".urlencode($search):'' ?>" class="<?= $view_mode==='kanban'?'on':'' ?>">Kanban</a>
      <a href="?mode=list<?= $stage_f?"&stage=$stage_f":'' ?><?= $search?"&q=".urlencode($search):'' ?>" class="<?= $view_mode==='list'?'on':'' ?>">List</a>
    </div>
  </form>
  <button class="btn btn-primary" onclick="openModal('modal-lead')" style="white-space:nowrap;flex-shrink:0">＋ New Lead</button>
</div>

<?php if ($view_mode==='kanban'): ?>
<div class="kb-scroll">
  <div class="kb-board">
    <?php foreach ($STAGES as $stage_key=>$stage_label):
      $col_leads=array_values(array_filter($leads,fn($l)=>$l['stage']===$stage_key));
      $kc=$STAGE_COLORS[$stage_key];
      $col_val=array_sum(array_column($col_leads,'budget_est'));
    ?>
    <div class="kb-col" style="--kc:<?= $kc ?>" data-stage="<?= $stage_key ?>">
      <div class="kb-hd">
        <span class="kb-title"><?= $stage_label ?></span>
        <span class="kb-badge"><?= count($col_leads) ?></span>
      </div>
      <div class="kb-body">
        <?php if(empty($col_leads)): ?><div class="kb-empty">No leads</div><?php endif; ?>
        <?php foreach ($col_leads as $l):
          $pbg=['urgent'=>'rgba(239,68,68,.15)','high'=>'rgba(249,115,22,.15)','medium'=>'rgba(245,158,11,.12)','low'=>'rgba(16,185,129,.12)'][$l['priority']]??'rgba(99,102,241,.1)';
          $ptx=['urgent'=>'#ef4444','high'=>'#f97316','medium'=>'#f59e0b','low'=>'#10b981'][$l['priority']]??'#6366f1';
        ?>
        <div class="kc" data-id="<?= $l['id'] ?>" data-stage="<?= $stage_key ?>" onclick="location.href='leads.php?view=<?= $l['id'] ?>'">
          <div class="kc-name"><?= h($l['name']) ?></div>
          <?php if($l['company']): ?><div class="kc-co">🏢 <?= h($l['company']) ?></div><?php endif; ?>
          <div class="kc-sep"></div>
          <div class="kc-row">
            <span class="kc-p" style="background:<?= $pbg ?>;color:<?= $ptx ?>"><?= priorityIcon($l['priority']) ?> <?= ucfirst($l['priority']) ?></span>
            <?php if($l['budget_est']): ?><span class="kc-b">💰<?= number_format($l['budget_est'],0) ?></span><?php endif; ?>
          </div>
          <?php if($l['expected_close']): ?><div class="kc-d">📅 <?= fDate($l['expected_close'],'M j, Y') ?></div><?php endif; ?>
          <?php if($l['assignee_name']): ?><div class="kc-w">👤 <?= h($l['assignee_name']) ?></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if($col_val>0): ?><div class="kb-foot">💰 <?= number_format($col_val,2) ?></div><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php else: ?>
<?php if(empty($leads)): ?>
<div class="card"><div class="empty-state"><div class="icon">🎯</div><p>No leads found.</p></div></div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Company</th><th>Source</th><th>Stage</th><th>Priority</th><th>Budget</th><th>Close Date</th><th>Assigned</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($leads as $l): $sc=$STAGE_COLORS[$l['stage']]??'#94a3b8'; ?>
        <tr>
          <td class="td-main" style="cursor:pointer" onclick="location.href='leads.php?view=<?= $l['id'] ?>'"><?= h($l['name']) ?></td>
          <td><?= h($l['company']?:'—') ?></td>
          <td style="font-size:12px"><?= $SOURCES[$l['source']]??h($l['source']) ?></td>
          <td><span class="badge" style="background:<?= $sc ?>20;color:<?= $sc ?>"><?= $STAGES[$l['stage']] ?></span></td>
          <td><?= priorityIcon($l['priority']) ?> <?= ucfirst($l['priority']) ?></td>
          <td style="text-align:right"><?= $l['budget_est']?h($l['budget_currency']).' '.number_format($l['budget_est'],0):'—' ?></td>
          <td style="font-size:12px"><?= fDate($l['expected_close']) ?></td>
          <td style="font-size:12.5px"><?= h($l['assignee_name']?:'—') ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="leads.php?view=<?= $l['id'] ?>" class="btn btn-ghost btn-sm btn-icon">👁</a>
              <a href="leads.php?edit=<?= $l['id'] ?>" class="btn btn-ghost btn-sm btn-icon">✎</a>
              <form method="POST" onsubmit="return confirm('Delete?')" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $l['id'] ?>"><button type="submit" class="btn btn-danger btn-sm btn-icon">✕</button></form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<div class="modal-overlay <?= $edit_id?'open':'' ?>" id="modal-lead">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><?= $edit_id?'Edit Lead':'New Lead' ?></div>
      <button class="modal-close" onclick="closeModal('modal-lead');<?= $edit_id?"location.href='leads.php'":'' ?>">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="<?= $edit_id?'edit':'create' ?>">
      <?php if($edit_id): ?><input type="hidden" name="id" value="<?= $edit_id ?>"><?php endif; ?>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required value="<?= h($edit_lead['name']??'') ?>"></div>
          <div class="form-group"><label class="form-label">Company</label><input type="text" name="company" class="form-control" value="<?= h($edit_lead['company']??'') ?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= h($edit_lead['email']??'') ?>"></div>
          <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= h($edit_lead['phone']??'') ?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Source</label><select name="source" class="form-control"><?php foreach($SOURCES as $k=>$v): ?><option value="<?= $k ?>" <?= ($edit_lead['source']??'other')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label class="form-label">Stage</label><select name="stage" class="form-control"><?php foreach($STAGES as $k=>$v): ?><option value="<?= $k ?>" <?= ($edit_lead['stage']??'new')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="form-group"><label class="form-label">Service Interest</label><input type="text" name="service_interest" class="form-control" value="<?= h($edit_lead['service_interest']??'') ?>" placeholder="e.g. Website, Branding, Digital Marketing"></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Budget Estimate</label><input type="number" name="budget_est" step="0.01" class="form-control" value="<?= h($edit_lead['budget_est']??'') ?>"></div>
          <div class="form-group"><label class="form-label">Currency</label><select name="budget_currency" class="form-control"><?php foreach(['LKR','INR','USD','EUR'] as $c): ?><option <?= ($edit_lead['budget_currency']??'LKR')===$c?'selected':'' ?>><?= $c ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Priority</label><select name="priority" class="form-control"><?php foreach(['low','medium','high','urgent'] as $p): ?><option <?= ($edit_lead['priority']??'medium')===$p?'selected':'' ?>><?= $p ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label class="form-label">Assign To</label><select name="assigned_to" class="form-control"><option value="">— Unassigned —</option><?php foreach($all_users as $u): ?><option value="<?= $u['id'] ?>" <?= ($edit_lead['assigned_to']??'')==$u['id']?'selected':'' ?>><?= h($u['name']) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Expected Close</label><input type="date" name="expected_close" class="form-control" value="<?= h($edit_lead['expected_close']??'') ?>"></div>
          <div class="form-group"><label class="form-label">Last Contact</label><input type="date" name="last_contact" class="form-control" value="<?= h($edit_lead['last_contact']??'') ?>"></div>
        </div>
        <div class="form-group"><label class="form-label">Notes</label><textarea name="notes" class="form-control"><?= h($edit_lead['notes']??'') ?></textarea></div>
        <div class="form-group"><label class="form-label">Loss Reason (if lost)</label><textarea name="loss_reason" class="form-control" style="min-height:60px"><?= h($edit_lead['loss_reason']??'') ?></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-lead');<?= $edit_id?"location.href='leads.php'":'' ?>">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= $edit_id?'Save Changes':'Create Lead' ?></button>
      </div>
    </form>
  </div>
</div>
<?php if($edit_id): ?><script>document.addEventListener('DOMContentLoaded',function(){openModal('modal-lead')})</script><?php endif; ?>

<?php if ($view_mode === 'kanban' && !$single): ?>
<script>
/* ── KANBAN DRAG & DROP ── */
(function(){
  var dragId   = null;   // lead id being dragged
  var dragEl   = null;   // the .kc element
  var placeholder = null;

  function makePlaceholder(){
    var p = document.createElement('div');
    p.className = 'kc-placeholder';
    return p;
  }

  // Attach drag events to every card
  document.querySelectorAll('.kc').forEach(function(card){
    card.setAttribute('draggable','true');

    card.addEventListener('dragstart', function(e){
      dragId  = card.dataset.id;
      dragEl  = card;
      setTimeout(function(){ card.classList.add('dragging'); }, 0);
      placeholder = makePlaceholder();
      e.dataTransfer.effectAllowed = 'move';
    });

    card.addEventListener('dragend', function(){
      card.classList.remove('dragging');
      if (placeholder && placeholder.parentNode) placeholder.parentNode.removeChild(placeholder);
      document.querySelectorAll('.kb-body').forEach(function(b){ b.classList.remove('drag-active'); });
      document.querySelectorAll('.kb-col').forEach(function(c){ c.classList.remove('drag-over'); });
      dragId = null; dragEl = null; placeholder = null;
    });
  });

  // Column body events
  document.querySelectorAll('.kb-body').forEach(function(body){
    var col = body.closest('.kb-col');

    body.addEventListener('dragover', function(e){
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      body.classList.add('drag-active');
      col.classList.add('drag-over');

      // Show placeholder at correct position
      var afterEl = getDragAfterElement(body, e.clientY);
      if (placeholder) {
        if (afterEl == null) body.appendChild(placeholder);
        else body.insertBefore(placeholder, afterEl);
      }
    });

    body.addEventListener('dragleave', function(e){
      if (!body.contains(e.relatedTarget)){
        body.classList.remove('drag-active');
        col.classList.remove('drag-over');
      }
    });

    body.addEventListener('drop', function(e){
      e.preventDefault();
      body.classList.remove('drag-active');
      col.classList.remove('drag-over');
      if (!dragId) return;

      var newStage = col.dataset.stage;
      var oldStage = dragEl ? dragEl.dataset.stage : null;
      if (!newStage || newStage === oldStage) return;

      // Optimistic UI: move card to new column
      if (placeholder && placeholder.parentNode) {
        body.insertBefore(dragEl, placeholder);
        placeholder.parentNode.removeChild(placeholder);
      } else {
        body.appendChild(dragEl);
      }
      dragEl.dataset.stage = newStage;

      // Update stage badge on card
      var stageNames = <?= json_encode($STAGES) ?>;
      var stageColors = <?= json_encode($STAGE_COLORS) ?>;

      // Update column badge counts
      if (oldStage) {
        var oldBody = document.querySelector('.kb-col[data-stage="'+oldStage+'"] .kb-body');
        var oldBadge = document.querySelector('.kb-col[data-stage="'+oldStage+'"] .kb-badge');
        if (oldBadge) oldBadge.textContent = Math.max(0, (parseInt(oldBadge.textContent)||1) - 1);
        // Show empty if no cards left
        if (oldBody && !oldBody.querySelector('.kc')) {
          var emp = oldBody.querySelector('.kb-empty');
          if (!emp) {
            emp = document.createElement('div');
            emp.className = 'kb-empty';
            emp.textContent = 'No leads';
            oldBody.appendChild(emp);
          }
        }
      }
      var newBadge = col.querySelector('.kb-badge');
      if (newBadge) newBadge.textContent = (parseInt(newBadge.textContent)||0) + 1;
      // Remove empty placeholder in new column
      var emp = body.querySelector('.kb-empty');
      if (emp) emp.remove();

      // Persist to server
      var fd = new FormData();
      fd.append('action','stage_update');
      fd.append('id', dragId);
      fd.append('stage', newStage);
      fetch('leads.php', {method:'POST', body: fd})
        .then(function(r){ return r.json(); })
        .then(function(d){
          if(d.ok){
            toast('Moved to ' + (stageNames[newStage]||newStage), 'success');
          } else {
            toast('Stage update failed','error');
          }
        })
        .catch(function(){ toast('Network error','error'); });
    });
  });

  // Find element to insert before based on Y position
  function getDragAfterElement(container, y){
    var els = Array.from(container.querySelectorAll('.kc:not(.dragging)'));
    return els.reduce(function(closest, el){
      var box    = el.getBoundingClientRect();
      var offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset){
        return {offset: offset, element: el};
      }
      return closest;
    }, {offset: Number.NEGATIVE_INFINITY}).element || null;
  }
})();
</script>
<?php endif; ?>

  <?php renderLayoutEnd(); ?>