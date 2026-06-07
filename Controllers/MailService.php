<?php

declare(strict_types=1);

/** Dernière erreur SMTP (diagnostic). */
function happybite_mail_last_error(): string
{
    return (string) ($GLOBALS['happybite_mail_last_error'] ?? '');
}

function happybite_mail_set_error(string $msg): void
{
    $GLOBALS['happybite_mail_last_error'] = $msg;
}

/** @return array<string, string>|null */
function happybite_mail_config(): ?array
{
    $path = dirname(__DIR__) . '/config/mail.php';
    if (!is_file($path)) {
        happybite_mail_set_error('Fichier config/mail.php introuvable.');

        return null;
    }
    $cfg = require $path;
    if (!is_array($cfg)) {
        happybite_mail_set_error('config/mail.php invalide.');

        return null;
    }

    return $cfg;
}

/** Escape SMTP DATA body (dot-stuffing). */
function happybite_smtp_prepare_body(string $body): string
{
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $lines = explode("\n", $body);
    $out = [];
    foreach ($lines as $line) {
        if ($line !== '' && $line[0] === '.') {
            $line = '.' . $line;
        }
        $out[] = $line;
    }

    return implode("\r\n", $out);
}

function happybite_mail_send(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
{
    happybite_mail_set_error('');
    $to = strtolower(trim($to));
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        happybite_mail_set_error('Adresse destinataire invalide : ' . $to);

        return false;
    }
    $cfg = happybite_mail_config();
    if ($cfg === null) {
        return false;
    }

    if (!extension_loaded('openssl')) {
        happybite_mail_set_error('Extension PHP openssl requise pour Gmail SMTP.');

        return false;
    }

    $user = trim((string) ($cfg['smtp_user'] ?? ''));
    $pass = trim((string) ($cfg['smtp_pass'] ?? ''), " \t\n\r\0\x0B'\"");
    if ($user === '' || $pass === ''
        || stripos($user, 'VOTRE_') !== false
        || stripos($pass, 'VOTRE_') !== false
        || stripos($user, 'votre.email') !== false
    ) {
        happybite_mail_set_error('Renseignez smtp_user et smtp_pass (mot de passe d\'application Google) dans config/mail.php.');

        return false;
    }

    $host = (string) ($cfg['smtp_host'] ?? 'smtp.gmail.com');
    $fromEmail = (string) ($cfg['from_email'] ?? $user);
    $fromName = (string) ($cfg['from_name'] ?? 'HappyBite');

    $mime = happybite_mail_build_mime_body($htmlBody, $textBody);
    $result = happybite_smtp_send_html(
        $host,
        587,
        false,
        $user,
        $pass,
        $fromEmail,
        $fromName,
        $to,
        $subject,
        $mime['body'],
        $mime['multipart'],
        $mime['boundary']
    );
    if ($result['ok']) {
        return true;
    }

    $err587 = $result['error'] ?? '';
    $result465 = happybite_smtp_send_html(
        $host,
        465,
        true,
        $user,
        $pass,
        $fromEmail,
        $fromName,
        $to,
        $subject,
        $mime['body'],
        $mime['multipart'],
        $mime['boundary']
    );
    if ($result465['ok']) {
        return true;
    }

    happybite_mail_set_error($err587 !== '' ? $err587 : ($result465['error'] ?? 'Échec SMTP.'));

    return false;
}

/**
 * @return array{body: string, multipart: bool, boundary: string}
 */
function happybite_mail_build_mime_body(string $htmlBody, ?string $textBody): array
{
    if ($textBody === null || trim($textBody) === '') {
        return ['body' => $htmlBody, 'multipart' => false, 'boundary' => ''];
    }

    $boundary = 'hb_' . bin2hex(random_bytes(8));
    $body = '--' . $boundary . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n\r\n"
        . trim($textBody) . "\r\n\r\n"
        . '--' . $boundary . "\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n\r\n"
        . $htmlBody . "\r\n\r\n"
        . '--' . $boundary . '--';

    return ['body' => $body, 'multipart' => true, 'boundary' => $boundary];
}

