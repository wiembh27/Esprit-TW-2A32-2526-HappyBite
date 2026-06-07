<?php
require_once __DIR__ . '/includes/bo_require_admin.php';
require_once __DIR__ . '/../Controllers/ProduitController.php';
require_once __DIR__ . '/../Controllers/CategorieController.php';
require_once __DIR__ . '/../Controllers/RecetteController.php';
require_once __DIR__ . '/../Controllers/FrigoController.php';
require_once __DIR__ . '/includes/bo_layout_start.php';

$produitController = new ProduitController();
$categorieController = new CategorieController();
$recetteController = new RecetteController();
$frigoController = new FrigoController();

$messagePromo = $_SESSION['message_promo'] ?? null;
$typeMessagePromo = $_SESSION['type_message_promo'] ?? null;

unset($_SESSION['message_promo'], $_SESSION['type_message_promo']);

/* =========================
   ACTIONS PROMO PRODUIT
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promo_product_id'], $_POST['promo_new_price'])) {
    $promoProductId = intval($_POST['promo_product_id']);
    $promoNewPrice = floatval($_POST['promo_new_price']);

    if ($promoProductId > 0) {
        $resultPromo = $produitController->setProduitPromo($promoProductId, $promoNewPrice);

        if ($resultPromo === true) {
            $_SESSION['message_promo'] = "La promo a été appliquée avec succès.";
            $_SESSION['type_message_promo'] = "success";
        } else {
            $_SESSION['message_promo'] = $resultPromo;
            $_SESSION['type_message_promo'] = "danger";
        }
    } else {
        $_SESSION['message_promo'] = "Produit invalide.";
        $_SESSION['type_message_promo'] = "danger";
    }

    header('Location: Dashboard-Produit.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_promo_product_id'])) {
    $cancelPromoProductId = intval($_POST['cancel_promo_product_id']);

    if ($cancelPromoProductId > 0) {
        $produitController->annulerPromoProduit($cancelPromoProductId);
        $_SESSION['message_promo'] = "La promo a été annulée avec succès.";
        $_SESSION['type_message_promo'] = "success";
    }

    header('Location: Dashboard-Produit.php');
    exit;
}

/* =========================
   ACTIONS SUPPRESSION
========================= */

if (isset($_GET['delete_product'])) {
    $idDelete = intval($_GET['delete_product']);

    if ($idDelete > 0) {
        $produitController->deleteProduit($idDelete);
    }

    header('Location: Dashboard-Produit.php');
    exit;
}

/* =========================
   ACTIONS RECETTES
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feature_recipe_id'])) {
    $featureRecipeId = intval($_POST['feature_recipe_id']);

    if ($featureRecipeId > 0 && method_exists($recetteController, 'setRecetteMiseEnAvant')) {
        $recetteController->setRecetteMiseEnAvant($featureRecipeId, 1);
    }

    header('Location: Dashboard-Produit.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unfeature_recipe_id'])) {
    $unfeatureRecipeId = intval($_POST['unfeature_recipe_id']);

    if ($unfeatureRecipeId > 0 && method_exists($recetteController, 'setRecetteMiseEnAvant')) {
        $recetteController->setRecetteMiseEnAvant($unfeatureRecipeId, 0);
    }

    header('Location: Dashboard-Produit.php');
    exit;
}

if (isset($_GET['delete_recipe'])) {
    $idDeleteRecipe = intval($_GET['delete_recipe']);

    if ($idDeleteRecipe > 0 && method_exists($recetteController, 'deleteRecette')) {
        $recetteController->deleteRecette($idDeleteRecipe);
    }

    header('Location: Dashboard-Produit.php');
    exit;
}

/* =========================
   DONNÉES PRINCIPALES
========================= */

$produits = $produitController->listProduits();
$categories = $categorieController->listCategories();
$recettes = $recetteController->listRecettes();

$topProduitsFrigo = method_exists($frigoController, 'getTopProduitsFrigo')
    ? $frigoController->getTopProduitsFrigo()
    : [];

$categoriesFrigo = method_exists($frigoController, 'getCategoriesLesPlusPresentes')
    ? $frigoController->getCategoriesLesPlusPresentes()
    : [];

/* =========================
   OUTILS
========================= */

function toFloatValue($value)
{
    if ($value === null || $value === '') {
        return 0;
    }

    $value = str_replace(['DT', 'dt', ' '], '', (string)$value);
    $value = str_replace(',', '.', $value);

    return floatval($value);
}

