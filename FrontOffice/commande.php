<?php
declare(strict_types=1);

require_once __DIR__ . '/../Controllers/CommandeController.php';
require_once __DIR__ . '/../Controllers/ProduitController.php';
require_once __DIR__ . '/includes/panier_session.php';
require_once __DIR__ . '/includes/fo_i18n.php';

panier_ensure_session();
fo_init_i18n_for_request();

$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userId = $loggedIn ? (int) ($_SESSION['user_id'] ?? 0) : 0;

$commandeCtrl = new CommandeController();
$produitCtrl = new ProduitController();

if (isset($_GET['annuler'])) {
    $idAnn = (int) ($_SESSION['commande_id'] ?? 0);
    if ($idAnn > 0 && $userId > 0 && $commandeCtrl->commandeAppartientAUtilisateur($idAnn, $userId)) {
        $commandeCtrl->supprimerCommande($idAnn);
    }
    unset($_SESSION['commande_id']);
    header('Location: panier.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preparer_commande'])) {
    if (!$loggedIn || $userId < 1) {
        header('Location: auth/login.php');
        exit;
    }
    $items = panier_get_items();
    if ($items === []) {
        header('Location: panier.php');
        exit;
    }
    $lignes = [];
    foreach ($items as $idP => $ent) {
        $p = $produitCtrl->getProduitById($idP);
        if (!$p) {
            continue;
        }
        $lignes[] = [
            'id_produit' => $idP,
            'quantite' => $ent['quantite'],
            'prix_unitaire' => $ent['prix_unitaire'],
            'nom' => (string) $p['nom'],
        ];
    }
    if ($lignes === []) {
        header('Location: panier.php');
        exit;
    }
    $res = $commandeCtrl->creerCommandeDepuisPanier($lignes, $userId);
    $_SESSION['commande_id'] = $res['id_commande'];
    header('Location: commande.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['terminer_commande'])) {
    $idCmd = (int) ($_SESSION['commande_id'] ?? 0);
    if ($idCmd < 1 || !$loggedIn || $userId < 1
        || !$commandeCtrl->commandeAppartientAUtilisateur($idCmd, $userId)) {
        unset($_SESSION['commande_id']);
        header('Location: panier.php');
        exit;
    }
    $mode = trim((string) ($_POST['mode_paiement'] ?? ''));
    if ($mode === '') {
        $_SESSION['flash_erreur_commande'] = 'order.flash_payment_required';
        header('Location: commande.php');
        exit;
    }
    $redStr = str_replace(',', '.', trim((string) ($_POST['reduction'] ?? '0')));
    $reduction = is_numeric($redStr) ? (float) $redStr : 0.0;
    $paypalVerified = (string) ($_POST['paypal_verified'] ?? '0') === '1';

    if ($mode === 'paypal' && !$paypalVerified) {
        $_SESSION['flash_erreur_commande'] = 'order.flash_paypal_required';
        header('Location: commande.php');
        exit;
    }

    $commandeCtrl->finaliserCommande($idCmd, $mode, $reduction);
    if ($mode === 'paypal') {
        $_SESSION['flash_paypal_complete'] = 'order.flash_paypal_complete';
    }
    panier_clear();
    header('Location: livraison.php');
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

$nomsProduits = $commandeCtrl->getNomsProduitsCommande($idCommande);
$totalFormate = number_format((float) $commande['total'], 2, ',', ' ');
$flashErreurKey = (string) ($_SESSION['flash_erreur_commande'] ?? '');
unset($_SESSION['flash_erreur_commande']);
$flashErreur = $flashErreurKey !== '' ? fo_t($flashErreurKey) : '';
?>
<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; hb_brand_render_head(); ?>

    <title>HappyBite — <?php echo fo_e('order.title'); ?></title>
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
    <section class="commande-panel" aria-label="<?php echo fo_e('order.title'); ?>">
        <form method="post" action="commande.php" id="form-commande">
            <div class="commande-field">
                <label for="produit"><?php echo fo_e('order.product'); ?></label>
                <textarea id="produit" name="produit" readonly rows="4"><?php echo htmlspecialchars($nomsProduits); ?></textarea>
            </div>
            <div class="commande-field">
                <label for="total"><?php echo fo_e('order.total'); ?></label>
                <input type="text" id="total" name="total" readonly value="<?php echo htmlspecialchars($totalFormate); ?> DT">
            </div>
            <div class="commande-field">
                <label for="reduction"><?php echo fo_e('order.promo_code'); ?></label>
                <input type="text" id="reduction" name="reduction" placeholder="<?php echo fo_e('order.promo_placeholder'); ?>" value="<?php echo htmlspecialchars((string) ($_POST['reduction'] ?? '')); ?>">
            </div>
            <div class="commande-field">
                <label for="mode-paiement"><?php echo fo_e('order.payment_mode'); ?></label>
                <select id="mode-paiement" name="mode_paiement" required>
                    <option value="" selected disabled><?php echo fo_e('order.payment_select'); ?></option>
                    <option value="carte"><?php echo fo_e('order.payment_card'); ?></option>
                    <option value="cash"><?php echo fo_e('order.payment_cash'); ?></option>
                    <option value="paypal"><?php echo fo_e('order.payment_paypal'); ?></option>
                </select>
                <div id="carte-paiement-details" class="mode-paiement-details" hidden>
                    <p class="mode-paiement-hint"><?php echo fo_e('order.hint_local'); ?></p>
                    <div class="commande-field commande-field--nested">
                        <label for="carte-titulaire"><?php echo fo_e('order.card_holder'); ?></label>
                        <input type="text" id="carte-titulaire" autocomplete="off" placeholder="<?php echo fo_e('order.card_holder_ph'); ?>">
                    </div>
                    <div class="commande-field commande-field--nested">
                        <label for="carte-numero"><?php echo fo_e('order.card_number'); ?></label>
                        <input type="text" id="carte-numero" inputmode="numeric" autocomplete="off" placeholder="0000 0000 0000 0000" maxlength="19">
                    </div>
                    <div class="commande-field-row">
                        <div class="commande-field commande-field--nested">
                            <label for="carte-expiration"><?php echo fo_e('order.card_expiry'); ?></label>
                            <input type="text" id="carte-expiration" autocomplete="off" placeholder="MM/AA" maxlength="5">
                        </div>
                        <div class="commande-field commande-field--nested">
                            <label for="carte-cvv"><?php echo fo_e('order.card_cvv'); ?></label>
                            <input type="password" id="carte-cvv" autocomplete="off" placeholder="•••" maxlength="4">
                        </div>
                    </div>
                </div>
                <div id="cash-paiement-details" class="mode-paiement-details" hidden>
                    <p class="mode-paiement-hint"><?php echo fo_e('order.hint_local'); ?></p>
                    <div class="commande-field commande-field--nested">
                        <label for="cash-montant"><?php echo fo_e('order.cash_amount'); ?></label>
                        <input type="text" id="cash-montant" autocomplete="off" placeholder="<?php echo fo_e('order.cash_amount_ph'); ?>">
                    </div>
                    <div class="commande-field commande-field--nested">
                        <label for="cash-contact"><?php echo fo_e('order.cash_phone'); ?></label>
                        <input type="tel" id="cash-contact" autocomplete="off" placeholder="+216 …">
                    </div>
                    <div class="commande-field commande-field--nested">
                        <label for="cash-note"><?php echo fo_e('order.cash_note'); ?></label>
                        <input type="text" id="cash-note" autocomplete="off" placeholder="<?php echo fo_e('order.cash_note_ph'); ?>">
                    </div>
                </div>
                <div id="paypal-paiement-details" class="mode-paiement-details" hidden>
                    <p class="mode-paiement-hint"><?php echo fo_e('order.paypal_hint'); ?></p>
                    <button type="button" id="paypal-auth-btn" class="paypal-auth-btn"><?php echo fo_e('order.paypal_pay_btn'); ?></button>
                    <p id="paypal-status" class="paypal-status" hidden><?php echo fo_e('order.paypal_done'); ?></p>
                </div>
            </div>
            <input type="hidden" name="paypal_verified" id="paypal-verified" value="0">
            <input type="hidden" name="paypal_face_snapshot" id="paypal-face-snapshot" value="">
            <div class="commande-actions">
                <a href="commande.php?annuler=1" class="btn-commande-outline"><?php echo fo_e('order.cancel'); ?></a>
                <button type="submit" name="terminer_commande" value="1" class="btn-commande-primary"><?php echo fo_e('order.finish'); ?></button>
            </div>
        </form>
    </section>
</main>

<div id="paypal-modal" class="paypal-modal" hidden aria-hidden="true">
    <div class="paypal-modal__panel" role="dialog" aria-modal="true" aria-labelledby="paypal-modal-title">
        <h2 id="paypal-modal-title" class="paypal-modal__title"><?php echo fo_e('order.paypal_modal_title'); ?></h2>
        <div class="paypal-modal__row">
            <label for="paypal-login-email"><?php echo fo_e('order.paypal_email'); ?></label>
            <input type="email" id="paypal-login-email" placeholder="email@paypal.com">
        </div>
        <div class="paypal-modal__row">
            <label for="paypal-login-password"><?php echo fo_e('order.paypal_password'); ?></label>
            <input type="password" id="paypal-login-password" placeholder="<?php echo fo_e('order.paypal_password_ph'); ?>">
        </div>
        <div class="paypal-modal__actions">
            <button type="button" id="paypal-login-submit" class="paypal-modal__btn paypal-modal__btn--login"><?php echo fo_e('order.paypal_login_pay'); ?></button>
            <img src="images/face id paypal.png" alt="<?php echo fo_e('order.paypal_face_alt'); ?>" id="paypal-faceid-submit" class="paypal-faceid-img" loading="lazy" tabindex="0" role="button">
            <button type="button" id="paypal-login-cancel" class="paypal-modal__btn paypal-modal__btn--cancel"><?php echo fo_e('order.cancel'); ?></button>
        </div>
        <div id="paypal-face-scan" class="paypal-face-scan" hidden aria-hidden="true">
            <div class="paypal-face-scan__frame">
                <video id="paypal-camera-preview" class="paypal-face-scan__video" autoplay playsinline muted></video>
                <span class="paypal-face-scan__line"></span>
            </div>
            <p class="paypal-face-scan__text"><?php echo fo_e('order.paypal_scan'); ?></p>
        </div>
        <p id="paypal-modal-msg" class="paypal-modal__msg" hidden></p>
    </div>
</div>

<footer>
    <?php echo fo_e('footer.copyright'); ?>
</footer>

<script src="js/controles.js" defer></script>
<?php
require_once __DIR__ . '/includes/hb_action_toast.php';
hb_action_toast_script($flashErreur !== '' ? $flashErreur : null, 4000);
?>

</body>
</html>
