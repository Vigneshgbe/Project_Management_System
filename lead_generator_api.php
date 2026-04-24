<?php
/**
 * lead_generator_api.php
 * Google Places API — Text Search + Place Details (Contact fields)
 * Cost: $0.032 per search + $0.003 per phone/website lookup = ~$0.035/lead
 * Monthly quota default: 300 leads = ~$10.50 (well within $200 free credit)
 */
require_once 'config.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];
mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── HELPERS ──
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
    $r = @$db->query("SELECT COALESCE(SUM(result_count),0) leads, COALESCE(SUM(api_calls_used),0) calls, COALESCE(SUM(estimated_cost),0) cost FROM lead_gen_usage WHERE user_id=$uid AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())");
    return $r ? ($r->fetch_assoc() ?: ['leads'=>0,'calls'=>0,'cost'=>0]) : ['leads'=>0,'calls'=>0,'cost'=>0];
}
function lgHttp(string $url): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [CURLOPT_URL=>$url, CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_TIMEOUT=>20, CURLOPT_SSL_VERIFYPEER=>false,
            CURLOPT_USERAGENT=>'PadakCRM/1.0 (internal)', CURLOPT_FOLLOWLOCATION=>true]);
        $r = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($code >= 200 && $code < 300) ? ($r ?: null) : null;
    }
    $ctx = stream_context_create(['http'=>['timeout'=>20,'ignore_errors'=>true,
        'user_agent'=>'PadakCRM/1.0'],'ssl'=>['verify_peer'=>false]]);
    $r = @file_get_contents($url, false, $ctx);
    return $r !== false ? $r : null;
}

if (!lgTableOk($db)) {
    echo json_encode(['ok'=>false,'error'=>'Run migration_v11.sql and migration_v14.sql first.']);
    exit;
}

