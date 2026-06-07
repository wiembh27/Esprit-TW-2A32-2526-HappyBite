<?php

declare(strict_types=1);

$foCatalogInline = defined('FO_CATALOG_INLINE') && FO_CATALOG_INLINE;

if (!$foCatalogInline && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    require_once __DIR__ . '/includes/fo_inline_crud.php';
    header('Location: ' . fo_inline_crud_list_url('List-Produit-Fournisseur.php', 'add', 0, fo_inline_preserve_list_query()));
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/fo_i18n.php';
fo_init_i18n_for_request();

require_once __DIR__ . '/../Controllers/ProduitController.php';
require_once __DIR__ . '/../Controllers/CategorieController.php';
require_once __DIR__ . '/../Models/Produit.php';
require_once __DIR__ . '/../Models/Categorie.php';

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

$error = '';

$produitController = new ProduitController();
$categorieController = new CategorieController();
$categories = $categorieController->listCategories();

$listeAllergenes = [
    'Gluten',
    'Lactose',
    'Sulfites',
    'Sucre élevé',
    'Sel élevé',
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
    'Protéines',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prix = trim($_POST['prix'] ?? '');
    $calories = trim($_POST['calories'] ?? '');
    $id_categorie = trim($_POST['id_categorie'] ?? '');

    $allergenes = $_POST['allergenes'] ?? [];
    $beneficesList = $_POST['benefices_list'] ?? [];

    $allergene = implode(',', is_array($allergenes) ? $allergenes : []);
    $benefices = implode(',', is_array($beneficesList) ? $beneficesList : []);

    $image = '';
    $errors = [];

    if ($nom === '') {
        $errors[] = 'Le nom du produit est obligatoire.';
    }

    if ($nom !== '' && mb_strlen($nom) < 2) {
        $errors[] = 'Le nom du produit doit contenir au moins 2 caractères.';
    }

    if ($nom !== '' && !preg_match("/^[\p{L}0-9\s%\-\'()]+$/u", $nom)) {
        $errors[] = 'Le nom du produit contient des caractères non autorisés.';
    }

    preg_match_all('/\p{L}/u', $nom, $matches);
    if ($nom !== '' && (!isset($matches[0]) || count($matches[0]) < 3)) {
        $errors[] = 'Le nom du produit doit contenir au moins 3 lettres.';
    }

    if ($nom !== '' && !preg_match('/\p{L}/u', $nom)) {
        $errors[] = 'Le nom du produit ne peut pas être composé uniquement de chiffres ou de symboles.';
    }

    if ($prix === '') {
        $errors[] = 'Le prix est obligatoire.';
    } elseif (!is_numeric($prix)) {
        $errors[] = 'Le prix doit être un nombre valide.';
    } else {
        if ((float) $prix <= 0) {
            $errors[] = 'Le prix doit être supérieur à 0.';
        }
        if ((float) $prix > 1000) {
            $errors[] = 'Le prix est trop élevé, le maximum est 1000 DT.';
        }
    }

    if ($calories !== '' && (!ctype_digit($calories) || (int) $calories < 0)) {
        $errors[] = 'Les calories doivent être un entier positif ou zéro.';
    }

    if ($id_categorie === '') {
        $errors[] = 'La catégorie est obligatoire.';
    }

    if (!isset($_FILES['image']) || (int) ($_FILES['image']['error'] ?? 0) === UPLOAD_ERR_NO_FILE) {
        $errors[] = "L'image du produit est obligatoire.";
    } else {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        $originalName = $_FILES['image']['name'] ?? '';
        $tmpName = $_FILES['image']['tmp_name'] ?? '';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            $errors[] = "Format d'image non autorisé. Utilisez jpg, jpeg, png, gif, webp ou avif.";
        } else {
            $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

            if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
                $errors[] = 'Impossible de créer le dossier des images (uploads).';
            } elseif (is_dir($uploadDir)) {
                $uploadPath = $uploadDir . $newFileName;
                if (move_uploaded_file($tmpName, $uploadPath)) {
                    $image = $newFileName;
                } else {
                    $errors[] = "Erreur lors de l'upload de l'image.";
                }
            }
        }
    }

    $date_ajout = date('Y-m-d');

    if ($errors !== []) {
        $error = implode(' ', $errors);
    } else {
        $produit = new Produit(
            $nom,
            (float) $prix,
            $image,
            $allergene,
            $benefices,
            $calories !== '' ? (int) $calories : null,
            $date_ajout,
            $idFournisseur,
            (int) $id_categorie
        );

        $produitController->addProduit($produit);

        require_once __DIR__ . '/includes/fo_inline_crud.php';
        fo_catalog_save_redirect('List-Produit-Fournisseur.php', array_merge(fo_inline_preserve_list_query(), ['notice' => 'product_added']));
    }
}

