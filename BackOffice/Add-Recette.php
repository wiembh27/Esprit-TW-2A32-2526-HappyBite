<?php
$boCatalogInline = defined('BO_CATALOG_INLINE') && BO_CATALOG_INLINE;

if (!$boCatalogInline && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $q = isset($_GET['embed']) ? '?embed=1&' : '?';
    header('Location: List-Recette.php' . $q . 'action=add#bo-inline-crud');
    exit;
}

include __DIR__ . '/../Controllers/RecetteController.php';
include __DIR__ . '/../Controllers/ProduitController.php';
require_once __DIR__ . '/../Models/Recette.php';

$error = [];

$recetteController = new RecetteController();
$produitController = new ProduitController();

$produits = $produitController->listProduits();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $produitsSelectionnes = $_POST['produits'] ?? [];
    $image = "";

    $errors = [];

    if ($nom === '') {
        $errors[] = "Le nom de la recette est obligatoire.";
    }

    if (mb_strlen($nom) < 2) {
        $errors[] = "Le nom de la recette doit contenir au moins 2 caractères.";
    }

    if ($nom !== '' && !preg_match("/^[\p{L}0-9\s%\-\'()]+$/u", $nom)) {
        $errors[] = "Le nom de la recette contient des caractères non autorisés.";
    }

    preg_match_all('/\p{L}/u', $nom, $matches);
    if ($nom !== '' && (!isset($matches[0]) || count($matches[0]) < 3)) {
        $errors[] = "Le nom de la recette doit contenir au moins 3 lettres.";
    }

    if ($nom !== '' && !preg_match('/\p{L}/u', $nom)) {
        $errors[] = "Le nom de la recette ne peut pas être composé uniquement de chiffres ou de symboles.";
    }

    if ($description === '') {
        $errors[] = "La description est obligatoire.";
    }

    if ($description !== '' && mb_strlen($description) < 10) {
        $errors[] = "La description doit contenir au moins 10 caractères.";
    }

    if (empty($produitsSelectionnes)) {
        $errors[] = "Veuillez sélectionner au moins un produit.";
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] === 4) {
        $errors[] = "L'image de la recette est obligatoire.";
    } else {
        if ($_FILES['image']['error'] === 0) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
            $originalName = $_FILES['image']['name'];
            $tmpName = $_FILES['image']['tmp_name'];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions)) {
                $errors[] = "Format d'image non autorisé. Utilise jpg, jpeg, png, gif, webp ou avif.";
            } else {
                $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

                if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
                    $errors[] = "Impossible de créer le dossier des images (uploads).";
                } elseif (is_dir($uploadDir)) {
                    $uploadPath = $uploadDir . $newFileName;

                    if (move_uploaded_file($tmpName, $uploadPath)) {
                        $image = $newFileName;
                    } else {
                        $errors[] = "Erreur lors de l'upload de l'image.";
                    }
                }
            }
        } else {
            $errors[] = "Erreur lors du téléchargement de l'image.";
        }
    }

    if (empty($errors)) {
        $calories = $recetteController->calculerCaloriesRecette($produitsSelectionnes);

        $recette = new Recette($nom, $description, $calories, $image);
        $idRecette = $recetteController->addRecette($recette);

        if ($idRecette) {
            $recetteController->ajouterProduitsRecette($idRecette, $produitsSelectionnes);
            require_once __DIR__ . '/includes/bo_inline_crud.php';
            bo_catalog_save_redirect('List-Recette.php');
        } else {
            $errors[] = "Erreur lors de l'ajout de la recette.";
        }
    }

    $error = $errors;
}

if ($boCatalogInline) {
    $listBackUrl = 'List-Recette.php';
    if (isset($_GET['embed']) && (string) $_GET['embed'] !== '0') {
        $listBackUrl .= '?embed=1';
    }
    require __DIR__ . '/includes/partials/bo_recette_form_inline.php';
    return;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Ajouter une recette</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <?php
    require_once __DIR__ . '/includes/bo_catalog_chrome.php';
    bo_catalog_chrome_styles();
    ?>
    <style>
        .page-form-add-recette .image-preview {
            max-width: 140px;
            max-height: 140px;
            border-radius: 12px;
            margin-top: 10px;
            border: 1px solid var(--bo-border);
            object-fit: cover;
            display: none;
        }
    </style>
</head>
<body class="page-bo page-bo-catalog-form page-form-add-recette">
<?php
require_once __DIR__ . '/includes/bo_layout_start.php';
bo_layout_start('produit');
?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1180px; width: 100%;">
        <?php bo_catalog_chrome_topbar('recette'); ?>

        <div class="list-produit-head">
            <div>
                <h1 class="list-produit-title">Ajouter une recette</h1>
                <p class="list-produit-subtitle">Créez une recette et associez des produits</p>
            </div>
            <a href="List-Recette.php" class="btn-commande-outline">Retour à la liste</a>
        </div>

        <section class="bo-panel" aria-label="Formulaire recette">
            <?php if (!empty($error)) { ?>
                <div class="bo-flash-error">
                    <ul class="mb-0">
                        <?php foreach ($error as $err) { ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="bo-field">
                    <label for="nom">Nom de la recette</label>
                    <input
                        type="text"
                        id="nom"
                        name="nom"
                        value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>"
                    >
                </div>

                <div class="bo-field" style="margin-top: 18px;">
                    <label for="description">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                    ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="bo-field" style="margin-top: 18px;">
                    <label for="image">Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <p style="margin:8px 0 0;font-size:13px;color:#5a6560;">En cas d’erreur de validation, re-sélectionnez l’image.</p>
                    <img id="imagePreview" class="image-preview" alt="Aperçu image">
                </div>

                <div class="bo-field" style="margin-top: 18px;">
                    <label>Produits de la recette</label>
                    <?php if (empty($produits)) { ?>
                        <p style="color:#6b7c76;margin:0;">Aucun produit disponible.</p>
                    <?php } else { ?>
                        <div class="bo-check-grid">
                            <?php foreach ($produits as $produit) { ?>
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="produits[]"
                                        value="<?php echo $produit['id_produit']; ?>"
                                        id="produit_<?php echo $produit['id_produit']; ?>"
                                        <?php echo (isset($_POST['produits']) && in_array($produit['id_produit'], $_POST['produits'])) ? 'checked' : ''; ?>
                                    >
                                    <label class="form-check-label" for="produit_<?php echo $produit['id_produit']; ?>">
                                        <?php echo htmlspecialchars($produit['nom']); ?>
                                        <span style="color:#6b7c76;">(<?php echo htmlspecialchars((string) ($produit['calories'] ?? 0)); ?> cal)</span>
                                    </label>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>

                <div class="bo-form-actions">
                    <a href="List-Recette.php" class="btn-commande-outline">Retour</a>
                    <button type="submit" class="bo-btn-primary">Ajouter</button>
                </div>
            </form>
        </section>
    </div>
</main>

<?php bo_layout_end(); ?>
<script src="/Views/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('image').addEventListener('change', function(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('imagePreview');

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
});
</script>
</body>
</html>

