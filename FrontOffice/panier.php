<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/fo_i18n.php';
fo_init_i18n_for_request();

require_once __DIR__ . '/../Controllers/ProduitController.php';
require_once __DIR__ . '/../Controllers/ChallengeController.php';
require_once __DIR__ . '/includes/panier_session.php';
require_once __DIR__ . '/includes/fortune_wheel_bootstrap.php';

panier_ensure_session();

$produitController = new ProduitController();
$challengeController = new ChallengeController();

$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$role = strtolower(trim((string) ($_SESSION['user_role'] ?? '')));
$userId = (int) ($_SESSION['user_id'] ?? 0);

$fw = hb_fortune_wheel_bootstrap($loggedIn, $role, $userId, $challengeController);
$ROUE_COST = $fw['ROUE_COST'];
$pointsSante = $fw['pointsSante'];
$canUseWheel = $fw['canUseWheel'];
$wheelSegments = $fw['wheelSegments'];
$pointsAvantRoue = $fw['pointsAvantRoue'];

if (!$loggedIn) {
    $_SESSION['panier'] = [];
}

function hb_panier(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function hb_panier_money(float $value): string
{
    return number_format($value, 2, ',', ' ');
}

/*
|--------------------------------------------------------------------------
| Actions panier
|--------------------------------------------------------------------------
*/

if ($loggedIn && isset($_GET['supprimer_ligne'])) {
    $lineKey = (string) $_GET['supprimer_ligne'];

    if ($lineKey !== '') {
        panier_remove_line($lineKey);
    }

    header('Location: panier.php');
    exit;
}

if ($loggedIn && isset($_GET['moins_ligne'])) {
    $lineKey = (string) $_GET['moins_ligne'];

    if ($lineKey !== '') {
        panier_decrement_line($lineKey);
    }

    header('Location: panier.php');
    exit;
}

if ($loggedIn && isset($_GET['plus_ligne'])) {
    $lineKey = (string) $_GET['plus_ligne'];

    if ($lineKey !== '') {
        $items = panier_get_items();

        if (isset($items[$lineKey])) {
            $idProduit = (int) $items[$lineKey]['id_produit'];

            if ($idProduit > 0 && empty($items[$lineKey]['cadeau'])) {
                $produit = $produitController->getProduitById($idProduit);

                if ($produit) {
                    panier_increment_line($lineKey, (float) ($produit['prix'] ?? $items[$lineKey]['prix_unitaire']));
                }
            }
        }
    }

    header('Location: panier.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Compatibilité anciens liens
|--------------------------------------------------------------------------
*/

if ($loggedIn && isset($_GET['supprimer'])) {
    $idSuppr = (int) $_GET['supprimer'];

    if ($idSuppr > 0) {
        panier_remove_product($idSuppr);
    }

    header('Location: panier.php');
    exit;
}

if ($loggedIn && isset($_GET['plus'])) {
    $idPlus = (int) $_GET['plus'];

    if ($idPlus > 0) {
        $produit = $produitController->getProduitById($idPlus);

        if ($produit) {
            panier_add_product($idPlus, (float) ($produit['prix'] ?? 0));
        }
    }

    header('Location: panier.php');
    exit;
}

if ($loggedIn && isset($_GET['moins'])) {
    $idMoins = (int) $_GET['moins'];

    if ($idMoins > 0) {
        panier_decrement_product($idMoins);
    }

    header('Location: panier.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Construction lignes panier
|--------------------------------------------------------------------------
*/

$items = $loggedIn ? panier_get_items() : [];
$pendingWheelPrizes = $loggedIn ? fortune_wheel_pending_get() : [];
$iconeSuppr = is_file(__DIR__ . '/images/delete.png') ? 'images/delete.png' : 'images/delete.svg';

$lignesPanier = [];
$total = 0.0;

foreach ($items as $lineKey => $entry) {
    $idProduit = (int) ($entry['id_produit'] ?? 0);

    if ($idProduit < 1) {
        panier_remove_line((string) $lineKey);
        continue;
    }

    $produit = $produitController->getProduitById($idProduit);

    if (!$produit) {
        panier_remove_line((string) $lineKey);
        continue;
    }

    $quantite = max(1, (int) ($entry['quantite'] ?? 1));
    $prixUnitaire = (float) ($entry['prix_unitaire'] ?? 0);
    $gratuit = !empty($entry['cadeau']) || $prixUnitaire <= 0.00001;
    $sousTotal = $gratuit ? 0.0 : $prixUnitaire * $quantite;

    $total += $sousTotal;

    $lignesPanier[] = [
        'line_key' => (string) $lineKey,
        'id_produit' => $idProduit,
        'nom' => (string) ($produit['nom'] ?? fo_t('cart.product_fallback')),
        'image' => (string) ($produit['image'] ?? ''),
        'prix_unitaire' => $gratuit ? 0.0 : $prixUnitaire,
        'quantite' => $quantite,
        'sous_total' => $sousTotal,
        'gratuit' => $gratuit,
        'label_gratuit' => (string) ($entry['source_cadeau'] ?? ''),
        'source' => (string) ($entry['source_cadeau'] ?? ''),
    ];
}

?>
<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>" dir="<?php echo fo_html_dir_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; hb_brand_render_head(); ?>
    <title>HappyBite — <?php echo fo_e('cart.title'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

    <style>
        .panier-ligne {
            align-items: center;
        }

        .panier-ligne-visual {
            width: 62px;
            height: 62px;
            border-radius: 16px;
            overflow: hidden;
            background: #f3f4f6;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 14px;
        }

        .panier-ligne-visual img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .panier-ligne-visual-placeholder {
            color: #9ca3af;
            font-size: 1.4rem;
        }

        .panier-ligne-main {
            display: flex;
            align-items: center;
            min-width: 0;
            flex: 1;
        }

        .panier-free-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            margin-top: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
            font-size: 0.74rem;
            font-weight: 800;
        }

        .panier-free-label {
            display: block;
            margin-top: 4px;
            color: #92400e;
            font-size: 0.74rem;
            line-height: 1.35;
            font-weight: 600;
        }

        .panier-free-inline {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .panier-pending-prizes {
            margin-bottom: 1.25rem;
            padding: 1rem 1.1rem;
            border-radius: 18px;
            background: #fffbeb;
            border: 1px solid #fde68a;
        }

        .panier-pending-prizes h3 {
            margin: 0 0 0.85rem;
            font-size: 0.95rem;
            color: #92400e;
        }

        .panier-pending-prizes ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .panier-pending-recette-title {
            display: block;
            margin-bottom: 0.45rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: #78350f;
        }

        .panier-pending-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panier-pending-item img,
        .panier-pending-item .panier-pending-placeholder {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            object-fit: cover;
            flex-shrink: 0;
            background: #fff;
        }

        .panier-pending-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .panier-pending-copy {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
        }

        .panier-pending-copy strong {
            font-size: 0.88rem;
            color: #1f2937;
        }

        .panier-total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-top: 1.4rem;
            padding: 1rem 1.1rem;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 18px;
            color: #166534;
            font-weight: 800;
        }

        .panier-total-box strong {
            font-size: 1.25rem;
        }

        .panier-ligne-step--disabled {
            opacity: 0.45;
            cursor: not-allowed;
            pointer-events: none;
        }

        .panier-gift-note {
            margin-top: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 16px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 0.9rem;
            line-height: 1.55;
            font-weight: 600;
        }

        .fortune-card {
            position: relative;
            margin-bottom: 1.3rem;
            border-radius: 22px;
            overflow: hidden;
            min-height: clamp(120px, 18vw, 160px);
            box-shadow: 0 14px 34px rgba(15, 42, 28, 0.18);
        }

        .fortune-card__bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
            z-index: 0;
        }

        .fortune-card::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 1;
            background: linear-gradient(90deg, rgba(0, 0, 0, 0.35) 0%, rgba(0, 0, 0, 0.12) 55%, rgba(0, 0, 0, 0.2) 100%);
            pointer-events: none;
        }

        .fortune-card__content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            padding: 1rem 1.15rem;
            min-height: inherit;
            box-sizing: border-box;
        }

        .fortune-card__copy {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.65rem;
            max-width: min(100%, 420px);
        }

        .fortune-card__copy h3,
        .fortune-card__copy p,
        .fortune-card__copy p strong,
        .fortune-card__copy #pointsValue {
            color: #ffd54f !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.55), 0 0 12px rgba(255, 193, 7, 0.35);
        }

        .fortune-card__copy h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
        }

        .fortune-card__copy p {
            margin: 0;
            font-size: 0.86rem;
            line-height: 1.45;
        }

        .fortune-card__copy .btn-fortune {
            border: none;
            border-radius: 999px;
            padding: 12px 22px;
            font-family: inherit;
            font-weight: 900;
            cursor: pointer;
            background: linear-gradient(135deg, #7c3aed, #a21caf);
            color: #fff !important;
            text-shadow: none;
            box-shadow: 0 10px 24px rgba(124, 58, 237, 0.22);
        }

        .fortune-card__copy .btn-fortune:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
            color: #fff !important;
        }

        @media (max-width: 640px) {
            .panier-ligne {
                align-items: flex-start;
            }

            .panier-ligne-main {
                align-items: flex-start;
            }

            .panier-ligne-visual {
                width: 52px;
                height: 52px;
                border-radius: 14px;
                margin-right: 10px;
            }

            .panier-total-box {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
    <?php require __DIR__ . '/includes/fortune_wheel_styles.php'; ?>
</head>

<body>

<?php
$nav_active = 'panier';
require __DIR__ . '/includes/nav_front.php';
?>

<main class="commande-wrap">
    <div class="panier-stack">
        <h1 class="panier-page-title"><?php echo fo_e('cart.title'); ?></h1>

        <?php if ($loggedIn && $role === 'client'): ?>
            <section class="fortune-card" aria-label="<?php echo fo_e('cart.fortune_aria'); ?>">
                <img
                    class="fortune-card__bg"
                    src="images/bottom4.png"
                    alt=""
                    width="1200"
                    height="400"
                    decoding="async"
                    onerror="this.onerror=null;this.src='images/bottom4.jpg';"
                >
                <div class="fortune-card__content">
                    <div class="fortune-card__copy">
                        <h3><?php echo fo_e('cart.fortune_title'); ?></h3>
                        <p>
                            <?php echo fo_e('cart.fortune_points_prefix'); ?>
                            <strong id="pointsValue"><?= (int) $pointsSante ?></strong>
                            <?php echo fo_e('cart.fortune_points_suffix'); ?><br>
                            <?php if ($canUseWheel): ?>
                                <?php echo fo_e('cart.fortune_can_play'); ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars(sprintf(fo_t('cart.fortune_points_needed'), (string) (int) $pointsAvantRoue), ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                        </p>
                        <button
                            class="btn-fortune"
                            type="button"
                            onclick="openRoue()"
                            <?php if (!$canUseWheel): ?>disabled aria-disabled="true"<?php endif; ?>
                        >
                            <?php if ($canUseWheel): ?>
                                <?php echo fo_e('cart.fortune_btn_play'); ?>
                            <?php else: ?>
                                <?php echo fo_e('cart.fortune_btn_preview'); ?>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="commande-panel panier-panel" aria-label="<?php echo fo_e('cart.title'); ?>">
            <?php if ($pendingWheelPrizes !== []): ?>
                <section class="panier-pending-prizes" aria-label="<?php echo fo_e('cart.fortune_pending_aria'); ?>">
                    <h3><?php echo fo_e('cart.fortune_pending_title'); ?></h3>
                    <ul>
                        <?php foreach ($pendingWheelPrizes as $pendingPrize): ?>
                            <?php if (!is_array($pendingPrize)): ?>
                                <?php continue; ?>
                            <?php endif; ?>

                            <?php if ((string) ($pendingPrize['type'] ?? '') === 'recette'): ?>
                                <li>
                                    <span class="panier-pending-recette-title">
                                        <?= hb_panier((string) ($pendingPrize['nom'] ?? fo_t('cart.recipe_fallback'))) ?>
                                    </span>
                                    <?php foreach (($pendingPrize['produits'] ?? []) as $pendingProduit): ?>
                                        <?php if (!is_array($pendingProduit)): ?>
                                            <?php continue; ?>
                                        <?php endif; ?>
                                        <?php
                                        $pendingImage = trim((string) ($pendingProduit['image'] ?? ''));
                                        $pendingImageSrc = $pendingImage !== '' ? '/uploads/' . ltrim($pendingImage, '/') : '';
                                        ?>
                                        <div class="panier-pending-item">
                                            <?php if ($pendingImageSrc !== ''): ?>
                                                <img src="<?= hb_panier($pendingImageSrc) ?>" alt="">
                                            <?php else: ?>
                                                <span class="panier-pending-placeholder">🥗</span>
                                            <?php endif; ?>
                                            <div class="panier-pending-copy">
                                                <strong><?= hb_panier((string) ($pendingProduit['nom'] ?? fo_t('cart.product_fallback'))) ?></strong>
                                                <span class="panier-free-inline"><?php echo fo_e('cart.free_label'); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </li>
                            <?php else: ?>
                                <?php
                                $pendingImage = trim((string) ($pendingPrize['image'] ?? ''));
                                $pendingImageSrc = $pendingImage !== '' ? '/uploads/' . ltrim($pendingImage, '/') : '';
                                ?>
                                <li class="panier-pending-item">
                                    <?php if ($pendingImageSrc !== ''): ?>
                                        <img src="<?= hb_panier($pendingImageSrc) ?>" alt="">
                                    <?php else: ?>
                                        <span class="panier-pending-placeholder">🥗</span>
                                    <?php endif; ?>
                                    <div class="panier-pending-copy">
                                        <strong><?= hb_panier((string) ($pendingPrize['nom'] ?? fo_t('cart.product_fallback'))) ?></strong>
                                        <span class="panier-free-inline"><?php echo fo_e('cart.free_label'); ?></span>
                                    </div>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    <p class="panier-free-label" style="margin-top: 0.75rem;">
                        <?php echo fo_e('cart.fortune_pending_hint'); ?>
                    </p>
                </section>
            <?php endif; ?>

            <?php if ($lignesPanier === []): ?>
                <p class="panier-vide"><?php echo fo_e('cart.empty'); ?></p>
            <?php else: ?>
                <ul class="panier-lignes">
                    <?php foreach ($lignesPanier as $ligne): ?>
                        <?php
                        $lineKey = (string) $ligne['line_key'];
                        $isFree = !empty($ligne['gratuit']);
                        $image = trim((string) $ligne['image']);
                        $imageSrc = $image !== '' ? '/uploads/' . ltrim($image, '/') : '';
                        ?>

                        <li class="panier-ligne">
                            <div class="panier-ligne-main">
                                <div class="panier-ligne-visual">
                                    <?php if ($imageSrc !== ''): ?>
                                        <img
                                            src="<?= hb_panier($imageSrc) ?>"
                                            alt="<?= hb_panier((string) $ligne['nom']) ?>"
                                        >
                                    <?php else: ?>
                                        <span class="panier-ligne-visual-placeholder">🥗</span>
                                    <?php endif; ?>
                                </div>

                                <div class="panier-ligne-infos">
                                    <span class="panier-ligne-nom">
                                        <?= hb_panier((string) $ligne['nom']) ?>
                                    </span>

                                    <span class="panier-ligne-meta">
                                        <?php if ($isFree): ?>
                                            <span class="panier-free-inline"><?php echo fo_e('cart.free_label'); ?></span>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars(sprintf(
                                                fo_t('cart.line_price'),
                                                hb_panier_money((float) $ligne['prix_unitaire']),
                                                (int) $ligne['quantite'],
                                                hb_panier_money((float) $ligne['sous_total'])
                                            ), ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="panier-ligne-actions">
                                <a href="panier.php?moins_ligne=<?= urlencode($lineKey) ?>"
                                   class="panier-ligne-step panier-ligne-step--minus"
                                   aria-label="<?= hb_panier($isFree ? fo_t('cart.remove_gift') : fo_t('cart.remove_qty')) ?>"
                                   title="<?= hb_panier($isFree ? fo_t('cart.remove_gift') : fo_t('cart.remove_qty')) ?>">
                                    -
                                </a>

                                <a href="panier.php?supprimer_ligne=<?= urlencode($lineKey) ?>"
                                   class="panier-ligne-suppr"
                                   aria-label="<?php echo fo_e('cart.remove_from_cart'); ?>"
                                   title="<?php echo fo_e('cart.remove'); ?>">
                                    <img src="<?= hb_panier($iconeSuppr) ?>" alt="" width="24" height="24">
                                </a>

                                <?php if ($isFree): ?>
                                    <span class="panier-ligne-step panier-ligne-step--plus panier-ligne-step--disabled"
                                          aria-label="<?php echo fo_e('cart.gift_no_add_qty'); ?>"
                                          title="<?php echo fo_e('cart.gift_qty_limited'); ?>">
                                        +
                                    </span>
                                <?php else: ?>
                                    <a href="panier.php?plus_ligne=<?= urlencode($lineKey) ?>"
                                       class="panier-ligne-step panier-ligne-step--plus"
                                       aria-label="<?php echo fo_e('cart.add_qty'); ?>"
                                       title="<?php echo fo_e('cart.add_qty'); ?>">
                                        +
                                    </a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="panier-total-box">
                    <span><?php echo fo_e('cart.total_payable'); ?></span>
                    <strong><?= hb_panier_money($total) ?> <?php echo fo_e('cart.currency_dt'); ?></strong>
                </div>

                <div class="panier-gift-note">
                    <?php echo fo_e('cart.fortune_gifts_note'); ?>
                </div>
            <?php endif; ?>

            <div class="commande-actions panier-panel-actions">
                <a href="List-Produit.php" class="btn-commande-outline">
                    <?php echo fo_e('cart.add_product'); ?>
                </a>

                <?php if ($lignesPanier !== []): ?>
                    <form method="post" action="commande.php" class="panier-form-commander">
                        <input type="hidden" name="preparer_commande" value="1">
                        <button type="submit" class="btn-commande-primary">
                            <?php echo fo_e('cart.order'); ?>
                        </button>
                    </form>
                <?php else: ?>
                    <span class="panier-commander-disabled" title="<?php echo fo_e('cart.empty_disabled'); ?>">
                        <?php echo fo_e('cart.order'); ?>
                    </span>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<?php if ($loggedIn && $role === 'client'): ?>
    <?php require __DIR__ . '/includes/fortune_wheel_modal.php'; ?>
<?php endif; ?>

<footer>
    <?php echo fo_e('footer.copyright'); ?>
</footer>

<?php if (!$loggedIn): ?>
    <?php require __DIR__ . '/includes/guest_login_gate.php'; ?>
<?php endif; ?>

<?php if ($loggedIn && $role === 'client'): ?>
<?php
$fortuneWheelBtnSelector = '.btn-fortune';
require __DIR__ . '/includes/fortune_wheel_script.php';
?>
<?php endif; ?>

</body>
</html>