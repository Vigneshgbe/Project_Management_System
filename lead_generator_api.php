<?php
/**
 * lead_generator_api.php — Foursquare (FREE) + Google Places
 * Fixed: proper FSQ Place Details for phone/website, error handling, auth
 */
require_once 'config.php';
requireLogin();
$db = getCRMDB(); $user = currentUser(); $uid = (int)$user['id'];
mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── DB HELPERS ──
function lgTableExists(mysqli $db):bool {
    return @$db->query("SELECT 1 FROM lead_gen_settings LIMIT 1") !== false;
}
function lgSetting(mysqli $db, string $key, string $def = ''): string {
    $r = @$db->query("SELECT setting_val FROM lead_gen_settings WHERE setting_key='".$db->real_escape_string($key)."' LIMIT 1");
    if (!$r) return $def;
    $row = $r->fetch_assoc();
    return $row ? (string)($row['setting_val'] ?? $def) : $def;
}
function lgMonthUsage(mysqli $db, int $uid): int {
    $r = @$db->query("SELECT COALESCE(SUM(result_count),0) c FROM lead_gen_usage WHERE user_id=$uid AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())");
    return $r ? (int)($r->fetch_assoc()['c'] ?? 0) : 0;
}

// ── HTTP GET — curl preferred, file_get_contents fallback ──
function lgHttp(string $url, array $headers = []): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_USERAGENT      => 'PadakCRM/1.0',
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $res   = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        if ($errno) return null;
        return $res ?: null;
    }
    // file_get_contents fallback
    $opts = [
        'http' => [
            'method'        => 'GET',
            'timeout'       => 20,
            'ignore_errors' => true,
            'user_agent'    => 'PadakCRM/1.0',
        ],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
    ];
    if ($headers) {
        $opts['http']['header'] = implode("\r\n", $headers) . "\r\n";
    }
    $res = @file_get_contents($url, false, stream_context_create($opts));
    return ($res !== false && $res !== '') ? $res : null;
}

if (!lgTableExists($db)) {
    echo json_encode(['ok' => false, 'error' => 'Please run migration_v11.sql in phpMyAdmin first.']);
    exit;
}

// ════════════════════════════════════════════════════════
// FOURSQUARE SEARCH (FREE — no credit card needed)
// Service API Key goes directly in Authorization header
// ════════════════════════════════════════════════════════
function searchFoursquare(string $key, string $location, string $industry, int $count): array {
    // Step 1: Geocode location via Nominatim (free, no key)
    $lat = $lng = '';
    $geo_raw = lgHttp(
        'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($location),
        ['User-Agent: PadakCRM/1.0', 'Accept-Language: en']
    );
    if ($geo_raw) {
        $geo = json_decode($geo_raw, true);
        if (!empty($geo[0]['lat'])) { $lat = $geo[0]['lat']; $lng = $geo[0]['lon']; }
    }

    // Step 2: Foursquare Places Search
    // FSQ v3 Service API Key: Authorization header = just the key (no Bearer prefix)
    $auth_headers = [
        "Authorization: $key",
        "Accept: application/json",
    ];

    $params = [
        'query'  => $industry,
        'limit'  => min($count, 50),
        'fields' => 'fsq_id,name,location,categories',
    ];
    if ($lat && $lng) {
        $params['ll']     = "$lat,$lng";
        $params['radius'] = 50000; // 50km
    } else {
        $params['near'] = $location;
    }

    $search_url = 'https://api.foursquare.com/v3/places/search?' . http_build_query($params);
    $raw = lgHttp($search_url, $auth_headers);

    if (!$raw) {
        return ['error' => 'Cannot reach Foursquare API. Ensure your XAMPP/server has internet access.'];
    }

    $data = json_decode($raw, true);

    // Check for auth/API errors
    if (!isset($data['results'])) {
        $msg = $data['message'] ?? ($data['detail'] ?? ($data['error'] ?? ''));
        if (!$msg && isset($data['code'])) $msg = 'HTTP ' . $data['code'];
        if (empty($msg)) $msg = 'Unexpected response. Raw: ' . substr($raw, 0, 200);

        // Common error patterns
        if (strpos($raw, 'Unauthorized') !== false || strpos($raw, 'Invalid auth') !== false) {
            return ['error' => 'Invalid API key. Make sure you copied the Service API Key (not Client ID or Client Secret) from your Foursquare project Settings page.'];
        }
        if (strpos($raw, 'quota') !== false || strpos($raw, 'limit') !== false) {
            return ['error' => 'Foursquare daily limit reached (1,000/day free). Try again tomorrow.'];
        }
        return ['error' => 'Foursquare error: ' . $msg];
    }

    if (empty($data['results'])) {
        return ['leads' => []];
    }

    // Step 3: Fetch Place Details for phone + website per result
    $leads = [];
    foreach ($data['results'] as $place) {
        $fsq_id  = $place['fsq_id'] ?? '';
        $name    = $place['name']   ?? '';

        // Build address from location object
        $address = '';
        if (!empty($place['location'])) {
            $loc   = $place['location'];
            $parts = array_filter([
                $loc['address']       ?? '',
                $loc['locality']      ?? '',
                $loc['region']        ?? '',
                $loc['postcode']      ?? '',
                $loc['country']       ?? '',
            ]);
            $address = implode(', ', $parts);
        }

        $phone = $website = '';
        $rating = null;

        // Fetch Place Details for phone + website (separate call per place)
        if ($fsq_id) {
            $det_url = "https://api.foursquare.com/v3/places/$fsq_id?fields=tel,website,rating";
            $det_raw = lgHttp($det_url, $auth_headers);
            if ($det_raw) {
                $det    = json_decode($det_raw, true);
                $phone  = isset($det['tel'])     ? preg_replace('/[^\d\+\-\s\(\)]/', '', $det['tel']) : '';
                $website= $det['website']   ?? '';
                // FSQ rating is 0–10, convert to 0–5
                $rating = isset($det['rating']) ? round((float)$det['rating'] / 2, 1) : null;
            }
        }

        $leads[] = [
            'place_id' => $fsq_id,
            'name'     => $name,
            'phone'    => $phone,
            'address'  => $address,
            'website'  => $website,
            'rating'   => $rating,
        ];
    }

    return ['leads' => $leads];
}

