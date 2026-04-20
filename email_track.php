<?php
/**
 * email_track.php — Open tracking pixel
 * Logs email opens via 1x1 transparent GIF
 */
require_once 'config.php';
$token = trim($_GET['t'] ?? '');
if ($token) {
    $db    = getCRMDB();
    $token = $db->real_escape_string($token);
    $db->query("UPDATE email_log SET opened_count=opened_count+1, opened_at=COALESCE(opened_at,NOW()) WHERE tracking_token='$token'");
}
// Return 1x1 transparent GIF
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');