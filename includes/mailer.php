<?php
/**
 * includes/mailer.php — Padak CRM Email Engine
 * Handles: SMTP sending, notification dispatch, template rendering, open tracking
 * No external dependencies — uses PHP streams for SMTP or php mail() fallback
 */
if (!defined('CRM_VERSION')) require_once __DIR__.'/../config.php';

// ══════════════════════════════════════════════════════
// SMTP SEND (pure PHP, no PHPMailer needed)
// ══════════════════════════════════════════════════════

/**
 * Send an email using stored SMTP settings or php mail().
 * Returns ['ok'=>bool, 'message_id'=>string, 'error'=>string]
 */
function crmSendEmail(array $opts, mysqli $db): array {
    $to      = (array)($opts['to']      ?? []);
    $subject = trim($opts['subject']    ?? '(no subject)');
    $html    = $opts['html']            ?? '';
    $text    = $opts['text']            ?? strip_tags($html);
    $cc      = (array)($opts['cc']      ?? []);
    $bcc     = (array)($opts['bcc']     ?? []);
    $token   = $opts['tracking_token']  ?? null;

    if (!$to) return ['ok'=>false,'error'=>'No recipients'];

    // Load SMTP settings from DB
    $smtp = $db->query("SELECT * FROM email_settings WHERE is_default=1 AND is_active=1 LIMIT 1")->fetch_assoc();
    if (!$smtp) $smtp = $db->query("SELECT * FROM email_settings WHERE is_active=1 LIMIT 1")->fetch_assoc();

    // Fallback: read MAIL_* from .env or environment variables if no DB config
    if (!$smtp) {
        $env_host = getenv('MAIL_HOST') ?: ($_ENV['MAIL_HOST'] ?? '');
        $env_user = getenv('MAIL_USERNAME') ?: ($_ENV['MAIL_USERNAME'] ?? '');
        $env_pass = getenv('MAIL_PASSWORD') ?: ($_ENV['MAIL_PASSWORD'] ?? '');
        $env_port = (int)(getenv('MAIL_PORT') ?: ($_ENV['MAIL_PORT'] ?? 465));
        if ($env_host && $env_user) {
            $smtp = [
                'host'       => $env_host,
                'port'       => $env_port,
                'encryption' => $env_port === 465 ? 'ssl' : 'tls',
                'username'   => $env_user,
                'password'   => $env_pass,
                'from_email' => $env_user,
                'from_name'  => defined('SITE_NAME') ? SITE_NAME : 'Padak CRM',
            ];
        }
    }

    // Inject open tracking pixel into HTML
    if ($token && $html) {
        $pixel = '<img src="'.BASE_URL.'/email_track.php?t='.urlencode($token).'" width="1" height="1" style="display:none" alt="">';
        $html .= $pixel;
    }

    // Build Message-ID
    $msg_id = '<'.uniqid('crm_',true).'@'.($_SERVER['HTTP_HOST']??'padak.local').'>';

    if ($smtp && $smtp['host']) {
        $result = smtpSend($smtp, $to, $cc, $bcc, $subject, $html, $text, $msg_id);
    } else {
        $result = phpMailFallback($smtp, $to, $cc, $bcc, $subject, $html, $text, $msg_id);
    }

    $result['message_id'] = $msg_id;
    return $result;
}

/**
 * SMTP send using stream_socket_client — supports SSL/TLS, works with Gmail.
 */
