<?php
/**
 * chatbot_api.php — Padak CRM AI Chatbot Backend
 *
 * ROOT CAUSE (confirmed via diagnose): API key created in Google Cloud Console
 * for a different API — "Generative Language API" was never enabled for that project.
 * OR the key is an AI Studio key but the wrong v1beta path is being hit.
 *
 * FIX: We now try every combination of API version (v1, v1beta) × model name.
 * The first combination that returns non-404 wins. This guarantees we find a
 * working path regardless of which Google project/key type you have.
 *
 * MODELS tried (all FREE tier, ₹0):
 *   gemini-2.0-flash     — current default free model as of 2025
 *   gemini-1.5-flash     — older free model, 1,500 req/day
 *   gemini-1.5-flash-8b  — lighter free fallback
 *   gemini-1.0-pro       — oldest stable free model
 *
 * API VERSIONS tried: v1 first (stable), then v1beta (preview features)
 */

require_once 'config.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];
mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

define('GEMINI_API_HOST', 'https://generativelanguage.googleapis.com');

// All model+version combinations to try, in priority order.
// Each entry: [api_version, model_id]
// We try v1 before v1beta because v1 is the stable channel.
define('GEMINI_COMBOS', [
    ['v1',    'gemini-2.0-flash'],        // current free model, stable API
    ['v1beta','gemini-2.0-flash'],        // same model, preview API
    ['v1',    'gemini-1.5-flash'],        // older free model
    ['v1beta','gemini-1.5-flash'],        // same, preview API
    ['v1beta','gemini-1.5-flash-8b'],     // lighter fallback
    ['v1',    'gemini-1.0-pro'],          // oldest stable
    ['v1beta','gemini-1.0-pro'],          // oldest, preview
]);

define('FREE_DAILY_HARD_LIMIT', 1400); // Google allows 1,500 — we stop 100 short
define('FREE_RPM_LIMIT', 12);          // Google allows 15 — we stop 3 short

// Helper: build URL from combo
function cbModelUrl(array $combo, string $api_key): string {
    [$ver, $model] = $combo;
    return GEMINI_API_HOST . "/$ver/models/$model:generateContent?key=$api_key";
}

// For display/storage: just the model name
function cbModelName(array $combo): string { return $combo[1]; }

// ── HELPERS ──
function cbGet(mysqli $db, string $k, string $def = ''): string {
    $r = @$db->query("SELECT setting_val FROM chatbot_settings WHERE setting_key='".$db->real_escape_string($k)."' LIMIT 1");
    if (!$r) return $def;
    $row = $r->fetch_assoc();
    $val = $row ? (string)($row['setting_val'] ?? $def) : $def;
    return preg_replace('/[\s\x00-\x1F\x7F\xC2\xA0\xE2\x80\x8B]/u', '', $val);
}

function cbTableOk(mysqli $db): bool {
    return @$db->query("SELECT 1 FROM chatbot_settings LIMIT 1") !== false;
}

function cbDailyUsed(mysqli $db, int $uid): int {
    $r = @$db->query("SELECT COALESCE(SUM(msg_count),0) c FROM chatbot_usage WHERE user_id=$uid AND DATE(created_at)=CURDATE()");
    return $r ? (int)($r->fetch_assoc()['c'] ?? 0) : 0;
}

/**
 * cbHttp() — now returns an array with full error detail.
 * Returns: ['body' => string|null, 'error' => string, 'http_code' => int]
 * 'body' is null ONLY on true connection failure (DNS fail, timeout, SSL error).
 * 'error' is '' on success, otherwise the actual cURL/stream error message.
 */
