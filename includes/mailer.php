<?php
function smtp_config(): array
{
    $c = CONFIG['smtp'] ?? [];
    // Admin → Settings values are used when no environment variable is set
    foreach (['host', 'port', 'user', 'pass', 'secure', 'from'] as $k) {
        if (empty($c[$k]) && setting('smtp_' . $k) !== '') $c[$k] = setting('smtp_' . $k);
    }
    $c['port'] = (int) ($c['port'] ?: 587);
    $c['secure'] = strtolower((string) ($c['secure'] ?: 'tls'));
    return $c;
}

function send_mail(string $to, string $subject, string $innerHtml): bool
{
    if (!valid_email($to)) return false;
    $smtp = smtp_config();
    $host = preg_replace('~^www\.~', '', (string) parse_url(site_origin(), PHP_URL_HOST)) ?: 'localhost';
    $from = valid_email((string) ($smtp['from'] ?? '')) ? $smtp['from'] : (valid_email((string) ($smtp['user'] ?? '')) ? $smtp['user'] : 'no-reply@' . $host);
    $name = setting('business_name', 'Mojhometouch NIG LTD');
    $reply = valid_email(setting('email')) ? setting('email') : null;
    $html = mail_wrap($subject, $innerHtml);
    $ok = false;
    $how = 'mail()';
    $err = '';
    if (!empty(CONFIG['mail_enabled'])) {
        if (!empty($smtp['host'])) {
            $how = 'SMTP';
            try {
                smtp_send($smtp, $from, $name, $to, $subject, $html, $reply);
                $ok = true;
            } catch (Throwable $e) {
                $err = $e->getMessage();
                error_log('SMTP send failed: ' . $err);
            }
        } else {
            $headers = ['MIME-Version: 1.0', 'Content-Type: text/html; charset=UTF-8', 'From: ' . mb_encode_mimeheader($name) . ' <' . $from . '>'];
            if ($reply) $headers[] = 'Reply-To: ' . $reply;
            $ok = @mail($to, mb_encode_mimeheader($subject), $html, implode("\r\n", $headers));
        }
    }
    @file_put_contents(ROOT . '/storage/logs/mail.log', '[' . now() . '] ' . ($ok ? 'SENT    ' : 'NOT SENT') . " via $how to $to — $subject" . ($err ? " ($err)" : '') . PHP_EOL, FILE_APPEND);
    return $ok;
}

/** Minimal SMTP client (AUTH LOGIN, STARTTLS / SSL). Throws on failure. */
function smtp_send(array $c, string $from, string $fromName, string $to, string $subject, string $html, ?string $replyTo = null): void
{
    $secure = $c['secure'];
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $c['host'] . ':' . $c['port'];
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $c['host']]]);
    $fp = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) throw new RuntimeException("Cannot connect to {$c['host']}:{$c['port']} ($errstr)");
    stream_set_timeout($fp, 20);
    $read = function () use ($fp): string {
        $out = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $out .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return $out;
    };
    $cmd = function (?string $line, array $expect) use ($fp, $read): string {
        if ($line !== null) fwrite($fp, $line . "\r\n");
        $r = $read();
        $code = (int) substr($r, 0, 3);
        if (!in_array($code, $expect, true)) throw new RuntimeException('SMTP error: ' . trim($r ?: 'no response'));
        return $r;
    };
    $helo = preg_replace('~[^A-Za-z0-9.\-]~', '', (string) parse_url(site_origin(), PHP_URL_HOST)) ?: 'localhost';
    try {
        $cmd(null, [220]);
        $ehlo = $cmd("EHLO $helo", [250]);
        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) throw new RuntimeException('STARTTLS failed');
            $ehlo = $cmd("EHLO $helo", [250]);
        }
        if (!empty($c['user'])) {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode((string) $c['user']), [334]);
            $cmd(base64_encode((string) $c['pass']), [235]);
        }
        $cmd('MAIL FROM:<' . $from . '>', [250]);
        $cmd('RCPT TO:<' . $to . '>', [250, 251]);
        $cmd('DATA', [354]);
        $hdr = [
            'Date: ' . date('r'),
            'From: ' . mb_encode_mimeheader($fromName) . ' <' . $from . '>',
            'To: <' . $to . '>',
            'Subject: ' . mb_encode_mimeheader($subject),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $helo . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        if ($replyTo) $hdr[] = 'Reply-To: ' . $replyTo;
        $body = implode("\r\n", $hdr) . "\r\n\r\n" . rtrim(chunk_split(base64_encode($html), 76, "\r\n")) . "\r\n.";
        $cmd($body, [250]);
        $cmd('QUIT', [221, 250]);
    } finally {
        fclose($fp);
    }
}

function mail_wrap(string $title, string $inner): string
{
    $logo = abs_url('assets/img/logo.png');
    $biz = e(setting('business_name', 'Mojhometouch NIG LTD'));
    $phone = e(setting('phone'));
    return '<!doctype html><html><body style="margin:0;background:#f3ede2;font-family:Arial,Helvetica,sans-serif;color:#2b2b2b">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3ede2;padding:24px 12px"><tr><td align="center">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden">'
        . '<tr><td style="background:#2b2b2b;padding:22px 28px"><img src="' . $logo . '" alt="" height="44" style="background:#fff;border-radius:8px;padding:4px;vertical-align:middle"> '
        . '<span style="color:#fff;font-size:18px;font-weight:bold;letter-spacing:1px;vertical-align:middle;margin-left:10px">MOJHOMETOUCH</span></td></tr>'
        . '<tr><td style="height:4px;background:#fecd2a"></td></tr>'
        . '<tr><td style="padding:28px">' . $inner . '</td></tr>'
        . '<tr><td style="background:#faf7f1;padding:18px 28px;font-size:12px;color:#6b655c">' . $biz . ' · Classic Paints &amp; Interior Finishing · ' . $phone . '<br><em style="color:#b8892f">Perfection Only.</em></td></tr>'
        . '</table></td></tr></table></body></html>';
}

