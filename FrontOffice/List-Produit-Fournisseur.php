<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/fo_i18n.php';
fo_init_i18n_for_request();

require_once __DIR__ . '/../Controllers/ProduitController.php';
require_once __DIR__ . '/../Controllers/CategorieController.php';

$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userRole = $loggedIn ? strtolower(trim((string) ($_SESSION['user_role'] ?? ''))) : '';

if (!$loggedIn || $userRole !== 'fournisseur') {
    header('Location: List-Produit.php');
    exit;
}

$idFournisseur = (int) ($_SESSION['user_id'] ?? 0);
if ($idFournisseur < 1) {
    header('Location: List-Produit.php');
    exit;
}

$produitController = new ProduitController();
$categorieController = new CategorieController();

require_once __DIR__ . '/includes/fo_inline_crud.php';
require_once __DIR__ . '/includes/hb_action_toast.php';

if (isset($_GET['delete']) && $_GET['delete'] !== '') {
    $idProduit = (int) $_GET['delete'];
    if ($idProduit > 0) {
        $produitController->deleteProduitByIdAndUtilisateur($idProduit, $idFournisseur);
    }
    header('Location: ' . fo_inline_crud_list_url(
        'List-Produit-Fournisseur.php',
        '',
        0,
        array_merge(fo_inline_preserve_list_query(), ['notice' => 'product_deleted'])
    ));
    exit;
}

$foCatalogPreserve = fo_inline_preserve_list_query();
$catalogNotice = isset($_GET['notice']) ? preg_replace('/[^a-z_]/', '', (string) $_GET['notice']) : '';
$catalogToastMsg = hb_catalog_notice_message($catalogNotice) ?? '';

$search = trim($_GET['search'] ?? '');
$idCategorie = trim($_GET['id_categorie'] ?? '');

$categories = $categorieController->listCategories();
$produits = $produitController->listProduitsByUtilisateur(
    $idFournisseur,
    $search,
    $idCategorie !== '' ? $idCategorie : null
);