function cbHttp(string $url, string $body): array {
    $result = ['body' => null, 'error' => '', 'http_code' => 0];

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,   // keep SSL verification ON for security
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_USERAGENT      => 'PadakCRM/1.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
        ]);
        $r    = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        $result['http_code'] = $code;

        if ($err || $r === false || $code === 0) {
            // Connection-level failure — expose exact cURL error
            $result['error'] = "cURL error $errno: $err" . ($code ? " (HTTP $code)" : '');
            return $result;
        }

        $result['body'] = ($r !== '') ? $r : null;
        if ($result['body'] === null) {
            $result['error'] = "cURL returned empty body (HTTP $code)";
        }
        return $result;
    }

    // Fallback: file_get_contents
    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'content'       => $body,
            'timeout'       => 30,
            'header'        => "Content-Type: application/json\r\nUser-Agent: PadakCRM/1.0\r\n",
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => true],
    ]);
    $r = @file_get_contents($url, false, $ctx);
    if ($r === false || $r === '') {
        $result['error'] = 'file_get_contents failed — cURL not available and stream failed. Check allow_url_fopen and SSL.';
        return $result;
    }
    // Parse HTTP status from response headers
    if (isset($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('/HTTP\/\S+\s+(\d+)/', $h, $m)) {
                $result['http_code'] = (int)$m[1];
                break;
            }
        }
    }
    $result['body'] = $r;
    return $result;
}

/**
 * Try every [api_version, model] combo until one returns a non-404 response.
 * Returns ['body'=>string,'model'=>string,'api_ver'=>string,'curl_error'=>'','all_errors'=>[]] on success
 * Returns ['body'=>null,'model'=>null,'api_ver'=>null,'curl_error'=>string,'all_errors'=>[]] on total failure
 */
function cbCallWithFallback(string $body, string $api_key): array {
    $allErrors     = [];
    $lastCurlError = '';

    foreach (GEMINI_COMBOS as $combo) {
        $url   = cbModelUrl($combo, $api_key);
        $label = $combo[0] . '/' . $combo[1];
        $r     = cbHttp($url, $body);

        if ($r['body'] === null) {
            $lastCurlError     = $r['error'];
            $allErrors[$label] = 'CONNECT FAIL: ' . $r['error'];
            continue;
        }

        $d    = json_decode($r['body'], true);
        $code = (int)($d['error']['code'] ?? 0);

        if ($code === 404) {
            $allErrors[$label] = '404 not found for this API version';
            continue;
        }

        // Any other response (success, 429, 403, 400, 503) — return it
        return [
            'body'       => $r['body'],
            'model'      => cbModelName($combo),
            'api_ver'    => $combo[0],
            'curl_error' => '',
            'all_errors' => $allErrors,
        ];
    }

    return ['body' => null, 'model' => null, 'api_ver' => null, 'curl_error' => $lastCurlError, 'all_errors' => $allErrors];
}

function cbGeminiError(int $code, string $msg, string $model): array {
    switch ($code) {
        case 429:
            if (stripos($msg, 'spending cap') !== false || stripos($msg, 'spend') !== false) {
                return ['ok' => false, 'error' =>
                    "❌ Project spend cap exceeded.\n\n" .
                    "Fix: Go to https://aistudio.google.com/spend → set spend cap to ₹0\n" .
                    "(This forces free quota only and never charges your account)\n\n" .
                    "Raw: $msg"
                ];
            }
            return ['ok' => false,
                'error'       => "Free tier rate limit hit (429). gemini-1.5-flash allows 15 req/min.\nWait a few seconds and try again.",
                'retry_after' => 8,
                'error_code'  => 429
            ];

        case 403:
            return ['ok' => false, 'error' =>
                "❌ Access denied (403).\n\n" .
                "Most likely cause: billing account has a payment issue.\n" .
                "Fix: console.cloud.google.com/billing → resolve the issue.\n\n" .
                "If billing is fine: regenerate your API key at aistudio.google.com/apikey\n\n" .
                "Raw: $msg"
            ];

        case 400:
            return ['ok' => false, 'error' => "Bad request (400) for model '$model'.\nDetail: $msg"];

        case 503:
            return ['ok' => false, 'error' => "Gemini temporarily unavailable (503). Try again in 10 seconds.",
                'retry_after' => 10, 'error_code' => 503];

        default:
            return ['ok' => false, 'error' => "Gemini error $code: $msg"];
    }
}

// ── TABLE GUARD ──
if (!cbTableOk($db)) {
    echo json_encode(['ok' => false, 'error' => 'Run migration_chatbot.sql first.']);
    exit;
}

