<?php
declare(strict_types=1);

require_once __DIR__ . '/../Controllers/LivraisonController.php';
require_once __DIR__ . '/includes/bo_layout_start.php';

$livCtrl = new LivraisonController();

$idLivraison = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($idLivraison <= 0) {
    header('Location: list-com-liv.php?vue=livraison');
    exit;
}

$livraisonEdition = $livCtrl->getLivraisonById($idLivraison);
if ($livraisonEdition === null) {
    header('Location: list-com-liv.php?vue=livraison');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maj_livraison'])) {
    $dateMaj = trim((string) ($_POST['livraison_date'] ?? ''));
    $statutMaj = trim((string) ($_POST['statut'] ?? ''));
    if ($dateMaj !== '' && $statutMaj !== '') {
        $livCtrl->updateLivraison($idLivraison, $dateMaj, $statutMaj);
    }
    header('Location: list-com-liv.php?vue=livraison');
    exit;
}

$rawDate = LivraisonController::extraireDatePourAffichage($livraisonEdition);
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
    $dateEditValue = $rawDate;
} else {
    $parsed = DateTimeImmutable::createFromFormat('d/m/Y', $rawDate);
    $dateEditValue = $parsed ? $parsed->format('Y-m-d') : $rawDate;
}
$statutEditValue = (string) ($livraisonEdition['statut'] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>Modifier livraison</title>
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
        <section class="commande-panel liste-com-liv-form-panel" aria-label="Modifier une livraison">
            <h1 class="liste-com-liv-form-title">Modifier la livraison #<?php echo $idLivraison; ?></h1>
            <form method="post" action="">
                <div class="commande-field">
                    <label for="livraison_date">Date de livraison</label>
                    <input type="date" id="livraison_date" name="livraison_date" required value="<?php echo htmlspecialchars($dateEditValue); ?>">
                </div>
                <div class="commande-field">
                    <label for="statut">Statut</label>
                    <input type="text" id="statut" name="statut" required value="<?php echo htmlspecialchars($statutEditValue); ?>">
                </div>
                <div class="commande-actions liste-com-liv-form-actions">
                    <a href="list-com-liv.php?vue=livraison" class="btn-commande-outline">Annuler</a>
                    <button type="submit" name="maj_livraison" value="1" class="btn-commande-primary">Enregistrer</button>
                </div>
            </form>
        </section>
    </div>
</main>

<?php bo_layout_end(); ?>

</body>
</html>
