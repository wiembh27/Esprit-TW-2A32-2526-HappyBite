<?php
include __DIR__ . '/../Controllers/CategorieController.php';

$categorieController = new CategorieController();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID de la catégorie manquant.");
}

$id = intval($_GET['id']);
$categorie = $categorieController->showCategorie($id);

if (!$categorie) {
    die("Catégorie introuvable.");
}

$isProtected = (mb_strtolower(trim($categorie->getNom())) === 'non classé');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Détail catégorie</title>
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
        .page-bo-catalog-form .bo-detail-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 22px; }
        .page-bo-catalog-form .bo-table-btn { display: inline-block; padding: 8px 14px; border-radius: 8px; font-size: 13px; text-decoration: none; border: 1px solid transparent; }
        .page-bo-catalog-form .bo-table-btn--edit { background: #facc15; border-color: #eab308; color: #1f2937; }
        .page-bo-catalog-form .bo-table-btn--muted { background: #e5e7eb; border-color: #d1d5db; color: #6b7280; cursor: not-allowed; pointer-events: none; }
    </style>
</head>
<body class="page-bo page-bo-catalog-form page-detail-categorie">
<?php
require_once __DIR__ . '/includes/bo_layout_start.php';
bo_layout_start('produit');
?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1180px; width: 100%;">
        <?php bo_catalog_chrome_topbar('categorie'); ?>

        <div class="list-produit-head">
            <div>
                <h1 class="list-produit-title">Détail de la catégorie</h1>
                <p class="list-produit-subtitle"><?php echo htmlspecialchars($categorie->getNom()); ?></p>
            </div>
            <a href="List-Categorie.php" class="btn-commande-outline">Retour à la liste</a>
        </div>

        <section class="bo-panel" aria-label="Détails">
            <div class="bo-detail-grid">
                <div class="bo-detail-row"><strong>ID</strong> : <?php echo htmlspecialchars((string) $categorie->getIdCategorie()); ?></div>
                <div class="bo-detail-row"><strong>Nom</strong> : <?php echo htmlspecialchars($categorie->getNom()); ?></div>
                <div class="bo-detail-row">
                    <strong>Description</strong> :
                    <div style="margin-top:6px;color:#333;">
                        <?php
                        if (trim($categorie->getDescription()) !== '') {
                            echo nl2br(htmlspecialchars($categorie->getDescription()));
                        } else {
                            echo '<span style="color:#6b7c76;">Aucune description</span>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="bo-detail-actions">
                <?php if (!$isProtected) { ?>
                    <a href="Edit-Categorie.php?id=<?php echo (int) $categorie->getIdCategorie(); ?>" class="bo-img-link" title="Modifier" aria-label="Modifier"><img src="images/modify.png" width="22" height="22" alt=""></a>
                <?php } else { ?>
                    <span class="bo-img-link bo-img-link--disabled" title="Non modifiable" aria-hidden="true"><img src="images/modify.png" width="22" height="22" alt=""></span>
                <?php } ?>
            </div>
        </section>
    </div>
</main>

<?php bo_layout_end(); ?>
<script src="/Views/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