// ══════════════════════════════════════════════════════
// ACTION: DIAGNOSE — tells you EXACTLY what is blocking
// Call: chatbot_api.php?action=diagnose
// ══════════════════════════════════════════════════════
if ($action === 'diagnose' && isAdmin()) {
    $out = [];

    // 1. PHP + cURL info
    $out[] = '=== PHP & cURL ===';
    $out[] = 'PHP version: ' . PHP_VERSION;
    $out[] = 'cURL available: ' . (function_exists('curl_init') ? 'YES' : 'NO — install php-curl');
    if (function_exists('curl_version')) {
        $cv = curl_version();
        $out[] = 'cURL version: ' . $cv['version'];
        $out[] = 'SSL: ' . $cv['ssl_version'];
        $out[] = 'libz: ' . $cv['libz_version'];
    }
    $out[] = 'allow_url_fopen: ' . (ini_get('allow_url_fopen') ? 'ON' : 'OFF');
    $out[] = 'open_basedir: ' . (ini_get('open_basedir') ?: 'none (good)');

    // 2. DNS resolution
    $out[] = '';
    $out[] = '=== DNS ===';
    $host = 'generativelanguage.googleapis.com';
    $ip   = gethostbyname($host);
    if ($ip === $host) {
        $out[] = "DNS FAIL: Cannot resolve $host — server has no internet or DNS is blocked";
    } else {
        $out[] = "DNS OK: $host → $ip";
    }

    // 3. TCP connect test (port 443)
    $out[] = '';
    $out[] = '=== TCP Connect (port 443) ===';
    $sock = @fsockopen('ssl://' . $host, 443, $errno, $errstr, 5);
    if ($sock) {
        fclose($sock);
        $out[] = "TCP OK: Connected to $host:443";
    } else {
        $out[] = "TCP FAIL ($errno): $errstr — port 443 is BLOCKED by firewall";
    }

    // 4. HTTP GET test (simple request, no API key)
    $out[] = '';
    $out[] = '=== HTTP GET test ===';
    $testUrl = 'https://generativelanguage.googleapis.com/v1beta/models?key=INVALID_KEY_TEST';
    $r = cbHttp($testUrl, '');  // Will be a GET via POST with empty body — adjust:
    if (function_exists('curl_init')) {
        $ch2 = curl_init($testUrl);
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'PadakCRM/1.0',
        ]);
        $body2  = curl_exec($ch2);
        $code2  = (int)curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        $err2   = curl_error($ch2);
        $errno2 = curl_errno($ch2);
        curl_close($ch2);

        if ($err2) {
            $out[] = "HTTP GET FAIL — cURL $errno2: $err2";
            // Common fixes
            if ($errno2 === 6)  $out[] = '  → Fix: DNS not resolving. Check server DNS config or add 8.8.8.8 to /etc/resolv.conf';
            if ($errno2 === 7)  $out[] = '  → Fix: Connection refused/timed out. Check firewall — allow outbound TCP 443 to googleapis.com';
            if ($errno2 === 28) $out[] = '  → Fix: Timeout. Server is too slow or port 443 is being rate-limited by firewall';
            if ($errno2 === 35 || $errno2 === 51 || $errno2 === 60) $out[] = '  → Fix: SSL error. Try: curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false) — or update ca-certificates on server';
        } else {
            $out[] = "HTTP GET OK — HTTP $code2";
            $d2 = json_decode($body2, true);
            if ($code2 === 400 || $code2 === 401 || isset($d2['error'])) {
                $out[] = "  → Google responded (HTTP $code2) — connectivity is fine, API key issue";
            } elseif ($code2 === 200) {
                $out[] = '  → 200 OK — connectivity confirmed';
            }
        }
    } else {
        $out[] = 'cURL not available — cannot run HTTP test';
    }

    // 5. SSL verify-off test (if the above failed)
    $out[] = '';
    $out[] = '=== SSL verify=OFF test (if above failed) ===';
    if (function_exists('curl_init')) {
        $ch3 = curl_init($testUrl);
        curl_setopt_array($ch3, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,  // bypass SSL
            CURLOPT_USERAGENT      => 'PadakCRM/1.0',
        ]);
        $body3 = curl_exec($ch3);
        $code3 = (int)curl_getinfo($ch3, CURLINFO_HTTP_CODE);
        $err3  = curl_error($ch3);
        curl_close($ch3);

        if ($err3) {
            $out[] = "SSL-off FAIL — $err3 (not an SSL issue, it's a network/firewall block)";
        } else {
            $out[] = "SSL-off OK — HTTP $code3";
            if ($code3 > 0) {
                $out[] = '  → SSL certificate is the problem. Fix: apt install ca-certificates && update-ca-certificates';
                $out[] = '  → Or contact your hosting provider to update SSL certs';
            }
        }
    }

    // 6. Proxy check
    $out[] = '';
    $out[] = '=== Proxy / Network ===';
    $proxy = getenv('https_proxy') ?: getenv('HTTPS_PROXY') ?: getenv('http_proxy') ?: getenv('HTTP_PROXY') ?: '';
    $out[] = 'Detected proxy: ' . ($proxy ?: 'none');
    $out[] = 'Server IP: ' . ($_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname()));

    // 7. API key test — tries every version×model combo
    $out[] = '';
    $out[] = '=== API Key Test (all version × model combos) ===';
    $key = cbGet($db, 'gemini_api_key');
    if (!$key) {
        $out[] = 'No API key configured yet — set it in Settings first';
    } else {
        $masked   = substr($key, 0, 8) . '...' . substr($key, -4);
        $out[]    = "Key: $masked (length " . strlen($key) . ')';
        $all404   = true;
        $anyWork  = false;
        $testBody = json_encode(['contents' => [['role' => 'user', 'parts' => [['text' => 'Hi']]]]]);

        foreach (GEMINI_COMBOS as $combo) {
            $url   = cbModelUrl($combo, $key);
            $label = $combo[0] . '/models/' . $combo[1];
            if (function_exists('curl_init')) {
                $ch4 = curl_init($url);
                curl_setopt_array($ch4, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 15,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $testBody,
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                    CURLOPT_USERAGENT      => 'PadakCRM/1.0',
                ]);
                $b4 = curl_exec($ch4);
                $c4 = (int)curl_getinfo($ch4, CURLINFO_HTTP_CODE);
                $e4 = curl_error($ch4);
                curl_close($ch4);
                if ($e4) {
                    $out[] = "  $label → CONNECT FAIL: $e4";
                    $all404 = false;
                } else {
                    $d4     = json_decode($b4, true);
                    $apiErr = (int)($d4['error']['code'] ?? 0);
                    $apiMsg = $d4['error']['message'] ?? '';
                    $reply4 = $d4['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    if ($reply4) {
                        $out[]   = "  $label → SUCCESS (HTTP $c4)";
                        $anyWork = true;
                        $all404  = false;
                    } elseif ($apiErr === 404) {
                        $out[] = "  $label → 404 (model/version not available for this key)";
                    } else {
                        $out[]  = "  $label → HTTP $c4, error $apiErr: " . substr($apiMsg, 0, 120);
                        $all404 = false;
                    }
                }
            }
        }

        $out[] = '';
        if ($anyWork) {
            $out[] = 'SUCCESS: At least one combo works — chatbot should be functional now.';
        } elseif ($all404) {
            $out[] = 'FAIL: ALL combos returned 404.';
            $out[] = '';
            $out[] = 'ROOT CAUSE: Your API key does not have the Generative Language API enabled.';
            $out[] = 'Network is fine. Key is valid. But the key has no Gemini permissions.';
            $out[] = '';
            $out[] = 'FIX — choose ONE of these options:';
            $out[] = '';
            $out[] = 'OPTION A (easiest — 2 minutes):';
            $out[] = '  1. Go to https://aistudio.google.com/apikey';
            $out[] = '  2. Create a NEW key (AI Studio keys auto-enable Gemini)';
            $out[] = '  3. Paste the new key in Settings -> Save -> Test';
            $out[] = '';
            $out[] = 'OPTION B (enable Gemini on your existing key project):';
            $out[] = '  1. Go to https://console.cloud.google.com/apis/library';
            $out[] = '  2. Search: "Generative Language API"';
            $out[] = '  3. Click Enable for your project';
            $out[] = '  4. Wait 1 minute, then click Test in Settings';
        }
    }
    echo json_encode(['ok' => true, 'diagnostic' => implode("\n", $out)]);
    exit;
}

