<?php
// Show errors so 500 becomes readable - remove after debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';
require_once 'includes/layout.php';
requireLogin();
requireRole(['admin','manager']);
$db = getCRMDB();
mysqli_report(MYSQLI_REPORT_OFF); // Use return values, not exceptions

// ── SAFE QUERY HELPERS (defined first, used everywhere) ──
function aq(mysqli $db, string $sql): array {
    try { $r = $db->query($sql); } catch (\Throwable $e) { return []; }
    return ($r && !is_bool($r)) ? $r->fetch_all(MYSQLI_ASSOC) : [];
}
function ar(mysqli $db, string $sql, array $def = []): array {
    try { $r = $db->query($sql); } catch (\Throwable $e) { return $def; }
    if (!$r || is_bool($r)) return $def;
    return $r->fetch_assoc() ?: $def;
}
function av(mysqli $db, string $sql, $def = 0) {
    try { $r = $db->query($sql); } catch (\Throwable $e) { return $def; }
    if (!$r || is_bool($r)) return $def;
    $row = $r->fetch_row();
    return $row[0] ?? $def;
}

// ── FILTERS ──
$period      = in_array($_GET['period'] ?? '', ['7','30','90','365','all']) ? $_GET['period'] : '30';
$proj_filter = (int)($_GET['project_id'] ?? 0);
$days_back   = in_array($period, ['7','30','90','365']) ? (int)$period : 30;

$pc  = $proj_filter ? "AND project_id=$proj_filter" : '';
$pct = $proj_filter ? "AND t.project_id=$proj_filter" : '';
$dc  = $period !== 'all' ? "AND created_at >= DATE_SUB(NOW(),INTERVAL {$period} DAY)" : '';
$dct = $period !== 'all' ? "AND t.created_at >= DATE_SUB(NOW(),INTERVAL {$period} DAY)" : '';

// ── CORE STATS ──
$total_projects  = (int)av($db,"SELECT COUNT(*) FROM projects");
$active_projects = (int)av($db,"SELECT COUNT(*) FROM projects WHERE status='active'");
$total_tasks     = (int)av($db,"SELECT COUNT(*) FROM tasks WHERE 1=1 $pc");
$done_tasks      = (int)av($db,"SELECT COUNT(*) FROM tasks WHERE status='done' $pc");
$overdue_tasks   = (int)av($db,"SELECT COUNT(*) FROM tasks WHERE due_date<CURDATE() AND status!='done' $pc");
$total_contacts  = (int)av($db,"SELECT COUNT(*) FROM contacts");
$total_docs      = (int)av($db,"SELECT COUNT(*) FROM documents");
$total_users     = (int)av($db,"SELECT COUNT(*) FROM users WHERE status='active'");
$task_rate       = $total_tasks > 0 ? round($done_tasks/$total_tasks*100) : 0;

// ── TASKS OVER TIME ──
$cr_map = []; foreach(aq($db,"SELECT DATE(created_at) d,COUNT(*) c FROM tasks WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL {$days_back} DAY) $pc GROUP BY d") as $r) $cr_map[$r['d']]=(int)$r['c'];
$dn_map = []; foreach(aq($db,"SELECT DATE(completed_at) d,COUNT(*) c FROM tasks WHERE completed_at>=DATE_SUB(CURDATE(),INTERVAL {$days_back} DAY) AND status='done' $pc GROUP BY d") as $r) $dn_map[$r['d']]=(int)$r['c'];
$tl=$tc=$td=[];
for($i=$days_back-1;$i>=0;$i--){$d=date('Y-m-d',strtotime("-$i days"));$tl[]=date('M j',strtotime($d));$tc[]=$cr_map[$d]??0;$td[]=$dn_map[$d]??0;}

// ── TASK STATUS / PRIORITY ──
$ts=['todo'=>0,'in_progress'=>0,'review'=>0,'done'=>0];
foreach(aq($db,"SELECT status,COUNT(*) c FROM tasks WHERE 1=1 $pc GROUP BY status") as $r) $ts[$r['status']]=(int)$r['c'];
$tp=['low'=>0,'medium'=>0,'high'=>0,'urgent'=>0];
foreach(aq($db,"SELECT priority,COUNT(*) c FROM tasks WHERE 1=1 $pc GROUP BY priority") as $r) $tp[$r['priority']]=(int)$r['c'];

// ── PROJECTS ──
$ps=['planning'=>0,'active'=>0,'on_hold'=>0,'completed'=>0,'cancelled'=>0];
foreach(aq($db,"SELECT status,COUNT(*) c FROM projects GROUP BY status") as $r) $ps[$r['status']]=(int)$r['c'];

