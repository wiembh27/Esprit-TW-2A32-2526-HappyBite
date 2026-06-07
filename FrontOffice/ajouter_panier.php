<?php
declare(strict_types=1);

require_once __DIR__ . '/../Controllers/ProduitController.php';
require_once __DIR__ . '/includes/panier_session.php';

panier_ensure_session();

$ajax = isset($_GET['ajax']) && $_GET['ajax'] === '1';

$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ok = false;
$message = 'Produit introuvable.';

if (!$loggedIn) {
    $message = 'Connectez-vous pour ajouter au panier.';
    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: auth/login.php');
    exit;
}

if ($id > 0) {
    $produitController = new ProduitController();
    $row = $produitController->getProduitById($id);
    if ($row) {
        panier_add_product($id, (float) $row['prix']);
        $ok = true;
        $message = 'Ajouté au panier';
    }
}

if ($ajax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$back = $_SERVER['HTTP_REFERER'] ?? '';
if ($back !== '') {
    header('Location: ' . $back);
    exit;
}

header('Location: List-Produit.php');
exit;
