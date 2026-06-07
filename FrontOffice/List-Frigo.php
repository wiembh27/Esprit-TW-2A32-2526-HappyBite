<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/fo_i18n.php';
fo_init_i18n_for_request();

require_once __DIR__ . '/../Controllers/FrigoController.php';
require_once __DIR__ . '/../Controllers/CategorieController.php';
require_once __DIR__ . '/../Controllers/AiRecetteController.php';
require_once __DIR__ . '/../config/Database.php';

$frigoController = new FrigoController();
$categorieController = new CategorieController();

$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$idUtilisateur = $loggedIn ? (int) ($_SESSION['user_id'] ?? 0) : 0;

$motCle = trim($_GET['motCle'] ?? '');
$idCategorie = trim($_GET['id_categorie'] ?? '');

$categories = $categorieController->listCategories();
$recetteIA = null;
$menuArray = [];

$profilSante = null;
if ($loggedIn && $idUtilisateur > 0) {
    if (isset($_SESSION['chefbot_menu'][$idUtilisateur])) {
        $recetteIA = $_SESSION['chefbot_menu'][$idUtilisateur];
        $menuArray = json_decode($recetteIA, true);
    }

    $db = Config::getConnexion();
    $stmt = $db->prepare('
        SELECT objectif, allergenes, carences, maladies
        FROM profil_sante
        WHERE id_utilisateur = :id_utilisateur
        LIMIT 1
    ');
    $stmt->execute(['id_utilisateur' => $idUtilisateur]);
    $rowProfil = $stmt->fetch(PDO::FETCH_ASSOC);
    $profilSante = is_array($rowProfil) ? $rowProfil : null;
}

if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generer_recette_ia'])) {
        $produitsFrigoTemp = $frigoController->getFrigoByUtilisateur($idUtilisateur, $motCle, $idCategorie);

        if (!empty($produitsFrigoTemp)) {
            $aiController = new AiRecetteController();
            $recetteIA = $aiController->genererMenuSemaine($produitsFrigoTemp, $profilSante);

            $_SESSION['chefbot_menu'][$idUtilisateur] = $recetteIA;

            $menuArray = json_decode($recetteIA, true);
        } else {
            $recetteIA = fo_t('fridge.empty_cannot_generate');
        }
    } else {
        $action = $_POST['action'] ?? '';
        $idProduit = (int) ($_POST['id_produit'] ?? 0);
        $quantite = (int) ($_POST['quantite'] ?? 1);

        if ($action === 'ajouter' && $idProduit > 0 && $quantite > 0) {
            $frigoController->ajouterAuFrigo($idUtilisateur, $idProduit, $quantite);
        }

        if ($action === 'modifier' && $idProduit > 0) {
            $frigoController->updateQuantite($idUtilisateur, $idProduit, $quantite);
        }

        if ($action === 'supprimer' && $idProduit > 0) {
            $frigoController->supprimerDuFrigo($idUtilisateur, $idProduit);
        }

        header('Location: List-Frigo.php#frigo-zone');
        exit;
    }
}

$produitsFrigo = $loggedIn && $idUtilisateur > 0
    ? $frigoController->getFrigoByUtilisateur($idUtilisateur, $motCle, $idCategorie)
    : [];
$totalProduits = $loggedIn && $idUtilisateur > 0
    ? $frigoController->getNombreProduitsDansFrigo($idUtilisateur)
    : 0;
?>

<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; hb_brand_render_head(); ?>

    <title><?php echo fo_e('fridge.title'); ?></title>
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