$top_projects = aq($db,"
    SELECT p.title,COUNT(t.id) total,SUM(t.status='done') done,p.status,p.due_date
    FROM projects p LEFT JOIN tasks t ON t.project_id=p.id
    GROUP BY p.id,p.title,p.status,p.due_date
    HAVING COUNT(t.id)>0 ORDER BY SUM(t.status='done') DESC LIMIT 8");

// ── TEAM ──
$member_tasks = aq($db,"
    SELECT u.name,
        SUM(IF(t.id IS NOT NULL AND t.status!='done',1,0)) open_cnt,
        SUM(IF(t.id IS NOT NULL AND t.status='done',1,0))  done_cnt
    FROM users u
    LEFT JOIN tasks t ON t.assigned_to=u.id $dct $pct
    WHERE u.status='active'
    GROUP BY u.id,u.name
    HAVING (SUM(IF(t.id IS NOT NULL AND t.status!='done',1,0))+SUM(IF(t.id IS NOT NULL AND t.status='done',1,0)))>0
    ORDER BY (SUM(IF(t.id IS NOT NULL AND t.status!='done',1,0))+SUM(IF(t.id IS NOT NULL AND t.status='done',1,0))) DESC LIMIT 10");

$team_prod = aq($db,"
    SELECT u.name,
        SUM(IF(t.id IS NOT NULL AND t.status='done',1,0)) done,
        SUM(IF(t.id IS NOT NULL AND t.status='done' AND t.due_date IS NOT NULL AND t.completed_at<=t.due_date,1,0)) on_time
    FROM users u
    LEFT JOIN tasks t ON t.assigned_to=u.id
    WHERE u.status='active'
    GROUP BY u.id,u.name
    HAVING SUM(IF(t.id IS NOT NULL AND t.status='done',1,0))>0
    ORDER BY SUM(IF(t.id IS NOT NULL AND t.status='done',1,0)) DESC LIMIT 8");

// ── CONTACTS / DOCS ──
$ct=['client'=>0,'lead'=>0,'partner'=>0,'vendor'=>0];
foreach(aq($db,"SELECT type,COUNT(*) c FROM contacts GROUP BY type") as $r) $ct[$r['type']]=(int)$r['c'];
$doc_cats = aq($db,"SELECT category,COUNT(*) c FROM documents GROUP BY category ORDER BY c DESC LIMIT 8");

// ── AVG COMPLETION TIME ──
$avg_days = ar($db,"SELECT ROUND(AVG(DATEDIFF(completed_at,created_at)),1) v FROM tasks WHERE status='done' AND completed_at IS NOT NULL $pc",['v'=>null])['v'];

// ── COMMENTS ACTIVITY ──
$comment_activity = aq($db,"
    SELECT t.title,COUNT(tc.id) cnt,MAX(tc.created_at) last_c
    FROM task_comments tc JOIN tasks t ON t.id=tc.task_id $pct
    GROUP BY tc.task_id,t.title ORDER BY cnt DESC LIMIT 5");

// ── INVOICES ──
$inv = ar($db,"
    SELECT COALESCE(SUM(IF(status NOT IN('cancelled','draft'),total,0)),0) invoiced,
           COALESCE(SUM(amount_paid),0) collected,
           COALESCE(SUM(IF(status='overdue',total-amount_paid,0)),0) overdue_amt,
           COUNT(IF(status NOT IN('cancelled','draft'),1,NULL)) inv_cnt,
           COUNT(IF(status='paid',1,NULL)) paid_cnt,
           COUNT(IF(status='overdue',1,NULL)) ov_cnt
    FROM invoices",
    ['invoiced'=>0,'collected'=>0,'overdue_amt'=>0,'inv_cnt'=>0,'paid_cnt'=>0,'ov_cnt'=>0]);

$rev_months = aq($db,"
    SELECT DATE_FORMAT(paid_at,'%b %Y') mo,DATE_FORMAT(paid_at,'%Y-%m') smo,SUM(amount) amt
    FROM invoice_payments WHERE paid_at>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH)
    GROUP BY mo,smo ORDER BY smo");

// ── LEADS ──
$lead_stages = aq($db,"SELECT stage,COUNT(*) cnt FROM leads GROUP BY stage ORDER BY FIELD(stage,'new','contacted','qualified','proposal','negotiation','won','lost')");
$lead_total  = (int)av($db,"SELECT COUNT(*) FROM leads");
$lead_won_c  = (int)av($db,"SELECT COUNT(*) FROM leads WHERE stage='won'");
$lead_active = (int)av($db,"SELECT COUNT(*) FROM leads WHERE stage NOT IN('won','lost')");
$conv_rate   = $lead_total > 0 ? round($lead_won_c/$lead_total*100,1) : 0;
$lead_sources = aq($db,"SELECT source,COUNT(*) cnt,SUM(stage='won') won_cnt FROM leads GROUP BY source ORDER BY cnt DESC LIMIT 7");

// ── EXPENSES ──
$exp_total   = (float)av($db,"SELECT COALESCE(SUM(own_spend+office_spend),0) FROM expense_entries WHERE created_at>=DATE_SUB(NOW(),INTERVAL 3 MONTH)",0);
$exp_by_cat  = aq($db,"SELECT category,COALESCE(SUM(own_spend+office_spend),0) total FROM expense_entries WHERE created_at>=DATE_SUB(NOW(),INTERVAL 3 MONTH) GROUP BY category ORDER BY total DESC LIMIT 8");
$exp_by_month= aq($db,"SELECT DATE_FORMAT(em.month_year,'%b %Y') mo,DATE_FORMAT(em.month_year,'%Y-%m') smo,COALESCE(SUM(ee.own_spend+ee.office_spend),0) total,em.revenue FROM expense_months em LEFT JOIN expense_entries ee ON ee.month_id=em.id WHERE em.month_year>=DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 5 MONTH),'%Y-%m') GROUP BY em.id,mo,smo,em.revenue ORDER BY smo");

// ── EMAIL ──
$email_stat = ar($db,"SELECT COUNT(*) total,SUM(status='sent') sent,SUM(status='failed') failed,SUM(opened_count>0) opened FROM email_log WHERE direction='out'",['total'=>0,'sent'=>0,'failed'=>0,'opened'=>0]);
$email_days = aq($db,"SELECT DATE(created_at) d,COUNT(*) total,SUM(status='sent') sent FROM email_log WHERE direction='out' AND created_at>=DATE_SUB(CURDATE(),INTERVAL 14 DAY) GROUP BY d ORDER BY d");

// ── ACTIVITY HEATMAP ──
$hr_data = array_fill(0,24,0);
foreach(aq($db,"SELECT HOUR(created_at) hr,COUNT(*) c FROM activity_log WHERE created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY HOUR(created_at)") as $r) $hr_data[(int)$r['hr']]=(int)$r['c'];

// ── ALL PROJECTS (filter dropdown) ──
$all_projects = aq($db,"SELECT id,title FROM projects ORDER BY title");

renderLayout('Analytics','analytics');
?>
<style>
.an2{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}
.an3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;margin-bottom:18px}
.an4{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}
.cc{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px}
.ct{font-family:var(--font-display);font-weight:700;font-size:14px;margin-bottom:3px}
.cs{font-size:11.5px;color:var(--text3);margin-bottom:14px}
.cw{position:relative}
.lr{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.ld{width:10px;height:10px;border-radius:3px;flex-shrink:0}
.ll{font-size:12px;color:var(--text2);flex:1}
.lv{font-size:12px;font-weight:700;color:var(--text)}
.fbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:20px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:12px 16px}
.kpi4{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px 16px}
.kpi4-v{font-size:20px;font-weight:800;font-family:var(--font-display)}
.kpi4-l{font-size:10.5px;color:var(--text3);text-transform:uppercase;letter-spacing:.05em}
.kpi4-s{font-size:11px;color:var(--text3);margin-top:2px}
.sh{font-family:var(--font-display);font-size:12px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.07em;margin:22px 0 12px;display:flex;align-items:center;gap:8px}
.sh::after{content:'';flex:1;height:1px;background:var(--border)}
.fb{display:flex;align-items:center;gap:10px;margin-bottom:5px}
.fbw{flex:1;background:var(--bg4);border-radius:4px;overflow:hidden;height:22px}
.fbf{height:100%;border-radius:4px;display:flex;align-items:center;padding-left:8px;font-size:11px;font-weight:700;color:#fff}
.fbl{font-size:11.5px;font-weight:600;color:var(--text2);width:90px;flex-shrink:0;text-align:right}
.fbc{font-size:12px;font-weight:700;color:var(--text);width:24px;text-align:right;flex-shrink:0}
.pr{display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border)}
.pr:last-child{border-bottom:none}
.pn{font-size:12px;font-weight:600;color:var(--text);width:110px;flex-shrink:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pb{flex:1;background:var(--bg4);border-radius:3px;height:8px;overflow:hidden}
.pf{height:100%;border-radius:3px}
.pv{font-size:11px;color:var(--text3);width:36px;text-align:right;flex-shrink:0}
.hg{display:grid;grid-template-columns:repeat(24,1fr);gap:3px;margin-top:8px}
.hc{height:28px;border-radius:3px;cursor:default;position:relative}
.hc:hover::after{content:attr(data-t);position:absolute;bottom:calc(100% + 4px);left:50%;transform:translateX(-50%);background:var(--bg4);border:1px solid var(--border);border-radius:4px;padding:2px 6px;font-size:10px;color:var(--text);white-space:nowrap;z-index:20;pointer-events:none}
@media(max-width:1100px){.an4{grid-template-columns:1fr 1fr}}
@media(max-width:900px){.an2,.an3{grid-template-columns:1fr}}
@media(max-width:500px){.an4{grid-template-columns:1fr 1fr}}
</style>

<!-- FILTER BAR -->
<form method="GET" class="fbar">
  <span style="font-size:12px;font-weight:600;color:var(--text2)">Filter:</span>
  <select name="period" class="form-control" style="width:auto" onchange="this.form.submit()">
    <?php foreach(['7'=>'Last 7 days','30'=>'Last 30 days','90'=>'Last 90 days','365'=>'Last Year','all'=>'All Time'] as $v=>$l): ?>
    <option value="<?=$v?>" <?=$period===$v?'selected':''?>><?=$l?></option>
    <?php endforeach; ?>
  </select>
  <select name="project_id" class="form-control" style="width:auto" onchange="this.form.submit()">
    <option value="">All Projects</option>
    <?php foreach($all_projects as $p): ?><option value="<?=$p['id']?>" <?=$proj_filter==$p['id']?'selected':''?>><?=h($p['title'])?></option><?php endforeach; ?>
  </select>
  <span style="font-size:11px;color:var(--text3);margin-left:auto">Auto-updates on change</span>
</form>

<!-- OVERVIEW KPIS -->
<div class="sh">📊 Overview</div>
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-bottom:18px">
<?php
$kpis=[
  ['Total Projects',$total_projects,'📁','#f97316'],
  ['Active',$active_projects,'🟢','#10b981'],
  ['Total Tasks',$total_tasks,'✅','#6366f1'],
  ['Overdue',$overdue_tasks,'⚠','#ef4444'],
  ['Rate',$task_rate.'%','📈','#8b5cf6'],
  ['Active Leads',$lead_active,'🎯','#f59e0b'],
  ['Won Leads',$lead_won_c,'🏆','#10b981'],
  ['Conversion',$conv_rate.'%','🔄','#6366f1'],
];
foreach($kpis as [$l,$v,$i,$c]):?>
<div class="stat-card">
  <div class="stat-icon" style="background:<?=$c?>18"><?=$i?></div>
  <div><div class="stat-val" style="font-size:20px;color:<?=$c?>"><?=$v?></div><div class="stat-lbl"><?=$l?></div></div>
</div>
<?php endforeach;?>
</div>

<!-- REVENUE -->
<?php $sym='Rs. ';?>
<div class="sh">💰 Revenue & Billing</div>
<div class="an4">
<?php
$rev_kpis=[
  [$sym.number_format($inv['invoiced'],0),'Total Invoiced',$inv['inv_cnt'].' invoices','#f97316'],
  [$sym.number_format($inv['collected'],0),'Collected',$inv['paid_cnt'].' paid','#10b981'],
  [$sym.number_format($inv['invoiced']-$inv['collected'],0),'Outstanding',($inv['inv_cnt']-$inv['paid_cnt']).' pending','#f59e0b'],
  [$sym.number_format($inv['overdue_amt'],0),'Overdue',$inv['ov_cnt'].' invoices','#ef4444'],
];
foreach($rev_kpis as [$v,$l,$s,$c]):?>
<div class="kpi4" style="border-left:3px solid <?=$c?>">
  <div class="kpi4-v" style="color:<?=$c?>"><?=$v?></div>
  <div class="kpi4-l"><?=$l?></div>
  <div class="kpi4-s"><?=$s?></div>
</div>
<?php endforeach;?>
</div>

<?php if($rev_months):?>
<div class="cc" style="margin-bottom:18px">
  <div class="ct">Revenue Collected by Month</div><div class="cs">Payments received — last 6 months</div>
  <div class="cw" style="height:180px"><canvas id="cRev"></canvas></div>
</div>
<?php endif;?>

<!-- TASKS -->
<div class="sh">✅ Task Performance</div>
<div class="cc" style="margin-bottom:18px">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:14px">
    <div><div class="ct">Tasks Over Time</div><div class="cs">Created vs Completed — last <?=$period==='all'?'all time':$period.' days'?></div></div>
    <?php if($avg_days!==null):?><div style="text-align:right"><div style="font-size:20px;font-weight:800;font-family:var(--font-display);color:var(--blue)"><?=$avg_days?> days</div><div style="font-size:10.5px;color:var(--text3);text-transform:uppercase">Avg completion</div></div><?php endif;?>
  </div>
  <div class="cw" style="height:200px"><canvas id="cTL"></canvas></div>
</div>

<div class="an3">
  <div class="cc">
    <div class="ct">By Status</div><div class="cs">Current</div>
    <div style="display:flex;align-items:center;gap:14px">
      <div style="width:120px;height:120px;flex-shrink:0"><canvas id="cTS"></canvas></div>
      <div style="flex:1">
        <?php $tsl=['To Do','In Progress','Review','Done'];$tsc=['#6366f1','#f59e0b','#8b5cf6','#10b981'];
        foreach(array_keys($ts) as $i=>$k):?>
        <div class="lr"><div class="ld" style="background:<?=$tsc[$i]?>"></div><span class="ll"><?=$tsl[$i]?></span><span class="lv"><?=$ts[$k]?></span></div>
        <?php endforeach;?>
      </div>
    </div>
  </div>
  <div class="cc">
    <div class="ct">By Priority</div><div class="cs">Urgency</div>
    <div style="display:flex;align-items:center;gap:14px">
      <div style="width:120px;height:120px;flex-shrink:0"><canvas id="cTP"></canvas></div>
      <div style="flex:1">
        <?php $tpl=['Low','Medium','High','Urgent'];$tpc=['#10b981','#f59e0b','#f97316','#ef4444'];
        foreach(array_keys($tp) as $i=>$k):?>
        <div class="lr"><div class="ld" style="background:<?=$tpc[$i]?>"></div><span class="ll"><?=$tpl[$i]?></span><span class="lv"><?=$tp[$k]?></span></div>
        <?php endforeach;?>
      </div>
    </div>
  </div>
  <div class="cc">
    <div class="ct">Most Discussed</div><div class="cs">Tasks by comment count</div>
    <?php if($comment_activity):foreach($comment_activity as $ca):?>
    <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border)">
      <div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=h($ca['title'])?></div><div style="font-size:10.5px;color:var(--text3)"><?=date('M j',strtotime($ca['last_c']))?></div></div>
      <span style="background:var(--orange-bg);color:var(--orange);font-size:11px;font-weight:800;padding:2px 7px;border-radius:99px;flex-shrink:0"><?=$ca['cnt']?> 💬</span>
    </div>
    <?php endforeach;else:?><div style="color:var(--text3);font-size:12.5px;padding:20px 0;text-align:center">No comments yet</div><?php endif;?>
  </div>
</div>

<!-- TEAM -->
<div class="sh">👥 Team Performance</div>
<div class="an2">
  <div class="cc">
    <div class="ct">Workload Distribution</div><div class="cs">Open vs completed per member</div>
    <?php if($member_tasks):?>
    <div class="cw" style="height:<?=max(160,count($member_tasks)*34+20)?>px"><canvas id="cTeam"></canvas></div>
    <?php else:?><div style="text-align:center;padding:30px;color:var(--text3)">No task data</div><?php endif;?>
  </div>
  <div class="cc">
    <div class="ct">On-Time Performance</div><div class="cs">% tasks completed before deadline</div>
    <?php foreach($team_prod as $tp_r):
      $rate=$tp_r['done']>0?round($tp_r['on_time']/$tp_r['done']*100):0;
      $col=$rate>=80?'#10b981':($rate>=50?'#f59e0b':'#ef4444');?>
    <div class="pr">
      <div class="pn"><?=h($tp_r['name'])?></div>
      <div class="pb"><div class="pf" style="width:<?=$rate?>%;background:<?=$col?>"></div></div>
      <div class="pv" style="color:<?=$col?>"><?=$rate?>%</div>
      <div style="font-size:10.5px;color:var(--text3);width:46px;text-align:right;flex-shrink:0"><?=$tp_r['done']?> done</div>
    </div>
    <?php endforeach;?>
  </div>
</div>

<!-- SALES PIPELINE -->
<?php if($lead_stages):?>
<div class="sh">🎯 Sales Pipeline</div>
<div class="an2">
  <div class="cc">
    <div class="ct">Lead Funnel</div><div class="cs"><?=$lead_total?> total · <?=$conv_rate?>% conversion</div>
    <?php
    $fstages=['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','proposal'=>'Proposal','negotiation'=>'Negotiating','won'=>'Won','lost'=>'Lost'];
    $fcols=['new'=>'#94a3b8','contacted'=>'#6366f1','qualified'=>'#3b82f6','proposal'=>'#f59e0b','negotiation'=>'#f97316','won'=>'#10b981','lost'=>'#ef4444'];
    $smap=[]; foreach($lead_stages as $r) $smap[$r['stage']]=(int)$r['cnt'];
    $mx=max(array_values($smap)?:[1]);
    foreach($fstages as $sk=>$sl):$cnt=$smap[$sk]??0;$pct=$mx>0?round($cnt/$mx*100):0;?>
    <div class="fb">
      <div class="fbl"><?=$sl?></div>
      <div class="fbw"><div class="fbf" style="width:<?=max($pct,2)?>%;background:<?=$fcols[$sk]?>"><?=$cnt>0?$cnt:''?></div></div>
      <div class="fbc"><?=$cnt?></div>
    </div>
    <?php endforeach;?>
  </div>
  <?php if($lead_sources):?>
  <div class="cc">
    <div class="ct">Lead Sources</div><div class="cs">Total vs won per channel</div>
    <div class="cw" style="height:200px"><canvas id="cLS"></canvas></div>
  </div>
  <?php endif;?>
</div>
<?php endif;?>

<!-- EXPENSES -->
<?php if($exp_by_cat):?>
<div class="sh">💸 Expenses</div>
<div class="an2">
  <div class="cc">
    <div class="ct">By Category</div><div class="cs">Last 3 months · Total: <?=$sym.number_format($exp_total,0)?></div>
    <div class="cw" style="height:200px"><canvas id="cEC"></canvas></div>
  </div>
  <?php if($exp_by_month):?>
  <div class="cc">
    <div class="ct">Revenue vs Expenses</div><div class="cs">Monthly comparison</div>
    <div class="cw" style="height:200px"><canvas id="cRE"></canvas></div>
  </div>
  <?php endif;?>
</div>
<?php endif;?>

<!-- PROJECTS -->
<div class="sh">📁 Projects</div>
<div class="an2">
  <div class="cc">
    <div class="ct">Portfolio Status</div><div class="cs">All projects by stage</div>
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <div style="width:140px;height:140px;flex-shrink:0"><canvas id="cPS"></canvas></div>
      <div style="flex:1">
        <?php $psl=['Planning','Active','On Hold','Completed','Cancelled'];$psc=['#6366f1','#10b981','#94a3b8','#f97316','#ef4444'];
        foreach(array_keys($ps) as $i=>$k):?>
        <div class="lr"><div class="ld" style="background:<?=$psc[$i]?>"></div><span class="ll"><?=$psl[$i]?></span><span class="lv"><?=$ps[$k]?></span></div>
        <?php endforeach;?>
      </div>
    </div>
  </div>
  <div class="cc">
    <div class="ct">Contacts</div><div class="cs">Relationship types</div>
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <div style="width:140px;height:140px;flex-shrink:0"><canvas id="cCT"></canvas></div>
      <div style="flex:1">
        <?php $ctl=['Client','Lead','Partner','Vendor'];$ctc=['#10b981','#f97316','#6366f1','#f59e0b'];
        foreach(array_keys($ct) as $i=>$k):?>
        <div class="lr"><div class="ld" style="background:<?=$ctc[$i]?>"></div><span class="ll"><?=$ctl[$i]?></span><span class="lv"><?=$ct[$k]?></span></div>
        <?php endforeach;?>
      </div>
    </div>
  </div>
</div>

<?php if($top_projects):?>
<div class="cc" style="margin-bottom:18px">
  <div class="ct">Project Progress</div><div class="cs">Task completion per project</div>
  <div class="table-wrap"><table>
    <thead><tr><th>Project</th><th>Status</th><th>Progress</th><th>Tasks</th><th>Due</th></tr></thead>
    <tbody>
    <?php foreach($top_projects as $p):
      $pct=$p['total']>0?round($p['done']/$p['total']*100):0;
      $sc=statusColor($p['status']);$od=$p['due_date']&&$p['due_date']<date('Y-m-d')&&$p['status']!=='completed';?>
    <tr>
      <td class="td-main"><?=h($p['title'])?></td>
      <td><span class="badge" style="background:<?=$sc?>20;color:<?=$sc?>"><?=h(str_replace('_',' ',$p['status']))?></span></td>
      <td style="min-width:140px"><div style="display:flex;align-items:center;gap:6px"><div class="progress-bar" style="flex:1"><div class="progress-fill" style="width:<?=$pct?>%;background:<?=$pct>=75?'#10b981':($pct>=40?'#f59e0b':'#ef4444')?>"></div></div><span style="font-size:11px;color:var(--text3);width:30px;text-align:right"><?=$pct?>%</span></div></td>
      <td><span style="color:var(--green);font-weight:700"><?=$p['done']?></span><span style="color:var(--text3)"> / <?=$p['total']?></span></td>
      <td style="<?=$od?'color:var(--red)':''?>"><?=fDate($p['due_date'])?><?=$od?' ⚠':''?></td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table></div>
</div>
<?php endif;?>

<!-- ACTIVITY & EMAIL -->
<div class="sh">⚡ Activity & Communications</div>
<div class="an2">
  <div class="cc">
    <div class="ct">Activity by Hour</div><div class="cs">Team heatmap — last 30 days</div>
    <div class="hg" id="heatmap"></div>
    <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--text3);margin-top:5px"><span>12am</span><span>6am</span><span>12pm</span><span>6pm</span><span>11pm</span></div>
  </div>
  <div class="cc">
    <div class="ct">Email Performance</div><div class="cs">Outbound email tracking</div>
    <?php if((int)$email_stat['total']>0):?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px">
      <?php foreach([['Sent',$email_stat['sent'],'#10b981'],['Failed',$email_stat['failed'],'#ef4444'],['Opened',$email_stat['opened'],'#6366f1'],['Total',$email_stat['total'],'#f97316']] as [$l,$v,$c]):?>
      <div style="background:var(--bg3);border-radius:var(--radius-sm);padding:9px 10px;border-left:3px solid <?=$c?>"><div style="font-size:16px;font-weight:800;color:<?=$c?>"><?=$v?></div><div style="font-size:10px;color:var(--text3);text-transform:uppercase"><?=$l?></div></div>
      <?php endforeach;?>
    </div>
    <?php if($email_days):?><div class="cw" style="height:90px"><canvas id="cEM"></canvas></div><?php endif;?>
    <?php else:?><div style="text-align:center;padding:28px;color:var(--text3)"><div style="font-size:26px;margin-bottom:6px">📧</div>No emails sent yet</div><?php endif;?>
  </div>
</div>

<?php if($doc_cats):?>
<div class="cc" style="margin-bottom:18px">
  <div class="ct">Documents by Category</div><div class="cs">File store distribution</div>
  <div class="cw" style="height:140px"><canvas id="cDC"></canvas></div>
</div>
<?php endif;?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
function cv(v){return getComputedStyle(document.documentElement).getPropertyValue(v).trim()}
Chart.defaults.font.family="'Plus Jakarta Sans',sans-serif";
Chart.defaults.font.size=12;

function sc(id,cfg){
  var el=document.getElementById(id);
  if(el)new Chart(el,cfg);
}

var gx={grid:{color:cv('--border')},ticks:{color:cv('--text2')}};
var gy={grid:{color:cv('--border')},ticks:{color:cv('--text2'),precision:0},beginAtZero:true};

// Timeline
sc('cTL',{type:'line',data:{labels:<?=json_encode($tl)?>,datasets:[
  {label:'Created',data:<?=json_encode($tc)?>,borderColor:'#f97316',backgroundColor:'rgba(249,115,22,.1)',fill:true,tension:.4,pointRadius:<?=$days_back<=30?3:0?>,borderWidth:2},
  {label:'Completed',data:<?=json_encode($td)?>,borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.08)',fill:true,tension:.4,pointRadius:<?=$days_back<=30?3:0?>,borderWidth:2}
]},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},
  plugins:{legend:{position:'top',labels:{boxWidth:10,padding:14,color:cv('--text2')}}},
  scales:{x:{...gx,ticks:{color:cv('--text2'),maxTicksLimit:12}},y:gy}}});

