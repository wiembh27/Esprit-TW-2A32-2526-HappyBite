<?php declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/fo_i18n.php';
fo_init_i18n_for_request();

if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ../Home.php');
    exit;
}

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
$pendingFaceEmail = $_SESSION['just_registered_email'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../includes/hb_brand_head.php'; hb_brand_render_head('../'); ?>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HappyBite — <?php echo fo_e('auth.login_page_title'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="auth-layout.css">
</head>
<body class="auth-page">
<div class="auth-split">
    <aside class="auth-brand" aria-hidden="true">
        <div class="auth-brand__bg"></div>
        <div class="auth-brand__inner">
            <h1><?php echo fo_e('auth.welcome_title'); ?></h1>
            <p><?php echo fo_e('auth.welcome_login_desc'); ?></p>
        </div>
    </aside>
    <main class="auth-panel">
        <div class="auth-card">
            <h2 class="auth-card__title"><?php echo fo_e('auth.login_heading'); ?></h2>

            <?php
            require_once __DIR__ . '/../includes/fo_demo_guide.php';
            fo_render_auth_demo_guide();
            ?>

            <?php if ($pendingFaceEmail !== ''): ?>
                <div class="auth-banner">
                    <?php echo sprintf(fo_t('auth.face_enroll_banner'), '<strong>' . htmlspecialchars($pendingFaceEmail, ENT_QUOTES, 'UTF-8') . '</strong>'); ?>
                </div>
                <button type="button" class="auth-btn-faceid" id="face-enroll-pending" data-email="<?php echo htmlspecialchars($pendingFaceEmail, ENT_QUOTES, 'UTF-8'); ?>">
                    <img src="../images/face-id.png" alt="" class="auth-btn-faceid__icon" width="20" height="20">
                    <span class="auth-btn-faceid__label"><?php echo fo_e('auth.face_enroll_btn'); ?></span>
                </button>
            <?php endif; ?>

            <form method="POST" action="../../Controllers/AuthProcess.php">
                <input type="hidden" name="action" value="login">
                <div class="auth-field">
                    <label for="email-pwd"><?php echo fo_e('auth.email'); ?></label>
                    <input type="email" name="email" required id="email-pwd" autocomplete="username" placeholder="<?php echo fo_e('auth.email_ph'); ?>">
                </div>
                <div class="auth-field">
                    <label for="pwd"><?php echo fo_e('auth.password'); ?></label>
                    <input type="password" name="password" required id="pwd" autocomplete="current-password" placeholder="<?php echo fo_e('auth.password_ph'); ?>">
                </div>
                <button type="submit" class="auth-btn-primary"><?php echo fo_e('auth.login_btn'); ?></button>
            </form>

            <div class="auth-divider">
                <p><?php echo fo_e('auth.face_section'); ?></p>
                <div class="auth-field">
                    <label for="email-faceid"><?php echo fo_e('auth.face_email'); ?></label>
                    <input type="email" id="email-faceid" placeholder="<?php echo fo_e('auth.email_ph'); ?>" autocomplete="username">
                </div>
                <button type="button" class="auth-btn-faceid" id="face-login">
                    <img src="../images/face-id.png" alt="" class="auth-btn-faceid__icon" width="20" height="20">
                    <span class="auth-btn-faceid__label"><?php echo fo_e('auth.face_login_btn'); ?></span>
                </button>
                <p class="auth-hint"><?php echo fo_e('auth.face_hint'); ?></p>
            </div>

            <p class="auth-footer-links"><?php echo fo_e('auth.no_account'); ?> <a href="register.php"><?php echo fo_e('auth.create_account'); ?></a></p>
            <p class="auth-back"><a href="../Home.php"><?php echo fo_e('auth.back_site'); ?></a></p>
        </div>
    </main>
</div>
<?php
require_once __DIR__ . '/../includes/face_scan_modal.php';
require_once __DIR__ . '/../includes/hb_action_toast.php';
hb_action_toast_render('../');
if ($error !== '') {
    hb_action_toast_script($error, 4500);
}
if ($success !== '') {
    hb_action_toast_script($success);
}
?>
<script src="../js/auth-face.js?v=6"></script>
<script>
    (function () {
        var savedEmail = localStorage.getItem('happybite_faceid_email');
        if (savedEmail) {
            var ef = document.getElementById('email-faceid');
            var ep = document.getElementById('email-pwd');
            if (ef) ef.value = savedEmail;
            if (ep && !ep.value) ep.value = savedEmail;
        }
        document.querySelector('form').addEventListener('submit', function () {
            var email = document.getElementById('email-pwd').value;
            if (email) localStorage.setItem('happybite_faceid_email', email);
        });
        document.getElementById('face-login').addEventListener('click', function (e) {
            e.preventDefault();
            var email = document.getElementById('email-faceid').value.trim();
            if (!email) {
                (window.hbAlert || alert)(<?php echo json_encode(fo_t('auth.face_enter_email'), JSON_UNESCAPED_UNICODE); ?>);
                return;
            }
            localStorage.setItem('happybite_faceid_email', email);
            if (window.HappyBiteAuthFace) {
                HappyBiteAuthFace.runLogin(function () {
                    return document.getElementById('email-faceid').value.trim();
                });
            }
        });
        var enrollBtn = document.getElementById('face-enroll-pending');
        if (enrollBtn && window.HappyBiteAuthFace) {
            enrollBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
                var em = (enrollBtn.getAttribute('data-email') || '').trim();
                if (!em) {
                    return;
                }
                HappyBiteAuthFace.runEnroll(function () {
                    return em;
                }, function (ok, data) {
                    if (ok) {
                        (window.hbAlert || alert)(<?php echo json_encode(fo_t('auth.face_saved'), JSON_UNESCAPED_UNICODE); ?>);
                        enrollBtn.closest('.auth-card').querySelector('.auth-banner').style.display = 'none';
                        enrollBtn.style.display = 'none';
                    } else if (data && data.error) {
                        (window.hbAlert || alert)(data.error);
                    }
                });
            });
        }
    })();
</script>
<?php require_once __DIR__ . '/../includes/hb_brand_head.php'; hb_brand_render_footer('../'); ?>
</body>
</html>
