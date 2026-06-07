<?php

declare(strict_types=1);

require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/AuthFaceSupport.php';
require_once __DIR__ . '/UtilisateurPhotoSql.php';
require_once __DIR__ . '/UserNotificationService.php';

function password_change_session_key(): string
{
    return 'password_change_pending';
}

/** @return array<string, mixed>|null */
function password_change_get_pending(): ?array
{
    $p = $_SESSION[password_change_session_key()] ?? null;

    return is_array($p) ? $p : null;
}

function password_change_clear_pending(): void
{
    unset($_SESSION[password_change_session_key()]);
}

function password_change_utilisateur_column_exists(PDO $pdo, string $column): bool
{
    static $cache = [];
    if (isset($cache[$column])) {
        return $cache[$column];
    }
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
    );
    $stmt->execute(['t' => 'utilisateur', 'c' => $column]);
    $cache[$column] = (int) $stmt->fetchColumn() > 0;

    return $cache[$column];
}

/** User enrolled Face ID at registration / login (face_id_auth, descriptor, or face_auth_image). */
function password_change_user_has_face_enrolled(PDO $pdo, int $uid, string $pk): bool
{
    if (password_change_utilisateur_column_exists($pdo, 'face_id_auth')) {
        $q = $pdo->prepare("SELECT face_id_auth FROM utilisateur WHERE `{$pk}` = :id LIMIT 1");
        $q->execute(['id' => $uid]);
        $val = $q->fetchColumn();
        if ($val === false) {
            return false;
        }
        if (is_numeric($val)) {
            return (int) $val !== 0;
        }
        $s = trim((string) $val);

        return $s !== '' && strtolower($s) !== 'null' && $s !== '0';
    }

    if (authFaceLoadDescriptor($uid) !== null) {
        return true;
    }

    return password_change_face_image_path($pdo, $uid) !== null;
}

function password_change_web_root_prefix(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (preg_match('#^(.*)/(?:Controllers|FrontOffice)/#', $script, $m)) {
        return rtrim($m[1], '/');
    }
    $dir = dirname($script);
    if (str_ends_with($dir, '/Controllers') || str_ends_with($dir, '/FrontOffice')) {
        return rtrim(dirname($dir), '/');
    }

    return rtrim($dir, '/');
}

function password_change_uses_local_url(string $url): bool
{
    return preg_match('#^https?://(localhost|127\.0\.0\.1|\[::1\])(:\d+)?#i', $url) === 1;
}

function password_change_profile_url(array $query = []): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $root = password_change_web_root_prefix();
    $path = ($root !== '' ? $root : '') . '/FrontOffice/Profile_Utilisateur.php';
    $url = $scheme . '://' . $host . $path;
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

function password_change_send_verification_email(string $toEmail, string $yesUrl, string $noUrl): bool
{
    $subject = 'HappyBite — Changement de mot de passe';
    $html = '<div style="font-family:Poppins,Arial,sans-serif;max-width:520px;margin:0 auto;padding:24px;">'
        . '<h2 style="color:#173b2c;">Est-ce bien vous ?</h2>'
        . '<p style="color:#5c6d66;line-height:1.5;">Une demande de changement de mot de passe a été faite sur votre compte HappyBite. '
        . 'Est-ce vous qui souhaitez modifier votre mot de passe ?</p>'
        . '<p style="margin:28px 0;">'
        . '<a href="' . htmlspecialchars($yesUrl, ENT_QUOTES, 'UTF-8') . '" '
        . 'style="display:inline-block;background:#2C7E34;color:#fff;padding:12px 22px;border-radius:12px;text-decoration:none;font-weight:600;margin-right:10px;">Oui, c\'est moi</a>'
        . '<a href="' . htmlspecialchars($noUrl, ENT_QUOTES, 'UTF-8') . '" '
        . 'style="display:inline-block;background:#b91c1c;color:#fff;padding:12px 22px;border-radius:12px;text-decoration:none;font-weight:600;">Non, ce n\'est pas moi</a>'
        . '</p>'
        . '<p style="font-size:12px;color:#999;">Si vous n\'êtes pas à l\'origine de cette demande, cliquez sur Non.</p>'
        . '</div>';

    $text = "HappyBite — Changement de mot de passe\r\n\r\n"
        . "Une demande de changement de mot de passe a été faite sur votre compte.\r\n\r\n"
        . "Oui, c'est moi :\r\n" . $yesUrl . "\r\n\r\n"
        . "Non, ce n'est pas moi :\r\n" . $noUrl . "\r\n";

    return happybite_mail_send($toEmail, $subject, $html, $text);
}

/**
 * @return array{ok: bool, error?: string, message?: string, dev_links?: array{yes: string, no: string}}
 */