/** @return array{ok: bool, error?: string} */
function happybite_smtp_send_html(
    string $host,
    int $port,
    bool $useSsl,
    string $user,
    string $pass,
    string $fromEmail,
    string $fromName,
    string $to,
    string $subject,
    string $htmlBody,
    bool $isMultipart = false,
    string $multipartBoundary = ''
): array {
    $pass = str_replace(' ', '', $pass);
    $errno = 0;
    $errstr = '';
    $target = ($useSsl ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $fp = @stream_socket_client($target, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$fp) {
        return ['ok' => false, 'error' => "Connexion {$target} impossible : {$errstr} ({$errno})"];
    }
    stream_set_timeout($fp, 20);

    $read = static function () use ($fp): string {
        $data = '';
        while ($line = fgets($fp, 8192)) {
            $data .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }

        return $data;
    };
    $write = static function (string $cmd) use ($fp): void {
        fwrite($fp, $cmd . "\r\n");
    };
    $expect = static function (string $resp, array $codes) use (&$read): bool {
        foreach ($codes as $code) {
            if (strpos($resp, (string) $code) === 0) {
                return true;
            }
        }

        return false;
    };

    $banner = $read();
    if (!$expect($banner, ['220'])) {
        fclose($fp);

        return ['ok' => false, 'error' => 'Serveur SMTP : ' . trim($banner)];
    }

    $write('EHLO localhost');
    $ehlo = $read();

    if (!$useSsl) {
        $write('STARTTLS');
        $tlsResp = $read();
        if (!$expect($tlsResp, ['220'])) {
            fclose($fp);

            return ['ok' => false, 'error' => 'STARTTLS refusé : ' . trim($tlsResp)];
        }
        $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        }
        if (!@stream_socket_enable_crypto($fp, true, $crypto)) {
            fclose($fp);

            return ['ok' => false, 'error' => 'Impossible d\'activer TLS (vérifiez extension openssl).'];
        }
        $write('EHLO localhost');
        $ehlo = $read();
    }

    $write('AUTH LOGIN');
    $authStart = $read();
    if (!$expect($authStart, ['334'])) {
        fclose($fp);

        return ['ok' => false, 'error' => 'AUTH LOGIN refusé : ' . trim($authStart)];
    }
    $write(base64_encode($user));
    $userResp = $read();
    if (!$expect($userResp, ['334'])) {
        fclose($fp);

        return ['ok' => false, 'error' => 'Utilisateur SMTP refusé : ' . trim($userResp)];
    }
    $write(base64_encode($pass));
    $auth = $read();
    if (!$expect($auth, ['235'])) {
        fclose($fp);
        $hint = 'Utilisez un mot de passe d\'application Google (16 caractères), pas votre mot de passe Gmail normal. '
            . 'https://myaccount.google.com/apppasswords';

        return ['ok' => false, 'error' => 'Authentification Gmail échouée : ' . trim($auth) . ' — ' . $hint];
    }

    $write('MAIL FROM:<' . $fromEmail . '>');
    $mf = $read();
    if (!$expect($mf, ['250'])) {
        fclose($fp);

        return ['ok' => false, 'error' => 'MAIL FROM refusé : ' . trim($mf)];
    }

    $write('RCPT TO:<' . $to . '>');
    $rcpt = $read();
    if (!$expect($rcpt, ['250', '251'])) {
        fclose($fp);

        return ['ok' => false, 'error' => 'RCPT TO refusé : ' . trim($rcpt)];
    }

    $write('DATA');
    $dataResp = $read();
    if (!$expect($dataResp, ['354'])) {
        fclose($fp);

        return ['ok' => false, 'error' => 'DATA refusé : ' . trim($dataResp)];
    }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $body = happybite_smtp_prepare_body($htmlBody);
    $contentType = $isMultipart && $multipartBoundary !== ''
        ? 'Content-Type: multipart/alternative; boundary="' . $multipartBoundary . "\"\r\n"
        : "Content-Type: text/html; charset=UTF-8\r\n";
    $msgId = '<hb.' . bin2hex(random_bytes(8)) . '@happybite.local>';
    $msg = 'From: ' . $fromName . ' <' . $fromEmail . ">\r\n"
        . 'To: <' . $to . ">\r\n"
        . 'Reply-To: ' . $fromEmail . "\r\n"
        . 'Message-ID: ' . $msgId . "\r\n"
        . 'Subject: ' . $encodedSubject . "\r\n"
        . "MIME-Version: 1.0\r\n"
        . $contentType
        . "Content-Transfer-Encoding: 8bit\r\n\r\n"
        . $body . "\r\n.\r\n";
    fwrite($fp, $msg);
    $sent = $read();
    $write('QUIT');
    fclose($fp);

    if (!$expect($sent, ['250'])) {
        return ['ok' => false, 'error' => 'Envoi refusé : ' . trim($sent)];
    }

    return ['ok' => true];
}
