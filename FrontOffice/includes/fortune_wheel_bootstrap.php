<?php

declare(strict_types=1);

require_once __DIR__ . '/panier_session.php';
require_once dirname(__DIR__, 2) . '/Controllers/ChallengeController.php';

/**
 * @return array{
 *   ROUE_COST:int,
 *   pointsSante:int,
 *   canUseWheel:bool,
 *   wheelSegments:list<array<string, mixed>>,
 *   pointsAvantRoue:int
 * }
 */
function hb_fortune_wheel_bootstrap(
    bool $loggedIn,
    string $role,
    int $userId,
    ChallengeController $challengeController
): array {
    panier_ensure_session();

    $ROUE_COST = 100;
    $pointsSante = 0;
    $canUseWheel = false;
    $wheelSegments = [];

    if ($loggedIn && $role === 'client' && $userId > 0) {
        fortune_wheel_consume_last_gain($userId);
        fortune_wheel_apply_db_rewards($userId);
        fortune_wheel_apply_pending();

        $pointsSante = $challengeController->getPointsClient($userId);
        $canUseWheel = $pointsSante >= $ROUE_COST;
        $wheelSegments = $challengeController->getFortuneWheelSegments();

        if ($wheelSegments !== []) {
            $_SESSION['fortune_wheel_segments'] = $wheelSegments;
        }
    }

    return [
        'ROUE_COST' => $ROUE_COST,
        'pointsSante' => $pointsSante,
        'canUseWheel' => $canUseWheel,
        'wheelSegments' => $wheelSegments,
        'pointsAvantRoue' => max(0, $ROUE_COST - $pointsSante),
    ];
}
