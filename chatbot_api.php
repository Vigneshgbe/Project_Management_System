<?php
/**
 * chatbot_api.php
 * Padak CRM — AI Chatbot Backend
 * Uses Google Gemini 2.5 Flash (FREE tier: 10 RPM, 250 RPD per project)
 *
 * HONEST FREE TIER FACTS (April 2026):
 *  - Gemini 2.5 Flash: 10 RPM, 250 req/day FREE → we cap at 200/day to stay safe
 *  - Gemini 2.5 Pro: PAID ONLY since April 2026 — do NOT use
 *  - Places API $200 credit: SEPARATE product, NOT shared with Gemini API
 *  - Use SEPARATE Google Cloud project for Gemini to keep quotas isolated
 */
require_once 'config.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];
mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── MODEL CONSTANT — update here if Google renames the model ──
// Primary: stable alias. Fallback: dated preview (used automatically on 403/404).
// Lite: higher RPM (15 vs 10), higher RPD (1000 vs 500) — auto-used on persistent 429.
define('GEMINI_MODEL',          'gemini-2.5-flash');
define('GEMINI_MODEL_FALLBACK', 'gemini-2.5-flash-preview-05-20');
define('GEMINI_MODEL_LITE',     'gemini-2.5-flash-lite'); // 15 RPM, 1000 RPD free
define('GEMINI_BASE',           'https://generativelanguage.googleapis.com/v1beta/models/');

// ── HELPERS ──
function cbGet(mysqli $db, string $k, string $def = ''): string {
    $r = @$db->query("SELECT setting_val FROM chatbot_settings WHERE setting_key='".$db->real_escape_string($k)."' LIMIT 1");
    if (!$r) return $def;
    $row = $r->fetch_assoc();
    $val = $row ? (string)($row['setting_val'] ?? $def) : $def;
    // Strip ALL whitespace including unicode zero-width chars that trim() misses.
    // Critical for API keys pasted directly into phpMyAdmin.
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
 * cbHttp — POST to an API endpoint and return the raw response body.
 *
 * FIX: Previously returned null for any non-2xx HTTP code, which meant a
 * 429 Rate-Limit or 503 Temporary Error from Gemini looked identical to a
 * complete network failure ("Cannot reach Gemini API"). Now we only return
 * null when there is a true network/connection error (curl error or HTTP 0).
 * All API error responses (4xx, 5xx) are returned as-is so the caller can
 * parse the JSON error body and show the real error message.
 *
 * Returns: response body string, or null ONLY on true connection failure.
 */
function cbHttp(string $url, string $body, array $headers = []): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/json'], $headers),
            CURLOPT_USERAGENT      => 'PadakCRM/1.0',
        ]);
        $r    = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        // Return null ONLY on true network failure (curl error or no response at all)
        if ($err || $code === 0) return null;
        // Return body for ALL HTTP codes — 4xx/5xx contain JSON error details we need
        return ($r !== false && $r !== '') ? $r : null;
    }
    // stream_context fallback (no curl)
    $ctx = stream_context_create([
        'http' => [
            'method'         => 'POST',
            'content'        => $body,
            'timeout'        => 30,
            'header'         => implode("\r\n", array_merge(['Content-Type: application/json'], $headers)),
            'ignore_errors'  => true,   // returns body even on 4xx/5xx
            'user_agent'     => 'PadakCRM/1.0',
        ],
        'ssl' => ['verify_peer' => false],
    ]);
    $r = @file_get_contents($url, false, $ctx);
    return ($r !== false && $r !== '') ? $r : null;
}

/**
 * cbCallGemini — POST to Gemini with exponential backoff + model fallback.
 *
 * Free tier: 10 RPM for gemini-2.5-flash, 15 RPM for gemini-2.5-flash-lite.
 * Strategy:
 *  1. Try primary model.
 *  2. On 429: wait 6s, retry primary.
 *  3. Still 429: wait 6s more, try flash-lite (15 RPM — higher quota, still free).
 *  4. On 403/404: try dated preview model.
 *
 * Returns raw response body, or null only on true connection failure.
 */
