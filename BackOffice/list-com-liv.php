<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bo_require_admin.php';
require_once __DIR__ . '/../Controllers/CommandeController.php';
require_once __DIR__ . '/../Controllers/LivraisonController.php';
require_once __DIR__ . '/includes/bo_layout_start.php';

$commandeCtrl = new CommandeController();
$livCtrl = new LivraisonController();

$vue = $_GET['vue'] ?? 'commande';
if (!in_array($vue, ['commande', 'livraison'], true)) {
    $vue = 'commande';
}

$filtreCommandeId = trim((string) ($_GET['commande_id'] ?? ''));
$filtreCommandeDate = trim((string) ($_GET['commande_date'] ?? ''));
$filtreLivraisonId = trim((string) ($_GET['livraison_id'] ?? ''));
$filtreLivraisonDate = trim((string) ($_GET['livraison_date'] ?? ''));
$sortBy = trim((string) ($_GET['sort_by'] ?? 'aucun'));
$sortOrder = trim((string) ($_GET['sort_order'] ?? 'desc'));
if (!in_array($sortBy, ['aucun', 'date', 'total', 'mode_paiement'], true)) {
    $sortBy = 'aucun';
}
if (!in_array($sortOrder, ['asc', 'desc'], true)) {
    $sortOrder = 'desc';
}
$mode = trim((string) ($_GET['mode'] ?? $vue));
if (!in_array($mode, ['commande', 'livraison', 'dashboard'], true)) {
    $mode = $vue;
}

if (isset($_GET['supprimer_livraison'])) {
    $idSuppr = (int) $_GET['supprimer_livraison'];
    if ($idSuppr > 0) {
        $livCtrl->supprimerLivraison($idSuppr);
    }
    header('Location: list-com-liv.php?vue=livraison');
    exit;
}

if (isset($_GET['supprimer_commande'])) {
    $idSupprC = (int) $_GET['supprimer_commande'];
    if ($idSupprC > 0) {
        $commandeCtrl->supprimerCommande($idSupprC);
    }
    header('Location: list-com-liv.php?vue=commande');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maj_livraison'])) {
    $idMaj = (int) ($_POST['id_livraison'] ?? 0);
    $dateMaj = trim((string) ($_POST['livraison_date'] ?? ''));
    $statutMaj = trim((string) ($_POST['statut'] ?? ''));
    if ($idMaj > 0 && $dateMaj !== '' && $statutMaj !== '') {
        $livCtrl->updateLivraison($idMaj, $dateMaj, $statutMaj);
    }
    header('Location: list-com-liv.php?vue=livraison');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maj_commande'])) {
    $idMajC = (int) ($_POST['id_commande'] ?? 0);
    $modeMaj = trim((string) ($_POST['mode_paiement'] ?? ''));
    $redStr = str_replace(',', '.', trim((string) ($_POST['reduction'] ?? '0')));
    $reductionMaj = is_numeric($redStr) ? (float) $redStr : 0.0;
    if ($idMajC > 0 && in_array($modeMaj, ['carte', 'cash', 'paypal'], true)) {
        $commandeCtrl->finaliserCommande($idMajC, $modeMaj, $reductionMaj);
    }
    header('Location: list-com-liv.php?vue=commande');
    exit;
}

$idModCom = isset($_GET['modifier_commande']) ? (int) $_GET['modifier_commande'] : 0;
$commandeEdition = null;
if ($idModCom > 0) {
    $commandeEdition = $commandeCtrl->getCommandeById($idModCom);
    if ($commandeEdition === null) {
        $idModCom = 0;
    } else {
        $vue = 'commande';
    }
}

$idModifier = isset($_GET['modifier_livraison']) ? (int) $_GET['modifier_livraison'] : 0;
$livraisonEdition = null;
if ($commandeEdition === null && $idModifier > 0) {
    $livraisonEdition = $livCtrl->getLivraisonById($idModifier);
    if ($livraisonEdition === null) {
        $idModifier = 0;
    }
}

$commandes = $commandeCtrl->listCommandes();
$livraisons = $livCtrl->listLivraisons();

$normalizeYmd = static function (string $raw): string {
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        return $raw;
    }
    $d1 = DateTimeImmutable::createFromFormat('d/m/Y', $raw);
    if ($d1 instanceof DateTimeImmutable) {
        return $d1->format('Y-m-d');
    }
    $d2 = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw);
    if ($d2 instanceof DateTimeImmutable) {
        return $d2->format('Y-m-d');
    }
    return $raw;
};

if ($filtreCommandeId !== '' || $filtreCommandeDate !== '') {
    $idNeedle = $filtreCommandeId !== '' ? (int) $filtreCommandeId : 0;
    $dateNeedle = $filtreCommandeDate !== '' ? $normalizeYmd($filtreCommandeDate) : '';
    $commandes = array_values(array_filter($commandes, static function (array $c) use ($idNeedle, $dateNeedle, $normalizeYmd): bool {
        if ($idNeedle > 0 && (int) ($c['id_commande'] ?? 0) !== $idNeedle) {
            return false;
        }
        if ($dateNeedle !== '') {
            $raw = (string) ($c['date'] ?? $c['date_commande'] ?? '');
            if ($raw === '') {
                return false;
            }
            $ymd = $normalizeYmd(substr($raw, 0, 10));
            if ($ymd !== $dateNeedle) {
                return false;
            }
        }
        return true;
    }));
}