// ════════════════════════════════════════════════════════
// GOOGLE PLACES SEARCH
// ════════════════════════════════════════════════════════
function searchGoogle(string $key, string $location, string $industry, int $count): array {
    $search_url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?query='
        . urlencode("$industry in $location") . "&key=$key";
    $raw = lgHttp($search_url);
    if (!$raw) return ['error' => 'Cannot reach Google API.'];

    $data = json_decode($raw, true);
    if (!isset($data['results'])) {
        return ['error' => 'Google: ' . ($data['status'] ?? 'error') . '. ' . ($data['error_message'] ?? '')];
    }
    if ($data['status'] === 'ZERO_RESULTS') return ['leads' => []];

    $leads = [];
    foreach (array_slice($data['results'], 0, $count) as $place) {
        $pid = $place['place_id'] ?? '';
        $phone = $website = '';
        if ($pid) {
            $dr = lgHttp("https://maps.googleapis.com/maps/api/place/details/json?place_id=$pid&fields=formatted_phone_number,website&key=$key");
            if ($dr) {
                $d       = json_decode($dr, true);
                $phone   = $d['result']['formatted_phone_number'] ?? '';
                $website = $d['result']['website']               ?? '';
            }
        }
        $leads[] = [
            'place_id' => $pid,
            'name'     => $place['name']              ?? '',
            'phone'    => $phone,
            'address'  => $place['formatted_address'] ?? '',
            'website'  => $website,
            'rating'   => isset($place['rating']) ? (float)$place['rating'] : null,
        ];
    }
    return ['leads' => $leads];
}