function cbCallGemini(string $url, string $body, string $api_key = ''): ?string {
    // Attempt 1
    $raw = cbHttp($url, $body);
    if ($raw === null) return null;

    $d       = json_decode($raw, true);
    $errCode = $d['error']['code'] ?? 0;

    // 429: wait 6s, retry same model
    if ($errCode === 429 || $errCode === 503) {
        sleep(6);
        $raw = cbHttp($url, $body);
        if ($raw === null) return null;
        $d       = json_decode($raw, true);
        $errCode = $d['error']['code'] ?? 0;
    }

    // Still 429: fall back to flash-lite (15 RPM, 1000 RPD — higher free quota)
    if (($errCode === 429 || $errCode === 503) && $api_key) {
        sleep(6);
        $lite_url = GEMINI_BASE.GEMINI_MODEL_LITE.':generateContent?key='.$api_key;
        $raw2 = cbHttp($lite_url, $body);
        if ($raw2 !== null) {
            $d2 = json_decode($raw2, true);
            // If lite worked or gave a different error, use it
            if (empty($d2['error']) || ($d2['error']['code'] ?? 0) !== 429) {
                return $raw2;
            }
        }
    }

    // 403/404: try dated preview name
    if (($errCode === 403 || $errCode === 404) && $api_key) {
        $fallback_url = GEMINI_BASE.GEMINI_MODEL_FALLBACK.':generateContent?key='.$api_key;
        $raw3 = cbHttp($fallback_url, $body);
        if ($raw3 !== null) return $raw3;
    }

    return $raw;
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
    echo json_encode([
        'ok'          => true,
        'configured'  => $configured,
        'daily_used'  => $daily_used,
        'daily_limit' => $daily_limit,
        'remaining'   => max(0, $daily_limit - $daily_used),
    ]);
    exit;
}

// ── ACTION: LIST SESSIONS ──
if ($action === 'list_sessions') {
    $r = @$db->query("SELECT id,title,msg_count,updated_at FROM chatbot_sessions WHERE user_id=$uid ORDER BY updated_at DESC LIMIT 50");
    $sessions = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode(['ok'=>true,'sessions'=>$sessions]);
    exit;
}

