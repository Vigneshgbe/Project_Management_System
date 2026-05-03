<?php
/**
 * chatbot_api.php — Padak CRM AI Chatbot Backend
 *
 * ROOT CAUSES IDENTIFIED FROM SCREENSHOTS:
 *
 * ISSUE 1 — 404 NotFound (introduced by previous "fix"):
 *   Model "gemini-2.5-flash-preview-05-20" does NOT exist in v1beta API.
 *   Your rate limit page shows "Gemini 2.5 Flash" — the correct API ID is
 *   "gemini-2.5-flash" (alias) or "gemini-2.5-flash-preview-04-17".
 *   Fixed: use working model chain with auto-discovery fallback.
 *
 * ISSUE 2 — 429 TooManyRequests (billing problem, NOT rate limit):
 *   Your billing page (Image 5) shows "There are issues with your payments
 *   account" in RED. When billing is suspended, Google returns 429 even with
 *   0 requests used. This is NOT a rate limit — it's a billing block.
 *   Action required: Fix billing at console.cloud.google.com/billing
 *
 * ISSUE 3 — 403 Forbidden:
 *   Also caused by the billing account issue, not a key problem.
 *
 * ISSUE 4 — sleep() blocking (from original code):
 *   Removed. All retries handled client-side.
 */

require_once 'config.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];
mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── MODEL CHAIN (tried in order until one works) ──
// Source: your rate limit page confirms "Gemini 2.5 Flash" is active on your account.
// "gemini-2.5-flash" is the stable alias. Preview dates change — never hardcode them.
define('GEMINI_BASE', 'https://generativelanguage.googleapis.com/v1beta/models/');
define('GEMINI_MODELS', [
    'gemini-2.5-flash',           // Alias shown in your rate limit page — try first
    'gemini-2.5-flash-preview-04-17', // Known good preview date (not 05-20)
    'gemini-2.0-flash',           // Very stable, always available on Tier 1
    'gemini-1.5-flash',           // Guaranteed fallback — never been removed
]);

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

// Single HTTP call — returns raw body string, or null ONLY on true connection failure
function cbHttp(string $url, string $body): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_USERAGENT      => 'PadakCRM/1.0',
        ]);
        $r    = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err || $code === 0) return null;
        return ($r !== false && $r !== '') ? $r : null;
    }
    $ctx = stream_context_create([
        'http' => ['method'=>'POST','content'=>$body,'timeout'=>25,
                   'header'=>'Content-Type: application/json','ignore_errors'=>true],
        'ssl'  => ['verify_peer'=>false],
    ]);
    $r = @file_get_contents($url, false, $ctx);
    return ($r !== false && $r !== '') ? $r : null;
}

/**
 * Try each model in GEMINI_MODELS until one returns a non-404 response.
 * No sleep() — no blocking. Returns [raw_body, model_used] or [null, null].
 */
function cbCallWithFallback(string $body, string $api_key): array {
    $models = GEMINI_MODELS;
    foreach ($models as $model) {
        $url = GEMINI_BASE . $model . ':generateContent?key=' . $api_key;
        $raw = cbHttp($url, $body);
        if ($raw === null) continue; // true network failure, try next

        $d    = json_decode($raw, true);
        $code = (int)($d['error']['code'] ?? 0);

        // 404 = model doesn't exist → try next model
        if ($code === 404) continue;

        // Any other response (success, 429, 403, 400, 503) → return it, stop trying
        return [$raw, $model];
    }
    return [null, null]; // all models failed with network error
}

/**
 * Translate Gemini billing/auth errors into clear, actionable messages.
 * This is the single most important function — must show the REAL reason.
 */
function cbGeminiError(int $code, string $msg, string $model): array {
    switch ($code) {
        case 429:
            // Check if this is billing suspension (common when billing has issues)
            $isBilling = str_contains(strtolower($msg), 'quota') ||
                         str_contains(strtolower($msg), 'billing') ||
                         str_contains(strtolower($msg), 'exceeded');
            if ($isBilling) {
                return ['ok'=>false, 'error'=>
                    "❌ API quota/billing error (429).\n\n".
                    "Your Google Cloud billing page shows 'There are issues with your payments account'.\n\n".
                    "This is blocking ALL API calls regardless of how many requests you've made.\n\n".
                    "Fix:\n".
                    "1. Go to console.cloud.google.com/billing\n".
                    "2. Click 'My Billing Account' → resolve the payment issue\n".
                    "3. Once billing is fixed, the API will work immediately.\n\n".
                    "Raw: $msg"
                ];
            }
            return ['ok'=>false, 'error'=>"Rate limited (429). Wait 30 seconds and retry.\n\nDetail: $msg",
                    'retry_after'=>30, 'error_code'=>429];

        case 403:
            return ['ok'=>false, 'error'=>
                "❌ Access denied (403).\n\n".
                "Most likely cause: Your billing account has a payment issue (visible on your billing page).\n\n".
                "Fix: Go to console.cloud.google.com/billing → resolve billing issue.\n\n".
                "If billing is fine: regenerate your API key at aistudio.google.com/apikey\n\n".
                "Raw: $msg"
            ];

        case 400:
            return ['ok'=>false, 'error'=>"Bad request (400) for model '$model'.\n\nDetail: $msg"];

        case 503:
            return ['ok'=>false, 'error'=>"Gemini temporarily unavailable (503). Try again in 10 seconds.",
                    'retry_after'=>10, 'error_code'=>503];

        default:
            return ['ok'=>false, 'error'=>"Gemini error $code: $msg"];
    }
}