if (isset($_GET['export_excel']) && $_GET['export_excel'] == '1') {
    $produitsExport = $produits;
    $titreExport = ($search !== '' || $idCategorie !== '')
        ? 'Liste des produits filtrés (fournisseur)'
        : 'Liste complète de mes produits';

    $nomFichier = 'HappyBite_Produits_Fournisseur_' . date('Y-m-d_H-i') . '.xls';

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $baseUrl = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $logoUrl = $baseUrl . '/images/logo.png';

    $nomFournisseurExport = trim(
        (string) ($_SESSION['user_prenom'] ?? '') . ' ' . (string) ($_SESSION['user_nom'] ?? '')
    );
    if ($nomFournisseurExport === '') {
        $nomFournisseurExport = (string) ($_SESSION['user_email'] ?? 'Fournisseur');
    }

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nomFichier . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";
    ?>
<html>
<head>
<meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; hb_brand_render_head(); ?>

<style>
table { border-collapse: collapse; width: 100%; font-family: "Poppins", sans-serif; font-size: 12px; font-weight: 400; }
.brand { background-color: #ffffff; border-bottom: 3px solid #2e7d32; height: 80px; }
.logo-cell { text-align: center; padding: 8px 0; }
.title { background-color: #e8f5e9; font-size: 20px; font-weight: 700; color: #2e7d32; text-align: center; height: 40px; border: 2px solid #2e7d32; }
.subtitle { background-color: #f1f8e9; color: #666; text-align: center; font-size: 12px; height: 28px; font-weight: 500; }
.header td { background-color: #2e7d32; color: white; font-weight: 700; text-align: center; border: 1px solid #1b5e20; }
td { border: 1px solid #a5d6a7; padding: 7px; vertical-align: middle; }
.center { text-align: center; }
.promo-oui { background-color: #fff3cd; color: #856404; font-weight: 600; text-align: center; }
.promo-non { background-color: #eeeeee; color: #555; text-align: center; }
.prix-promo { background-color: #ffe082; color: #5d4037; font-weight: 600; text-align: center; }
.allergene { background-color: #f8d7da; color: #842029; font-weight: 600; }
</style>
</head>
<body>
<table>
<tr class="brand">
    <td colspan="11" class="logo-cell">
        <img src="<?php echo htmlspecialchars($logoUrl); ?>" width="92" alt="HappyBite">
    </td>
</tr>
<tr><td colspan="11" class="title"><?php echo htmlspecialchars($titreExport); ?></td></tr>
<tr><td colspan="11" class="subtitle">Export généré le <?php echo date('d/m/Y à H:i'); ?></td></tr>
<tr class="header">
    <td>ID</td>
    <td>Nom</td>
    <td>Fournisseur</td>
    <td>Prix normal</td>
    <td>Promo</td>
    <td>Prix promo</td>
    <td>Calories</td>
    <td>Catégorie</td>
    <td>Allergènes</td>
    <td>Bénéfices</td>
    <td>Date ajout</td>
</tr>
<?php foreach ($produitsExport as $produit) { ?>
<?php
    $isPromo = isset($produit['promo']) && $produit['promo'] !== null && $produit['promo'] !== '';
    $hasAllergene = !empty($produit['allergene']) && strtolower(trim((string) $produit['allergene'])) !== 'aucun';
?>
<tr>
    <td class="center"><?php echo htmlspecialchars((string) ($produit['id_produit'] ?? '')); ?></td>
    <td><?php echo htmlspecialchars((string) ($produit['nom'] ?? '')); ?></td>
    <td><?php echo htmlspecialchars($nomFournisseurExport); ?></td>
    <td class="center"><?php echo htmlspecialchars((string) ($produit['prix'] ?? '')); ?> DT</td>
    <td class="<?php echo $isPromo ? 'promo-oui' : 'promo-non'; ?>"><?php echo $isPromo ? 'Oui' : 'Non'; ?></td>
    <td class="<?php echo $isPromo ? 'prix-promo' : 'center'; ?>">
        <?php echo $isPromo ? htmlspecialchars((string) $produit['promo']) . ' DT' : '-'; ?>
    </td>
    <td class="center"><?php echo htmlspecialchars((string) ($produit['calories'] ?? 'Non défini')); ?></td>
    <td class="center"><?php echo htmlspecialchars((string) ($produit['nom_categorie'] ?? '')); ?></td>
    <td class="<?php echo $hasAllergene ? 'allergene' : 'center'; ?>">
        <?php echo htmlspecialchars((string) ($produit['allergene'] ?? 'Aucun')); ?>
    </td>
    <td><?php echo htmlspecialchars((string) ($produit['benefices'] ?? 'Non précisé')); ?></td>
    <td class="center"><?php echo htmlspecialchars((string) ($produit['date_ajout'] ?? '')); ?></td>
</tr>
<?php } ?>
</table>
</body>
</html>
<?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; hb_brand_render_head(); ?>

    <title>HappyBite — <?php echo fo_e('supplier.page_title'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Views/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style-original-views.css">
    <style>
        html, body {
            margin: 0 !important;
            padding: 0 !important;
        }
        .promo-card {
            border: 2px solid #f0ad4e !important;
            background: #fff8e1 !important;
        }
        .promo-badge {
            display: inline-block;
            background: #f0ad4e;
            color: #fff;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
        }
        .promo-old-price {
            color: #8c8c8c;
            text-decoration: line-through;
            margin-right: 8px;
        }
        .promo-new-price {
            color: #d97706;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .btn-hb-details {
            font-weight: 700 !important;
            font-size: 0.95rem;
            padding: 0.5rem 1.35rem !important;
            min-width: 7.5rem;
        }
    </style>
</head>
<body>

<main class="commande-wrap">
<div class="container py-5">
    <div class="text-center mb-4">
        <h2 class="fw-bold"><?php echo fo_e('supplier.heading'); ?></h2>
        <p class="text-muted"><?php echo sprintf(fo_e('supplier.subheading'), $idFournisseur); ?></p>
    </div>

    <div class="d-flex justify-content-center gap-3 mb-4 flex-wrap">
        <a href="List-Produit.php" class="btn btn-outline-secondary rounded-pill px-4"><?php echo fo_e('supplier.view_public'); ?></a>
        <a href="<?php echo htmlspecialchars(fo_inline_crud_list_url('List-Produit-Fournisseur.php', 'add', 0, $foCatalogPreserve), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success rounded-pill px-4"><?php echo fo_e('supplier.add_product'); ?></a>
    </div>

    <div class="rayons-section mb-4">
        <div class="rayons-header mb-3">
            <h4 class="mb-2"><?php echo fo_e('supplier.rayons'); ?></h4>
            <p class="mb-0"><?php echo fo_e('supplier.rayons_desc'); ?></p>
        </div>
        <?php if (!empty($categories)) { ?>
            <div class="rayons-scroll">
                <?php foreach ($categories as $categorie) { ?>
                    <div class="rayon-card-mini">
                        <h5><?php echo fo_db_e($categorie->getNom()); ?></h5>
                        <p>
                            <?php
                            $description = trim($categorie->getDescription() ?? '');
                            echo $description !== ''
                                ? fo_db_e($description)
                                : fo_e('supplier.category_default_desc');
                            ?>
                        </p>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label"><?php echo fo_e('supplier.search'); ?></label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        placeholder="<?php echo fo_e('supplier.search_ph'); ?>"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>
                <div class="col-md-4">
                    <label for="id_categorie" class="form-label"><?php echo fo_e('products.search_category'); ?></label>
                    <select name="id_categorie" id="id_categorie" class="form-select">
                        <option value=""><?php echo fo_e('products.all_categories'); ?></option>
                        <?php foreach ($categories as $categorie) { ?>
                            <option
                                value="<?php echo (int) $categorie->getIdCategorie(); ?>"
                                <?php echo ($idCategorie !== '' && (string) $idCategorie === (string) $categorie->getIdCategorie()) ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($categorie->getNom()); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-success"><?php echo fo_e('products.filter_btn'); ?></button>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" name="export_excel" value="1" class="btn btn-outline-success"><?php echo fo_e('supplier.export_excel'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($produits === []) { ?>
        <div class="alert alert-info text-center shadow-sm">
            <?php echo fo_e('supplier.none_found'); ?>
        </div>
    <?php } else { ?>
        <div class="row">
            <?php foreach ($produits as $produit) { ?>
                <?php
                $allergenes = array_filter(array_map('trim', explode(',', (string) ($produit['allergene'] ?? ''))));
                $benefices = array_filter(array_map('trim', explode(',', (string) ($produit['benefices'] ?? ''))));
                $isPromo = isset($produit['promo']) && $produit['promo'] !== null && $produit['promo'] !== '';
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm rounded-4 <?php echo $isPromo ? 'promo-card' : 'border-0'; ?>">
                        <div class="card-body d-flex flex-column">
                            <?php if ($isPromo) { ?>
                                <div class="mb-2">
                                    <span class="promo-badge"><?php echo fo_e('products.promo_badge'); ?></span>
                                </div>
                            <?php } ?>
                            <div class="text-center mb-3">
                                <?php if (!empty($produit['image'])) { ?>
                                    <img
                                        src="/uploads/<?php echo htmlspecialchars((string) $produit['image']); ?>"
                                        alt="<?php echo htmlspecialchars((string) $produit['nom']); ?>"
                                        style="width: 100%; max-height: 200px; object-fit: cover; border-radius: 15px;"
                                    >
                                <?php } else { ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center rounded-4" style="height: 200px;">
                                        <span class="text-muted"><?php echo fo_e('product.no_image'); ?></span>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="mb-3">
                                <h5 class="fw-bold mb-1"><?php echo fo_db_e((string) $produit['nom']); ?></h5>
                                <span class="badge bg-light text-dark">
                                    <?php echo fo_db_e((string) ($produit['nom_categorie'] ?? fo_t('supplier.no_category'))); ?>
                                </span>
                            </div>
                            <p class="mb-2">
                                <strong><?php echo fo_e('product.price'); ?></strong>
                                <?php if ($isPromo) { ?>
                                    <span class="promo-old-price"><?php echo htmlspecialchars((string) $produit['prix']); ?> DT</span>
                                    <span class="promo-new-price"><?php echo htmlspecialchars((string) $produit['promo']); ?> DT</span>
                                <?php } else { ?>
                                    <span class="text-success fw-bold"><?php echo htmlspecialchars((string) $produit['prix']); ?> DT</span>
                                <?php } ?>
                            </p>
                            <p class="mb-2">
                                <strong><?php echo fo_e('product.calories'); ?></strong>
                                <?php echo htmlspecialchars((string) ($produit['calories'] ?? fo_t('fridge.undefined'))); ?> <?php echo fo_e('products.cal_unit'); ?>
                            </p>
                            <div class="mb-3">
                                <strong><?php echo fo_e('fridge.allergens'); ?></strong><br>
                                <?php if ($allergenes !== []) { ?>
                                    <?php foreach ($allergenes as $item) { ?>
                                        <span class="badge bg-danger me-1 mb-1"><?php echo htmlspecialchars($item); ?></span>
                                    <?php } ?>
                                <?php } else { ?>
                                    <span class="text-muted"><?php echo fo_e('fridge.none'); ?></span>
                                <?php } ?>
                            </div>
                            <div class="mb-3">
                                <strong><?php echo fo_e('fridge.benefits'); ?></strong><br>
                                <?php if ($benefices !== []) { ?>
                                    <?php foreach ($benefices as $item) { ?>
                                        <span class="badge bg-success me-1 mb-1"><?php echo htmlspecialchars($item); ?></span>
                                    <?php } ?>
                                <?php } else { ?>
                                    <span class="text-muted"><?php echo fo_e('fridge.not_specified'); ?></span>
                                <?php } ?>
                            </div>
                            <div class="mt-auto">
                                <div class="d-flex flex-wrap gap-2 justify-content-between">
                                    <button type="button"
                                            class="btn btn-outline-success rounded-pill btn-hb-details js-fo-catalog-detail"
                                            data-detail-url="Detail-Produit-Fournisseur.php?id=<?php echo (int) $produit['id_produit']; ?>&fragment=1"
                                            data-detail-title="<?php echo fo_e('supplier.details'); ?>">
                                        <?php echo fo_e('supplier.details'); ?>
                                    </button>
                                    <a href="<?php echo htmlspecialchars(fo_inline_crud_list_url('List-Produit-Fournisseur.php', 'edit', (int) $produit['id_produit'], $foCatalogPreserve), ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-warning btn-sm rounded-pill"><?php echo fo_e('supplier.modify'); ?></a>
                                    <a href="List-Produit-Fournisseur.php?delete=<?php echo (int) $produit['id_produit']; ?>"
                                       class="btn btn-danger btn-sm rounded-pill"
                                       data-hb-confirm="<?php echo fo_e('supplier.delete_confirm'); ?>"><?php echo fo_e('common.delete'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>
</main>

<footer>
    <?php echo fo_e('footer.copyright'); ?>
</footer>

<script src="/Views/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<?php
require_once __DIR__ . '/includes/fo_catalog_inline.php';
fo_catalog_inline_render_panel();
$foDetailModalTitle = fo_t('supplier.details');
require_once __DIR__ . '/includes/fo_catalog_detail_modal.php';
require_once __DIR__ . '/includes/hb_brand_head.php';
hb_brand_render_footer();
hb_action_toast_script($catalogToastMsg !== '' ? $catalogToastMsg : null, 3500, $catalogToastMsg !== '', ['notice']);
?>
</body>
</html>
