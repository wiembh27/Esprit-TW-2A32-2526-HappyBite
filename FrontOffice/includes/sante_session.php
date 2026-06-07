<?php

declare(strict_types=1);

/**
 * Exige un utilisateur connecté ; renvoie son id (profil_sante.id_utilisateur / session).
 */
function sante_require_user_id(): int
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: auth/login.php');
        exit;
    }
    $uid = (int) ($_SESSION['user_id'] ?? 0);
    if ($uid < 1) {
        header('Location: auth/login.php');
        exit;
    }
    return $uid;
}
