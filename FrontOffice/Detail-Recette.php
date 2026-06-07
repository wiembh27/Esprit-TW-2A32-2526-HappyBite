<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/fo_i18n.php';
fo_init_i18n_for_request();
require_once __DIR__ . '/includes/fo_catalog_fragment.php';

$foCatalogInline = fo_catalog_fragment_bootstrap();

include __DIR__ . '/../Controllers/RecetteController.php';

$recetteController = new RecetteController();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die(fo_t('recipe.id_missing'));
}

$id = intval($_GET['id']);
$action = $_GET['action'] ?? 'normal';
$motCle = $_GET['motCle'] ?? '';

$recette = $recetteController->showRecetteDetails($id);

if (!$recette) {
    if ($foCatalogInline) {
        echo '<p class="text-danger mb-0">' . fo_e('recipe.not_found') . '</p>';
        return;
    }
    die(fo_t('recipe.not_found'));
}

$produitsRecette = $recetteController->getProduitsByRecette($id);
$miseEnAvant = (int) ($recette['mise_en_avant'] ?? 0);

if ($foCatalogInline) {
    ?>
<div class="fo-catalog-inline-form">
    <div class="card shadow border-0 rounded-4">
        <div class="card-body p-4">
            <div class="mb-4">
                <h2 class="fw-bold mb-2"><?php echo fo_db_e((string) $recette['nom']); ?></h2>
                <?php if ($miseEnAvant) { ?>
                    <span class="badge rounded-pill text-bg-warning mb-2"><?php echo fo_e('recipes.featured_badge'); ?></span>
                <?php } ?>
                <p class="text-muted mb-0"><?php echo fo_db_e((string) $recette['description']); ?></p>
            </div>
            <div class="mb-4 text-center">
                <?php if (!empty($recette['image'])) { ?>
                    <img src="/uploads/<?php echo htmlspecialchars((string) $recette['image']); ?>"
                         alt="<?php echo fo_db_e((string) $recette['nom']); ?>"
                         style="max-width: 280px; max-height: 280px; object-fit: cover; border-radius: 16px;">
                <?php } else { ?>
                    <p class="text-muted mb-0"><?php echo fo_e('product.no_image'); ?></p>
                <?php } ?>
            </div>
            <div class="mb-4">
                <div class="p-3 bg-light rounded-3">
                    <strong><?php echo fo_e('recipe.total_calories'); ?></strong><br>
                    <span class="text-success fs-5 fw-bold"><?php echo htmlspecialchars((string) ($recette['calories'] ?? 0)); ?> cal</span>
                </div>
            </div>
            <div class="mb-4">
                <h5 class="fw-bold"><?php echo fo_e('recipe.products_heading'); ?></h5>
                <?php if (!empty($produitsRecette)) { ?>
                    <div class="row">
                        <?php foreach ($produitsRecette as $produit) { ?>
                            <?php
                            $allergenes = array_filter(array_map('trim', explode(',', $produit['allergene'] ?? '')));
                            $benefices = array_filter(array_map('trim', explode(',', $produit['benefices'] ?? '')));
                            $isPromo = isset($produit['promo']) && $produit['promo'] !== null && $produit['promo'] !== '';
                            ?>
                            <div class="col-md-6 mb-3">
                                <div class="border rounded-3 p-3 h-100">
                                    <h6 class="fw-bold mb-2"><?php echo fo_db_e((string) $produit['nom']); ?></h6>
                                    <p class="mb-2">
                                        <strong><?php echo fo_e('product.price'); ?></strong>
                                        <?php if ($isPromo) { ?>
                                            <span class="promo-old-price"><?php echo htmlspecialchars((string) $produit['prix']); ?> DT</span>
                                            <span class="promo-new-price"><?php echo htmlspecialchars((string) $produit['promo']); ?> DT</span>
                                        <?php } else { ?>
                                            <?php echo htmlspecialchars((string) $produit['prix']); ?> DT
                                        <?php } ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong><?php echo fo_e('product.calories'); ?></strong>
                                        <?php echo htmlspecialchars((string) ($produit['calories'] ?? 0)); ?> cal
                                    </p>
                                    <div class="mb-2">
                                        <strong><?php echo fo_e('fridge.allergens'); ?></strong><br>
                                        <?php if (!empty($allergenes)) { ?>
                                            <?php foreach ($allergenes as $item) { ?>
                                                <span class="badge bg-danger me-1 mb-1"><?php echo fo_db_e($item); ?></span>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <span class="text-muted"><?php echo fo_e('fridge.none'); ?></span>
                                        <?php } ?>
                                    </div>
                                    <div>
                                        <strong><?php echo fo_e('fridge.benefits'); ?></strong><br>
                                        <?php if (!empty($benefices)) { ?>
                                            <?php foreach ($benefices as $item) { ?>
                                                <span class="badge bg-success me-1 mb-1"><?php echo fo_db_e($item); ?></span>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <span class="text-muted"><?php echo fo_e('fridge.not_specified'); ?></span>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <p class="text-muted mb-0"><?php echo fo_e('recipe.no_products'); ?></p>
                <?php } ?>
            </div>
            <div class="text-center">
                <button type="button" class="btn btn-outline-secondary rounded-pill js-fo-catalog-detail-close"><?php echo fo_e('health.inline.close'); ?></button>
            </div>
        </div>
    </div>
</div>
<style>
.fo-catalog-inline-form .promo-old-price { text-decoration: line-through; color: #6c757d; margin-right: 8px; }
.fo-catalog-inline-form .promo-new-price { color: #e65100; font-weight: 700; }
</style>
    <?php
    return;
}
?>

<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; hb_brand_render_head(); ?>

    <title><?php echo fo_e('recipe.detail_title'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700&display=swap" rel="stylesheet">

    <link rel="stylesheet" type="text/css" href="/Views/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style-original-views.css">
    <style>
        .promo-old-price {
            text-decoration: line-through;
            color: #6c757d;
            margin-right: 8px;
        }
        .promo-new-price {
            color: #e65100;
            font-weight: 700;
        }
    </style>
</head>
<body>

<?php
$nav_active = 'recettes';
require __DIR__ . '/includes/nav_front.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-success text-white rounded-top-4">
                    <h3 class="mb-0"><?php echo fo_e('recipe.detail_heading'); ?></h3>
                </div>

                <div class="card-body p-4">
                    <div class="mb-4">
                        <h2 class="fw-bold mb-2"><?php echo fo_db_e((string) $recette['nom']); ?></h2>
                        <?php if ($miseEnAvant) { ?>
                            <span class="badge rounded-pill text-bg-warning mb-2"><?php echo fo_e('recipes.featured_badge'); ?></span>
                        <?php } ?>
                        <p class="text-muted mb-0"><?php echo fo_db_e((string) $recette['description']); ?></p>
                    </div>

                    <div class="mb-4 text-center">
                        <h5 class="fw-bold mb-3"><?php echo fo_e('recipe.image'); ?></h5>
                        <?php if (!empty($recette['image'])) { ?>
                            <img
                                src="/uploads/<?php echo htmlspecialchars($recette['image']); ?>"
                                alt="<?php echo fo_db_e((string) $recette['nom']); ?>"
                                style="max-width: 280px; max-height: 280px; object-fit: cover; border-radius: 16px;"
                            >
                        <?php } else { ?>
                            <p class="text-muted mb-0"><?php echo fo_e('product.no_image'); ?></p>
                        <?php } ?>
                    </div>

                    <div class="mb-4">
                        <div class="p-3 bg-light rounded-3">
                            <strong><?php echo fo_e('recipe.total_calories'); ?></strong><br>
                            <span class="text-success fs-5 fw-bold">
                                <?php echo htmlspecialchars((string) ($recette['calories'] ?? 0)); ?> cal
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold"><?php echo fo_e('recipe.products_heading'); ?></h5>

                        <?php if (!empty($produitsRecette)) { ?>
                            <div class="row">
                                <?php foreach ($produitsRecette as $produit) { ?>
                                    <?php
                                    $allergenes = array_filter(array_map('trim', explode(',', $produit['allergene'] ?? '')));
                                    $benefices = array_filter(array_map('trim', explode(',', $produit['benefices'] ?? '')));
                                    $isPromo = isset($produit['promo']) && $produit['promo'] !== null && $produit['promo'] !== '';
                                    ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="border rounded-3 p-3 h-100">
                                            <h6 class="fw-bold mb-2"><?php echo fo_db_e((string) $produit['nom']); ?></h6>

                                            <p class="mb-2">
                                                <strong><?php echo fo_e('product.price'); ?></strong>
                                                <?php if ($isPromo) { ?>
                                                    <span class="promo-old-price"><?php echo htmlspecialchars((string) $produit['prix']); ?> DT</span>
                                                    <span class="promo-new-price"><?php echo htmlspecialchars((string) $produit['promo']); ?> DT</span>
                                                <?php } else { ?>
                                                    <?php echo htmlspecialchars((string) $produit['prix']); ?> DT
                                                <?php } ?>
                                            </p>

                                            <p class="mb-2">
                                                <strong><?php echo fo_e('product.calories'); ?></strong>
                                                <?php echo htmlspecialchars((string) ($produit['calories'] ?? 0)); ?> cal
                                            </p>

                                            <div class="mb-2">
                                                <strong><?php echo fo_e('fridge.allergens'); ?></strong><br>
                                                <?php if (!empty($allergenes)) { ?>
                                                    <?php foreach ($allergenes as $item) { ?>
                                                        <span class="badge bg-danger me-1 mb-1">
                                                            <?php echo fo_db_e($item); ?>
                                                        </span>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <span class="text-muted"><?php echo fo_e('fridge.none'); ?></span>
                                                <?php } ?>
                                            </div>

                                            <div>
                                                <strong><?php echo fo_e('fridge.benefits'); ?></strong><br>
                                                <?php if (!empty($benefices)) { ?>
                                                    <?php foreach ($benefices as $item) { ?>
                                                        <span class="badge bg-success me-1 mb-1">
                                                            <?php echo fo_db_e($item); ?>
                                                        </span>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <span class="text-muted"><?php echo fo_e('fridge.not_specified'); ?></span>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <p class="text-muted mb-0"><?php echo fo_e('recipe.no_products'); ?></p>
                        <?php } ?>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="List-Recette.php?action=<?php echo urlencode($action); ?>&motCle=<?php echo urlencode($motCle); ?>"
                           class="btn btn-secondary rounded-pill">
                            <?php echo fo_e('product.back_list'); ?>
                        </a>
                        <a href="#" class="btn btn-success rounded-pill"><?php echo fo_e('recipe.try'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer>
    <?php echo fo_e('footer.copyright'); ?>
</footer>

<script src="/Views/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
