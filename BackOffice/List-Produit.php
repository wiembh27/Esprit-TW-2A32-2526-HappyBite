<?php

require_once __DIR__ . '/includes/bo_require_admin.php';

include __DIR__ . '/../Controllers/ProduitController.php';
include __DIR__ . '/../Controllers/CategorieController.php';

$produitController = new ProduitController();
$categorieController = new CategorieController();

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    if ($id > 0) {
        $produitController->deleteProduit($id);
    }

    header('Location: List-Produit.php');
    exit;
}

$motCle = trim($_GET['motCle'] ?? '');
$idCategorie = trim($_GET['id_categorie'] ?? '');

$categories = $categorieController->listCategories();

if (isset($_GET['export_excel']) && $_GET['export_excel'] == '1') {

    if (!empty($motCle) || !empty($idCategorie)) {
        $produitsExport = $produitController->rechercherProduits($motCle, $idCategorie);
        $titreExport = 'Liste des produits filtrés';
    } else {
        $produitsExport = $produitController->listProduits();
        $titreExport = 'Liste complète des produits';
    }

    $nomFichier = "HappyBite_Produits_" . date("Y-m-d_H-i") . ".xls";

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$baseUrl = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$logoUrl = $baseUrl . '/images/logo.png';
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$nomFichier\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "\xEF\xBB\xBF";
?>

<html>
<head>
<meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

<style>
table { border-collapse: collapse; width: 100%; font-family: "Poppins", sans-serif; font-size: 12px; font-weight: 400; }
.brand { background-color: #ffffff; border-bottom: 3px solid #2e7d32; height: 80px; }
.logo-cell { text-align: center; padding: 8px 0; }
.title { background-color: #e8f5e9; font-size: 20px; font-weight: 700; color: #2e7d32; text-align: center; height: 40px; border: 2px solid #2e7d32; }
.subtitle { background-color: #f1f8e9; color: #666; text-align: center; font-size: 12px; height: 28px; font-weight: 500; }
.header td { background-color: #2e7d32; color: white; font-weight: 700; text-align: center; border: 1px solid #1b5e20; }
td { border: 1px solid #a5d6a7; padding: 7px; vertical-align: middle; }
.center { text-align: center; }
.promo-oui { background-color: #fff3cd; color: #856404; font-weight: 600; text-align: center; }
.promo-non { background-color: #eeeeee; color: #555; text-align: center; }
.prix-promo { background-color: #ffe082; color: #5d4037; font-weight: 600; text-align: center; }
.allergene { background-color: #f8d7da; color: #842029; font-weight: 600; }
</style>
<link rel="stylesheet" href="css/style.css">
</head>

<body>
<table>
<tr class="brand">
    <td colspan="11" class="logo-cell">
        <img src="<?php echo htmlspecialchars($logoUrl); ?>" width="92" alt="HappyBite">
    </td>
</tr>

<tr><td colspan="11" class="title"><?php echo $titreExport; ?></td></tr>
<tr><td colspan="11" class="subtitle">Export généré le <?php echo date('d/m/Y à H:i'); ?></td></tr>

<tr class="header">
    <td>ID</td>
    <td>Nom</td>
    <td>Fournisseur</td>
    <td>Prix normal</td>
    <td>Promo</td>
    <td>Prix promo</td>
    <td>Calories</td>
    <td>Catégorie</td>
    <td>Allergènes</td>
    <td>Bénéfices</td>
    <td>Date ajout</td>
</tr>

<?php foreach ($produitsExport as $produit) { ?>
<?php
$isPromo = isset($produit['promo']) && $produit['promo'] !== null && $produit['promo'] !== '';
$hasAllergene = !empty($produit['allergene']) && strtolower(trim($produit['allergene'])) !== 'aucun';
?>
<tr>
    <td class="center"><?php echo htmlspecialchars((string) ($produit['id_produit'] ?? '')); ?></td>
    <td><?php echo htmlspecialchars((string) ($produit['nom'] ?? '')); ?></td>
    <td><?php echo htmlspecialchars((string) ($produit['nom_fournisseur'] ?? 'Non renseigné')); ?></td>
    <td class="center"><?php echo htmlspecialchars((string) ($produit['prix'] ?? '')); ?> DT</td>
    <td class="<?php echo $isPromo ? 'promo-oui' : 'promo-non'; ?>"><?php echo $isPromo ? 'Oui' : 'Non'; ?></td>
    <td class="<?php echo $isPromo ? 'prix-promo' : 'center'; ?>">
        <?php echo $isPromo ? htmlspecialchars((string) $produit['promo']) . ' DT' : '-'; ?>
    </td>
    <td class="center"><?php echo htmlspecialchars((string) ($produit['calories'] ?? 'Non défini')); ?></td>
    <td class="center"><?php echo htmlspecialchars((string) ($produit['nom_categorie'] ?? '')); ?></td>
    <td class="<?php echo $hasAllergene ? 'allergene' : 'center'; ?>">
        <?php echo htmlspecialchars((string) ($produit['allergene'] ?? 'Aucun')); ?>
    </td>
    <td><?php echo htmlspecialchars((string) ($produit['benefices'] ?? 'Non précisé')); ?></td>
    <td class="center"><?php echo htmlspecialchars((string) ($produit['date_ajout'] ?? '')); ?></td>
</tr>
<?php } ?>
</table>
</body>
</html>

<?php
exit;
}

if (!empty($motCle) || !empty($idCategorie)) {
    $produits = $produitController->rechercherProduits($motCle, $idCategorie);
} else {
    $produits = $produitController->listProduits();
}

require_once __DIR__ . '/includes/bo_inline_crud.php';
$boPanelAction = isset($_GET['action']) ? (string) $_GET['action'] : '';
$boPanelId = (int) ($_GET['id'] ?? 0);
$boListUrl = bo_inline_crud_list_url('List-Produit.php');
$boSaved = isset($_GET['saved']) && $_GET['saved'] === '1';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Liste des produits</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-bo page-list-produit">
<?php
require_once __DIR__ . '/includes/bo_layout_start.php';
bo_layout_start('produit');
?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1180px; width: 100%;">
        <style>
            .list-produit-head {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 14px;
                flex-wrap: wrap;
                margin-bottom: 18px;
            }
            .list-produit-title {
                margin: 0;
                font-size: 2rem;
                font-weight: 700;
                color: #1f3a28;
            }
            .list-produit-subtitle {
                margin: 8px 0 0;
                font-size: 1rem;
                color: #2f3d36;
            }
            .page-list-produit .bo-form-row {
                grid-template-columns: 1fr 1fr auto auto;
            }
            .bo-thumb {
                width: 64px;
                height: 64px;
                border-radius: 10px;
                object-fit: cover;
                display: block;
                margin: 0 auto;
            }
            .page-list-produit td.bo-td-center .bo-table-actions {
                display: inline-flex;
                flex-direction: row;
                flex-wrap: nowrap;
                align-items: center;
                justify-content: center;
                gap: 10px;
                white-space: nowrap;
            }
            .page-list-produit .bo-table-actions .bo-img-link {
                margin: 0 !important;
            }
            .bo-table-btn {
                display: inline-block;
                padding: 6px 10px;
                border-radius: 8px;
                font-size: 12px;
                text-decoration: none;
                line-height: 1.2;
                border: 1px solid transparent;
            }
            .bo-table-btn--edit {
                background: #facc15;
                border-color: #eab308;
                color: #1f2937;
            }
            .bo-table-btn--delete {
                background: #ef4444;
                border-color: #dc2626;
                color: #fff;
            }
            .bo-table-btn:hover { filter: brightness(0.96); }
            .bo-table-pager {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 10px;
                flex-wrap: wrap;
            }
            .bo-pager-arrow {
                width: 36px;
                height: 36px;
                border-radius: 999px;
                border: 2px solid #2c7e34;
                background: #e8f5e9;
                color: #1f6b31;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
            }
            .bo-pager-arrow[disabled] {
                opacity: 0.45;
                cursor: not-allowed;
            }
            .bo-pager-info {
                color: #1f3a28;
                font-size: 13px;
            }
        </style>

        <?php
        require_once __DIR__ . '/includes/bo_catalog_chrome.php';
        bo_catalog_chrome_topbar('produit');
        ?>

        <div class="list-produit-head">
            <div>
                <h1 class="list-produit-title">Liste des produits</h1>
                <p class="list-produit-subtitle">Gérez les produits du catalogue</p>
            </div>
            <a href="Add-Produit.php" class="bo-btn-primary">Ajouter un produit</a>
        </div>

        <section class="bo-panel" aria-label="Recherche / filtres">
            <form method="GET" action="">
                <div class="bo-form-row">
                    <div class="bo-field">
                        <label for="motCle">Rechercher un produit</label>
                        <input
                            type="text"
                            id="motCle"
                            name="motCle"
                            placeholder="Nom du produit, fournisseur ou promo..."
                            value="<?php echo htmlspecialchars($motCle); ?>"
                        >
                    </div>

                    <div class="bo-field">
                        <label for="id_categorie">Filtrer par catégorie</label>
                        <select id="id_categorie" name="id_categorie">
                            <option value="">-- Toutes les catégories --</option>
                            <?php foreach ($categories as $categorie) { ?>
                                <option
                                    value="<?php echo $categorie->getIdCategorie(); ?>"
                                    <?php echo ($idCategorie == $categorie->getIdCategorie()) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($categorie->getNom()); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="bo-field bo-field-submit">
                        <button type="submit" class="bo-btn-primary">Filtrer</button>
                    </div>

                    <div class="bo-field bo-field-submit">
                        <button type="submit" name="export_excel" value="1" class="btn-commande-outline">Exporter Excel</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="bo-table-wrap" aria-label="Tableau des produits">
            <?php if (empty($produits)) { ?>
                <div class="bo-empty">Aucun produit trouvé.</div>
            <?php } else { ?>
                <div class="bo-table-scroll" id="produits-table-wrap">
                    <table class="bo-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Fournisseur</th>
                                <th>Prix normal</th>
                                <th>Promo</th>
                                <th>Prix promo</th>
                                <th>Image</th>
                                <th>Calories</th>
                                <th>Catégorie</th>
                                <th>Allergènes</th>
                                <th>Bénéfices</th>
                                <th>Date ajout</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produits as $produit) { ?>
                                <?php
                                $allergenes = array_filter(array_map('trim', explode(',', $produit['allergene'] ?? '')));
                                $benefices = array_filter(array_map('trim', explode(',', $produit['benefices'] ?? '')));
                                $isPromo = isset($produit['promo']) && $produit['promo'] !== null && $produit['promo'] !== '';
                                ?>
                                <tr>
                                    <td class="bo-td-center"><?php echo htmlspecialchars((string) ($produit['id_produit'] ?? '')); ?></td>
                                    <td class="bo-td-left"><strong><?php echo htmlspecialchars((string) ($produit['nom'] ?? '')); ?></strong></td>
                                    <td class="bo-td-center"><?php echo htmlspecialchars((string) ($produit['nom_fournisseur'] ?? 'Non renseigné')); ?></td>
                                    <td class="bo-td-center"><?php echo htmlspecialchars((string) ($produit['prix'] ?? '')); ?> DT</td>
                                    <td class="bo-td-center">
                                        <?php if ($isPromo) { ?>
                                            <span class="bo-pill bo-pill--success">Oui</span>
                                        <?php } else { ?>
                                            <span class="bo-pill bo-pill--muted">Non</span>
                                        <?php } ?>
                                    </td>
                                    <td class="bo-td-center"><?php echo $isPromo ? htmlspecialchars((string) $produit['promo']) . ' DT' : '-'; ?></td>
                                    <td class="bo-td-center">
                                        <?php if (!empty($produit['image'])) { ?>
                                            <img src="../uploads/<?php echo htmlspecialchars((string) ($produit['image'] ?? '')); ?>" alt="Image produit" class="bo-thumb">
                                        <?php } else { ?>
                                            <span class="bo-pill bo-pill--muted">Aucune</span>
                                        <?php } ?>
                                    </td>
                                    <td class="bo-td-center"><?php echo htmlspecialchars((string) ($produit['calories'] ?? 'Non défini')); ?></td>
                                    <td class="bo-td-center">
                                        <span class="bo-pill bo-pill--muted"><?php echo htmlspecialchars((string) ($produit['nom_categorie'] ?? '')); ?></span>
                                    </td>
                                    <td class="bo-td-left">
                                        <?php if (!empty($allergenes)) { ?>
                                            <?php foreach ($allergenes as $item) { ?>
                                                <span class="bo-pill bo-pill--danger"><?php echo htmlspecialchars((string) $item); ?></span>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <span class="bo-pill bo-pill--muted">Aucun</span>
                                        <?php } ?>
                                    </td>
                                    <td class="bo-td-left">
                                        <?php if (!empty($benefices)) { ?>
                                            <?php foreach ($benefices as $item) { ?>
                                                <span class="bo-pill bo-pill--success"><?php echo htmlspecialchars((string) $item); ?></span>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <span class="bo-pill bo-pill--muted">Non précisé</span>
                                        <?php } ?>
                                    </td>
                                    <td class="bo-td-center"><?php echo htmlspecialchars((string) ($produit['date_ajout'] ?? '')); ?></td>
                                    <td class="bo-td-center">
                                        <span class="bo-table-actions">
                                            <a href="<?php echo htmlspecialchars(bo_inline_crud_list_url('List-Produit.php', 'edit', (int) $produit['id_produit']), ENT_QUOTES, 'UTF-8'); ?>" class="bo-img-link" title="Modifier" aria-label="Modifier"><img src="images/modify.png" width="22" height="22" alt=""></a>
                                            <a
                                                href="List-Produit.php?delete=<?php echo $produit['id_produit']; ?>"
                                                class="bo-img-link"
                                                title="Supprimer"
                                                aria-label="Supprimer"
                                                onclick="return confirm('Voulez-vous vraiment supprimer ce produit ?');"
                                            ><img src="images/delete.png" width="22" height="22" alt=""></a>
                                        </span>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <div class="bo-table-pager">
                    <button type="button" class="bo-pager-arrow" id="produits-pager-prev" aria-label="Précédent">‹</button>
                    <span class="bo-pager-info" id="produits-pager-info">Page 1 / 1</span>
                    <button type="button" class="bo-pager-arrow" id="produits-pager-next" aria-label="Suivant">›</button>
                </div>
            <?php } ?>
        </section>
    </div>
</main>

<script>
    (function () {
        var wrap = document.getElementById('produits-table-wrap');
        if (!wrap) return;
        var rows = Array.prototype.slice.call(wrap.querySelectorAll('tbody tr'));
        if (!rows.length) return;
        var prev = document.getElementById('produits-pager-prev');
        var next = document.getElementById('produits-pager-next');
        var info = document.getElementById('produits-pager-info');
        if (!prev || !next || !info) return;

        var perPage = 8;
        var page = 1;
        var totalPages = Math.max(1, Math.ceil(rows.length / perPage));

        function render() {
            totalPages = Math.max(1, Math.ceil(rows.length / perPage));
            if (page > totalPages) page = totalPages;
            var start = (page - 1) * perPage;
            var end = start + perPage;
            rows.forEach(function (row, idx) {
                row.hidden = !(idx >= start && idx < end);
            });
            info.textContent = 'Page ' + page + ' / ' + totalPages;
            prev.disabled = page <= 1;
            next.disabled = page >= totalPages;
        }

        prev.addEventListener('click', function () {
            if (page > 1) {
                page--;
                render();
            }
        });
        next.addEventListener('click', function () {
            if (page < totalPages) {
                page++;
                render();
            }
        });

        render();
    })();
</script>

<?php bo_layout_end(); ?>
</body>
</html>