// Doughnuts
sc('cTS',{type:'doughnut',data:{labels:['To Do','In Progress','Review','Done'],datasets:[{data:<?=json_encode(array_values($ts))?>,backgroundColor:['#6366f1','#f59e0b','#8b5cf6','#10b981'],borderWidth:0,hoverOffset:4}]},options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false}}}});
sc('cTP',{type:'doughnut',data:{labels:['Low','Medium','High','Urgent'],datasets:[{data:<?=json_encode(array_values($tp))?>,backgroundColor:['#10b981','#f59e0b','#f97316','#ef4444'],borderWidth:0,hoverOffset:4}]},options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false}}}});
sc('cPS',{type:'doughnut',data:{labels:['Planning','Active','On Hold','Completed','Cancelled'],datasets:[{data:<?=json_encode(array_values($ps))?>,backgroundColor:['#6366f1','#10b981','#94a3b8','#f97316','#ef4444'],borderWidth:0,hoverOffset:4}]},options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false}}}});
sc('cCT',{type:'doughnut',data:{labels:['Client','Lead','Partner','Vendor'],datasets:[{data:<?=json_encode(array_values($ct))?>,backgroundColor:['#10b981','#f97316','#6366f1','#f59e0b'],borderWidth:0,hoverOffset:4}]},options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false}}}});