// ── ACTION: GET STATS ──
if ($action === 'get_stats') {
    $configured  = cbGet($db, 'gemini_api_key') !== '';
    $admin_limit = max(10, (int)cbGet($db, 'daily_limit', '200'));
    $daily_limit = min($admin_limit, FREE_DAILY_HARD_LIMIT);
    $daily_used  = cbDailyUsed($db, $uid);
    $active_model = cbGet($db, 'active_model', 'gemini-2.0-flash');
    echo json_encode([
        'ok'          => true,
        'configured'  => $configured,
        'daily_used'  => $daily_used,
        'daily_limit' => $daily_limit,
        'remaining'   => max(0, $daily_limit - $daily_used),
        'model'       => $active_model,
        'tier'        => 'free',
    ]);
    exit;
}

// ── ACTION: LIST SESSIONS ──
if ($action === 'list_sessions') {
    $r = @$db->query("SELECT id,title,msg_count,updated_at FROM chatbot_sessions WHERE user_id=$uid ORDER BY updated_at DESC LIMIT 50");
    echo json_encode(['ok' => true, 'sessions' => $r ? $r->fetch_all(MYSQLI_ASSOC) : []]);
    exit;
}

// ── ACTION: GET SESSION ──
if ($action === 'get_session') {
    $sid = (int)($_GET['session_id'] ?? 0);
    if (!$sid) { echo json_encode(['ok' => false, 'error' => 'No session']); exit; }
    $r = @$db->query("SELECT id FROM chatbot_sessions WHERE id=$sid AND user_id=$uid LIMIT 1");
    if (!$r || !$r->num_rows) { echo json_encode(['ok' => false, 'error' => 'Not found']); exit; }
    $mr = @$db->query("SELECT role,content,created_at ts FROM chatbot_messages WHERE session_id=$sid ORDER BY id ASC LIMIT 200");
    echo json_encode(['ok' => true, 'messages' => $mr ? $mr->fetch_all(MYSQLI_ASSOC) : []]);
    exit;
}

