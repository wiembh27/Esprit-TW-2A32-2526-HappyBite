<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/sante_session.php';
require_once __DIR__ . '/../config/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: user_health_space.php');
    exit;
}

$uid = sante_require_user_id();
$pdo = Database::getConnection();

$stmt = $pdo->prepare('DELETE FROM profil_sante WHERE id_utilisateur = :u LIMIT 1');
$stmt->execute(['u' => $uid]);

header('Location: user_health_space.php');
exit;
