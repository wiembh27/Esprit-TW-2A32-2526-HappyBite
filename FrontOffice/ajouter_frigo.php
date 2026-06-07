<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Controllers/FrigoController.php';
require_once __DIR__ . '/../Controllers/ProduitController.php';

$ajax = ($_POST['ajax'] ?? $_GET['ajax'] ?? '') === '1';

$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$idUtilisateur = $loggedIn ? (int) ($_SESSION['user_id'] ?? 0) : 0;

$idProduit = (int) ($_POST['id_produit'] ?? $_GET['id'] ?? 0);
$quantite = (int) ($_POST['quantite'] ?? $_GET['quantite'] ?? 1);
if ($quantite < 1) {
    $quantite = 1;
}

$ok = false;
$message = 'Produit introuvable.';

if (!$loggedIn || $idUtilisateur < 1) {
    $message = 'Connectez-vous pour ajouter au frigo.';
    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: auth/login.php');
    exit;
}

if ($idProduit > 0) {
    $produitController = new ProduitController();
    $row = $produitController->getProduitById($idProduit);
    if ($row) {
        $frigoController = new FrigoController();
        if ($frigoController->ajouterAuFrigo($idUtilisateur, $idProduit, $quantite)) {
            $ok = true;
            $message = 'Ajouté au frigo';
        } else {
            $message = "Impossible d'enregistrer dans le frigo.";
        }
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