// ── ACTION: SEARCH ──
if ($action === 'search') {
    $location = trim($_POST['location'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $count    = min(20, max(1, (int)($_POST['count'] ?? 5)));

    if (!$location || !$industry) {
        echo json_encode(['ok'=>false,'error'=>'Location and industry are required']); exit;
    }

    $api_key = lgGet($db, 'google_api_key');
    if (!$api_key) {
        echo json_encode(['ok'=>false,'error'=>'Google API key not configured. Click ⚙ Settings.']); exit;
    }

    // ── QUOTA + BUDGET GUARD ──
    $quota      = max(1, (int)lgGet($db, 'monthly_quota', '300'));
    $budget_usd = (float)lgGet($db, 'monthly_budget_usd', '15.00');
    $cost_ts    = (float)lgGet($db, 'cost_per_textsearch', '0.032');
    $cost_det   = (float)lgGet($db, 'cost_per_details',    '0.003');
    $usage      = lgMonthUsed($db, $uid);
    $used_leads = (int)$usage['leads'];
    $used_cost  = (float)$usage['cost'];
    $cost_this  = ($cost_ts + $cost_det) * $count; // estimated cost for this search

    if ($used_leads + $count > $quota) {
        echo json_encode(['ok'=>false,'error'=>"Monthly quota of $quota leads reached ($used_leads used). Reset next month or ask admin to increase quota in Settings."]);
        exit;
    }
    if ($used_cost + $cost_this > $budget_usd) {
        $rem = round($budget_usd - $used_cost, 4);
        echo json_encode(['ok'=>false,'error'=>"Monthly budget limit \$$budget_usd reached. Used: \$".round($used_cost,4).". Remaining: \$$rem. Ask admin to increase budget in Settings."]);
        exit;
    }

    // ── STEP 1: Text Search ──
    $query      = urlencode("$industry in $location");
    $search_url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=$query&key=$api_key";
    $raw = lgHttp($search_url);
    $api_calls = 1;

    if (!$raw) {
        echo json_encode(['ok'=>false,'error'=>'Cannot reach Google API. Check your server internet connection.']); exit;
    }
    $data = json_decode($raw, true);
    if (!isset($data['status'])) {
        echo json_encode(['ok'=>false,'error'=>'Invalid Google API response. Check your API key.']); exit;
    }
    if ($data['status'] === 'REQUEST_DENIED') {
        $msg = $data['error_message'] ?? '';
        if (strpos($msg,'not authorized') !== false || strpos($msg,'Places API') !== false)
            echo json_encode(['ok'=>false,'error'=>"Places API not enabled on your key.\n\nFix: Google Cloud Console → APIs & Services → Enable → search 'Places API' → Enable.\n\nError: $msg"]);
        else
            echo json_encode(['ok'=>false,'error'=>"API key error: $msg"]);
        exit;
    }
    if ($data['status'] === 'ZERO_RESULTS' || empty($data['results'])) {
        echo json_encode(['ok'=>true,'leads'=>[],'message'=>"No businesses found for '$industry' in '$location'. Try broader keywords or a different city name."]);
        exit;
    }
    if (!in_array($data['status'], ['OK','ZERO_RESULTS'])) {
        echo json_encode(['ok'=>false,'error'=>'Google API: '.$data['status'].' — '.($data['error_message']??'')]); exit;
    }

    $results = array_slice($data['results'], 0, $count);
    $leads   = [];

    // ── STEP 2: Place Details (phone + website + email if available) ──
    foreach ($results as $place) {
        $place_id = $place['place_id'] ?? '';
        $name     = $place['name'] ?? '';
        if (!$name) continue;

        $address  = $place['formatted_address'] ?? ($place['vicinity'] ?? '');
        $rating   = isset($place['rating']) ? (float)$place['rating'] : null;
        $types    = $place['types'] ?? [];
        $phone    = ''; $website = ''; $email = ''; $has_website = 0;

        if ($place_id) {
            $fields   = 'formatted_phone_number,website,international_phone_number';
            $det_url  = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$place_id&fields=$fields&key=$api_key";
            $det_raw  = lgHttp($det_url);
            $api_calls++;
            if ($det_raw) {
                $det     = json_decode($det_raw, true);
                $res     = $det['result'] ?? [];
                $phone   = $res['formatted_phone_number'] ?? ($res['international_phone_number'] ?? '');
                $website = $res['website'] ?? '';
                $has_website = $website ? 1 : 0;
            }
        }

        // Save to holding table
        $pe = $db->real_escape_string($place_id);
        $ne = $db->real_escape_string($name);
        $phe= $db->real_escape_string($phone);
        $ae = $db->real_escape_string($address);
        $we = $db->real_escape_string($website);
        $le = $db->real_escape_string($location);
        $ie = $db->real_escape_string($industry);
        $rt = $rating !== null ? $rating : 'NULL';

        $db->query("INSERT INTO lead_gen_results
            (user_id,place_id,name,phone,address,website,has_website,rating,location,industry,imported,api_calls)
            VALUES ($uid,'$pe','$ne','$phe','$ae','$we',$has_website,$rt,'$le','$ie',0,$api_calls)
            ON DUPLICATE KEY UPDATE name='$ne',phone='$phe',address='$ae',website='$we',has_website=$has_website");
        $rid = (int)($db->insert_id ?: 0);
        if (!$rid && $place_id) {
            $ex = @$db->query("SELECT id FROM lead_gen_results WHERE place_id='$pe' AND user_id=$uid LIMIT 1");
            if ($ex) $rid = (int)($ex->fetch_assoc()['id'] ?? 0);
        }

        $leads[] = ['id'=>$rid,'place_id'=>$place_id,'name'=>$name,'phone'=>$phone,
                    'address'=>$address,'website'=>$website,'has_website'=>$has_website,
                    'email'=>$email,'rating'=>$rating,'types'=>$types,'imported'=>false];
    }

    // ── LOG USAGE WITH COST ──
    $real_count  = count($leads);
    $actual_cost = round($cost_ts + ($cost_det * $real_count), 6);
    $le_e = $db->real_escape_string($location);
    $ie_e = $db->real_escape_string($industry);
    $db->query("INSERT INTO lead_gen_usage (user_id,location,industry,result_count,api_calls_used,estimated_cost)
        VALUES ($uid,'$le_e','$ie_e',$real_count,$api_calls,'$actual_cost')");
    logActivity('generated leads', "$industry in $location", 0, "$real_count leads");

    $new_usage = lgMonthUsed($db, $uid);
    echo json_encode([
        'ok'      => true,
        'leads'   => $leads,
        'used'    => (int)$new_usage['leads'],
        'quota'   => $quota,
        'cost'    => round((float)$new_usage['cost'], 4),
        'budget'  => $budget_usd,
    ]);
    exit;
}

// ── ACTION: IMPORT LEAD ──
if ($action === 'import_lead') {
    $rid = (int)($_POST['result_id'] ?? 0);
    $row = @$db->query("SELECT * FROM lead_gen_results WHERE id=$rid AND user_id=$uid")->fetch_assoc();
    if (!$row) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }
    if ($row['imported']) { echo json_encode(['ok'=>false,'error'=>'Already imported']); exit; }

    $n=$row['name']; $p=$row['phone']; $em=$row['email']??'';
    $svc=$row['industry']; $src='other'; $st='new'; $pr='medium'; $bc='LKR'; $null=null;
    $notes = "Lead Generator: {$row['industry']} in {$row['location']}.";
    if ($row['address']) $notes .= " Address: {$row['address']}.";
    if ($row['website']) $notes .= " Website: {$row['website']}.";
    if ($row['rating'])  $notes .= " Google Rating: {$row['rating']}/5.";
    $notes .= $row['has_website'] ? ' Has website: Yes.' : ' Has website: No.';

    $stmt = $db->prepare("INSERT INTO leads (name,company,email,phone,source,service_interest,budget_est,budget_currency,stage,priority,expected_close,last_contact,notes,loss_reason,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssdsssssssii",$n,$n,$em,$p,$src,$svc,$null,$bc,$st,$pr,$null,$null,$notes,$null,$null,$uid);
    $stmt->execute();
    $lid = (int)$db->insert_id;
    $db->query("UPDATE lead_gen_results SET imported=1,lead_id=$lid WHERE id=$rid");
    logActivity('imported lead',$n,$lid);
    echo json_encode(['ok'=>true,'lead_id'=>$lid,'message'=>"$n imported to CRM Leads"]);
    exit;
}

// ── ACTION: IMPORT ALL ──
if ($action === 'import_all') {
    $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
    if (!$ids) { echo json_encode(['ok'=>false,'error'=>'No IDs']); exit; }
    $imported = 0;
    foreach ($ids as $rid) {
        $row = @$db->query("SELECT * FROM lead_gen_results WHERE id=$rid AND user_id=$uid AND imported=0")->fetch_assoc();
        if (!$row) continue;
        $n=$row['name']; $p=$row['phone']; $em=$row['email']??'';
        $svc=$row['industry']; $src='other'; $st='new'; $pr='medium'; $bc='LKR'; $null=null;
        $notes = "Lead Generator: {$row['industry']} in {$row['location']}.";
        if ($row['address']) $notes.=" Address: {$row['address']}.";
        if ($row['website']) $notes.=" Web: {$row['website']}.";
        $notes.=$row['has_website']?' Has website: Yes.':" Has website: No.";
        $stmt=$db->prepare("INSERT INTO leads (name,company,email,phone,source,service_interest,budget_est,budget_currency,stage,priority,expected_close,last_contact,notes,loss_reason,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssdsssssssii",$n,$n,$em,$p,$src,$svc,$null,$bc,$st,$pr,$null,$null,$notes,$null,$null,$uid);
        $stmt->execute();
        $lid=(int)$db->insert_id;
        $db->query("UPDATE lead_gen_results SET imported=1,lead_id=$lid WHERE id=$rid");
        $imported++;
    }
    logActivity('bulk import leads','',0,"$imported leads");
    echo json_encode(['ok'=>true,'imported'=>$imported]);
    exit;
}

// ── ACTION: GET STATS ──
if ($action === 'get_stats') {
    $quota     = (int)lgGet($db,'monthly_quota','300');
    $budget    = (float)lgGet($db,'monthly_budget_usd','15.00');
    $api_set   = lgGet($db,'google_api_key') !== '';
    $usage     = lgMonthUsed($db,$uid);

    $trend = [];
    $tr = @$db->query("SELECT DATE_FORMAT(created_at,'%b') mo,DATE_FORMAT(created_at,'%Y-%m') smo,SUM(result_count) cnt,SUM(estimated_cost) cost FROM lead_gen_usage WHERE user_id=$uid AND created_at>=DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY mo,smo ORDER BY smo");
    if ($tr) $trend = $tr->fetch_all(MYSQLI_ASSOC);

    $recent = [];
    $rc = @$db->query("SELECT industry,location,result_count,estimated_cost,created_at FROM lead_gen_usage WHERE user_id=$uid ORDER BY created_at DESC LIMIT 8");
    if ($rc) $recent = $rc->fetch_all(MYSQLI_ASSOC);

    $total_imp = (int)(@$db->query("SELECT COUNT(*) c FROM lead_gen_results WHERE user_id=$uid AND imported=1")->fetch_assoc()['c'] ?? 0);
    $total_all = (int)(@$db->query("SELECT COUNT(*) c FROM lead_gen_results WHERE user_id=$uid")->fetch_assoc()['c'] ?? 0);

    echo json_encode(['ok'=>true,'used'=>(int)$usage['leads'],'quota'=>$quota,
        'cost'=>round((float)$usage['cost'],4),'budget'=>$budget,'api_set'=>$api_set,
        'trend'=>$trend,'recent'=>$recent,'total_imp'=>$total_imp,'total_all'=>$total_all]);
    exit;
}

// ── ACTION: SAVE SETTINGS (admin) ──
if ($action === 'save_settings' && isAdmin()) {
    $g_key   = trim($_POST['google_key'] ?? '');
    $quota   = max(10, min(5000, (int)($_POST['quota'] ?? 300)));
    $budget  = max(1, min(180, (float)($_POST['budget'] ?? 15)));

    $db->query("UPDATE lead_gen_settings SET setting_val='google',updated_by=$uid WHERE setting_key='api_provider'");
    if ($g_key) {
        $gke = $db->real_escape_string($g_key);
        $db->query("UPDATE lead_gen_settings SET setting_val='$gke',updated_by=$uid WHERE setting_key='google_api_key'");
    }
    $db->query("UPDATE lead_gen_settings SET setting_val=$quota,updated_by=$uid WHERE setting_key='monthly_quota'");
    $db->query("UPDATE lead_gen_settings SET setting_val=$budget,updated_by=$uid WHERE setting_key='monthly_budget_usd'");
    echo json_encode(['ok'=>true,'message'=>'Settings saved']);
    exit;
}

// ── ACTION: TEST KEY ──
if ($action === 'test_key') {
    $key = trim($_POST['google_key'] ?? lgGet($db,'google_api_key'));
    if (!$key) { echo json_encode(['ok'=>false,'error'=>'No API key provided']); exit; }
    $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=restaurant+in+London&key=$key";
    $raw = lgHttp($url);
    if (!$raw) { echo json_encode(['ok'=>false,'error'=>'Cannot reach Google API from your server']); exit; }
    $d = json_decode($raw, true);
    if (!empty($d['results'])) {
        echo json_encode(['ok'=>true,'message'=>'✅ API key works! Found: '.$d['results'][0]['name']]);
    } elseif ($d['status'] === 'REQUEST_DENIED') {
        $msg = $d['error_message'] ?? 'Request denied';
        if (strpos($msg,'Places API') !== false)
            echo json_encode(['ok'=>false,'error'=>"Places API not enabled.\nGo to: Google Cloud Console → APIs & Services → Enable → search 'Places API' → Enable.\n\n$msg"]);
        else
            echo json_encode(['ok'=>false,'error'=>"Key rejected: $msg"]);
    } elseif ($d['status'] === 'OVER_QUERY_LIMIT') {
        echo json_encode(['ok'=>false,'error'=>'Over query limit. Wait a moment and try again.']);
    } else {
        echo json_encode(['ok'=>false,'error'=>'Response: '.($d['status']??'unknown').' — '.($d['error_message']??substr($raw,0,200))]);
    }
    exit;
}

// ── ACTION: EXPORT CSV ──
if ($action === 'export_csv') {
    $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
    if (!$ids) { echo json_encode(['ok'=>false,'error'=>'No data']); exit; }
    $rows = @$db->query("SELECT name,phone,address,website,has_website,rating,industry,location,imported FROM lead_gen_results WHERE id IN(".implode(',',$ids).") AND user_id=$uid")->fetch_all(MYSQLI_ASSOC);
    if (!$rows) { echo json_encode(['ok'=>false,'error'=>'No data']); exit; }
    $csv  = "Business Name,Phone,Address,Website,Has Website,Rating,Industry,Location,Imported\n";
    foreach ($rows as $r) {
        $r['has_website'] = $r['has_website'] ? 'Yes' : 'No';
        $r['imported']    = $r['imported']    ? 'Yes' : 'No';
        $csv .= implode(',', array_map(fn($v)=>'"'.str_replace('"','""',(string)($v??'')).'"', array_values($r)))."\n";
    }
    echo json_encode(['ok'=>true,'csv'=>$csv]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Unknown action']);