if ($sortBy !== 'aucun') {
    usort($commandes, static function (array $a, array $b) use ($sortBy, $sortOrder, $normalizeYmd): int {
        $left = '';
        $right = '';
        if ($sortBy === 'date') {
            $left = $normalizeYmd((string) ($a['date'] ?? $a['date_commande'] ?? ''));
            $right = $normalizeYmd((string) ($b['date'] ?? $b['date_commande'] ?? ''));
        } elseif ($sortBy === 'total') {
            $left = (string) ((float) ($a['total'] ?? 0));
            $right = (string) ((float) ($b['total'] ?? 0));
        } elseif ($sortBy === 'mode_paiement') {
            $left = strtolower(trim((string) ($a['modePaiement'] ?? '')));
            $right = strtolower(trim((string) ($b['modePaiement'] ?? '')));
        }

        if ($sortBy === 'total') {
            $cmp = ((float) $left) <=> ((float) $right);
        } else {
            $cmp = $left <=> $right;
        }
        return $sortOrder === 'asc' ? $cmp : -$cmp;
    });
}

if ($filtreLivraisonId !== '' || $filtreLivraisonDate !== '') {
    $idNeedle = $filtreLivraisonId !== '' ? (int) $filtreLivraisonId : 0;
    $dateNeedle = $filtreLivraisonDate !== '' ? $normalizeYmd($filtreLivraisonDate) : '';
    $livraisons = array_values(array_filter($livraisons, static function (array $l) use ($idNeedle, $dateNeedle, $normalizeYmd): bool {
        if ($idNeedle > 0 && (int) ($l['id_livraison'] ?? 0) !== $idNeedle) {
            return false;
        }
        if ($dateNeedle !== '') {
            $raw = LivraisonController::extraireDatePourAffichage($l);
            $ymd = $normalizeYmd($raw);
            if ($ymd !== $dateNeedle) {
                return false;
            }
        }
        return true;
    }));
}

$normalizeStatutKey = static function (string $statut): string {
    $statut = trim($statut);
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT', $statut);
    $value = strtolower($ascii !== false ? $ascii : $statut);
    $value = str_replace(['é', 'è', 'ê', 'ë', 'à', 'â', 'î', 'ï', 'ô', 'ù', 'û', 'ç'], ['e', 'e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'u', 'u', 'c'], $value);
    if (str_contains($value, 'annul')) {
        return 'annulee';
    }
    if (str_contains($value, 'cours')) {
        return 'encours';
    }
    if (str_contains($value, 'livr')) {
        return 'livree';
    }
    if (str_contains($value, 'prepar') || str_contains($value, 'attente')) {
        return 'preparation';
    }
    if (str_starts_with($value, 'en ')) {
        return 'preparation';
    }
    return 'autre';
};

$todayYmd = (new DateTimeImmutable('today'))->format('Y-m-d');

$commandeTotal = count($commandes);
$commandeRevenue = 0.0;
$commandeToday = 0;
$commandePending = 0;
$commandesParMois = [];
$modePaiementDist = [];
foreach ($commandes as $c) {
    $commandeRevenue += (float) ($c['total'] ?? 0);

    $rawDate = (string) ($c['date'] ?? $c['date_commande'] ?? '');
    $ymd = $rawDate !== '' ? $normalizeYmd(substr($rawDate, 0, 10)) : '';
    if ($ymd === $todayYmd) {
        $commandeToday++;
    }
    if ($ymd !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
        $monthKey = substr($ymd, 0, 7);
        $commandesParMois[$monthKey] = ($commandesParMois[$monthKey] ?? 0) + 1;
    }

    $modeRaw = strtolower(trim((string) ($c['modePaiement'] ?? '')));
    if ($modeRaw === '') {
        $commandePending++;
    }
    $modeKey = $modeRaw !== '' ? $modeRaw : 'non defini';
    $modePaiementDist[$modeKey] = ($modePaiementDist[$modeKey] ?? 0) + 1;
}
ksort($commandesParMois);
ksort($modePaiementDist);

$livraisonTotal = count($livraisons);
$livraisonPreparation = 0;
$livraisonEnCours = 0;
$livraisonTerminees = 0;
$statutDist = [
    'En attente' => 0,
    'En cours' => 0,
    'Livrée' => 0,
    'Annulée' => 0,
];
$livraisonsParJour = [];
foreach ($livraisons as $l) {
    $statutKey = $normalizeStatutKey((string) ($l['statut'] ?? ''));
    if ($statutKey === 'preparation') {
        $livraisonPreparation++;
        $statutDist['En attente']++;
    } elseif ($statutKey === 'encours') {
        $livraisonEnCours++;
        $statutDist['En cours']++;
    } elseif ($statutKey === 'livree') {
        $livraisonTerminees++;
        $statutDist['Livrée']++;
    } elseif ($statutKey === 'annulee') {
        $statutDist['Annulée']++;
    }

    $dateLiv = $normalizeYmd(LivraisonController::extraireDatePourAffichage($l));
    if ($dateLiv !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateLiv)) {
        $livraisonsParJour[$dateLiv] = ($livraisonsParJour[$dateLiv] ?? 0) + 1;
    }
}
ksort($livraisonsParJour);

$dateEditValue = '';
$statutEditValue = '';
if ($livraisonEdition !== null) {
    $rawDate = LivraisonController::extraireDatePourAffichage($livraisonEdition);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
        $dateEditValue = $rawDate;
    } else {
        $parsed = DateTimeImmutable::createFromFormat('d/m/Y', $rawDate);
        $dateEditValue = $parsed ? $parsed->format('Y-m-d') : $rawDate;
    }
    $statutEditValue = (string) ($livraisonEdition['statut'] ?? '');
}

