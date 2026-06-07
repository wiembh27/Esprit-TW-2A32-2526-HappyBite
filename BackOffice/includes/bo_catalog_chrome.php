<?php
declare(strict_types=1);

/**
 * Shared layout helpers for catalog Add / Edit / Detail pages (aligned with List-* pages).
 *
 * @param string $active One of: produit, categorie, recette, frigo, dashboard
 */
function bo_catalog_chrome_styles(): void
{
    ?>
    <style>
        .page-bo-catalog-form .commande-wrap { padding-top: 8px; }
        .page-bo-catalog-form .list-produit-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .page-bo-catalog-form .list-produit-title {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            color: #1f3a28;
        }
        .page-bo-catalog-form .list-produit-subtitle {
            margin: 8px 0 0;
            font-size: 1rem;
            color: #2f3d36;
        }
        .page-bo-catalog-form .bo-form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 22px;
        }
        .page-bo-catalog-form .bo-flash-error {
            background: #fdeaea;
            border: 1px solid #f5c2c7;
            color: #842029;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 18px;
        }
        .page-bo-catalog-form .bo-flash-error ul { margin: 0; padding-left: 1.2rem; }
        .page-bo-catalog-form .bo-flash-success {
            background: #d1e7dd;
            border: 1px solid #badbcc;
            color: #0f5132;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 18px;
        }
        .page-bo-catalog-form .bo-check-grid {
            border: 1px solid var(--bo-border);
            border-radius: 12px;
            padding: 14px;
            max-height: 300px;
            overflow-y: auto;
            background: #fafcfb;
        }
        .page-bo-catalog-form .bo-detail-grid {
            display: grid;
            gap: 12px;
        }
        .page-bo-catalog-form .bo-detail-row strong {
            color: #1f3a28;
        }
    </style>
    <?php
}

function bo_catalog_chrome_topbar(string $active): void
{
    $items = [
        'produit' => ['List-Produit.php', 'Produit'],
        'categorie' => ['List-Categorie.php', 'Categorie'],
        'recette' => ['List-Recette.php', 'Recettes'],
        'frigo' => ['List-Frigo-Back.php', 'Frigo'],
        'dashboard' => ['Dashboard-Produit.php', 'Dashboard'],
    ];
    ?>
    <div class="liste-com-liv-topbar">
        <div class="mode-buttons">
            <?php foreach ($items as $key => $link) { ?>
                <a
                    href="<?php echo htmlspecialchars($link[0]); ?>"
                    class="<?php echo $key === $active ? 'btn-commande-primary btn-vue-toggle is-active' : 'btn-commande-outline btn-vue-toggle'; ?>"
                ><?php echo htmlspecialchars($link[1]); ?></a>
            <?php } ?>
        </div>
    </div>
    <?php
}
