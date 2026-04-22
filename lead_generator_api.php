<?php
/**
 * lead_generator_api.php
 * Backend: Calls Google Places API → returns business leads as JSON
 * Actions: search, import_lead, import_all, delete_result, get_stats, save_settings
 */
require_once 'config.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];

mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── HELPER: safe table check ──
function lgTableExists(mysqli $db): bool {
    $r = @$db->query("SELECT 1 FROM lead_gen_settings LIMIT 1");
    return ($r !== false);
}

// ── HELPER: get setting ──
function lgSetting(mysqli $db, string $key, string $def = ''): string {
    $r = @$db->query("SELECT setting_val FROM lead_gen_settings WHERE setting_key='".
        $db->real_escape_string($key)."' LIMIT 1");
    if (!$r) return $def;
    $row = $r->fetch_assoc();
    return $row ? (string)($row['setting_val'] ?? $def) : $def;
}

// ── HELPER: get monthly usage for user ──
function lgMonthUsage(mysqli $db, int $uid): int {
    $r = @$db->query("SELECT COALESCE(SUM(result_count),0) AS c FROM lead_gen_usage
        WHERE user_id=$uid AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())");
    if (!$r) return 0;
    return (int)($r->fetch_assoc()['c'] ?? 0);
}

// ── HELPER: HTTP GET (curl with file_get_contents fallback) ──
function lgHttpGet(string $url): ?string {
    // Try curl first
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'PadakCRM/1.0',
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res ?: null;
    }
    // Fallback: file_get_contents
    $ctx = stream_context_create(['http' => [
        'timeout'       => 15,
        'ignore_errors' => true,
    ], 'ssl' => [
        'verify_peer' => false,
    ]]);
    $res = @file_get_contents($url, false, $ctx);
    return $res !== false ? $res : null;
}

if (!lgTableExists($db)) {
    echo json_encode(['ok'=>false,'error'=>'Please run migration_v11.sql first']);
    exit;
}

