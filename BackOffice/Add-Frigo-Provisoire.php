<?php
include __DIR__ . '/../Controllers/ProduitController.php';
require_once __DIR__ . '/../config/Database.php';

$produitController = new ProduitController();
$produits = $produitController->listProduits();

$db = Config::getConnexion();

$utilisateurs = $db->query("
    SELECT id, nom, prenom 
    FROM utilisateur 
    WHERE role = 'client'
    ORDER BY id ASC
")->fetchAll(PDO::FETCH_ASSOC);

$message = "";

$idUtilisateurSelectionne = $_GET['id_utilisateur'] ?? ($_POST['id_utilisateur'] ?? '');

if (isset($_POST['save'])) {
    $idUtilisateurSelectionne = $_POST['id_utilisateur'];
    $produitsCoches = $_POST['produits'] ?? [];
    $quantites = $_POST['quantites'] ?? [];

    // Supprimer l'ancien frigo du client
    $delete = $db->prepare("DELETE FROM frigo WHERE id_utilisateur = :id_utilisateur");
    $delete->execute([
        'id_utilisateur' => $idUtilisateurSelectionne
    ]);

    // Réinsérer les produits cochés
    foreach ($produitsCoches as $idProduit => $value) {
        $quantite = $quantites[$idProduit] ?? 1;

        $insert = $db->prepare("
            INSERT INTO frigo (id_utilisateur, id_produit, quantite, date_ajout)
            VALUES (:id_utilisateur, :id_produit, :quantite, NOW())
        ");

        $insert->execute([
            'id_utilisateur' => $idUtilisateurSelectionne,
            'id_produit' => $idProduit,
            'quantite' => $quantite
        ]);
    }

    $message = "Frigo enregistré avec succès.";
}

// Récupérer le frigo actuel du client sélectionné
$frigoActuel = [];

if (!empty($idUtilisateurSelectionne)) {
    $query = $db->prepare("
        SELECT id_produit, quantite
        FROM frigo
        WHERE id_utilisateur = :id_utilisateur
    ");

    $query->execute([
        'id_utilisateur' => $idUtilisateurSelectionne
    ]);

    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
        $frigoActuel[$ligne['id_produit']] = $ligne['quantite'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Frigo provisoire</title>
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
        .page-add-frigo-prov .bo-frigo-prod-row {
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid var(--bo-border);
            border-radius: 12px;
            padding: 12px 14px;
            background: #fff;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .page-add-frigo-prov .bo-frigo-prod-row input[type="number"] { max-width: 100px; }
    </style>
</head>

<body class="page-bo page-bo-catalog-form page-add-frigo-prov">
<?php
require_once __DIR__ . '/includes/bo_layout_start.php';
bo_layout_start('produit');
?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1180px; width: 100%;">
        <?php bo_catalog_chrome_topbar('frigo'); ?>

        <div class="list-produit-head">
            <div>
                <h1 class="list-produit-title">Frigo provisoire</h1>
                <p class="list-produit-subtitle">Ajouter ou modifier les produits du frigo d’un client</p>
            </div>
            <a href="List-Frigo-Back.php" class="btn-commande-outline">Retour aux frigos</a>
        </div>

        <?php if (!empty($message)) { ?>
            <div class="bo-flash-success"><?php echo htmlspecialchars($message); ?></div>
        <?php } ?>

        <section class="bo-panel" aria-label="Sélection client">
            <form method="GET">
                <div class="bo-form-row" style="grid-template-columns: 1fr auto; align-items: end;">
                    <div class="bo-field">
                        <label for="id_utilisateur_select">Choisir un client</label>
                        <select id="id_utilisateur_select" name="id_utilisateur">
                            <option value="">-- Choisir un client --</option>
                            <?php foreach ($utilisateurs as $u) { ?>
                                <option
                                    value="<?php echo htmlspecialchars((string) $u['id']); ?>"
                                    <?php echo ($idUtilisateurSelectionne == $u['id']) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars(trim($u['prenom'] . ' ' . $u['nom'])); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="bo-field bo-field-submit">
                        <button type="submit" class="bo-btn-primary">Charger</button>
                    </div>
                </div>
            </form>
        </section>

        <?php if (!empty($idUtilisateurSelectionne)) { ?>
            <section class="bo-panel" aria-label="Produits du frigo" style="margin-top: 18px;">
                <h2 style="margin:0 0 16px;font-size:1.1rem;font-weight:700;color:#1f3a28;">Produits du frigo</h2>
                <form method="POST">
                    <input type="hidden" name="id_utilisateur" value="<?php echo htmlspecialchars((string) $idUtilisateurSelectionne); ?>">

                    <?php foreach ($produits as $p) { ?>
                        <?php
                        $idProduit = $p['id_produit'];
                        $isChecked = isset($frigoActuel[$idProduit]);
                        $quantite = $isChecked ? $frigoActuel[$idProduit] : 1;
                        ?>
                        <div class="bo-frigo-prod-row">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                name="produits[<?php echo (int) $idProduit; ?>]"
                                value="1"
                                id="fp_<?php echo (int) $idProduit; ?>"
                                <?php echo $isChecked ? 'checked' : ''; ?>
                            >
                            <div style="flex:1;min-width:160px;">
                                <label for="fp_<?php echo (int) $idProduit; ?>" style="font-weight:500;cursor:pointer;margin:0;">
                                    <?php echo htmlspecialchars($p['nom']); ?>
                                </label>
                                <div style="font-size:13px;color:#6b7c76;">
                                    <?php echo htmlspecialchars($p['nom_categorie'] ?? 'Sans catégorie'); ?>
                                </div>
                            </div>
                            <div class="bo-field" style="margin:0;min-width:120px;">
                                <label for="q_<?php echo (int) $idProduit; ?>">Quantité</label>
                                <input
                                    type="number"
                                    id="q_<?php echo (int) $idProduit; ?>"
                                    name="quantites[<?php echo (int) $idProduit; ?>]"
                                    value="<?php echo htmlspecialchars((string) $quantite); ?>"
                                    min="1"
                                >
                            </div>
                        </div>
                    <?php } ?>

                    <div class="bo-form-actions" style="justify-content: flex-end;">
                        <button type="submit" name="save" value="1" class="bo-btn-primary">Enregistrer le frigo</button>
                    </div>
                </form>
            </section>
        <?php } ?>
    </div>
</main>

<?php bo_layout_end(); ?>
<script src="/Views/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