require_once __DIR__ . '/includes/fo_inline_crud.php';
$foListCloseUrl = fo_inline_crud_list_url('List-Produit-Fournisseur.php', '', 0, fo_inline_preserve_list_query());
?>
<div class="fo-catalog-inline-form">
<main class="commande-wrap">
<div class="container py-5">
    <div class="text-center mb-4">
        <h2 class="fw-bold"><?php echo fo_e('supplier.add_heading'); ?></h2>
        <p class="text-muted"><?php echo fo_e('supplier.add_sub'); ?></p>
    </div>

    <div class="d-flex justify-content-center gap-3 mb-4 flex-wrap">
        <a href="List-Produit-Fournisseur.php" class="btn btn-outline-secondary rounded-pill px-4"><?php echo fo_e('supplier.my_products'); ?></a>
        <a href="List-Produit.php" class="btn btn-outline-success rounded-pill px-4"><?php echo fo_e('supplier.public_catalog'); ?></a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-success rounded-top-4 py-3">
                    <h3 class="mb-0 fw-bold text-white" style="color: #fff !important;"><?php echo fo_e('supplier.form_title'); ?></h3>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="Add-Produit-Fournisseur.php" enctype="multipart/form-data">
                        <input type="hidden" name="fo" value="add">
                        <div class="mb-3">
                            <label for="nom" class="form-label"><?php echo fo_e('supplier.field_name'); ?></label>
                            <input type="text" class="form-control" id="nom" name="nom" required
                                   value="<?php echo isset($_POST['nom']) ? htmlspecialchars((string) $_POST['nom']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="prix" class="form-label"><?php echo fo_e('supplier.field_price'); ?></label>
                            <input type="text" class="form-control" id="prix" name="prix" required
                                   value="<?php echo isset($_POST['prix']) ? htmlspecialchars((string) $_POST['prix']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label"><?php echo fo_e('supplier.field_image'); ?></label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                            <img id="imagePreview" class="image-preview" alt="<?php echo fo_e('auth.photo_preview'); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="calories" class="form-label"><?php echo fo_e('supplier.field_calories'); ?></label>
                            <input type="text" class="form-control" id="calories" name="calories"
                                   value="<?php echo isset($_POST['calories']) ? htmlspecialchars((string) $_POST['calories']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="id_categorie" class="form-label"><?php echo fo_e('supplier.field_category'); ?></label>
                            <select class="form-select" id="id_categorie" name="id_categorie" required>
                                <option value=""><?php echo fo_e('supplier.choose_category'); ?></option>
                                <?php foreach ($categories as $categorie) { ?>
                                    <option value="<?php echo (int) $categorie->getIdCategorie(); ?>"
                                        <?php echo (isset($_POST['id_categorie']) && (string) $_POST['id_categorie'] === (string) $categorie->getIdCategorie()) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categorie->getNom()); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo fo_e('supplier.field_allergens'); ?></label>
                            <?php foreach ($listeAllergenes as $item) { ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allergenes[]" value="<?php echo htmlspecialchars($item); ?>"
                                           id="allergene_<?php echo md5($item); ?>"
                                        <?php echo (isset($_POST['allergenes']) && is_array($_POST['allergenes']) && in_array($item, $_POST['allergenes'], true)) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="allergene_<?php echo md5($item); ?>"><?php echo htmlspecialchars($item); ?></label>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><?php echo fo_e('supplier.field_benefits'); ?></label>
                            <?php foreach ($listeBenefices as $item) { ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="benefices_list[]" value="<?php echo htmlspecialchars($item); ?>"
                                           id="benefice_<?php echo md5($item); ?>"
                                        <?php echo (isset($_POST['benefices_list']) && is_array($_POST['benefices_list']) && in_array($item, $_POST['benefices_list'], true)) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="benefice_<?php echo md5($item); ?>"><?php echo htmlspecialchars($item); ?></label>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2 justify-content-between">
                            <a href="<?php echo htmlspecialchars($foListCloseUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary rounded-pill px-4"><?php echo fo_e('common.cancel'); ?></a>
                            <button type="submit" class="btn btn-success rounded-pill px-4"><?php echo fo_e('supplier.save_product'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</main>
</div>
<style>
.fo-catalog-inline-form .image-preview {
    max-width: 160px;
    max-height: 160px;
    border-radius: 12px;
    margin-top: 10px;
    border: 1px solid #dee2e6;
    object-fit: cover;
    display: none;
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
        } else {
            preview.src = '';
            preview.style.display = 'none';
        }
    });
})();
</script>
<?php
require_once __DIR__ . '/includes/hb_action_toast.php';
hb_action_toast_script($error !== '' ? $error : null, 5000);
?>
