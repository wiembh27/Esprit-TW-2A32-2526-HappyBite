<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/fo_i18n.php';
require_once __DIR__ . '/hb_brand_head.php';
require_once __DIR__ . '/fo_demo_guide.php';
fo_init_i18n_for_request();

$nav_logged_in = !empty($_SESSION['logged_in']) && ($_SESSION['logged_in'] === true);
$nav_role = $nav_logged_in ? strtolower(trim((string) ($_SESSION['user_role'] ?? ''))) : '';

$nav_is_client = $nav_logged_in && $nav_role === 'client';
$nav_show_frigo = $nav_is_client;
$nav_show_sante = $nav_is_client;
$nav_show_panier = $nav_is_client;
$nav_show_ai = $nav_is_client;

if (!empty($hide_frigo)) {
    $nav_show_frigo = false;
}
if (!empty($hide_sante)) {
    $nav_show_sante = false;
}
if (!empty($hide_panier)) {
    $nav_show_panier = false;
}
if (!empty($hide_ai)) {
    $nav_show_ai = false;
}

if (!isset($nav_active)) {
    $nav_active = '';
}

$nav_class = static function (string $key, string $current): string {
    return $key === $current ? ' nav-link-active' : '';
};

$nav_icon_class = static function (string $key, string $current): string {
    return $key === $current ? ' nav-icon-active' : '';
};

$nav_profile_img_src = 'images/profile.png';
$nav_user_display_name = '';
$nav_user_email = '';
$nav_unread_notif_count = 0;

if ($nav_logged_in) {
    require_once __DIR__ . '/../../Controllers/PasswordChangeService.php';
    $navScript = basename(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '')));
    if ($navScript !== 'Profile_Utilisateur.php' && password_change_get_pending() !== null) {
        password_change_clear_pending();
    }

        $nav_user_display_name = trim((string) ($_SESSION['user_nom'] ?? '') . ' ' . (string) ($_SESSION['user_prenom'] ?? ''));
        $nav_user_email = (string) ($_SESSION['user_email'] ?? '');
        if ($nav_user_display_name === '') {
            $nav_user_display_name = fo_t('nav.member');
        }
        try {
            require_once __DIR__ . '/../../config/Database.php';
            require_once __DIR__ . '/../../Controllers/UtilisateurPhotoSql.php';
            $nav_uid = (int) ($_SESSION['user_id'] ?? 0);
            require_once __DIR__ . '/../../Controllers/UserSettingsService.php';
            user_settings_load_for_user(Database::getConnection(), $nav_uid);
        $nav_rel = utilisateur_fetch_profile_relative_path(Database::getConnection(), $nav_uid);
        $nav_custom_src = utilisateur_nav_profile_img_src($nav_rel);
        if ($nav_custom_src !== null) {
            $nav_profile_img_src = $nav_custom_src;
        }
        if (!isset($navUnreadNotifCount)) {
            require_once __DIR__ . '/../../Controllers/UserNotificationService.php';
            $navPdo = Database::getConnection();
            user_notification_ensure_table($navPdo);
            user_notification_run_scheduled_checks($navPdo, $nav_uid);
            $nav_unread_notif_count = user_notification_count_unread($navPdo, $nav_uid);
        } else {
            $nav_unread_notif_count = (int) $navUnreadNotifCount;
        }
    } catch (Throwable $e) {
        // garder l’icône par défaut
    }
}
?>
<style>
        /* Hover des liens texte (Accueil, Produits, …) — main-nav */
        .main-nav .nav-link:hover {
            color: var(--hb-forest-mid);
            border-bottom-color: rgba(37, 107, 45, 0.4);
        }

        .main-nav .nav-link.nav-link-active:hover {
            color: var(--hb-forest-mid);
            border-bottom-color: var(--hb-forest);
        }

        /* Déconnexion rouge partout (pages avec style-original-views ou sans css/style.css). */
        .nav-profile-logout {
            background-color: #b91c1c !important;
            color: #fff !important;
            border: 2px solid #991b1b !important;
        }

        .nav-profile-logout:hover {
            background-color: #991b1b !important;
            filter: brightness(1.05);
        }

        .nav-profile-settings {
            background-color: #ffffff !important;
            color: #2C7E34 !important;
            border: 2px solid #2C7E34 !important;
        }

        .nav-profile-settings:hover {
            background-color: #ecfdf3 !important;
            color: #1f5d28 !important;
        }

        .nav-profile-userblock {
            padding: 4px 6px 10px;
            margin-bottom: 4px;
            border-bottom: 1px solid #e8ecf0;
            text-align: left;
        }

        .nav-profile-name {
            font-weight: 700;
            font-size: 0.88rem;
            color: #2C7E34;
            line-height: 1.35;
            word-break: break-word;
        }

        .nav-profile-email {
            margin-top: 4px;
            font-size: 0.78rem;
            color: #5c6d66;
            line-height: 1.35;
            word-break: break-all;
        }

        .nav-profile-img--photo {
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(44, 126, 52, 0.35);
        }

        .main-nav .nav-profile-menu {
            min-width: 220px;
        }

        .nav-profile-icon-wrap {
            position: relative;
            display: inline-flex;
            line-height: 0;
        }

        .nav-profile-notif-dot {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background: #e53935;
            border: 2px solid #fff;
            box-shadow: 0 1px 4px rgba(229, 57, 53, 0.45);
            pointer-events: none;
        }

        .hb-demo-session-notice {
            max-width: 1140px;
            margin: 0.65rem auto 0;
            padding: 0.55rem 1rem;
            border-radius: 10px;
            border: 1px solid #fcd34d;
            background: #fffbeb;
            color: #92400e;
            font-size: 0.82rem;
            line-height: 1.45;
        }

        .hb-demo-session-notice a {
            color: #b45309;
            font-weight: 600;
            margin-left: 0.35rem;
        }
