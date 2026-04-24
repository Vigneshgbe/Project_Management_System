<?php
/**
 * chatbot_api.php
 * Padak CRM — AI Chatbot Backend
 * Uses Google Gemini 2.5 Flash (FREE tier: 10 RPM, 250 RPD per project)
 * Cost: $0.00 — fully free, completely separate from Places API quota
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

// ── HELPERS ──
function cbGet(mysqli $db, string $k, string $def = ''): string {
    $r = @$db->query("SELECT setting_val FROM chatbot_settings WHERE setting_key='".$db->real_escape_string($k)."' LIMIT 1");
    if (!$r) return $def;
    $row = $r->fetch_assoc();
    return $row ? trim((string)($row['setting_val'] ?? $def)) : $def;
}

function cbTableOk(mysqli $db): bool {
    return @$db->query("SELECT 1 FROM chatbot_settings LIMIT 1") !== false;
}

function cbDailyUsed(mysqli $db, int $uid): int {
    $r = @$db->query("SELECT COALESCE(SUM(msg_count),0) c FROM chatbot_usage WHERE user_id=$uid AND DATE(created_at)=CURDATE()");
    return $r ? (int)($r->fetch_assoc()['c'] ?? 0) : 0;
}

function cbHttp(string $url, string $body, array $headers = []): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
            CURLOPT_USERAGENT => 'PadakCRM/1.0',
        ]);
        $r = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err || $code < 200 || $code >= 300) return null;
        return $r ?: null;
    }
    $ctx = stream_context_create([
        'http' => ['method'=>'POST','content'=>$body,'timeout'=>30,
            'header' => implode("\r\n", array_merge(['Content-Type: application/json'],$headers)),
            'ignore_errors' => true,'user_agent'=>'PadakCRM/1.0'],
        'ssl'  => ['verify_peer'=>false]
    ]);
    $r = @file_get_contents($url, false, $ctx);
    return $r !== false ? $r : null;
}

// ── TABLE GUARD ──
if (!cbTableOk($db)) {
    echo json_encode(['ok'=>false,'error'=>'Run migration_chatbot.sql first. See instructions in the migration file.']);
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
        $db->query("UPDATE chatbot_settings SET setting_val='$ke',updated_at=NOW() WHERE setting_key='gemini_api_key'");
        if (!$db->affected_rows) {
            $db->query("INSERT INTO chatbot_settings (setting_key,setting_val) VALUES ('gemini_api_key','$ke') ON DUPLICATE KEY UPDATE setting_val='$ke'");
        }
    }
    $db->query("UPDATE chatbot_settings SET setting_val=$limit,updated_at=NOW() WHERE setting_key='daily_limit'");
    if (!$db->affected_rows) {
        $db->query("INSERT INTO chatbot_settings (setting_key,setting_val) VALUES ('daily_limit','$limit') ON DUPLICATE KEY UPDATE setting_val=$limit");
    }
    @logActivity('chatbot settings saved','',0,'');
    echo json_encode(['ok'=>true,'message'=>'Settings saved']);
    exit;
}

// ── ACTION: TEST KEY ──
if ($action === 'test_key') {
    $key = trim($_POST['gemini_key'] ?? cbGet($db,'gemini_api_key'));
    if (!$key) { echo json_encode(['ok'=>false,'error'=>'No API key provided']); exit; }

    $url  = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$key";
    $body = json_encode(['contents'=>[['role'=>'user','parts'=>[['text'=>'Reply with exactly: OK']]]]]);
    $raw  = cbHttp($url, $body);

    if (!$raw) { echo json_encode(['ok'=>false,'error'=>'Cannot reach Gemini API. Check your server internet connection.']); exit; }
    $d = json_decode($raw, true);

    if (!empty($d['candidates'][0]['content']['parts'][0]['text'])) {
        $reply = $d['candidates'][0]['content']['parts'][0]['text'];
        echo json_encode(['ok'=>true,'message'=>'✅ Gemini API key works! Response: '.$reply]);
    } elseif (!empty($d['error'])) {
        $code = $d['error']['code'] ?? 0;
        $msg  = $d['error']['message'] ?? 'Unknown error';
        if ($code == 403 || $code == 401) {
            echo json_encode(['ok'=>false,'error'=>"API key rejected (403/401). Make sure:\n1. Key is from aistudio.google.com/apikey\n2. Gemini API is enabled in that project\n3. Key has no IP restrictions blocking your server\n\nError: $msg"]);
        } elseif ($code == 429) {
            echo json_encode(['ok'=>false,'error'=>"Rate limit hit (429). The key works but you've hit free tier limits. Try again in a minute.\n\nError: $msg"]);
        } else {
            echo json_encode(['ok'=>false,'error'=>"API error $code: $msg"]);
        }
    } else {
        echo json_encode(['ok'=>false,'error'=>'Unexpected response: '.substr($raw,0,200)]);
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
    if (!$api_key) { echo json_encode(['ok'=>false,'error'=>'Gemini API key not configured. Ask admin to set it up in chatbot settings.']); exit; }

    // ── QUOTA GUARD ──
    $daily_limit = max(10, (int)cbGet($db, 'daily_limit', '200'));
    $daily_used  = cbDailyUsed($db, $uid);
    if ($daily_used >= $daily_limit) {
        echo json_encode(['ok'=>false,'error'=>"Daily message limit of $daily_limit reached. Resets at midnight Pacific time. (Free tier: 250 req/day, we cap at $daily_limit to stay safe)"]);
        exit;
    }

    // ── GET/CREATE SESSION ──
    if ($sid) {
        // Verify ownership
        $sr = @$db->query("SELECT id FROM chatbot_sessions WHERE id=$sid AND user_id=$uid LIMIT 1");
        if (!$sr || !$sr->num_rows) $sid = 0;
    }
    if (!$sid) {
        // Create new session — title = first 60 chars of first message
        $title = mb_substr($message, 0, 60);
        $te = $db->real_escape_string($title);
        $db->query("INSERT INTO chatbot_sessions (user_id,title,msg_count) VALUES ($uid,'$te',0)");
        $sid = (int)$db->insert_id;
    }

    // ── LOAD HISTORY (last 20 exchanges = 40 messages to keep tokens low) ──
    $hr = @$db->query("SELECT role,content FROM chatbot_messages WHERE session_id=$sid ORDER BY id DESC LIMIT 40");
    $history = [];
    if ($hr) {
        $rows = $hr->fetch_all(MYSQLI_ASSOC);
        foreach (array_reverse($rows) as $row) {
            $history[] = ['role' => $row['role'], 'parts' => [['text' => $row['content']]]];
        }
    }

    // ── BUILD SYSTEM PROMPT ──
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

    // ── BUILD GEMINI REQUEST ──
    // Add user message to history
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

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$api_key";
    $raw = cbHttp($url, $request_body);

    if (!$raw) {
        echo json_encode(['ok'=>false,'error'=>'Cannot reach Gemini API. Check server internet connection.']);
        exit;
    }

    $resp = json_decode($raw, true);

    // Handle errors
    if (!empty($resp['error'])) {
        $code = $resp['error']['code'] ?? 0;
        $msg  = $resp['error']['message'] ?? 'Unknown error';
        if ($code == 429) {
            echo json_encode(['ok'=>false,'error'=>"Gemini rate limit hit (429). Free tier allows 10 requests/minute. Please wait 10 seconds and try again."]);
        } elseif ($code == 403) {
            echo json_encode(['ok'=>false,'error'=>"API key rejected. Please check your Gemini API key in Settings."]);
        } else {
            echo json_encode(['ok'=>false,'error'=>"Gemini API error $code: $msg"]);
        }
        exit;
    }

    // Extract reply
    $reply = $resp['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!$reply) {
        // Safety blocked?
        $finish = $resp['candidates'][0]['finishReason'] ?? 'UNKNOWN';
        if ($finish === 'SAFETY') {
            echo json_encode(['ok'=>false,'error'=>'Response blocked by safety filters. Please rephrase your message.']);
        } else {
            echo json_encode(['ok'=>false,'error'=>'No response from AI (reason: '.$finish.'). Please try again.']);
        }
        exit;
    }

    // ── SAVE MESSAGES TO DB ──
    $me = $db->real_escape_string($message);
    $re = $db->real_escape_string($reply);
    @$db->query("INSERT INTO chatbot_messages (session_id,role,content) VALUES ($sid,'user','$me')");
    @$db->query("INSERT INTO chatbot_messages (session_id,role,content) VALUES ($sid,'assistant','$re')");

    // ── UPDATE SESSION ──
    $new_msg_count_r = @$db->query("SELECT COUNT(*) c FROM chatbot_messages WHERE session_id=$sid");
    $new_msg_count   = (int)($new_msg_count_r ? $new_msg_count_r->fetch_assoc()['c'] : 0);
    $te = $db->real_escape_string(mb_substr($message, 0, 60));
    @$db->query("UPDATE chatbot_sessions SET msg_count=$new_msg_count,updated_at=NOW() WHERE id=$sid");
    // Update title if only 2 messages (first exchange)
    if ($new_msg_count <= 2) {
        @$db->query("UPDATE chatbot_sessions SET title='$te' WHERE id=$sid");
    }

    // ── LOG DAILY USAGE ──
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