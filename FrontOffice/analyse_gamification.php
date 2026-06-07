<?php
declare(strict_types=1);
require_once __DIR__ . '/../Controllers/ControllerGamification.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    empty($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true ||
    empty($_SESSION['user_id'])
) {
    header('Location: user_health_space.php?notice=ia_error');
    exit;
}

$idProfilSante = isset($_GET['id_profil_sante'])
    ? (int) $_GET['id_profil_sante']
    : 0;

$idUtilisateur = (int) $_SESSION['user_id'];

if ($idProfilSante < 1) {
    header('Location: user_health_space.php?notice=ia_error');
    exit;
}

try {
    $controller = new ControllerGamification();

    $result = $controller->analyserSuiviDuJour(
        $idProfilSante,
        $idUtilisateur
    );

    if (empty($result['success'])) {
        $code = $result['code'] ?? '';

        if ($code === 'already_done') {
            header('Location: user_health_space.php?notice=ia_done');
            exit;
        }

        if ($code === 'suivi_not_found') {
            header('Location: user_health_space.php?notice=ia_no_suivi');
            exit;
        }

        if ($code === 'profile_not_found') {
            header('Location: user_health_space.php?notice=ia_profile');
            exit;
        }

        if ($code === 'api_key_missing') {
            header('Location: user_health_space.php?notice=ia_api_key');
            exit;
        }

        if ($code === 'ai_error') {
            header('Location: user_health_space.php?notice=ia_ai_fail');
            exit;
        }

        header('Location: user_health_space.php?notice=ia_error');
        exit;
    }

    $points = (int) ($result['points'] ?? 0);

    if ($points > 0) {
        header('Location: user_health_space.php?notice=ia_plus');
        exit;
    }

    header('Location: user_health_space.php?notice=ia_minus');
    exit;

} catch (Throwable $e) {
    header('Location: user_health_space.php?notice=ia_error');
    exit;
}