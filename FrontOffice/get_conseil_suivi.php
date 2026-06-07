<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/fo_i18n.php';
fo_init_i18n_for_request();

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => fo_t('health.conseil.auth_required')], JSON_UNESCAPED_UNICODE);
    exit;
}

$idSuivi = (int) ($_GET['id'] ?? 0);

if ($idSuivi < 1) {
    http_response_code(400);
    echo json_encode(['error' => fo_t('health.conseil.invalid_id')], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId < 1) {
    http_response_code(401);
    echo json_encode(['error' => fo_t('health.conseil.session_invalid')], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../Controllers/SanteGamificationService.php';

$pdo = Database::getConnection();
$locale = fo_lang();

$chk = $pdo->prepare(
    'SELECT
        sj.id,
        sj.id_profil_sante,
        sj.date_jour,
        sj.calories,
        sj.sommeil_heures,
        sj.nbr_pas,
        sj.hydratation_litre,
        sj.sport_type,
        sj.sport_duree_minutes,
        sj.sport_intensite,
        sj.analyse_resultat,
        sj.points_resultat,
        sj.analyse_commentaire,
        sj.analysed_at,
        ps.objectif,
        ps.allergenes,
        ps.carences,
        ps.maladies
     FROM suivi_journalier sj
     INNER JOIN profil_sante ps ON ps.id = sj.id_profil_sante
     WHERE sj.id = :sid
     AND ps.id_utilisateur = :uid
     LIMIT 1'
);

$chk->execute([
    'sid' => $idSuivi,
    'uid' => $userId,
]);

$row = $chk->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(403);
    echo json_encode(['error' => fo_t('health.conseil.access_denied')], JSON_UNESCAPED_UNICODE);
    exit;
}

$analyseCommentaire = trim((string) ($row['analyse_commentaire'] ?? ''));
$analyseResultat = trim((string) ($row['analyse_resultat'] ?? ''));
$analysedAt = trim((string) ($row['analysed_at'] ?? ''));

if ($analyseCommentaire === '' || $analyseResultat === '' || $analysedAt === '') {
    $result = SanteGamificationService::analyserEtSauvegarder($pdo, $idSuivi);

    if (empty($result['success'])) {
        http_response_code(500);
        echo json_encode([
            'error' => fo_t('health.conseil.generate_error'),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $chk->execute(['sid' => $idSuivi, 'uid' => $userId]);
    $row = $chk->fetch(PDO::FETCH_ASSOC) ?: $row;
}

$profil = [
    'objectif' => $row['objectif'] ?? '',
    'allergenes' => $row['allergenes'] ?? [],
    'carences' => $row['carences'] ?? [],
    'maladies' => $row['maladies'] ?? [],
];

$suivi = [
    'calories' => $row['calories'] ?? null,
    'sommeil_heures' => $row['sommeil_heures'] ?? null,
    'nbr_pas' => $row['nbr_pas'] ?? null,
    'hydratation_litre' => $row['hydratation_litre'] ?? '',
    'sport_type' => $row['sport_type'] ?? 'aucune',
    'sport_duree_minutes' => $row['sport_duree_minutes'] ?? 0,
    'sport_intensite' => $row['sport_intensite'] ?? 'aucune',
];

$live = SanteGamificationService::analyser($profil, $suivi, $locale);
$commentaire = trim((string) ($live['commentaire'] ?? ''));

if ($commentaire === '') {
    $commentaire = fo_t('health.conseil.none');
}

$dateJour = (string) ($row['date_jour'] ?? '');
$dateLabel = $dateJour !== ''
    ? date('d / m / Y', strtotime($dateJour) ?: time())
    : '—';

$points = (int) ($row['points_resultat'] ?? 0);
$resultat = (string) ($row['analyse_resultat'] ?? '');

$commentaireHtml = nl2br(htmlspecialchars($commentaire, ENT_QUOTES, 'UTF-8'));

if ($points > 0) {
    $badge = sprintf(
        '<div class="conseil-badge conseil-badge--good">%s</div>',
        htmlspecialchars(sprintf(fo_t('health.conseil.badge_good'), $points), ENT_QUOTES, 'UTF-8')
    );
} elseif ($points < 0) {
    $badge = sprintf(
        '<div class="conseil-badge conseil-badge--warn">%s</div>',
        htmlspecialchars(sprintf(fo_t('health.conseil.badge_improve'), $points), ENT_QUOTES, 'UTF-8')
    );
} else {
    $badge = '<div class="conseil-badge conseil-badge--neutral">' . fo_e('health.conseil.badge_neutral') . '</div>';
}

$html = sprintf(
    '<div class="conseil-box"><div class="conseil-date">%s</div>%s<div>%s</div></div>',
    htmlspecialchars(sprintf(fo_t('health.conseil.tracking_date'), $dateLabel), ENT_QUOTES, 'UTF-8'),
    $badge,
    $commentaireHtml
);

echo json_encode([
    'success' => true,
    'id_suivi' => $idSuivi,
    'date_jour' => $dateJour,
    'points' => $points,
    'resultat' => $resultat,
    'conseil_ai' => $html,
], JSON_UNESCAPED_UNICODE);

exit;
