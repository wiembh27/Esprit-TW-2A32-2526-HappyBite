<?php declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../Controllers/UtilisateurPhotoSql.php';
require_once __DIR__ . '/../Controllers/UtilisateurAccountDelete.php';
require_once __DIR__ . '/../Controllers/PasswordChangeService.php';
require_once __DIR__ . '/../Controllers/UserNotificationService.php';

$pdo = Database::getConnection();
$uid = (int) ($_SESSION['user_id'] ?? 0);
if ($uid <= 0) {
    header('Location: auth/login.php');
    exit;
}

$pk = utilisateur_table_pk_column($pdo);

if (isset($_GET['pwd_token'], $_GET['pwd_answer'])) {
    $_SESSION['hb_pwd_link_nav'] = time();
    $answer = strtolower((string) $_GET['pwd_answer']);
    $linkResult = password_change_handle_email_link(
        (string) $_GET['pwd_token'],
        $answer,
        $uid,
        $pdo,
        $pk
    );
    $flash = (string) ($linkResult['flash'] ?? '');

    if (isset($_GET['bridge'])) {
        if ($flash === 'email_ok' && password_change_user_has_face_enrolled($pdo, $uid, $pk)) {
            $_SESSION['pwd_show_face_step_once'] = '1';
        } elseif ($flash === 'not_you' || !empty($linkResult['logout'])) {
            $_SESSION['pwd_flash_not_you'] = '1';
            $_SESSION['pwd_flash_logout'] = '1';
        } elseif ($flash === 'expired') {
            $_SESSION['pwd_flash_expired'] = '1';
        }

        $profileCleanUrl = 'Profile_Utilisateur.php';
        header('Content-Type: text/html; charset=utf-8');
        $payload = json_encode([
            'type' => 'hb_pwd_email',
            'answer' => $answer,
            'flash' => $flash,
            'logout' => !empty($linkResult['logout']),
        ], JSON_UNESCAPED_UNICODE);
        $profileUrlJs = json_encode($profileCleanUrl, JSON_UNESCAPED_UNICODE);
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
            . '<link rel="icon" type="image/png" href="images/tiny_logo.png">'
            . '<title>HappyBite</title></head><body>'
            . '<p id="bridge-msg" style="font-family:sans-serif;text-align:center;padding:2rem;color:#5c6d66;">Retour à Paramètres…</p>'
            . '<script>var payload=' . $payload . ',key="hb_pwd_email_result",profileUrl=' . $profileUrlJs . ';'
            . 'try{localStorage.setItem(key,JSON.stringify(payload));}catch(e){}'
            . 'if(window.opener&&!window.opener.closed){try{window.opener.postMessage(payload,window.location.origin);}catch(e){}}'
            . 'var closed=window.close();'
            . 'setTimeout(function(){'
            . 'if(payload.flash==="email_ok"||payload.answer==="yes"||payload.flash==="not_you"||payload.logout||payload.flash==="expired"){window.location.href=profileUrl;return;}'
            . 'document.getElementById("bridge-msg").textContent="Revenez à l’onglet Paramètres, ou fermez cet onglet.";'
            . '},600);'
            . '</script></body></html>';
        exit;
    }

    if ($flash === 'email_ok' && password_change_user_has_face_enrolled($pdo, $uid, $pk)) {
        $_SESSION['pwd_show_face_step_once'] = '1';
    } elseif ($flash === 'not_you' || !empty($linkResult['logout'])) {
        $_SESSION['pwd_flash_not_you'] = '1';
        $_SESSION['pwd_flash_logout'] = '1';
    } elseif ($flash === 'expired') {
        $_SESSION['pwd_flash_expired'] = '1';
    }
    header('Location: Profile_Utilisateur.php');
    exit;
}

$showPwdFaceStepOnce = !empty($_SESSION['pwd_show_face_step_once']);
unset($_SESSION['pwd_show_face_step_once']);

$pwdFlashNotYou = !empty($_SESSION['pwd_flash_not_you']);
unset($_SESSION['pwd_flash_not_you']);

$pwdFlashLogout = !empty($_SESSION['pwd_flash_logout']);
unset($_SESSION['pwd_flash_logout']);

$pwdFlashExpired = !empty($_SESSION['pwd_flash_expired']);
unset($_SESSION['pwd_flash_expired']);

$pwdUserHasFaceAuth = password_change_user_has_face_enrolled($pdo, $uid, $pk);

$pwdPendingEmailVerified = false;
$pwdChangePendingActive = false;
$pwdReadyToFinalizeOnLoad = false;
$pwdPending = password_change_get_pending();
if ($pwdPending !== null && (int) ($pwdPending['user_id'] ?? 0) !== $uid) {
    password_change_clear_pending();
    $pwdPending = null;
}
if ($pwdPending !== null
    && (int) ($pwdPending['user_id'] ?? 0) === $uid
    && time() <= (int) ($pwdPending['expires'] ?? 0)
) {
    $pwdChangePendingActive = true;
    $pwdPendingEmailVerified = !empty($pwdPending['email_verified'])
        && empty($pwdPending['face_verified'])
        && $pwdUserHasFaceAuth;
    $pwdReadyToFinalizeOnLoad = !empty($pwdPending['email_verified'])
        && !empty($pwdPending['face_verified']);
}

