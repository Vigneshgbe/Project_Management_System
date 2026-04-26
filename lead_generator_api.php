<?php
/**
 * lead_generator_api.php
 * Google Places API - Text Search + Place Details
 * Fields: name, owner_name, phone, email (scraped), address, website, rating
 * Cost: $0.032 per search + $0.003 per details lookup = ~$0.035/lead
 */
require_once 'config.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];
mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function lgGet(mysqli $db, string $k, string $def = ''): string {
    $r = @$db->query("SELECT setting_val FROM lead_gen_settings WHERE setting_key='".$db->real_escape_string($k)."' LIMIT 1");
    if (!$r) return $def;
    $row = $r->fetch_assoc();
    return $row ? trim((string)($row['setting_val'] ?? $def)) : $def;
}
function lgTableOk(mysqli $db): bool {
    return @$db->query("SELECT 1 FROM lead_gen_settings LIMIT 1") !== false;
}
function lgMonthUsed(mysqli $db, int $uid): array {
    $r = @$db->query("SELECT COALESCE(SUM(result_count),0) leads, COALESCE(SUM(api_calls_used),0) calls, COALESCE(SUM(estimated_cost),0) cost
                      FROM lead_gen_usage
                      WHERE user_id=$uid AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())");
    return $r ? ($r->fetch_assoc() ?: ['leads'=>0,'calls'=>0,'cost'=>0]) : ['leads'=>0,'calls'=>0,'cost'=>0];
}
function lgHttp(string $url): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; PadakCRM/1.0)',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
        ]);
        $r    = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($code >= 200 && $code < 400 && $r) ? $r : null;
    }
    $ctx = stream_context_create(['http'=>['timeout'=>15,'ignore_errors'=>true,'user_agent'=>'Mozilla/5.0 (compatible; PadakCRM/1.0)'],'ssl'=>['verify_peer'=>false]]);
    $r = @file_get_contents($url, false, $ctx);
    return $r !== false ? $r : null;
}

// Scrape email from website homepage
function lgScrapeEmail(string $url): string {
    if (!$url) return '';
    $base  = rtrim(preg_replace('#^(https?://[^/]+).*#', '$1', $url), '/');
    $pages = [$url, $base.'/contact', $base.'/contact-us'];
    foreach ($pages as $page) {
        $html = lgHttp($page);
        if (!$html) continue;
        if (preg_match_all('/mailto:([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,6})/i', $html, $m)) {
            foreach ($m[1] as $candidate) {
                $e = strtolower(trim($candidate));
                if (!preg_match('/(example|noreply|no-reply|sentry|w3\.org|schema|privacy|legal|unsubscribe)/i', $e))
                    return $e;
            }
        }
        if (preg_match_all('/\b([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,6})\b/', $html, $m)) {
            foreach ($m[1] as $candidate) {
                $e = strtolower(trim($candidate));
                if (!preg_match('/(example|noreply|no-reply|sentry|w3\.org|schema|privacy|legal|unsubscribe|\.png|\.jpg|\.gif)/i', $e))
                    return $e;
            }
        }
    }
    return '';
}

// Safe column migration - adds new columns if missing
function lgEnsureColumns(mysqli $db): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $cols = [];
    $r = @$db->query("SHOW COLUMNS FROM lead_gen_results");
    if ($r) { while ($row = $r->fetch_assoc()) $cols[] = $row['Field']; }
    $add = [];
    if (!in_array('owner_name',        $cols)) $add[] = "ADD COLUMN owner_name        VARCHAR(200) DEFAULT NULL AFTER name";
    if (!in_array('email',             $cols)) $add[] = "ADD COLUMN email             VARCHAR(200) DEFAULT NULL AFTER phone";
    if (!in_array('has_website',       $cols)) $add[] = "ADD COLUMN has_website       TINYINT(1) DEFAULT 0 AFTER website";
    if (!in_array('ratings_total',     $cols)) $add[] = "ADD COLUMN ratings_total     INT DEFAULT 0 AFTER rating";
    if (!in_array('price_level',       $cols)) $add[] = "ADD COLUMN price_level       TINYINT DEFAULT NULL AFTER ratings_total";
    if (!in_array('opportunity_score', $cols)) $add[] = "ADD COLUMN opportunity_score INT DEFAULT 0 AFTER price_level";
    if (!in_array('api_calls',         $cols)) $add[] = "ADD COLUMN api_calls         INT DEFAULT 0";
    if ($add) @$db->query("ALTER TABLE lead_gen_results " . implode(', ', $add));
}

