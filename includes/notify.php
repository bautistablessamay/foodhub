<?php
// Email helpers + OTP delivery. Sender settings (GMAIL_USER / GMAIL_APP_PASS) are in includes/config.php.
// The SENDER is a Gmail account; the RECIPIENT can be any real email (Gmail, Yahoo, Outlook, school/work...).

// Returns a lower-cased email address, or null if it is invalid or its domain cannot receive mail.
function normalize_email($raw) {
    $m = strtolower(trim((string)$raw));
    if (strlen($m) > 120 || !filter_var($m, FILTER_VALIDATE_EMAIL)) return null;
    $domain = substr(strrchr($m, '@'), 1);
    if (strpos($domain, '.') === false) return null;
    // Real-domain check: the domain must publish mail servers (MX) or at least an address record.
    if (function_exists('checkdnsrr') && !in_array($domain, ['gmail.com', 'googlemail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com'], true)) {
        if (!@checkdnsrr($domain . '.', 'MX') && !@checkdnsrr($domain . '.', 'A')) return null;
    }
    return $m;
}

function mask_email($v) {
    [$a, $b] = explode('@', $v, 2);
    return substr($a, 0, 2) . str_repeat('•', max(1, strlen($a) - 2)) . '@' . $b;
}

// Emails the 6-digit code. $purpose = 'password reset' or 'registration'. Returns true only if Gmail accepted the message.
function deliver_otp($to, $otp, $purpose = 'password reset') {
    $text = "Your Abia’s Eatery $purpose code is $otp. It expires in 10 minutes. Do not share it with anyone.";
    return send_gmail($to, "Your Abia’s Eatery $purpose code", $text);
}

// ---- Gmail via SMTP (smtp.gmail.com:587, STARTTLS, App Password) --------------------
function send_gmail($to, $subject, $body) {
    if (GMAIL_USER === '' || GMAIL_APP_PASS === '') { error_log('Abia’s Eatery: GMAIL_USER / GMAIL_APP_PASS are not set in includes/config.php'); return false; }
    $fp = @stream_socket_client('tcp://smtp.gmail.com:587', $en, $es, 15);
    if (!$fp) { error_log("Abia’s Eatery: cannot reach smtp.gmail.com:587 ($es)"); return false; }
    stream_set_timeout($fp, 15);
    $last = '';
    $read = function () use ($fp, &$last) { $r = ''; while (($l = fgets($fp, 515)) !== false) { $r .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; } return $last = $r; };
    $cmd = function ($c, $ok) use ($fp, $read) { if ($c !== null) fwrite($fp, $c . "\r\n"); $r = $read(); return in_array(substr($r, 0, 3), (array)$ok, true); };
    $pass = str_replace(' ', '', GMAIL_APP_PASS);
    $msg  = 'From: =?UTF-8?B?' . base64_encode('Abia’s Eatery') . '?= <' . GMAIL_USER . ">\r\n"
          . "To: <$to>\r\n"
          . 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n"
          . 'Date: ' . date('r') . "\r\n"
          . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode($body));
    $ok = $cmd(null, '220') && $cmd('EHLO localhost', '250') && $cmd('STARTTLS', '220')
        && @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)
        && $cmd('EHLO localhost', '250') && $cmd('AUTH LOGIN', '334')
        && $cmd(base64_encode(GMAIL_USER), '334') && $cmd(base64_encode($pass), '235')
        && $cmd('MAIL FROM:<' . GMAIL_USER . '>', '250') && $cmd("RCPT TO:<$to>", ['250', '251'])
        && $cmd('DATA', '354');
    if ($ok) { fwrite($fp, $msg . "\r\n.\r\n"); $ok = $cmd(null, '250'); }
    if (!$ok) error_log('Abia’s Eatery: Gmail SMTP failed: ' . trim($last));
    @fwrite($fp, "QUIT\r\n"); @fclose($fp);
    return $ok;
}