function countCsvItems($value)
{
    if (empty($value)) {
        return 0;
    }

    $items = array_filter(array_map('trim', explode(',', $value)));

    return count($items);
}

function avgValue($sum, $count)
{
    return $count > 0 ? $sum / $count : 0;
}

/* =========================
   STATS GÉNÉRALES
========================= */

$totalProduits = count($produits);
$totalCategories = count($categories);
$totalRecettes = count($recettes);

$sumPrix = 0;
$produitsParCategorie = [];
$categoryStats = [];
$productHealthData = [];

foreach ($categories as $categorie) {
    $nomCategorie = $categorie->getNom();

    $produitsParCategorie[$nomCategorie] = 0;

    $categoryStats[$nomCategorie] = [
        'count' => 0,
        'sum_calories' => 0,
        'sum_benefices' => 0,
        'sum_allergenes' => 0,
    ];
}

foreach ($produits as $produit) {
    $id = $produit['id_produit'] ?? 0;
    $nom = $produit['nom'] ?? 'Produit';
    $categorieNom = $produit['nom_categorie'] ?? 'Non classé';
    $prix = toFloatValue($produit['prix'] ?? 0);
    $promo = isset($produit['promo']) ? toFloatValue($produit['promo']) : 0;
    $calories = intval($produit['calories'] ?? 0);
    $nbBenefices = countCsvItems($produit['benefices'] ?? '');
    $nbAllergenes = countCsvItems($produit['allergene'] ?? '');
    $scoreSante = (2 * $nbBenefices) - $nbAllergenes;
    $image = $produit['image'] ?? '';

    $sumPrix += $prix;

    if (!isset($produitsParCategorie[$categorieNom])) {
        $produitsParCategorie[$categorieNom] = 0;
    }

    $produitsParCategorie[$categorieNom]++;

    if (!isset($categoryStats[$categorieNom])) {
        $categoryStats[$categorieNom] = [
            'count' => 0,
            'sum_calories' => 0,
            'sum_benefices' => 0,
            'sum_allergenes' => 0,
        ];
    }

    $categoryStats[$categorieNom]['count']++;
    $categoryStats[$categorieNom]['sum_calories'] += $calories;
    $categoryStats[$categorieNom]['sum_benefices'] += $nbBenefices;
    $categoryStats[$categorieNom]['sum_allergenes'] += $nbAllergenes;

    $productHealthData[] = [
        'id' => $id,
        'nom' => $nom,
        'score' => $scoreSante,
        'benefices' => $nbBenefices,
        'allergenes' => $nbAllergenes,
        'prix' => $prix,
        'promo' => $promo,
        'has_promo' => !empty($produit['promo']),
        'categorie' => $categorieNom,
        'image' => $image,
    ];
}

$prixMoyen = avgValue($sumPrix, $totalProduits);

/* =========================
   TOP / WORST PRODUITS
========================= */

$bestProductHealthData = $productHealthData;
usort($bestProductHealthData, fn($a, $b) => $b['score'] <=> $a['score']);
$bestProductHealthData = array_slice($bestProductHealthData, 0, 5);

$worstProductHealthData = $productHealthData;
usort($worstProductHealthData, fn($a, $b) => $a['score'] <=> $b['score']);
$worstProductHealthData = array_slice($worstProductHealthData, 0, 5);

/* =========================
   STATS RECETTES
========================= */

$recipeHealthData = [];

foreach ($recettes as $recette) {
    $idRecette = $recette['id_recette'] ?? 0;
    $nomRecette = $recette['nom'] ?? 'Recette';
    $imageRecette = $recette['image'] ?? '';
    $caloriesRecette = intval($recette['calories'] ?? 0);
    $miseEnAvant = intval($recette['mise_en_avant'] ?? 0);

    $produitsRecette = [];

    if (method_exists($recetteController, 'getProduitsByRecette')) {
        $produitsRecette = $recetteController->getProduitsByRecette($idRecette);
    }

    $sumBeneficesRecette = 0;
    $sumAllergenesRecette = 0;
    $nomsProduitsRecette = [];
    $nombreIngredients = 0;

    foreach ($produitsRecette as $prodRecette) {
        $nomsProduitsRecette[] = $prodRecette['nom'] ?? 'Produit';

        $beneficesProduit = countCsvItems($prodRecette['benefices'] ?? '');
        $allergenesProduit = countCsvItems($prodRecette['allergene'] ?? '');

        $sumBeneficesRecette += $beneficesProduit;
        $sumAllergenesRecette += $allergenesProduit;
        $nombreIngredients++;
    }

    $scoreRecetteBrut = (2 * $sumBeneficesRecette) - $sumAllergenesRecette;
    $scoreRecette = round($scoreRecetteBrut / max(1, $nombreIngredients), 2);

    $recipeHealthData[] = [
        'id' => $idRecette,
        'nom' => $nomRecette,
        'score' => $scoreRecette,
        'benefices' => $sumBeneficesRecette,
        'allergenes' => $sumAllergenesRecette,
        'calories' => $caloriesRecette,
        'image' => $imageRecette,
        'produits' => $nomsProduitsRecette,
        'ingredients_count' => $nombreIngredients,
        'mise_en_avant' => $miseEnAvant,
    ];
}

