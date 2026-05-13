<?php
/**
 * email_track.php — Open tracking pixel
 */
require_once 'config.php';
$token = trim($_GET['t'] ?? '');
if ($token && preg_match('/^[a-f0-9]{32}$/', $token)) {
    $db    = getCRMDB();
    $token = $db->real_escape_string($token);
    $db->query("UPDATE email_log 
                SET opened_count = opened_count + 1,
                    opened_at    = COALESCE(opened_at, NOW()),
                    status       = IF(status='sent','sent',status)
                WHERE tracking_token = '$token'");
}
// Return 1x1 transparent GIF — no caching so every open is logged
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
exit;