function mail_order_table(array $o): string
{
    $rows = '';
    foreach (order_items((int) $o['id']) as $it) {
        $meta = trim(($it['variant_name'] ?? '') . ($it['option_value'] ? ' · ' . ($it['option_label'] ?: 'Option') . ': ' . $it['option_value'] : ''), ' ·');
        $rows .= '<tr><td style="padding:8px 0;border-bottom:1px solid #eee">' . e($it['name']) . ($meta ? '<br><small style="color:#6b655c">' . e($meta) . '</small>' : '')
            . '</td><td align="center" style="border-bottom:1px solid #eee">' . (int) $it['qty'] . '</td><td align="right" style="border-bottom:1px solid #eee">' . money($it['line_total']) . '</td></tr>';
    }
    return '<table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px">'
        . '<tr><th align="left" style="font-size:12px;color:#6b655c;padding-bottom:6px">Item</th><th style="font-size:12px;color:#6b655c">Qty</th><th align="right" style="font-size:12px;color:#6b655c">Total</th></tr>'
        . $rows
        . '<tr><td colspan="2" style="padding-top:10px">Subtotal</td><td align="right" style="padding-top:10px">' . money($o['subtotal']) . '</td></tr>'
        . '<tr><td colspan="2">Delivery (' . e($o['zone_name']) . ')</td><td align="right">' . money($o['delivery_fee']) . '</td></tr>'
        . '<tr><td colspan="2" style="font-weight:bold;padding-top:6px">Total</td><td align="right" style="font-weight:bold;padding-top:6px">' . money($o['total']) . '</td></tr></table>';
}

function mail_button(string $href, string $label): string
{
    return '<p style="margin:24px 0"><a href="' . e($href) . '" style="background:#fecd2a;color:#1a1a1a;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:30px;display:inline-block">' . e($label) . '</a></p>';
}

function notify_new_order(array $o): void
{
    $link = order_url($o, true);
    if (!empty($o['email'])) {
        send_mail($o['email'], 'Order ' . $o['ref'] . ' received — Mojhometouch',
            '<h2 style="margin:0 0 8px">Thank you, ' . e($o['customer_name']) . '!</h2>'
            . '<p>We have received your order <strong>' . e($o['ref']) . '</strong>. Our team will confirm it shortly.</p>'
            . '<p>Payment method: <strong>' . e(payment_method_label($o['payment_method'])) . '</strong></p>'
            . mail_order_table($o) . mail_button($link, 'View / track your order'));
    }
    $admin = setting('notify_email') ?: setting('email');
    if ($admin) {
        send_mail($admin, 'New order ' . $o['ref'] . ' — ' . money($o['total']),
            '<h2 style="margin:0 0 8px">New order ' . e($o['ref']) . '</h2>'
            . '<p><strong>' . e($o['customer_name']) . '</strong> · ' . e($o['phone']) . ($o['email'] ? ' · ' . e($o['email']) : '') . '<br>'
            . e($o['zone_name']) . ($o['address'] ? ' — ' . e($o['address']) . ', ' . e($o['city']) . ', ' . e($o['state']) : '') . '</p>'
            . '<p>Payment: ' . e(payment_method_label($o['payment_method'])) . '</p>'
            . mail_order_table($o) . mail_button(abs_url('admin/order.php?id=' . $o['id']), 'Open in admin'));
    }
}

function notify_status(?array $o, string $msg): void
{
    if (!$o || empty($o['email'])) return;
    send_mail($o['email'], 'Order ' . $o['ref'] . ': ' . (ORDER_STATUSES[$o['status']] ?? 'Update'),
        '<h2 style="margin:0 0 8px">Order update</h2><p>Hello ' . e($o['customer_name']) . ',</p><p>' . e($msg) . '</p>'
        . '<p>Order: <strong>' . e($o['ref']) . '</strong><br>Status: <strong>' . e(ORDER_STATUSES[$o['status']] ?? $o['status']) . '</strong><br>Payment: <strong>' . e(PAYMENT_STATUSES[$o['payment_status']] ?? '') . '</strong></p>'
        . mail_button(order_url($o, true), 'View your order'));
}

function notify_enquiry(array $q): void
{
    $admin = setting('notify_email') ?: setting('email');
    if (!$admin) return;
    $rows = '';
    foreach (['Name' => 'name', 'Phone' => 'phone', 'Email' => 'email', 'Service' => 'service', 'Property' => 'property', 'Location' => 'location', 'Start' => 'start_when', 'Details' => 'details'] as $label => $k) {
        if (!empty($q[$k])) $rows .= '<tr><td style="padding:6px 12px 6px 0;color:#6b655c;vertical-align:top">' . $label . '</td><td style="padding:6px 0">' . nl2br(e($q[$k])) . '</td></tr>';
    }
    send_mail($admin, 'New ' . ($q['type'] === 'quote' ? 'quote request' : 'enquiry') . ' from ' . $q['name'],
        '<h2 style="margin:0 0 12px">New ' . e($q['type']) . ' request</h2><table style="font-size:14px">' . $rows . '</table>'
        . mail_button(abs_url('admin/enquiries.php'), 'Open in admin'));
}
