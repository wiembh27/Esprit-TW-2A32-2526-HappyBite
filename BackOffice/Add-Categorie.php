<?php
include __DIR__ . '/../Controllers/CategorieController.php';
require_once __DIR__ . '/../Models/Categorie.php';

$error = "";

$categorieController = new CategorieController();
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
        $errors[] = "La description  doit  dépasser 20 caractères.";
    }

    // Vérification doublon
    if ($nom !== '') {
        $categories = $categorieController->listCategories();

        foreach ($categories as $cat) {
            if (mb_strtolower(trim($cat->getNom())) === mb_strtolower($nom)) {
                $errors[] = "Une catégorie avec ce nom existe déjà.";
                break;
            }
        }
    }

    if (!empty($errors)) {
        $error = implode(" ",$errors);
    } else {
        $categorie = new Categorie($nom, $description);
        $categorieController->addCategorie($categorie);

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

    <title>Ajouter une catégorie</title>
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
<body class="page-bo page-bo-catalog-form page-form-add-categorie">
<?php
require_once __DIR__ . '/includes/bo_layout_start.php';
bo_layout_start('produit');
?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1180px; width: 100%;">
        <?php bo_catalog_chrome_topbar('categorie'); ?>

        <div class="list-produit-head">
            <div>
                <h1 class="list-produit-title">Ajouter une catégorie</h1>
                <p class="list-produit-subtitle">Organisez le catalogue avec une nouvelle catégorie</p>
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
                        placeholder="Ex. Fruits, Légumes, Boissons…"
                        value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>"
                    >
                </div>

                <div class="bo-field" style="margin-top: 18px;">
                    <label for="description">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        placeholder="Décrivez brièvement cette catégorie…"
                    ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="bo-form-actions">
                    <a href="List-Categorie.php" class="btn-commande-outline">Retour</a>
                    <button type="submit" class="bo-btn-primary">Ajouter</button>
                </div>
            </form>
        </section>
    </div>
</main>

<?php bo_layout_end(); ?>
<script src="/Views/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

