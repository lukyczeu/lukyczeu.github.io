<?php
$admin_email = "lukas.hanus.cz@icloud.com"; 
$smtp_host = "smtp.mail.me.com";
$smtp_port = 587;
$smtp_username = "lukas.hanus.cz@icloud.com";
$smtp_password = "dstq-mzxt-jhqa-ycse";
$smtp_secure = "TLS";
$turnstile_secret = "0x4AAAAAAB2dVCikodlchzylZ9ywcQwCBQw";
$response = array();
$response['success'] = false;
$response['message'] = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Form submission - Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("POST data: " . print_r($_POST, true));
    error_log("Ajax field: " . ($_POST['ajax'] ?? 'not set'));

    $errors = array();

    $email = trim($_POST['email'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';
if (empty($email)) {
        $errors[] = "Email je povinný.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Neplatný formát emailu.";
    } else {
        $email_domain = substr(strrchr($email, "@"), 1);
        if (!$email_domain || !checkdnsrr($email_domain, "MX")) {
            $errors[] = "Emailová adresa neexistuje nebo doména nepřijímá emaily.";
        }
    }
if (empty($title)) {
        $errors[] = "Jméno je povinné.";
    } elseif (strlen($title) < 5 || strlen($title) > 30) {
        $errors[] = "Jméno musí být mezi 5 až 30 znaky.";
    }
if (empty($subject)) {
        $errors[] = "Předmět je povinný.";
    } elseif (strlen($subject) < 5 || strlen($subject) > 30) {
        $errors[] = "Předmět musí být mezi 5 až 30 znaky.";
    }
if (empty($message)) {
        $errors[] = "Zpráva je povinná.";
    } elseif (strlen($message) < 10 || strlen($message) > 500) {
        $errors[] = "Zpráva musí být mezi 10 až 500 znaky.";
    }
if (empty($turnstile_response)) {
        $errors[] = "Prosím dokončete CAPTCHA ověření.";
    } else {
        $verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $verify_data = array(
            'secret' => $turnstile_secret,
            'response' => $turnstile_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        );

        $verify_context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($verify_data)
            )
        ));

        $verify_response = file_get_contents($verify_url, false, $verify_context);
        $verify_result = json_decode($verify_response, true);

        if (!$verify_result['success']) {
            $errors[] = "CAPTCHA ověření selhalo. Prosím zkuste znovu.";
        }
    }
