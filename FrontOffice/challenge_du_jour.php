<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/fo_i18n.php';
fo_init_i18n_for_request();

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => fo_t('challenge.err_not_logged'),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: auth/login.php');
    exit;
}

require_once __DIR__ . '/../Controllers/ChallengeController.php';
require_once __DIR__ . '/includes/fortune_wheel_bootstrap.php';

$ctrl = new ChallengeController();

$userId = (int) ($_SESSION['user_id'] ?? 0);
$role = strtolower(trim((string) ($_SESSION['user_role'] ?? 'client')));

$success = '';
$error = '';

function hb_challenge_h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function hb_challenge_img_url(?string $path): string
{
    $path = trim((string) $path);

    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path)) {
        return str_replace(' ', '%20', $path);
    }

    return '../' . ltrim(str_replace(' ', '%20', $path), '/');
}

function hb_challenge_initials(?string $prenom, ?string $nom): string
{
    $p = mb_strtoupper(mb_substr(trim((string) $prenom), 0, 1, 'UTF-8'), 'UTF-8');
    $n = mb_strtoupper(mb_substr(trim((string) $nom), 0, 1, 'UTF-8'), 'UTF-8');
    $out = $p . $n;

    return $out !== '' ? $out : fo_t('challenge.initials_fallback');
}

function challenge_resolve_message(array $res, string $fallbackKey): string
{
    if (!empty($res['message_key'])) {
        return fo_t((string) $res['message_key']);
    }

    if (!empty($res['message'])) {
        return (string) $res['message'];
    }

    return fo_t($fallbackKey);
}

function challenge_localize_ajax_response(array $res): array
{
    if (!empty($res['message_key'])) {
        $res['message'] = fo_t((string) $res['message_key']);
        unset($res['message_key']);
    }

    return $res;
}

function hb_challenge_date_local(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }

    $ts = strtotime($datetime);

    if ($ts === false) {
        return '';
    }

    if (fo_lang() === 'en') {
        return sprintf(fo_t('challenge.date_time_en'), date('m/d/Y', $ts), date('H:i', $ts));
    }

    return sprintf(fo_t('challenge.date_time_fr'), date('d/m/Y', $ts), date('H:i', $ts));
}

function hb_challenge_build_winners_board(
    ChallengeController $ctrl,
    int $challengeId,
    int $userId,
    bool $showOnLoad
): ?array {
    $board = $ctrl->getWinnersLeaderboard(
        $challengeId,
        $userId,
        (string) ($_SESSION['user_prenom'] ?? ''),
        (string) ($_SESSION['user_nom'] ?? '')
    );

    if (!is_array($board)) {
        return null;
    }

    foreach ($board['top5'] as $idx => $row) {
        $board['top5'][$idx]['rank'] = fo_t('challenge.rank_' . ($idx + 1));
    }

    if (!empty($board['user']) && is_array($board['user'])) {
        $userName = trim((string) ($_SESSION['user_nom'] ?? '') . ' ' . (string) ($_SESSION['user_prenom'] ?? ''));
        if ($userName === '') {
            $userName = fo_t('challenge.member_fallback');
        }
        $board['user']['name'] = $userName . ' — ' . fo_t('challenge.winners_preview_keep_going');
    }

    $board['userId'] = $userId;
    $board['emptyLabel'] = fo_t('challenge.winners_empty');
    $board['showOnLoad'] = $showOnLoad;

    return $board;
}

function hb_challenge_ceremony_due(?string $dateSelection): bool
{
    $dateStr = substr(trim((string) $dateSelection), 0, 10);
    if ($dateStr === '') {
        return false;
    }

    try {
        $ceremonyAt = (new DateTimeImmutable($dateStr))
            ->setTime(9, 0, 0)
            ->modify('+1 day');

        return (new DateTimeImmutable()) >= $ceremonyAt;
    } catch (Exception $e) {
        return false;
    }
}

