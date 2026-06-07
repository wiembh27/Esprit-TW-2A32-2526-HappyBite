<?php

declare(strict_types=1);

/**
 * Garde d’accès BackOffice : session dédiée (cookie HAPPYBITE_BO), indépendante du FrontOffice.
 */

require_once __DIR__ . '/bo_session.php';

bo_session_start();

$loggedIn = !empty($_SESSION['bo_logged_in']) && $_SESSION['bo_logged_in'] === true;
$role = strtolower(trim((string) ($_SESSION['bo_user_role'] ?? '')));
$statut = strtolower(trim((string) ($_SESSION['bo_user_statut'] ?? 'actif')));

if (!$loggedIn) {
    $_SESSION['bo_login_error'] = 'Connexion requise pour accéder au back-office.';
    header('Location: ' . bo_login_path());
    exit;
}

if ($role !== 'admin') {
    header('Location: ../FrontOffice/Home.php');
    exit;
}

if ($statut === 'bloqué') {
    $_SESSION['bo_login_error'] = 'Compte bloqué.';
    $_SESSION['bo_logged_in'] = false;
    unset(
        $_SESSION['bo_user_id'],
        $_SESSION['bo_user_role'],
        $_SESSION['bo_user_email'],
        $_SESSION['bo_user_prenom'],
        $_SESSION['bo_user_nom'],
        $_SESSION['bo_user_statut']
    );
    header('Location: ' . bo_login_path());
    exit;
}
