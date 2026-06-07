<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/fo_catalog_fragment.php';
$foCatalogInline = fo_catalog_fragment_bootstrap();

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

$idProduit = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($idProduit < 1) {
    header('Location: List-Produit-Fournisseur.php');
    exit;
}

if (!$foCatalogInline) {
    header('Location: List-Produit-Fournisseur.php');
    exit;
}

include __DIR__ . '/../Controllers/ProduitController.php';

$produitController = new ProduitController();
if (method_exists($produitController, 'getProduitDetailsByIdAndUtilisateur')) {
    $produit = $produitController->getProduitDetailsByIdAndUtilisateur($idProduit, $idFournisseur);
} else {
    $produit = $produitController->getProduitByIdAndUtilisateur($idProduit, $idFournisseur);
}

if (!$produit) {
    echo '<p class="text-danger mb-0">Produit introuvable ou accès refusé.</p>';
    return;
}

require_once __DIR__ . '/includes/fo_inline_crud.php';
$foEditUrl = fo_inline_crud_list_url('List-Produit-Fournisseur.php', 'edit', $idProduit, fo_inline_preserve_list_query());

$allergenes = array_filter(array_map('trim', explode(',', (string) ($produit['allergene'] ?? ''))));
$benefices = array_filter(array_map('trim', explode(',', (string) ($produit['benefices'] ?? ''))));
$isPromo = isset($produit['promo']) && $produit['promo'] !== null && $produit['promo'] !== '';
?>
<div class="fo-catalog-inline-form">
    <div class="card shadow border-0 rounded-4">
        <div class="card-body p-4">
            <div class="mb-4">
                <h2 class="fw-bold mb-2"><?php echo htmlspecialchars((string) $produit['nom']); ?></h2>
                <span class="badge bg-light text-dark fs-6">
                    <?php echo htmlspecialchars((string) ($produit['nom_categorie'] ?? ('Catégorie #' . ($produit['id_categorie'] ?? '')))); ?>
                </span>
            </div>

            <div class="mb-4 text-center">
                <?php if (!empty($produit['image'])) { ?>
                    <img
                        src="/uploads/<?php echo htmlspecialchars((string) $produit['image']); ?>"
                        alt="<?php echo htmlspecialchars((string) $produit['nom']); ?>"
                        style="max-width: 250px; max-height: 250px; object-fit: cover; border-radius: 16px;"
                    >
                <?php } else { ?>
                    <p class="text-muted mb-0">Aucune image définie.</p>
                <?php } ?>
            </div>

            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="p-3 bg-light rounded-3 h-100">
                        <strong>Prix :</strong><br>
                        <?php if ($isPromo) { ?>
                            <span class="text-muted text-decoration-line-through"><?php echo htmlspecialchars((string) $produit['prix']); ?> DT</span>
                            <span class="text-warning fw-bold"><?php echo htmlspecialchars((string) $produit['promo']); ?> DT</span>
                        <?php } else { ?>
                            <span class="text-success fs-5 fw-bold"><?php echo htmlspecialchars((string) $produit['prix']); ?> DT</span>
                        <?php } ?>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="p-3 bg-light rounded-3 h-100">
                        <strong>Calories :</strong><br>
                        <span class="fs-5"><?php echo htmlspecialchars((string) ($produit['calories'] ?? 'Non défini')); ?></span>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h5 class="fw-bold">Allergènes</h5>
                <?php if ($allergenes !== []) { ?>
                    <?php foreach ($allergenes as $item) { ?>
                        <span class="badge bg-danger me-1 mb-1"><?php echo htmlspecialchars($item); ?></span>
                    <?php } ?>
                <?php } else { ?>
                    <p class="text-muted mb-0">Aucun allergène précisé.</p>
                <?php } ?>
            </div>

            <div class="mb-4">
                <h5 class="fw-bold">Bénéfices</h5>
                <?php if ($benefices !== []) { ?>
                    <?php foreach ($benefices as $item) { ?>
                        <span class="badge bg-success me-1 mb-1"><?php echo htmlspecialchars($item); ?></span>
                    <?php } ?>
                <?php } else { ?>
                    <p class="text-muted mb-0">Aucun bénéfice précisé.</p>
                <?php } ?>
            </div>

            <p class="mb-4"><strong>Date d'ajout :</strong> <?php echo htmlspecialchars((string) ($produit['date_ajout'] ?? '')); ?></p>

            <div class="d-flex flex-wrap gap-2 justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill js-fo-catalog-detail-close"><?php echo fo_e('health.inline.close'); ?></button>
                <a href="<?php echo htmlspecialchars($foEditUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-warning rounded-pill"><?php echo fo_e('supplier.modify'); ?></a>
            </div>
        </div>
    </div>
</div>