/*
|--------------------------------------------------------------------------
| AJAX handlers
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ajaxAction = (string) ($_POST['action'] ?? '');

    if ($ajaxAction === 'like_participation') {
        header('Content-Type: application/json; charset=utf-8');

        if ($role !== 'client') {
            echo json_encode([
                'success' => false,
                'message' => fo_t('challenge.err_clients_only_like'),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $participationId = (int) ($_POST['participation_id'] ?? 0);
        echo json_encode(
            challenge_localize_ajax_response($ctrl->likerParticipation($participationId, $userId)),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    if ($ajaxAction === 'tourner_roue') {
        header('Content-Type: application/json; charset=utf-8');

        if ($role !== 'client') {
            echo json_encode([
                'success' => false,
                'message' => fo_t('challenge.err_clients_only_wheel'),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(
            $ctrl->tournerRoue($userId),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    if ($ajaxAction === 'draw_challenge_jour') {
        header('Content-Type: application/json; charset=utf-8');

        if ($role !== 'client') {
            echo json_encode([
                'success' => false,
                'message' => fo_t('challenge.err_clients_only_draw'),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $drawn = $ctrl->getChallengeduJour();

        if (!$drawn) {
            echo json_encode([
                'success' => false,
                'message' => fo_t('challenge.draw_no_available'),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'success' => true,
            'challenge' => [
                'id' => (int) ($drawn['id'] ?? 0),
                'titre' => (string) ($drawn['titre'] ?? ''),
                'description' => (string) ($drawn['description'] ?? ''),
                'image' => hb_challenge_img_url($drawn['image'] ?? null),
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| POST classique : participation au challenge
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && (string) ($_POST['action'] ?? '') === 'participer'
    && $role === 'client'
) {
    $challengeId = (int) ($_POST['challenge_id'] ?? 0);
    $description = (string) ($_POST['description'] ?? '');

    $res = $ctrl->soumettreparticipation(
        $userId,
        $challengeId,
        $description,
        $_FILES['photo'] ?? null
    );

    if (!empty($res['success'])) {
        $success = challenge_resolve_message($res, 'challenge.participation_success');
    } else {
        $error = challenge_resolve_message($res, 'challenge.participation_failed');
    }
}

$challengeToastMsg = $success !== '' ? $success : $error;

/*
|--------------------------------------------------------------------------
| Données page
|--------------------------------------------------------------------------
*/

$activeChallenge = $ctrl->getActiveSelectedChallenge();
$challengeSansTirage = $ctrl->getChallengeDuJourSansTirage();
$challenge = $activeChallenge ?? $challengeSansTirage;
$needsDrawToday = ($activeChallenge === null && $challengeSansTirage === null);
/** Ball machine only after the 9:00 ceremony window (winners first), never during an active countdown. */
$forceDrawOverlay = false;
$dejaParticipe = false;
$participations = [];
$points = 0;
$ROUE_COST = 100;
$canUseWheel = false;
$wheelSegments = [];
$pointsAvantRoue = $ROUE_COST;

if ($challenge) {
    $challengeId = (int) ($challenge['id'] ?? 0);

    if ($role === 'client') {
        $dejaParticipe = $ctrl->aDejaParticipe($userId, $challengeId);
    }

    $participations = $ctrl->getParticipationsValidees($challengeId);
}

if ($role === 'client') {
    $fw = hb_fortune_wheel_bootstrap(true, $role, $userId, $ctrl);
    $ROUE_COST = $fw['ROUE_COST'];
    $points = $fw['pointsSante'];
    $canUseWheel = $fw['canUseWheel'];
    $wheelSegments = $fw['wheelSegments'];
    $pointsAvantRoue = $fw['pointsAvantRoue'];
}

$ceremonyChallenge = is_array($activeChallenge) ? $activeChallenge : $ctrl->getLastEndedChallenge();
$ceremonyChallengeId = is_array($ceremonyChallenge) && !empty($ceremonyChallenge['id'])
    ? (int) $ceremonyChallenge['id']
    : 0;
$ceremonyDateSelection = is_array($ceremonyChallenge)
    ? (string) ($ceremonyChallenge['dateSelection'] ?? '')
    : '';
$endedCeremonyDue = hb_challenge_ceremony_due($ceremonyDateSelection);

$winnersPayload = null;
$countdownWinnersPayload = null;
if ($role === 'client' && $ceremonyChallengeId > 0) {
    $winnersPayload = hb_challenge_build_winners_board(
        $ctrl,
        $ceremonyChallengeId,
        $userId,
        $endedCeremonyDue && $needsDrawToday
    );
    $countdownWinnersPayload = hb_challenge_build_winners_board(
        $ctrl,
        $ceremonyChallengeId,
        $userId,
        false
    );
}

$todayLabel = date('d/m/Y');

$challengeEndsAtIso = '';
$challengePeriodEnded = false;
if (is_array($challenge) && $challenge !== []) {
    $sel = trim((string) ($challenge['dateSelection'] ?? ''));
    try {
        $base = $sel !== '' ? new DateTimeImmutable($sel) : new DateTimeImmutable('today');
        $challengeEndAt = $base->setTime(9, 0, 0)->modify('+1 day');
        $challengeEndsAtIso = $challengeEndAt->format(DateTimeInterface::ATOM);
        $challengePeriodEnded = (new DateTimeImmutable()) >= $challengeEndAt;
    } catch (Exception $e) {
        $challengeEndAt = (new DateTimeImmutable('today'))
            ->setTime(9, 0, 0)
            ->modify('+1 day');
        $challengeEndsAtIso = $challengeEndAt->format(DateTimeInterface::ATOM);
        $challengePeriodEnded = (new DateTimeImmutable()) >= $challengeEndAt;
    }
}