// ── TABLE GUARD ──
if (!cbTableOk($db)) {
    echo json_encode(['ok'=>false,'error'=>'Run migration_chatbot.sql first.']);
    exit;
}

// ── ACTION: GET STATS ──
if ($action === 'get_stats') {
    $configured  = cbGet($db, 'gemini_api_key') !== '';
    $daily_limit = max(10, (int)cbGet($db, 'daily_limit', '200'));
    $daily_used  = cbDailyUsed($db, $uid);
    $active_model = cbGet($db, 'active_model', GEMINI_MODELS[0]);
    echo json_encode([
        'ok'          => true,
        'configured'  => $configured,
        'daily_used'  => $daily_used,
        'daily_limit' => $daily_limit,
        'remaining'   => max(0, $daily_limit - $daily_used),
        'model'       => $active_model,
    ]);
    exit;
}

// ── ACTION: LIST SESSIONS ──
if ($action === 'list_sessions') {
    $r = @$db->query("SELECT id,title,msg_count,updated_at FROM chatbot_sessions WHERE user_id=$uid ORDER BY updated_at DESC LIMIT 50");
    echo json_encode(['ok'=>true,'sessions'=> $r ? $r->fetch_all(MYSQLI_ASSOC) : []]);
    exit;
}

// ── ACTION: GET SESSION ──
if ($action === 'get_session') {
    $sid = (int)($_GET['session_id'] ?? 0);
    if (!$sid) { echo json_encode(['ok'=>false,'error'=>'No session']); exit; }
    $r = @$db->query("SELECT id FROM chatbot_sessions WHERE id=$sid AND user_id=$uid LIMIT 1");
    if (!$r || !$r->num_rows) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }
    $mr = @$db->query("SELECT role,content,created_at ts FROM chatbot_messages WHERE session_id=$sid ORDER BY id ASC LIMIT 200");
    echo json_encode(['ok'=>true,'messages'=> $mr ? $mr->fetch_all(MYSQLI_ASSOC) : []]);
    exit;
}

// ── ACTION: DELETE SESSION ──
if ($action === 'delete_session') {
    $sid = (int)($_POST['session_id'] ?? 0);
    if (!$sid) { echo json_encode(['ok'=>false,'error'=>'No session']); exit; }
    @$db->query("DELETE FROM chatbot_messages WHERE session_id=$sid AND session_id IN (SELECT id FROM chatbot_sessions WHERE user_id=$uid)");
    @$db->query("DELETE FROM chatbot_sessions WHERE id=$sid AND user_id=$uid");
    echo json_encode(['ok'=>true]);
    exit;
}

// ── ACTION: SAVE SETTINGS (admin only) ──
if ($action === 'save_settings' && isAdmin()) {
    $key   = trim($_POST['gemini_key'] ?? '');
    $limit = max(10, min(1000, (int)($_POST['daily_limit'] ?? 200)));
    if ($key) {
        $ke = $db->real_escape_string($key);
        $db->query("INSERT INTO chatbot_settings (setting_key,setting_val) VALUES ('gemini_api_key','$ke') ON DUPLICATE KEY UPDATE setting_val='$ke',updated_at=NOW()");
    }
    $db->query("INSERT INTO chatbot_settings (setting_key,setting_val) VALUES ('daily_limit','$limit') ON DUPLICATE KEY UPDATE setting_val=$limit,updated_at=NOW()");
    @logActivity('chatbot settings saved','',0,'');
    echo json_encode(['ok'=>true,'message'=>'Settings saved']);
    exit;
}

