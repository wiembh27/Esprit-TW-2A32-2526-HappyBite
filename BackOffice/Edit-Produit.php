<?php
$boCatalogInline = defined('BO_CATALOG_INLINE') && BO_CATALOG_INLINE;

include __DIR__ . '/../Controllers/ProduitController.php';
include __DIR__ . '/../Controllers/CategorieController.php';
require_once __DIR__ . '/../Models/Produit.php';
require_once __DIR__ . '/../Models/Categorie.php';

$error = "";

$produitController = new ProduitController();
$categorieController = new CategorieController();
$categories = $categorieController->listCategories();

// Listes fixes
$listeAllergenes = [
    'Gluten',
    'Lactose',
    'Sulfites',
    'Sucre élevé',
    'Sel élevé'
];

$listeBenefices = [
    'Vitamine A',
    'Vitamine B',
    'Vitamine C',
    'Vitamine D',
    'Fer',
    'Calcium',
    'Magnésium',
    'Fibres',
    'Protéines'
];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    if (!$boCatalogInline) {
        die('ID du produit manquant.');
    }
    return;
}

$id = (int) $_GET['id'];

if (!$boCatalogInline && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $q = isset($_GET['embed']) ? '?embed=1&' : '?';
    header('Location: List-Produit.php' . $q . 'action=edit&id=' . $id . '#bo-inline-crud');
    exit;
}

// Récupération du produit existant
$produitData = $produitController->getProduitById($id);

if (!$produitData) {
    die("Produit introuvable.");
}

// Préremplissage initial
$nom = $produitData['nom'] ?? '';
$prix = $produitData['prix'] ?? '';
$image = $produitData['image'] ?? '';
$calories = $produitData['calories'] ?? '';
$id_categorie = $produitData['id_categorie'] ?? '';
$id_utilisateur = $produitData['id_utilisateur'] ?? 1;
$date_ajout = $produitData['date_ajout'] ?? date('Y-m-d');

$allergenesSelectionnes = !empty($produitData['allergene'])
    ? array_map('trim', explode(',', $produitData['allergene']))
    : [];

$beneficesSelectionnes = !empty($produitData['benefices'])
    ? array_map('trim', explode(',', $produitData['benefices']))
    : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prix = trim($_POST['prix'] ?? '');
    $calories = trim($_POST['calories'] ?? '');
    $id_categorie = trim($_POST['id_categorie'] ?? '');

    $allergenesSelectionnes = $_POST['allergenes'] ?? [];
    $beneficesSelectionnes = $_POST['benefices_list'] ?? [];

    $allergene = implode(',', $allergenesSelectionnes);
    $benefices = implode(',', $beneficesSelectionnes);

    // on garde l'ancienne image par défaut
    $image = $produitData['image'] ?? '';
    $errors = [];

    // ===== NOM =====
    if ($nom === '') {
        $errors[] = "Le nom du produit est obligatoire.";
    }

    if (mb_strlen($nom) < 2) {
        $errors[] = "Le nom du produit doit contenir au moins 2 caractères.";
    }

    // caractères autorisés
    if ($nom !== '' && !preg_match("/^[\p{L}0-9\s%\-\'()]+$/u", $nom)) {
        $errors[] = "Le nom du produit contient des caractères non autorisés.";
    }

    // au moins 3 lettres
    preg_match_all('/\p{L}/u', $nom, $matches);
    if ($nom !== '' && (!isset($matches[0]) || count($matches[0]) < 3)) {
        $errors[] = "Le nom du produit doit contenir au moins 3 lettres.";
    }

    // pas uniquement des chiffres / symboles / espaces
    if ($nom !== '' && !preg_match('/\p{L}/u', $nom)) {
        $errors[] = "Le nom du produit ne peut pas être composé uniquement de chiffres ou de symboles.";
    }

    // ===== PRIX =====
    if ($prix === '') {
        $errors[] = "Le prix est obligatoire.";
    } elseif (!is_numeric($prix)) {
        $errors[] = "Le prix doit être un nombre valide.";
    } else {
        if ((float)$prix <= 0) {
            $errors[] = "Le prix doit être supérieur à 0.";
        }

        if ((float)$prix > 1000) {
            $errors[] = "Le prix est trop élevé, le maximum est 1000 DT.";
        }
    }

    // ===== CALORIES =====
    if ($calories !== '' && (!ctype_digit($calories) || (int)$calories < 0)) {
        $errors[] = "Les calories doivent être un entier positif ou zéro.";
    }

    // ===== CATÉGORIE =====
    if ($id_categorie === '') {
        $errors[] = "La catégorie est obligatoire.";
    }

    // ===== IMAGE OPTIONNELLE EN MODIFICATION =====
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== 4) {
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

    if (!empty($errors)) {
        $error = implode(" ",$errors);
    } else {
        $produit = new Produit(
            $nom,
            (float)$prix,
            $image,
            $allergene,
            $benefices,
            $calories !== '' ? (int)$calories : null,
            $date_ajout,
            $id_utilisateur,
            (int)$id_categorie
        );

        $produitController->updateProduit($produit, $id);

        require_once __DIR__ . '/includes/bo_inline_crud.php';
        bo_catalog_save_redirect('List-Produit.php');
    }
}

