<?php
include __DIR__ . '/../Controllers/RecetteController.php';

$recetteController = new RecetteController();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID de la recette manquant.");
}

$id = intval($_GET['id']);
$recette = $recetteController->showRecetteDetails($id);

if (!$recette) {
    die("Recette introuvable.");
}

$produitsRecette = $recetteController->getProduitsByRecette($id);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Détail recette</title>
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
        .page-detail-recette .bo-recette-img { max-width: 280px; max-height: 280px; object-fit: cover; border-radius: 16px; border: 1px solid var(--bo-border); display: block; margin: 12px auto 0; }
        .page-detail-recette .bo-produit-subcard { border: 1px solid var(--bo-border); border-radius: 12px; padding: 14px; background: #fafcfb; margin-bottom: 12px; }
        .page-detail-recette .bo-detail-actions { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 22px; }
        .page-detail-recette .bo-table-btn { display: inline-block; padding: 8px 14px; border-radius: 8px; font-size: 13px; text-decoration: none; border: 1px solid transparent; }
        .page-detail-recette .bo-table-btn--edit { background: #facc15; border-color: #eab308; color: #1f2937; }
    </style>
</head>
<body class="page-bo page-bo-catalog-form page-detail-recette">
<?php
require_once __DIR__ . '/includes/bo_layout_start.php';
bo_layout_start('produit');
?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1180px; width: 100%;">
        <?php bo_catalog_chrome_topbar('recette'); ?>

        <div class="list-produit-head">
            <div>
                <h1 class="list-produit-title">Détail de la recette</h1>
                <p class="list-produit-subtitle"><?php echo htmlspecialchars($recette['nom']); ?></p>
            </div>
            <a href="List-Recette.php" class="btn-commande-outline">Retour à la liste</a>
        </div>

        <section class="bo-panel" aria-label="Détails recette">
            <div class="bo-detail-grid">
                <?php if (!empty($recette['image'])) { ?>
                    <img
                        class="bo-recette-img"
                        src="/uploads/<?php echo htmlspecialchars($recette['image']); ?>"
                        alt="<?php echo htmlspecialchars($recette['nom']); ?>"
                    >
                <?php } else { ?>
                    <p style="color:#6b7c76;">Aucune image définie.</p>
                <?php } ?>

                <div class="bo-detail-row" style="margin-top:14px;">
                    <strong>Description</strong>
                    <div style="margin-top:6px;">
                        <?php
                        if (trim($recette['description'] ?? '') !== '') {
                            echo nl2br(htmlspecialchars($recette['description']));
                        } else {
                            echo '<span style="color:#6b7c76;">Aucune description</span>';
                        }
                        ?>
                    </div>
                </div>

                <div class="bo-detail-row" style="margin-top:12px;">
                    <strong>Calories totales</strong> :
                    <span class="bo-pill bo-pill--success" style="margin-left:8px;"><?php echo htmlspecialchars((string) ($recette['calories'] ?? 0)); ?> cal</span>
                </div>
            </div>

            <h2 style="margin:22px 0 12px;font-size:1.1rem;font-weight:700;color:#1f3a28;">Produits de la recette</h2>

            <?php if (!empty($produitsRecette)) { ?>
                <?php foreach ($produitsRecette as $produit) { ?>
                    <?php
                    $allergenes = array_filter(array_map('trim', explode(',', $produit['allergene'] ?? '')));
                    $benefices = array_filter(array_map('trim', explode(',', $produit['benefices'] ?? '')));
                    ?>
                    <div class="bo-produit-subcard">
                        <strong style="font-size:1.05rem;color:#1f3a28;"><?php echo htmlspecialchars($produit['nom']); ?></strong>
                        <p style="margin:8px 0 6px;font-size:14px;"><strong>Prix</strong> : <?php echo htmlspecialchars((string) $produit['prix']); ?> DT · <strong>Calories</strong> : <?php echo htmlspecialchars((string) ($produit['calories'] ?? 0)); ?> cal</p>
                        <div style="margin-bottom:8px;">
                            <strong style="font-size:13px;">Allergènes</strong> :
                            <?php if (!empty($allergenes)) { ?>
                                <?php foreach ($allergenes as $item) { ?>
                                    <span class="bo-pill bo-pill--danger"><?php echo htmlspecialchars($item); ?></span>
                                <?php } ?>
                            <?php } else { ?>
                                <span class="bo-pill bo-pill--muted">Aucun</span>
                            <?php } ?>
                        </div>
                        <div>
                            <strong style="font-size:13px;">Bénéfices</strong> :
                            <?php if (!empty($benefices)) { ?>
                                <?php foreach ($benefices as $item) { ?>
                                    <span class="bo-pill bo-pill--success"><?php echo htmlspecialchars($item); ?></span>
                                <?php } ?>
                            <?php } else { ?>
                                <span class="bo-pill bo-pill--muted">Non précisé</span>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p style="color:#6b7c76;">Aucun produit associé à cette recette.</p>
            <?php } ?>

            <div class="bo-detail-actions">
                <a href="List-Recette.php" class="btn-commande-outline">Retour</a>
                <a href="Edit-Recette.php?id=<?php echo (int) $recette['id_recette']; ?>" class="bo-img-link" title="Modifier" aria-label="Modifier"><img src="images/modify.png" width="22" height="22" alt=""></a>
            </div>
        </section>
    </div>
</main>

<?php bo_layout_end(); ?>
<script src="/Views/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