$bestRecipeHealthData = $recipeHealthData;
usort($bestRecipeHealthData, fn($a, $b) => $b['score'] <=> $a['score']);
$bestRecipeHealthData = array_slice($bestRecipeHealthData, 0, 5);

$worstRecipeHealthData = $recipeHealthData;
usort($worstRecipeHealthData, fn($a, $b) => $a['score'] <=> $b['score']);
$worstRecipeHealthData = array_slice($worstRecipeHealthData, 0, 5);

/* =========================
   CHARTS DATA
========================= */

$recetteCaloriesBands = [
    'Faible calorie' => 0,
    'Moyenne calorie' => 0,
    'Élevée calorie' => 0,
];

foreach ($recettes as $recette) {
    $calories = intval($recette['calories'] ?? 0);

    if ($calories < 150) {
        $recetteCaloriesBands['Faible calorie']++;
    } elseif ($calories <= 300) {
        $recetteCaloriesBands['Moyenne calorie']++;
    } else {
        $recetteCaloriesBands['Élevée calorie']++;
    }
}

$categoryCaloriesAvg = [];
$categoryBeneficesAvg = [];
$categoryAllergenesAvg = [];

foreach ($categoryStats as $nomCategorie => $stats) {
    $count = $stats['count'];

    $categoryCaloriesAvg[$nomCategorie] = round(avgValue($stats['sum_calories'], $count), 2);
    $categoryBeneficesAvg[$nomCategorie] = round(avgValue($stats['sum_benefices'], $count), 2);
    $categoryAllergenesAvg[$nomCategorie] = round(avgValue($stats['sum_allergenes'], $count), 2);
}

$chartProduitsParCategorieLabels = array_keys($produitsParCategorie);
$chartProduitsParCategorieValues = array_values($produitsParCategorie);

$chartRecetteCalorieLabels = array_keys($recetteCaloriesBands);
$chartRecetteCalorieValues = array_values($recetteCaloriesBands);

$chartCategoryAvgLabels = array_keys($categoryCaloriesAvg);
$chartCategoryAvgCalories = array_values($categoryCaloriesAvg);
$chartCategoryAvgBenefits = array_values($categoryBeneficesAvg);
$chartCategoryAvgAllergenes = array_values($categoryAllergenesAvg);

$chartCategoriesFrigoLabels = [];
$chartCategoriesFrigoValues = [];

foreach ($categoriesFrigo as $cat) {
    $chartCategoriesFrigoLabels[] = $cat['nom_categorie'] ?? 'Non classé';
    $chartCategoriesFrigoValues[] = $cat['total'] ?? 0;
}

