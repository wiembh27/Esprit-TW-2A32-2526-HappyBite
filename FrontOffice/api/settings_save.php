<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['ok' => false, 'error' => 'not_logged_in'], JSON_UNESCAPED_UNICODE);
    exit;
}

$uid = (int) ($_SESSION['user_id'] ?? 0);
if ($uid <= 0) {
    echo json_encode(['ok' => false, 'error' => 'invalid_user'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../Controllers/UserSettingsService.php';
require_once __DIR__ . '/../includes/fo_i18n.php';

$lang = strtolower(trim((string) ($_POST['language'] ?? '')));
$mode = strtolower(trim((string) ($_POST['mode'] ?? 'light')));
if ($lang === '') {
    echo json_encode(['ok' => false, 'error' => 'invalid_language'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = Database::getConnection();
if (!user_settings_save($pdo, $uid, $mode, $lang)) {
    echo json_encode(['ok' => false, 'error' => 'save_failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

user_settings_apply_to_session(['mode' => $mode, 'language' => $lang]);

echo json_encode([
    'ok' => true,
    'language' => fo_lang(),
    'mode' => fo_mode(),
], JSON_UNESCAPED_UNICODE);