// ══════════════════════════════════════════════
// ACTION: SEARCH — call Google Places API
// ══════════════════════════════════════════════
if ($action === 'search') {
    $location  = trim($_POST['location'] ?? '');
    $industry  = trim($_POST['industry'] ?? '');
    $count     = min(20, max(1, (int)($_POST['count'] ?? 5)));

    if (!$location || !$industry) {
        echo json_encode(['ok'=>false,'error'=>'Location and industry are required']);
        exit;
    }

    $api_key = lgSetting($db, 'google_api_key');
    if (!$api_key) {
        echo json_encode(['ok'=>false,'error'=>'Google Places API key not configured. Go to Lead Generator Settings.']);
        exit;
    }

    // Check monthly quota
    $quota    = (int)lgSetting($db, 'monthly_quota', '200');
    $used     = lgMonthUsage($db, $uid);
    if ($used + $count > $quota) {
        echo json_encode(['ok'=>false,'error'=>"Monthly quota exceeded. Used: $used / $quota. Reduce count or wait until next month."]);
        exit;
    }

    // ── Step 1: Text Search ──
    $query    = urlencode("$industry in $location");
    $search_url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=$query&key=$api_key";
    $raw = lgHttpGet($search_url);

    if (!$raw) {
        echo json_encode(['ok'=>false,'error'=>'Failed to reach Google API. Check your server network/firewall.']);
        exit;
    }

    $data = json_decode($raw, true);
    if (!isset($data['results'])) {
        $status = $data['status'] ?? 'UNKNOWN';
        $msg    = $data['error_message'] ?? '';
        echo json_encode(['ok'=>false,'error'=>"Google API error: $status. $msg"]);
        exit;
    }

    if ($data['status'] === 'ZERO_RESULTS') {
        echo json_encode(['ok'=>true,'leads'=>[],'message'=>'No businesses found for this location/industry combination.']);
        exit;
    }

    // ── Step 2: Fetch Place Details for phone numbers ──
    $results   = array_slice($data['results'], 0, $count);
    $leads_out = [];

    foreach ($results as $place) {
        $place_id = $place['place_id'] ?? '';
        $name     = $place['name'] ?? '';
        $address  = $place['formatted_address'] ?? ($place['vicinity'] ?? '');
        $rating   = isset($place['rating']) ? (float)$place['rating'] : null;
        $phone    = '';
        $website  = '';

        // Get phone + website from Place Details
        if ($place_id) {
            $fields    = 'formatted_phone_number,website,opening_hours';
            $det_url   = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$place_id&fields=$fields&key=$api_key";
            $det_raw   = lgHttpGet($det_url);
            if ($det_raw) {
                $det = json_decode($det_raw, true);
                $phone   = $det['result']['formatted_phone_number'] ?? '';
                $website = $det['result']['website'] ?? '';
            }
        }

        // Check if already imported to avoid duplicates
        $already_imported = false;
        if ($place_id) {
            $chk = @$db->query("SELECT id FROM lead_gen_results WHERE place_id='".
                $db->real_escape_string($place_id)."' AND user_id=$uid AND imported=1");
            $already_imported = ($chk && $chk->num_rows > 0);
        }

        // Save to holding table
        $loc_e  = $db->real_escape_string($location);
        $ind_e  = $db->real_escape_string($industry);
        $pid_e  = $db->real_escape_string($place_id);
        $nm_e   = $db->real_escape_string($name);
        $ph_e   = $db->real_escape_string($phone);
        $ad_e   = $db->real_escape_string($address);
        $ws_e   = $db->real_escape_string($website);
        $rt_sql = $rating !== null ? $rating : 'NULL';

        $db->query("INSERT INTO lead_gen_results
            (user_id,place_id,name,phone,address,website,rating,location,industry,imported)
            VALUES ($uid,'$pid_e','$nm_e','$ph_e','$ad_e','$ws_e',$rt_sql,'$loc_e','$ind_e',0)
            ON DUPLICATE KEY UPDATE name='$nm_e',phone='$ph_e',address='$ad_e'");
        $result_id = $db->insert_id ?: 0;

        // If ON DUPLICATE KEY hit, get existing id
        if (!$result_id && $place_id) {
            $ex = @$db->query("SELECT id,imported FROM lead_gen_results WHERE place_id='$pid_e' AND user_id=$uid LIMIT 1");
            if ($ex) { $exr = $ex->fetch_assoc(); $result_id = (int)($exr['id']??0); }
        }

        $leads_out[] = [
            'id'       => $result_id,
            'place_id' => $place_id,
            'name'     => $name,
            'phone'    => $phone,
            'address'  => $address,
            'website'  => $website,
            'rating'   => $rating,
            'imported' => $already_imported,
        ];
    }

    // Log usage
    $real_count = count($leads_out);
    $loc_e = $db->real_escape_string($location);
    $ind_e = $db->real_escape_string($industry);
    $db->query("INSERT INTO lead_gen_usage (user_id,location,industry,result_count)
        VALUES ($uid,'$loc_e','$ind_e',$real_count)");

    // Log activity
    logActivity('generated leads', "$industry in $location", 0, "$real_count leads found");

    echo json_encode(['ok'=>true,'leads'=>$leads_out,'used'=>$used+$real_count,'quota'=>$quota]);
    exit;
}

// ══════════════════════════════════════════════
// ACTION: IMPORT_LEAD — add one result to leads table
// ══════════════════════════════════════════════
if ($action === 'import_lead') {
    $result_id = (int)($_POST['result_id'] ?? 0);
    if (!$result_id) { echo json_encode(['ok'=>false,'error'=>'Missing result_id']); exit; }

    $row = @$db->query("SELECT * FROM lead_gen_results WHERE id=$result_id AND user_id=$uid")->fetch_assoc();
    if (!$row) { echo json_encode(['ok'=>false,'error'=>'Result not found']); exit; }
    if ($row['imported']) { echo json_encode(['ok'=>false,'error'=>'Already imported']); exit; }

    $name    = $row['name'];
    $company = $row['name'];  // Company = business name
    $phone   = $row['phone'];
    $address = $row['address'];
    $website = $row['website'];
    $service = $row['industry'];
    $source  = 'other';
    $stage   = 'new';
    $prio    = 'medium';
    $notes   = "Generated by Lead Generator. Location: {$row['location']}. Industry: {$row['industry']}.";
    if ($website) $notes .= " Website: $website";
    if ($row['rating']) $notes .= " Google Rating: {$row['rating']}/5";
    $budget  = null;
    $bc      = 'LKR';

    $stmt = $db->prepare("INSERT INTO leads
        (name,company,email,phone,source,service_interest,budget_est,budget_currency,
         stage,priority,expected_close,last_contact,notes,loss_reason,assigned_to,created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $email    = '';
    $cl = $lc = $lr = null;
    $as = null;
    $stmt->bind_param("ssssssdsssssssii",
        $name,$company,$email,$phone,$source,$service,$budget,$bc,
        $stage,$prio,$cl,$lc,$notes,$lr,$as,$uid);
    $stmt->execute();
    $lead_id = $db->insert_id;

    // Mark as imported
    $db->query("UPDATE lead_gen_results SET imported=1, lead_id=$lead_id WHERE id=$result_id");
    logActivity('imported lead from generator', $name, $lead_id);

    echo json_encode(['ok'=>true,'lead_id'=>$lead_id,'message'=>"$name imported to Leads"]);
    exit;
}

// ══════════════════════════════════════════════
// ACTION: IMPORT_ALL — import all non-imported from current search
// ══════════════════════════════════════════════
if ($action === 'import_all') {
    $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
    if (!$ids) { echo json_encode(['ok'=>false,'error'=>'No IDs provided']); exit; }

    $imported = 0;
    foreach ($ids as $rid) {
        $row = @$db->query("SELECT * FROM lead_gen_results WHERE id=$rid AND user_id=$uid AND imported=0")->fetch_assoc();
        if (!$row) continue;

        $name    = $row['name'];
        $phone   = $row['phone'];
        $service = $row['industry'];
        $notes   = "Generated by Lead Generator. Location: {$row['location']}. Industry: {$row['industry']}.";
        if ($row['website']) $notes .= " Website: {$row['website']}";
        if ($row['rating']) $notes .= " Google Rating: {$row['rating']}/5";
        $empty = ''; $null = null;

        $stmt = $db->prepare("INSERT INTO leads
            (name,company,email,phone,source,service_interest,budget_est,budget_currency,
             stage,priority,expected_close,last_contact,notes,loss_reason,assigned_to,created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $bc='LKR'; $stage='new'; $prio='medium'; $src='other';
        $stmt->bind_param("ssssssdsssssssii",
            $name,$name,$empty,$phone,$src,$service,$null,$bc,
            $stage,$prio,$null,$null,$notes,$null,$null,$uid);
        $stmt->execute();
        $lead_id = $db->insert_id;
        $db->query("UPDATE lead_gen_results SET imported=1, lead_id=$lead_id WHERE id=$rid");
        $imported++;
    }

    logActivity('bulk imported leads from generator', '', 0, "$imported leads imported");
    echo json_encode(['ok'=>true,'imported'=>$imported]);
    exit;
}

// ══════════════════════════════════════════════
// ACTION: GET_STATS — usage stats for dashboard
// ══════════════════════════════════════════════
if ($action === 'get_stats') {
    $quota     = (int)lgSetting($db, 'monthly_quota', '200');
    $used      = lgMonthUsage($db, $uid);
    $api_set   = lgSetting($db, 'google_api_key') !== '';

    // Monthly trend (last 6 months)
    $trend = [];
    $trend_raw = @$db->query("
        SELECT DATE_FORMAT(created_at,'%b') AS mo,
               DATE_FORMAT(created_at,'%Y-%m') AS smo,
               SUM(result_count) AS cnt
        FROM lead_gen_usage WHERE user_id=$uid
          AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY mo,smo ORDER BY smo");
    if ($trend_raw) $trend = $trend_raw->fetch_all(MYSQLI_ASSOC);

    // Recent activity (last 8 searches)
    $recent = [];
    $rec_raw = @$db->query("
        SELECT industry, location, result_count, created_at
        FROM lead_gen_usage WHERE user_id=$uid
        ORDER BY created_at DESC LIMIT 8");
    if ($rec_raw) $recent = $rec_raw->fetch_all(MYSQLI_ASSOC);

    // Total all-time
    $total_all = (int)(@$db->query("SELECT COALESCE(SUM(result_count),0) AS c FROM lead_gen_usage WHERE user_id=$uid")->fetch_assoc()['c'] ?? 0);
    $total_imp = (int)(@$db->query("SELECT COUNT(*) AS c FROM lead_gen_results WHERE user_id=$uid AND imported=1")->fetch_assoc()['c'] ?? 0);

    echo json_encode([
        'ok'        => true,
        'used'      => $used,
        'quota'     => $quota,
        'api_set'   => $api_set,
        'trend'     => $trend,
        'recent'    => $recent,
        'total_all' => $total_all,
        'total_imp' => $total_imp,
    ]);
    exit;
}

// ══════════════════════════════════════════════
// ACTION: SAVE_SETTINGS (admin only)
// ══════════════════════════════════════════════
if ($action === 'save_settings' && isAdmin()) {
    $api_key = trim($_POST['api_key'] ?? '');
    $quota   = max(1, (int)($_POST['quota'] ?? 200));

    $ak_e = $db->real_escape_string($api_key);
    $db->query("UPDATE lead_gen_settings SET setting_val='$ak_e', updated_by=$uid WHERE setting_key='google_api_key'");
    $db->query("UPDATE lead_gen_settings SET setting_val=$quota, updated_by=$uid WHERE setting_key='monthly_quota'");

    echo json_encode(['ok'=>true,'message'=>'Settings saved']);
    exit;
}

// ══════════════════════════════════════════════
// ACTION: EXPORT_EXCEL — returns CSV data
// ══════════════════════════════════════════════
if ($action === 'export_excel') {
    $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
    if (!$ids) { echo json_encode(['ok'=>false,'error'=>'No data']); exit; }
    $in  = implode(',', $ids);
    $rows = @$db->query("SELECT name,phone,address,website,rating,industry,location FROM lead_gen_results WHERE id IN($in) AND user_id=$uid")->fetch_all(MYSQLI_ASSOC);
    if (!$rows) { echo json_encode(['ok'=>false,'error'=>'No data']); exit; }

    // Build CSV
    $csv  = "Name,Phone,Address,Website,Rating,Industry,Location\n";
    foreach ($rows as $r) {
        $csv .= implode(',', array_map(fn($v) => '"'.str_replace('"','""',(string)($v??'')).'"', array_values($r)))."\n";
    }
    echo json_encode(['ok'=>true,'csv'=>$csv]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Unknown action']);