.hb-ai-section,
.chefbot-header {
    background: linear-gradient(135deg, #e8f8ef, #ffffff);
    border-radius: 24px;
    padding: 28px;
    margin-bottom: 25px;
    border-left: 6px solid #43a047;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

/* Titre ChefBot — même dégradé texte que « Demandez-moi » (Ai.php) */
.chefbot-header-title {
    margin-bottom: 12px;
}

.hb-gradient-title,
.chefbot-header .chefbot-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.55rem;
    line-height: 1.25;
    display: inline-block;
    background: linear-gradient(90deg, #e53935 0%, #fb8c00 52%, #43a047 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    color: transparent;
}

.chefbot-header p {
    color: #555;
    margin-bottom: 15px;
}

.chefbot-profile {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.chefbot-profile span {
    background: white;
    border-radius: 999px;
    padding: 10px 16px;
    font-weight: 600;
    color: #2f6b4f;
    border: 1px solid #d7f0e2;
}

.chefbot-scroll {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    padding: 10px 5px 25px;
    scroll-snap-type: x mandatory;
}

.chefbot-card {
    min-width: 360px;
    max-width: 360px;
    background: white;
    border-radius: 24px;
    padding: 24px;
    scroll-snap-align: start;
    border: 1px solid #eef4ef;
}

.chefbot-day {
    background: #20b978;
    color: white;
    display: inline-block;
    padding: 8px 15px;
    border-radius: 999px;
    font-weight: 700;
    margin-bottom: 15px;
}

.chefbot-card h4 {
    font-weight: 700;
    color: #173b2c;
    margin-bottom: 15px;
}

.chefbot-card h6 {
    color: #13a66b;
    font-weight: 700;
    margin-top: 15px;
}

.priority {
    background: #f4fff8;
    border-radius: 16px;
    padding: 12px;
    color: #456;
}

.why-box {
    background: #fff8e6;
    border-radius: 16px;
    padding: 14px;
    margin-top: 15px;
    color: #5d4a1f;
}

.chefbot-scroll::-webkit-scrollbar {
    height: 8px;
}

.chefbot-scroll::-webkit-scrollbar-thumb {
    background: #20b978;
    border-radius: 999px;
}

.frigo-ai-btn {
    border: 2px solid #43a047;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 12px 34px rgba(19, 30, 23, 0.25);
    color: #1d4a2f;
    padding: 10px 16px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.frigo-ai-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 36px rgba(19, 30, 23, 0.3);
}

.frigo-ai-btn-icon {
    width: 20px;
    height: 20px;
    object-fit: contain;
    display: block;
}

.frigo-ai-btn-label {
    background: linear-gradient(90deg, #e53935 0%, #fb8c00 52%, #43a047 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
</style>
</head>
<body>

<?php
$nav_active = 'frigo';
require __DIR__ . '/includes/nav_front.php';
?>

<main class="commande-wrap">
<div class="container py-5" id="frigo-zone">

    <div class="text-center mb-4">
        <h2 class="fw-bold"><?php echo fo_e('fridge.title'); ?></h2>
        <p class="text-muted"><?php echo fo_e('fridge.subtitle'); ?></p>
    </div>

    <?php if ($loggedIn): ?>
    <div class="alert alert-success text-center shadow-sm mb-4">
        <strong><?php echo fo_e('fridge.account'); ?></strong> utilisateur ID <?php echo (int) $idUtilisateur; ?><br>
        <strong><?php echo fo_e('fridge.items_count'); ?></strong> <?php echo (int) $totalProduits; ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="text-center mb-4">
        <button type="submit" name="generer_recette_ia" class="frigo-ai-btn">
            <img src="images/recette.png" alt="" class="frigo-ai-btn-icon">
            <span class="frigo-ai-btn-label"><?php echo fo_e('fridge.generate_recipe'); ?></span>
        </button>
    </form>

    <?php if (!empty($menuArray) && is_array($menuArray)): ?>

<div class="chefbot-section mb-5">

    <div class="chefbot-header hb-ai-section shadow-sm">
        <div class="chefbot-header-title">
            <h3 class="chefbot-title hb-gradient-title">ChefBot</h3>
        </div>
        <p><?php echo fo_e('fridge.chefbot_analyzed'); ?></p>

        <div class="chefbot-profile">
            <span><?php echo fo_e('fridge.objective'); ?> <?php echo fo_db_e((string) ($profilSante['objectif'] ?? fo_t('fridge.not_specified'))); ?></span>
            <span> Santé : <?php echo htmlspecialchars(($profilSante['maladies'] ?? 'aucune maladie') . ' | Allergènes : ' . ($profilSante['allergenes'] ?? 'aucun') . ' | Carences : ' . ($profilSante['carences'] ?? 'aucune')); ?></span>
        </div>
    </div>

    <div class="chefbot-scroll">
        <?php foreach ($menuArray as $jour): ?>
            <div class="chefbot-card shadow-sm">

                <div class="chefbot-day">
                    📅 <?php echo fo_db_e((string) ($jour['jour'] ?? fo_t('fridge.day'))); ?>
                </div>

                <h4><?php echo fo_db_e((string) ($jour['titre'] ?? fo_t('fridge.recipe'))); ?></h4>

                <p class="priority">
                     <strong><?php echo fo_e('fridge.priority_products'); ?></strong><br>
                    <?php echo fo_db_e((string) ($jour['produits_prioritaires'] ?? fo_t('fridge.not_specified'))); ?>
                </p>

                <h6><?php echo fo_e('fridge.ingredients'); ?></h6>
                <ul>
                    <?php foreach (($jour['ingredients'] ?? []) as $ingredient): ?>
                        <li><?php echo fo_db_e((string) $ingredient); ?></li>
                    <?php endforeach; ?>
                </ul>

                <h6><?php echo fo_e('fridge.steps'); ?></h6>
                <ol>
                    <?php foreach (($jour['etapes'] ?? []) as $etape): ?>
                        <li><?php echo fo_db_e((string) $etape); ?></li>
                    <?php endforeach; ?>
                </ol>

                <div class="why-box">
                    <strong><?php echo fo_e('fridge.why'); ?></strong><br>
                    <?php echo fo_db_e((string) ($jour['pourquoi'] ?? '')); ?>
                </div>

            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php elseif ($recetteIA): ?>

<div class="alert alert-warning">
    <?php echo nl2br(htmlspecialchars($recetteIA)); ?>
</div>

<?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label for="motCle" class="form-label"><?php echo fo_e('fridge.search_name'); ?></label>
                        <input
                            type="text"
                            class="form-control"
                            id="motCle"
                            name="motCle"
                            placeholder="<?php echo fo_e('fridge.search_ph'); ?>"
                            value="<?php echo htmlspecialchars($motCle); ?>"
                        >
                    </div>

                    <div class="col-md-5">
                        <label for="id_categorie" class="form-label"><?php echo fo_e('fridge.category'); ?></label>
                        <select class="form-select" id="id_categorie" name="id_categorie">
                            <option value=""><?php echo fo_e('fridge.all_categories'); ?></option>
                            <?php foreach ($categories as $categorie) { ?>
                                <option
                                    value="<?php echo $categorie->getIdCategorie(); ?>"
                                    <?php echo ($idCategorie == $categorie->getIdCategorie()) ? 'selected' : ''; ?>
                                >
                                    <?php echo fo_db_e($categorie->getNom()); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100"><?php echo fo_e('fridge.filter'); ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($produitsFrigo)) { ?>
        <div class="alert alert-info text-center shadow-sm">
            <?php echo fo_e('fridge.empty'); ?>
        </div>
    <?php } else { ?>
        <div class="row">
            <?php foreach ($produitsFrigo as $produit) { ?>
                <?php
                $allergenes = array_filter(array_map('trim', explode(',', $produit['allergene'] ?? '')));
                $benefices = array_filter(array_map('trim', explode(',', $produit['benefices'] ?? '')));
                ?>

                <div class="col-md-6 col-lg-4 mb-4" id="frigo-produit-<?php echo $produit['id_produit']; ?>">
                    <div class="card h-100 shadow-sm border-0 rounded-4">
                        <div class="card-body d-flex flex-column">

                            <div class="text-center mb-3">
                                <?php if (!empty($produit['image'])) { ?>
                                    <img
                                        src="/uploads/<?php echo htmlspecialchars($produit['image']); ?>"
                                        alt="<?php echo fo_db_e((string) ($produit['nom'] ?? '')); ?>"
                                        style="width: 100%; max-height: 200px; object-fit: cover; border-radius: 15px;"
                                    >
                                <?php } else { ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center rounded-4"
                                         style="height: 200px;">
                                        <span class="text-muted"><?php echo fo_e('fridge.no_image'); ?></span>
                                    </div>
                                <?php } ?>
                            </div>

                            <h5 class="fw-bold mb-2"><?php echo fo_db_e((string) $produit['nom']); ?></h5>

                            <p class="mb-2">
                                <strong><?php echo fo_e('fridge.category_label'); ?></strong>
                                <?php echo fo_db_e((string) ($produit['nom_categorie'] ?? fo_t('fridge.unclassified'))); ?>
                            </p>

                            <p class="mb-2">
                                <strong><?php echo fo_e('fridge.price'); ?></strong>
                                <span class="text-success fw-bold">
                                    <?php echo htmlspecialchars($produit['prix']); ?> DT
                                </span>
                            </p>

                            <p class="mb-2">
                                <strong><?php echo fo_e('fridge.calories'); ?></strong>
                                <?php echo htmlspecialchars((string) ($produit['calories'] ?? fo_t('fridge.undefined'))); ?> <?php echo fo_e('products.cal_unit'); ?>
                            </p>

                            <p class="mb-2">
                                <strong><?php echo fo_e('fridge.quantity'); ?></strong>
                                <?php echo htmlspecialchars($produit['quantite']); ?>
                            </p>

                            <div class="mb-3">
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

                            <div class="mb-3">
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

                            <div class="mt-auto">
                                <div class="row g-2">
                                    <div class="col-7">
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="action" value="modifier">
                                            <input type="hidden" name="id_produit" value="<?php echo $produit['id_produit']; ?>">
                                            <input
                                                type="number"
                                                name="quantite"
                                                min="0"
                                                value="<?php echo (int)$produit['quantite']; ?>"
                                                class="form-control form-control-sm rounded-pill"
                                            >
                                    </div>

                                    <div class="col-5">
                                            <button type="submit" class="btn btn-outline-success w-100 rounded-pill btn-sm">
                                                <?php echo fo_e('fridge.modify'); ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="mt-2">
                                    <form method="POST" class="m-0">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id_produit" value="<?php echo $produit['id_produit']; ?>">
                                        <button type="submit" class="btn btn-outline-danger w-100 rounded-pill btn-sm">
                                            <?php echo fo_e('fridge.remove'); ?>
                                        </button>
                                    </form>
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
<?php if (!$loggedIn) {
    require __DIR__ . '/includes/guest_login_gate.php';
} ?>
</body>
</html>



