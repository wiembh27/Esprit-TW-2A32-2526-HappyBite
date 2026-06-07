<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bo_session.php';
require_once __DIR__ . '/includes/bo_login_helpers.php';
require_once __DIR__ . '/../config/Database.php';

bo_session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . bo_login_path());
    exit;
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    $_SESSION['bo_login_error'] = 'Email et mot de passe requis.';
    header('Location: ' . bo_login_path());
    exit;
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT * FROM utilisateur WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !bo_auth_verify_password($password, (string) ($user['motDePasse'] ?? ''))) {
        $_SESSION['bo_login_error'] = 'Email ou mot de passe incorrect.';
        header('Location: ' . bo_login_path());
        exit;
    }

    if (($user['statut'] ?? '') === 'bloqué') {
        $_SESSION['bo_login_error'] = 'Compte bloqué.';
        header('Location: ' . bo_login_path());
        exit;
    }

    if (strtolower(trim((string) ($user['role'] ?? ''))) !== 'admin') {
        $_SESSION['bo_login_error'] = 'Accès réservé aux administrateurs.';
        header('Location: ' . bo_login_path());
        exit;
    }

    bo_auth_apply_login_session($user);
    unset($_SESSION['bo_login_error']);

    header('Location: main.php?page=accueil');
    exit;
} catch (Throwable $e) {
    $_SESSION['bo_login_error'] = 'Erreur technique : ' . $e->getMessage();
    header('Location: ' . bo_login_path());
    exit;
}
