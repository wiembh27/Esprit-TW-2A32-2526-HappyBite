<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

try {
    if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Vous devez être connecté pour utiliser la machine à cadeaux.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $role = strtolower(trim((string) ($_SESSION['user_role'] ?? '')));

    if ($role !== 'client') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Seuls les clients peuvent utiliser la machine à cadeaux.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($userId < 1) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Session utilisateur invalide.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Méthode non autorisée. Le tirage doit être lancé en POST.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once __DIR__ . '/../Controllers/ChallengeController.php';

    $controller = new ChallengeController();

    if (!method_exists($controller, 'tournerRoue')) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'La méthode tournerRoue() manque dans ChallengeController.php.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = $controller->tournerRoue($userId);

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erreur PHP : ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}