<?php
require_once __DIR__ . '/includes/bo_require_admin.php';

include __DIR__ . '/../Controllers/CategorieController.php';
include __DIR__ . '/../Controllers/ProduitController.php';
require_once __DIR__ . '/includes/bo_layout_start.php';

$categorieController = new CategorieController();
$produitController = new ProduitController();

// Suppression sécurisée avec réaffectation
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    if ($id > 0) {
        $categorieSecours = $categorieController->createCategorieIfNotExists(
            'Non classé',
            'Catégorie utilisée automatiquement pour les produits réaffectés.'
        );

        $idCategorieSecours = (int)$categorieSecours['id_categorie'];

        if ($id === $idCategorieSecours) {
            $_SESSION['popup_error'] = "Impossible de supprimer la catégorie de secours.";
            header('Location: List-Categorie.php');
            exit;
        } else {
            $produitController->reassignProduitsToCategorie($id, $idCategorieSecours);
            $categorieController->deleteCategorie($id);

            $_SESSION['popup_success'] = 'La catégorie a été supprimée et ses produits ont été déplacés vers "Non classé".';
            header('Location: List-Categorie.php');
            exit;
        }
    }
}

// Recherche
$motCle = trim($_GET['motCle'] ?? '');

if (!empty($motCle)) {
    $categories = $categorieController->rechercherCategories($motCle);
} else {
    $categories = $categorieController->listCategories();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Liste des catégories</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-bo page-list-categorie">
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
            .bo-table-actions {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
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
            .bo-table-btn--delete {
                background: #ef4444;
                border-color: #dc2626;
                color: #fff;
            }
            .bo-table-btn--muted {
                background: #e5e7eb;
                border-color: #d1d5db;
                color: #6b7280;
                cursor: not-allowed;
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
        bo_catalog_chrome_topbar('categorie');
        ?>

        <div class="list-produit-head">
            <div>
                <h1 class="list-produit-title">Liste des catégories</h1>
                <p class="list-produit-subtitle">Gérez les catégories de vos produits</p>
            </div>
            <a href="Add-Categorie.php" class="bo-btn-primary">Ajouter une catégorie</a>
        </div>

            <section class="bo-panel" aria-label="Recherche / filtres">
                <form method="GET" action="">
                    <div class="bo-form-row" style="grid-template-columns: 1fr auto;">
                        <div class="bo-field">
                            <label for="motCle">Rechercher une catégorie</label>
                            <input
                                type="text"
                                id="motCle"
                                name="motCle"
                                placeholder="Rechercher par nom..."
                                value="<?php echo htmlspecialchars($motCle); ?>"
                            >
                        </div>
                        <div class="bo-field bo-field-submit">
                            <button type="submit" class="bo-btn-primary">Rechercher</button>
                        </div>
                    </div>
                </form>
            </section>

            <section class="bo-table-wrap" aria-label="Tableau des catégories">
                <?php if (empty($categories)) { ?>
                    <div class="bo-empty">Aucune catégorie trouvée.</div>
                <?php } else { ?>
                    <div class="bo-table-scroll" id="categories-table-wrap">
                        <table class="bo-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $categorie) { ?>
                                    <?php $isProtected = (mb_strtolower(trim($categorie->getNom())) === 'non classé'); ?>
                                    <tr>
                                        <td class="bo-td-center"><?php echo (int) $categorie->getIdCategorie(); ?></td>
                                        <td class="bo-td-left"><strong><?php echo htmlspecialchars($categorie->getNom()); ?></strong></td>
                                        <td class="bo-td-left">
                                            <?php
                                            $description = trim($categorie->getDescription());
                                            echo $description !== ''
                                                ? htmlspecialchars(mb_strimwidth($description, 0, 120, '...'))
                                                : '<span class="bo-pill bo-pill--muted">Aucune description</span>';
                                            ?>
                                        </td>
                                        <td class="bo-td-center">
                                            <span class="bo-table-actions">
                                                <a href="Detail-Categorie.php?id=<?php echo $categorie->getIdCategorie(); ?>" class="bo-img-link" title="Voir le détail" aria-label="Voir le détail"><img src="images/details.png" width="22" height="22" alt=""></a>
                                                <?php if (!$isProtected) { ?>
                                                    <a href="Edit-Categorie.php?id=<?php echo $categorie->getIdCategorie(); ?>" class="bo-img-link" title="Modifier" aria-label="Modifier"><img src="images/modify.png" width="22" height="22" alt=""></a>
                                                    <a
                                                        href="List-Categorie.php?delete=<?php echo $categorie->getIdCategorie(); ?>"
                                                        class="bo-img-link"
                                                        title="Supprimer"
                                                        aria-label="Supprimer"
                                                        onclick="return confirm('Voulez-vous vraiment supprimer cette catégorie ?');"
                                                    ><img src="images/delete.png" width="22" height="22" alt=""></a>
                                                <?php } else { ?>
                                                    <span class="bo-img-link bo-img-link--disabled" title="Non modifiable" aria-hidden="true"><img src="images/modify.png" width="22" height="22" alt=""></span>
                                                    <span class="bo-img-link bo-img-link--disabled" title="Non supprimable" aria-hidden="true"><img src="images/delete.png" width="22" height="22" alt=""></span>
                                                <?php } ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="bo-table-pager">
                        <button type="button" class="bo-pager-arrow" id="categories-pager-prev" aria-label="Précédent">‹</button>
                        <span class="bo-pager-info" id="categories-pager-info">Page 1 / 1</span>
                        <button type="button" class="bo-pager-arrow" id="categories-pager-next" aria-label="Suivant">›</button>
                    </div>
                <?php } ?>
            </section>

    </div>
</main>
<?php bo_layout_end(); ?>

<script>
    (function () {
        var wrap = document.getElementById('categories-table-wrap');
        if (!wrap) return;
        var rows = Array.prototype.slice.call(wrap.querySelectorAll('tbody tr'));
        if (!rows.length) return;
        var prev = document.getElementById('categories-pager-prev');
        var next = document.getElementById('categories-pager-next');
        var info = document.getElementById('categories-pager-info');
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

<?php if (isset($_SESSION['popup_success'])) { ?>
<script>
    alert(<?php echo json_encode($_SESSION['popup_success']); ?>);
</script>
<?php unset($_SESSION['popup_success']); } ?>

<?php if (isset($_SESSION['popup_error'])) { ?>
<script>
    alert(<?php echo json_encode($_SESSION['popup_error']); ?>);
</script>
<?php unset($_SESSION['popup_error']); } ?>

</body>
</html>