<?php if($member_tasks):?>
sc('cTeam',{type:'bar',data:{labels:<?=json_encode(array_column($member_tasks,'name'))?>,datasets:[
  {label:'Open',data:<?=json_encode(array_column($member_tasks,'open_cnt'))?>,backgroundColor:'#f97316',borderRadius:4},
  {label:'Done',data:<?=json_encode(array_column($member_tasks,'done_cnt'))?>,backgroundColor:'#10b981',borderRadius:4}
]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,
  plugins:{legend:{position:'top',labels:{boxWidth:10,padding:12,color:cv('--text2')}}},
  scales:{x:{...gy,grid:{color:cv('--border')}},y:{grid:{display:false},ticks:{color:cv('--text2')}}}}});
<?php endif;?>

<?php if($rev_months):?>
sc('cRev',{type:'bar',data:{labels:<?=json_encode(array_column($rev_months,'mo'))?>,datasets:[{label:'Collected',data:<?=json_encode(array_map(fn($r)=>(float)$r['amt'],$rev_months))?>,backgroundColor:'rgba(16,185,129,.75)',borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{...gx,grid:{display:false}},y:{...gy,ticks:{...gy.ticks,callback:function(v){return 'Rs.'+v.toLocaleString()}}}}}});
<?php endif;?>

<?php if($lead_sources):?>
sc('cLS',{type:'bar',data:{labels:<?=json_encode(array_column($lead_sources,'source'))?>,datasets:[
  {label:'Total',data:<?=json_encode(array_map(fn($r)=>(int)$r['cnt'],$lead_sources))?>,backgroundColor:'rgba(99,102,241,.7)',borderRadius:4},
  {label:'Won',data:<?=json_encode(array_map(fn($r)=>(int)$r['won_cnt'],$lead_sources))?>,backgroundColor:'rgba(16,185,129,.85)',borderRadius:4}
]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{boxWidth:10,padding:10,color:cv('--text2')}}},scales:{x:{...gx,grid:{display:false}},y:gy}}});
<?php endif;?>