/**
 * Get the effective monthly lead quota for a specific user.
 * Priority: per-user override > role default > global fallback.
 * Stored in lead_gen_settings as:
 *   quota_user_{id}     = per-user override (admin sets this)
 *   quota_role_admin    = default for admin role
 *   quota_role_manager  = default for manager role
 *   quota_role_member   = default for member role
 */
function lgUserQuota(mysqli $db, int $uid, string $role): int {
    // 1. Per-user override
    $override = lgGet($db, "quota_user_$uid");
    if ($override !== '') return max(1, (int)$override);
    // 2. Role default
    $roleKey = "quota_role_$role";
    $roleQ   = lgGet($db, $roleKey);
    if ($roleQ !== '') return max(1, (int)$roleQ);
    // 3. Global fallback
    return max(1, (int)lgGet($db, 'monthly_quota', '300'));
}

/** Get this month's lead count for a specific user only. */
function lgUserMonthUsed(mysqli $db, int $uid): int {
    $r = @$db->query("SELECT COALESCE(SUM(result_count),0) c FROM lead_gen_usage
                      WHERE user_id=$uid AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())");
    return $r ? (int)($r->fetch_assoc()['c'] ?? 0) : 0;
}

if (!lgTableOk($db)) {
    echo json_encode(['ok'=>false,'error'=>'Run migration_v11.sql and migration_v14.sql first.']);
    exit;
}
lgEnsureColumns($db);

