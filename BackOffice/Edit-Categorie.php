<?php
include __DIR__ . '/../Controllers/CategorieController.php';
require_once __DIR__ . '/../Models/Categorie.php';

$error = "";

$categorieController = new CategorieController();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID de la catégorie manquant.");
}

$id = intval($_GET['id']);
$categorie = $categorieController->showCategorie($id);

if (!$categorie) {
    die("Catégorie introuvable.");
}
if (strtolower($categorie->getNom()) === 'non classé') {
    die("Impossible de modifier la catégorie de secours.");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $errors = [];

    // Nom obligatoire
    if ($nom === '') {
        $errors[] = "Le nom de la catégorie est obligatoire.";
    }

    // Longueur minimale
    if (strlen($nom) < 2) {
        $errors[] = "Le nom de la catégorie doit contenir au moins 2 caractères.";
    }

    // Seulement lettres et espaces
    if ($nom !== '' && !preg_match("/^[\p{L}\s]+$/u", $nom)) {
        $errors[] = "Le nom ne doit contenir que des lettres et des espaces.";
    }

    // Description trop longue
    if (strlen($description) > 255) {
        $errors[] = "La description ne doit pas dépasser 255 caractères.";
    }

    if (strlen($description) < 20) { 
        $errors[] = "La description doit dépasser 20 caractères."; 
        }

    // Vérification doublon en excluant la catégorie actuelle
    if ($nom !== '') {
        $categories = $categorieController->listCategories();

        foreach ($categories as $cat) {
            if (
                $cat->getIdCategorie() != $id &&
                mb_strtolower(trim($cat->getNom())) === mb_strtolower($nom)
            ) {
                $errors[] = "Une catégorie avec ce nom existe déjà.";
                break;
            }
        }
    }

    if (!empty($errors)) {
        $error = implode(" ",$errors);
    } else {
        $categorieModifiee = new Categorie($nom, $description);
        $categorieController->updateCategorie($categorieModifiee, $id);

        header('Location: List-Categorie.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Modifier une catégorie</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <?php
    require_once __DIR__ . '/includes/bo_catalog_chrome.php';
    bo_catalog_chrome_styles();
    ?>
</head>
<body class="page-bo page-bo-catalog-form page-form-edit-categorie">
<?php
require_once __DIR__ . '/includes/bo_layout_start.php';
bo_layout_start('produit');
?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1180px; width: 100%;">
        <?php bo_catalog_chrome_topbar('categorie'); ?>

        <div class="list-produit-head">
            <div>
                <h1 class="list-produit-title">Modifier une catégorie</h1>
                <p class="list-produit-subtitle">Mettez à jour les informations de cette catégorie</p>
            </div>
            <a href="List-Categorie.php" class="btn-commande-outline">Retour à la liste</a>
        </div>

        <section class="bo-panel" aria-label="Formulaire catégorie">
            <?php if (!empty($error)) { ?>
                <div class="bo-flash-error"><?php echo htmlspecialchars($error); ?></div>
            <?php } ?>

            <form method="POST" action="">
                <div class="bo-field">
                    <label for="nom">Nom de la catégorie</label>
                    <input
                        type="text"
                        id="nom"
                        name="nom"
                        value="<?php echo htmlspecialchars($_POST['nom'] ?? $categorie->getNom()); ?>"
                    >
                </div>

                <div class="bo-field" style="margin-top: 18px;">
                    <label for="description">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                    ><?php echo htmlspecialchars($_POST['description'] ?? $categorie->getDescription()); ?></textarea>
                </div>

                <div class="bo-form-actions">
                    <a href="List-Categorie.php" class="btn-commande-outline">Retour</a>
                    <button type="submit" class="bo-btn-primary">Enregistrer</button>
                </div>
            </form>
        </section>
    </div>
</main>

<?php bo_layout_end(); ?>
<script src="/Views/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