<?php if($exp_by_cat):?>
sc('cEC',{type:'doughnut',data:{labels:<?=json_encode(array_column($exp_by_cat,'category'))?>,datasets:[{data:<?=json_encode(array_map(fn($r)=>(float)$r['total'],$exp_by_cat))?>,backgroundColor:['#f97316','#6366f1','#10b981','#f59e0b','#8b5cf6','#ef4444','#94a3b8','#14b8a6'],borderWidth:2,borderColor:cv('--bg2'),hoverOffset:4}]},options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{position:'right',labels:{boxWidth:10,padding:8,color:cv('--text2'),font:{size:11}}}}}});
<?php endif;?>

<?php if($exp_by_month):?>
sc('cRE',{type:'bar',data:{labels:<?=json_encode(array_column($exp_by_month,'mo'))?>,datasets:[
  {label:'Revenue',data:<?=json_encode(array_map(fn($r)=>(float)$r['revenue'],$exp_by_month))?>,backgroundColor:'rgba(16,185,129,.7)',borderRadius:4},
  {label:'Expenses',data:<?=json_encode(array_map(fn($r)=>(float)$r['total'],$exp_by_month))?>,backgroundColor:'rgba(239,68,68,.65)',borderRadius:4}
]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{boxWidth:10,padding:10,color:cv('--text2')}}},scales:{x:{...gx,grid:{display:false}},y:gy}}});
<?php endif;?>

