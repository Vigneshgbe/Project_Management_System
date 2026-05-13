<?php
/**
 * notify_helper.php — Drop into config.php via require_once
 * Provides: notify(), notifyMany(), notifyAssignees()
 *
 * Usage:
 *   notify($db, $user_id, 'task_assigned', 'task', $task_id,
 *          'Task Assigned: Fix login bug',
 *          'You have been assigned a new task.',
 *          'tasks.php?edit='.$task_id);
 */

if (!function_exists('notify')) {

/**
 * Insert one notification for one user.
 * Skips silently if user_id is 0 or same as actor (no self-notifications).
 */
function notify(mysqli $db, int $user_id, string $type, string $entity_type,
                int $entity_id, string $title, string $body='', string $link='',
                int $actor_id=0): void
{
    if ($user_id <= 0) return;
    if ($actor_id > 0 && $user_id === $actor_id) return; // no self-notification

    // Deduplicate: don't re-notify the same unread event within 5 minutes
    $esc_type   = $db->real_escape_string($type);
    $esc_entity = $db->real_escape_string($entity_type);
    $esc_title  = $db->real_escape_string($title);
    $esc_body   = $db->real_escape_string($body);
    $esc_link   = $db->real_escape_string($link);

    $dupe = $db->query("
        SELECT id FROM notifications
        WHERE user_id=$user_id
          AND type='$esc_type'
          AND entity_id=$entity_id
          AND is_read=0
          AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        LIMIT 1
    ")->fetch_assoc();
    if ($dupe) return;

    $db->query("
        INSERT INTO notifications (user_id, type, entity_type, entity_id, title, body, link, is_read, created_at)
        VALUES ($user_id, '$esc_type', '$esc_entity', $entity_id,
                '$esc_title', '$esc_body', '$esc_link', 0, NOW())
    ");
}

/**
 * Notify multiple users at once.
 * $user_ids — array of int user IDs
 */
function notifyMany(mysqli $db, array $user_ids, string $type, string $entity_type,
                    int $entity_id, string $title, string $body='', string $link='',
                    int $actor_id=0): void
{
    foreach (array_unique(array_filter(array_map('intval', $user_ids))) as $uid) {
        notify($db, $uid, $type, $entity_type, $entity_id, $title, $body, $link, $actor_id);
    }
}

/**
 * Fetch all user_ids attending/assigned to an entity, notify them all.
 */
function notifyAttendees(mysqli $db, string $attendee_table, string $fk_col,
                         int $entity_id, string $type, string $entity_type,
                         string $title, string $body='', string $link='',
                         int $actor_id=0): void
{
    $rows = $db->query("SELECT user_id FROM `$attendee_table` WHERE `$fk_col`=$entity_id")
               ->fetch_all(MYSQLI_ASSOC);
    $ids = array_column($rows, 'user_id');
    notifyMany($db, $ids, $type, $entity_type, $entity_id, $title, $body, $link, $actor_id);
}

} // end if !function_exists