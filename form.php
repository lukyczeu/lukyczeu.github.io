<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();
$admin_email = "lukas.hanus.cz@icloud.com"; 
$smtp_host = "smtp.mail.me.com";
$smtp_port = 587;
$smtp_username = "lukas.hanus.cz@icloud.com";
$smtp_password = "dstq-mzxt-jhqa-ycse";
$smtp_secure = "SSL";

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
        $admin_subject = "Kontakt z webu: $subject";
$admin_body = "
Nový kontakt z vašeho webu:

Email: $email
Jméno: $title
Předmět: $subject
Zpráva:
$message

---
Odesláno z: {$_SERVER['HTTP_HOST']}
IP adresa: {$_SERVER['REMOTE_ADDR']}
Datum: " . date('d.m.Y H:i:s');
$user_subject = "Potvrzení přijetí vaší zprávy - _Luky_Cz_";
    $user_body_plain = "Dobrý den $title,\n\nDěkuji za váš zájem o mé služby. Vaše zpráva byla úspěšně přijata.\n\nVáš požadavek:\nPředmět: $subject\nZpráva: $message\n\nOdpovím vám co nejdříve na email: $email\n\nS pozdravem,\n_Luky_Cz_\nWeb: https://lukycz.is-a.dev\nEmail: lukas.hanus.cz@icloud.com";
    $user_body = "<!doctype html>\n<html lang=\"cs\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>Potvrzení přijetí</title></head><body style=\"font-family:Arial,Helvetica,sans-serif;color:#222;background:#f7f7f7;margin:0;padding:20px;\">\n<div style=\"max-width:600px;margin:0 auto;background:#ffffff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.06);\">\n  <h2 style=\"color:#FFD154;margin-top:0;\">Děkujeme za zprávu, $title</h2>\n  <p>Vaše zpráva byla úspěšně přijata. Níže najdete shrnutí vašeho požadavku:</p>\n  <table style=\"width:100%;border-collapse:collapse;margin:12px 0;\">\n    <tr><td style=\"padding:8px;border:1px solid #eee;width:35%;font-weight:600;\">Předmět</td><td style=\"padding:8px;border:1px solid #eee;\">$subject</td></tr>\n    <tr><td style=\"padding:8px;border:1px solid #eee;font-weight:600;\">Email</td><td style=\"padding:8px;border:1px solid #eee;\">$email</td></tr>\n    <tr><td style=\"padding:8px;border:1px solid #eee;font-weight:600;vertical-align:top;\">Zpráva</td><td style=\"padding:8px;border:1px solid #eee;white-space:pre-wrap;\">$message</td></tr>\n  </table>\n  <p>Odpovím vám co nejdříve na výše uvedený email.</p>\n  <hr style=\"border:none;border-top:1px solid #eee;margin:18px 0;\">\n  <p style=\"font-size:13px;color:#666;margin:0;\">S pozdravem,<br>_Luky_Cz_<br><a href=\"https://lukycz.is-a.dev\">lukycz.is-a.dev</a></p>\n</div>\n</body></html>";
$admin_sent = send_email($admin_email, $admin_subject, $admin_body, $smtp_username, "Website Contact", false, $email);
       if (!$admin_sent) {
            error_log("SMTP admin send failed: " . ($last_smtp_error ?? 'unknown'));
            $headers = [];
            $headers[] = 'From: Website Contact <' . $smtp_username . '>';
            $headers[] = 'Reply-To: ' . ($email ?: $smtp_username);
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $admin_sent = mail($admin_email, $admin_subject, $admin_body, implode("\r\n", $headers));
            if ($admin_sent) {
                error_log("Fallback mail() to admin succeeded.");
            }
        }

        $user_sent = send_email($email, $user_subject, $user_body_plain, $smtp_username, "_Luky_Cz_", false);
        if (!$user_sent) {
            error_log("SMTP user send failed: " . ($last_smtp_error ?? 'unknown'));
            $headers = [];
            $headers[] = 'From: _Luky_Cz_ <' . $smtp_username . '>';
            $headers[] = 'Reply-To: ' . ($admin_email);
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $user_sent = mail($email, $user_subject, $user_body_plain, implode("\r\n", $headers));
            if ($user_sent) {
                error_log("Fallback mail() to user succeeded.");
            }
        }

        if ($admin_sent || $user_sent) {
            $response['success'] = true;
            $response['message'] = "Zpráva byla úspěšně odeslána! Děkuji za kontakt.";
        } else {
            $response['success'] = false;
            $response['message'] = "Chyba při odesílání emailu. Prosím zkuste znovu nebo mě kontaktujte přímo.";
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

    $remote = ($smtp_secure === 'ssl') ? "ssl://{$smtp_host}:{$smtp_port}" : "{$smtp_host}:{$smtp_port}";

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