$columnExists = static function (PDO $pdoConn, string $table, string $column): bool {
    $stmt = $pdoConn->prepare(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
    );
    $stmt->execute(['t' => $table, 'c' => $column]);

    return (int) $stmt->fetchColumn() > 0;
};

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'notif_mark_read') {
        header('Content-Type: application/json; charset=utf-8');
        $nid = (int) ($_POST['id_notification'] ?? 0);
        $ok = user_notification_mark_one_read($pdo, $uid, $nid);
        echo json_encode(['ok' => $ok], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'update_profile') {
        $prenom = trim((string) ($_POST['prenom'] ?? ''));
        $nom = trim((string) ($_POST['nom'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($prenom === '' || $nom === '' || $email === '') {
            $error = 'Prénom, nom et email sont obligatoires.';
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE email = :email AND `{$pk}` != :id");
            $stmt->execute(['email' => $email, 'id' => $uid]);
            if ((int) $stmt->fetchColumn() > 0) {
                $error = 'Cette adresse email est déjà utilisée.';
            } else {
                $sets = ['prenom = :prenom', 'nom = :nom', 'email = :email'];
                $params = [
                    'prenom' => $prenom,
                    'nom' => $nom,
                    'email' => $email,
                    'id' => $uid,
                ];
                if ($columnExists($pdo, 'utilisateur', 'description')) {
                    $sets[] = 'description = :description';
                    $params['description'] = $description;
                }
                if ($columnExists($pdo, 'utilisateur', 'budget')) {
                    $sets[] = 'budget = :budget';
                    $params['budget'] = isset($_POST['budget']) && $_POST['budget'] !== '' ? (float) $_POST['budget'] : 0.0;
                }

                $photoPath = null;
                if (!empty($_FILES['profile_photo']['tmp_name']) && is_uploaded_file((string) $_FILES['profile_photo']['tmp_name'])) {
                    $photoPath = utilisateur_handle_profile_photo_upload($_FILES['profile_photo']);
                    if ($photoPath === null) {
                        $error = 'Photo non enregistrée (format JPEG, PNG, GIF ou Webp, max 2 Mo).';
                    }
                }

                if ($error === '' && $photoPath !== null) {
                    if ($columnExists($pdo, 'utilisateur', 'profil-image')) {
                        $sets[] = '`profil-image` = :profil_image';
                        $params['profil_image'] = $photoPath;
                    } elseif ($columnExists($pdo, 'utilisateur', 'profile_photo')) {
                        $sets[] = 'profile_photo = :profile_photo';
                        $params['profile_photo'] = $photoPath;
                    }
                }

                if ($error === '') {
                    $sql = 'UPDATE utilisateur SET ' . implode(', ', $sets) . " WHERE `{$pk}` = :id";
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute($params)) {
                        $_SESSION['user_prenom'] = $prenom;
                        $_SESSION['user_nom'] = $nom;
                        $_SESSION['user_email'] = $email;
                        $message = 'Profil mis à jour.';
                        header('Location: Profile_Utilisateur.php?ok=1');
                        exit;
                    }
                    $error = 'Impossible d’enregistrer les modifications.';
                }
            }
        }
    } elseif ($action === 'delete_account') {
        try {
            $photoForCleanup = utilisateur_fetch_profile_relative_path($pdo, $uid);
            utilisateur_delete_account_cascade($pdo, $uid, $pk);
            utilisateur_delete_upload_files($uid, $photoForCleanup);
            utilisateur_destroy_session_and_go_home();
        } catch (Throwable $e) {
            $error = 'Suppression impossible (données liées au compte). Contactez le support.';
        }
    }
}

if (isset($_GET['ok'])) {
    $message = 'Profil mis à jour.';
}

$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE `{$pk}` = :id LIMIT 1");
$stmt->execute(['id' => $uid]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$userRow) {
    header('Location: auth/login.php');
    exit;
}

$prenom = (string) ($userRow['prenom'] ?? '');
$nom = (string) ($userRow['nom'] ?? '');
$email = (string) ($userRow['email'] ?? '');
$role = (string) ($userRow['role'] ?? '');
$description = (string) ($userRow['description'] ?? '');
$budget = $userRow['budget'] ?? null;

$relPhoto = utilisateur_fetch_profile_relative_path($pdo, $uid);
$photoSrc = $relPhoto !== null ? utilisateur_nav_profile_img_src($relPhoto) : null;

$initials = '';
if ($prenom !== '') {
    $initials .= strtoupper(substr($prenom, 0, 1));
}
if ($nom !== '') {
    $initials .= strtoupper(substr($nom, 0, 1));
}
if ($initials === '') {
    $initials = 'M';
}

$hasBudget = $columnExists($pdo, 'utilisateur', 'budget');
$hasDescription = $columnExists($pdo, 'utilisateur', 'description');

user_notification_ensure_table($pdo);
$profileNotifications = user_notification_list($pdo, $uid);
$navUnreadNotifCount = user_notification_count_unread($pdo, $uid);

require_once __DIR__ . '/includes/fo_i18n.php';
fo_init_i18n_for_request();
require_once __DIR__ . '/../Controllers/UserSettingsService.php';
user_settings_load_for_user($pdo, $uid);
$profileLang = fo_lang();
$profileMode = fo_mode();
?>
<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>" dir="<?php echo fo_html_dir_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; hb_brand_render_head(); ?>

    <title>HappyBite — Paramètres</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Views/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style-original-views.css">
    <link rel="stylesheet" href="auth/auth-layout.css">
    <style>
        .profile-page .profile-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e8ecf0;
            box-shadow: 0 8px 28px rgba(19, 30, 23, 0.06);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .profile-page .profile-card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #eef2f0;
            font-weight: 700;
            color: #173b2c;
            font-size: 1.05rem;
        }
        .profile-page .profile-card-body {
            padding: 1.25rem;
        }
        .profile-page .profile-photo-wrap {
            text-align: center;
        }
        .profile-page .profile-photo-img {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(44, 126, 52, 0.35);
        }
        .profile-page .profile-photo-placeholder {
            width: 160px;
            height: 160px;
            margin: 0 auto;
            border-radius: 50%;
            background: linear-gradient(135deg, #1d6b2a, #43a047);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 2rem;
            border: 3px solid rgba(44, 126, 52, 0.35);
        }
        .profile-page .btn-profile-primary {
            background: #2C7E34;
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 0.5rem 1.25rem;
            border-radius: 12px;
        }
        .profile-page .btn-profile-primary:hover {
            background: #256d2c;
            color: #fff;
        }
        .profile-page .btn-profile-muted {
            background: #eef2f0;
            border: none;
            color: #334;
            font-weight: 600;
            padding: 0.5rem 1.25rem;
            border-radius: 12px;
        }
        .profile-page .btn-profile-danger {
            background: #b91c1c;
            border: 2px solid #991b1b;
            color: #fff;
            font-weight: 600;
            padding: 0.6rem 1.25rem;
            border-radius: 12px;
            width: 100%;
        }
        .profile-page .btn-profile-danger:hover {
            background: #991b1b;
            color: #fff;
        }
        .profile-page .edit-panel {
            display: none;
            padding: 1.25rem;
            background: #f8faf8;
            border-top: 1px solid #eef2f0;
        }
        .profile-page .edit-panel.is-open {
            display: block;
        }
        .profile-page .info-row {
            display: flex;
            gap: 1rem;
            padding: 0.65rem 0;
            border-bottom: 1px solid #f0f3f1;
        }
        .profile-page .info-label {
            width: 130px;
            flex-shrink: 0;
            color: #5c6d66;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .profile-page .info-value {
            color: #173b2c;
            font-weight: 600;
        }
        .profile-page .page-lead {
            color: #5c6d66;
            max-width: 640px;
        }
        .profile-page .settings-layout {
            align-items: flex-start;
        }
        .profile-page .settings-col-left {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .profile-page .settings-col-left .profile-card,
        .profile-page .settings-col-right .profile-card {
            margin-bottom: 0;
        }
        .profile-page .settings-col-right .profile-card {
            position: sticky;
            top: 1rem;
        }
        /* —— Thème (UI seulement) —— */
        .profile-page .pref-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .profile-page .pref-label {
            font-weight: 600;
            color: #173b2c;
            font-size: 0.95rem;
        }
        .profile-page .pref-hint {
            font-size: 0.78rem;
            color: #5c6d66;
            margin: 0.2rem 0 0;
        }
        .theme-toggle {
            position: relative;
            display: inline-block;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        .theme-toggle-input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .theme-toggle-track {
            display: block;
            width: 88px;
            height: 44px;
            border-radius: 999px;
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.12), 0 4px 14px rgba(19, 30, 23, 0.12);
            transition: box-shadow 0.35s ease;
        }
        .theme-scene {
            position: absolute;
            inset: 0;
            border-radius: inherit;
            transition: opacity 0.55s ease, transform 0.55s ease;
        }
        .theme-scene--light {
            background: linear-gradient(180deg, #87ceeb 0%, #b8e4f8 55%, #e8f4fc 100%);
            opacity: 1;
        }
        .theme-scene--dark {
            background: linear-gradient(165deg, #1a0a2e 0%, #2d1b4e 45%, #4a2c6a 100%);
            opacity: 0;
        }
        .theme-cloud {
            position: absolute;
            background: rgba(255, 255, 255, 0.92);
            border-radius: 999px;
            animation: theme-cloud-drift 6s ease-in-out infinite;
        }
        .theme-cloud--1 { width: 22px; height: 10px; top: 14px; left: 8px; animation-delay: 0s; }
        .theme-cloud--2 { width: 16px; height: 8px; top: 22px; left: 38px; animation-delay: -2s; opacity: 0.85; }
        .theme-cloud--3 { width: 14px; height: 7px; top: 10px; right: 10px; animation-delay: -4s; }
        .theme-star {
            position: absolute;
            width: 3px;
            height: 3px;
            background: #fff;
            border-radius: 50%;
            opacity: 0;
            animation: theme-star-twinkle 2.2s ease-in-out infinite;
        }
        .theme-star--1 { top: 10px; left: 14px; }
        .theme-star--2 { top: 18px; left: 32px; animation-delay: 0.4s; }
        .theme-star--3 { top: 8px; right: 22px; animation-delay: 0.8s; }
        .theme-star--4 { top: 24px; right: 12px; animation-delay: 1.2s; }
        .theme-star--5 { top: 16px; left: 52px; animation-delay: 0.6s; width: 2px; height: 2px; }
        .theme-toggle-thumb {
            position: absolute;
            top: 4px;
            left: 4px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(145deg, #ffe566 0%, #ffb347 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.55s cubic-bezier(0.34, 1.4, 0.64, 1), background 0.45s ease;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .theme-icon {
            position: absolute;
            transition: opacity 0.35s ease, transform 0.45s ease;
        }
        .theme-icon--sun {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 35%, #fff9c4, #ffca28);
            box-shadow: 0 0 0 3px rgba(255, 202, 40, 0.35);
            opacity: 1;
            transform: scale(1) rotate(0deg);
        }
        .theme-icon--sun::before {
            content: '';
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            border: 2px dashed rgba(255, 193, 7, 0.45);
            animation: theme-sun-spin 8s linear infinite;
        }
        .theme-icon--moon {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #f5f0e8;
            box-shadow: inset -4px -2px 0 0 #c9b896;
            opacity: 0;
            transform: scale(0.4) rotate(-30deg);
        }
        .theme-toggle-input:checked + .theme-toggle-track .theme-scene--light {
            opacity: 0;
            transform: scale(1.05);
        }
        .theme-toggle-input:checked + .theme-toggle-track .theme-scene--dark {
            opacity: 1;
            transform: scale(1);
        }
        .theme-toggle-input:checked + .theme-toggle-track .theme-toggle-thumb {
            transform: translateX(44px);
            background: linear-gradient(145deg, #e8e4f0 0%, #c4b8d4 100%);
        }
        .theme-toggle-input:checked + .theme-toggle-track .theme-icon--sun {
            opacity: 0;
            transform: scale(0.3) rotate(90deg);
        }
        .theme-toggle-input:checked + .theme-toggle-track .theme-icon--moon {
            opacity: 1;
            transform: scale(1) rotate(0deg);
        }
        .theme-toggle-input:checked + .theme-toggle-track .theme-star {
            opacity: 1;
        }
        @keyframes theme-cloud-drift {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(4px); }
        }
        @keyframes theme-star-twinkle {
            0%, 100% { opacity: 0.35; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        @keyframes theme-sun-spin {
            to { transform: rotate(360deg); }
        }
        /* —— Langue (UI seulement) —— */
        .lang-switch {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .lang-switch-btn {
            flex: 1 1 calc(50% - 0.5rem);
            min-width: 7rem;
            padding: 0.55rem 0.75rem;
            border: 2px solid #e8ecf0;
            border-radius: 12px;
            background: #fff;
            color: #334;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s, color 0.2s, transform 0.15s;
        }
        .lang-switch-btn:hover {
            border-color: #b8dcc4;
            background: #f4fbf6;
        }
        .lang-switch-btn.is-active {
            border-color: #2C7E34;
            background: #eef8ef;
            color: #173b2c;
        }
        .lang-switch-btn__flag {
            margin-right: 0.35rem;
        }
        .profile-page #profile-notifications-card {
            display: flex;
            flex-direction: column;
            min-height: 560px;
        }
        .profile-page #profile-notifications-card .profile-card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }
        .profile-page #profile-notifications-panel {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        .profile-page .notif-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            flex: 1;
            min-height: 420px;
            max-height: 480px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 0.4rem;
            scrollbar-gutter: stable;
        }
        .profile-page .notif-list::-webkit-scrollbar {
            width: 8px;
        }
        .profile-page .notif-list::-webkit-scrollbar-thumb {
            background: rgba(44, 126, 52, 0.35);
            border-radius: 8px;
        }
        .profile-page .notif-list::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.06);
            border-radius: 8px;
        }
        .profile-page .notif-item {
            padding: 0;
            border-radius: 14px;
            border: 1px solid #e8ecf0;
            background: #fff;
            overflow: visible;
            flex-shrink: 0;
            transition: background 0.25s, border-color 0.25s;
        }
        .profile-page .notif-item--unread {
            border-color: #64b5f6;
            background: linear-gradient(135deg, #42a5f5 0%, #1e88e5 100%);
        }
        .profile-page .notif-item--read {
            background: #fff;
            border-color: #e8ecf0;
        }
        .profile-page .notif-item__toggle {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            width: 100%;
            padding: 0.85rem 1rem;
            border: none;
            background: transparent;
            text-align: left;
            cursor: pointer;
            font-family: inherit;
        }
        .profile-page .notif-item__title {
            font-weight: 700;
            font-size: 0.92rem;
            color: #173b2c;
            margin: 0;
            flex: 1;
            min-width: 0;
            white-space: normal;
            overflow: visible;
            line-height: 1.35;
        }
        .profile-page .notif-item--unread .notif-item__title {
            color: #fff;
        }
        .profile-page .notif-item__date {
            font-size: 0.72rem;
            color: #9aa5a0;
            flex-shrink: 0;
            white-space: nowrap;
            padding-top: 0.12rem;
        }
        .profile-page .notif-item--unread .notif-item__date {
            color: rgba(255, 255, 255, 0.88);
        }
        .profile-page .notif-item__expand {
            display: none;
            padding: 0 1rem 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }
        .profile-page .notif-item.is-open .notif-item__expand {
            display: block;
        }
        .profile-page .notif-item--unread .notif-item__expand {
            border-top-color: rgba(255, 255, 255, 0.22);
        }
        .profile-page .notif-item__message {
            font-size: 0.88rem;
            color: #5c6d66;
            line-height: 1.55;
            margin: 0;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .profile-page .notif-item--unread.is-open .notif-item__message {
            color: rgba(255, 255, 255, 0.95);
        }
        .profile-page .notif-empty {
            text-align: center;
            color: #9aa5a0;
            font-size: 0.9rem;
            padding: 2rem 1rem;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .account-delete-overlay {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 26, 20, 0.45);
            backdrop-filter: blur(4px);
        }
        .account-delete-overlay[hidden] {
            display: none !important;
        }
        .account-delete-dialog {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e8ecf0;
            box-shadow: 0 20px 50px rgba(19, 30, 23, 0.2);
            padding: 1.35rem 1.5rem 1.25rem;
            animation: account-delete-pop 0.28s ease;
        }
        @keyframes account-delete-pop {
            from { opacity: 0; transform: scale(0.94) translateY(8px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .account-delete-dialog__title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #173b2c;
            margin: 0 0 0.5rem;
        }
        .account-delete-dialog__text {
            font-size: 0.9rem;
            color: #5c6d66;
            margin: 0 0 1.25rem;
            line-height: 1.5;
        }
        .account-delete-dialog__actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .pwd-step-dialog .fo-ai-widget__trigger {
            width: 100%;
        }
        .pwd-toast {
            position: fixed;
            left: 50%;
            top: 88px;
            transform: translateX(-50%) translateY(-12px);
            z-index: 2100;
            max-width: min(420px, calc(100vw - 2rem));
            padding: 1rem 1.25rem;
            border-radius: 14px;
            background: linear-gradient(135deg, #fb8c00 0%, #e65100 100%);
            color: #fff;
            font-size: 0.92rem;
            font-weight: 600;
            line-height: 1.45;
            text-align: center;
            box-shadow: 0 16px 40px rgba(230, 81, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.25);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .pwd-toast.is-visible {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        .pwd-step-overlay {
            position: fixed;
            inset: 0;
            z-index: 2050;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 26, 20, 0.45);
            backdrop-filter: blur(4px);
        }
        .pwd-step-overlay[hidden] {
            display: none !important;
        }
        .pwd-step-dialog {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e8ecf0;
            box-shadow: 0 20px 50px rgba(19, 30, 23, 0.2);
            padding: 1.5rem;
            text-align: center;
        }
        .pwd-step-dialog p {
            color: #5c6d66;
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 0 0 1.25rem;
        }
        .pwd-dev-overlay .pwd-step-dialog {
            max-width: 440px;
        }
        .pwd-dev-links {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .pwd-dev-links button {
            display: block;
            width: 100%;
            padding: 0.65rem 1rem;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.9rem;
        }
        .pwd-dev-links .pwd-dev-yes {
            background: #2C7E34;
            color: #fff;
        }
        .pwd-dev-links .pwd-dev-no {
            background: #b91c1c;
            color: #fff;
        }
        .pwd-dev-hint {
            font-size: 0.8rem;
            color: #5c6d66;
            margin: 0 0 0.75rem;
        }
        .pwd-dev-hint#pwd-dev-error {
            color: #b91c1c;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .pwd-email-fallback {
            margin-top: 1rem;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            border: 1px solid #f59e0b;
            background: rgba(245, 158, 11, 0.12);
        }
        .pwd-email-fallback__title {
            font-weight: 600;
            color: #b45309;
            margin-bottom: 0.35rem;
        }
        .pwd-change-email-notice {
            margin: 0 1rem 0.75rem;
            padding: 0.75rem 0.9rem;
            border-radius: 12px;
            border: 1px solid #f59e0b;
            background: rgba(245, 158, 11, 0.12);
            font-size: 0.85rem;
            line-height: 1.45;
            color: #92400e;
        }
        .pwd-change-email-notice strong {
            color: #b45309;
        }
        .profile-email-edit-notice {
            margin-bottom: 0.5rem;
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            border: 1px solid #fcd34d;
            background: rgba(255, 251, 235, 0.9);
            font-size: 0.84rem;
            color: #92400e;
        }
        @media (max-width: 991.98px) {
            .profile-page .settings-col-right .profile-card {
                position: static;
            }
        }
    </style>
</head>
<body class="profile-page">

<?php
$nav_active = 'profile';
require __DIR__ . '/includes/nav_front.php';
?>

<main class="commande-wrap">
    <div class="container py-4 py-lg-5">
        <div class="text-center mb-4">
            <h1 class="fw-bold" style="color: #173b2c;"><?php echo fo_e('profile.title'); ?></h1>
            <p class="page-lead mx-auto"><?php echo fo_e('profile.page_lead'); ?></p>
        </div>

        <div class="row g-4 settings-layout">
            <div class="col-lg-7 settings-col-left">
                <div class="profile-card">
                    <div class="profile-card-header"><?php echo fo_e('profile.appearance'); ?></div>
                    <div class="profile-card-body">
                        <div class="pref-row">
                            <div>
                                <div class="pref-label"><?php echo fo_e('profile.appearance'); ?></div>
                                <p class="pref-hint"><?php echo fo_e('profile.appearance_hint'); ?></p>
                            </div>
                            <label class="theme-toggle" title="<?php echo fo_e('profile.toggle_theme'); ?>">
                                <input type="checkbox" class="theme-toggle-input" id="theme-toggle-demo" aria-label="<?php echo fo_e('profile.dark_mode'); ?>"<?php echo $profileMode === 'dark' ? ' checked' : ''; ?>>
                                <span class="theme-toggle-track">
                                    <span class="theme-scene theme-scene--light" aria-hidden="true">
                                        <span class="theme-cloud theme-cloud--1"></span>
                                        <span class="theme-cloud theme-cloud--2"></span>
                                        <span class="theme-cloud theme-cloud--3"></span>
                                    </span>
                                    <span class="theme-scene theme-scene--dark" aria-hidden="true">
                                        <span class="theme-star theme-star--1"></span>
                                        <span class="theme-star theme-star--2"></span>
                                        <span class="theme-star theme-star--3"></span>
                                        <span class="theme-star theme-star--4"></span>
                                        <span class="theme-star theme-star--5"></span>
                                    </span>
                                    <span class="theme-toggle-thumb">
                                        <span class="theme-icon theme-icon--sun" aria-hidden="true"></span>
                                        <span class="theme-icon theme-icon--moon" aria-hidden="true"></span>
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="profile-card-header"><?php echo fo_e('profile.photo'); ?></div>
                    <div class="profile-card-body profile-photo-wrap">
                        <?php if (is_string($photoSrc) && $photoSrc !== ''): ?>
                            <img class="profile-photo-img" src="<?php echo htmlspecialchars($photoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                        <?php else: ?>
                            <div class="profile-photo-placeholder" aria-hidden="true"><?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <p class="text-muted small mt-3 mb-0"><?php echo fo_e('profile.photo_hint'); ?></p>
                    </div>
                </div>

                <div class="profile-card" id="profile-personal-card">
                    <div class="profile-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span><?php echo fo_e('profile.personal_info'); ?></span>
                        <button type="button" class="btn btn-sm btn-profile-primary" id="btn-toggle-profile"><?php echo fo_e('common.edit'); ?></button>
                    </div>
                    <div class="profile-card-body">
                        <div class="info-row">
                            <div class="info-label"><?php echo fo_e('profile.name'); ?></div>
                            <div class="info-value"><?php echo htmlspecialchars(trim($prenom . ' ' . $nom), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><?php echo fo_e('profile.email'); ?></div>
                            <div class="info-value"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><?php echo fo_e('profile.role'); ?></div>
                            <div class="info-value"><?php echo htmlspecialchars($role !== '' ? $role : 'client', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <?php if ($hasDescription): ?>
                            <div class="info-row border-bottom-0">
                                <div class="info-label"><?php echo fo_e('profile.description'); ?></div>
                                <div class="info-value" style="font-weight:500;"><?php echo nl2br(htmlspecialchars($description !== '' ? $description : '—', ENT_QUOTES, 'UTF-8')); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($hasBudget): ?>
                            <div class="info-row border-bottom-0">
                                <div class="info-label"><?php echo fo_e('profile.budget'); ?></div>
                                <div class="info-value"><?php echo $budget !== null && $budget !== '' ? htmlspecialchars((string) $budget, ENT_QUOTES, 'UTF-8') . ' DT' : '—'; ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="edit-panel" id="panel-edit-profile">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="mb-3">
                                <label class="form-label"><?php echo fo_e('profile.photo_optional'); ?></label>
                                <input type="file" name="profile_photo" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" id="input-profile-photo">
                                <div class="mt-2" id="photo-preview-wrap" style="display:none;">
                                    <img id="photo-preview-img" alt="" style="max-width:120px;max-height:120px;border-radius:50%;border:2px solid #ddd;">
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label"><?php echo fo_e('auth.first_name'); ?></label>
                                    <input type="text" name="prenom" class="form-control" required value="<?php echo htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label"><?php echo fo_e('auth.last_name'); ?></label>
                                    <input type="text" name="nom" class="form-control" required value="<?php echo htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label"><?php echo fo_e('profile.email'); ?></label>
                                <p class="profile-email-edit-notice" role="note"><?php echo fo_e('profile.email_edit_warning'); ?></p>
                                <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <?php if ($hasDescription): ?>
                                <div class="mb-2">
                                    <label class="form-label"><?php echo fo_e('profile.description'); ?></label>
                                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                            <?php endif; ?>
                            <?php if ($hasBudget): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo fo_e('profile.budget'); ?> (DT)</label>
                                    <input type="number" name="budget" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars((string) ($budget ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-profile-primary"><?php echo fo_e('common.save'); ?></button>
                                <button type="button" class="btn btn-profile-muted" id="btn-cancel-profile"><?php echo fo_e('common.cancel'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="profile-card-header"><?php echo fo_e('profile.translate'); ?></div>
                    <div class="profile-card-body">
                        <div class="lang-switch" role="group" aria-label="<?php echo fo_e('profile.translate'); ?>">
                            <?php foreach (user_settings_supported_languages() as $code => $label) { ?>
                            <button type="button" class="lang-switch-btn<?php echo $profileLang === $code ? ' is-active' : ''; ?>" data-lang="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>"><span class="lang-switch-btn__flag" aria-hidden="true"><?php echo $code === 'fr' ? '🇫🇷' : '🇬🇧'; ?></span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></button>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="profile-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span><?php echo fo_e('profile.security'); ?></span>
                        <button type="button" class="btn btn-sm btn-profile-primary" id="btn-toggle-password"><?php echo fo_e('profile.change_password'); ?></button>
                    </div>
                    <p class="pwd-change-email-notice" role="note">
                        <?php
                        echo htmlspecialchars(
                            sprintf(fo_t('profile.password_change_email_warning'), $email),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                    </p>
                    <div class="edit-panel" id="panel-password">
                        <form id="form-password-change" method="post" novalidate>
                            <div class="mb-2">
                                <label class="form-label"><?php echo fo_e('profile.current_password'); ?></label>
                                <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                            </div>
                            <div class="mb-2">
                                <label class="form-label"><?php echo fo_e('profile.new_password'); ?></label>
                                <input type="password" name="new_password" class="form-control" required autocomplete="new-password" minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?php echo fo_e('profile.confirm_password'); ?></label>
                                <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password" minlength="6">
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-profile-primary"><?php echo fo_e('profile.save_password'); ?></button>
                                <button type="button" class="btn btn-profile-muted" id="btn-cancel-password"><?php echo fo_e('common.cancel'); ?></button>
                            </div>
                        </form>
                        <div id="pwd-email-fallback" class="pwd-email-fallback" hidden>
                            <p class="pwd-dev-hint pwd-email-fallback__title">Pas d&rsquo;email dans Gmail ?</p>
                            <p class="pwd-dev-hint">En local, Google bloque souvent les messages avec des liens <code>localhost</code>. Confirmez l&rsquo;&eacute;tape 1 ici&nbsp;:</p>
                            <div class="pwd-dev-links" id="pwd-fallback-links"></div>
                        </div>
                    </div>
                </div>

                <div class="profile-card border-danger" style="border-color: #fecaca;">
                    <div class="profile-card-header" style="color:#991b1b;"><?php echo fo_e('profile.sensitive'); ?></div>
                    <div class="profile-card-body">
                        <p class="text-muted small mb-3"><?php echo fo_e('profile.delete_warning'); ?></p>
                        <button type="button" class="btn-profile-danger" id="btn-open-delete"><?php echo fo_e('profile.delete_account'); ?></button>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 settings-col-right">
                <div class="profile-card" id="profile-notifications-card">
                    <div class="profile-card-header"><?php echo fo_e('profile.notifications'); ?></div>
                    <div class="profile-card-body" id="profile-notifications-panel">
                        <?php if ($profileNotifications === []) { ?>
                            <p class="notif-empty"><?php echo fo_e('profile.no_notifications'); ?></p>
                        <?php } else { ?>
                            <div class="notif-list" role="list" id="profile-notifications-list">
                                <?php foreach ($profileNotifications as $notif) {
                                    $isUnread = empty($notif['lu']) || (int) $notif['lu'] === 0;
                                    $notifId = (int) ($notif['id_notification'] ?? 0);
                                    ?>
                                    <article class="notif-item<?php echo $isUnread ? ' notif-item--unread' : ' notif-item--read'; ?>"
                                             role="listitem"
                                             data-notif-id="<?php echo $notifId; ?>"
                                             data-read="<?php echo $isUnread ? '0' : '1'; ?>">
                                        <button type="button" class="notif-item__toggle" aria-expanded="false">
                                            <h3 class="notif-item__title"><?php echo fo_db_e((string) ($notif['titre'] ?? '')); ?></h3>
                                            <time class="notif-item__date" datetime="<?php echo htmlspecialchars((string) ($notif['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(fo_notification_format_date((string) ($notif['created_at'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></time>
                                        </button>
                                        <div class="notif-item__expand">
                                            <p class="notif-item__message"><?php echo fo_db_e((string) ($notif['message'] ?? '')); ?></p>
                                        </div>
                                    </article>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<footer>
    © 2026 HappyBite
</footer>

<div id="account-delete-overlay" class="account-delete-overlay" hidden aria-hidden="true">
    <div class="account-delete-dialog" role="dialog" aria-labelledby="account-delete-title" aria-modal="true">
        <h2 id="account-delete-title" class="account-delete-dialog__title">Supprimer le compte ?</h2>
        <p class="account-delete-dialog__text">Êtes-vous sûr de vouloir supprimer définitivement ce compte ? Cette action est irréversible.</p>
        <form method="post" class="account-delete-dialog__actions">
            <input type="hidden" name="action" value="delete_account">
            <button type="button" class="btn btn-profile-muted" id="btn-cancel-delete">Annuler</button>
            <button type="submit" class="btn btn-profile-danger" style="width:auto;">Oui</button>
        </form>
    </div>
</div>

<div id="pwd-step-overlay" class="pwd-step-overlay" hidden aria-hidden="true">
    <div class="pwd-step-dialog" role="dialog" aria-modal="true">
        <p id="pwd-step-text">Merci pour la vérification. Il ne reste qu'une dernière étape pour confirmer que c'est bien vous.</p>
        <button type="button" class="fo-ai-widget__trigger" id="btn-pwd-face-id" aria-label="Scanner avec Face ID">
            <img src="images/face-id.png" alt="" class="fo-ai-widget__icon" width="20" height="20">
            <span class="fo-ai-widget__label">Scanner avec Face ID</span>
        </button>
    </div>
</div>

<div id="pwd-dev-overlay" class="pwd-step-overlay pwd-dev-overlay" hidden aria-hidden="true">
    <div class="pwd-step-dialog" role="dialog" aria-modal="true">
        <p class="pwd-dev-hint" id="pwd-dev-error"></p>
        <p class="pwd-dev-hint">Email non envoyé — utilisez les boutons ci-dessous en local, ou corrigez <code>config/mail.php</code>.</p>
        <div class="pwd-dev-links" id="pwd-dev-links"></div>
    </div>
</div>

<?php require __DIR__ . '/includes/face_scan_modal.php'; ?>

<script src="/Views/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/auth-face.js?v=6"></script>
<script>
(function () {
    var showPwdFaceStepOnce = <?php echo $showPwdFaceStepOnce ? 'true' : 'false'; ?>;
    var pwdPendingEmailVerified = <?php echo $pwdPendingEmailVerified ? 'true' : 'false'; ?>;
    var pwdChangePendingActive = <?php echo $pwdChangePendingActive ? 'true' : 'false'; ?>;
    var pwdFaceAuthEnrolled = <?php echo $pwdUserHasFaceAuth ? 'true' : 'false'; ?>;
    var pwdReadyToFinalizeOnLoad = <?php echo $pwdReadyToFinalizeOnLoad ? 'true' : 'false'; ?>;
    var pwdFinalizeInFlight = false;
    var pwdFinalizeDone = false;
    var pwdFlashNotYou = <?php echo $pwdFlashNotYou ? 'true' : 'false'; ?>;
    var pwdFlashLogout = <?php echo $pwdFlashLogout ? 'true' : 'false'; ?>;
    var pwdFlashExpired = <?php echo $pwdFlashExpired ? 'true' : 'false'; ?>;
    var userEmail = <?php echo json_encode($email, JSON_UNESCAPED_UNICODE); ?>;
    var authProcessUrl = new URL('../../Controllers/AuthProcess.php', window.location.href).href;

    if (window.location.search.indexOf('pwd_flash=') !== -1 || window.location.search.indexOf('pwd_token=') !== -1) {
        history.replaceState(null, '', window.location.pathname);
    }

    function handlePwdEmailAnswer(answer, flash, doLogout) {
        closePwdDevOverlay();
        if (answer === 'no' || flash === 'not_you' || doLogout) {
            showPwdToast('Désolé, mais apparemment ce n\'est pas vous.', 3000);
            setTimeout(function () {
                window.location.href = '../../Controllers/AuthProcess.php?action=logout';
            }, 3000);
            return;
        }
        if (flash === 'expired') {
            showPwdToast('Lien expiré. Recommencez le changement de mot de passe.', 3500);
            return;
        }
        if (answer === 'yes' || flash === 'email_ok') {
            pwdStepOpenedFromPoll = true;
            stopPwdChangePoll();
            if (pwdFaceAuthEnrolled) {
                openPwdStepOverlay();
                showPwdToast('Email confirmé — étape 2 : Face ID.', 3500);
            } else {
                finalizePasswordChange();
            }
        }
    }

    var pwdEmailStorageKey = 'hb_pwd_email_result';

    function applyPwdEmailPayload(data) {
        if (!data || data.type !== 'hb_pwd_email') {
            return;
        }
        handlePwdEmailAnswer(data.answer || '', data.flash || '', !!data.logout);
    }

    function consumePwdEmailFromStorage() {
        try {
            var raw = localStorage.getItem(pwdEmailStorageKey);
            if (!raw) {
                return;
            }
            localStorage.removeItem(pwdEmailStorageKey);
            applyPwdEmailPayload(JSON.parse(raw));
        } catch (err) {}
    }

    window.addEventListener('storage', function (e) {
        if (e.key !== pwdEmailStorageKey || !e.newValue) {
            return;
        }
        try {
            localStorage.removeItem(pwdEmailStorageKey);
            applyPwdEmailPayload(JSON.parse(e.newValue));
        } catch (err) {}
    });

    window.addEventListener('message', function (e) {
        if (!e.data || e.data.type !== 'hb_pwd_email') {
            return;
        }
        if (e.origin !== window.location.origin) {
            return;
        }
        applyPwdEmailPayload(e.data);
    });

    consumePwdEmailFromStorage();

    var pwdPollTimer = null;
    var pwdStepOpenedFromPoll = false;

    function stopPwdChangePoll() {
        if (pwdPollTimer) {
            clearInterval(pwdPollTimer);
            pwdPollTimer = null;
        }
    }

    function pollPwdChangeStatus() {
        var body = new URLSearchParams();
        body.set('action', 'password_change_status');
        return fetch(authProcessUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body: body
        })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (!json || !json.ok || !json.active) {
                    stopPwdChangePoll();
                    return;
                }
                if (json.ready_to_finalize) {
                    stopPwdChangePoll();
                    if (!pwdFinalizeDone && !pwdFinalizeInFlight) {
                        finalizePasswordChange();
                    }
                    return;
                }
                if (json.email_verified && json.face_required && !json.face_verified && !pwdStepOpenedFromPoll) {
                    pwdStepOpenedFromPoll = true;
                    openPwdStepOverlay();
                    showPwdToast('Email confirmé — étape 2 : Face ID.', 3500);
                }
                if (json.face_verified) {
                    stopPwdChangePoll();
                }
            })
            .catch(function () {});
    }

    function startPwdChangePoll() {
        stopPwdChangePoll();
        pollPwdChangeStatus();
        pwdPollTimer = setInterval(pollPwdChangeStatus, 2000);
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && (pwdChangePendingActive || pwdPollTimer)) {
            pollPwdChangeStatus();
        }
    });
    window.addEventListener('focus', function () {
        if (pwdChangePendingActive || pwdPollTimer) {
            pollPwdChangeStatus();
        }
    });

    function showPwdToast(text, durationMs) {
        if (typeof window.hbShowActionToast === 'function') {
            window.hbShowActionToast(text, durationMs || 3000);
        }
    }

    function closePwdStepOverlay() {
        ['pwd-step-overlay', 'pwd-dev-overlay'].forEach(function (id) {
            var o = document.getElementById(id);
            if (o) {
                o.hidden = true;
                o.setAttribute('aria-hidden', 'true');
            }
        });
    }

    function openPwdStepOverlay() {
        if (!pwdFaceAuthEnrolled) {
            return;
        }
        var o = document.getElementById('pwd-step-overlay');
        if (o) {
            o.hidden = false;
            o.setAttribute('aria-hidden', 'false');
        }
    }

    function schedulePasswordChangedNotification() {
        window.setTimeout(function () {
            var pollUrl = (window.HB_NOTIF_POLL_URL || 'api/notifications_poll.php') + '?list=1';
            fetch(pollUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data || !data.ok) {
                        return;
                    }
                    if (typeof window.hbApplyNotificationUnread === 'function') {
                        window.hbApplyNotificationUnread(data.unread, true);
                    }
                })
                .catch(function () {});
        }, 3000);
    }

    function finalizePasswordChange() {
        if (pwdFinalizeInFlight || pwdFinalizeDone) {
            return;
        }
        pwdFinalizeInFlight = true;
        stopPwdChangePoll();

        var formPwd = document.getElementById('form-password-change');
        var body = new URLSearchParams();
        body.set('action', 'password_change_finalize');
        fetch(authProcessUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body: body
        })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                pwdFinalizeInFlight = false;
                if (json && json.ok) {
                    pwdFinalizeDone = true;
                    pwdChangePendingActive = false;
                    closePwdStepOverlay();
                    showPwdToast('Mot de passe modifié avec succès.', 3000);
                    schedulePasswordChangedNotification();
                    if (formPwd) {
                        formPwd.reset();
                    }
                    var panelPwd = document.getElementById('panel-password');
                    if (panelPwd) {
                        panelPwd.classList.remove('is-open');
                    }
                    if (typeof window.hbRefreshProfileNotificationsList === 'function') {
                        window.setTimeout(window.hbRefreshProfileNotificationsList, 3200);
                    }
                    return;
                }
                var err = (json && json.error) ? String(json.error) : '';
                if (pwdFinalizeDone || err.indexOf('incomplète') !== -1 || err.indexOf('incomplete') !== -1) {
                    return;
                }
                showPwdToast(err || 'Erreur lors de la mise à jour.', 3500);
            })
            .catch(function () {
                pwdFinalizeInFlight = false;
                if (!pwdFinalizeDone) {
                    showPwdToast('Erreur réseau.', 3000);
                }
            });
    }

    function confirmPwdEmailAnswer(answer) {
        var body = new URLSearchParams();
        body.set('action', 'password_email_answer');
        body.set('answer', answer);
        return fetch(authProcessUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        }).then(function (r) {
            return r.json();
        });
    }

    function mountPwdDevButtons(wrap, onAnswer) {
        if (!wrap) {
            return;
        }
        wrap.textContent = '';
        var yesBtn = document.createElement('button');
        yesBtn.type = 'button';
        yesBtn.className = 'pwd-dev-yes';
        yesBtn.textContent = 'Oui, c\'est moi';
        yesBtn.addEventListener('click', function () {
            confirmPwdEmailAnswer('yes').then(function (json) {
                onAnswer(json, 'yes');
            });
        });
        var noBtn = document.createElement('button');
        noBtn.type = 'button';
        noBtn.className = 'pwd-dev-no';
        noBtn.textContent = 'Non, ce n\'est pas moi';
        noBtn.addEventListener('click', function () {
            confirmPwdEmailAnswer('no').then(function (json) {
                onAnswer(json, 'no');
            });
        });
        wrap.appendChild(yesBtn);
        wrap.appendChild(noBtn);
    }

    function onPwdEmailConfirm(json, answer) {
        if (!json) {
            (window.hbAlert || alert)('Erreur');
            return;
        }
        if (!json.ok) {
            if (json.flash === 'expired') {
                handlePwdEmailAnswer('', 'expired', false);
                return;
            }
            (window.hbAlert || alert)(json.error || 'Erreur');
            return;
        }
        var fb = document.getElementById('pwd-email-fallback');
        if (fb) {
            fb.hidden = true;
        }
        handlePwdEmailAnswer(answer, json.flash || (answer === 'yes' ? 'email_ok' : 'not_you'), !!json.logout);
        if (answer === 'yes' && json.ok) {
            stopPwdChangePoll();
        }
    }

    function showPwdInlineFallback() {
        var box = document.getElementById('pwd-email-fallback');
        var wrap = document.getElementById('pwd-fallback-links');
        if (!box || !wrap) {
            return;
        }
        mountPwdDevButtons(wrap, onPwdEmailConfirm);
        box.hidden = false;
        document.getElementById('panel-password').classList.add('is-open');
    }

    function openPwdDevOverlay(mailError) {
        var o = document.getElementById('pwd-dev-overlay');
        var wrap = document.getElementById('pwd-dev-links');
        var errEl = document.getElementById('pwd-dev-error');
        if (!o || !wrap) {
            return;
        }
        if (errEl) {
            errEl.textContent = mailError || '';
            errEl.hidden = !mailError;
        }
        mountPwdDevButtons(wrap, onPwdEmailConfirm);
        o.hidden = false;
        o.setAttribute('aria-hidden', 'false');
    }

    if (pwdFlashNotYou && pwdFlashLogout) {
        showPwdToast('Désolé, mais apparemment ce n\'est pas vous.', 3000);
        setTimeout(function () {
            window.location.href = '../../Controllers/AuthProcess.php?action=logout';
        }, 3000);
    } else if (pwdFlashExpired) {
        showPwdToast('Lien expiré. Recommencez le changement de mot de passe.', 3500);
    } else if (pwdReadyToFinalizeOnLoad) {
        finalizePasswordChange();
    } else if (showPwdFaceStepOnce || pwdPendingEmailVerified) {
        openPwdStepOverlay();
        if (pwdPendingEmailVerified || showPwdFaceStepOnce) {
            showPwdToast('Email confirmé — étape 2 : Face ID.', 3500);
        }
    }

    if (pwdChangePendingActive && !pwdPendingEmailVerified && !pwdReadyToFinalizeOnLoad) {
        startPwdChangePoll();
    }

    window.addEventListener('pagehide', function () {
        if (!pwdChangePendingActive) {
            return;
        }
        var body = new URLSearchParams();
        body.set('action', 'password_change_cancel');
        if (navigator.sendBeacon) {
            navigator.sendBeacon(authProcessUrl, body);
        } else {
            fetch(authProcessUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                keepalive: true,
                body: body
            });
        }
    });

    var formPwd = document.getElementById('form-password-change');
    if (formPwd) {
        formPwd.addEventListener('submit', function (e) {
            e.preventDefault();
            var body = new URLSearchParams();
            body.set('action', 'password_change_request');
            body.set('current_password', formPwd.current_password.value);
            body.set('new_password', formPwd.new_password.value);
            body.set('confirm_password', formPwd.confirm_password.value);
            fetch(authProcessUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (!json || !json.ok) {
                        (window.hbAlert || alert)((json && json.error) || 'Erreur');
                        return;
                    }
                    if (json.mail_sent === false || (json.dev_links && !json.local_fallback)) {
                        openPwdDevOverlay(json.mail_error || json.message || '');
                    } else {
                        var toastMsg = json.message || ('Email envoyé à ' + (json.sent_to || userEmail) + '.');
                        showPwdToast(toastMsg, 8000);
                        if (json.dev_links && json.local_fallback) {
                            showPwdInlineFallback();
                        }
                    }
                    startPwdChangePoll();
                    if (!json.local_fallback) {
                        document.getElementById('panel-password').classList.remove('is-open');
                    }
                })
                .catch(function () {
                    (window.hbAlert || alert)('Erreur réseau.');
                });
        });
    }

    var btnPwdFace = document.getElementById('btn-pwd-face-id');
    if (btnPwdFace && window.HappyBiteAuthFace) {
        btnPwdFace.addEventListener('click', function () {
            closePwdStepOverlay();
            HappyBiteAuthFace.runPasswordVerify(function () {
                return userEmail;
            }, function (ok, data) {
                if (!ok) {
                    openPwdStepOverlay();
                    if (data && data.error) {
                        showPwdToast(data.error, 3500);
                    }
                    return;
                }
                finalizePasswordChange();
            });
        });
    }
})();
</script>
<script>
(function () {
    var panelProfile = document.getElementById('panel-edit-profile');
    var panelPwd = document.getElementById('panel-password');
    var btnProfile = document.getElementById('btn-toggle-profile');
    var btnCancelProfile = document.getElementById('btn-cancel-profile');
    var btnPwd = document.getElementById('btn-toggle-password');
    var btnCancelPwd = document.getElementById('btn-cancel-password');

    function closeAll() {
        panelProfile.classList.remove('is-open');
        panelPwd.classList.remove('is-open');
    }

    if (btnProfile) {
        btnProfile.addEventListener('click', function () {
            closeAll();
            panelProfile.classList.toggle('is-open');
        });
    }
    if (btnCancelProfile) {
        btnCancelProfile.addEventListener('click', function () {
            panelProfile.classList.remove('is-open');
        });
    }
    if (btnPwd) {
        btnPwd.addEventListener('click', function () {
            closeAll();
            panelPwd.classList.toggle('is-open');
        });
    }
    if (btnCancelPwd) {
        btnCancelPwd.addEventListener('click', function () {
            panelPwd.classList.remove('is-open');
        });
    }

    var inputPhoto = document.getElementById('input-profile-photo');
    if (inputPhoto) {
        inputPhoto.addEventListener('change', function () {
            var f = inputPhoto.files && inputPhoto.files[0];
            var wrap = document.getElementById('photo-preview-wrap');
            var img = document.getElementById('photo-preview-img');
            if (!f || !f.type.match(/^image\//)) {
                if (wrap) wrap.style.display = 'none';
                return;
            }
            var r = new FileReader();
            r.onload = function (e) {
                img.src = e.target.result;
                wrap.style.display = 'block';
            };
            r.readAsDataURL(f);
        });
    }

    var deleteOverlay = document.getElementById('account-delete-overlay');
    var btnOpenDel = document.getElementById('btn-open-delete');
    var btnCancelDel = document.getElementById('btn-cancel-delete');

    function openDeleteDialog() {
        if (!deleteOverlay) {
            return;
        }
        deleteOverlay.hidden = false;
        deleteOverlay.setAttribute('aria-hidden', 'false');
    }

    function closeDeleteDialog() {
        if (!deleteOverlay) {
            return;
        }
        deleteOverlay.hidden = true;
        deleteOverlay.setAttribute('aria-hidden', 'true');
    }

    if (btnOpenDel) {
        btnOpenDel.addEventListener('click', openDeleteDialog);
    }
    if (btnCancelDel) {
        btnCancelDel.addEventListener('click', closeDeleteDialog);
    }
    if (deleteOverlay) {
        deleteOverlay.addEventListener('click', function (e) {
            if (e.target === deleteOverlay) {
                closeDeleteDialog();
            }
        });
    }

    document.querySelectorAll('.lang-switch-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var lang = btn.getAttribute('data-lang');
            if (!lang || btn.classList.contains('is-active')) {
                return;
            }
            var body = new URLSearchParams();
            body.set('language', lang);
            body.set('mode', document.documentElement.getAttribute('data-hb-mode') || '<?php echo addslashes($profileMode); ?>');
            fetch('api/settings_save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: body
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.ok) {
                        window.location.reload();
                        return;
                    }
                    (window.hbAlert || alert)('<?php echo addslashes(fo_t('profile.lang_error')); ?>');
                })
                .catch(function () {
                    (window.hbAlert || alert)('<?php echo addslashes(fo_t('profile.lang_error')); ?>');
                });
        });
    });

    bindProfileNotificationToggles(document);

    function bindProfileNotificationToggles(root) {
        if (!root) {
            return;
        }
        root.querySelectorAll('.notif-item__toggle').forEach(function (btn) {
            if (btn.dataset.hbBound === '1') {
                return;
            }
            btn.dataset.hbBound = '1';
            btn.addEventListener('click', function () {
                var item = btn.closest('.notif-item');
                if (!item) {
                    return;
                }
                var open = item.classList.toggle('is-open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (!open || item.dataset.read !== '0') {
                    return;
                }
                var id = parseInt(item.getAttribute('data-notif-id') || '0', 10);
                if (id < 1) {
                    return;
                }
                var body = new URLSearchParams();
                body.set('action', 'notif_mark_read');
                body.set('id_notification', String(id));
                fetch(window.location.pathname, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!data || !data.ok) {
                            return;
                        }
                        item.dataset.read = '1';
                        item.classList.remove('notif-item--unread');
                        item.classList.add('notif-item--read');
                        fetch((window.HB_NOTIF_POLL_URL || 'api/notifications_poll.php'), {
                            credentials: 'same-origin',
                            headers: { Accept: 'application/json' }
                        })
                            .then(function (r) { return r.json(); })
                            .then(function (poll) {
                                if (poll && typeof window.hbApplyNotificationUnread === 'function') {
                                    window.hbApplyNotificationUnread(poll.unread, false);
                                }
                            })
                            .catch(function () {});
                    })
                    .catch(function () {});
            });
        });
    }

    window.hbRefreshProfileNotificationsList = function () {
        var pollUrl = (window.HB_NOTIF_POLL_URL || 'api/notifications_poll.php') + '?list=1';
        fetch(pollUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok) {
                    return;
                }
                var panel = document.getElementById('profile-notifications-panel');
                if (!panel) {
                    return;
                }
                if (!data.items || data.items.length === 0) {
                    panel.innerHTML = '<p class="notif-empty"><?php echo addslashes(fo_t('profile.no_notifications')); ?></p>';
                    if (typeof window.hbApplyNotificationUnread === 'function') {
                        window.hbApplyNotificationUnread(0, false);
                    }
                    return;
                }
                var html = '<div class="notif-list" role="list" id="profile-notifications-list">';
                data.items.forEach(function (n) {
                    var unread = !n.lu || parseInt(n.lu, 10) === 0;
                    html += '<article class="notif-item' + (unread ? ' notif-item--unread' : ' notif-item--read') + '" role="listitem" data-notif-id="' + n.id + '" data-read="' + (unread ? '0' : '1') + '">';
                    html += '<button type="button" class="notif-item__toggle" aria-expanded="false">';
                    html += '<h3 class="notif-item__title"></h3>';
                    html += '<time class="notif-item__date"></time></button>';
                    html += '<div class="notif-item__expand"><p class="notif-item__message"></p></div></article>';
                });
                html += '</div>';
                panel.innerHTML = html;
                panel.querySelectorAll('.notif-item').forEach(function (item, idx) {
                    var n = data.items[idx];
                    var title = item.querySelector('.notif-item__title');
                    var dateEl = item.querySelector('.notif-item__date');
                    var msg = item.querySelector('.notif-item__message');
                    if (title) title.textContent = n.titre || '';
                    if (dateEl) {
                        dateEl.textContent = n.date_label || '';
                        dateEl.setAttribute('datetime', n.created_at || '');
                    }
                    if (msg) msg.textContent = n.message || '';
                });
                bindProfileNotificationToggles(panel);
                if (typeof window.hbApplyNotificationUnread === 'function') {
                    window.hbApplyNotificationUnread(data.unread, false);
                }
                syncNotificationsPanelHeight();
            })
            .catch(function () {});
    };

    function syncNotificationsPanelHeight() {
        var personal = document.getElementById('profile-personal-card');
        var notifCard = document.getElementById('profile-notifications-card');
        var notifList = document.getElementById('profile-notifications-list');
        if (!notifCard) {
            return;
        }
        var minCardH = 560;
        var personalH = personal ? personal.offsetHeight : 0;
        var targetH = Math.max(minCardH, personalH);
        notifCard.style.minHeight = targetH + 'px';
        if (notifList) {
            var headerH = notifCard.querySelector('.profile-card-header');
            var bodyPad = 36;
            var headH = headerH ? headerH.offsetHeight : 48;
            var listH = Math.max(420, targetH - headH - bodyPad);
            notifList.style.minHeight = '420px';
            notifList.style.maxHeight = listH + 'px';
        }
    }

    syncNotificationsPanelHeight();
    window.addEventListener('resize', syncNotificationsPanelHeight);
    window.addEventListener('load', syncNotificationsPanelHeight);
})();
</script>
<?php
require_once __DIR__ . '/includes/hb_action_toast.php';
if ($message !== '') {
    hb_action_toast_script($message);
}
if ($error !== '') {
    hb_action_toast_script($error, 4500);
}
?>
</body>
</html>