// ── ACTION: GET SESSION ──
if ($action === 'get_session') {
    $sid = (int)($_GET['session_id'] ?? 0);
    if (!$sid) { echo json_encode(['ok'=>false,'error'=>'No session']); exit; }
    $r = @$db->query("SELECT id FROM chatbot_sessions WHERE id=$sid AND user_id=$uid LIMIT 1");
    if (!$r || !$r->num_rows) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }
    $mr = @$db->query("SELECT role,content,created_at ts FROM chatbot_messages WHERE session_id=$sid ORDER BY id ASC LIMIT 200");
    $msgs = $mr ? $mr->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode(['ok'=>true,'messages'=>$msgs]);
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
    $limit = max(10, min(240, (int)($_POST['daily_limit'] ?? 200)));
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
if ($action === 'test_key') {
    $key = trim($_POST['gemini_key'] ?? cbGet($db,'gemini_api_key'));
    if (!$key) { echo json_encode(['ok'=>false,'error'=>'No API key provided']); exit; }

    $url  = GEMINI_BASE.GEMINI_MODEL.':generateContent?key='.$key;
    $body = json_encode(['contents'=>[['role'=>'user','parts'=>[['text'=>'Reply with exactly: OK']]]]]);
    $raw  = cbHttp($url, $body);

    // True network failure
    if ($raw === null) {
        echo json_encode(['ok'=>false,'error'=>"Cannot reach Gemini API.\n\nPossible causes:\n• Server has no internet access to googleapis.com\n• Firewall blocking outbound HTTPS on port 443\n• PHP curl extension disabled\n\nTry: ping generativelanguage.googleapis.com from your server."]);
        exit;
    }

    $d = json_decode($raw, true);

    // On 403/404 try the fallback model name before giving up
    $errCode = $d['error']['code'] ?? 0;
    if (($errCode === 403 || $errCode === 404) && GEMINI_MODEL !== GEMINI_MODEL_FALLBACK) {
        $url2 = GEMINI_BASE.GEMINI_MODEL_FALLBACK.':generateContent?key='.$key;
        $raw2 = cbHttp($url2, $body);
        if ($raw2 !== null) {
            $d2 = json_decode($raw2, true);
            if (!empty($d2['candidates'][0]['content']['parts'][0]['text'])) {
                // Fallback worked — tell user which model to use
                echo json_encode(['ok'=>true,'message'=>"✅ Key works with model: ".GEMINI_MODEL_FALLBACK." (update GEMINI_MODEL constant in chatbot_api.php)"]);
                exit;
            }
            // Use fallback error if it's more informative
            if (empty($d2['error'])) $d = $d2;
        }
    }

    if (!empty($d['candidates'][0]['content']['parts'][0]['text'])) {
        echo json_encode(['ok'=>true,'message'=>'✅ Gemini API key works! Model: '.GEMINI_MODEL]);
    } elseif (!empty($d['error'])) {
        $code = $d['error']['code'] ?? 0;
        $msg  = $d['error']['message'] ?? 'Unknown error';
        if ($code === 403 || $code === 401) {
            echo json_encode(['ok'=>false,'error'=>"API key rejected (403).\n\nMost common cause: The key was created in Google Cloud Console but the 'Generative Language API' is not enabled in that project.\n\nFix:\n1. Get a NEW key from aistudio.google.com/apikey (not Cloud Console)\n   — AI Studio keys work immediately, no extra setup needed.\n\nOR if using Cloud Console key:\n2. Go to console.cloud.google.com → APIs → Enable 'Generative Language API'\n\nError: $msg"]);
        } elseif ($code === 429) {
            echo json_encode(['ok'=>true,'message'=>"✅ Key is valid but rate-limited (429) — free tier 10 RPM. Wait 60s. Your key works fine."]);
        } elseif ($code === 400) {
            echo json_encode(['ok'=>false,'error'=>"Bad request (400). Model '".GEMINI_MODEL."' may not be available.\n\nError: $msg"]);
        } else {
            echo json_encode(['ok'=>false,'error'=>"API error $code: $msg"]);
        }
    } else {
        echo json_encode(['ok'=>false,'error'=>'Unexpected response: '.substr($raw,0,300)]);
    }
    exit;
}

// ── ACTION: KEY INFO (admin only — shows masked stored key for debugging) ──
if ($action === 'key_info' && isAdmin()) {
    $raw_key = cbGet($db, 'gemini_api_key');
    $len     = strlen($raw_key);
    $masked  = $len > 8 ? substr($raw_key, 0, 6).'...'.substr($raw_key, -4) : ($len > 0 ? str_repeat('*', $len) : '');
    $has_spaces = preg_match('/\s/', $raw_key) ? 'YES — whitespace found!' : 'No';
    echo json_encode([
        'ok'         => true,
        'key_length' => $len,
        'key_masked' => $masked,
        'has_spaces' => $has_spaces,
        'starts_with'=> $len > 0 ? substr($raw_key, 0, 6) : '(empty)',
        'model'      => GEMINI_MODEL,
        'fallback'   => GEMINI_MODEL_FALLBACK,
    ]);
    exit;
}