function password_change_resolve_user_email(PDO $pdo, int $uid, string $pk, string $fallbackEmail): string
{
    $stmt = $pdo->prepare("SELECT email FROM utilisateur WHERE `{$pk}` = :id LIMIT 1");
    $stmt->execute(['id' => $uid]);
    $dbEmail = strtolower(trim((string) ($stmt->fetchColumn() ?: '')));
    if ($dbEmail !== '' && filter_var($dbEmail, FILTER_VALIDATE_EMAIL)) {
        return $dbEmail;
    }

    return strtolower(trim($fallbackEmail));
}

function password_change_start(PDO $pdo, int $uid, string $pk, string $userEmail, string $current, string $new, string $confirm): array
{
    $userEmail = password_change_resolve_user_email($pdo, $uid, $pk, $userEmail);
    if ($userEmail === '' || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Adresse email du compte invalide. Mettez à jour votre email dans le profil.'];
    }

    $stmt = $pdo->prepare("SELECT motDePasse FROM utilisateur WHERE `{$pk}` = :id LIMIT 1");
    $stmt->execute(['id' => $uid]);
    $hash = (string) ($stmt->fetchColumn() ?: '');

    if ($hash === '' || !password_verify($current, $hash)) {
        return ['ok' => false, 'error' => 'Mot de passe actuel incorrect.'];
    }
    if (strlen($new) < 6) {
        return ['ok' => false, 'error' => 'Le nouveau mot de passe doit contenir au moins 6 caractères.'];
    }
    if ($new !== $confirm) {
        return ['ok' => false, 'error' => 'La confirmation ne correspond pas.'];
    }

    $token = bin2hex(random_bytes(24));
    $_SESSION[password_change_session_key()] = [
        'token' => $token,
        'user_id' => $uid,
        'new_password' => $new,
        'email_verified' => false,
        'face_verified' => false,
        'expires' => time() + 1800,
    ];

    $yesUrl = password_change_profile_url(['pwd_token' => $token, 'pwd_answer' => 'yes', 'bridge' => '1']);
    $noUrl = password_change_profile_url(['pwd_token' => $token, 'pwd_answer' => 'no', 'bridge' => '1']);

    $sent = password_change_send_verification_email($userEmail, $yesUrl, $noUrl);
    if (!$sent) {
        $mailErr = happybite_mail_last_error();

        return [
            'ok' => true,
            'mail_sent' => false,
            'message' => 'Email non envoyé vers ' . $userEmail . '.',
            'mail_error' => $mailErr !== '' ? $mailErr : 'Vérifiez config/mail.php (mot de passe d\'application Google).',
            'sent_to' => $userEmail,
            'dev_links' => ['yes' => $yesUrl, 'no' => $noUrl],
        ];
    }

    $response = [
        'ok' => true,
        'mail_sent' => true,
        'message' => 'Email envoyé à ' . $userEmail . ' — sujet « HappyBite — Changement de mot de passe ». '
            . 'Cherchez aussi dans Spams / Promotions / Réseaux sociaux (onglet Tout).',
        'sent_to' => $userEmail,
    ];
    // Gmail accepte souvent le SMTP mais masque les mails avec liens localhost — secours sur la page.
    if (password_change_uses_local_url($yesUrl)) {
        $response['dev_links'] = ['yes' => $yesUrl, 'no' => $noUrl];
        $response['local_fallback'] = true;
        $response['message'] .= ' Si vous ne voyez rien dans Gmail, utilisez les boutons de secours sous le formulaire.';
    }

    return $response;
}

/**
 * Confirmation Oui/Non depuis la page Paramètres (sans changer d'onglet).
 *
 * @return array{ok: bool, flash?: string, logout?: bool, error?: string}
 */
function password_change_confirm_email_answer(int $uid, string $answer, ?PDO $pdo = null, ?string $pk = null): array
{
    $pending = password_change_get_pending();
    if ($pending === null || (int) ($pending['user_id'] ?? 0) !== $uid) {
        return ['ok' => false, 'error' => 'Aucune demande de changement de mot de passe en cours.'];
    }

    return password_change_handle_email_link((string) ($pending['token'] ?? ''), $answer, $uid, $pdo, $pk);
}

/**
 * @return array{ok: bool, flash?: string, logout?: bool}
 */
