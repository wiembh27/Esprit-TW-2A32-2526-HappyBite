<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/fo_i18n.php';
fo_init_i18n_for_request();

$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userId = $loggedIn ? (int) ($_SESSION['user_id'] ?? 0) : 0;

$profilSnippet = null;
if ($loggedIn && $userId > 0) {
    require_once __DIR__ . '/../config/Database.php';
    try {
        $db = Database::getConnection();
        $st = $db->prepare('SELECT objectif, poids_actuel, taille FROM profil_sante WHERE id_utilisateur = :id LIMIT 1');
        $st->execute(['id' => $userId]);
        $profilSnippet = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $profilSnippet = null;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; hb_brand_render_head(); ?>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HappyBite — <?php echo fo_e('health.page_title'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style-original-views.css">
</head>
<body>

<?php
$nav_active = 'sante';
require __DIR__ . '/includes/nav_front.php';
?>

<main class="commande-wrap sante-landing" style="max-width:720px;margin:0 auto;padding:2rem 1.25rem 4rem;">
    <h1 class="sante-page-title" style="font-family:var(--hb-font-main,Poppins,sans-serif);font-size:1.85rem;margin-bottom:0.5rem;font-weight:700;text-align:center;"><?php echo fo_e('health.page_title'); ?></h1>
    <p class="sante-page-sub" style="text-align:center;margin-bottom:1.5rem;font-size:0.95rem;"><?php echo fo_e('health.page_sub'); ?></p>

    <?php if ($loggedIn): ?>
        <p class="sante-page-text" style="line-height:1.6;margin-bottom:1.25rem;text-align:center;">
            <?php
            $dash = '<a href="user_health_space.php" class="sante-page-link" style="font-weight:400;">' . fo_e('health.dashboard_link') . '</a>';
            $rec = '<a href="List-Recette.php" class="sante-page-link">' . fo_e('health.recipes_link') . '</a>';
            $fr = '<a href="List-Frigo.php" class="sante-page-link">' . fo_e('health.fridge_link') . '</a>';
            echo sprintf(fo_t('health.open_dashboard'), $dash, $rec, $fr);
            ?>
        </p>
        <?php if ($profilSnippet): ?>
            <div class="sante-profile-snippet" style="border-radius:12px;padding:1rem 1.1rem;text-align:center;">
                <strong><?php echo fo_e('health.profile_label'); ?></strong> —
                <?php echo fo_e('health.goal'); ?> : <?php echo fo_db_e((string) ($profilSnippet['objectif'] ?? '—')); ?>
                <?php if (!empty($profilSnippet['poids_actuel']) || !empty($profilSnippet['taille'])): ?>
                    ·
                    <?php if (!empty($profilSnippet['poids_actuel'])): ?>
                        <?php echo htmlspecialchars((string) $profilSnippet['poids_actuel']); ?> kg
                    <?php endif; ?>
                    <?php if (!empty($profilSnippet['taille'])): ?>
                        <?php if (!empty($profilSnippet['poids_actuel'])): ?> / <?php endif; ?>
                        <?php echo htmlspecialchars((string) $profilSnippet['taille']); ?> cm
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <p style="text-align:center;margin-top:1.25rem;">
                <a href="user_health_space.php" class="sante-cta-btn" style="display:inline-block;padding:10px 20px;text-decoration:none;border-radius:10px;font-weight:600;"><?php echo fo_e('health.view_space'); ?></a>
            </p>
        <?php else: ?>
            <p class="sante-page-sub" style="text-align:center;"><?php echo fo_e('health.no_profile'); ?></p>
            <p style="text-align:center;margin-top:1rem;">
                <a href="user_health_space.php?fo=create" class="sante-cta-btn" style="display:inline-block;padding:10px 20px;text-decoration:none;border-radius:10px;font-weight:600;"><?php echo fo_e('health.create_profile_btn'); ?></a>
            </p>
        <?php endif; ?>
    <?php endif; ?>
</main>

<footer style="text-align:center;padding:1rem;color:#2C7E34;font-weight:400;font-family:Poppins,sans-serif;">
    <?php echo fo_e('footer.copyright'); ?>
</footer>

<?php if (!$loggedIn) {
    require __DIR__ . '/includes/guest_login_gate.php';
} ?>
</body>
</html>