if (empty($errors)) {



        $admin_subject = "Webový kontakt: $subject";
        $admin_body = '<!DOCTYPE html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Webový kontakt</title></head><body style="background:#f7f7f7;margin:0;padding:30px;font-family:Poppins,Arial,sans-serif;">
        <div style="max-width:600px;margin:0 auto;background:#fff;padding:32px 24px 24px 24px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07);">
        <h2 style="color:#FFD154;margin-top:0;margin-bottom:24px;">Webový kontakt</h2>
        <p style="margin-bottom:18px;">Nová zpráva z webového formuláře:</p>
        <table style="width:100%;border-collapse:collapse;margin-bottom:18px;">
            <tr><td style="padding:10px 8px;border:1px solid #eaeaea;font-weight:600;width:30%;background:#f9f9f9;">Předmět</td><td style="padding:10px 8px;border:1px solid #eaeaea;">'.htmlspecialchars($subject).'</td></tr>
            <tr><td style="padding:10px 8px;border:1px solid #eaeaea;font-weight:600;background:#f9f9f9;">Jméno</td><td style="padding:10px 8px;border:1px solid #eaeaea;">'.htmlspecialchars($title).'</td></tr>
            <tr><td style="padding:10px 8px;border:1px solid #eaeaea;font-weight:600;background:#f9f9f9;">Email</td><td style="padding:10px 8px;border:1px solid #eaeaea;"><a href="mailto:'.htmlspecialchars($email).'" style="color:#FFD154;text-decoration:none;">'.htmlspecialchars($email).'</a></td></tr>
            <tr><td style="padding:10px 8px;border:1px solid #eaeaea;font-weight:600;background:#f9f9f9;vertical-align:top;">Zpráva</td><td style="padding:10px 8px;border:1px solid #eaeaea;white-space:pre-wrap;">'.nl2br(htmlspecialchars($message)).'</td></tr>
        </table>
        <hr style="border:none;border-top:1px solid #eee;margin:18px 0;">
    <p style="font-size:13px;color:#666;margin:0;">Odesláno z: <a href="https://'.htmlspecialchars($_SERVER['HTTP_HOST']).'" style="color:#FFD154;text-decoration:none;">'.htmlspecialchars($_SERVER['HTTP_HOST']).'</a><br>IP adresa: '.htmlspecialchars($_SERVER['REMOTE_ADDR']).'<br>Datum: '.date('d.m.Y H:i:s').'</p>
        </div></body></html>';

        $user_subject = "Děkujeme za zprávu - Lukáš Hanuš";
        $user_body = '<!DOCTYPE html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Děkujeme za zprávu</title></head><body style="background:#f7f7f7;margin:0;padding:30px;font-family:Poppins,Arial,sans-serif;">
        <div style="max-width:600px;margin:0 auto;background:#fff;padding:32px 24px 24px 24px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07);">
        <h2 style="color:#FFD154;margin-top:0;margin-bottom:24px;">Děkujeme za zprávu, '.htmlspecialchars($title).'</h2>
        <p style="margin-bottom:18px;">Vaše zpráva byla úspěšně přijata. Níže najdete shrnutí vašeho požadavku:</p>
        <table style="width:100%;border-collapse:collapse;margin-bottom:18px;">
            <tr><td style="padding:10px 8px;border:1px solid #eaeaea;font-weight:600;width:30%;background:#f9f9f9;">Předmět</td><td style="padding:10px 8px;border:1px solid #eaeaea;">'.htmlspecialchars($subject).'</td></tr>
            <tr><td style="padding:10px 8px;border:1px solid #eaeaea;font-weight:600;background:#f9f9f9;">Email</td><td style="padding:10px 8px;border:1px solid #eaeaea;"><a href="mailto:'.htmlspecialchars($email).'" style="color:#FFD154;text-decoration:none;">'.htmlspecialchars($email).'</a></td></tr>
            <tr><td style="padding:10px 8px;border:1px solid #eaeaea;font-weight:600;background:#f9f9f9;vertical-align:top;">Zpráva</td><td style="padding:10px 8px;border:1px solid #eaeaea;white-space:pre-wrap;">'.nl2br(htmlspecialchars($message)).'</td></tr>
        </table>
        <p style="margin-bottom:24px;">Odpovím vám co nejdříve na výše uvedený email.</p>
        <hr style="border:none;border-top:1px solid #eee;margin:18px 0;">
        <p style="font-size:13px;color:#666;margin:0;">S pozdravem,<br>Lukáš Hanuš<br><a href="https://lukycz.is-a.dev" style="color:#FFD154;text-decoration:none;">lukycz.is-a.dev</a></p>
        </div></body></html>';

        $admin_sent = send_email($admin_email, $admin_subject, $admin_body, $smtp_username, "Webový kontakt", true, $email);
        if (!$admin_sent) {
            error_log("SMTP admin send failed: " . ($last_smtp_error ?? 'unknown'));
            $headers = [];
            $headers[] = 'From: Webový kontakt <' . $smtp_username . '>';
            $headers[] = 'Reply-To: ' . ($email ?: $smtp_username);
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $admin_sent = mail($admin_email, $admin_subject, $admin_body, implode("\r\n", $headers));
            if ($admin_sent) {
                error_log("Fallback mail() to admin succeeded.");
            }
        }

        $user_sent = send_email($email, $user_subject, $user_body, $smtp_username, "Lukáš Hanuš", true);
        if (!$user_sent) {
            error_log("SMTP user send failed: " . ($last_smtp_error ?? 'unknown'));
            $headers = [];
            $headers[] = 'From: Lukáš Hanuš <' . $smtp_username . '>';
            $headers[] = 'Reply-To: ' . ($admin_email);
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $user_sent = mail($email, $user_subject, $user_body, implode("\r\n", $headers));
            if ($user_sent) {
                error_log("Fallback mail() to user succeeded.");
            }
        }

        if ($admin_sent || $user_sent) {
            $response['success'] = true;
            $response['message'] = "Zpráva byla úspěšně odeslána! Děkuji za kontakt.";
        } else {
            global $last_smtp_error;
            $errMsg = isset($last_smtp_error) ? ("<br><small>Detail: ".$last_smtp_error."</small>") : "";
            $response['success'] = false;
            $response['message'] = "Chyba při odesílání emailu. Prosím zkuste znovu nebo mě kontaktujte přímo.".$errMsg;
        }
    } else {
        $response['success'] = false;
        $response['message'] = implode('<br>', $errors);
    }
}
function send_email($to, $subject, $body, $from_email = null, $from_name = null, $is_html = false, $reply_to = null) {
    global $smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_secure, $admin_email, $last_smtp_error;

    if (empty($smtp_host) || $smtp_host === "your-smtp-server.com") {
        $headers = "From: " . ($from_name ? "$from_name <$admin_email>" : $admin_email) . "\r\n";
        $headers .= "Reply-To: " . ($from_email ?? $admin_email) . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        return mail($to, $subject, $body, $headers);
    }


    $from = $from_email ?? $admin_email;
    $fromName = $from_name ?? '';

    $use_ssl = (strtolower($smtp_secure) === 'ssl');
    $use_tls = (strtolower($smtp_secure) === 'tls' || strtolower($smtp_secure) === 'starttls');
    $remote = $use_ssl ? "ssl://{$smtp_host}:{$smtp_port}" : "{$smtp_host}:{$smtp_port}";

    $errno = 0; $errstr = '';
    $timeout = 30;
    $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    if (!$fp) {
        $msg = "SMTP connect failed: $errstr ($errno)";
        error_log($msg);
        $last_smtp_error = $msg;
        return false;
    }
    stream_set_timeout($fp, $timeout);

    $read_response = function() use ($fp) {
        $full = '';
        while (($line = fgets($fp, 516)) !== false) {
            $full .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $full;
    };

    $send = function($cmd) use ($fp, $read_response) {
        fwrite($fp, $cmd . "\r\n");
        return $read_response();
    };

    $res = $read_response();
    if (substr($res,0,3) !== '220') {
        $msg = "SMTP banner not received: " . trim($res);
        error_log($msg);
        $last_smtp_error = $msg;
        fclose($fp);
        return false;
    }

    $hostname = gethostname() ?: 'localhost';
    $resp = $send("EHLO {$hostname}");
    if (substr($resp,0,3) !== '250') {
        $resp = $send("HELO {$hostname}");
        if (substr($resp,0,3) !== '250') {
            $msg = "SMTP EHLO/HELO failed: " . trim($resp);
            error_log($msg);
            $last_smtp_error = $msg;
            fclose($fp);
            return false;
        }
    }

    if ($use_tls) {
        $resp = $send('STARTTLS');
        if (substr($resp,0,3) !== '220') {
            $msg = "STARTTLS failed: " . trim($resp);
            error_log($msg);
            $last_smtp_error = $msg;
            fclose($fp);
            return false;
        }
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $msg = "Failed to enable TLS crypto";
            error_log($msg);
            $last_smtp_error = $msg;
            fclose($fp);
            return false;
        }

        $resp = $send("EHLO {$hostname}");
        if (substr($resp,0,3) !== '250') {
            $msg = "EHLO after STARTTLS failed: " . trim($resp);
            error_log($msg);
            $last_smtp_error = $msg;
            fclose($fp);
            return false;
        }
    }

    $resp = $send('AUTH LOGIN');
    if (substr($resp,0,3) === '334') {
        $resp = $send(base64_encode($smtp_username));
        if (substr($resp,0,3) === '334') {
            $resp = $send(base64_encode($smtp_password));
            if (substr($resp,0,3) !== '235') {
                $msg = "SMTP auth failed: " . trim($resp);
                error_log($msg);
                $last_smtp_error = $msg;
                fclose($fp);
                return false;
            }
        } elseif (substr($resp,0,3) === '235') {
        } else {
            $auth = base64_encode("\0{$smtp_username}\0{$smtp_password}");
            $resp2 = $send("AUTH PLAIN {$auth}");
            if (substr($resp2,0,3) !== '235') {
                $msg = "AUTH PLAIN fallback failed: " . trim($resp2);
                error_log($msg);
                $last_smtp_error = $msg;
                fclose($fp);
                return false;
            }
        }
    } elseif (substr($resp,0,3) === '235') {
    } else {
        $auth = base64_encode("\0{$smtp_username}\0{$smtp_password}");
        $resp = $send("AUTH PLAIN {$auth}");
        if (substr($resp,0,3) !== '235') {
            $msg = "SMTP AUTH not accepted: " . trim($resp);
            error_log($msg);
            $last_smtp_error = $msg;
            fclose($fp);
            return false;
        }
    }

    $resp = $send("MAIL FROM:<{$from}>");
    if (substr($resp,0,3) !== '250') {
        $msg = "MAIL FROM failed: " . trim($resp);
        error_log($msg);
        $last_smtp_error = $msg;
        fclose($fp);
        return false;
    }

    $resp = $send("RCPT TO:<{$to}>");
    if (substr($resp,0,3) !== '250' && substr($resp,0,3) !== '251') {
        $msg = "RCPT TO rejected: " . trim($resp);
        error_log($msg);
        $last_smtp_error = $msg;
        fclose($fp);
        return false;
    }

    $resp = $send('DATA');
    if (substr($resp,0,3) !== '354') {
        $msg = "DATA command not accepted: " . trim($resp);
        error_log($msg);
        $last_smtp_error = $msg;
        fclose($fp);
        return false;
    }

    $headers = [];
    $headers[] = 'From: ' . ($fromName ? "{$fromName} <{$from}>" : $from);
    $headers[] = 'Reply-To: ' . ($reply_to ?? $from);
    $headers[] = 'To: ' . $to;
    $headers[] = 'Subject: ' . $subject;
    $headers[] = 'MIME-Version: 1.0';

    if ($is_html) {
        $boundary = 'b_' . md5(uniqid((string)rand(), true));
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

        $plain = strip_tags(preg_replace('/\s+/', ' ', $body));

        $msgParts = [];
        $msgParts[] = "--{$boundary}";
        $msgParts[] = "Content-Type: text/plain; charset=UTF-8";
        $msgParts[] = "Content-Transfer-Encoding: 8bit";
        $msgParts[] = "";
        $msgParts[] = $plain;

        $msgParts[] = "--{$boundary}";
        $msgParts[] = "Content-Type: text/html; charset=UTF-8";
        $msgParts[] = "Content-Transfer-Encoding: 8bit";
        $msgParts[] = "";
        $msgParts[] = $body;

        $msgParts[] = "--{$boundary}--";

        $msg = implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $msgParts) . "\r\n.";
    } else {
        $headers[] = "Content-Type: text/plain; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: 8bit";

        $msg = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
    }

    fwrite($fp, $msg . "\r\n");
    $resp = $read_response();
    if (substr($resp,0,3) !== '250') {
        $msg = "Message not accepted: " . trim($resp);
        error_log($msg);
        $last_smtp_error = $msg;
        fclose($fp);
        return false;
    }

    $send('QUIT');
    fclose($fp);
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        if (ob_get_length() !== false) { @ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    } else {
        $params = array(
            'status' => $response['success'] ? 'success' : 'error',
            'message' => $response['message']
        );
        $qs = http_build_query($params);
        header('Location: /?' . $qs);
        exit;
    }
}
?>