function smtpSend(array $smtp, array $to, array $cc, array $bcc, string $subject, string $html, string $text, string $msg_id): array {
    $host       = $smtp['host']       ?? '';
    $port       = (int)($smtp['port'] ?? 587);
    $enc        = $smtp['encryption'] ?? 'tls';
    $user       = $smtp['username']   ?? '';
    $pass       = $smtp['password']   ?? '';
    $from_email = $smtp['from_email'] ?? '';
    $from_name  = $smtp['from_name']  ?? 'Padak CRM';

    if (!$host) return ['ok'=>false,'error'=>'SMTP host not configured'];

    try {
    // SSL context — verify peer for production certs, disable for self-signed
    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'allow_self_signed' => false,
            'cafile'            => defined('OPENSSL_CAFILE') ? OPENSSL_CAFILE : '',
        ]
    ]);

    // For SSL (port 465): connect with ssl:// wrapper directly
    // For TLS (port 587): connect plain, then STARTTLS
    $conn = ($enc === 'ssl') ? "ssl://$host:$port" : "tcp://$host:$port";

    $sock = @stream_socket_client($conn, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $ctx);
    if (!$sock) {
        // Retry without peer verification (useful for some hosts)
        $ctx2 = stream_context_create(['ssl'=>['verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true]]);
        $sock = @stream_socket_client($conn, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $ctx2);
        if (!$sock) return ['ok'=>false,'error'=>"SMTP connection to $host:$port failed: $errstr ($errno)"];
    }
    stream_set_timeout($sock, 30);

    // SMTP read helper — handles multi-line responses (e.g. EHLO 250-)
    $read = function() use ($sock): string {
        $buf = '';
        while (!feof($sock)) {
            $line = fgets($sock, 512);
            if ($line === false) break;
            $buf .= $line;
            // Last line of response: 4th char is space, not dash
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        return $buf;
    };
    $cmd = function(string $c) use ($sock, $read): string {
        fwrite($sock, $c."\r\n");
        return $read();
    };

    $r = $read(); // Server greeting
    if (substr($r,0,3) !== '220') { fclose($sock); return ['ok'=>false,'error'=>"Bad greeting: ".trim($r)]; }

    $ehlo = $_SERVER['SERVER_NAME'] ?? gethostname() ?: 'padak.local';
    $r = $cmd("EHLO $ehlo");
    if (substr($r,0,3) !== '250') { fclose($sock); return ['ok'=>false,'error'=>"EHLO failed: ".trim($r)]; }

    // STARTTLS upgrade for port 587
    if ($enc === 'tls') {
        $r = $cmd("STARTTLS");
        if (substr($r,0,3) !== '220') { fclose($sock); return ['ok'=>false,'error'=>"STARTTLS failed: ".trim($r)]; }
        $ctx3 = stream_context_create(['ssl'=>['verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true]]);
        stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT, $ctx3);
        $r = $cmd("EHLO $ehlo");
        if (substr($r,0,3) !== '250') { fclose($sock); return ['ok'=>false,'error'=>"EHLO after STARTTLS failed: ".trim($r)]; }
    }

    // AUTH LOGIN
    if ($user && $pass) {
        $r = $cmd("AUTH LOGIN");
        if (substr($r,0,3) !== '334') { fclose($sock); return ['ok'=>false,'error'=>"AUTH LOGIN not accepted: ".trim($r)]; }
        $cmd(base64_encode($user));
        $r = $cmd(base64_encode($pass));
        if (substr($r,0,3) !== '235') { fclose($sock); return ['ok'=>false,'error'=>"Authentication failed — check username/password. Gmail requires an App Password."]; }
    }

    // MAIL FROM
    $r = $cmd("MAIL FROM:<$from_email>");
    if (substr($r,0,3) !== '250') { fclose($sock); return ['ok'=>false,'error'=>"MAIL FROM rejected: ".trim($r)]; }

    // RCPT TO
    foreach (array_merge($to, $cc, $bcc) as $addr) {
        $addr = extractEmail($addr);
        $r = $cmd("RCPT TO:<$addr>");
        if (substr($r,0,1) !== '2') { fclose($sock); return ['ok'=>false,'error'=>"Recipient $addr rejected: ".trim($r)]; }
    }

    // DATA
    $r = $cmd("DATA");
    if (substr($r,0,3) !== '354') { fclose($sock); return ['ok'=>false,'error'=>"DATA command rejected: ".trim($r)]; }

    $boundary = 'crm_'.md5(uniqid());
    $headers  = buildMimeHeaders($from_name, $from_email, $to, $cc, $subject, $msg_id, $boundary);
    $body_msg = buildMimeBody($html, $text, $boundary);
    // Dot-stuffing: lines starting with . must be doubled
    $data = $headers."\r\n".$body_msg;
    $data = preg_replace('/^\./m', '..', $data);
    fwrite($sock, $data."\r\n.\r\n");
    $r = $read();

    $cmd("QUIT");
    fclose($sock);

    if (substr($r,0,3) !== '250') return ['ok'=>false,'error'=>"Server rejected message: ".trim($r)];
    return ['ok'=>true,'error'=>''];
    } catch (\Throwable $e) {
        return ['ok'=>false,'error'=>'SMTP error: '.$e->getMessage()];
    }
}

/**
 * Fallback to PHP mail() when no SMTP configured.
 */
function phpMailFallback(?array $smtp, array $to, array $cc, array $bcc, string $subject, string $html, string $text, string $msg_id): array {
    $from_email = $smtp['from_email'] ?? 'noreply@thepadak.com';
    $from_name  = $smtp['from_name']  ?? 'Padak CRM';
    $boundary   = 'crm_'.md5(uniqid());

    $to_str = implode(', ', $to);
    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    if ($cc) $headers .= "Cc: ".implode(', ',$cc)."\r\n";
    if ($bcc) $headers .= "Bcc: ".implode(', ',$bcc)."\r\n";
    $headers .= "Message-ID: $msg_id\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $headers .= "X-Mailer: PadakCRM\r\n";

    $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n$text\r\n";
    $body .= "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n$html\r\n--$boundary--";

    $ok = @mail($to_str, $subject, $body, $headers);
    $err = '';
    if (!$ok) {
        $last = error_get_last();
        $err  = $last['message'] ?? 'mail() failed';
        // Give actionable hint
        if (str_contains($err, 'localhost') || str_contains($err, 'mailserver')) {
            $err .= ' — Configure SMTP in Emails → SMTP Settings to fix this.';
        }
    }
    return ['ok'=>(bool)$ok, 'error'=>$err];
}

// ── MIME helpers ──
function buildMimeHeaders(string $fn, string $fe, array $to, array $cc, string $subj, string $mid, string $bd): string {
    $h  = "From: $fn <$fe>\r\n";
    $h .= "To: ".implode(', ', $to)."\r\n";
    if ($cc) $h .= "Cc: ".implode(', ',$cc)."\r\n";
    $h .= "Subject: =?UTF-8?B?".base64_encode($subj)."?=\r\n";
    $h .= "Message-ID: $mid\r\n";
    $h .= "Date: ".date('r')."\r\n";
    $h .= "MIME-Version: 1.0\r\n";
    $h .= "Content-Type: multipart/alternative; boundary=\"$bd\"\r\n";
    $h .= "X-Mailer: PadakCRM/1.0\r\n";
    return $h;
}
function buildMimeBody(string $html, string $text, string $bd): string {
    $b  = "--$bd\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($text))."\r\n";
    $b .= "--$bd\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($html))."\r\n";
    $b .= "--$bd--";
    return $b;
}
function extractEmail(string $addr): string {
    if (preg_match('/<([^>]+)>/', $addr, $m)) return $m[1];
    return trim($addr);
}

// ══════════════════════════════════════════════════════
// LOG EMAIL TO DB
// ══════════════════════════════════════════════════════
function logEmail(array $opts, string $status, string $error, string $msg_id, mysqli $db): int {
    $dir      = $opts['direction']  ?? 'out';
    $subject  = $opts['subject']    ?? '';
    $from_e   = $opts['from_email'] ?? '';
    $from_n   = $opts['from_name']  ?? '';
    $to       = json_encode((array)($opts['to']  ?? []));
    $cc       = json_encode((array)($opts['cc']  ?? []));
    $bcc      = json_encode((array)($opts['bcc'] ?? []));
    $html     = $opts['html']       ?? '';
    $txt      = $opts['text']       ?? '';
    $token    = $opts['tracking_token'] ?? null;
    $sent_by  = $opts['sent_by']    ?? null;
    $cid      = $opts['contact_id'] ?? null;
    $lid      = $opts['lead_id']    ?? null;
    $invid    = $opts['invoice_id'] ?? null;
    $pid      = $opts['project_id'] ?? null;
    $tid      = $opts['task_id']    ?? null;
    $sent_at  = ($status === 'sent') ? date('Y-m-d H:i:s') : null;

    $stmt = $db->prepare("INSERT INTO email_log
        (direction,subject,from_email,from_name,to_email,cc_email,bcc_email,body_html,body_text,
         status,error_msg,message_id,tracking_token,sent_by,sent_at,contact_id,lead_id,invoice_id,project_id,task_id)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssssssssssssisiiiii",
        $dir,$subject,$from_e,$from_n,$to,$cc,$bcc,$html,$txt,
        $status,$error,$msg_id,$token,$sent_by,$sent_at,
        $cid,$lid,$invid,$pid,$tid);
    $stmt->execute();
    return $db->insert_id;
}

// ══════════════════════════════════════════════════════
// TEMPLATE RENDERER
// ══════════════════════════════════════════════════════
function renderEmailTemplate(string $html, array $vars): string {
    foreach ($vars as $k => $v) {
        $html = str_replace('{{'.$k.'}}', htmlspecialchars((string)$v, ENT_QUOTES), $html);
    }
    return $html;
}

// ══════════════════════════════════════════════════════
// SEND + LOG CONVENIENCE WRAPPER
// ══════════════════════════════════════════════════════
function sendAndLog(array $opts, mysqli $db): array {
    $opts['tracking_token'] = $opts['tracking_token'] ?? bin2hex(random_bytes(16));
    $result = ['ok'=>false,'error'=>'','message_id'=>'','log_id'=>0];
    try {
        $result = crmSendEmail($opts, $db);
    } catch (\Throwable $e) {
        $result['ok']    = false;
        $result['error'] = 'Internal error: '.$e->getMessage();
    }
    // Always log — even on exception
    try {
        $status = $result['ok'] ? 'sent' : 'failed';
        $lid    = logEmail($opts, $status, $result['error']??'', $result['message_id']??'', $db);
        $result['log_id'] = $lid;
    } catch (\Throwable $e) {
        error_log('logEmail failed: '.$e->getMessage());
    }
    return $result;
}

// ══════════════════════════════════════════════════════
// NOTIFICATION ENGINE
// ══════════════════════════════════════════════════════

/**
 * Create in-app notification + optionally send email.
 * Called from tasks.php, leads.php, invoices.php, etc.
 */
function pushNotification(array $opts, mysqli $db): void {
    $user_id = (int)($opts['user_id'] ?? 0);
    $type    = $opts['type']  ?? 'info';
    $title   = $opts['title'] ?? '';
    $body    = $opts['body']  ?? '';
    $link    = $opts['link']  ?? '';
    if (!$user_id || !$title) return;

    // In-app notification
    $stmt = $db->prepare("INSERT INTO notifications (user_id,type,title,body,link) VALUES (?,?,?,?,?)");
    $stmt->bind_param("issss", $user_id, $type, $title, $body, $link);
    $stmt->execute();

    // Email notification — get user's email
    $urow = $db->query("SELECT name,email FROM users WHERE id=$user_id AND status='active'")->fetch_assoc();
    if (!$urow || !$urow['email']) return;

    // Find template for this type
    $tmap = [
        'task_assigned' => 'Task Assigned',
        'task_due'      => 'Task Due Reminder',
        'invoice_sent'  => 'Invoice Sent',
    ];
    $tmpl_name = $tmap[$type] ?? null;
    $html = '';
    if ($tmpl_name) {
        $tmpl = $db->query("SELECT body_html FROM email_templates WHERE name='".$db->real_escape_string($tmpl_name)."' LIMIT 1")->fetch_assoc();
        if ($tmpl) {
            $html = renderEmailTemplate($tmpl['body_html'], array_merge(
                ['name' => $urow['name'], 'link' => BASE_URL.'/'.$link],
                $opts['vars'] ?? []
            ));
        }
    }
    if (!$html) {
        $html = "<div style='font-family:Arial,sans-serif;padding:20px'><h3>$title</h3><p>$body</p><p><a href='".BASE_URL.'/'.$link."'>View in CRM →</a></p></div>";
    }

    sendAndLog([
        'to'         => ["{$urow['name']} <{$urow['email']}>"],
        'subject'    => $title,
        'html'       => $html,
        'text'       => "$title\n\n$body\n\n".BASE_URL.'/'.$link,
        'from_email' => 'noreply@thepadak.com',
        'from_name'  => 'Padak CRM',
        'sent_by'    => null, // system
    ], $db);
}

/**
 * Auto-dispatch: check tasks due today/tomorrow → send reminders.
 * Call from a daily cron: php -r "require 'config.php'; require 'includes/mailer.php'; sendDueReminders(getCRMDB());"
 */
function sendDueReminders(mysqli $db): int {
    $sent = 0;
    $rows = $db->query("
        SELECT t.id, t.title, t.due_date, t.assigned_to,
               u.name AS user_name, u.email AS user_email,
               p.title AS project_title
        FROM tasks t
        JOIN users u ON u.id=t.assigned_to
        LEFT JOIN projects p ON p.id=t.project_id
        WHERE t.status NOT IN('done')
          AND t.due_date IN (CURDATE(), DATE_ADD(CURDATE(),INTERVAL 1 DAY))
          AND t.assigned_to IS NOT NULL
          AND u.status='active'
    ")->fetch_all(MYSQLI_ASSOC);

    foreach ($rows as $t) {
        // Check not already notified today
        $exists = $db->query("SELECT id FROM notifications WHERE user_id={$t['assigned_to']} AND type='task_due' AND body LIKE '%task_id:{$t['id']}%' AND DATE(created_at)=CURDATE()")->fetch_assoc();
        if ($exists) continue;

        pushNotification([
            'user_id' => $t['assigned_to'],
            'type'    => 'task_due',
            'title'   => "Task Due Soon: {$t['title']}",
            'body'    => "Due: ".date('M j, Y',strtotime($t['due_date']))." | task_id:{$t['id']}",
            'link'    => 'tasks.php',
            'vars'    => [
                'task'    => $t['title'],
                'project' => $t['project_title'] ?? 'No Project',
                'due_date'=> date('M j, Y', strtotime($t['due_date'])),
            ],
        ], $db);
        $sent++;
    }
    return $sent;
}