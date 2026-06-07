<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Controllers/CommandeController.php';
require_once __DIR__ . '/../../Controllers/LivraisonController.php';
require_once __DIR__ . '/../includes/panier_session.php';
require_once __DIR__ . '/../includes/fo_i18n.php';

panier_ensure_session();
fo_init_i18n_for_request();

header('Content-Type: application/json; charset=utf-8');

$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userId = $loggedIn ? (int) ($_SESSION['user_id'] ?? 0) : 0;

if (!$loggedIn || $userId < 1) {
    echo json_encode([
        'ok' => false,
        'error' => 'login',
        'message' => fo_t('track.login_required'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$commandeCtrl = new CommandeController();
$livraisonCtrl = new LivraisonController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_transit') {
    $postCmd = (int) ($_POST['id_commande'] ?? 0);
    $transitSec = (int) ($_POST['transit_seconds'] ?? 0);
    if ($postCmd < 1 || $transitSec < 1 || !$commandeCtrl->commandeAppartientAUtilisateur($postCmd, $userId)) {
        echo json_encode(['ok' => false, 'error' => 'commande'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $cmdPost = $commandeCtrl->getCommandeById($postCmd);
    if ($cmdPost === null || empty($cmdPost['id_livraison'])) {
        echo json_encode(['ok' => false, 'error' => 'livraison'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    try {
        $timelinePost = $livraisonCtrl->enregistrerDureeTransit((int) $cmdPost['id_livraison'], $transitSec, $cmdPost);
        echo json_encode(['ok' => true, 'timeline' => $timelinePost], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => 'server'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$idCommande = isset($_GET['id_commande']) ? (int) $_GET['id_commande'] : 0;
$commande = null;

if ($idCommande > 0 && $commandeCtrl->commandeAppartientAUtilisateur($idCommande, $userId)) {
    $row = $commandeCtrl->getCommandeById($idCommande);
    if (is_array($row) && !empty($row['id_livraison'])) {
        $commande = $row;
    }
}

if ($commande === null) {
    $commande = $commandeCtrl->getDerniereCommandeAvecLivraisonPourUtilisateur($userId);
    $idCommande = (int) ($commande['id_commande'] ?? 0);
}

$commandesSuivi = $commandeCtrl->listCommandesAvecLivraisonPourUtilisateur($userId);
if ($commande === null && $commandesSuivi !== []) {
    $commande = $commandesSuivi[0];
    $idCommande = (int) ($commande['id_commande'] ?? 0);
}

if ($commande === null || empty($commande['id_livraison'])) {
    echo json_encode([
        'ok' => false,
        'error' => 'no_delivery',
        'message' => fo_t('track.no_delivery'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$livraison = $livraisonCtrl->getLivraisonById((int) $commande['id_livraison'], $commande);
if ($livraison === null) {
    echo json_encode(['ok' => false, 'error' => 'livraison'], JSON_UNESCAPED_UNICODE);
    exit;
}

$trackTimeline = fo_delivery_localize_timeline($livraisonCtrl->buildTimelineState($livraison, $commande));
$statusRaw = (string) ($trackTimeline['statut'] ?? ($livraison['statut'] ?? ''));
$statusKey = (string) ($trackTimeline['phase'] ?? 'preparation');

$selectOptions = [];
foreach ($commandesSuivi as $rowC) {
    $idc = (int) ($rowC['id_commande'] ?? 0);
    $idl = (int) ($rowC['id_livraison'] ?? 0);
    if ($idc < 1 || $idl < 1) {
        continue;
    }
    $livOpt = $livraisonCtrl->getLivraisonById($idl);
    $statLabel = $livOpt !== null ? fo_delivery_status_label((string) ($livOpt['statut'] ?? '—')) : '—';
    $dateRaw = $livOpt !== null ? LivraisonController::extraireDatePourAffichage($livOpt) : '';
    $dateLabel = $dateRaw;
    $dtOpt = DateTimeImmutable::createFromFormat('Y-m-d', $dateRaw);
    if ($dtOpt instanceof DateTimeImmutable) {
        $dateLabel = $dtOpt->format('d/m/Y');
    }
    $selectOptions[] = [
        'id_commande' => $idc,
        'label' => sprintf(
            fo_t('track.order_option'),
            $idc,
            $dateLabel !== '' ? $dateLabel : 'N/A',
            $statLabel
        ),
        'selected' => ($idc === $idCommande),
    ];
}

echo json_encode([
    'ok' => true,
    'id_commande' => $idCommande,
    'status_raw' => $statusRaw,
    'status_key' => $statusKey,
    'progress' => (int) ($trackTimeline['progress_percent'] ?? 10),
    'eta_line' => (string) ($trackTimeline['eta_line'] ?? ''),
    'sub_line' => (string) ($trackTimeline['sub_line'] ?? ''),
    'timeline' => $trackTimeline,
    'shop_lat' => 36.8996184,
    'shop_lng' => 10.1929178,
    'select_options' => $selectOptions,
], JSON_UNESCAPED_UNICODE);