function password_change_handle_email_link(string $token, string $answer, int $uid, ?PDO $pdo = null, ?string $pk = null): array
{
    $pending = password_change_get_pending();
    if ($pending === null
        || (int) ($pending['user_id'] ?? 0) !== $uid
        || !hash_equals((string) ($pending['token'] ?? ''), $token)
        || time() > (int) ($pending['expires'] ?? 0)
    ) {
        password_change_clear_pending();

        return ['ok' => false, 'flash' => 'expired'];
    }

    if ($answer === 'no') {
        password_change_clear_pending();

        return ['ok' => true, 'flash' => 'not_you', 'logout' => true];
    }

    if ($answer === 'yes') {
        $_SESSION[password_change_session_key()]['email_verified'] = true;
        $skipFace = true;
        if ($pdo !== null && $pk !== null && $pk !== '') {
            $skipFace = !password_change_user_has_face_enrolled($pdo, $uid, $pk);
        }
        if ($skipFace) {
            $_SESSION[password_change_session_key()]['face_verified'] = true;
        }

        return [
            'ok' => true,
            'flash' => 'email_ok',
            'skip_face' => $skipFace,
        ];
    }

    return ['ok' => false, 'flash' => 'invalid'];
}

/**
 * @return array{ok: bool, error?: string}
 */
function password_change_verify_face(PDO $pdo, int $uid, string $descriptorRaw, ?string $jpeg): array
{
    $pending = password_change_get_pending();
    if ($pending === null
        || (int) ($pending['user_id'] ?? 0) !== $uid
        || empty($pending['email_verified'])
        || time() > (int) ($pending['expires'] ?? 0)
    ) {
        return ['ok' => false, 'error' => 'Étape email requise ou session expirée.'];
    }

    $pk = utilisateur_table_pk_column($pdo);
    if (!password_change_user_has_face_enrolled($pdo, $uid, $pk)) {
        return ['ok' => false, 'error' => 'Face ID non enregistré sur ce compte.'];
    }

    $liveDescriptor = authFaceParseDescriptor($descriptorRaw);
    if ($liveDescriptor === null) {
        return ['ok' => false, 'error' => 'Empreinte visage invalide.'];
    }

    $storedDescriptor = authFaceLoadDescriptor($uid);
    $matched = false;
    if ($storedDescriptor !== null) {
        $matched = authFaceMatchDescriptorVectors($storedDescriptor, $liveDescriptor);
    }
    if (!$matched && $jpeg !== null && function_exists('imagecreatefromstring')) {
        $rel = password_change_face_image_path($pdo, $uid);
        if ($rel !== null) {
            $abs = dirname(__DIR__) . '/' . $rel;
            $matched = authFaceMatchStored($abs, $jpeg);
        }
    }
    if (!$matched) {
        return ['ok' => false, 'error' => 'Visage non reconnu. Réessayez ou réenregistrez Face ID.'];
    }

    $_SESSION[password_change_session_key()]['face_verified'] = true;

    return ['ok' => true];
}

function password_change_face_image_path(PDO $pdo, int $uid): ?string
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
    );
    $stmt->execute(['t' => 'utilisateur', 'c' => 'face_auth_image']);
    if ((int) $stmt->fetchColumn() < 1) {
        return null;
    }
    $pk = utilisateur_table_pk_column($pdo);
    $q = $pdo->prepare("SELECT face_auth_image FROM utilisateur WHERE `{$pk}` = :id LIMIT 1");
    $q->execute(['id' => $uid]);
    $rel = trim((string) ($q->fetchColumn() ?: ''));

    return $rel !== '' ? $rel : null;
}

/**
 * @return array{ok: bool, error?: string, message?: string}
 */
function password_change_finalize(PDO $pdo, int $uid, string $pk): array
{
    $pending = password_change_get_pending();
    $faceRequired = password_change_user_has_face_enrolled($pdo, $uid, $pk);
    if ($pending === null
        || (int) ($pending['user_id'] ?? 0) !== $uid
        || empty($pending['email_verified'])
        || time() > (int) ($pending['expires'] ?? 0)
    ) {
        return ['ok' => false, 'error' => 'Vérification incomplète. Recommencez le changement de mot de passe.'];
    }
    if ($faceRequired && empty($pending['face_verified'])) {
        return ['ok' => false, 'error' => 'Vérification Face ID requise.'];
    }
    if (!$faceRequired) {
        $_SESSION[password_change_session_key()]['face_verified'] = true;
    }

    $new = (string) ($pending['new_password'] ?? '');
    if (strlen($new) < 6) {
        return ['ok' => false, 'error' => 'Mot de passe invalide.'];
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $up = $pdo->prepare("UPDATE utilisateur SET motDePasse = :h WHERE `{$pk}` = :id");
    if (!$up->execute(['h' => $newHash, 'id' => $uid])) {
        return ['ok' => false, 'error' => 'Impossible de mettre à jour le mot de passe.'];
    }

    password_change_clear_pending();

    user_notification_password_changed($pdo, $uid);

    return ['ok' => true, 'message' => 'Mot de passe modifié avec succès.'];
}