$legendColors = ['#2f8b3a', '#4cb963', '#8bc34a', '#ffca28', '#29b6f6', '#ab47bc', '#ef5350'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Dashboard Produit Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="css/style.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .page-dashboard-produit .commande-wrap {
            padding-top: 8px;
        }

        .dashboard-page-title {
            font-size: 2.3rem;
            font-weight: 700;
            color: #1f3a28;
            margin: 0;
        }

        .dashboard-subtitle {
            margin: 8px 0 0;
            color: #2f3d36;
            font-size: 1rem;
            font-weight: 600;
        }

        .dashboard-kpi-card,
        .dashboard-chart-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            border: 1px solid #eef0ef;
        }

        .dashboard-kpi-card {
            padding: 22px;
            height: 100%;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .dashboard-kpi-label {
            color: #7d8b88;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .dashboard-kpi-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f3a28;
            line-height: 1.1;
        }

        .dashboard-chart-card {
            padding: 24px;
            height: 100%;
        }

        .dashboard-chart-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #204b39;
            margin-bottom: 18px;
        }

        .dashboard-section-title {
            font-size: 1.45rem;
            font-weight: 700;
            color: #1f3a28;
            margin-bottom: 18px;
        }

        .kpi-and-structure-row {
            align-items: stretch;
        }

        .kpi-stack {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            height: 100%;
        }

        .structure-card-top {
            height: 100%;
            min-height: calc(160px * 2 + 20px);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .donut-wrapper {
            max-width: 300px;
            margin: 0 auto 10px auto;
        }

        .custom-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 14px 18px;
            margin-top: auto;
            padding-top: 10px;
        }

        .custom-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            color: #65736f;
        }

        .custom-legend-color {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            display: inline-block;
            flex-shrink: 0;
        }

        .recipe-pie-wrapper {
            max-width: 340px;
            margin: 0 auto;
        }

        .chart-categories-frigo-host {
            height: 330px;
            position: relative;
        }

        .health-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
        }

        .health-scroll-wrapper {
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 8px;
            scrollbar-width: thin;
            scroll-snap-type: x mandatory;
        }

        .health-scroll-row {
            display: flex;
            gap: 16px;
        }

        .health-product-card {
            width: 100%;
            min-width: 100%;
            scroll-snap-align: start;
            background: #ffffff;
            border: 1px solid #eef0ef;
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            min-height: 360px;
            display: flex;
            flex-direction: column;
        }

        .frigo-top-card {
            background: #ffffff;
            border: 1px solid #eef0ef;
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            margin-bottom: 14px;
        }

        .frigo-rank {
            display: inline-block;
            background: #eaf6ee;
            color: #2f8b3a;
            border-radius: 999px;
            padding: 6px 12px;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .health-score-good {
            display: inline-block;
            width: fit-content;
            max-width: 100%;
            background: #eaf6ee;
            color: #2f8b3a;
            border-radius: 999px;
            padding: 6px 12px;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .health-score-bad {
            display: inline-block;
            width: fit-content;
            max-width: 100%;
            background: #fdeaea;
            color: #dc3545;
            border-radius: 999px;
            padding: 6px 12px;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .badge-front,
        .badge-promo {
            display: inline-block;
            width: fit-content;
            max-width: 100%;
            margin-bottom: 10px;
            background: #fff3cd;
            color: #856404;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .health-top-row {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .health-info-box {
            flex: 1;
            min-width: 0;
        }

        .health-image-box {
            width: 96px;
            height: 96px;
            border-radius: 16px;
            background: #f4f7f5;
            border: 1px solid #e7ece9;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            order: 2;
        }

        .health-image-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .health-image-placeholder {
            font-size: 0.8rem;
            color: #90a09a;
            text-align: center;
            padding: 8px;
        }

        .health-product-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1f2d2a;
            margin-bottom: 10px;
        }

        .health-product-meta {
            color: #6f7d79;
            font-size: 0.92rem;
            margin-bottom: 6px;
        }

        .promo-inline-form {
            margin-top: 12px;
        }

        .promo-inline-form input {
            border-radius: 999px;
            font-size: 0.95rem;
        }

        .promo-inline-form .btn {
            border-radius: 999px;
            font-size: 0.9rem;
        }

        .recipe-products-list {
            color: #6f7d79;
            font-size: 0.9rem;
            margin-top: 8px;
        }

        .recipe-actions-vertical {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 20px;
        }

        .delete-action-bottom {
            margin-top: auto;
            padding-top: 20px;
        }

        @media (max-width: 991px) {
            .kpi-stack,
            .health-grid {
                grid-template-columns: 1fr;
            }

            .structure-card-top {
                min-height: auto;
            }
        }

        @media (max-width: 768px) {
            .dashboard-page-title {
                font-size: 2rem;
            }

            .health-image-box {
                width: 82px;
                height: 82px;
            }
        }
    </style>
</head>

<body class="page-bo page-dashboard-produit">
<?php bo_layout_start('produit'); ?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1180px; width: 100%;">

        <?php
        require_once __DIR__ . '/includes/bo_catalog_chrome.php';
        bo_catalog_chrome_topbar('dashboard');
        ?>

        <div class="mb-4">
            <h1 class="dashboard-page-title">Dashboard Produit</h1>
            <p class="dashboard-subtitle">
                Vue globale, nutrition et popularité des produits, recettes...
            </p>

            <?php if (!empty($messagePromo)) { ?>
                <div class="alert alert-<?php echo htmlspecialchars((string) ($typeMessagePromo ?? '')); ?> mt-3 rounded-4">
                    <?php echo htmlspecialchars((string) ($messagePromo ?? '')); ?>
                </div>
            <?php } ?>
        </div>

        <!-- KPI + STRUCTURE -->
        <div class="row g-4 mb-5 kpi-and-structure-row">
            <div class="col-lg-4">
                <div class="kpi-stack">

                    <div class="dashboard-kpi-card">
                        <div class="dashboard-kpi-label">Produits</div>
                        <div class="dashboard-kpi-value"><?php echo $totalProduits; ?></div>
                    </div>

                    <div class="dashboard-kpi-card">
                        <div class="dashboard-kpi-label">Recettes</div>
                        <div class="dashboard-kpi-value"><?php echo $totalRecettes; ?></div>
                    </div>

                    <div class="dashboard-kpi-card">
                        <div class="dashboard-kpi-label">Catégories</div>
                        <div class="dashboard-kpi-value"><?php echo $totalCategories; ?></div>
                    </div>

                    <div class="dashboard-kpi-card">
                        <div class="dashboard-kpi-label">Prix moyen</div>
                        <div class="dashboard-kpi-value">
                            <?php echo number_format($prixMoyen, 2, '.', ' '); ?> DT
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-lg-8">
                <div class="dashboard-chart-card structure-card-top">
                    <div class="dashboard-chart-title">Structure du catalogue</div>

                    <div class="donut-wrapper">
                        <canvas id="chartProduitsCategorie"></canvas>
                    </div>

                    <div class="custom-legend">
                        <?php foreach ($chartProduitsParCategorieLabels as $index => $label) { ?>
                            <div class="custom-legend-item">
                                <span
                                    class="custom-legend-color"
                                    style="background-color: <?php echo $legendColors[$index % count($legendColors)]; ?>;"
                                ></span>
                                <span><?php echo htmlspecialchars((string) ($label ?? '')); ?></span>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SCORES SANTÉ -->
        <h2 class="dashboard-section-title">Scores santé</h2>

        <div class="health-grid mb-5">

            <!-- TOP PRODUITS -->
            <div class="dashboard-chart-card score-column-card">
                <div class="dashboard-chart-title">Top 5 meilleurs scores santé — Produits</div>

                <div class="health-scroll-wrapper">
                    <div class="health-scroll-row">

                        <?php if (empty($bestProductHealthData)) { ?>
                            <div class="text-muted">Aucun produit disponible.</div>
                        <?php } ?>

                        <?php foreach ($bestProductHealthData as $item) { ?>
                            <div class="health-product-card">

                                <div class="health-score-good">
                                    Score santé : <?php echo htmlspecialchars((string) ($item['score'] ?? '')); ?>
                                </div>

                                <?php if ($item['has_promo']) { ?>
                                    <div class="badge-promo">Produit en promo</div>
                                <?php } ?>

                                <div class="health-top-row">
                                    <div class="health-info-box">
                                        <div class="health-product-name">
                                            <?php echo htmlspecialchars((string) ($item['nom'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Catégorie :</strong>
                                            <?php echo htmlspecialchars((string) ($item['categorie'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Bénéfices :</strong>
                                            <?php echo htmlspecialchars((string) ($item['benefices'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Allergènes :</strong>
                                            <?php echo htmlspecialchars((string) ($item['allergenes'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Prix normal :</strong>
                                            <?php echo number_format($item['prix'], 2, '.', ' '); ?> DT
                                        </div>

                                        <?php if ($item['has_promo']) { ?>
                                            <div class="health-product-meta">
                                                <strong>Prix promo :</strong>
                                                <?php echo number_format($item['promo'], 2, '.', ' '); ?> DT
                                            </div>
                                        <?php } ?>
                                    </div>

                                    <div class="health-image-box">
                                        <?php if (!empty($item['image'])) { ?>
                                            <img
                                                src="../uploads/<?php echo htmlspecialchars((string) ($item['image'] ?? '')); ?>"
                                                alt="Produit"
                                            >
                                        <?php } else { ?>
                                            <div class="health-image-placeholder">Pas d’image</div>
                                        <?php } ?>
                                    </div>
                                </div>

                                <form method="POST" class="promo-inline-form">
                                    <input
                                        type="hidden"
                                        name="promo_product_id"
                                        value="<?php echo intval($item['id']); ?>"
                                    >

                                    <div class="row g-2">
                                        <div class="col-12">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                name="promo_new_price"
                                                class="form-control"
                                                value="<?php echo $item['has_promo'] ? number_format($item['promo'], 2, '.', '') : number_format($item['prix'] * 0.90, 2, '.', ''); ?>"
                                                required
                                            >
                                        </div>

                                        <div class="col-6">
                                            <button type="submit" class="btn btn-warning w-100">
                                                Faire promo
                                            </button>
                                        </div>

                                        <div class="col-6">
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary w-100"
                                                onclick="document.getElementById('cancel-promo-<?php echo intval($item['id']); ?>').submit();"
                                            >
                                                Annuler promo
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <form method="POST" id="cancel-promo-<?php echo intval($item['id']); ?>" style="display:none;">
                                    <input
                                        type="hidden"
                                        name="cancel_promo_product_id"
                                        value="<?php echo intval($item['id']); ?>"
                                    >
                                </form>

                            </div>
                        <?php } ?>

                    </div>
                </div>
            </div>

            <!-- TOP RECETTES -->
            <div class="dashboard-chart-card score-column-card">
                <div class="dashboard-chart-title">Top 5 meilleurs scores santé — Recettes</div>

                <div class="health-scroll-wrapper">
                    <div class="health-scroll-row">

                        <?php if (empty($bestRecipeHealthData)) { ?>
                            <div class="text-muted">Aucune recette disponible.</div>
                        <?php } ?>

                        <?php foreach ($bestRecipeHealthData as $item) { ?>
                            <div class="health-product-card">

                                <div class="health-score-good">
                                    Score santé : <?php echo htmlspecialchars((string) ($item['score'] ?? '')); ?>
                                </div>

                                <?php if (!empty($item['mise_en_avant'])) { ?>
                                    <div class="badge-front">Mise en avant</div>
                                <?php } ?>

                                <div class="health-top-row">
                                    <div class="health-info-box">
                                        <div class="health-product-name">
                                            <?php echo htmlspecialchars((string) ($item['nom'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Calories :</strong>
                                            <?php echo htmlspecialchars((string) ($item['calories'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Ingrédients :</strong>
                                            <?php echo htmlspecialchars((string) ($item['ingredients_count'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Bénéfices :</strong>
                                            <?php echo htmlspecialchars((string) ($item['benefices'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Allergènes :</strong>
                                            <?php echo htmlspecialchars((string) ($item['allergenes'] ?? '')); ?>
                                        </div>
                                    </div>

                                    <div class="health-image-box">
                                        <?php if (!empty($item['image'])) { ?>
                                            <img
                                                src="../uploads/<?php echo htmlspecialchars((string) ($item['image'] ?? '')); ?>"
                                                alt="Recette"
                                            >
                                        <?php } else { ?>
                                            <div class="health-image-placeholder">Pas d’image</div>
                                        <?php } ?>
                                    </div>
                                </div>

                                <div class="recipe-products-list">
                                    <strong>Produits :</strong>
                                    <?php echo htmlspecialchars(!empty($item['produits']) ? implode(', ', $item['produits']) : 'Aucun'); ?>
                                </div>

                                <div class="recipe-actions-vertical">
                                    <form method="POST">
                                        <input
                                            type="hidden"
                                            name="feature_recipe_id"
                                            value="<?php echo intval($item['id']); ?>"
                                        >
                                        <button type="submit" class="btn btn-success w-100">
                                            Mettre en avant
                                        </button>
                                    </form>

                                    <form method="POST">
                                        <input
                                            type="hidden"
                                            name="unfeature_recipe_id"
                                            value="<?php echo intval($item['id']); ?>"
                                        >
                                        <button type="submit" class="btn btn-outline-secondary w-100">
                                            Annuler
                                        </button>
                                    </form>
                                </div>

                            </div>
                        <?php } ?>

                    </div>
                </div>
            </div>

            <!-- WORST PRODUITS -->
            <div class="dashboard-chart-card score-column-card">
                <div class="dashboard-chart-title">Top 5 pires scores santé — Produits</div>

                <div class="health-scroll-wrapper">
                    <div class="health-scroll-row">

                        <?php if (empty($worstProductHealthData)) { ?>
                            <div class="text-muted">Aucun produit disponible.</div>
                        <?php } ?>

                        <?php foreach ($worstProductHealthData as $item) { ?>
                            <div class="health-product-card">

                                <div class="health-score-bad">
                                    Score santé : <?php echo htmlspecialchars((string) ($item['score'] ?? '')); ?>
                                </div>

                                <div class="health-top-row">
                                    <div class="health-info-box">
                                        <div class="health-product-name">
                                            <?php echo htmlspecialchars((string) ($item['nom'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Catégorie :</strong>
                                            <?php echo htmlspecialchars((string) ($item['categorie'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Bénéfices :</strong>
                                            <?php echo htmlspecialchars((string) ($item['benefices'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Allergènes :</strong>
                                            <?php echo htmlspecialchars((string) ($item['allergenes'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Prix normal :</strong>
                                            <?php echo number_format($item['prix'], 2, '.', ' '); ?> DT
                                        </div>
                                    </div>

                                    <div class="health-image-box">
                                        <?php if (!empty($item['image'])) { ?>
                                            <img
                                                src="../uploads/<?php echo htmlspecialchars((string) ($item['image'] ?? '')); ?>"
                                                alt="Produit"
                                            >
                                        <?php } else { ?>
                                            <div class="health-image-placeholder">Pas d’image</div>
                                        <?php } ?>
                                    </div>
                                </div>

                                <div class="delete-action-bottom">
                                    <a
                                        href="Dashboard-Produit.php?delete_product=<?php echo intval($item['id']); ?>"
                                        class="bo-img-link bo-img-link--block"
                                        title="Supprimer ce produit"
                                        aria-label="Supprimer ce produit"
                                        onclick="return confirm('Voulez-vous vraiment supprimer ce produit ?');"
                                    ><img src="images/delete.png" width="24" height="24" alt=""></a>
                                </div>

                            </div>
                        <?php } ?>

                    </div>
                </div>
            </div>

            <!-- WORST RECETTES -->
            <div class="dashboard-chart-card score-column-card">
                <div class="dashboard-chart-title">Top 5 pires scores santé — Recettes</div>

                <div class="health-scroll-wrapper">
                    <div class="health-scroll-row">

                        <?php if (empty($worstRecipeHealthData)) { ?>
                            <div class="text-muted">Aucune recette disponible.</div>
                        <?php } ?>

                        <?php foreach ($worstRecipeHealthData as $item) { ?>
                            <div class="health-product-card">

                                <div class="health-score-bad">
                                    Score santé : <?php echo htmlspecialchars((string) ($item['score'] ?? '')); ?>
                                </div>

                                <div class="health-top-row">
                                    <div class="health-info-box">
                                        <div class="health-product-name">
                                            <?php echo htmlspecialchars((string) ($item['nom'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Calories :</strong>
                                            <?php echo htmlspecialchars((string) ($item['calories'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Ingrédients :</strong>
                                            <?php echo htmlspecialchars((string) ($item['ingredients_count'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Bénéfices :</strong>
                                            <?php echo htmlspecialchars((string) ($item['benefices'] ?? '')); ?>
                                        </div>

                                        <div class="health-product-meta">
                                            <strong>Allergènes :</strong>
                                            <?php echo htmlspecialchars((string) ($item['allergenes'] ?? '')); ?>
                                        </div>
                                    </div>

                                    <div class="health-image-box">
                                        <?php if (!empty($item['image'])) { ?>
                                            <img
                                                src="../uploads/<?php echo htmlspecialchars((string) ($item['image'] ?? '')); ?>"
                                                alt="Recette"
                                            >
                                        <?php } else { ?>
                                            <div class="health-image-placeholder">Pas d’image</div>
                                        <?php } ?>
                                    </div>
                                </div>

                                <div class="recipe-products-list">
                                    <strong>Produits :</strong>
                                    <?php echo htmlspecialchars(!empty($item['produits']) ? implode(', ', $item['produits']) : 'Aucun'); ?>
                                </div>

                                <div class="delete-action-bottom">
                                    <a
                                        href="Dashboard-Produit.php?delete_recipe=<?php echo intval($item['id']); ?>"
                                        class="bo-img-link bo-img-link--block"
                                        title="Supprimer cette recette"
                                        aria-label="Supprimer cette recette"
                                        onclick="return confirm('Voulez-vous vraiment supprimer cette recette ?');"
                                    ><img src="images/delete.png" width="24" height="24" alt=""></a>
                                </div>

                            </div>
                        <?php } ?>

                    </div>
                </div>
            </div>

        </div>

        <!-- FRIGO -->
        <h2 class="dashboard-section-title">Frigo</h2>

        <div class="row g-4 mb-5">

            <div class="col-lg-5">
                <div class="dashboard-chart-card">
                    <div class="dashboard-chart-title">Top produits dans les frigos</div>

                    <?php if (empty($topProduitsFrigo)) { ?>
                        <div class="text-muted">Aucune donnée frigo disponible.</div>
                    <?php } else { ?>
                        <?php $rang = 1; ?>
                        <?php foreach ($topProduitsFrigo as $item) { ?>
                            <div class="frigo-top-card">
                                <div class="frigo-rank">#<?php echo $rang; ?></div>

                                <div class="health-product-name">
                                    <?php echo htmlspecialchars((string) ($item['nom'] ?? 'Produit')); ?>
                                </div>

                                <div class="health-product-meta">
                                    Présent <?php echo htmlspecialchars((string) ($item['total'] ?? 0)); ?> fois dans les frigos
                                </div>
                            </div>
                            <?php $rang++; ?>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="dashboard-chart-card">
                    <div class="dashboard-chart-title">Catégories les plus présentes dans les frigos</div>

                    <div class="chart-categories-frigo-host">
                        <canvas id="chartCategoriesFrigo"></canvas>
                    </div>
                </div>
            </div>

        </div>

        <!-- RECETTES -->
        <h2 class="dashboard-section-title">Recettes</h2>

        <div class="row g-4 mb-5">
            <div class="col-lg-12">
                <div class="dashboard-chart-card">
                    <div class="dashboard-chart-title">
                        Répartition des recettes par tranche de calories
                    </div>

                    <div class="recipe-pie-wrapper">
                        <canvas id="chartRecettesCalories"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php bo_layout_end(); ?>

<script>
const chartProduitsParCategorieLabels = <?php echo json_encode($chartProduitsParCategorieLabels, JSON_UNESCAPED_UNICODE); ?>;
const chartProduitsParCategorieValues = <?php echo json_encode($chartProduitsParCategorieValues); ?>;

const chartRecetteCalorieLabels = <?php echo json_encode($chartRecetteCalorieLabels, JSON_UNESCAPED_UNICODE); ?>;
const chartRecetteCalorieValues = <?php echo json_encode($chartRecetteCalorieValues); ?>;

const chartCategoriesFrigoLabels = <?php echo json_encode($chartCategoriesFrigoLabels, JSON_UNESCAPED_UNICODE); ?>;
const chartCategoriesFrigoValues = <?php echo json_encode($chartCategoriesFrigoValues); ?>;

const frigoCategoryBarColors = [
    '#2f8b3a',
    '#4cb963',
    '#8bc34a',
    '#ffca28',
    '#29b6f6',
    '#ab47bc',
    '#ef5350'
];

const doughnutLabelPlugin = {
    id: 'doughnutLabelPlugin',
    afterDatasetsDraw(chart) {
        if (chart.config.type !== 'doughnut') return;

        const { ctx } = chart;
        const dataset = chart.data.datasets[0];
        const meta = chart.getDatasetMeta(0);

        meta.data.forEach((element, index) => {
            const value = dataset.data[index];
            const position = element.tooltipPosition();

            ctx.save();
            ctx.fillStyle = '#1f2d2a';
            ctx.font = '500 13px Poppins, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(value, position.x, position.y);
            ctx.restore();
        });
    }
};

Chart.register(doughnutLabelPlugin);

const chartProduitsCategorieCanvas = document.getElementById('chartProduitsCategorie');

if (chartProduitsCategorieCanvas) {
    new Chart(chartProduitsCategorieCanvas, {
        type: 'doughnut',
        data: {
            labels: chartProduitsParCategorieLabels,
            datasets: [{
                data: chartProduitsParCategorieValues,
                backgroundColor: [
                    '#2f8b3a',
                    '#4cb963',
                    '#8bc34a',
                    '#ffca28',
                    '#29b6f6',
                    '#ab47bc',
                    '#ef5350'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.1,
            cutout: '52%',
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

const chartCategoriesFrigoCanvas = document.getElementById('chartCategoriesFrigo');

if (chartCategoriesFrigoCanvas) {
    new Chart(chartCategoriesFrigoCanvas, {
        type: 'bar',
        data: {
            labels: chartCategoriesFrigoLabels,
            datasets: [{
                label: 'Présence dans les frigos',
                data: chartCategoriesFrigoValues,
                backgroundColor: chartCategoriesFrigoLabels.map((_, i) => {
                    return frigoCategoryBarColors[i % frigoCategoryBarColors.length];
                }),
                borderRadius: 10,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    grid: {
                        color: 'rgba(44, 126, 52, 0.12)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

const chartRecettesCaloriesCanvas = document.getElementById('chartRecettesCalories');

if (chartRecettesCaloriesCanvas) {
    new Chart(chartRecettesCaloriesCanvas, {
        type: 'pie',
        data: {
            labels: chartRecetteCalorieLabels,
            datasets: [{
                data: chartRecetteCalorieValues,
                backgroundColor: ['#4cb963', '#ffca28', '#ef5350']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.15,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>