if ($boCatalogInline) {
    $listBackUrl = 'List-Produit.php';
    if (isset($_GET['embed']) && (string) $_GET['embed'] !== '0') {
        $listBackUrl .= '?embed=1';
    }
    require __DIR__ . '/includes/partials/bo_produit_edit_form_inline.php';
    return;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Modifier un produit</title>
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
<body class="page-bo page-bo-catalog-form page-form-edit-produit">
<?php
require_once __DIR__ . '/includes/bo_layout_start.php';
bo_layout_start('produit');
?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1180px; width: 100%;">
        <?php bo_catalog_chrome_topbar('produit'); ?>

        <div class="list-produit-head">
            <div>
                <h1 class="list-produit-title">Modifier un produit</h1>
                <p class="list-produit-subtitle">Mettez à jour la fiche produit</p>
            </div>
            <a href="List-Produit.php" class="btn-commande-outline">Retour à la liste</a>
        </div>

        <section class="bo-panel" aria-label="Formulaire produit">
            <?php if (!empty($error)) { ?>
                <div class="bo-flash-error"><?php echo htmlspecialchars($error); ?></div>
            <?php } ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="bo-form-row" style="grid-template-columns: 1fr 1fr;">
                    <div class="bo-field">
                        <label for="nom">Nom du produit</label>
                        <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($nom); ?>">
                    </div>
                    <div class="bo-field">
                        <label for="prix">Prix (DT)</label>
                        <input type="text" id="prix" name="prix" value="<?php echo htmlspecialchars($prix); ?>">
                    </div>
                </div>

                <div class="bo-field" style="margin-top: 18px;">
                    <label for="image">Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <?php if (!empty($image)) { ?>
                        <p style="margin:10px 0 6px;font-size:13px;color:#5a6560;">Image actuelle :</p>
                        <img
                            src="/uploads/<?php echo htmlspecialchars($image); ?>"
                            alt="Image du produit"
                            style="max-width: 120px; border-radius: 10px; border: 1px solid var(--bo-border);"
                        >
                    <?php } ?>
                </div>

                <div class="bo-form-row" style="grid-template-columns: 1fr 1fr; margin-top: 18px;">
                    <div class="bo-field">
                        <label for="calories">Calories</label>
                        <input type="text" id="calories" name="calories" value="<?php echo htmlspecialchars($calories); ?>">
                    </div>
                    <div class="bo-field">
                        <label for="id_categorie">Catégorie</label>
                        <select id="id_categorie" name="id_categorie">
                            <option value="">-- Choisir une catégorie --</option>
                            <?php foreach ($categories as $categorie) { ?>
                                <option
                                    value="<?php echo $categorie->getIdCategorie(); ?>"
                                    <?php echo ($id_categorie == $categorie->getIdCategorie()) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($categorie->getNom()); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="bo-field" style="margin-top: 18px;">
                    <label>Allergènes / composants sensibles</label>
                    <div class="bo-check-grid">
                        <?php foreach ($listeAllergenes as $item) { ?>
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="allergenes[]"
                                    value="<?php echo $item; ?>"
                                    id="allergene_<?php echo md5($item); ?>"
                                    <?php echo in_array($item, $allergenesSelectionnes) ? 'checked' : ''; ?>
                                >
                                <label class="form-check-label" for="allergene_<?php echo md5($item); ?>">
                                    <?php echo htmlspecialchars($item); ?>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="bo-field" style="margin-top: 18px;">
                    <label>Bénéfices</label>
                    <div class="bo-check-grid">
                        <?php foreach ($listeBenefices as $item) { ?>
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="benefices_list[]"
                                    value="<?php echo $item; ?>"
                                    id="benefice_<?php echo md5($item); ?>"
                                    <?php echo in_array($item, $beneficesSelectionnes) ? 'checked' : ''; ?>
                                >
                                <label class="form-check-label" for="benefice_<?php echo md5($item); ?>">
                                    <?php echo htmlspecialchars($item); ?>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="bo-form-actions">
                    <a href="List-Produit.php" class="btn-commande-outline">Retour</a>
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

