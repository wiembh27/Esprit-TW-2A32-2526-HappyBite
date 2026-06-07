<?php
require_once __DIR__ . '/includes/bo_require_admin.php';

include __DIR__ . '/../Controllers/RecetteController.php';
require_once __DIR__ . '/includes/bo_layout_start.php';

$recetteController = new RecetteController();

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    if ($id > 0) {
        $recetteController->deleteRecette($id);
    }

    header('Location: List-Recette.php');
    exit;
}

$motCle = trim($_GET['motCle'] ?? '');

/*
|--------------------------------------------------------------------------
| Export Excel
|--------------------------------------------------------------------------
| - Si recherche active : export des résultats affichés
| - Sinon : export de toute la liste
| - Toutes les colonnes utiles sauf l'image
*/
if (isset($_GET['export_excel']) && $_GET['export_excel'] == '1') {

    if (!empty($motCle)) {
        $recettesExport = $recetteController->rechercherRecettes($motCle);
        $titreExport = 'Liste des recettes filtrées';
    } else {
        $recettesExport = $recetteController->listRecettes();
        $titreExport = 'Liste complète des recettes';
    }

    $nomFichier = "HappyBite_Recettes_" . date("Y-m-d_H-i") . ".xls";

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
.title { background-color: #e8f5e9; font-size: 20px; font-weight: 700; color: #2e7d32; text-align: center; border: 2px solid #2e7d32; }
.subtitle { background-color: #f1f8e9; color: #666; text-align: center; font-size: 12px; font-weight: 500; }
.header td { background-color: #2e7d32; color: white; font-weight: 700; text-align: center; }
td { border: 1px solid #a5d6a7; padding: 7px; }
.center { text-align: center; }
.nom-col { width: 220px; font-weight: 600; }
.produits { background-color: #e8f5e9; color: #2e7d32; font-weight: 600; }
.cal-high { background-color: #f8d7da; color: #842029; font-weight: 600; text-align: center; }
.cal-medium { background-color: #fff3cd; color: #856404; text-align: center; }
.cal-low { background-color: #e8f5e9; color: #2e7d32; text-align: center; }
</style>
</head>

<body>
<table>
<tr class="brand">
    <td colspan="4" class="logo-cell">
        <img src="<?php echo htmlspecialchars($logoUrl); ?>" width="92" alt="HappyBite">
    </td>
</tr>

<tr><td colspan="4" class="title"><?php echo $titreExport; ?></td></tr>
<tr><td colspan="4" class="subtitle">Export généré le <?php echo date('d/m/Y à H:i'); ?></td></tr>

<tr class="header">
    <td>ID</td>
    <td>Nom</td>
    <td>Calories</td>
    <td>Produits</td>
</tr>

<?php foreach ($recettesExport as $recette) {
    $produitsRecette = $recetteController->getProduitsByRecette($recette['id_recette']);
    $nomsProduits = [];

    if (!empty($produitsRecette)) {
        foreach ($produitsRecette as $produit) {
            $nomsProduits[] = $produit['nom'];
        }
    }

    $cal = intval($recette['calories'] ?? 0);

    if ($cal > 500) {
        $calClass = 'cal-high';
    } elseif ($cal > 300) {
        $calClass = 'cal-medium';
    } else {
        $calClass = 'cal-low';
    }
?>
<tr>
    <td class="center"><?php echo htmlspecialchars((string) ($recette['id_recette'] ?? '')); ?></td>
    <td class="nom-col"><?php echo htmlspecialchars((string) ($recette['nom'] ?? '')); ?></td>
    <td class="<?php echo $calClass; ?>"><?php echo $cal; ?> cal</td>
    <td class="produits">
        <?php echo !empty($nomsProduits) ? htmlspecialchars(implode(', ', $nomsProduits)) : 'Aucun produit'; ?>
    </td>
</tr>
<?php } ?>
</table>
</body>
</html>

<?php
exit;
}
if (!empty($motCle)) {
    $recettes = $recetteController->rechercherRecettes($motCle);
} else {
    $recettes = $recetteController->listRecettes();
}

require_once __DIR__ . '/includes/bo_inline_crud.php';
$boPanelAction = isset($_GET['action']) ? (string) $_GET['action'] : '';
$boPanelId = (int) ($_GET['id'] ?? 0);
$boListUrl = bo_inline_crud_list_url('List-Recette.php');
$boSaved = isset($_GET['saved']) && $_GET['saved'] === '1';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Liste des recettes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-bo page-list-recette">
<?php bo_layout_start('produit'); ?>
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
            .bo-table-actions { display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; }
            .bo-table-btn {
                display: inline-block;
                padding: 6px 10px;
                border-radius: 8px;
                font-size: 12px;
                text-decoration: none;
                line-height: 1.2;
                border: 1px solid transparent;
            }
            .bo-table-btn--view {
                background: #fff;
                border-color: #2C7E34;
                color: #2C7E34;
            }
            .bo-table-btn--edit {
                background: #facc15;
                border-color: #eab308;
                color: #1f2937;
            }
            .bo-table-btn--delete { background: #ef4444; border-color: #dc2626; color: #fff; }
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
        <?php bo_inline_crud_styles(); ?>
        <?php
        require_once __DIR__ . '/includes/bo_catalog_chrome.php';
        bo_catalog_chrome_topbar('recette');
        ?>

        <div class="list-produit-head">
            <div>
                <h1 class="list-produit-title">Liste des recettes</h1>
                <p class="list-produit-subtitle">Gérez les recettes du catalogue</p>
            </div>
            <a href="<?php echo htmlspecialchars(bo_inline_crud_list_url('List-Recette.php', 'add'), ENT_QUOTES, 'UTF-8'); ?>" class="bo-btn-primary">Ajouter une recette</a>
        </div>

        <?php if ($boSaved) { ?>
            <div class="bo-flash-success" style="margin-bottom:16px;padding:12px 16px;border-radius:10px;background:#d1e7dd;color:#0f5132;">Enregistrement réussi.</div>
        <?php } ?>

        <?php if (in_array($boPanelAction, ['add', 'edit'], true)) {
            $panelTitles = ['add' => 'Ajouter une recette', 'edit' => 'Modifier une recette'];
            ?>
        <section class="bo-inline-crud" id="bo-inline-crud">
            <div class="bo-inline-crud__head">
                <h2><?php echo htmlspecialchars($panelTitles[$boPanelAction], ENT_QUOTES, 'UTF-8'); ?></h2>
                <a href="<?php echo htmlspecialchars($boListUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn-commande-outline">Fermer</a>
            </div>
            <div class="bo-inline-crud__body">
                <?php
                define('BO_CATALOG_INLINE', true);
                if ($boPanelAction === 'add') {
                    require __DIR__ . '/Add-Recette.php';
                } elseif ($boPanelId > 0) {
                    $_GET['id'] = (string) $boPanelId;
                    require __DIR__ . '/Edit-Recette.php';
                }
                ?>
            </div>
        </section>
        <?php } ?>

            <section class="bo-panel" aria-label="Recherche / filtres">
                <form method="GET" action="">
                    <div class="bo-form-row" style="grid-template-columns: 1fr auto auto;">
                        <div class="bo-field">
                            <label for="motCle">Rechercher une recette</label>
                            <input
                                type="text"
                                id="motCle"
                                name="motCle"
                                placeholder="Nom de la recette..."
                                value="<?php echo htmlspecialchars($motCle); ?>"
                            >
                        </div>
                        <div class="bo-field bo-field-submit">
                            <button type="submit" class="bo-btn-primary">Rechercher</button>
                        </div>
                        <div class="bo-field bo-field-submit">
                            <button type="submit" name="export_excel" value="1" class="btn-commande-outline">Exporter Excel</button>
                        </div>
                    </div>
                </form>
            </section>

            <section class="bo-table-wrap" aria-label="Tableau des recettes">
                <?php if (empty($recettes)) { ?>
                    <div class="bo-empty">Aucune recette trouvée.</div>
                <?php } else { ?>
                    <div class="bo-table-scroll" id="recettes-table-wrap">
                        <table class="bo-table">
                            <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Image</th>
                                        <th>Calories</th>
                                        <th>Produits</th>
                                        <th>Actions</th>
                                    </tr>
                            </thead>
                            <tbody>
                                    <?php foreach ($recettes as $recette) { ?>
                                        <?php $produitsRecette = $recetteController->getProduitsByRecette($recette['id_recette']); ?>
                                        <tr>
                                            <td class="bo-td-center">
                                                <?php echo htmlspecialchars((string) ($recette['id_recette'] ?? '')); ?>
                                            </td>

                                            <td class="bo-td-left">
                                                <strong><?php echo htmlspecialchars((string) ($recette['nom'] ?? '')); ?></strong>
                                            </td>

                                            <td class="bo-td-center">
                                                <?php if (!empty($recette['image'])) { ?>
                                                    <img
                                                        src="../uploads/<?php echo htmlspecialchars((string) ($recette['image'] ?? '')); ?>"
                                                        alt="Image recette"
                                                        style="width: 70px; height: 70px; object-fit: cover; border-radius: 10px;"
                                                    >
                                                <?php } else { ?>
                                                    <span class="text-muted">Aucune</span>
                                                <?php } ?>
                                            </td>

                                            <td class="bo-td-center">
                                                <?php echo htmlspecialchars((string) ($recette['calories'] ?? 0)); ?> cal
                                            </td>

                                            <td class="bo-td-left">
                                                <?php if (!empty($produitsRecette)) { ?>
                                                    <?php foreach ($produitsRecette as $produit) { ?>
                                                        <span class="bo-pill bo-pill--success">
                                                            <?php echo htmlspecialchars((string) ($produit['nom'] ?? '')); ?>
                                                        </span>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <span class="bo-pill bo-pill--muted">Aucun produit</span>
                                                <?php } ?>
                                            </td>

                                            <td class="bo-td-center">
                                                <span class="bo-table-actions">
                                                    <a href="Detail-Recette.php?id=<?php echo $recette['id_recette']; ?>" class="bo-img-link" title="Voir le détail" aria-label="Voir le détail"><img src="images/details.png" width="22" height="22" alt=""></a>
                                                    <a href="<?php echo htmlspecialchars(bo_inline_crud_list_url('List-Recette.php', 'edit', (int) $recette['id_recette']), ENT_QUOTES, 'UTF-8'); ?>" class="bo-img-link" title="Modifier" aria-label="Modifier"><img src="images/modify.png" width="22" height="22" alt=""></a>
                                                    <a
                                                        href="List-Recette.php?delete=<?php echo $recette['id_recette']; ?>"
                                                        class="bo-img-link"
                                                        title="Supprimer"
                                                        aria-label="Supprimer"
                                                        onclick="return confirm('Voulez-vous vraiment supprimer cette recette ?');"
                                                    ><img src="images/delete.png" width="22" height="22" alt=""></a>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="bo-table-pager">
                        <button type="button" class="bo-pager-arrow" id="recettes-pager-prev" aria-label="Précédent">‹</button>
                        <span class="bo-pager-info" id="recettes-pager-info">Page 1 / 1</span>
                        <button type="button" class="bo-pager-arrow" id="recettes-pager-next" aria-label="Suivant">›</button>
                    </div>
                <?php } ?>
            </section>

    </div>
</main>
<?php bo_layout_end(); ?>

<script>
    (function () {
        var wrap = document.getElementById('recettes-table-wrap');
        if (!wrap) return;
        var rows = Array.prototype.slice.call(wrap.querySelectorAll('tbody tr'));
        if (!rows.length) return;
        var prev = document.getElementById('recettes-pager-prev');
        var next = document.getElementById('recettes-pager-next');
        var info = document.getElementById('recettes-pager-info');
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

<script src="/Views/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

