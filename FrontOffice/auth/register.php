<?php declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/fo_i18n.php';
fo_init_i18n_for_request();

$errors = $_SESSION['errors'] ?? [];
$error = $_SESSION['error'] ?? '';
$pendingFaceEmail = $_SESSION['just_registered_email'] ?? '';
unset($_SESSION['errors'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../includes/hb_brand_head.php'; hb_brand_render_head('../'); ?>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HappyBite — <?php echo fo_e('auth.register_page_title'); ?></title>
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
            <p><?php echo fo_e('auth.welcome_register_desc'); ?></p>
        </div>
    </aside>
    <main class="auth-panel">
        <div class="auth-card auth-card--scroll">
            <h2 class="auth-card__title"><?php echo fo_e('auth.register_heading'); ?></h2>

            <form method="POST" action="../../Controllers/AuthProcess.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="register">

                <div class="auth-row">
                    <div class="auth-field">
                        <label for="prenom"><?php echo fo_e('auth.first_name'); ?></label>
                        <input type="text" name="prenom" id="prenom" required placeholder="<?php echo fo_e('auth.first_name'); ?>" autocomplete="given-name">
                    </div>
                    <div class="auth-field">
                        <label for="nom"><?php echo fo_e('auth.last_name'); ?></label>
                        <input type="text" name="nom" id="nom" required placeholder="<?php echo fo_e('auth.last_name'); ?>" autocomplete="family-name">
                    </div>
                </div>

                <div class="auth-field">
                    <label for="email-register"><?php echo fo_e('auth.email'); ?></label>
                    <input type="email" name="email" required id="email-register" autocomplete="username" placeholder="<?php echo fo_e('auth.email_ph'); ?>">
                </div>

                <div class="auth-field auth-profile-photo">
                    <label>Photo de profil (optionnel)</label>
                    <p class="auth-profile-photo__hint">Choisissez une photo depuis votre appareil ou prenez-en une avec la caméra.</p>
                    <div class="auth-profile-photo__actions">
                        <button type="button" class="auth-profile-photo__btn" id="photo-choose-btn">Choisir une photo</button>
                        <button type="button" class="auth-profile-photo__btn" id="photo-camera-btn">Prendre une photo</button>
                    </div>
                    <input type="file" name="profile_photo" accept="image/*" id="photo-preview-file" class="auth-profile-photo__file" hidden>
                    <div id="photo-preview-container" class="auth-profile-photo__preview-wrap" hidden>
                        <img id="photo-preview" class="auth-profile-photo__preview" alt="Aperçu photo" width="120" height="120">
                    </div>
                </div>

                <div class="auth-field">
                    <label for="password"><?php echo fo_e('auth.password'); ?></label>
                    <input type="password" name="password" id="password" required autocomplete="new-password" placeholder="<?php echo fo_e('auth.password_min'); ?>">
                </div>

                <div class="auth-field">
                    <label for="role"><?php echo fo_e('auth.role'); ?></label>
                    <select name="role" id="role" required>
                        <option value="client"><?php echo fo_e('auth.role_client'); ?></option>
                        <option value="nutritionniste"><?php echo fo_e('auth.role_nutritionist'); ?></option>
                        <option value="fournisseur"><?php echo fo_e('auth.role_supplier'); ?></option>
                    </select>
                </div>

                <div class="auth-field">
                    <label for="referral_code"><?php echo fo_e('auth.referral_code'); ?></label>
                    <input type="text" name="referral_code" id="referral_code" placeholder="<?php echo fo_e('auth.optional'); ?>">
                </div>

                <div class="auth-field">
                    <label for="budget"><?php echo fo_e('auth.budget'); ?></label>
                    <input type="number" name="budget" id="budget" step="50" placeholder="<?php echo fo_e('auth.optional'); ?>">
                </div>

                <div class="auth-field">
                    <label for="description"><?php echo fo_e('auth.description'); ?></label>
                    <input type="text" name="description" id="description" placeholder="<?php echo fo_e('auth.description_ph'); ?>">
                </div>

                <button type="submit" class="auth-btn-primary"><?php echo fo_e('auth.signup_btn'); ?></button>
            </form>

            <?php if ($pendingFaceEmail !== ''): ?>
                <div class="auth-divider">
                    <p><?php echo fo_e('auth.face_register_section'); ?></p>
                    <div class="auth-banner">
                        <?php echo sprintf(fo_t('auth.face_register_banner'), '<strong>' . htmlspecialchars($pendingFaceEmail, ENT_QUOTES, 'UTF-8') . '</strong>'); ?>
                    </div>
                    <button type="button" class="auth-btn-faceid" id="face-enroll-pending-reg" data-email="<?php echo htmlspecialchars($pendingFaceEmail, ENT_QUOTES, 'UTF-8'); ?>">
                        <img src="../images/face-id.png" alt="" class="auth-btn-faceid__icon" width="20" height="20">
                        <span class="auth-btn-faceid__label"><?php echo fo_e('auth.face_register_btn'); ?></span>
                    </button>
                </div>
            <?php else: ?>
                <p class="auth-hint" style="margin-top:1rem;text-align:center;">
                    <?php echo fo_e('auth.face_after_register'); ?>
                </p>
            <?php endif; ?>

            <p class="auth-footer-links"><?php echo fo_e('auth.has_account'); ?> <a href="login.php"><?php echo fo_e('auth.login_btn'); ?></a></p>
            <p class="auth-back"><a href="../Home.php"><?php echo fo_e('auth.back_site'); ?></a></p>
        </div>
    </main>
</div>
<div id="auth-photo-modal" class="auth-photo-modal" hidden aria-hidden="true">
    <div class="auth-photo-modal__dialog" role="dialog" aria-modal="true" aria-label="Photo de profil">
        <p id="auth-photo-modal-error" class="auth-photo-modal__error" hidden></p>
        <div class="auth-photo-modal__frame">
            <video id="auth-photo-video" class="auth-photo-modal__video" autoplay playsinline muted></video>
            <img id="auth-photo-review" class="auth-photo-modal__review" alt="Photo capturée" width="280" height="280" hidden>
            <canvas id="auth-photo-canvas" class="auth-photo-modal__canvas" hidden></canvas>
        </div>
        <div id="auth-photo-live-actions" class="auth-photo-modal__actions">
            <button type="button" class="auth-photo-modal__btn auth-photo-modal__btn--ghost" id="auth-photo-cancel">Annuler</button>
            <button type="button" class="auth-photo-modal__btn auth-photo-modal__btn--primary" id="auth-photo-capture">Capturer</button>
        </div>
        <div id="auth-photo-review-actions" class="auth-photo-modal__actions" hidden>
            <button type="button" class="auth-photo-modal__btn auth-photo-modal__btn--ghost" id="auth-photo-retake">Reprendre</button>
            <button type="button" class="auth-photo-modal__btn auth-photo-modal__btn--primary" id="auth-photo-confirm">Mettre en photo de profil</button>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/face_scan_modal.php'; ?>
<?php
require_once __DIR__ . '/../includes/hb_action_toast.php';
hb_action_toast_render('../');
$registerToastMsg = $error;
if ($registerToastMsg === '' && $errors !== []) {
    $registerToastMsg = implode(' ', array_map(static fn ($e) => (string) $e, $errors));
}
hb_action_toast_script($registerToastMsg !== '' ? $registerToastMsg : null, 5000);
?>
<script src="../js/auth-face.js?v=5"></script>
<script src="../js/auth-profile-photo.js"></script>
<script>
    var savedEmail = localStorage.getItem('happybite_faceid_email');
    if (savedEmail) {
        var er = document.getElementById('email-register');
        if (er && !er.value) er.value = savedEmail;
    }
    document.querySelector('form').addEventListener('submit', function () {
        var email = document.getElementById('email-register').value;
        if (email) localStorage.setItem('happybite_faceid_email', email);
    });
    var enrollReg = document.getElementById('face-enroll-pending-reg');
    if (enrollReg && window.HappyBiteAuthFace) {
        enrollReg.addEventListener('click', function (ev) {
            ev.preventDefault();
            var em = (enrollReg.getAttribute('data-email') || '').trim();
            if (!em) return;
            HappyBiteAuthFace.runEnroll(function () { return em; }, function (ok, data) {
                if (ok) {
                    (window.hbAlert || alert)(<?php echo json_encode(fo_t('auth.face_saved_register'), JSON_UNESCAPED_UNICODE); ?>);
                    window.location.href = 'login.php';
                } else if (data && data.error) {
                    (window.hbAlert || alert)(data.error);
                }
            });
        });
    }
</script>

<?php require_once __DIR__ . '/../includes/hb_brand_head.php'; hb_brand_render_footer('../'); ?>
</body>
</html>
