<?php
require_once __DIR__ . '/includes/bo_require_admin.php';

include __DIR__ . '/../Controllers/FrigoController.php';
require_once __DIR__ . '/includes/bo_layout_start.php';

$frigoController = new FrigoController();

$recherche = trim($_GET['recherche'] ?? '');
$motCle = $recherche;
$idUtilisateur = ctype_digit($recherche) ? $recherche : '';

/*
|--------------------------------------------------------------------------
| Export Excel
|--------------------------------------------------------------------------
| - Si recherche active : export des résultats affichés
| - Sinon : export de toute la liste
*/
if (isset($_GET['export_excel']) && $_GET['export_excel'] == '1') {
    $frigosExport = $frigoController->getResumeFrigos($motCle, $idUtilisateur);

    $titreExport = !empty($recherche)
        ? 'Liste des frigos filtrés'
        : 'Liste complète des frigos';

    $nomFichier = "HappyBite_Frigos_" . date("Y-m-d_H-i") . ".xls";

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
.header td { background-color: #2e7d32; color: white; font-weight: 700; text-align: center; border: 1px solid #1b5e20; }
td { border: 1px solid #a5d6a7; padding: 7px; vertical-align: middle; }
.center { text-align: center; }
.id-col { width: 120px; white-space: nowrap; }
.user-col { width: 180px; white-space: nowrap; font-weight: 600; }
.quantite { background-color: #ffe082; color: #5d4037; font-weight: 600; text-align: center; }
.produits { background-color: #e8f5e9; color: #2e7d32; font-weight: 600; }
</style>
</head>

<body>
<table>
<tr class="brand">
    <td colspan="6" class="logo-cell">
        <img src="<?php echo htmlspecialchars($logoUrl); ?>" width="92" alt="HappyBite">
    </td>
</tr>

<tr><td colspan="6" class="title"><?php echo $titreExport; ?></td></tr>
<tr><td colspan="6" class="subtitle">Export généré le <?php echo date('d/m/Y à H:i'); ?></td></tr>

<tr class="header">
    <td class="id-col">ID Utilisateur</td>
    <td class="user-col">Utilisateur</td>
    <td>Nombre de produits</td>
    <td>Quantité totale</td>
    <td>Contenu du frigo</td>
    <td>Dernier ajout</td>
</tr>

<?php foreach ($frigosExport as $frigo) { ?>
<tr>
    <td class="id-col center"><?php echo htmlspecialchars((string) ($frigo['id_utilisateur'] ?? '')); ?></td>
    <td class="user-col center"><?php echo htmlspecialchars((string) ($frigo['nom_utilisateur'] ?? '')); ?></td>
    <td class="center"><?php echo htmlspecialchars((string) ($frigo['nombre_produits'] ?? 0)); ?></td>
    <td class="quantite"><?php echo htmlspecialchars((string) ($frigo['quantite_totale'] ?? 0)); ?></td>
    <td class="produits"><?php echo htmlspecialchars((string) ($frigo['liste_produits'] ?? 'Aucun produit')); ?></td>
    <td class="center"><?php echo htmlspecialchars((string) ($frigo['derniere_date_ajout'] ?? '')); ?></td>
</tr>
<?php } ?>
</table>
</body>
</html>

<?php
exit;
}

$frigos = $frigoController->getResumeFrigos($motCle, $idUtilisateur);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Liste des frigos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-bo page-list-frigo-back">
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
            .bo-table-btn {
                display: inline-block;
                padding: 6px 10px;
                border-radius: 8px;
                font-size: 12px;
                text-decoration: none;
                line-height: 1.2;
                border: 1px solid transparent;
                background: #0ea5e9;
                border-color: #0284c7;
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
        bo_catalog_chrome_topbar('frigo');
        ?>

            <div class="list-produit-head">
                <div>
                    <h1 class="list-produit-title">Liste des frigos</h1>
                    <p class="list-produit-subtitle">Consultation des frigos par utilisateur</p>
                </div>
            </div>

            <section class="bo-panel" aria-label="Recherche / filtres">
                <form method="GET" action="">
                    <div class="bo-form-row" style="grid-template-columns: 1fr auto auto;">
                        <div class="bo-field">
                            <label for="recherche">Recherche</label>
                            <input
                                type="text"
                                id="recherche"
                                name="recherche"
                                placeholder="Produit, utilisateur ou ID utilisateur..."
                                value="<?php echo htmlspecialchars($recherche); ?>"
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

            <section class="bo-table-wrap" aria-label="Tableau des frigos">
                <?php if (empty($frigos)) { ?>
                    <div class="bo-empty">Aucun frigo trouvé.</div>
                <?php } else { ?>
                    <div class="bo-table-scroll" id="frigos-table-wrap">
                        <table class="bo-table">
                            <thead>
                                    <tr>
                                        <th>ID Utilisateur</th>
                                        <th>Utilisateur</th>
                                        <th>Nombre de produits</th>
                                        <th>Quantité totale</th>
                                        <th>Contenu du frigo</th>
                                        <th>Dernier ajout</th>
                                        <th>Action</th>
                                    </tr>
                            </thead>
                            <tbody>
                                    <?php foreach ($frigos as $frigo) { ?>
                                        <tr>
                                            <td class="bo-td-center">
                                                <?php echo htmlspecialchars((string) ($frigo['id_utilisateur'] ?? '')); ?>
                                            </td>

                                            <td class="bo-td-center">
                                                <strong><?php echo htmlspecialchars((string) ($frigo['nom_utilisateur'] ?? '')); ?></strong>
                                            </td>

                                            <td class="bo-td-center">
                                                <?php echo htmlspecialchars((string) ($frigo['nombre_produits'] ?? 0)); ?>
                                            </td>

                                            <td class="bo-td-center">
                                                <?php echo htmlspecialchars((string) ($frigo['quantite_totale'] ?? 0)); ?>
                                            </td>

                                            <td class="bo-td-left">
                                                <?php
                                                $produits = explode(' | ', $frigo['liste_produits'] ?? '');
                                                foreach ($produits as $produit) {
                                                    if (trim($produit) !== '') {
                                                        echo '<span class="bo-pill bo-pill--success">' . htmlspecialchars((string) $produit) . '</span>';
                                                    }
                                                }
                                                ?>
                                            </td>

                                            <td class="bo-td-center">
                                                <?php echo htmlspecialchars((string) ($frigo['derniere_date_ajout'] ?? '')); ?>
                                            </td>

                                            <td class="bo-td-center">
                                                <a
                                                    href="Detail-Frigo-Back.php?id_utilisateur=<?php echo urlencode($frigo['id_utilisateur']); ?>"
                                                    class="bo-img-link"
                                                    title="Voir le détail"
                                                    aria-label="Voir le détail"
                                                ><img src="images/details.png" width="22" height="22" alt=""></a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="bo-table-pager">
                        <button type="button" class="bo-pager-arrow" id="frigos-pager-prev" aria-label="Précédent">‹</button>
                        <span class="bo-pager-info" id="frigos-pager-info">Page 1 / 1</span>
                        <button type="button" class="bo-pager-arrow" id="frigos-pager-next" aria-label="Suivant">›</button>
                    </div>
                <?php } ?>
            </section>

    </div>
</main>
<?php bo_layout_end(); ?>

<script>
    (function () {
        var wrap = document.getElementById('frigos-table-wrap');
        if (!wrap) return;
        var rows = Array.prototype.slice.call(wrap.querySelectorAll('tbody tr'));
        if (!rows.length) return;
        var prev = document.getElementById('frigos-pager-prev');
        var next = document.getElementById('frigos-pager-next');
        var info = document.getElementById('frigos-pager-info');
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

