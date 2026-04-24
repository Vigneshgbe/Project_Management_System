<?php
/**
 * whatsapp_webhook.php — Twilio WhatsApp incoming message webhook
 * Set this URL in Twilio Console → Messaging → WhatsApp Sandbox → "When a message comes in"
 * URL: https://yourdomain.com/Project_Management/whatsapp_webhook.php
 */
require_once 'config.php';
$db = getCRMDB();
mysqli_report(MYSQLI_REPORT_OFF);

// Twilio sends POST to this endpoint
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

$from   = $_POST['From']      ?? '';  // whatsapp:+94771234567
$body   = $_POST['Body']      ?? '';
$media  = $_POST['MediaUrl0'] ?? '';
$sid    = $_POST['MessageSid'] ?? '';

if (!$from || !$body) {
    http_response_code(200);
    die('<?xml version="1.0" encoding="UTF-8"?><Response></Response>');
}

// Normalise phone: strip "whatsapp:" prefix
$phone = str_replace('whatsapp:', '', $from);

// Try to match contact or lead
$contact_id = null;
$lead_id    = null;
$cname      = $phone;

$clean = preg_replace('/[^0-9+]/', '', $phone);
$c = @$db->query("SELECT id, name FROM contacts WHERE REPLACE(REPLACE(phone,' ',''),'-','') LIKE '%".substr($clean,-9)."%' LIMIT 1");
if ($c && $row = $c->fetch_assoc()) {
    $contact_id = (int)$row['id'];
    $cname      = $row['name'];
} else {
    $l = @$db->query("SELECT id, name FROM leads WHERE REPLACE(REPLACE(phone,' ',''),'-','') LIKE '%".substr($clean,-9)."%' LIMIT 1");
    if ($l && $lrow = $l->fetch_assoc()) {
        $lead_id = (int)$lrow['id'];
        $cname   = $lrow['name'];
    }
}

// Save incoming message
$phone_e   = $db->real_escape_string($phone);
$body_e    = $db->real_escape_string($body);
$media_e   = $db->real_escape_string($media);
$sid_e     = $db->real_escape_string($sid);
$cname_e   = $db->real_escape_string($cname);
$cid_sql   = $contact_id ? $contact_id : 'NULL';
$lid_sql   = $lead_id    ? $lead_id    : 'NULL';

$db->query("INSERT INTO whatsapp_messages
    (contact_id, lead_id, direction, phone, contact_name, body, media_url, twilio_sid, status)
    VALUES ($cid_sql, $lid_sql, 'in', '$phone_e', '$cname_e', '$body_e', '$media_e', '$sid_e', 'received')");

// Push in-app notification to all admins/managers
$admins = @$db->query("SELECT id FROM users WHERE role IN('admin','manager') AND status='active'");
if ($admins) {
    while ($admin = $admins->fetch_assoc()) {
        $aid = (int)$admin['id'];
        $ntitle = $db->real_escape_string("WhatsApp from $cname");
        $nbody  = $db->real_escape_string(mb_substr($body, 0, 100));
        $db->query("INSERT INTO notifications (user_id,type,entity_type,title,body,link)
            VALUES ($aid,'whatsapp','message','$ntitle','$nbody','whatsapp.php')");
    }
}

// Respond to Twilio (empty TwiML = no auto-reply)
header('Content-Type: text/xml');
echo '<?xml version="1.0" encoding="UTF-8"?><Response></Response>';