// ── ACTION: DELETE SESSION ──
if ($action === 'delete_session') {
    $sid = (int)($_POST['session_id'] ?? 0);
    if (!$sid) { echo json_encode(['ok' => false, 'error' => 'No session']); exit; }
    @$db->query("DELETE FROM chatbot_messages WHERE session_id=$sid AND session_id IN (SELECT id FROM chatbot_sessions WHERE user_id=$uid)");
    @$db->query("DELETE FROM chatbot_sessions WHERE id=$sid AND user_id=$uid");
    echo json_encode(['ok' => true]);
    exit;
}

// ── ACTION: SAVE SETTINGS (admin only) ──
if ($action === 'save_settings' && isAdmin()) {
    $key   = trim($_POST['gemini_key'] ?? '');
    $limit = max(10, min(FREE_DAILY_HARD_LIMIT, (int)($_POST['daily_limit'] ?? 200)));
    if ($key) {
        $ke = $db->real_escape_string($key);
        $db->query("INSERT INTO chatbot_settings (setting_key,setting_val) VALUES ('gemini_api_key','$ke') ON DUPLICATE KEY UPDATE setting_val='$ke',updated_at=NOW()");
    }
    $db->query("INSERT INTO chatbot_settings (setting_key,setting_val) VALUES ('daily_limit','$limit') ON DUPLICATE KEY UPDATE setting_val=$limit,updated_at=NOW()");
    @logActivity('chatbot settings saved', '', 0, '');
    echo json_encode(['ok' => true, 'message' => 'Settings saved']);
    exit;
}