// ============================================================
// SEARCH
// ============================================================
if ($action === 'search') {
    $location    = trim($_POST['location'] ?? '');
    $industry    = trim($_POST['industry'] ?? '');
    $count       = min(20, max(1, (int)($_POST['count'] ?? 5)));
    $search_mode = in_array($_POST['search_mode'] ?? '', ['no_website','high_value','all'])
                   ? $_POST['search_mode'] : 'all';

    if (!$location || !$industry) {
        echo json_encode(['ok'=>false,'error'=>'Location and industry are required']); exit;
    }
    $api_key = lgGet($db, 'google_api_key');
    if (!$api_key) {
        echo json_encode(['ok'=>false,'error'=>'Google API key not configured. Click Settings.']); exit;
    }

    // Per-user quota (role-based with admin override)
    $user_role  = $_SESSION['crm_role'] ?? 'member';
    $quota      = lgUserQuota($db, $uid, $user_role);
    $budget_usd = (float)lgGet($db, 'monthly_budget_usd', '15.00');
    $cost_ts    = (float)lgGet($db, 'cost_per_textsearch', '0.032');
    $cost_det   = (float)lgGet($db, 'cost_per_details', '0.003');
    $used_leads = lgUserMonthUsed($db, $uid);
    $usage      = lgMonthUsed($db, $uid);
    $used_cost  = (float)$usage['cost'];

    if ($used_leads + $count > $quota) {
        echo json_encode(['ok'=>false,'error'=>"Your monthly quota of $quota leads reached ($used_leads used). Contact admin to increase your limit."]); exit;
    }
    if ($used_cost + ($cost_ts + $cost_det * $count) > $budget_usd) {
        $rem = round($budget_usd - $used_cost, 4);
        echo json_encode(['ok'=>false,'error'=>"Monthly budget \$$budget_usd reached. Remaining: \$$rem."]); exit;
    }

    $query      = urlencode("$industry in $location");
    $search_url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=$query&key=$api_key";
    $raw        = lgHttp($search_url);
    $api_calls  = 1;

    if (!$raw) { echo json_encode(['ok'=>false,'error'=>'Cannot reach Google API.']); exit; }
    $data = json_decode($raw, true);
    if (!isset($data['status'])) { echo json_encode(['ok'=>false,'error'=>'Invalid Google API response.']); exit; }

    if ($data['status'] === 'REQUEST_DENIED') {
        $msg = $data['error_message'] ?? '';
        echo json_encode(['ok'=>false,'error'=>strpos($msg,'Places API')!==false
            ? "Places API not enabled.\nCloud Console => APIs & Services => Enable Places API.\n\n$msg"
            : "API key error: $msg"]);
        exit;
    }
    if ($data['status'] === 'ZERO_RESULTS' || empty($data['results'])) {
        echo json_encode(['ok'=>true,'leads'=>[],'message'=>"No businesses found for '$industry' in '$location'."]); exit;
    }
    if (!in_array($data['status'], ['OK','ZERO_RESULTS'])) {
        echo json_encode(['ok'=>false,'error'=>'Google API: '.$data['status'].' - '.($data['error_message']??'')]); exit;
    }

    $results = array_slice($data['results'], 0, $count);
    $leads   = [];

    foreach ($results as $place) {
        $place_id      = $place['place_id'] ?? '';
        $name          = $place['name'] ?? '';
        if (!$name) continue;
        $address       = $place['formatted_address'] ?? ($place['vicinity'] ?? '');
        $rating        = isset($place['rating']) ? (float)$place['rating'] : null;
        $phone         = ''; $website = ''; $email = ''; $owner_name = ''; $has_website = 0;
        $ratings_total = 0; $price_level = null;

        if ($place_id) {
            $fields  = 'formatted_phone_number,international_phone_number,website,reviews,user_ratings_total,price_level';
            $det_url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$place_id&fields=$fields&key=$api_key";
            $det_raw = lgHttp($det_url);
            $api_calls++;
            if ($det_raw) {
                $det           = json_decode($det_raw, true);
                $res           = $det['result'] ?? [];
                $phone         = $res['formatted_phone_number'] ?? ($res['international_phone_number'] ?? '');
                $website       = $res['website'] ?? '';
                $has_website   = $website ? 1 : 0;
                $ratings_total = (int)($res['user_ratings_total'] ?? 0);
                $price_level   = isset($res['price_level']) ? (int)$res['price_level'] : null;
                if (!empty($res['reviews'])) {
                    foreach ($res['reviews'] as $rev) {
                        if (!empty($rev['owner_reply']['author_name'])) {
                            $owner_name = $rev['owner_reply']['author_name']; break;
                        }
                    }
                }
            }
        }

        if ($website && !$email) {
            $email = lgScrapeEmail($website);
        }

        // Opportunity score (0-100): no website = top priority for web dev company
        $opp = 0;
        if (!$has_website)             $opp += 50; // Primary: needs a website
        if ($ratings_total >= 100)     $opp += 20; // Very established = can pay
        elseif ($ratings_total >= 30)  $opp += 12;
        elseif ($ratings_total >= 10)  $opp +=  6;
        if ($price_level !== null) {
            if ($price_level >= 3)     $opp += 15; // Upscale
            elseif ($price_level === 2)$opp +=  8;
        }
        if ($rating !== null && $rating >= 4.0) $opp += 10; // Cares about image
        if ($phone)                    $opp +=  5; // Reachable

        // Human-readable reason
        $reasons = [];
        if (!$has_website)      $reasons[] = 'No website - needs one built';
        if ($ratings_total>=30) $reasons[] = $ratings_total.' Google reviews - established';
        if ($price_level>=3)    $reasons[] = 'Upscale business - larger budget likely';
        if ($rating>=4.5)       $reasons[] = 'Highly rated '.number_format((float)$rating,1).'/5';
        $why = implode('. ', $reasons);

        $pe  = $db->real_escape_string($place_id);
        $ne  = $db->real_escape_string($name);
        $one = $db->real_escape_string($owner_name);
        $phe = $db->real_escape_string($phone);
        $ee  = $db->real_escape_string($email);
        $ae  = $db->real_escape_string($address);
        $we  = $db->real_escape_string($website);
        $le  = $db->real_escape_string($location);
        $ie  = $db->real_escape_string($industry);
        $rt  = $rating !== null ? (float)$rating : 'NULL';
        $pl  = $price_level !== null ? (int)$price_level : 'NULL';

        $db->query("INSERT INTO lead_gen_results
            (user_id,place_id,name,owner_name,phone,email,address,website,has_website,
             rating,ratings_total,price_level,opportunity_score,location,industry,imported,api_calls)
            VALUES ($uid,'$pe','$ne','$one','$phe','$ee','$ae','$we',$has_website,
                    $rt,$ratings_total,$pl,$opp,'$le','$ie',0,$api_calls)
            ON DUPLICATE KEY UPDATE
                name='$ne',owner_name='$one',phone='$phe',email='$ee',
                address='$ae',website='$we',has_website=$has_website,
                ratings_total=$ratings_total,price_level=$pl,opportunity_score=$opp");

        $rid = (int)($db->insert_id ?: 0);
        if (!$rid && $place_id) {
            $ex = @$db->query("SELECT id FROM lead_gen_results WHERE place_id='$pe' AND user_id=$uid LIMIT 1");
            if ($ex) $rid = (int)($ex->fetch_assoc()['id'] ?? 0);
        }

        $leads[] = ['id'=>$rid,'place_id'=>$place_id,'name'=>$name,'owner_name'=>$owner_name,
                    'phone'=>$phone,'email'=>$email,'address'=>$address,'website'=>$website,
                    'has_website'=>$has_website,'rating'=>$rating,'ratings_total'=>$ratings_total,
                    'opportunity_score'=>$opp,'why'=>$why,'imported'=>false];
    }

    // Sort: no-website first, then by opportunity score desc
    usort($leads, function($a,$b){
        $an = !$a['has_website'] ? 1 : 0;
        $bn = !$b['has_website'] ? 1 : 0;
        if ($an !== $bn) return $bn - $an;
        return $b['opportunity_score'] - $a['opportunity_score'];
    });

    // Apply mode filter
    if ($search_mode === 'no_website') {
        $leads = array_values(array_filter($leads, fn($l) => !$l['has_website']));
    } elseif ($search_mode === 'high_value') {
        $leads = array_values(array_filter($leads, fn($l) => $l['opportunity_score'] >= 50));
    }

    $real_count  = count($leads);
    $actual_cost = round($cost_ts + ($cost_det * $real_count), 6);
    $le_e = $db->real_escape_string($location);
    $ie_e = $db->real_escape_string($industry);
    $db->query("INSERT INTO lead_gen_usage (user_id,location,industry,result_count,api_calls_used,estimated_cost)
        VALUES ($uid,'$le_e','$ie_e',$real_count,$api_calls,'$actual_cost')");
    logActivity('generated leads', "$industry in $location", 0, "$real_count leads");

    $new_usage = lgMonthUsed($db, $uid);
    echo json_encode(['ok'=>true,'leads'=>$leads,
        'used'=>(int)$new_usage['leads'],'quota'=>$quota,
        'cost'=>round((float)$new_usage['cost'],4),'budget'=>$budget_usd]);
    exit;
}

// ============================================================
// GET ALL STORED (Document 4 exact - no new columns in query)
// ============================================================
if ($action === 'get_all_stored') {
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $per_page = max(10, min(200, (int)($_GET['per_page'] ?? 50)));
    $offset   = ($page - 1) * $per_page;
    $search   = trim($_GET['search']   ?? '');
    $location = trim($_GET['location'] ?? '');
    $industry = trim($_GET['industry'] ?? '');
    $website  = $_GET['website']  ?? '';
    $imported = $_GET['imported'] ?? '';

    $where = ['1=1'];
    if (!isManager()) $where[] = "r.user_id = $uid";
    if ($search !== '') {
        $se = $db->real_escape_string($search);
        $where[] = "(r.name LIKE '%$se%' OR r.location LIKE '%$se%' OR r.industry LIKE '%$se%' OR r.phone LIKE '%$se%' OR r.address LIKE '%$se%')";
    }
    if ($location !== '') { $le = $db->real_escape_string($location); $where[] = "r.location='$le'"; }
    if ($industry !== '') { $ie = $db->real_escape_string($industry); $where[] = "r.industry='$ie'"; }
    if ($website  === '1') $where[] = "r.has_website=1";
    elseif ($website === '0') $where[] = "r.has_website=0";
    if ($imported === '1') $where[] = "r.imported=1";
    elseif ($imported === '0') $where[] = "r.imported=0";

    $whereSQL = implode(' AND ', $where);
    $cnt_result = @$db->query("SELECT COUNT(*) c FROM lead_gen_results r WHERE $whereSQL");
    $total = $cnt_result ? (int)($cnt_result->fetch_assoc()['c'] ?? 0) : 0;

    $rows = [];
    $q = @$db->query("SELECT r.id, r.name, r.phone, r.address,
                             r.website, r.has_website, r.rating, r.location, r.industry,
                             r.imported, r.lead_id, r.created_at
                      FROM lead_gen_results r WHERE $whereSQL
                      ORDER BY r.id DESC LIMIT $per_page OFFSET $offset");
    if ($q) { while ($row = $q->fetch_assoc()) $rows[] = $row; }

    // Add optional columns if they exist
    foreach ($rows as &$row) {
        $row['owner_name'] = $row['owner_name'] ?? '';
        $row['email']      = $row['email'] ?? '';
    }
    unset($row);

    $locations = []; $industries = [];
    $lq = @$db->query("SELECT DISTINCT location FROM lead_gen_results WHERE location IS NOT NULL AND location!='' ORDER BY location");
    if ($lq) { while ($row=$lq->fetch_assoc()) $locations[]=$row['location']; }
    $iq = @$db->query("SELECT DISTINCT industry FROM lead_gen_results WHERE industry IS NOT NULL AND industry!='' ORDER BY industry");
    if ($iq) { while ($row=$iq->fetch_assoc()) $industries[]=$row['industry']; }

    echo json_encode(['ok'=>true,'leads'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$per_page,'locations'=>$locations,'industries'=>$industries]);
    exit;
}

// ============================================================
// GET SEARCH HISTORY
// ============================================================
if ($action === 'get_search_history') {
    $usage_id = (int)($_GET['usage_id'] ?? 0);
    if (!$usage_id) { echo json_encode(['ok'=>false,'error'=>'Invalid usage ID']); exit; }
    $urow = @$db->query("SELECT * FROM lead_gen_usage WHERE id=$usage_id AND user_id=$uid LIMIT 1")->fetch_assoc();
    if (!$urow) { echo json_encode(['ok'=>false,'error'=>'Search record not found']); exit; }
    $le = $db->real_escape_string($urow['location']);
    $ie = $db->real_escape_string($urow['industry']);
    $created = $db->real_escape_string($urow['created_at']);
    $q = @$db->query("SELECT * FROM lead_gen_results
                      WHERE user_id=$uid AND location='$le' AND industry='$ie'
                        AND created_at BETWEEN DATE_SUB('$created',INTERVAL 5 MINUTE)
                                            AND DATE_ADD('$created',INTERVAL 5 MINUTE)
                      ORDER BY id ASC LIMIT 20");
    $leads = [];
    if ($q) { while ($row=$q->fetch_assoc()) $leads[]=$row; }
    echo json_encode(['ok'=>true,'leads'=>$leads,'location'=>$urow['location'],'industry'=>$urow['industry']]);
    exit;
}

// ============================================================
// IMPORT LEAD
// ============================================================
if ($action === 'import_lead') {
    $rid = (int)($_POST['result_id'] ?? 0);
    $row = @$db->query("SELECT * FROM lead_gen_results WHERE id=$rid AND user_id=$uid")->fetch_assoc();
    if (!$row) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }
    if ($row['imported']) { echo json_encode(['ok'=>false,'error'=>'Already imported']); exit; }
    $n=$row['name']; $p=$row['phone']??''; $em=$row['email']??'';
    $svc=$row['industry']; $src='other'; $st='new'; $pr='medium'; $bc='LKR'; $null=null;
    $notes = "Lead Generator: {$row['industry']} in {$row['location']}.";
    if (!empty($row['owner_name'])) $notes .= " Owner: {$row['owner_name']}.";
    if (!empty($row['address']))    $notes .= " Address: {$row['address']}.";
    if (!empty($row['website']))    $notes .= " Website: {$row['website']}.";
    if (!empty($row['rating']))     $notes .= " Google Rating: {$row['rating']}/5.";
    $notes .= ($row['has_website'] ?? 0) ? ' Has website: Yes.' : ' Has website: No.';
    $stmt = $db->prepare("INSERT INTO leads (name,company,email,phone,source,service_interest,budget_est,budget_currency,stage,priority,expected_close,last_contact,notes,loss_reason,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssdsssssssii",$n,$n,$em,$p,$src,$svc,$null,$bc,$st,$pr,$null,$null,$notes,$null,$null,$uid);
    $stmt->execute();
    $lid=(int)$db->insert_id;
    $db->query("UPDATE lead_gen_results SET imported=1,lead_id=$lid WHERE id=$rid");
    logActivity('imported lead',$n,$lid);
    echo json_encode(['ok'=>true,'lead_id'=>$lid,'message'=>"$n imported to CRM Leads"]);
    exit;
}

// ============================================================
// IMPORT ALL
// ============================================================
if ($action === 'import_all') {
    $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
    if (!$ids) { echo json_encode(['ok'=>false,'error'=>'No IDs']); exit; }
    $imported_count = 0;
    foreach ($ids as $rid) {
        $row = @$db->query("SELECT * FROM lead_gen_results WHERE id=$rid AND user_id=$uid AND imported=0")->fetch_assoc();
        if (!$row) continue;
        $n=$row['name']; $p=$row['phone']??''; $em=$row['email']??'';
        $svc=$row['industry']; $src='other'; $st='new'; $pr='medium'; $bc='LKR'; $null=null;
        $notes = "Lead Generator: {$row['industry']} in {$row['location']}.";
        if (!empty($row['owner_name'])) $notes .= " Owner: {$row['owner_name']}.";
        if (!empty($row['address']))    $notes .= " Address: {$row['address']}.";
        if (!empty($row['website']))    $notes .= " Web: {$row['website']}.";
        $notes .= ($row['has_website'] ?? 0) ? ' Has website: Yes.' : ' Has website: No.';
        $stmt = $db->prepare("INSERT INTO leads (name,company,email,phone,source,service_interest,budget_est,budget_currency,stage,priority,expected_close,last_contact,notes,loss_reason,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssdsssssssii",$n,$n,$em,$p,$src,$svc,$null,$bc,$st,$pr,$null,$null,$notes,$null,$null,$uid);
        $stmt->execute();
        $lid=(int)$db->insert_id;
        $db->query("UPDATE lead_gen_results SET imported=1,lead_id=$lid WHERE id=$rid");
        $imported_count++;
    }
    logActivity('bulk import leads','',0,"$imported_count leads");
    echo json_encode(['ok'=>true,'imported'=>$imported_count]);
    exit;
}

// ============================================================
// BULK DELETE
// ============================================================
if ($action === 'bulk_delete') {
    if (!isManager()) { echo json_encode(['ok'=>false,'error'=>'Permission denied']); exit; }
    $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
    if (!$ids) { echo json_encode(['ok'=>false,'error'=>'No IDs provided']); exit; }
    $result = @$db->query("DELETE FROM lead_gen_results WHERE id IN(".implode(',',$ids).")");
    echo $result ? json_encode(['ok'=>true,'deleted'=>$db->affected_rows])
                 : json_encode(['ok'=>false,'error'=>'Delete failed']);
    exit;
}

// ============================================================
// GET STATS
// ============================================================
if ($action === 'get_stats') {
    $user_role = $_SESSION['crm_role'] ?? 'member';
    $quota  = lgUserQuota($db, $uid, $user_role);
    $used   = lgUserMonthUsed($db, $uid);
    $budget =(float)lgGet($db,'monthly_budget_usd','15.00');
    $api_set= lgGet($db,'google_api_key') !== '';
    $usage  = lgMonthUsed($db,$uid);
    $trend=[];
    $tr=@$db->query("SELECT DATE_FORMAT(created_at,'%b') mo,DATE_FORMAT(created_at,'%Y-%m') smo,SUM(result_count) cnt,SUM(estimated_cost) cost FROM lead_gen_usage WHERE user_id=$uid AND created_at>=DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY mo,smo ORDER BY smo");
    if ($tr) $trend=$tr->fetch_all(MYSQLI_ASSOC);
    $recent=[];
    $rc=@$db->query("SELECT id,industry,location,result_count,estimated_cost,created_at FROM lead_gen_usage WHERE user_id=$uid ORDER BY created_at DESC LIMIT 5");
    if ($rc) $recent=$rc->fetch_all(MYSQLI_ASSOC);
    $total_imp=(int)(@$db->query("SELECT COUNT(*) c FROM lead_gen_results WHERE user_id=$uid AND imported=1")->fetch_assoc()['c']??0);
    $total_all=(int)(@$db->query("SELECT COUNT(*) c FROM lead_gen_results WHERE user_id=$uid")->fetch_assoc()['c']??0);

    // Admin/manager: return all users usage for quota management panel
    $all_users = [];
    if (in_array($user_role, ['admin','manager'])) {
        $uq = @$db->query("SELECT u.id,u.name,u.role,
            COALESCE(SUM(CASE WHEN YEAR(lu.created_at)=YEAR(NOW()) AND MONTH(lu.created_at)=MONTH(NOW()) THEN lu.result_count ELSE 0 END),0) used_month,
            COALESCE(SUM(lu.result_count),0) used_total
            FROM users u
            LEFT JOIN lead_gen_usage lu ON lu.user_id=u.id
            WHERE u.status='active'
            GROUP BY u.id,u.name,u.role
            ORDER BY used_month DESC");
        if ($uq) {
            while ($row=$uq->fetch_assoc()) {
                $row['quota']    = lgUserQuota($db,(int)$row['id'],$row['role']);
                $row['override'] = lgGet($db,'quota_user_'.$row['id']);
                $all_users[] = $row;
            }
        }
    }

    echo json_encode(['ok'=>true,'used'=>$used,'quota'=>$quota,
        'cost'=>round((float)$usage['cost'],4),'budget'=>$budget,'api_set'=>$api_set,
        'trend'=>$trend,'recent'=>$recent,'total_imp'=>$total_imp,'total_all'=>$total_all,
        'user_role'=>$user_role,'all_users'=>$all_users]);
    exit;
}

// ============================================================
// SAVE SETTINGS
// ============================================================
if ($action === 'save_settings' && isAdmin()) {
    $g_key  = trim($_POST['google_key'] ?? '');
    $quota  = max(10, min(5000, (int)($_POST['quota'] ?? 300)));
    $budget = max(1, min(180, (float)($_POST['budget'] ?? 15)));
    $db->query("UPDATE lead_gen_settings SET setting_val='google',updated_by=$uid WHERE setting_key='api_provider'");
    if ($g_key) { $gke=$db->real_escape_string($g_key); $db->query("UPDATE lead_gen_settings SET setting_val='$gke',updated_by=$uid WHERE setting_key='google_api_key'"); }
    $db->query("UPDATE lead_gen_settings SET setting_val=$quota,updated_by=$uid WHERE setting_key='monthly_quota'");
    $db->query("UPDATE lead_gen_settings SET setting_val=$budget,updated_by=$uid WHERE setting_key='monthly_budget_usd'");
    echo json_encode(['ok'=>true,'message'=>'Settings saved']);
    exit;
}

// ============================================================
// TEST KEY
// ============================================================
if ($action === 'test_key') {
    $key = trim($_POST['google_key'] ?? lgGet($db,'google_api_key'));
    if (!$key) { echo json_encode(['ok'=>false,'error'=>'No API key provided']); exit; }
    $raw = lgHttp("https://maps.googleapis.com/maps/api/place/textsearch/json?query=restaurant+in+London&key=$key");
    if (!$raw) { echo json_encode(['ok'=>false,'error'=>'Cannot reach Google API from your server']); exit; }
    $d = json_decode($raw, true);
    if (!empty($d['results'])) { echo json_encode(['ok'=>true,'message'=>'API key works! Found: '.$d['results'][0]['name']]); }
    elseif (($d['status']??'') === 'REQUEST_DENIED') {
        $msg=$d['error_message']??'Request denied';
        echo json_encode(['ok'=>false,'error'=>strpos($msg,'Places API')!==false?"Places API not enabled.\nCloud Console => APIs & Services => Enable Places API.\n\n$msg":"Key rejected: $msg"]);
    } elseif (($d['status']??'') === 'OVER_QUERY_LIMIT') {
        echo json_encode(['ok'=>false,'error'=>'Over query limit. Wait and retry.']);
    } else {
        echo json_encode(['ok'=>false,'error'=>'Response: '.($d['status']??'unknown').' - '.($d['error_message']??substr($raw,0,200))]);
    }
    exit;
}

// ============================================================
// EXPORT CSV
// ============================================================
if ($action === 'export_csv') {
    $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
    if (!$ids) { echo json_encode(['ok'=>false,'error'=>'No data']); exit; }
    $rows = @$db->query("SELECT name,phone,address,website,has_website,rating,industry,location,imported FROM lead_gen_results WHERE id IN(".implode(',',$ids).") AND user_id=$uid")->fetch_all(MYSQLI_ASSOC);
    if (!$rows) { echo json_encode(['ok'=>false,'error'=>'No data']); exit; }
    $csv = "Business Name,Phone,Address,Website,Has Website,Rating,Industry,Location,Imported\n";
    foreach ($rows as $r) {
        $r['has_website']=$r['has_website']?'Yes':'No';
        $r['imported']=$r['imported']?'Yes':'No';
        $csv.=implode(',',array_map(fn($v)=>'"'.str_replace('"','""',(string)($v??'')).'"',array_values($r)))."\n";
    }
    echo json_encode(['ok'=>true,'csv'=>$csv]);
    exit;
}

// ── SAVE ROLE QUOTAS (admin only) ──
if ($action === 'save_role_quotas' && isAdmin()) {
    $q_admin   = max(1, min(5000, (int)($_POST['q_admin']   ?? 500)));
    $q_manager = max(1, min(2000, (int)($_POST['q_manager'] ?? 100)));
    $q_member  = max(1, min(500,  (int)($_POST['q_member']  ?? 20)));
    foreach ([
        'quota_role_admin'   => $q_admin,
        'quota_role_manager' => $q_manager,
        'quota_role_member'  => $q_member,
    ] as $k => $v) {
        $ke=$db->real_escape_string($k);
        $db->query("INSERT INTO lead_gen_settings (setting_key,setting_val,updated_by) VALUES ('$ke',$v,$uid)
                    ON DUPLICATE KEY UPDATE setting_val=$v,updated_by=$uid");
    }
    echo json_encode(['ok'=>true,'message'=>'Role quotas saved']);
    exit;
}

// ── SAVE USER QUOTA OVERRIDE (admin only) ──
if ($action === 'save_user_quota' && isAdmin()) {
    $target_uid = (int)($_POST['target_uid'] ?? 0);
    $new_quota  = trim($_POST['user_quota'] ?? '');
    if (!$target_uid) { echo json_encode(['ok'=>false,'error'=>'Invalid user']); exit; }
    $key = "quota_user_$target_uid";
    $ke  = $db->real_escape_string($key);
    if ($new_quota === '' || $new_quota === '0') {
        // Remove override — revert to role default
        $db->query("DELETE FROM lead_gen_settings WHERE setting_key='$ke'");
        echo json_encode(['ok'=>true,'message'=>'User quota reset to role default']);
    } else {
        $v = max(1, min(5000, (int)$new_quota));
        $db->query("INSERT INTO lead_gen_settings (setting_key,setting_val,updated_by) VALUES ('$ke',$v,$uid)
                    ON DUPLICATE KEY UPDATE setting_val=$v,updated_by=$uid");
        echo json_encode(['ok'=>true,'message'=>"User quota set to $v leads/month"]);
    }
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Unknown action: '.$action]);