// ── ACTION: CHAT ──
if ($action === 'chat') {
    $message = trim($_POST['message'] ?? '');
    $sid     = (int)($_POST['session_id'] ?? 0);

    if (!$message) { echo json_encode(['ok'=>false,'error'=>'Empty message']); exit; }
    if (mb_strlen($message) > 4000) { echo json_encode(['ok'=>false,'error'=>'Message too long (max 4000 chars)']); exit; }

    $api_key = cbGet($db, 'gemini_api_key');
    if (!$api_key) { echo json_encode(['ok'=>false,'error'=>'Gemini API key not configured. Ask admin to set it up in Settings.']); exit; }

    // ── QUOTA GUARD ──
    $daily_limit = max(10, (int)cbGet($db, 'daily_limit', '200'));
    $daily_used  = cbDailyUsed($db, $uid);
    if ($daily_used >= $daily_limit) {
        echo json_encode(['ok'=>false,'error'=>"Daily message limit of $daily_limit reached. Resets at midnight. (Free tier: 250 req/day, capped at $daily_limit for safety)"]);
        exit;
    }

    // ── GET/CREATE SESSION ──
    if ($sid) {
        $sr = @$db->query("SELECT id FROM chatbot_sessions WHERE id=$sid AND user_id=$uid LIMIT 1");
        if (!$sr || !$sr->num_rows) $sid = 0;
    }
    if (!$sid) {
        $title = mb_substr($message, 0, 60);
        $te = $db->real_escape_string($title);
        $db->query("INSERT INTO chatbot_sessions (user_id,title,msg_count) VALUES ($uid,'$te',0)");
        $sid = (int)$db->insert_id;
    }

    // ── LOAD HISTORY (last 40 messages to keep context without hitting token limits) ──
    $hr = @$db->query("SELECT role,content FROM chatbot_messages WHERE session_id=$sid ORDER BY id DESC LIMIT 40");
    $history = [];
    if ($hr) {
        $rows = $hr->fetch_all(MYSQLI_ASSOC);
        foreach (array_reverse($rows) as $row) {
            $history[] = ['role' => $row['role'], 'parts' => [['text' => $row['content']]]];
        }
    }

    // ── SYSTEM PROMPT ──
    $crm_name  = defined('SITE_NAME') ? SITE_NAME : 'Padak CRM';
    $user_name = $user['name'];
    $user_role = $user['role'];
    $today     = date('l, F j, Y');

    $system_prompt = "You are an AI assistant built into $crm_name, an internal CRM system. "
        . "You are talking to $user_name (role: $user_role). Today is $today. "
        . "You help with CRM-related tasks: drafting emails, writing follow-ups, summarizing information, "
        . "creating reports, answering business questions, giving sales tips, helping with lead qualification, "
        . "writing invoice messages, project updates, and general productivity. "
        . "Keep responses clear and practical. Use markdown formatting where helpful. "
        . "When asked to draft something, do it directly without excessive preamble. "
        . "You have NO access to live CRM data — if asked about specific records, politely clarify this "
        . "and offer to help with templates, strategies, or general advice instead.";

    // ── BUILD REQUEST ──
    $history[] = ['role' => 'user', 'parts' => [['text' => $message]]];

    $request_body = json_encode([
        'system_instruction' => ['parts' => [['text' => $system_prompt]]],
        'contents'           => $history,
        'generationConfig'   => [
            'maxOutputTokens' => 1024,
            'temperature'     => 0.7,
            'topP'            => 0.9,
        ],
        'safetySettings' => [
            ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ],
    ]);

    $url = GEMINI_BASE.GEMINI_MODEL.':generateContent?key='.$api_key;
    $raw = cbCallGemini($url, $request_body, $api_key);

    // True network failure (curl couldn't connect at all)
    if ($raw === null) {
        echo json_encode(['ok'=>false,'error'=>"Cannot reach Gemini API. Check server internet connection.\n\nIf this worked before and stopped, possible causes:\n• Temporary Gemini outage (check status.cloud.google.com)\n• Server firewall now blocking googleapis.com\n• Daily quota exhausted on Google's side (resets midnight Pacific)"]);
        exit;
    }

    $resp    = json_decode($raw, true);
    $errCode = $resp['error']['code'] ?? 0;

    // Auto-retry with fallback model on 403/404 (model renamed or not available in region)
    if (($errCode === 403 || $errCode === 404) && GEMINI_MODEL !== GEMINI_MODEL_FALLBACK) {
        $url2 = GEMINI_BASE.GEMINI_MODEL_FALLBACK.':generateContent?key='.$api_key;
        $raw2 = cbCallGemini($url2, $request_body);
        if ($raw2 !== null) {
            $resp2 = json_decode($raw2, true);
            // If fallback succeeded, use its response
            if (!empty($resp2['candidates'][0]['content']['parts'][0]['text'])) {
                $resp = $resp2;
                $errCode = 0;
            }
        }
    }

    // Handle API-level errors (now properly parsed instead of swallowed)
    if (!empty($resp['error'])) {
        $code = $resp['error']['code'] ?? 0;
        $msg  = $resp['error']['message'] ?? 'Unknown error';
        if ($code === 429) {
            echo json_encode(['ok'=>false,'error'=>"Rate limit reached (429). Gemini free tier allows 10 requests/minute. Please wait a moment and try again."]);
        } elseif ($code === 403) {
            echo json_encode(['ok'=>false,'error'=>"API key rejected (403).\n\nMost common cause: Key created in Google Cloud Console without enabling 'Generative Language API', or key has IP restrictions.\n\nFix: Go to ⚙ Settings → re-save your key. Get a fresh key from aistudio.google.com/apikey if needed — AI Studio keys work immediately with no extra setup."]);
        } elseif ($code === 503) {
            echo json_encode(['ok'=>false,'error'=>"Gemini service temporarily unavailable (503). Please try again in a few seconds."]);
        } elseif ($code === 400 && str_contains($msg, 'model')) {
            // Model not found — suggest fix
            echo json_encode(['ok'=>false,'error'=>"Model '".GEMINI_MODEL."' not available (400). Please check aistudio.google.com for the current free model name and update chatbot_api.php line: define('GEMINI_MODEL', ...)"]);
        } else {
            echo json_encode(['ok'=>false,'error'=>"Gemini API error $code: $msg"]);
        }
        exit;
    }

    // Extract reply text
    $reply = $resp['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$reply) {
        $finish = $resp['candidates'][0]['finishReason'] ?? 'UNKNOWN';
        if ($finish === 'SAFETY') {
            echo json_encode(['ok'=>false,'error'=>'Response blocked by safety filters. Please rephrase your message.']);
        } else {
            echo json_encode(['ok'=>false,'error'=>'No response from AI (reason: '.$finish.'). Please try again.']);
        }
        exit;
    }

    // ── SAVE TO DB ──
    $me = $db->real_escape_string($message);
    $re = $db->real_escape_string($reply);
    @$db->query("INSERT INTO chatbot_messages (session_id,role,content) VALUES ($sid,'user','$me')");
    @$db->query("INSERT INTO chatbot_messages (session_id,role,content) VALUES ($sid,'assistant','$re')");

    $new_msg_count_r = @$db->query("SELECT COUNT(*) c FROM chatbot_messages WHERE session_id=$sid");
    $new_msg_count   = (int)($new_msg_count_r ? $new_msg_count_r->fetch_assoc()['c'] : 0);
    $te = $db->real_escape_string(mb_substr($message, 0, 60));
    @$db->query("UPDATE chatbot_sessions SET msg_count=$new_msg_count,updated_at=NOW() WHERE id=$sid");
    if ($new_msg_count <= 2) {
        @$db->query("UPDATE chatbot_sessions SET title='$te' WHERE id=$sid");
    }

    @$db->query("INSERT INTO chatbot_usage (user_id,session_id,msg_count,created_at) VALUES ($uid,$sid,1,NOW())");
    $new_daily_used = cbDailyUsed($db, $uid);
    @logActivity('chatbot message', '', 0, 'session '.$sid);

    echo json_encode([
        'ok'         => true,
        'reply'      => $reply,
        'session_id' => $sid,
        'daily_used' => $new_daily_used,
        'remaining'  => max(0, $daily_limit - $new_daily_used),
    ]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Unknown action']);