$nomsProduitsEditCom = '';
$totalEditComFmt = '';
$reductionEditComVal = '';
$modePaiementEditSel = '';
if ($commandeEdition !== null) {
    $idComEd = (int) $commandeEdition['id_commande'];
    $nomsProduitsEditCom = $commandeCtrl->getNomsProduitsCommande($idComEd);
    $totalEditComFmt = number_format((float) ($commandeEdition['total'] ?? 0), 2, ',', ' ');
    $reductionEditComVal = number_format((float) ($commandeEdition['reduction'] ?? 0), 2, ',', ' ');
    $modeRaw = strtolower(trim((string) ($commandeEdition['modePaiement'] ?? '')));
    $modePaiementEditSel = in_array($modeRaw, ['carte', 'cash', 'paypal'], true) ? $modeRaw : '';
}

$imgSwap = is_file(__DIR__ . '/images/swap.png') ? 'images/swap.png' : '';
$pdfLogoSrc = is_file(__DIR__ . '/../FrontOffice/images/logo.png')
    ? '../FrontOffice/images/logo.png'
    : (is_file(__DIR__ . '/images/logo.png') ? 'images/logo.png' : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; bo_brand_render_head(); ?>

    <title>HappyBite — Commandes &amp; livraisons</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-bo page-list-com-liv">

<?php bo_layout_start('comliv'); ?>

<main class="commande-wrap">
    <div class="liste-com-liv-stack" style="max-width: 1100px; width: 100%;">
        <style>
            .liste-com-liv-title-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                flex-wrap: wrap;
            }
            .liste-com-liv-title-actions {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .liste-com-liv-dashboard-title {
                margin: 18px 0 10px;
                font-size: 2.2rem;
                font-weight: 700;
                color: #1f3a28;
            }
            .liste-com-liv-subtitle {
                margin: 8px 0 10px;
                font-size: 1.05rem;
                color: #2f3d36;
            }
            .page-list-com-liv .bo-dashboard {
                max-height: none;
                overflow: visible;
            }
            .liste-com-liv-dash-section {
                margin-bottom: 18px;
            }
            .liste-com-liv-sort-row {
                display: flex;
                align-items: end;
                gap: 10px;
                flex-wrap: wrap;
            }
            .page-list-com-liv .bo-form-row {
                grid-template-columns: 1fr 1fr auto auto;
            }
            .bo-sort-swap {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                width: 24px;
                height: 24px;
            }
            img.bo-sort-swap {
                width: 24px;
                height: 24px;
                object-fit: contain;
                display: block;
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
                transition: filter 0.2s ease, transform 0.1s ease;
            }
            .bo-table-btn:hover {
                filter: brightness(0.96);
            }
            .bo-table-btn:active {
                transform: translateY(1px);
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
            .bo-table-scroll {
                overflow: visible;
            }
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

        <?php if ($commandeEdition !== null) { ?>
            <section class="commande-panel liste-com-liv-form-panel" aria-label="Modifier une commande">
                <h2 class="liste-com-liv-form-title">Modifier la commande #<?php echo (int) $commandeEdition['id_commande']; ?></h2>
                <form method="post" action="list-com-liv.php?vue=commande">
                    <input type="hidden" name="id_commande" value="<?php echo (int) $commandeEdition['id_commande']; ?>">
                    <div class="commande-field">
                        <label for="produit-edit-com">Produit</label>
                        <input type="text" id="produit-edit-com" readonly value="<?php echo htmlspecialchars($nomsProduitsEditCom); ?>">
                    </div>
                    <div class="commande-field">
                        <label for="total-edit-com">Total</label>
                        <input type="text" id="total-edit-com" readonly value="<?php echo htmlspecialchars($totalEditComFmt); ?> DT">
                    </div>
                    <div class="commande-field">
                        <label for="reduction-edit-com">Code de Promo</label>
                        <input type="text" id="reduction-edit-com" name="reduction" placeholder="Montant ou 0" value="<?php echo htmlspecialchars($reductionEditComVal); ?>">
                    </div>
                    <div class="commande-field">
                        <label for="mode-paiement-edit">Mode de paiement</label>
                        <select id="mode-paiement-edit" name="mode_paiement" required>
                            <option value="" disabled<?php echo $modePaiementEditSel === '' ? ' selected' : ''; ?>>Choisir un mode de paiement</option>
                            <option value="carte"<?php echo $modePaiementEditSel === 'carte' ? ' selected' : ''; ?>>Carte</option>
                            <option value="cash"<?php echo $modePaiementEditSel === 'cash' ? ' selected' : ''; ?>>Cash</option>
                            <option value="paypal"<?php echo $modePaiementEditSel === 'paypal' ? ' selected' : ''; ?>>Paypal</option>
                        </select>
                        <div id="carte-paiement-details-edit" class="mode-paiement-details" hidden>
                            <p class="mode-paiement-hint">Saisie locale uniquement — non enregistrée en base.</p>
                            <div class="commande-field commande-field--nested">
                                <label for="carte-titulaire-edit">Titulaire de la carte</label>
                                <input type="text" id="carte-titulaire-edit" autocomplete="off" placeholder="Nom sur la carte">
                            </div>
                            <div class="commande-field commande-field--nested">
                                <label for="carte-numero-edit">Numéro de carte</label>
                                <input type="text" id="carte-numero-edit" inputmode="numeric" autocomplete="off" placeholder="0000 0000 0000 0000" maxlength="19">
                            </div>
                            <div class="commande-field-row">
                                <div class="commande-field commande-field--nested">
                                    <label for="carte-expiration-edit">Expiration</label>
                                    <input type="text" id="carte-expiration-edit" autocomplete="off" placeholder="MM/AA" maxlength="5">
                                </div>
                                <div class="commande-field commande-field--nested">
                                    <label for="carte-cvv-edit">CVV</label>
                                    <input type="password" id="carte-cvv-edit" autocomplete="off" placeholder="•••" maxlength="4">
                                </div>
                            </div>
                        </div>
                        <div id="cash-paiement-details-edit" class="mode-paiement-details" hidden>
                            <p class="mode-paiement-hint">Saisie locale uniquement — non enregistrée en base.</p>
                            <div class="commande-field commande-field--nested">
                                <label for="cash-montant-edit">Montant prévu (billets / pièces)</label>
                                <input type="text" id="cash-montant-edit" autocomplete="off" placeholder="Ex. 50 DT">
                            </div>
                            <div class="commande-field commande-field--nested">
                                <label for="cash-contact-edit">Téléphone pour le livreur</label>
                                <input type="tel" id="cash-contact-edit" autocomplete="off" placeholder="+216 …">
                            </div>
                            <div class="commande-field commande-field--nested">
                                <label for="cash-note-edit">Note pour la livraison</label>
                                <input type="text" id="cash-note-edit" autocomplete="off" placeholder="Sonnette, étage…">
                            </div>
                        </div>
                        <div id="paypal-paiement-details-edit" class="mode-paiement-details" hidden>
                            <p class="mode-paiement-hint">Saisie locale uniquement — non enregistrée en base.</p>
                            <div class="commande-field commande-field--nested">
                                <label for="paypal-email-edit">E-mail du compte PayPal</label>
                                <input type="email" id="paypal-email-edit" autocomplete="off" placeholder="vous@exemple.com">
                            </div>
                            <div class="commande-field commande-field--nested">
                                <label for="paypal-nom-edit">Nom affiché sur PayPal</label>
                                <input type="text" id="paypal-nom-edit" autocomplete="off" placeholder="Nom du compte">
                            </div>
                        </div>
                    </div>
                    <div class="commande-actions liste-com-liv-form-actions">
                        <a href="list-com-liv.php?vue=commande" class="btn-commande-outline">Annuler</a>
                        <button type="submit" name="maj_commande" value="1" class="btn-commande-primary">Enregistrer</button>
                    </div>
                </form>
            </section>
        <?php } elseif ($livraisonEdition !== null) { ?>
            <section class="commande-panel liste-com-liv-form-panel" aria-label="Modifier une livraison">
                <h2 class="liste-com-liv-form-title">Modifier la livraison #<?php echo (int) $livraisonEdition['id_livraison']; ?></h2>
                <form method="post" action="list-com-liv.php?vue=livraison">
                    <input type="hidden" name="id_livraison" value="<?php echo (int) $livraisonEdition['id_livraison']; ?>">
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
        <?php } ?>

        <div class="liste-com-liv-topbar">
            <div class="mode-buttons">
                <button type="button" id="btn-vue-commande" class="<?php echo $mode === 'commande' ? 'btn-commande-primary is-active' : 'btn-commande-outline'; ?> btn-vue-toggle" data-vue="commande">Commande</button>
                <button type="button" id="btn-vue-livraison" class="<?php echo $mode === 'livraison' ? 'btn-commande-primary is-active' : 'btn-commande-outline'; ?> btn-vue-toggle" data-vue="livraison">Livraison</button>
                <button type="button" id="btn-vue-dashboard" class="<?php echo $mode === 'dashboard' ? 'btn-commande-primary is-active' : 'btn-commande-outline'; ?> btn-vue-toggle" data-vue="dashboard">Dashboard</button>
            </div>
        </div>

        <div class="liste-com-liv-title-row"<?php echo $mode === 'dashboard' ? ' hidden' : ''; ?>>
            <h1 id="main-view-title" class="liste-com-liv-title">Liste de commande et livraison</h1>
            <div id="liste-title-actions" class="liste-com-liv-title-actions">
                <button type="button" id="btn-export-pdf" class="btn-commande-primary">Exporter PDF</button>
            </div>
        </div>

        <section id="dashboard-global" class="bo-dashboard"<?php echo $mode !== 'dashboard' ? ' hidden' : ''; ?>>
            <div class="liste-com-liv-dash-section liste-com-liv-dash-section--commande">
                <h2 class="liste-com-liv-dashboard-title">Dashboard des Commande</h2>
                <div class="bo-stats-grid bo-home-stats-row bo-home-stats-row--4">
                    <article class="bo-stat-card bo-home-stat bo-home-stat--c1">
                        <div class="bo-home-stat-emoji" aria-hidden="true">🛒</div>
                        <h3>Total commandes</h3>
                        <p><?php echo $commandeTotal; ?></p>
                    </article>
                    <article class="bo-stat-card bo-home-stat bo-home-stat--c2">
                        <div class="bo-home-stat-emoji" aria-hidden="true">💰</div>
                        <h3>Chiffre d'affaires</h3>
                        <p><?php echo htmlspecialchars(number_format($commandeRevenue, 2, ',', ' ')); ?> DT</p>
                    </article>
                    <article class="bo-stat-card bo-home-stat bo-home-stat--c3">
                        <div class="bo-home-stat-emoji" aria-hidden="true">📅</div>
                        <h3>Commandes aujourd'hui</h3>
                        <p><?php echo $commandeToday; ?></p>
                    </article>
                    <article class="bo-stat-card bo-home-stat bo-home-stat--c4">
                        <div class="bo-home-stat-emoji" aria-hidden="true">⏳</div>
                        <h3>Commandes en attente</h3>
                        <p><?php echo $commandePending; ?></p>
                    </article>
                </div>
                <h3 class="liste-com-liv-subtitle">Charts commandes</h3>
                <div class="bo-charts-grid bo-dash-charts-tall">
                    <section class="bo-panel">
                        <h2>Évolution des commandes (par mois)</h2>
                        <div class="bo-home-chart-wrap">
                            <canvas id="chart-commandes-mois"></canvas>
                        </div>
                    </section>
                    <section class="bo-panel">
                        <h2>Répartition par mode de paiement</h2>
                        <div class="bo-home-chart-wrap">
                            <canvas id="chart-modes-paiement"></canvas>
                        </div>
                    </section>
                </div>
            </div>
            <div class="liste-com-liv-dash-section liste-com-liv-dash-section--livraison">
                <h2 class="liste-com-liv-dashboard-title">Dashboard des livraisons</h2>
                <h3 class="liste-com-liv-subtitle">Top stats de livraison :</h3>
                <div class="bo-stats-grid bo-home-stats-row bo-home-stats-row--4">
                    <article class="bo-stat-card bo-home-stat bo-home-stat--c6">
                        <div class="bo-home-stat-emoji" aria-hidden="true">🚚</div>
                        <h3>Total livraisons</h3>
                        <p><?php echo $livraisonTotal; ?></p>
                    </article>
                    <article class="bo-stat-card bo-home-stat bo-home-stat--c3">
                        <div class="bo-home-stat-emoji" aria-hidden="true">📦</div>
                        <h3>Livraisons en attente</h3>
                        <p><?php echo $livraisonPreparation; ?></p>
                    </article>
                    <article class="bo-stat-card bo-home-stat bo-home-stat--c5">
                        <div class="bo-home-stat-emoji" aria-hidden="true">🔄</div>
                        <h3>Livraisons en cours</h3>
                        <p><?php echo $livraisonEnCours; ?></p>
                    </article>
                    <article class="bo-stat-card bo-home-stat bo-home-stat--c1">
                        <div class="bo-home-stat-emoji" aria-hidden="true">✅</div>
                        <h3>Livraisons terminées</h3>
                        <p><?php echo $livraisonTerminees; ?></p>
                    </article>
                </div>
                <h3 class="liste-com-liv-subtitle">Charts Statut distribution</h3>
                <div class="bo-charts-grid bo-dash-charts-tall">
                    <section class="bo-panel">
                        <h2>Répartition des statuts</h2>
                        <div class="bo-home-chart-wrap">
                            <canvas id="chart-statut-livraison"></canvas>
                        </div>
                    </section>
                    <section class="bo-panel">
                        <h2>Livraisons par jour</h2>
                        <div class="bo-home-chart-wrap">
                            <canvas id="chart-livraisons-jour"></canvas>
                        </div>
                    </section>
                </div>
            </div>
        </section>

        <section id="liste-com-liv-filters-panel" class="bo-panel" aria-label="Recherche / filtres"<?php echo $mode === 'dashboard' ? ' hidden' : ''; ?>>
            <form id="filters-commande" method="get" action="list-com-liv.php"<?php echo $mode !== 'commande' ? ' hidden' : ''; ?>>
                <input type="hidden" name="vue" value="commande">
                <input type="hidden" name="sort_order" id="sort_order_commande" value="<?php echo htmlspecialchars($sortOrder); ?>">
                <div class="bo-form-row">
                    <div class="bo-field">
                        <label for="commande_id">Rechercher commande (ID)</label>
                        <input type="number" id="commande_id" name="commande_id" min="1" placeholder="Ex. 12" value="<?php echo htmlspecialchars($filtreCommandeId); ?>">
                    </div>
                    <div class="bo-field">
                        <label for="commande_date">Filtrer commande (date)</label>
                        <input type="date" id="commande_date" name="commande_date" value="<?php echo htmlspecialchars($filtreCommandeDate); ?>">
                    </div>
                    <div class="bo-field bo-field-submit">
                        <button type="submit" class="bo-btn-primary">Filtrer</button>
                    </div>
                    <div class="liste-com-liv-sort-row">
                        <div class="bo-field">
                            <label for="sort_by_commande">Trier par</label>
                            <select id="sort_by_commande" name="sort_by">
                                <option value="aucun"<?php echo $sortBy === 'aucun' ? ' selected' : ''; ?>>Aucun</option>
                                <option value="date"<?php echo $sortBy === 'date' ? ' selected' : ''; ?>>Date</option>
                                <option value="total"<?php echo $sortBy === 'total' ? ' selected' : ''; ?>>Total</option>
                                <option value="mode_paiement"<?php echo $sortBy === 'mode_paiement' ? ' selected' : ''; ?>>Mode de paiement</option>
                            </select>
                        </div>
                        <div class="bo-field bo-field-submit">
                            <button type="submit" class="bo-btn-primary">Trier</button>
                        </div>
                        <div class="bo-field bo-field-submit">
                            <?php if ($imgSwap !== '') { ?>
                                <img id="btn-order-commande" class="bo-sort-swap" src="<?php echo htmlspecialchars($imgSwap); ?>" alt="Inverser le tri" role="button" tabindex="0">
                            <?php } else { ?>
                                <span id="btn-order-commande" class="bo-sort-swap" role="button" tabindex="0">↕</span>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </form>

            <form id="filters-livraison" method="get" action="list-com-liv.php"<?php echo $mode !== 'livraison' ? ' hidden' : ''; ?>>
                <input type="hidden" name="vue" value="livraison">
                <input type="hidden" name="sort_order" id="sort_order_livraison" value="<?php echo htmlspecialchars($sortOrder); ?>">
                <div class="bo-form-row">
                    <div class="bo-field">
                        <label for="livraison_id">Rechercher livraison (ID)</label>
                        <input type="number" id="livraison_id" name="livraison_id" min="1" placeholder="Ex. 4" value="<?php echo htmlspecialchars($filtreLivraisonId); ?>">
                    </div>
                    <div class="bo-field">
                        <label for="livraison_date_filter">Filtrer livraison (date)</label>
                        <input type="date" id="livraison_date_filter" name="livraison_date" value="<?php echo htmlspecialchars($filtreLivraisonDate); ?>">
                    </div>
                    <div class="bo-field bo-field-submit">
                        <button type="submit" class="bo-btn-primary">Filtrer</button>
                    </div>
                    <div class="liste-com-liv-sort-row">
                        <div class="bo-field">
                            <label for="sort_by_livraison">Trier par</label>
                            <select id="sort_by_livraison" name="sort_by">
                                <option value="aucun"<?php echo $sortBy === 'aucun' ? ' selected' : ''; ?>>Aucun</option>
                                <option value="date"<?php echo $sortBy === 'date' ? ' selected' : ''; ?>>Date</option>
                                <option value="total"<?php echo $sortBy === 'total' ? ' selected' : ''; ?>>Total</option>
                                <option value="mode_paiement"<?php echo $sortBy === 'mode_paiement' ? ' selected' : ''; ?>>Mode de paiement</option>
                            </select>
                        </div>
                        <div class="bo-field bo-field-submit">
                            <button type="submit" class="bo-btn-primary">Trier</button>
                        </div>
                        <div class="bo-field bo-field-submit">
                            <?php if ($imgSwap !== '') { ?>
                                <img id="btn-order-livraison" class="bo-sort-swap" src="<?php echo htmlspecialchars($imgSwap); ?>" alt="Inverser le tri" role="button" tabindex="0">
                            <?php } else { ?>
                                <span id="btn-order-livraison" class="bo-sort-swap" role="button" tabindex="0">↕</span>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </form>
        </section>

        <section class="bo-table-wrap" aria-label="Tableau"<?php echo $mode === 'dashboard' ? ' hidden' : ''; ?>>
            <div id="wrap-table-commande" class="table-vue"<?php echo $mode !== 'commande' ? ' hidden' : ''; ?>>
                <div class="bo-table-scroll">
                    <table class="bo-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Mode paiement</th>
                                <th>Réduction</th>
                                <th>ID livraison</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($commandes === []) { ?>
                                <tr><td colspan="7" class="bo-empty">Aucune commande.</td></tr>
                            <?php } else { ?>
                                <?php foreach ($commandes as $c) { ?>
                                    <?php $idC = (int) $c['id_commande']; ?>
                                    <tr>
                                        <td class="bo-td-center"><?php echo $idC; ?></td>
                                        <td class="bo-td-center"><?php echo htmlspecialchars((string) ($c['date'] ?? $c['date_commande'] ?? '')); ?></td>
                                        <td class="bo-td-center"><?php echo htmlspecialchars(number_format((float) ($c['total'] ?? 0), 2, ',', ' ')); ?> DT</td>
                                        <td class="bo-td-center"><?php echo htmlspecialchars((string) ($c['modePaiement'] ?? '—')); ?></td>
                                        <td class="bo-td-center"><?php echo htmlspecialchars(number_format((float) ($c['reduction'] ?? 0), 2, ',', ' ')); ?></td>
                                        <td class="bo-td-center"><?php echo $c['id_livraison'] !== null && $c['id_livraison'] !== '' ? (int) $c['id_livraison'] : '—'; ?></td>
                                        <td class="bo-td-center">
                                            <span class="bo-table-actions">
                                                <a class="bo-img-link" href="Edit-commande.php?id=<?php echo $idC; ?>" title="Modifier" aria-label="Modifier"><img src="images/modify.png" width="22" height="22" alt=""></a>
                                                <a class="bo-img-link" href="list-com-liv.php?supprimer_commande=<?php echo $idC; ?>&vue=commande" title="Supprimer" aria-label="Supprimer"><img src="images/delete.png" width="22" height="22" alt=""></a>
                                            </span>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <div class="bo-table-pager" data-table-pager="commande" aria-label="Pagination commandes">
                    <button type="button" class="bo-pager-arrow" data-pager-prev aria-label="Précédent">‹</button>
                    <span class="bo-pager-info" data-pager-info>Page 1</span>
                    <button type="button" class="bo-pager-arrow" data-pager-next aria-label="Suivant">›</button>
                </div>
            </div>

            <div id="wrap-table-livraison" class="table-vue"<?php echo $mode !== 'livraison' ? ' hidden' : ''; ?>>
                <div class="bo-table-scroll">
                    <table class="bo-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($livraisons === []) { ?>
                                <tr><td colspan="4" class="bo-empty">Aucune livraison.</td></tr>
                            <?php } else { ?>
                                <?php foreach ($livraisons as $liv) { ?>
                                    <?php
                                    $idL = (int) $liv['id_livraison'];
                                    $dateAff = LivraisonController::extraireDatePourAffichage($liv);
                                    $dtAff = $dateAff;
                                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateAff)) {
                                        $dtx = DateTimeImmutable::createFromFormat('Y-m-d', $dateAff);
                                        $dtAff = $dtx ? $dtx->format('d/m/Y') : $dateAff;
                                    }
                                    $statut = trim((string) ($liv['statut'] ?? ''));
                                    $statutKey = $normalizeStatutKey($statut);
                                    $statutClass = 'bo-statut--muted';
                                    if ($statutKey === 'annulee') {
                                        $statutClass = 'bo-statut--annulee';
                                    } elseif ($statutKey === 'preparation') {
                                        $statutClass = 'bo-statut--preparation';
                                    } elseif ($statutKey === 'encours') {
                                        $statutClass = 'bo-statut--encours';
                                    } elseif ($statutKey === 'livree') {
                                        $statutClass = 'bo-statut--livree';
                                    }
                                    ?>
                                    <tr>
                                        <td class="bo-td-center"><?php echo $idL; ?></td>
                                        <td class="bo-td-center"><?php echo htmlspecialchars($dtAff); ?></td>
                                        <td class="bo-td-center">
                                            <span class="bo-statut <?php echo htmlspecialchars($statutClass); ?>">
                                                <?php echo htmlspecialchars($statut); ?>
                                            </span>
                                        </td>
                                        <td class="bo-td-center">
                                            <span class="bo-table-actions">
                                                <a class="bo-img-link" href="Edit-livraison.php?id=<?php echo $idL; ?>" title="Modifier" aria-label="Modifier"><img src="images/modify.png" width="22" height="22" alt=""></a>
                                                <a class="bo-img-link" href="list-com-liv.php?supprimer_livraison=<?php echo $idL; ?>&vue=livraison" title="Supprimer" aria-label="Supprimer"><img src="images/delete.png" width="22" height="22" alt=""></a>
                                            </span>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <div class="bo-table-pager" data-table-pager="livraison" aria-label="Pagination livraisons">
                    <button type="button" class="bo-pager-arrow" data-pager-prev aria-label="Précédent">‹</button>
                    <span class="bo-pager-info" data-pager-info>Page 1</span>
                    <button type="button" class="bo-pager-arrow" data-pager-next aria-label="Suivant">›</button>
                </div>
            </div>
        </section>
    </div>
</main>

<?php bo_layout_end(); ?>

<div id="modal-suppression-liste" class="liste-com-liv-modal" hidden aria-hidden="true">
    <div class="liste-com-liv-modal__backdrop" tabindex="-1"></div>
    <div class="liste-com-liv-modal__box" role="dialog" aria-modal="true" aria-labelledby="modal-suppression-titre">
        <h2 id="modal-suppression-titre" class="liste-com-liv-modal__title">Supprimer cette ligne ?</h2>
        <p class="liste-com-liv-modal__text">Cette action est définitive. Confirmez-vous la suppression ?</p>
        <div class="liste-com-liv-modal__actions">
            <button type="button" class="btn-commande-outline" id="modal-suppression-annuler">Annuler</button>
            <button type="button" class="btn-commande-primary" id="modal-suppression-confirmer">Supprimer</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
<script>
(function () {
    var vueInit = <?php echo json_encode($vue); ?>;
    var modeInit = <?php echo json_encode($mode); ?>;
    var wrapC = document.getElementById('wrap-table-commande');
    var wrapL = document.getElementById('wrap-table-livraison');
    var dashGlobal = document.getElementById('dashboard-global');
    var btnC = document.getElementById('btn-vue-commande');
    var btnL = document.getElementById('btn-vue-livraison');
    var btnD = document.getElementById('btn-vue-dashboard');
    var titleEl = document.getElementById('main-view-title');
    var panelFiltres = document.getElementById('liste-com-liv-filters-panel');
    var tableWrap = document.querySelector('.bo-table-wrap');
    var titleRow = document.querySelector('.liste-com-liv-title-row');
    var filtersCommande = document.getElementById('filters-commande');
    var filtersLivraison = document.getElementById('filters-livraison');
    var orderBtnCommande = document.getElementById('btn-order-commande');
    var orderBtnLivraison = document.getElementById('btn-order-livraison');
    var sortOrderCommande = document.getElementById('sort_order_commande');
    var sortOrderLivraison = document.getElementById('sort_order_livraison');
    var exportBtn = document.getElementById('btn-export-pdf');
    var titleActions = document.getElementById('liste-title-actions');
    var pdfLogoSrc = <?php echo json_encode($pdfLogoSrc); ?>;
    if (!wrapC || !wrapL || !dashGlobal || !btnC || !btnL || !btnD || !panelFiltres || !tableWrap || !filtersCommande || !filtersLivraison) return;

    var charts = [];
    if (window.Chart) {
        var commandesMoisCanvas = document.getElementById('chart-commandes-mois');
        if (commandesMoisCanvas) {
            charts.push(new Chart(commandesMoisCanvas, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_keys($commandesParMois)); ?>,
                    datasets: [{
                        label: 'Commandes',
                        data: <?php echo json_encode(array_values($commandesParMois)); ?>,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(134,239,172,0.35)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: '#22c55e',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        x: { grid: { color: 'rgba(0,0,0,0.06)' } },
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.06)' } }
                    }
                }
            }));
        }

        var modesPaiementCanvas = document.getElementById('chart-modes-paiement');
        if (modesPaiementCanvas) {
            charts.push(new Chart(modesPaiementCanvas, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_map('ucfirst', array_keys($modePaiementDist))); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($modePaiementDist)); ?>,
                        backgroundColor: ['#ef5350', '#42a5f5', '#ffca28', '#66bb6a', '#ab47bc', '#ff7043', '#26c6da']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            }));
        }

        var statutChartCanvas = document.getElementById('chart-statut-livraison');
        if (statutChartCanvas) {
            charts.push(new Chart(statutChartCanvas, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_keys($statutDist)); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($statutDist)); ?>,
                        backgroundColor: ['#ffd54f', '#64b5f6', '#81c784', '#ef5350']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            }));
        }

        var livraisonsJourCanvas = document.getElementById('chart-livraisons-jour');
        if (livraisonsJourCanvas) {
            charts.push(new Chart(livraisonsJourCanvas, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_keys($livraisonsParJour)); ?>,
                    datasets: [{
                        label: 'Livraisons',
                        data: <?php echo json_encode(array_values($livraisonsParJour)); ?>,
                        borderColor: '#fb923c',
                        backgroundColor: 'rgba(253,186,116,0.28)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: '#fb923c',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        x: { grid: { color: 'rgba(0,0,0,0.06)' } },
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.06)' } }
                    }
                }
            }));
        }
    }

    function setTitle(mode) {
        if (!titleEl) return;
        if (mode === 'commande') {
            titleEl.textContent = 'Liste des commandes';
        } else if (mode === 'livraison') {
            titleEl.textContent = 'Liste des Livraisons';
        } else {
            titleEl.textContent = 'Liste de commande et livraison';
        }
    }

    function setMode(mode) {
        var isDashboard = mode === 'dashboard';
        var isC = mode === 'commande';
        var isL = mode === 'livraison';
        wrapC.hidden = !isC;
        wrapL.hidden = !isL;
        dashGlobal.hidden = !isDashboard;
        if (titleRow) {
            titleRow.hidden = isDashboard;
            titleRow.style.display = isDashboard ? 'none' : 'flex';
        }
        if (titleActions) {
            titleActions.hidden = isDashboard;
        }
        panelFiltres.hidden = isDashboard;
        tableWrap.hidden = isDashboard;
        filtersCommande.hidden = !isC;
        filtersLivraison.hidden = !isL;

        btnC.classList.toggle('btn-commande-primary', isC);
        btnC.classList.toggle('btn-commande-outline', !isC);
        btnC.classList.toggle('is-active', isC);
        btnL.classList.toggle('btn-commande-primary', isL);
        btnL.classList.toggle('btn-commande-outline', !isL);
        btnL.classList.toggle('is-active', isL);
        btnD.classList.toggle('btn-commande-primary', isDashboard);
        btnD.classList.toggle('btn-commande-outline', !isDashboard);
        btnD.classList.toggle('is-active', isDashboard);
        setTitle(mode);
        if (charts.length > 0) {
            setTimeout(function () {
                charts.forEach(function (chart) { chart.resize(); });
            }, 0);
        }
        try {
            var vueParam = isL ? 'livraison' : 'commande';
            history.replaceState(null, '', 'list-com-liv.php?vue=' + vueParam + '&mode=' + mode);
        } catch (e) {}
    }

    function attachOrderToggle(button, hiddenInput) {
        if (!button || !hiddenInput) return;
        function reverseAndSubmit() {
            var next = hiddenInput.value === 'asc' ? 'desc' : 'asc';
            hiddenInput.value = next;
            var form = button.closest('form');
            if (form) form.submit();
        }
        button.addEventListener('click', reverseAndSubmit);
        button.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                reverseAndSubmit();
            }
        });
    }

    btnC.addEventListener('click', function () { setMode('commande'); });
    btnL.addEventListener('click', function () { setMode('livraison'); });
    btnD.addEventListener('click', function () { setMode('dashboard'); });
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            if (!window.jspdf || !window.jspdf.jsPDF) {
                alert('Export PDF indisponible.');
                return;
            }
            var isCommande = !wrapC.hidden;
            var activeTable = isCommande ? wrapC.querySelector('table') : wrapL.querySelector('table');
            if (!activeTable) {
                alert('Aucun tableau a exporter.');
                return;
            }
            var doc = new window.jspdf.jsPDF('p', 'mm', 'a4');
            var title = isCommande ? 'Liste des commandes' : 'Liste des livraisons';
            var now = new Date();
            var dateLabel = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
            var pageCenterX = doc.internal.pageSize.getWidth() / 2;
            var headerText = title + ' - ' + dateLabel;
            function drawTable(startY) {
                doc.autoTable({
                    html: activeTable,
                    startY: startY,
                    styles: { fontSize: 9 },
                    headStyles: { fillColor: [44, 126, 52] }
                });
                doc.save((isCommande ? 'commandes' : 'livraisons') + '-' + dateLabel + '.pdf');
            }
            function drawWithoutLogo() {
                doc.setFontSize(14);
                doc.text(headerText, pageCenterX, 14, { align: 'center' });
                drawTable(20);
            }

            if (!pdfLogoSrc) {
                drawWithoutLogo();
                return;
            }

            var logoImg = new Image();
            logoImg.onload = function () {
                var ext = pdfLogoSrc.toLowerCase().split('.').pop();
                var format = ext === 'jpg' || ext === 'jpeg' ? 'JPEG' : (ext === 'png' ? 'PNG' : '');
                if (!format) {
                    drawWithoutLogo();
                    return;
                }
                doc.addImage(logoImg, format, 14, 6, 50, 17);
                doc.setFontSize(14);
                doc.text(headerText, pageCenterX, 20, { align: 'center' });
                drawTable(40);
            };
            logoImg.onerror = drawWithoutLogo;
            logoImg.src = pdfLogoSrc + (pdfLogoSrc.indexOf('?') === -1 ? '?' : '&') + 'v=' + Date.now();
        });
    }
    attachOrderToggle(orderBtnCommande, sortOrderCommande);
    attachOrderToggle(orderBtnLivraison, sortOrderLivraison);
    if (modeInit === 'dashboard' || modeInit === 'commande' || modeInit === 'livraison') {
        setMode(modeInit);
    } else {
        setMode(vueInit === 'livraison' ? 'livraison' : 'commande');
    }

    function setupPager(root, rowsPerPage) {
        if (!root) return;
        var table = root.querySelector('table');
        if (!table) return;
        var tbody = table.querySelector('tbody');
        if (!tbody) return;
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
        if (rows.length === 0) return;
        var pager = root.querySelector('.bo-table-pager');
        if (!pager) return;
        var prev = pager.querySelector('[data-pager-prev]');
        var next = pager.querySelector('[data-pager-next]');
        var info = pager.querySelector('[data-pager-info]');
        if (!prev || !next || !info) return;

        // ignore empty-state single row (colspan + .bo-empty)
        if (rows.length === 1 && rows[0].querySelector('.bo-empty')) {
            pager.hidden = true;
            return;
        }

        var page = 1;
        var totalPages = Math.max(1, Math.ceil(rows.length / rowsPerPage));

        function render() {
            totalPages = Math.max(1, Math.ceil(rows.length / rowsPerPage));
            if (page > totalPages) page = totalPages;
            var start = (page - 1) * rowsPerPage;
            var end = start + rowsPerPage;
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
    }

    setupPager(wrapC, 8);
    setupPager(wrapL, 10);
})();
</script>
<script src="js/controles.js" defer></script>

</body>
</html>
