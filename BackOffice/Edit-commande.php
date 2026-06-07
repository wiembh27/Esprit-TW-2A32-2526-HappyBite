<?php
declare(strict_types=1);

require_once __DIR__ . '/../Controllers/CommandeController.php';
require_once __DIR__ . '/includes/bo_layout_start.php';

$commandeCtrl = new CommandeController();

$idCommande = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($idCommande <= 0) {
    header('Location: list-com-liv.php?vue=commande');
    exit;
}

$commandeEdition = $commandeCtrl->getCommandeById($idCommande);
if ($commandeEdition === null) {
    header('Location: list-com-liv.php?vue=commande');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maj_commande'])) {
    $modeMaj = trim((string) ($_POST['mode_paiement'] ?? ''));
    $redStr = str_replace(',', '.', trim((string) ($_POST['reduction'] ?? '0')));
    $reductionMaj = is_numeric($redStr) ? (float) $redStr : 0.0;
    if (in_array($modeMaj, ['carte', 'cash', 'paypal'], true)) {
        $commandeCtrl->finaliserCommande($idCommande, $modeMaj, $reductionMaj);
    }
    header('Location: list-com-liv.php?vue=commande');
    exit;
}

$nomsProduits = $commandeCtrl->getNomsProduitsCommande($idCommande);
$totalFmt = number_format((float) ($commandeEdition['total'] ?? 0), 2, ',', ' ');
$reductionVal = number_format((float) ($commandeEdition['reduction'] ?? 0), 2, ',', ' ');
$modeRaw = strtolower(trim((string) ($commandeEdition['modePaiement'] ?? '')));
$modeSel = in_array($modeRaw, ['carte', 'cash', 'paypal'], true) ? $modeRaw : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Modifier commande</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-bo">

<?php bo_layout_start('comliv'); ?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 700px; width: 100%;">
        <section class="commande-panel liste-com-liv-form-panel" aria-label="Modifier une commande">
            <h1 class="liste-com-liv-form-title">Modifier la commande #<?php echo $idCommande; ?></h1>
            <form method="post" action="">
                <div class="commande-field">
                    <label for="produit-edit-com">Produit</label>
                    <input type="text" id="produit-edit-com" readonly value="<?php echo htmlspecialchars($nomsProduits); ?>">
                </div>
                <div class="commande-field">
                    <label for="total-edit-com">Total</label>
                    <input type="text" id="total-edit-com" readonly value="<?php echo htmlspecialchars($totalFmt); ?> DT">
                </div>
                <div class="commande-field">
                    <label for="reduction-edit-com">Code de Promo</label>
                    <input type="text" id="reduction-edit-com" name="reduction" placeholder="Montant ou 0" value="<?php echo htmlspecialchars($reductionVal); ?>">
                </div>
                <div class="commande-field">
                    <label for="mode-paiement-edit">Mode de paiement</label>
                    <select id="mode-paiement-edit" name="mode_paiement" required>
                        <option value="" disabled<?php echo $modeSel === '' ? ' selected' : ''; ?>>Choisir un mode de paiement</option>
                        <option value="carte"<?php echo $modeSel === 'carte' ? ' selected' : ''; ?>>Carte</option>
                        <option value="cash"<?php echo $modeSel === 'cash' ? ' selected' : ''; ?>>Cash</option>
                        <option value="paypal"<?php echo $modeSel === 'paypal' ? ' selected' : ''; ?>>Paypal</option>
                    </select>
                </div>
                <div class="commande-actions liste-com-liv-form-actions">
                    <a href="list-com-liv.php?vue=commande" class="btn-commande-outline">Annuler</a>
                    <button type="submit" name="maj_commande" value="1" class="btn-commande-primary">Enregistrer</button>
                </div>
            </form>
        </section>
    </div>
</main>

<?php bo_layout_end(); ?>

</body>
</html>
