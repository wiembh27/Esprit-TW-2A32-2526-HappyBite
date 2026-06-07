<?php
include __DIR__ . '/../Controllers/FrigoController.php';

$frigoController = new FrigoController();

$idUtilisateur = (int)($_GET['id_utilisateur'] ?? 0);

$detailsFrigo = [];
$nomUtilisateur = '';

if ($idUtilisateur > 0) {
    $detailsFrigo = $frigoController->getDetailFrigoByUtilisateur($idUtilisateur);

    if (!empty($detailsFrigo)) {
        $nomUtilisateur = $detailsFrigo[0]['nom_utilisateur'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Détail du frigo</title>
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
        .page-detail-frigo .bo-frigo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
        .page-detail-frigo .bo-frigo-card { border: 1px solid var(--bo-border); border-radius: 12px; padding: 14px; background: #fafcfb; }
        .page-detail-frigo .bo-frigo-card img { width: 100%; max-height: 200px; object-fit: cover; border-radius: 12px; border: 1px solid var(--bo-border); }
    </style>
</head>
<body class="page-bo page-bo-catalog-form page-detail-frigo">
<?php
require_once __DIR__ . '/includes/bo_layout_start.php';
bo_layout_start('produit');
?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1180px; width: 100%;">
        <?php bo_catalog_chrome_topbar('frigo'); ?>

        <div class="list-produit-head">
            <div>
                <h1 class="list-produit-title">Détail du frigo</h1>
                <p class="list-produit-subtitle">
                    <?php if (!empty($nomUtilisateur)) { ?>
                        Utilisateur : <?php echo htmlspecialchars($nomUtilisateur); ?> (ID <?php echo (int) $idUtilisateur; ?>)
                    <?php } else { ?>
                        Aucun utilisateur trouvé
                    <?php } ?>
                </p>
            </div>
            <a href="List-Frigo-Back.php" class="btn-commande-outline">Retour à la liste</a>
        </div>

        <section class="bo-panel" aria-label="Contenu du frigo">
            <?php if (empty($detailsFrigo)) { ?>
                <p style="text-align:center;color:#6b7c76;margin:0;">Ce frigo est vide.</p>
            <?php } else { ?>
                <div class="bo-frigo-grid">
                    <?php foreach ($detailsFrigo as $produit) { ?>
                        <?php
                        $allergenes = array_filter(array_map('trim', explode(',', $produit['allergene'] ?? '')));
                        $benefices = array_filter(array_map('trim', explode(',', $produit['benefices'] ?? '')));
                        ?>
                        <div class="bo-frigo-card">
                            <?php if (!empty($produit['image'])) { ?>
                                <img
                                    src="/uploads/<?php echo htmlspecialchars($produit['image']); ?>"
                                    alt="<?php echo htmlspecialchars($produit['nom']); ?>"
                                >
                            <?php } else { ?>
                                <div style="height:160px;border-radius:12px;background:#f0f4f2;display:flex;align-items:center;justify-content:center;color:#6b7c76;font-size:14px;">Aucune image</div>
                            <?php } ?>
                            <strong style="display:block;margin:12px 0 8px;font-size:1.05rem;color:#1f3a28;"><?php echo htmlspecialchars($produit['nom']); ?></strong>
                            <p style="margin:0 0 6px;font-size:14px;"><strong>Catégorie</strong> : <?php echo htmlspecialchars($produit['nom_categorie'] ?? 'Non classé'); ?></p>
                            <p style="margin:0 0 6px;font-size:14px;"><strong>Prix</strong> : <?php echo htmlspecialchars((string) $produit['prix']); ?> DT</p>
                            <p style="margin:0 0 6px;font-size:14px;"><strong>Calories</strong> : <?php echo htmlspecialchars((string) ($produit['calories'] ?? 'Non défini')); ?> cal</p>
                            <p style="margin:0 0 6px;font-size:14px;"><strong>Quantité</strong> : <?php echo htmlspecialchars((string) $produit['quantite']); ?></p>
                            <p style="margin:0 0 10px;font-size:14px;"><strong>Date ajout</strong> : <?php echo htmlspecialchars((string) $produit['date_ajout']); ?></p>
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
                </div>
            <?php } ?>
        </section>
    </div>
</main>

<?php bo_layout_end(); ?>
<script src="/Views/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