<?php if($email_days):?>
sc('cEM',{type:'line',data:{labels:<?=json_encode(array_map(fn($r)=>date('M j',strtotime($r['d'])),$email_days))?>,datasets:[
  {label:'Total',data:<?=json_encode(array_map(fn($r)=>(int)$r['total'],$email_days))?>,borderColor:'#f97316',fill:false,tension:.4,pointRadius:2,borderWidth:1.5},
  {label:'Sent',data:<?=json_encode(array_map(fn($r)=>(int)$r['sent'],$email_days))?>,borderColor:'#10b981',fill:false,tension:.4,pointRadius:2,borderWidth:1.5}
]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{boxWidth:8,padding:8,color:cv('--text2'),font:{size:10}}}},scales:{x:{...gx,ticks:{color:cv('--text2'),font:{size:9}}},y:{...gy,ticks:{...gy.ticks}}}}});
<?php endif;?>

<?php if($doc_cats):?>
sc('cDC',{type:'bar',data:{labels:<?=json_encode(array_column($doc_cats,'category'))?>,datasets:[{label:'Docs',data:<?=json_encode(array_column($doc_cats,'c'))?>,backgroundColor:['#f97316','#6366f1','#10b981','#f59e0b','#8b5cf6','#ef4444','#94a3b8','#14b8a6'],borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{...gx,grid:{display:false}},y:gy}}});
<?php endif;?>

// Heatmap
(function(){
  var d=<?=json_encode($hr_data)?>,mx=Math.max.apply(null,d)||1,h=document.getElementById('heatmap');
  if(!h)return;
  for(var i=0;i<24;i++){var c=document.createElement('div');c.className='hc';c.style.background='rgba(249,115,22,'+(0.08+(d[i]/mx)*0.87).toFixed(2)+')';c.dataset.t=i+':00 — '+d[i]+' actions';h.appendChild(c);}
})();

document.addEventListener('themeChanged',function(){
  Chart.helpers.each(Chart.instances,function(ch){
    if(ch.options.scales)Object.values(ch.options.scales).forEach(function(s){if(s.grid)s.grid.color=cv('--border');if(s.ticks)s.ticks.color=cv('--text2');});
    if(ch.options.plugins&&ch.options.plugins.legend&&ch.options.plugins.legend.labels)ch.options.plugins.legend.labels.color=cv('--text2');
    ch.update('none');
  });
});
</script>
<?php renderLayoutEnd();?>