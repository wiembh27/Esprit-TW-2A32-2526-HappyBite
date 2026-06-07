<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/fo_i18n.php';
fo_init_i18n_for_request();
require_once __DIR__ . '/includes/fo_catalog_fragment.php';

$foCatalogInline = fo_catalog_fragment_bootstrap();

include __DIR__ . '/../Controllers/ProduitController.php';

$produitController = new ProduitController();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die(fo_t('fridge.id_missing'));
}

$id = intval($_GET['id']);

$produit = $produitController->showProduitDetails($id);

if (!$produit) {
    if ($foCatalogInline) {
        echo '<p class="text-danger mb-0">' . fo_e('fridge.not_found') . '</p>';
        return;
    }
    die(fo_t('fridge.not_found'));
}

$allergenes = array_filter(array_map('trim', explode(',', $produit['allergene'] ?? '')));
$benefices = array_filter(array_map('trim', explode(',', $produit['benefices'] ?? '')));
$isPromo = isset($produit['promo']) && $produit['promo'] !== null && $produit['promo'] !== '';

if ($foCatalogInline) {
    ?>
<div class="fo-catalog-inline-form">
    <div class="card shadow border-0 rounded-4">
        <div class="card-body p-4">
            <div class="mb-4">
                <h2 class="fw-bold mb-2"><?php echo fo_db_e((string) $produit['nom']); ?></h2>
                <?php if ($isPromo) { ?>
                    <span class="promo-badge"><?php echo fo_e('products.promo_badge'); ?></span>
                <?php } ?>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark fs-6"><?php echo fo_db_e((string) $produit['nom_categorie']); ?></span>
                    <span class="badge bg-secondary fs-6">
                        <?php echo fo_e('product.supplier'); ?> <?php echo fo_db_e((string) ($produit['nom_fournisseur'] ?? fo_t('product.supplier_unknown'))); ?>
                    </span>
                </div>
            </div>
            <div class="mb-4 text-center">
                <?php if (!empty($produit['image'])) { ?>
                    <img src="/uploads/<?php echo htmlspecialchars((string) $produit['image']); ?>"
                         alt="<?php echo fo_db_e((string) $produit['nom']); ?>"
                         style="max-width: 250px; max-height: 250px; object-fit: cover; border-radius: 16px;">
                <?php } else { ?>
                    <p class="text-muted mb-0"><?php echo fo_e('product.no_image'); ?></p>
                <?php } ?>
            </div>
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="p-3 bg-light rounded-3 h-100">
                        <strong><?php echo fo_e('product.price'); ?></strong><br>
                        <?php if ($isPromo) { ?>
                            <span class="promo-old-price fs-6"><?php echo htmlspecialchars((string) $produit['prix']); ?> DT</span>
                            <span class="promo-new-price fs-5"><?php echo htmlspecialchars((string) $produit['promo']); ?> DT</span>
                        <?php } else { ?>
                            <span class="text-success fs-5 fw-bold"><?php echo htmlspecialchars((string) $produit['prix']); ?> DT</span>
                        <?php } ?>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="p-3 bg-light rounded-3 h-100">
                        <strong><?php echo fo_e('product.calories'); ?></strong><br>
                        <span class="fs-5"><?php echo htmlspecialchars((string) ($produit['calories'] ?? fo_t('fridge.undefined'))); ?></span>
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <h5 class="fw-bold"><?php echo fo_e('product.allergens_heading'); ?></h5>
                <?php if (!empty($allergenes)) { ?>
                    <?php foreach ($allergenes as $item) { ?>
                        <span class="badge bg-danger me-1 mb-1"><?php echo fo_db_e($item); ?></span>
                    <?php } ?>
                <?php } else { ?>
                    <p class="text-muted mb-0"><?php echo fo_e('product.no_allergens'); ?></p>
                <?php } ?>
            </div>
            <div class="mb-4">
                <h5 class="fw-bold"><?php echo fo_e('product.benefits'); ?></h5>
                <?php if (!empty($benefices)) { ?>
                    <?php foreach ($benefices as $item) { ?>
                        <span class="badge bg-success me-1 mb-1"><?php echo fo_db_e($item); ?></span>
                    <?php } ?>
                <?php } else { ?>
                    <p class="text-muted mb-0"><?php echo fo_e('product.no_benefits'); ?></p>
                <?php } ?>
            </div>
            <p class="mb-0"><strong><?php echo fo_e('product.added_date'); ?></strong> <?php echo htmlspecialchars((string) $produit['date_ajout']); ?></p>
            <div class="mt-4 text-center">
                <button type="button" class="btn btn-outline-secondary rounded-pill js-fo-catalog-detail-close"><?php echo fo_e('health.inline.close'); ?></button>
            </div>
        </div>
    </div>
</div>
<style>
.fo-catalog-inline-form .promo-badge { display: inline-block; background: #f0ad4e; color: #fff; font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 999px; margin-bottom: 8px; }
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

    <title><?php echo fo_e('product.detail_title'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700&display=swap" rel="stylesheet">

    <link rel="stylesheet" type="text/css" href="/Views/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style-original-views.css">
    <style>
        .promo-badge {
            display: inline-block;
            background: #f0ad4e;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
            margin-bottom: 8px;
        }
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
$nav_active = 'produits';
require __DIR__ . '/includes/nav_front.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-success text-white rounded-top-4">
                    <h3 class="mb-0"><?php echo fo_e('product.detail_heading'); ?></h3>
                </div>

                <div class="card-body p-4">
                    <div class="mb-4">
                        <h2 class="fw-bold mb-2"><?php echo fo_db_e((string) $produit['nom']); ?></h2>
                        <?php if ($isPromo) { ?>
                            <span class="promo-badge"><?php echo fo_e('products.promo_badge'); ?></span>
                        <?php } ?>

                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-light text-dark fs-6">
                                <?php echo fo_db_e((string) $produit['nom_categorie']); ?>
                            </span>

                            <span class="badge bg-secondary fs-6">
                                <?php echo fo_e('product.supplier'); ?> <?php echo fo_db_e((string) ($produit['nom_fournisseur'] ?? fo_t('product.supplier_unknown'))); ?>
                            </span>
                        </div>
                    </div>

                    <div class="mb-4 text-center">
                        <h5 class="fw-bold mb-3"><?php echo fo_e('product.image'); ?></h5>
                        <?php if (!empty($produit['image'])) { ?>
                            <img
                                src="/uploads/<?php echo htmlspecialchars($produit['image']); ?>"
                                alt="<?php echo fo_db_e((string) $produit['nom']); ?>"
                                style="max-width: 250px; max-height: 250px; object-fit: cover; border-radius: 16px;"
                            >
                        <?php } else { ?>
                            <p class="text-muted mb-0"><?php echo fo_e('product.no_image'); ?></p>
                        <?php } ?>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <strong><?php echo fo_e('product.price'); ?></strong><br>
                                <?php if ($isPromo) { ?>
                                    <span class="promo-old-price fs-6">
                                        <?php echo htmlspecialchars((string) $produit['prix']); ?> DT
                                    </span>
                                    <span class="promo-new-price fs-5">
                                        <?php echo htmlspecialchars((string) $produit['promo']); ?> DT
                                    </span>
                                <?php } else { ?>
                                    <span class="text-success fs-5 fw-bold">
                                        <?php echo htmlspecialchars((string) $produit['prix']); ?> DT
                                    </span>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <strong><?php echo fo_e('product.calories'); ?></strong><br>
                                <span class="fs-5">
                                    <?php echo htmlspecialchars((string) ($produit['calories'] ?? fo_t('fridge.undefined'))); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold"><?php echo fo_e('product.allergens_heading'); ?></h5>
                        <?php if (!empty($allergenes)) { ?>
                            <?php foreach ($allergenes as $item) { ?>
                                <span class="badge bg-danger me-1 mb-1"><?php echo fo_db_e($item); ?></span>
                            <?php } ?>
                        <?php } else { ?>
                            <p class="text-muted mb-0"><?php echo fo_e('product.no_allergens'); ?></p>
                        <?php } ?>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold"><?php echo fo_e('product.benefits'); ?></h5>
                        <?php if (!empty($benefices)) { ?>
                            <?php foreach ($benefices as $item) { ?>
                                <span class="badge bg-success me-1 mb-1"><?php echo fo_db_e($item); ?></span>
                            <?php } ?>
                        <?php } else { ?>
                            <p class="text-muted mb-0"><?php echo fo_e('product.no_benefits'); ?></p>
                        <?php } ?>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold"><?php echo fo_e('product.extra_info'); ?></h5>
                        <p class="mb-0">
                            <strong><?php echo fo_e('product.added_date'); ?></strong>
                            <?php echo htmlspecialchars((string) $produit['date_ajout']); ?>
                        </p>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="List-Produit.php" class="btn btn-secondary rounded-pill"><?php echo fo_e('product.back_list'); ?></a>
                        <a href="#" class="btn btn-success rounded-pill"><?php echo fo_e('product.add_fridge'); ?></a>
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