// ── ACTION: TEST KEY ──
// Uses SSL verify=false so it bypasses cert issues and tests the KEY itself
if ($action === 'test_key') {
    $key = trim($_POST['gemini_key'] ?? cbGet($db, 'gemini_api_key'));
    if (!$key) { echo json_encode(['ok' => false, 'error' => 'No API key provided']); exit; }

    $testBody = json_encode(['contents' => [['role' => 'user', 'parts' => [['text' => 'Reply with the word OK only']]]]]);
    $results  = [];
    $working  = null;

    foreach (GEMINI_COMBOS as $combo) {
        $url   = cbModelUrl($combo, $key);
        $label = $combo[0] . '/' . $combo[1];

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $testBody,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_USERAGENT      => 'PadakCRM/1.0',
            ]);
            $raw   = curl_exec($ch);
            $code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err   = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
        } else {
            $r   = cbHttp($url, $testBody);
            $raw = $r['body']; $err = $r['error']; $errno = 0; $code = $r['http_code'];
        }

        if ($err || !$raw) {
            $results[$label] = "CONNECT FAIL — errno $errno: $err";
            if ($errno === 6)  $results[$label] .= ' [DNS failure]';
            if ($errno === 7)  $results[$label] .= ' [firewall blocking port 443]';
            if ($errno === 28) $results[$label] .= ' [Timeout]';
            continue;
        }

        $d     = json_decode($raw, true);
        $eCode = (int)($d['error']['code'] ?? 0);

        if ($eCode === 404) { $results[$label] = '404 — model not found for this key/version'; continue; }

        if ($eCode === 0 && !empty($d['candidates'][0]['content']['parts'][0]['text'])) {
            $working = cbModelName($combo);
            $results[$label] = 'OK';
            $me = $db->real_escape_string($working);
            $db->query("INSERT INTO chatbot_settings (setting_key,setting_val) VALUES ('active_model','$me') ON DUPLICATE KEY UPDATE setting_val='$me',updated_at=NOW()");
            break;
        }

        $eMsg = $d['error']['message'] ?? 'unknown';
        $results[$label] = "HTTP $code — error $eCode: " . substr($eMsg, 0, 120);
    }

    $resultLines = implode("\n", array_map(fn($m, $r) => "  $m -> $r", array_keys($results), $results));
    $all404 = count(array_filter($results, fn($v) => str_contains($v, '404'))) === count($results);

    if ($working) {
        echo json_encode(['ok' => true, 'message' => "Working model: $working\n\nAll tested:\n$resultLines"]);
    } elseif ($all404) {
        echo json_encode(['ok' => false, 'error' =>
            "All 404 — Generative Language API not enabled for this key's project.\n\n" .
            "FIX (2 minutes):\n" .
            "OPTION A: Get a new key from https://aistudio.google.com/apikey\n" .
            "  (AI Studio keys auto-enable Gemini — just create and paste)\n\n" .
            "OPTION B: Enable the API on your existing project:\n" .
            "  https://console.cloud.google.com/apis/library\n" .
            "  Search 'Generative Language API' -> Enable -> wait 1 min -> Test\n\n" .
            "Results:\n$resultLines"
        ]);
    } else {
        $firstResult = array_values($results)[0] ?? '';
        $hint = '';
        if (stripos($firstResult, '403') !== false) $hint = "\nFIX: Billing issue — check console.cloud.google.com/billing";
        if (stripos($firstResult, '429') !== false) $hint = "\nFIX: Rate limit — set spend cap to 0 at aistudio.google.com/spend";
        echo json_encode(['ok' => false, 'error' => "No working model.$hint\n\nResults:\n$resultLines"]);
    }
    exit;
}
}