// ════════════════════════════════════════════════════════
// ACTION: SEARCH
// ════════════════════════════════════════════════════════
if ($action === 'search') {
    $location = trim($_POST['location'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $count    = min(20, max(1, (int)($_POST['count'] ?? 5)));

    if (!$location || !$industry) {
        echo json_encode(['ok' => false, 'error' => 'Location and industry are required.']);
        exit;
    }

    $provider = lgSetting($db, 'api_provider', 'foursquare');
    $quota    = (int)lgSetting($db, 'monthly_quota', '500');
    $used     = lgMonthUsage($db, $uid);

    if ($used + $count > $quota) {
        echo json_encode(['ok' => false, 'error' => "Monthly quota ($quota) reached. Used: $used. Increase in Settings."]);
        exit;
    }

    if ($provider === 'foursquare') {
        $k = lgSetting($db, 'foursquare_key');
        if (!$k) {
            echo json_encode(['ok' => false, 'error' => 'Foursquare API key not set. Click ⚙ Settings.']);
            exit;
        }
        $result = searchFoursquare($k, $location, $industry, $count);
    } else {
        $k = lgSetting($db, 'google_api_key');
        if (!$k) {
            echo json_encode(['ok' => false, 'error' => 'Google API key not set. Click ⚙ Settings.']);
            exit;
        }
        $result = searchGoogle($k, $location, $industry, $count);
    }

    if (isset($result['error'])) {
        echo json_encode(['ok' => false, 'error' => $result['error']]);
        exit;
    }

    $raw_leads = $result['leads'] ?? [];
    if (empty($raw_leads)) {
        echo json_encode(['ok' => true, 'leads' => [], 'message' => "No businesses found for '$industry' in '$location'. Try broader keywords e.g. 'restaurant', 'hotel', 'pharmacy'."]);
        exit;
    }

    // Save to holding table
    $out = [];
    foreach ($raw_leads as $l) {
        $pe  = $db->real_escape_string($l['place_id'] ?? '');
        $ne  = $db->real_escape_string($l['name']     ?? '');
        $phe = $db->real_escape_string($l['phone']    ?? '');
        $ae  = $db->real_escape_string($l['address']  ?? '');
        $we  = $db->real_escape_string($l['website']  ?? '');
        $le  = $db->real_escape_string($location);
        $ie  = $db->real_escape_string($industry);
        $rt  = isset($l['rating']) ? (float)$l['rating'] : null;
        $rts = $rt !== null ? $rt : 'NULL';

        $db->query("INSERT INTO lead_gen_results
            (user_id,place_id,name,phone,address,website,rating,location,industry,imported)
            VALUES ($uid,'$pe','$ne','$phe','$ae','$we',$rts,'$le','$ie',0)");
        $rid = (int)$db->insert_id;

        $out[] = [
            'id'       => $rid,
            'place_id' => $l['place_id'] ?? '',
            'name'     => $l['name']     ?? '',
            'phone'    => $l['phone']    ?? '',
            'address'  => $l['address']  ?? '',
            'website'  => $l['website']  ?? '',
            'rating'   => $rt,
            'imported' => false,
        ];
    }

    $rc = count($out);
    $db->query("INSERT INTO lead_gen_usage (user_id,location,industry,result_count)
        VALUES ($uid,'".$db->real_escape_string($location)."','".$db->real_escape_string($industry)."',$rc)");
    logActivity('generated leads', "$industry in $location", 0, "$rc leads via $provider");

    echo json_encode(['ok' => true, 'leads' => $out, 'used' => $used + $rc, 'quota' => $quota, 'provider' => $provider]);
    exit;
}

// ════════════════════════════════════════════════════════
// ACTION: IMPORT SINGLE LEAD
// ════════════════════════════════════════════════════════
if ($action === 'import_lead') {
    $rid = (int)($_POST['result_id'] ?? 0);
    if (!$rid) { echo json_encode(['ok' => false, 'error' => 'Missing result_id']); exit; }

    $row = @$db->query("SELECT * FROM lead_gen_results WHERE id=$rid AND user_id=$uid")->fetch_assoc();
    if (!$row)            { echo json_encode(['ok' => false, 'error' => 'Not found']);        exit; }
    if ($row['imported']) { echo json_encode(['ok' => false, 'error' => 'Already imported']); exit; }

    $n = $row['name']; $p = $row['phone']; $svc = $row['industry'];
    $notes = "Generated by Lead Generator. Location: {$row['location']}. Industry: {$row['industry']}.";
    if ($row['website']) $notes .= " Website: {$row['website']}";
    if ($row['rating'])  $notes .= " Rating: {$row['rating']}/5";
    $e = ''; $null = null; $bc = 'LKR'; $st = 'new'; $pr = 'medium'; $src = 'other';

    $stmt = $db->prepare("INSERT INTO leads (name,company,email,phone,source,service_interest,budget_est,budget_currency,stage,priority,expected_close,last_contact,notes,loss_reason,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssdsssssssii", $n,$n,$e,$p,$src,$svc,$null,$bc,$st,$pr,$null,$null,$notes,$null,$null,$uid);
    $stmt->execute();
    $lid = (int)$db->insert_id;
    $db->query("UPDATE lead_gen_results SET imported=1, lead_id=$lid WHERE id=$rid");
    logActivity('imported lead', $n, $lid);
    echo json_encode(['ok' => true, 'lead_id' => $lid, 'message' => "$n imported to Leads"]);
    exit;
}

// ════════════════════════════════════════════════════════
// ACTION: IMPORT ALL
// ════════════════════════════════════════════════════════
if ($action === 'import_all') {
    $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
    $imported = 0;
    foreach ($ids as $rid) {
        $row = @$db->query("SELECT * FROM lead_gen_results WHERE id=$rid AND user_id=$uid AND imported=0")->fetch_assoc();
        if (!$row) continue;
        $n = $row['name']; $p = $row['phone']; $svc = $row['industry'];
        $notes = "Generated by Lead Generator. Location: {$row['location']}. Industry: {$row['industry']}.";
        if ($row['website']) $notes .= " Website: {$row['website']}";
        if ($row['rating'])  $notes .= " Rating: {$row['rating']}/5";
        $e = ''; $null = null; $bc = 'LKR'; $st = 'new'; $pr = 'medium'; $src = 'other';
        $stmt = $db->prepare("INSERT INTO leads (name,company,email,phone,source,service_interest,budget_est,budget_currency,stage,priority,expected_close,last_contact,notes,loss_reason,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssdsssssssii", $n,$n,$e,$p,$src,$svc,$null,$bc,$st,$pr,$null,$null,$notes,$null,$null,$uid);
        $stmt->execute();
        $lid = (int)$db->insert_id;
        $db->query("UPDATE lead_gen_results SET imported=1, lead_id=$lid WHERE id=$rid");
        $imported++;
    }
    logActivity('bulk imported leads', '', 0, "$imported leads");
    echo json_encode(['ok' => true, 'imported' => $imported]);
    exit;
}

// ════════════════════════════════════════════════════════
// ACTION: GET STATS
// ════════════════════════════════════════════════════════
if ($action === 'get_stats') {
    $quota    = (int)lgSetting($db, 'monthly_quota', '500');
    $used     = lgMonthUsage($db, $uid);
    $provider = lgSetting($db, 'api_provider', 'foursquare');
    $api_set  = ($provider === 'foursquare') ? lgSetting($db,'foursquare_key') !== '' : lgSetting($db,'google_api_key') !== '';

    $trend  = [];
    $tr = @$db->query("SELECT DATE_FORMAT(created_at,'%b') mo, DATE_FORMAT(created_at,'%Y-%m') smo, SUM(result_count) cnt FROM lead_gen_usage WHERE user_id=$uid AND created_at>=DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY mo,smo ORDER BY smo");
    if ($tr) $trend = $tr->fetch_all(MYSQLI_ASSOC);

    $recent = [];
    $rc = @$db->query("SELECT industry,location,result_count,created_at FROM lead_gen_usage WHERE user_id=$uid ORDER BY created_at DESC LIMIT 8");
    if ($rc) $recent = $rc->fetch_all(MYSQLI_ASSOC);

    $total_imp = (int)(@$db->query("SELECT COUNT(*) c FROM lead_gen_results WHERE user_id=$uid AND imported=1")->fetch_assoc()['c'] ?? 0);

    echo json_encode([
        'ok'        => true,
        'used'      => $used,
        'quota'     => $quota,
        'api_set'   => $api_set,
        'provider'  => $provider,
        'trend'     => $trend,
        'recent'    => $recent,
        'total_imp' => $total_imp,
    ]);
    exit;
}

// ════════════════════════════════════════════════════════
// ACTION: SAVE SETTINGS (admin only)
// ════════════════════════════════════════════════════════
if ($action === 'save_settings' && isAdmin()) {
    $prov  = in_array($_POST['provider'] ?? '', ['foursquare','google']) ? $_POST['provider'] : 'foursquare';
    $fk    = trim($_POST['foursquare_key'] ?? '');
    $gk    = trim($_POST['google_key']     ?? '');
    $quota = max(10, (int)($_POST['quota'] ?? 500));

    $db->query("UPDATE lead_gen_settings SET setting_val='$prov',          updated_by=$uid WHERE setting_key='api_provider'");
    $db->query("UPDATE lead_gen_settings SET setting_val=$quota,           updated_by=$uid WHERE setting_key='monthly_quota'");
    if ($fk) $db->query("UPDATE lead_gen_settings SET setting_val='".$db->real_escape_string($fk)."', updated_by=$uid WHERE setting_key='foursquare_key'");
    if ($gk) $db->query("UPDATE lead_gen_settings SET setting_val='".$db->real_escape_string($gk)."', updated_by=$uid WHERE setting_key='google_api_key'");

    echo json_encode(['ok' => true, 'message' => 'Settings saved successfully!']);
    exit;
}

// ════════════════════════════════════════════════════════
// ACTION: EXPORT CSV
// ════════════════════════════════════════════════════════
if ($action === 'export_excel') {
    $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
    if (!$ids) { echo json_encode(['ok' => false, 'error' => 'No data to export']); exit; }
    $rows = @$db->query("SELECT name,phone,address,website,rating,industry,location FROM lead_gen_results WHERE id IN(".implode(',',$ids).") AND user_id=$uid")->fetch_all(MYSQLI_ASSOC);
    if (!$rows) { echo json_encode(['ok' => false, 'error' => 'No data']); exit; }
    $csv = "Name,Phone,Address,Website,Rating,Industry,Location\n";
    foreach ($rows as $r) {
        $csv .= implode(',', array_map(fn($v) => '"'.str_replace('"','""',(string)($v??'')).'"', array_values($r))) . "\n";
    }
    echo json_encode(['ok' => true, 'csv' => $csv]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Unknown action']);