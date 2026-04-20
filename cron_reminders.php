<?php
/**
 * cron_reminders.php — Padak CRM automated reminder dispatcher
 * Run daily via cron: 0 8 * * * php /path/to/padak-crm/cron_reminders.php
 */
if (php_sapi_name() !== 'cli') { http_response_code(403); exit; }

require_once __DIR__.'/config.php';
require_once __DIR__.'/includes/mailer.php';

$db   = getCRMDB();
$sent = sendDueReminders($db);
echo date('Y-m-d H:i:s')." — Sent $sent due reminders.\n";

// Also process invoice overdue auto-flag
$db->query("UPDATE invoices SET status='overdue' WHERE status='sent' AND due_date < CURDATE() AND due_date IS NOT NULL");

// Check invoices due in 2 days → notify assigned contact manager
$inv_rows = $db->query("
    SELECT i.id, i.invoice_no, i.title, i.total, i.currency, i.due_date, i.contact_id,
           c.name AS client_name, c.email AS client_email,
           u.id AS mgr_id, u.name AS mgr_name, u.email AS mgr_email
    FROM invoices i
    LEFT JOIN contacts c ON c.id=i.contact_id
    JOIN users u ON u.id=i.created_by
    WHERE i.status IN('sent','partial')
      AND i.due_date = DATE_ADD(CURDATE(), INTERVAL 2 DAY)
")->fetch_all(MYSQLI_ASSOC);

foreach ($inv_rows as $inv) {
    if (!$inv['mgr_id']) continue;
    $exists = $db->query("SELECT id FROM notifications WHERE user_id={$inv['mgr_id']} AND type='invoice_due' AND body LIKE '%inv:{$inv['id']}%' AND DATE(created_at)=CURDATE()")->fetch_assoc();
    if ($exists) continue;
    pushNotification([
        'user_id' => $inv['mgr_id'],
        'type'    => 'invoice_due',
        'title'   => "Invoice due in 2 days: {$inv['invoice_no']}",
        'body'    => "Client: {$inv['client_name']} | inv:{$inv['id']}",
        'link'    => 'invoices.php?view='.$inv['id'],
        'vars'    => [
            'invoice_no' => $inv['invoice_no'],
            'amount'     => 'Rs. '.number_format($inv['total'],2),
            'due_date'   => date('M j, Y',strtotime($inv['due_date'])),
        ],
    ], $db);
    echo "Invoice reminder: {$inv['invoice_no']} → {$inv['mgr_name']}\n";
}
echo "Done.\n";