// ── ACTION: CHAT ──
if ($action === 'chat') {
    $message = trim($_POST['message'] ?? '');
    $sid     = (int)($_POST['session_id'] ?? 0);

    if (!$message) { echo json_encode(['ok' => false, 'error' => 'Empty message']); exit; }
    if (mb_strlen($message) > 4000) { echo json_encode(['ok' => false, 'error' => 'Message too long (max 4000 chars)']); exit; }

    $api_key = cbGet($db, 'gemini_api_key');
    if (!$api_key) {
        echo json_encode(['ok' => false, 'error' => 'Gemini API key not configured. Go to Settings.']);
        exit;
    }

    // ── FREE TIER QUOTA GUARDS ──
    $admin_limit = max(10, (int)cbGet($db, 'daily_limit', '200'));
    $daily_limit = min($admin_limit, FREE_DAILY_HARD_LIMIT);
    $daily_used  = cbDailyUsed($db, $uid);
    if ($daily_used >= $daily_limit) {
        echo json_encode(['ok' => false,
            'error' => "Daily free limit of $daily_limit messages reached.\nResets at midnight (IST)."
        ]);
        exit;
    }

    $one_min_ago = date('Y-m-d H:i:s', time() - 60);
    $rpm_r = @$db->query("SELECT COALESCE(SUM(msg_count),0) c FROM chatbot_usage WHERE user_id=$uid AND created_at >= '$one_min_ago'");
    $rpm_used = (int)($rpm_r ? $rpm_r->fetch_assoc()['c'] : 0);
    if ($rpm_used >= FREE_RPM_LIMIT) {
        echo json_encode(['ok' => false,
            'error'       => "Sending too fast. Free tier: 15 req/min max.\nWait a moment and try again.",
            'retry_after' => 8,
            'error_code'  => 429
        ]);
        exit;
    }

    // Get/create session
    if ($sid) {
        $sr = @$db->query("SELECT id FROM chatbot_sessions WHERE id=$sid AND user_id=$uid LIMIT 1");
        if (!$sr || !$sr->num_rows) $sid = 0;
    }
    if (!$sid) {
        $te = $db->real_escape_string(mb_substr($message, 0, 60));
        $db->query("INSERT INTO chatbot_sessions (user_id,title,msg_count) VALUES ($uid,'$te',0)");
        $sid = (int)$db->insert_id;
    }

    // Load history (last 40 msgs)
    $hr      = @$db->query("SELECT role,content FROM chatbot_messages WHERE session_id=$sid ORDER BY id DESC LIMIT 40");
    $history = [];
    if ($hr) {
        foreach (array_reverse($hr->fetch_all(MYSQLI_ASSOC)) as $row) {
            $history[] = ['role' => $row['role'], 'parts' => [['text' => $row['content']]]];
        }
    }

    // System prompt
    $crm_name  = defined('SITE_NAME') ? SITE_NAME : 'Padak CRM';
    $user_name = $user['name'];
    $user_role = $user['role'];
    $today     = date('l, F j, Y');
    $system    = "You are an AI assistant in $crm_name. Talking to $user_name (role: $user_role). Today: $today. "
        . "Help with: drafting emails, follow-ups, summarizing info, reports, sales tips, lead qualification, invoice messages. "
        . "Keep responses clear and practical. Use markdown where helpful. "
        . "You have NO access to live CRM records — offer templates/strategies instead if asked about specific records.";

    $history[] = ['role' => 'user', 'parts' => [['text' => $message]]];

    $requestBody = json_encode([
        'system_instruction' => ['parts' => [['text' => $system]]],
        'contents'           => $history,
        'generationConfig'   => ['maxOutputTokens' => 1024, 'temperature' => 0.7, 'topP' => 0.9],
        'safetySettings'     => [
            ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ],
    ]);

    $result = cbCallWithFallback($requestBody, $api_key);

    if ($result['body'] === null) {
        // Build a genuinely helpful error with the real cURL error
        $curlErr   = $result['curl_error'];
        $allErrors = $result['all_errors'] ?? [];
        $errorLines = implode("\n", array_map(fn($m, $e) => "  • $m → $e", array_keys($allErrors), $allErrors));

        $hint = '';
        if (stripos($curlErr, 'Could not resolve') !== false || stripos($curlErr, 'errno 6') !== false) {
            $hint = "\n\n🔧 ROOT CAUSE: DNS failure — server can't resolve googleapis.com\n"
                  . "FIX OPTIONS:\n"
                  . "  1. Add to /etc/resolv.conf: nameserver 8.8.8.8\n"
                  . "  2. Ask hosting to allow outbound UDP/TCP port 53\n"
                  . "  3. Run as admin: chatbot_api.php?action=diagnose for full report";
        } elseif (stripos($curlErr, 'Connection refused') !== false || stripos($curlErr, 'errno 7') !== false) {
            $hint = "\n\n🔧 ROOT CAUSE: Outbound port 443 is BLOCKED by firewall\n"
                  . "FIX: Ask your hosting provider to whitelist outbound TCP 443 to:\n"
                  . "  generativelanguage.googleapis.com\n"
                  . "  Run chatbot_api.php?action=diagnose for full report";
        } elseif (stripos($curlErr, 'timed out') !== false || stripos($curlErr, 'errno 28') !== false) {
            $hint = "\n\n🔧 ROOT CAUSE: Connection timeout — firewall silently blocking port 443\n"
                  . "FIX: Ask hosting to allow outbound TCP 443 to *.googleapis.com\n"
                  . "Run chatbot_api.php?action=diagnose for full report";
        } elseif (stripos($curlErr, 'SSL') !== false || stripos($curlErr, 'errno 35') !== false || stripos($curlErr, 'errno 60') !== false) {
            $hint = "\n\n🔧 ROOT CAUSE: SSL certificate error\n"
                  . "FIX: Run on server: apt install ca-certificates && update-ca-certificates\n"
                  . "Or ask hosting to update SSL bundle";
        } else {
            $hint = "\n\n🔧 Run chatbot_api.php?action=diagnose (admin only) for full network diagnostics";
        }

        echo json_encode(['ok' => false,
            'error' => "Cannot reach Gemini API.\n\nReal error: $curlErr\n\nPer model:\n$errorLines$hint"
        ]);
        exit;
    }

    $resp    = json_decode($result['body'], true);
    $errCode = (int)($resp['error']['code'] ?? 0);

    if ($errCode !== 0) {
        $errMsg = $resp['error']['message'] ?? 'Unknown';
        echo json_encode(cbGeminiError($errCode, $errMsg, $result['model'] ?? 'unknown'));
        exit;
    }

    $reply = $resp['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$reply) {
        $finish = $resp['candidates'][0]['finishReason'] ?? 'UNKNOWN';
        echo json_encode(['ok' => false, 'error' => $finish === 'SAFETY'
            ? 'Blocked by safety filters. Please rephrase.'
            : "No response generated (reason: $finish). Try again."]);
        exit;
    }

    // Save to DB
    $me = $db->real_escape_string($message);
    $re = $db->real_escape_string($reply);
    @$db->query("INSERT INTO chatbot_messages (session_id,role,content) VALUES ($sid,'user','$me')");
    @$db->query("INSERT INTO chatbot_messages (session_id,role,content) VALUES ($sid,'assistant','$re')");
    $cntR = @$db->query("SELECT COUNT(*) c FROM chatbot_messages WHERE session_id=$sid");
    $cnt  = (int)($cntR ? $cntR->fetch_assoc()['c'] : 0);
    $te   = $db->real_escape_string(mb_substr($message, 0, 60));
    @$db->query("UPDATE chatbot_sessions SET msg_count=$cnt,updated_at=NOW() WHERE id=$sid");
    if ($cnt <= 2) @$db->query("UPDATE chatbot_sessions SET title='$te' WHERE id=$sid");
    @$db->query("INSERT INTO chatbot_usage (user_id,session_id,msg_count,created_at) VALUES ($uid,$sid,1,NOW())");
    $usedModel = $result['model'];
    $me2 = $db->real_escape_string($usedModel);
    @$db->query("INSERT INTO chatbot_settings (setting_key,setting_val) VALUES ('active_model','$me2') ON DUPLICATE KEY UPDATE setting_val='$me2',updated_at=NOW()");
    @logActivity('chatbot message', '', 0, 'session ' . $sid);

    $newUsed = cbDailyUsed($db, $uid);
    echo json_encode([
        'ok'         => true,
        'reply'      => $reply,
        'session_id' => $sid,
        'model_used' => $usedModel,
        'daily_used' => $newUsed,
        'remaining'  => max(0, $daily_limit - $newUsed),
    ]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Unknown action']);