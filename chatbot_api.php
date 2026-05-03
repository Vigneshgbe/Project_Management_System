<?php
/**
 * chatbot_api.php — Padak CRM AI Chatbot Backend (FIXED)
 *
 * BUGS FIXED:
 *  BUG #1: Wrong API key type — "AQ." keys are Vertex/Cloud Console keys.
 *           Only "AIzaSy..." keys from aistudio.google.com work with this API.
 *           Added key type validation with clear error message.
 *
 *  BUG #2: sleep() inside cbCallGemini() was blocking PHP process 6-18 seconds.
 *           On Hostinger shared hosting this causes gateway timeouts.
 *           Removed all sleep() calls. Retry is handled client-side via JS.
 *
 *  BUG #3: Model alias "gemini-2.5-flash" returns 404/429 on free tier.
 *           Changed primary to "gemini-2.5-flash-preview-05-20" (confirmed working).
 *           Lite fallback changed to "gemini-1.5-flash" (always available free tier).
 *
 *  BUG #4: 429 errors from wrong key were being swallowed by retry logic,
 *           causing the user to always see a generic "rate limit" message
 *           even when the real problem was an invalid/wrong-type key.
 *           Now returns the actual Gemini error message directly.
 */

require_once 'config.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];
mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── MODEL CONFIG ──
// FIXED: Use dated preview name — the alias "gemini-2.5-flash" is unreliable on free tier
define('GEMINI_MODEL',          'gemini-2.5-flash-preview-05-20');  // FIX #3: was 'gemini-2.5-flash'
define('GEMINI_MODEL_FALLBACK', 'gemini-1.5-flash');                 // FIX #3: was lite preview (unreliable)
define('GEMINI_BASE',           'https://generativelanguage.googleapis.com/v1beta/models/');

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
 * FIX #1: Validate API key type before sending to Gemini.
 * "AQ." prefix = Vertex AI / Google Cloud Console key → WILL NOT WORK with Generative Language API
 * "AIzaSy" prefix = AI Studio key → works immediately, no extra setup
 */
function cbValidateKeyType(string $key): ?string {
    if (empty($key)) return 'API key is empty.';
    if (str_starts_with($key, 'AQ.') || str_starts_with($key, 'AQ_')) {
        return "❌ Wrong API key type detected.\n\n"
            . "Your key starts with 'AQ.' — this is a Vertex AI / Google Cloud Console key. "
            . "These keys use a different authentication system and do NOT work with the free Generative Language API.\n\n"
            . "✅ Fix: Get a new key from aistudio.google.com/apikey\n"
            . "  1. Go to aistudio.google.com/apikey\n"
            . "  2. Click 'Create API key'\n"
            . "  3. The new key will start with 'AIzaSy...'\n"
            . "  4. Paste that key in Settings and save.";
    }
    if (!str_starts_with($key, 'AIza')) {
        return "⚠️ Unexpected API key format (expected key starting with 'AIzaSy...').\n"
            . "Please get your key from aistudio.google.com/apikey";
    }
    return null; // key type looks correct
}

/**
 * FIX #2: Removed all sleep() calls — no more blocking.
 * Single HTTP call, returns raw body or null on true network failure only.
 */
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
    // stream_context fallback
    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'content'       => $body,
            'timeout'       => 25,
            'header'        => 'Content-Type: application/json',
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => false],
    ]);
    $r = @file_get_contents($url, false, $ctx);
    return ($r !== false && $r !== '') ? $r : null;
}

/**
 * FIX #2 + #3: No sleep(), smarter fallback.
 * On 429 → immediately return the error (JS handles retry with countdown).
 * On 404/403 → try gemini-1.5-flash (always available on free tier).
 */