</style>
<nav class="main-nav">
    <a class="nav-brand" href="Home.php" aria-label="HappyBite — accueil">
        <img class="nav-brand-logo" src="images/logo.png" alt="" width="76" height="76">
    </a>
    <div class="nav-links-wrap">
        <div class="nav-links">
            <a href="Home.php" class="nav-link<?php echo $nav_class('accueil', $nav_active); ?>"><?php echo fo_e('nav.home'); ?></a>
            <a href="List-Produit.php" class="nav-link<?php echo $nav_class('produits', $nav_active); ?>"><?php echo fo_e('nav.products'); ?></a>
            <a href="List-Recette.php" class="nav-link<?php echo $nav_class('recettes', $nav_active); ?>"><?php echo fo_e('nav.recipes'); ?></a>
            <a href="Communaute.php" class="nav-link<?php echo $nav_class('communaute', $nav_active); ?>"><?php echo fo_e('nav.community'); ?></a>
        </div>
    </div>
    <div class="nav-icons">
        <?php if ($nav_show_frigo): ?>
        <a href="List-Frigo.php"
           class="nav-cart-link nav-icon-link<?php echo $nav_icon_class('frigo', $nav_active); ?>"
           aria-label="<?php echo fo_e('nav.fridge'); ?>">
            <img class="nav-cart-img" src="images/frigo.png" alt="" width="40" height="40">
            <span class="nav-icon-label" aria-hidden="true"><?php echo fo_e('nav.fridge'); ?></span>
        </a>
        <?php endif; ?>
        <?php if ($nav_show_sante): ?>
        <a href="user_health_space.php"
           class="nav-cart-link nav-icon-link<?php echo $nav_icon_class('sante', $nav_active); ?>"
           aria-label="<?php echo fo_e('nav.health'); ?>">
            <img class="nav-cart-img" src="images/sante.png" alt="" width="40" height="40">
            <span class="nav-icon-label" aria-hidden="true"><?php echo fo_e('nav.health'); ?></span>
        </a>
        <?php endif; ?>
        <?php if ($nav_show_panier): ?>
        <a href="panier.php"
           class="nav-cart-link nav-icon-link<?php echo $nav_icon_class('panier', $nav_active); ?>"
           aria-label="<?php echo fo_e('nav.cart'); ?>">
            <img class="nav-cart-img" src="images/panier.png" alt="" width="40" height="40">
            <span class="nav-icon-label" aria-hidden="true"><?php echo fo_e('nav.cart'); ?></span>
        </a>
        <?php endif; ?>
        <?php if ($nav_logged_in && $nav_role === 'nutritionniste'): ?>
        <a href="nutritionniste_dashboard.php"
           class="nav-cart-link nav-icon-link<?php echo $nav_icon_class('nutritionniste', $nav_active); ?>"
           aria-label="<?php echo fo_e('nav.nutritionist_space'); ?>">
            <img class="nav-cart-img" src="images/nutritionniste.png" alt="" width="40" height="40">
            <span class="nav-icon-label" aria-hidden="true"><?php echo fo_e('nav.nutritionist_short'); ?></span>
        </a>
        <?php endif; ?>
        <details class="nav-profile-dropdown<?php echo $nav_icon_class('profile', $nav_active); ?>">
            <summary class="nav-profile-trigger nav-icon-link" aria-label="Compte<?php echo $nav_unread_notif_count > 0 ? ' — notifications non lues' : ''; ?>">
                <span class="nav-profile-icon-wrap">
                    <img class="nav-profile-img<?php echo $nav_profile_img_src !== 'images/profile.png' ? ' nav-profile-img--photo' : ''; ?>"
                         src="<?php echo htmlspecialchars($nav_profile_img_src, ENT_QUOTES, 'UTF-8'); ?>"
                         alt="" width="40" height="40">
                    <?php if ($nav_unread_notif_count > 0) { ?>
                        <span class="nav-profile-notif-dot" id="hb-nav-notif-dot" aria-hidden="true"></span>
                    <?php } else { ?>
                        <span class="nav-profile-notif-dot" id="hb-nav-notif-dot" aria-hidden="true" hidden></span>
                    <?php } ?>
                </span>
                <span class="nav-icon-label" aria-hidden="true"><?php echo fo_e('nav.profile'); ?></span>
            </summary>
            <div class="nav-profile-menu">
                <?php if (!$nav_logged_in): ?>
                    <a href="auth/register.php" class="nav-profile-btn nav-profile-signup"><?php echo fo_e('nav.signup'); ?></a>
                    <a href="auth/login.php" class="nav-profile-btn nav-profile-login"><?php echo fo_e('nav.login'); ?></a>
                <?php else: ?>
                    <div class="nav-profile-userblock">
                        <div class="nav-profile-name"><?php echo htmlspecialchars($nav_user_display_name, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php if ($nav_user_email !== ''): ?>
                            <div class="nav-profile-email"><?php echo htmlspecialchars($nav_user_email, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>
                    <a href="Profile_Utilisateur.php" class="nav-profile-btn nav-profile-settings"><?php echo fo_e('nav.settings'); ?></a>
                    <a href="../Controllers/AuthProcess.php?action=logout" class="nav-profile-btn nav-profile-logout"><?php echo fo_e('nav.logout'); ?></a>
                <?php endif; ?>
            </div>
        </details>
    </div>
</nav>
<?php fo_render_demo_session_notice(); ?>
<?php
require_once __DIR__ . '/hb_action_toast.php';
hb_action_toast_render();
fo_i18n_render_bootstrap(hb_brand_asset_prefix_from_request());
?>
<?php include_once __DIR__ . '/track_map_modal.php'; ?>
<?php if ($nav_show_ai) {
    include_once __DIR__ . '/../Ai.php';
} ?>
<?php if ($nav_logged_in) { ?>
<script>
window.HB_NOTIF_SOUND_URL = 'sounds/notification.wav';
window.HB_NOTIF_POLL_URL = 'api/notifications_poll.php';
window.HB_NOTIF_INITIAL_UNREAD = <?php echo (int) $nav_unread_notif_count; ?>;
window.HB_NOTIF_POLL_ENABLED = true;
window.HB_NOTIF_POLL_MS = 28000;
</script>
<script src="js/notification-sound.js"></script>
<?php } ?>
<?php
require_once __DIR__ . '/hb_brand_head.php';
hb_brand_render_footer(hb_brand_asset_prefix_from_request());
?>
