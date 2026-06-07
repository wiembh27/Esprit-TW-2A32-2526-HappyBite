<?php
declare(strict_types=1);

require_once __DIR__ . '/../Controllers/CommandeController.php';
require_once __DIR__ . '/../Controllers/LivraisonController.php';
require_once __DIR__ . '/includes/panier_session.php';
require_once __DIR__ . '/includes/fo_i18n.php';

panier_ensure_session();
fo_init_i18n_for_request();

$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userId = $loggedIn ? (int) ($_SESSION['user_id'] ?? 0) : 0;

$commandeCtrl = new CommandeController();
$livraisonCtrl = new LivraisonController();

if (isset($_GET['annuler_livraison'])) {
    $idCmd = (int) ($_SESSION['commande_id'] ?? 0);
    if ($idCmd > 0 && $userId > 0 && $commandeCtrl->commandeAppartientAUtilisateur($idCmd, $userId)) {
        $cmd = $commandeCtrl->getCommandeById($idCmd);
        if ($cmd !== null && !empty($cmd['id_livraison'])) {
            $livraisonCtrl->annulerLivraison((int) $cmd['id_livraison']);
        }
    }
    unset($_SESSION['commande_id']);
    header('Location: Home.php');
    exit;
}

if (isset($_GET['ok'])) {
    unset($_SESSION['commande_id']);
    header('Location: Home.php');
    exit;
}

$idCommande = (int) ($_SESSION['commande_id'] ?? 0);
if ($idCommande < 1) {
    header('Location: panier.php');
    exit;
}
if (!$loggedIn || $userId < 1) {
    header('Location: auth/login.php');
    exit;
}

if (!$commandeCtrl->commandeAppartientAUtilisateur($idCommande, $userId)) {
    unset($_SESSION['commande_id']);
    header('Location: panier.php');
    exit;
}

$commande = $commandeCtrl->getCommandeById($idCommande);
if ($commande === null) {
    unset($_SESSION['commande_id']);
    header('Location: panier.php');
    exit;
}

if (empty($commande['id_livraison'])) {
    $livraisonCtrl->creerEtLierCommande($idCommande);
    $commande = $commandeCtrl->getCommandeById($idCommande);
    if ($commande === null || empty($commande['id_livraison'])) {
        die(fo_t('order.error_create_delivery'));
    }
}

$livraison = $livraisonCtrl->getLivraisonById((int) $commande['id_livraison'], $commande);
if ($livraison === null) {
    die(fo_t('order.error_delivery_not_found'));
}

$trackTimeline = fo_delivery_localize_timeline($livraisonCtrl->buildTimelineState($livraison, $commande));
$statutAffiche = (string) ($trackTimeline['statut'] ?? $livraison['statut']);
$etaLine = (string) ($trackTimeline['eta_line'] ?? '');
$subLine = (string) ($trackTimeline['sub_line'] ?? '');

$dateStr = LivraisonController::extraireDatePourAffichage($livraison);
$dateAffiche = $dateStr;
$dt = DateTimeImmutable::createFromFormat('Y-m-d', $dateStr);
if ($dt instanceof DateTimeImmutable) {
    $dateAffiche = $dt->format('d/m/Y');
}
$paypalFlashKey = (string) ($_SESSION['flash_paypal_complete'] ?? '');
unset($_SESSION['flash_paypal_complete']);
$paypalFlash = $paypalFlashKey !== '' ? fo_t($paypalFlashKey) : '';
?>
<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; hb_brand_render_head(); ?>

    <title>HappyBite — <?php echo fo_e('delivery.title'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php
$nav_active = 'panier';
require __DIR__ . '/includes/nav_front.php';
?>

<main class="commande-wrap">
    <section class="commande-panel livraison-panel" aria-label="<?php echo fo_e('delivery.title'); ?>">
        <div class="livraison-hero">
            <h1 class="livraison-title"><?php echo fo_e('delivery.confirmed'); ?></h1>
            <img class="livraison-success-icon" src="images/success.svg" alt="">
        </div>
        <p class="livraison-line livraison-line--value">
            <span class="livraison-label"><?php echo fo_e('delivery.status'); ?></span>
            <?php echo htmlspecialchars($statutAffiche); ?>
        </p>
        <?php if ($etaLine !== '') { ?>
        <p class="livraison-line livraison-line--value">
            <span class="livraison-label"><?php echo fo_e('delivery.track_label'); ?></span>
            <?php echo htmlspecialchars($etaLine); ?>
        </p>
        <?php } ?>
        <?php if ($subLine !== '') { ?>
        <p class="livraison-line livraison-line--value livraison-line--muted">
            <?php echo htmlspecialchars($subLine); ?>
        </p>
        <?php } ?>
        <p class="livraison-line livraison-line--value">
            <span class="livraison-label"><?php echo fo_e('delivery.expected'); ?></span>
            <?php echo htmlspecialchars($dateAffiche); ?>
        </p>

        <div class="commande-actions livraison-actions">
            <a href="livraison.php?annuler_livraison=1" class="btn-commande-outline"><?php echo fo_e('delivery.cancel'); ?></a>
            <button type="button" class="livraison-track-btn" data-hb-open-track-map data-commande-id="<?php echo (int) $idCommande; ?>">
                <img src="images/track.png" alt="" class="livraison-track-btn__icon">
                <span class="livraison-track-btn__label"><?php echo fo_e('delivery.track_btn'); ?></span>
            </button>
            <a href="livraison.php?ok=1" class="btn-commande-primary"><?php echo fo_e('delivery.ok'); ?></a>
        </div>
    </section>
</main>

<footer>
    <?php echo fo_e('footer.copyright'); ?>
</footer>
<?php
require_once __DIR__ . '/includes/hb_action_toast.php';
hb_action_toast_script($paypalFlash !== '' ? $paypalFlash : null, 4000);
?>

</body>
</html>