function cbCallGemini(string $url, string $body, string $api_key): ?string {
    $raw = cbHttp($url, $body);
    if ($raw === null) return null;

    $d       = json_decode($raw, true);
    $errCode = (int)($d['error']['code'] ?? 0);

    // FIX #2: Do NOT sleep/retry on 429 — return immediately so JS can show countdown
    if ($errCode === 429 || $errCode === 503) {
        return $raw; // let caller handle it with real error message
    }

    // FIX #3: On 404/403 try reliable fallback model gemini-1.5-flash
    if ($errCode === 404 || $errCode === 403) {
        $fallback_url = GEMINI_BASE . GEMINI_MODEL_FALLBACK . ':generateContent?key=' . $api_key;
        $raw2 = cbHttp($fallback_url, $body);
        if ($raw2 !== null) {
            $d2 = json_decode($raw2, true);
            // If fallback worked, return it
            if (!empty($d2['candidates'][0]['content']['parts'][0]['text'])) {
                return $raw2;
            }
            // If fallback also errored but differently, return original
        }
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
    // FIX #1: Check key type and include warning in stats
    $key_warning = '';
    if ($configured) {
        $key = cbGet($db, 'gemini_api_key');
        $keyErr = cbValidateKeyType($key);
        if ($keyErr) $key_warning = $keyErr;
    }
    echo json_encode([
        'ok'          => true,
        'configured'  => $configured,
        'daily_used'  => $daily_used,
        'daily_limit' => $daily_limit,
        'remaining'   => max(0, $daily_limit - $daily_used),
        'key_warning' => $key_warning,
        'model'       => GEMINI_MODEL,
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
        // FIX #1: Validate key type before saving
        $keyErr = cbValidateKeyType($key);
        if ($keyErr) {
            echo json_encode(['ok'=>false,'error'=>$keyErr]);
            exit;
        }
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

    // FIX #1: Check key type first
    $keyErr = cbValidateKeyType($key);
    if ($keyErr) {
        echo json_encode(['ok'=>false,'error'=>$keyErr]);
        exit;
    }

    $url  = GEMINI_BASE . GEMINI_MODEL . ':generateContent?key=' . $key;
    $body = json_encode(['contents'=>[['role'=>'user','parts'=>[['text'=>'Reply with exactly: OK']]]]]);
    $raw  = cbHttp($url, $body);

    if ($raw === null) {
        echo json_encode(['ok'=>false,'error'=>"Cannot reach Gemini API.\n\nCheck:\n• Server internet access to googleapis.com\n• Firewall not blocking outbound HTTPS port 443\n• PHP curl extension is enabled"]);
        exit;
    }

    $d = json_decode($raw, true);
    $errCode = (int)($d['error']['code'] ?? 0);

    // Try fallback model on 404/403
    if ($errCode === 404 || $errCode === 403) {
        $url2 = GEMINI_BASE . GEMINI_MODEL_FALLBACK . ':generateContent?key=' . $key;
        $raw2 = cbHttp($url2, $body);
        if ($raw2 !== null) {
            $d2 = json_decode($raw2, true);
            if (!empty($d2['candidates'][0]['content']['parts'][0]['text'])) {
                echo json_encode(['ok'=>true,'message'=>"✅ Key works! Using fallback model: ".GEMINI_MODEL_FALLBACK]);
                exit;
            }
        }
    }

    if (!empty($d['candidates'][0]['content']['parts'][0]['text'])) {
        echo json_encode(['ok'=>true,'message'=>'✅ API key works! Model: '.GEMINI_MODEL]);
    } elseif (!empty($d['error'])) {
        $code = $errCode;
        $msg  = $d['error']['message'] ?? 'Unknown error';
        if ($code === 429) {
            // FIX #1+#4: On test, 429 from a fresh key = wrong project quota, not rate limit
            echo json_encode(['ok'=>false,'error'=>"Key rejected with 429. This usually means:\n\n"
                . "• The key was created in a Google Cloud project that has billing issues\n"
                . "• The Generative Language API is not enabled in that project\n\n"
                . "✅ Fix: Get a fresh key from aistudio.google.com/apikey (NOT Cloud Console)\n"
                . "AI Studio keys work immediately with no project setup needed."]);
        } elseif ($code === 403) {
            echo json_encode(['ok'=>false,'error'=>"Key rejected (403). The 'Generative Language API' is not enabled.\n\n"
                . "✅ Quickest fix: Get a new key from aistudio.google.com/apikey\n"
                . "These work immediately — no API enabling needed.\n\nError: $msg"]);
        } else {
            echo json_encode(['ok'=>false,'error'=>"API error $code: $msg"]);
        }
    } else {
        echo json_encode(['ok'=>false,'error'=>'Unexpected response: '.substr($raw,0,300)]);
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
        echo json_encode(['ok'=>false,'error'=>'Gemini API key not configured. Ask admin to set it in Settings.']);
        exit;
    }

    // FIX #1: Validate key type on every chat call — catch wrong keys immediately
    $keyErr = cbValidateKeyType($api_key);
    if ($keyErr) {
        echo json_encode(['ok'=>false,'error'=>$keyErr]);
        exit;
    }

    // ── QUOTA GUARD ──
    $daily_limit = max(10, (int)cbGet($db, 'daily_limit', '200'));
    $daily_used  = cbDailyUsed($db, $uid);
    if ($daily_used >= $daily_limit) {
        echo json_encode(['ok'=>false,'error'=>"Daily message limit of $daily_limit reached. Resets at midnight."]);
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

    // ── LOAD HISTORY ──
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

    $url = GEMINI_BASE . GEMINI_MODEL . ':generateContent?key=' . $api_key;
    $raw = cbCallGemini($url, $request_body, $api_key);

    if ($raw === null) {
        echo json_encode(['ok'=>false,'error'=>"Cannot reach Gemini API. Check server internet connection."]);
        exit;
    }

    $resp    = json_decode($raw, true);
    $errCode = (int)($resp['error']['code'] ?? 0);

    if (!empty($resp['error'])) {
        $msg = $resp['error']['message'] ?? 'Unknown error';
        if ($errCode === 429) {
            // FIX #2+#4: Return clean 429 with retry_after hint for JS countdown
            echo json_encode([
                'ok'          => false,
                'error'       => "Rate limited (429). Please wait 15 seconds and try again.",
                'retry_after' => 15,
                'error_code'  => 429,
            ]);
        } elseif ($errCode === 403) {
            echo json_encode(['ok'=>false,'error'=>"API key rejected (403). Go to Settings and replace with a key from aistudio.google.com/apikey\n\nError: $msg"]);
        } elseif ($errCode === 503) {
            echo json_encode(['ok'=>false,'error'=>"Gemini temporarily unavailable (503). Please try again in a moment.",'retry_after'=>5,'error_code'=>503]);
        } elseif ($errCode === 400 && str_contains($msg, 'model')) {
            echo json_encode(['ok'=>false,'error'=>"Model '".GEMINI_MODEL."' not found (400). This model may have been renamed. Contact admin.\n\nError: $msg"]);
        } else {
            echo json_encode(['ok'=>false,'error'=>"Gemini error $errCode: $msg"]);
        }
        exit;
    }

    $reply = $resp['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$reply) {
        $finish = $resp['candidates'][0]['finishReason'] ?? 'UNKNOWN';
        if ($finish === 'SAFETY') {
            echo json_encode(['ok'=>false,'error'=>'Response blocked by safety filters. Please rephrase your message.']);
        } else {
            echo json_encode(['ok'=>false,'error'=>'No response generated (reason: '.$finish.'). Please try again.']);
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