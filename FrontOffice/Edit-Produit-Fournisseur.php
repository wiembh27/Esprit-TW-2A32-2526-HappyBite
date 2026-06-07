<?php

declare(strict_types=1);

$foCatalogInline = defined('FO_CATALOG_INLINE') && FO_CATALOG_INLINE;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

if (!$foCatalogInline && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $editId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($editId < 1) {
        header('Location: List-Produit-Fournisseur.php');
        exit;
    }
    require_once __DIR__ . '/includes/fo_inline_crud.php';
    header('Location: ' . fo_inline_crud_list_url('List-Produit-Fournisseur.php', 'edit', $editId, fo_inline_preserve_list_query()));
    exit;
}

include __DIR__ . '/../Controllers/ProduitController.php';
include __DIR__ . '/../Controllers/CategorieController.php';
require_once __DIR__ . '/../Models/Produit.php';
require_once __DIR__ . '/../Models/Categorie.php';

$error = '';

$produitController = new ProduitController();
$categorieController = new CategorieController();
$categories = $categorieController->listCategories();

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

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id < 1) {
    header('Location: List-Produit-Fournisseur.php');
    exit;
}
if ($id < 1) {
    header('Location: List-Produit-Fournisseur.php');
    exit;
}

$produitData = $produitController->getProduitByIdAndUtilisateur($id, $idFournisseur);

if (!$produitData) {
    header('Location: List-Produit-Fournisseur.php');
    exit;
}

// Préremplissage
$nom = $produitData['nom'] ?? '';
$prix = $produitData['prix'] ?? '';
$image = $produitData['image'] ?? '';
$calories = $produitData['calories'] ?? '';
$id_categorie = $produitData['id_categorie'] ?? '';
$id_utilisateur = $produitData['id_utilisateur'] ?? $idFournisseur;
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

    if ($nom !== '' && !preg_match("/^[\p{L}0-9\s%\-\'()]+$/u", $nom)) {
        $errors[] = "Le nom du produit contient des caractères non autorisés.";
    }

    preg_match_all('/\p{L}/u', $nom, $matches);
    if ($nom !== '' && (!isset($matches[0]) || count($matches[0]) < 3)) {
        $errors[] = "Le nom du produit doit contenir au moins 3 lettres.";
    }

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

        $produitController->updateProduitByUtilisateur($produit, $id, $idFournisseur);

        require_once __DIR__ . '/includes/fo_inline_crud.php';
        fo_catalog_save_redirect('List-Produit-Fournisseur.php', array_merge(fo_inline_preserve_list_query(), ['notice' => 'product_updated']));
    }
}

require_once __DIR__ . '/includes/fo_inline_crud.php';
$foListCloseUrl = fo_inline_crud_list_url('List-Produit-Fournisseur.php', '', 0, fo_inline_preserve_list_query());
?>
<div class="fo-catalog-inline-form">
<div class="container mt-3 mb-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h3 class="mb-0">Modifier mon produit</h3>
                </div>

                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="fo" value="edit">
                        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom du produit</label>
                            <input
                                type="text"
                                class="form-control"
                                id="nom"
                                name="nom"
                                value="<?php echo htmlspecialchars($nom); ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="prix" class="form-label">Prix</label>
                            <input
                                type="text"
                                class="form-control"
                                id="prix"
                                name="prix"
                                value="<?php echo htmlspecialchars($prix); ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Image</label>
                            <input
                                type="file"
                                class="form-control"
                                id="image"
                                name="image"
                                accept="image/*"
                            >

                            <small class="text-muted d-block mt-2">Aperçu :</small>

                            <?php if (!empty($image)) { ?>
                                <img
                                    id="imagePreview"
                                    src="/uploads/<?php echo htmlspecialchars($image); ?>"
                                    alt="Image du produit"
                                    class="image-preview"
                                >
                            <?php } else { ?>
                                <img
                                    id="imagePreview"
                                    src=""
                                    alt="Aperçu image"
                                    class="image-preview"
                                    style="display:none;"
                                >
                            <?php } ?>
                        </div>

                        <div class="mb-3">
                            <label for="calories" class="form-label">Calories</label>
                            <input
                                type="text"
                                class="form-control"
                                id="calories"
                                name="calories"
                                value="<?php echo htmlspecialchars($calories); ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="id_categorie" class="form-label">Catégorie</label>
                            <select class="form-select" id="id_categorie" name="id_categorie">
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

                        <div class="mb-3">
                            <label class="form-label">Allergènes / composants sensibles</label>
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

                        <div class="mb-3">
                            <label class="form-label">Bénéfices</label>
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

                        <div class="d-flex justify-content-between">
                            <a href="<?php echo htmlspecialchars($foListCloseUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Retour</a>
                            <button type="submit" class="btn btn-warning">Modifier</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

</div>
<style>
.fo-catalog-inline-form .image-preview {
    max-width: 140px;
    max-height: 140px;
    border-radius: 12px;
    margin-top: 10px;
    border: 1px solid #ddd;
    object-fit: cover;
    display: block;
}
</style>
<script>
(function () {
    var input = document.getElementById('image');
    var preview = document.getElementById('imagePreview');
    if (!input || !preview) return;
    input.addEventListener('change', function (event) {
        var file = event.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
})();
</script>
<?php
require_once __DIR__ . '/includes/hb_action_toast.php';
hb_action_toast_script(!empty($error) ? (string) $error : null, 5000);
?>