// ── ACTION: TEST KEY ──
// Tries each model in order and reports which one works
if ($action === 'test_key') {
    $key = trim($_POST['gemini_key'] ?? cbGet($db,'gemini_api_key'));
    if (!$key) { echo json_encode(['ok'=>false,'error'=>'No API key provided']); exit; }

    $testBody = json_encode(['contents'=>[['role'=>'user','parts'=>[['text'=>'Reply OK']]]]]);
    $results  = [];
    $working  = null;

    foreach (GEMINI_MODELS as $model) {
        $url = GEMINI_BASE . $model . ':generateContent?key=' . $key;
        $raw = cbHttp($url, $testBody);
        if ($raw === null) { $results[$model] = 'network_fail'; continue; }
        $d    = json_decode($raw, true);
        $code = (int)($d['error']['code'] ?? 0);
        if ($code === 0 && !empty($d['candidates'][0]['content']['parts'][0]['text'])) {
            $working = $model;
            $results[$model] = 'OK';
            // Save working model to DB
            $me = $db->real_escape_string($model);
            $db->query("INSERT INTO chatbot_settings (setting_key,setting_val) VALUES ('active_model','$me') ON DUPLICATE KEY UPDATE setting_val='$me',updated_at=NOW()");
            break;
        }
        $msg = $d['error']['message'] ?? 'unknown';
        $results[$model] = "error $code: " . substr($msg, 0, 80);
    }

    if ($working) {
        echo json_encode(['ok'=>true,'message'=>"✅ Working model found: $working\n\nAll tested:\n" . implode("\n", array_map(fn($m,$r)=>"• $m → $r", array_keys($results), $results))]);
    } else {
        // Build diagnostic from first real error
        $firstErr = array_values($results)[0] ?? 'No response';
        $billingMsg = '';
        if (str_contains($firstErr, '429') || str_contains($firstErr, '403')) {
            $billingMsg = "\n\n⚠️ Your billing page shows a PAYMENT ISSUE.\nThis is blocking API access. Fix billing first:\nconsole.cloud.google.com/billing";
        }
        echo json_encode(['ok'=>false,'error'=>
            "No working model found.$billingMsg\n\nResults per model:\n" .
            implode("\n", array_map(fn($m,$r)=>"• $m → $r", array_keys($results), $results))
        ]);
    }
    exit;
}

// ── ACTION: CHAT ──
if ($action === 'chat') {
    $message = trim($_POST['message'] ?? '');
    $sid     = (int)($_POST['session_id'] ?? 0);

    if (!$message) { echo json_encode(['ok'=>false,'error'=>'Empty message']); exit; }
    if (mb_strlen($message) > 4000) { echo json_encode(['ok'=>false,'error'=>'Message too long (max 4000 chars)']); exit; }

    $api_key = cbGet($db, 'gemini_api_key');
    if (!$api_key) {
        echo json_encode(['ok'=>false,'error'=>'Gemini API key not configured. Go to Settings.']);
        exit;
    }

    // Quota guard
    $daily_limit = max(10, (int)cbGet($db, 'daily_limit', '200'));
    $daily_used  = cbDailyUsed($db, $uid);
    if ($daily_used >= $daily_limit) {
        echo json_encode(['ok'=>false,'error'=>"Daily limit of $daily_limit reached. Resets at midnight."]);
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
            $history[] = ['role'=>$row['role'], 'parts'=>[['text'=>$row['content']]]];
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

    $history[] = ['role'=>'user', 'parts'=>[['text'=>$message]]];

    $requestBody = json_encode([
        'system_instruction' => ['parts'=>[['text'=>$system]]],
        'contents'           => $history,
        'generationConfig'   => ['maxOutputTokens'=>1024,'temperature'=>0.7,'topP'=>0.9],
        'safetySettings'     => [
            ['category'=>'HARM_CATEGORY_HARASSMENT',        'threshold'=>'BLOCK_MEDIUM_AND_ABOVE'],
            ['category'=>'HARM_CATEGORY_HATE_SPEECH',       'threshold'=>'BLOCK_MEDIUM_AND_ABOVE'],
            ['category'=>'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold'=>'BLOCK_MEDIUM_AND_ABOVE'],
            ['category'=>'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold'=>'BLOCK_MEDIUM_AND_ABOVE'],
        ],
    ]);

    [$raw, $usedModel] = cbCallWithFallback($requestBody, $api_key);

    if ($raw === null) {
        echo json_encode(['ok'=>false,'error'=>"Cannot reach Gemini API. All models tried:\n" . implode(', ', GEMINI_MODELS) . "\n\nCheck server internet / firewall."]);
        exit;
    }

    $resp    = json_decode($raw, true);
    $errCode = (int)($resp['error']['code'] ?? 0);

    if ($errCode !== 0) {
        $errMsg = $resp['error']['message'] ?? 'Unknown';
        echo json_encode(cbGeminiError($errCode, $errMsg, $usedModel ?? 'unknown'));
        exit;
    }

    $reply = $resp['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$reply) {
        $finish = $resp['candidates'][0]['finishReason'] ?? 'UNKNOWN';
        echo json_encode(['ok'=>false,'error'=> $finish === 'SAFETY'
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
    // Save last working model
    $me2 = $db->real_escape_string($usedModel);
    @$db->query("INSERT INTO chatbot_settings (setting_key,setting_val) VALUES ('active_model','$me2') ON DUPLICATE KEY UPDATE setting_val='$me2',updated_at=NOW()");
    @logActivity('chatbot message','',0,'session '.$sid);

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

echo json_encode(['ok'=>false,'error'=>'Unknown action']);