<?php
declare(strict_types=1);

require_once __DIR__ . '/../Controllers/RecetteController.php';
require_once __DIR__ . '/includes/panier_session.php';

panier_ensure_session();

$ajax = isset($_GET['ajax']) && $_GET['ajax'] === '1';
$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

$idRecette = isset($_GET['id_recette']) ? (int) $_GET['id_recette'] : 0;

$ok = false;
$message = 'Recette introuvable.';

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

if ($idRecette > 0) {
    $recetteController = new RecetteController();
    $produits = $recetteController->getProduitsByRecette($idRecette);
    if (!empty($produits)) {
        foreach ($produits as $produit) {
            $idProduit = (int) ($produit['id_produit'] ?? 0);
            if ($idProduit < 1) {
                continue;
            }
            $prix = (float) ($produit['prix'] ?? 0);
            panier_add_product($idProduit, $prix);
        }
        $ok = true;
        $message = 'Ajoute au panier';
    } else {
        $message = 'Aucun produit dans cette recette.';
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

header('Location: List-Recette.php');
exit;
