<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/sante_session.php';
require_once __DIR__ . '/includes/fo_i18n.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../Controllers/SanteGamificationService.php';

fo_init_i18n_for_request();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: user_health_space.php');
    exit;
}

$uid = sante_require_user_id();
$id = (int) ($_POST['id'] ?? 0);
if ($id < 1) {
    header('Location: user_health_space.php');
    exit;
}

$pdo = Database::getConnection();
$chk = $pdo->prepare(
    'SELECT sj.id, sj.id_profil_sante
     FROM suivi_journalier sj
     INNER JOIN profil_sante ps ON ps.id = sj.id_profil_sante
     WHERE sj.id = :sid AND ps.id_utilisateur = :uid'
);
$chk->execute(['sid' => $id, 'uid' => $uid]);
$suiviRow = $chk->fetch(PDO::FETCH_ASSOC);
if (!$suiviRow) {
    http_response_code(403);
    exit('Accès refusé.');
}

$del = $pdo->prepare('DELETE FROM suivi_journalier WHERE id = :id LIMIT 1');
$del->execute(['id' => $id]);

$idProfil = (int) ($suiviRow['id_profil_sante'] ?? 0);
if ($idProfil > 0) {
    SanteGamificationService::recalculerPointsProfil($pdo, $idProfil);
}

header('Location: user_health_space.php?notice=suivi_deleted');
exit;