if ($needsDrawToday && !$challengePeriodEnded) {
    $endedForPeriod = $ctrl->getLastEndedChallenge();
    if (is_array($endedForPeriod) && !empty($endedForPeriod['dateSelection'])) {
        try {
            $endedSel = trim((string) $endedForPeriod['dateSelection']);
            $endedEndAt = (new DateTimeImmutable($endedSel))
                ->setTime(9, 0, 0)
                ->modify('+1 day');
            $challengePeriodEnded = (new DateTimeImmutable()) >= $endedEndAt;
        } catch (Exception $e) {
            // keep existing value
        }
    }
}

$forceDrawOverlay = ($role === 'client' && $needsDrawToday && $challengePeriodEnded);

?>
<!DOCTYPE html>
<html lang="<?php echo fo_html_lang_attr(); ?>">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/includes/hb_brand_head.php'; hb_brand_render_head(); ?>
    <title><?php echo fo_e('challenge.page_title'); ?> — HappyBite</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style-original-views.css">

    <style>
        :root {
            --green: #2C7E34;
            --green-dark: #256b2d;
            --green-light: #eaf4ef;
            --mint: #2ec4b6;
            --bg: #f4f7f5;
            --text: #1a1a1a;
            --muted: #6b7280;
            --border: #e3ebe6;
            --danger: #dc2626;
            --warning: #f59e0b;
            --shadow: 0 10px 34px rgba(15, 42, 28, 0.09);
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            background: var(--bg);
            font-family: "Poppins", sans-serif;
            color: var(--text);
            display: flex;
            flex-direction: column;
        }

        .challenge-page {
            flex: 1 0 auto;
            max-width: 1180px;
            width: 100%;
            margin: 0 auto;
            padding: 2rem clamp(1rem, 3vw, 2rem) 3rem;
            overflow-x: clip;
            box-sizing: border-box;
        }

        body > footer.site-copyright {
            flex-shrink: 0;
            position: relative;
            z-index: 5;
            background-color: #fff;
            color: var(--green);
            text-align: center;
            padding: 15px 0;
            width: 100%;
            font-family: "Poppins", sans-serif;
            font-weight: 400;
            border-top: 1px solid var(--border);
            margin-top: auto;
        }

        [data-hb-mode="dark"] body > footer.site-copyright {
            background-color: #0f1419;
            border-top-color: #1e2832;
            color: #66bb6a;
        }

        .challenge-hero {
            position: relative;
            overflow: hidden;
            border-radius: 28px;
            background-color: #1a472a;
            background-image:
                linear-gradient(105deg, rgba(15, 42, 28, 0.88) 0%, rgba(15, 42, 28, 0.62) 42%, rgba(15, 42, 28, 0.28) 100%),
                url("images/daily%20challenge.png");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: #fff;
            padding: clamp(2rem, 5vw, 3.2rem);
            box-shadow: none;
            margin-bottom: 1.5rem;
            min-height: clamp(220px, 38vw, 320px);
        }

        .challenge-hero__content {
            position: relative;
            z-index: 1;
            max-width: 720px;
        }

        .hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.22);
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .challenge-hero h1 {
            font-size: clamp(2rem, 5vw, 3.2rem);
            line-height: 1.05;
            margin: 0 0 0.8rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #fff;
        }

        .challenge-hero p {
            font-size: 1rem;
            line-height: 1.75;
            color: rgba(255,255,255,0.9);
            margin: 0;
        }

        .challenge-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 1.2rem;
        }

        .meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .points-strip {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.1rem 1.25rem;
            box-shadow: none;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .points-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .points-card-icon {
            flex-shrink: 0;
            width: 48px;
            height: 48px;
        }

        .points-coin {
            position: relative;
            width: 48px;
            height: 48px;
            filter: none;
        }

        .points-coin__rim {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: conic-gradient(
                from 200deg,
                #6ee680 0deg,
                #2c7e34 70deg,
                #1a5528 140deg,
                #358f42 220deg,
                #5ed86f 300deg,
                #6ee680 360deg
            );
            box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.38);
        }

        .points-coin__face {
            position: absolute;
            inset: 4px;
            border-radius: 50%;
            background: radial-gradient(circle at 38% 32%, #6ed97f 0%, #43b556 38%, #2c7e34 72%, #247a32 100%);
            box-shadow: inset 0 1px 3px rgba(255, 255, 255, 0.22);
        }

        .points-coin__star {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 24px;
            height: 24px;
            transform: translate(-50%, -50%);
            background: #fff;
            clip-path: polygon(
                50% 2%,
                61% 36%,
                96% 36%,
                67% 56%,
                78% 90%,
                50% 68%,
                22% 90%,
                33% 56%,
                4% 36%,
                39% 36%
            );
            filter: none;
        }

        .points-left h3 {
            margin: 0;
            font-size: 1rem;
            color: #166534;
            font-weight: 800;
        }

        .points-left p {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .points-left strong {
            color: var(--green);
            font-size: 1.25rem;
        }

        .btn-wheel {
            border: none;
            border-radius: 999px;
            padding: 12px 20px;
            font-family: inherit;
            font-weight: 800;
            cursor: pointer;
            background: linear-gradient(135deg, #7c3aed, #a21caf);
            color: #fff;
            box-shadow: 0 10px 24px rgba(124, 58, 237, 0.22);
        }

        .btn-wheel:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            box-shadow: none;
        }

        .points-strip-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.65rem;
        }

        .page-grid {
            display: grid;
            grid-template-columns: minmax(0, 0.92fr) minmax(320px, 0.68fr);
            gap: 1.5rem;
            align-items: start;
        }

        @media (max-width: 980px) {
            .page-grid {
                grid-template-columns: 1fr;
            }
        }

        .card-panel {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: clamp(1.25rem, 3vw, 1.6rem);
            box-shadow: none;
            margin-bottom: 1.5rem;
        }

        .card-panel h2 {
            margin: 0 0 1rem;
            font-size: 1.18rem;
            color: var(--green);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
        }

        .challenge-participation-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem 1rem;
            margin: 0 0 1rem;
            width: 100%;
        }

        .challenge-participation-head__title {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1.18rem;
            color: var(--green);
            font-weight: 800;
        }

        .challenge-countdown {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #dc2626;
            font-weight: 700;
            font-size: 0.85rem;
            line-height: 1.2;
        }

        .challenge-countdown__time {
            font-variant-numeric: tabular-nums;
            font-size: 1.08rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            padding: 5px 11px;
            border-radius: 8px;
            background: rgba(220, 38, 38, 0.09);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #b91c1c;
        }

        .challenge-countdown.is-ended .challenge-countdown__time {
            font-size: 0.82rem;
            letter-spacing: 0.02em;
            padding: 5px 10px;
        }

        .challenge-description {
            color: #374151;
            line-height: 1.8;
            margin: 0;
            font-size: 0.96rem;
        }

        .rules-list {
            margin: 1rem 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 10px;
        }

        .rules-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: #374151;
            font-size: 0.92rem;
            line-height: 1.55;
        }

        .rules-list i {
            color: var(--green);
            margin-top: 3px;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-group input[type="file"],
        .form-group textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 0.95rem;
            background: #fff;
            outline: none;
        }

        .form-group textarea {
            min-height: 110px;
            resize: vertical;
            line-height: 1.6;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 4px rgba(44, 126, 52, 0.09);
        }

        .btn-green {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            border-radius: 14px;
            background: var(--green);
            color: #fff;
            padding: 13px 18px;
            font-family: inherit;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.15s ease, filter 0.15s ease;
        }

        .btn-green:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
            color: #fff;
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1.5px solid var(--green);
            border-radius: 14px;
            background: #fff;
            color: var(--green);
            padding: 12px 18px;
            font-family: inherit;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
        }

        .file-hint {
            display: block;
            color: var(--muted);
            font-size: 0.78rem;
            margin-top: 6px;
        }

        .already-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 1rem;
            color: #166534;
            font-weight: 700;
            line-height: 1.5;
        }

        .ranking-list {
            display: grid;
            gap: 1rem;
        }

        .participation-card {
            display: grid;
            grid-template-columns: 110px minmax(0, 1fr);
            gap: 1rem;
            padding: 1rem;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: #fff;
            box-shadow: none;
        }

        @media (max-width: 620px) {
            .participation-card {
                grid-template-columns: 1fr;
            }
        }

        .participation-photo,
        .participation-placeholder {
            width: 110px;
            height: 110px;
            border-radius: 16px;
            object-fit: cover;
            background: #f3f4f6;
        }

        @media (max-width: 620px) {
            .participation-photo,
            .participation-placeholder {
                width: 100%;
                height: 220px;
            }
        }

        .participation-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 1.8rem;
        }

        .participation-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .author {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .avatar-sm,
        .avatar-img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .avatar-sm {
            background: var(--green);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.8rem;
        }

        .avatar-img {
            object-fit: cover;
        }

        .author-name {
            font-weight: 800;
            color: #111827;
            font-size: 0.92rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .author-date {
            color: var(--muted);
            font-size: 0.76rem;
            margin-top: 2px;
        }

        .rank-badge {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 0.82rem;
            flex-shrink: 0;
        }

        .rank-1 {
            background: #ffd700;
            color: #1f2937;
            box-shadow: none;
        }

        .rank-2 {
            background: #d1d5db;
            color: #111827;
        }

        .rank-3 {
            background: #b45309;
            color: #fff;
        }

        .rank-other {
            background: #f3f4f6;
            color: #6b7280;
        }

        .participation-desc {
            color: #374151;
            font-size: 0.9rem;
            line-height: 1.65;
            margin: 0 0 0.8rem;
        }

        .participation-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .like-btn {
            border: 1.5px solid var(--border);
            background: #fff;
            color: #374151;
            border-radius: 999px;
            padding: 8px 13px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 800;
            font-size: 0.82rem;
            transition: all 0.18s ease;
        }

        .like-btn:hover {
            border-color: #ef4444;
            color: #ef4444;
            background: #fff1f2;
        }

        .like-btn.liked {
            border-color: #ef4444;
            color: #ef4444;
            background: #fff1f2;
        }

        .ai-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 5px 10px;
            background: #ecfdf3;
            color: #166534;
            font-size: 0.72rem;
            font-weight: 800;
        }

        .no-challenge,
        .empty-ranking {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--muted);
        }

        .no-challenge i,
        .empty-ranking i {
            font-size: 2.8rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        /* No stray green glows / lines on this page */
        .challenge-page .points-left,
        .challenge-page .points-card-icon,
        .challenge-page .points-coin,
        .challenge-page .points-coin * {
            filter: none !important;
            box-shadow: none !important;
        }

        .challenge-page .points-coin__face {
            box-shadow: inset 0 1px 3px rgba(255, 255, 255, 0.22) !important;
        }

        .challenge-page .points-coin__rim {
            box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.38) !important;
        }

        /* No stray underlines / pseudo-lines (nav icons, legacy nav rules) */
        .main-nav .nav-icon-link,
        .main-nav .nav-profile-trigger,
        .main-nav .nav-cart-link {
            border-bottom: none !important;
        }

        .main-nav .nav-links a::after,
        .main-nav .nav-link::after,
        .main-nav .nav-icon-link::after,
        .challenge-page section::before,
        .challenge-page section::after,
        .challenge-page .card-panel h2::before,
        .challenge-page .card-panel h2::after,
        .challenge-hero::before,
        .challenge-hero::after {
            display: none !important;
            content: none !important;
        }
    </style>
    <?php require __DIR__ . '/includes/fortune_wheel_styles.php'; ?>
</head>

<body>

<?php
$nav_active = 'communaute';
require __DIR__ . '/includes/nav_front.php';
?>

<main class="challenge-page">

    <?php if ($role === 'client'): ?>
        <section class="points-strip">
            <div class="points-left">
                <div class="points-card-icon">
                    <div class="points-coin" aria-hidden="true">
                        <span class="points-coin__rim"></span>
                        <span class="points-coin__face"></span>
                        <span class="points-coin__star"></span>
                    </div>
                </div>
                <div>
                    <h3><?php echo fo_e('challenge.points_title'); ?></h3>
                    <p>
                        <strong id="pointsValue"><?= (int) $points ?></strong> <?php echo fo_e('challenge.points_suffix'); ?>
                        <?php if ($pointsAvantRoue > 0): ?>
                            · <?php echo htmlspecialchars(sprintf(fo_t('challenge.points_before_wheel'), (int) $pointsAvantRoue), ENT_QUOTES, 'UTF-8'); ?>
                        <?php else: ?>
                            · <?php echo fo_e('challenge.points_wheel_unlocked'); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="points-strip-actions">
                <?php if ($canUseWheel): ?>
                    <button class="btn-wheel" type="button" onclick="openRoue()">
                        <?php echo fo_e('challenge.wheel_unlock_btn'); ?>
                    </button>
                <?php else: ?>
                    <button class="btn-wheel" type="button" disabled aria-disabled="true">
                        <?php echo fo_e('challenge.wheel_locked_btn'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($challenge): ?>

        <section class="challenge-hero">
            <div class="challenge-hero__content">
                <div class="hero-kicker">
                    <?php echo htmlspecialchars(sprintf(fo_t('challenge.hero_kicker'), $todayLabel), ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <h1><?= hb_challenge_h((string) ($challenge['titre'] ?? fo_t('challenge.title_fallback'))) ?></h1>

                <p>
                    <?= nl2br(hb_challenge_h((string) ($challenge['description'] ?? ''))) ?>
                </p>

                <div class="challenge-meta">
                    <?php if (!empty($challenge['nutri_prenom']) || !empty($challenge['nutri_nom'])): ?>
                        <span class="meta-pill">
                            <?php echo htmlspecialchars(sprintf(fo_t('challenge.meta_by'), trim((string) ($challenge['nutri_prenom'] ?? '') . ' ' . (string) ($challenge['nutri_nom'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    <?php endif; ?>

                    <span class="meta-pill">
                        <?php echo fo_e('challenge.meta_ai_photo'); ?>
                    </span>

                    <span class="meta-pill">
                        <?php echo fo_e('challenge.meta_top1'); ?>
                    </span>
                </div>
            </div>
        </section>

        <div class="page-grid">
            <section>
                <div class="card-panel">
                    <h2>
                        <i class="fas fa-list-check"></i>
                        <?php echo fo_e('challenge.rules_title'); ?>
                    </h2>

                    <p class="challenge-description">
                        <?php echo fo_e('challenge.rules_intro'); ?>
                    </p>

                    <ul class="rules-list">
                        <li>
                            <i class="fas fa-circle-check"></i>
                            <?php echo fo_e('challenge.rule_1'); ?>
                        </li>
                        <li>
                            <i class="fas fa-circle-check"></i>
                            <?php echo fo_e('challenge.rule_2'); ?>
                        </li>
                        <li>
                            <i class="fas fa-circle-check"></i>
                            <?php echo fo_e('challenge.rule_3'); ?>
                        </li>
                        <li>
                            <i class="fas fa-circle-check"></i>
                            <?php echo fo_e('challenge.rule_4'); ?>
                        </li>
                    </ul>
                </div>

                <div class="card-panel">
                    <h2>
                        <i class="fas fa-ranking-star"></i>
                        <?php echo fo_e('challenge.ranking_title'); ?>
                    </h2>

                    <?php if (empty($participations)): ?>
                        <div class="empty-ranking">
                            <i class="fas fa-seedling"></i>
                            <h3 style="margin:0 0 0.4rem;color:#374151;"><?php echo fo_e('challenge.ranking_empty_title'); ?></h3>
                            <p style="margin:0;"><?php echo fo_e('challenge.ranking_empty_desc'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="ranking-list" id="rankingList">
                            <?php foreach ($participations as $index => $p): ?>
                                <?php
                                $rank = $index + 1;
                                $rankClass = 'rank-other';

                                if ($rank === 1) {
                                    $rankClass = 'rank-1';
                                } elseif ($rank === 2) {
                                    $rankClass = 'rank-2';
                                } elseif ($rank === 3) {
                                    $rankClass = 'rank-3';
                                }

                                $pPhoto = hb_challenge_img_url($p['photo'] ?? '');
                                $clientPhoto = hb_challenge_img_url($p['client_photo'] ?? '');
                                $prenom = (string) ($p['client_prenom'] ?? '');
                                $nom = (string) ($p['client_nom'] ?? '');
                                $displayName = trim($prenom . ' ' . $nom);
                                if ($displayName === '') {
                                    $displayName = fo_t('challenge.member_fallback');
                                }

                                $liked = $role === 'client'
                                    ? $ctrl->aLike((int) ($p['id'] ?? 0), $userId)
                                    : false;
                                ?>

                                <article class="participation-card" data-participation-id="<?= (int) ($p['id'] ?? 0) ?>">
                                    <?php if ($pPhoto !== ''): ?>
                                        <img
                                            src="<?= hb_challenge_h($pPhoto) ?>"
                                            alt="<?php echo fo_e('challenge.participation_alt'); ?>"
                                            class="participation-photo"
                                        >
                                    <?php else: ?>
                                        <div class="participation-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <div class="participation-head">
                                            <div class="author">
                                                <?php if ($clientPhoto !== ''): ?>
                                                    <img src="<?= hb_challenge_h($clientPhoto) ?>" alt="" class="avatar-img">
                                                <?php else: ?>
                                                    <div class="avatar-sm">
                                                        <?= hb_challenge_h(hb_challenge_initials($prenom, $nom)) ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div style="min-width:0;">
                                                    <div class="author-name">
                                                        <?= hb_challenge_h($displayName) ?>
                                                    </div>
                                                    <div class="author-date">
                                                        <?= hb_challenge_h(hb_challenge_date_local((string) ($p['dateParticipation'] ?? ''))) ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="rank-badge <?= hb_challenge_h($rankClass) ?>">
                                                #<?= (int) $rank ?>
                                            </div>
                                        </div>

                                        <?php if (!empty($p['description'])): ?>
                                            <p class="participation-desc">
                                                <?= hb_challenge_h(mb_strimwidth((string) $p['description'], 0, 190, '…', 'UTF-8')) ?>
                                            </p>
                                        <?php endif; ?>

                                        <div class="participation-actions">
                                            <?php if ($role === 'client'): ?>
                                                <button
                                                    type="button"
                                                    class="like-btn <?= $liked ? 'liked' : '' ?>"
                                                    onclick="likeParticipation(<?= (int) ($p['id'] ?? 0) ?>, this)"
                                                >
                                                    <span class="like-count"><?= (int) ($p['nombreLikes'] ?? 0) ?></span>
                                                </button>
                                            <?php else: ?>
                                                <span class="like-btn" style="cursor:default;">
                                                    <span><?= (int) ($p['nombreLikes'] ?? 0) ?></span>
                                                </span>
                                            <?php endif; ?>

                                            <?php if (!empty($p['validation_ai_score'])): ?>
                                                <span class="ai-badge">
                                                    <i class="fas fa-robot"></i>
                                                    <?php echo htmlspecialchars(sprintf(fo_t('challenge.ai_score'), (int) $p['validation_ai_score']), ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="ai-badge">
                                                    <i class="fas fa-robot"></i>
                                                    <?php echo fo_e('challenge.ai_validated'); ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if ((int) ($p['bonus_top1_given'] ?? 0) === 1): ?>
                                                <span class="ai-badge" style="background:#fff7ed;color:#c2410c;">
                                                    <i class="fas fa-trophy"></i>
                                                    <?php echo fo_e('challenge.bonus_top1'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <aside>
                <?php if ($role === 'client'): ?>
                    <div class="card-panel">
                        <h2 class="challenge-participation-head">
                            <span class="challenge-participation-head__title">
                                <i class="fas fa-camera"></i>
                                <?php echo fo_e('challenge.my_participation'); ?>
                            </span>
                            <?php if ($challengeEndsAtIso !== ''): ?>
                                <span
                                    class="challenge-countdown"
                                    id="challengeDayCountdown"
                                    data-ends-at="<?= hb_challenge_h($challengeEndsAtIso) ?>"
                                    data-ended-label="<?= hb_challenge_h(fo_t('challenge.countdown_ended')) ?>"
                                    aria-live="polite"
                                >
                                    <span class="challenge-countdown__label"><?php echo fo_e('challenge.countdown_label'); ?></span>
                                    <span class="challenge-countdown__time">--:--:--</span>
                                </span>
                            <?php endif; ?>
                        </h2>

                        <?php if ($dejaParticipe): ?>
                            <div class="already-box">
                                <i class="fas fa-check-circle"></i>
                                <?php echo fo_e('challenge.already_participated'); ?>
                            </div>
                        <?php else: ?>
                            <form method="post" enctype="multipart/form-data" id="challengeForm">
                                <input type="hidden" name="action" value="participer">
                                <input type="hidden" name="challenge_id" value="<?= (int) ($challenge['id'] ?? 0) ?>">

                                <div class="form-group">
                                    <label for="photo"><?php echo fo_e('challenge.photo_label'); ?></label>
                                    <input
                                        type="file"
                                        id="photo"
                                        name="photo"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        required
                                    >
                                    <span class="file-hint">
                                        <?php echo fo_e('challenge.photo_hint'); ?>
                                    </span>
                                </div>

                                <div class="form-group">
                                    <label for="description"><?php echo fo_e('challenge.description_label'); ?></label>
                                    <textarea
                                        id="description"
                                        name="description"
                                        placeholder="<?php echo fo_e('challenge.description_ph'); ?>"
                                        required
                                    ></textarea>
                                    <span class="file-hint">
                                        <?php echo fo_e('challenge.description_hint'); ?>
                                    </span>
                                </div>

                                <button type="submit" class="btn-green" id="submitChallengeBtn">
                                    <?php echo fo_e('challenge.submit_btn'); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php elseif ($role === 'nutritionniste'): ?>
                    <div class="card-panel">
                        <h2>
                            <i class="fas fa-user-doctor"></i>
                            <?php echo fo_e('challenge.nutri_aside_title'); ?>
                        </h2>

                        <p class="challenge-description">
                            <?php echo fo_e('challenge.nutri_aside_desc'); ?>
                        </p>

                        <div style="margin-top:1rem;">
                            <a href="nutritionniste_dashboard.php" class="btn-green">
                                <?php echo fo_e('challenge.nutri_aside_btn'); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card-panel">
                    <h2>
                        <i class="fas fa-lightbulb"></i>
                        <?php echo fo_e('challenge.tip_title'); ?>
                    </h2>

                    <p class="challenge-description">
                        <?php echo fo_e('challenge.tip_desc'); ?>
                    </p>
                </div>

                <div class="card-panel">
                    <h2>
                        <i class="fas fa-users"></i>
                        <?php echo fo_e('nav.community'); ?>
                    </h2>

                    <p class="challenge-description">
                        <?php echo fo_e('challenge.community_desc'); ?>
                    </p>

                    <div style="margin-top:1rem;">
                        <a href="Communaute.php" class="btn-outline">
                            <?php echo fo_e('challenge.community_btn'); ?>
                        </a>
                    </div>
                </div>
            </aside>
        </div>

    <?php else: ?>

        <div class="card-panel">
            <div class="no-challenge">
                <i class="fas fa-calendar-times"></i>
                <h2 style="justify-content:center;"><?php echo fo_e('challenge.no_challenge_title'); ?></h2>
                <p>
                    <?php echo fo_e('challenge.no_challenge_desc'); ?>
                </p>

                <div style="margin-top:1.2rem;">
                    <a href="Communaute.php" class="btn-green">
                        <?php echo fo_e('challenge.no_challenge_btn'); ?>
                    </a>
                </div>
            </div>
        </div>

    <?php endif; ?>

</main>

<footer class="site-copyright">
    <?php echo fo_e('footer.copyright'); ?>
</footer>

<?php if ($role === 'client'): ?>
    <?php require __DIR__ . '/includes/fortune_wheel_modal.php'; ?>
<?php endif; ?>

<?php if ($role === 'client'): ?>
    <?php require __DIR__ . '/includes/challenge_winners_overlay.php'; ?>
    <?php require __DIR__ . '/includes/challenge_draw_overlay.php'; ?>
<?php endif; ?>

<?php if ($role === 'client'): ?>
<script>
window.HB_CHALLENGE_DAY_FLOW = <?= json_encode([
    'draw' => [
        'enabled' => true,
        'userId' => $userId,
        'drawUrl' => 'challenge_du_jour.php',
        'forceDraw' => $forceDrawOverlay,
        'revealKicker' => fo_t('challenge.reveal_kicker'),
        'errorGeneric' => fo_t('challenge.draw_no_available'),
        'errorNetwork' => fo_t('challenge.draw_network_error'),
        'errorTitle' => fo_t('common.error'),
    ],
    'winners' => $winnersPayload,
    'countdownWinners' => $countdownWinnersPayload,
    'countdownEndsAt' => $challengeEndsAtIso !== '' ? $challengeEndsAtIso : null,
    'challengePeriodEnded' => $challengePeriodEnded,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="js/challenge-day-flow.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.hbInitChallengeDayFlow === 'function') {
        window.hbInitChallengeDayFlow(window.HB_CHALLENGE_DAY_FLOW);
    }
});
</script>
<?php endif; ?>

<?php if ($challenge && $challengeEndsAtIso !== ''): ?>
<script>
(function () {
    function pad2(n) {
        return String(n).padStart(2, '0');
    }

    function initChallengeDayCountdown() {
        var root = document.getElementById('challengeDayCountdown');
        if (!root) {
            return;
        }

        var endsAt = root.getAttribute('data-ends-at');
        var endedLabel = root.getAttribute('data-ended-label') || '00:00:00';
        var timeEl = root.querySelector('.challenge-countdown__time');
        if (!endsAt || !timeEl) {
            return;
        }

        var endMs = new Date(endsAt).getTime();
        if (Number.isNaN(endMs)) {
            return;
        }

        var countdownWasRunning = false;

        function tick() {
            var diff = endMs - Date.now();
            if (diff <= 0) {
                root.classList.add('is-ended');
                timeEl.textContent = endedLabel;
                if (countdownWasRunning && typeof window.hbChallengeOnCountdownEnded === 'function') {
                    window.hbChallengeOnCountdownEnded();
                }
                return;
            }

            countdownWasRunning = true;

            var totalSec = Math.floor(diff / 1000);
            var hours = Math.floor(totalSec / 3600);
            var minutes = Math.floor((totalSec % 3600) / 60);
            var seconds = totalSec % 60;
            timeEl.textContent = pad2(hours) + ':' + pad2(minutes) + ':' + pad2(seconds);
        }

        tick();
        window.setInterval(tick, 1000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChallengeDayCountdown);
    } else {
        initChallengeDayCountdown();
    }
})();
</script>
<?php endif; ?>

<script>
window.HB_CHALLENGE_I18N = <?= json_encode([
    'likeError' => fo_t('challenge.like_error'),
    'likeNetworkError' => fo_t('challenge.like_network_error'),
    'validating' => fo_t('challenge.validating'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const challengeI18n = window.HB_CHALLENGE_I18N || {};

function likeParticipation(participationId, button) {
    if (!participationId || !button) {
        return;
    }

    button.disabled = true;

    const formData = new FormData();
    formData.append('action', 'like_participation');
    formData.append('participation_id', participationId);

    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (!data || !data.success) {
                var likeErr = (data && data.message) ? data.message : (challengeI18n.likeError || '');
                if (typeof window.hbShowActionToast === 'function') {
                    window.hbShowActionToast(likeErr, 4000);
                } else {
                    alert(likeErr);
                }
                return;
            }

            const count = button.querySelector('.like-count');

            if (count && typeof data.likes !== 'undefined') {
                count.textContent = data.likes;
            }

            if (data.action === 'like') {
                button.classList.add('liked');
            } else if (data.action === 'unlike') {
                button.classList.remove('liked');
            }

            /*
             * Pour garder le classement parfaitement à jour après un like,
             * on recharge doucement la page après un court délai.
             */
            setTimeout(function () {
                window.location.reload();
            }, 650);
        })
        .catch(function () {
            var netErr = challengeI18n.likeNetworkError || '';
            if (typeof window.hbShowActionToast === 'function') {
                window.hbShowActionToast(netErr, 4000);
            } else {
                alert(netErr);
            }
        })
        .finally(function () {
            button.disabled = false;
        });
}

const challengeForm = document.getElementById('challengeForm');
const submitChallengeBtn = document.getElementById('submitChallengeBtn');

if (challengeForm && submitChallengeBtn) {
    challengeForm.addEventListener('submit', function () {
        submitChallengeBtn.disabled = true;
        submitChallengeBtn.textContent = challengeI18n.validating || '';
    });
}

</script>

<?php if ($challengeToastMsg !== ''): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.hbShowActionToast === 'function') {
        window.hbShowActionToast(<?php echo json_encode($challengeToastMsg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, 5000);
    }
});
</script>
<?php endif; ?>

<?php if ($role === 'client'): ?>
<?php
$fortuneWheelBtnSelector = '.btn-wheel';
require __DIR__ . '/includes/fortune_wheel_script.php';
?>
<?php endif; ?>

</body>
</html>