<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Controllers/UserNotificationService.php';
require_once __DIR__ . '/../includes/panier_session.php';
require_once __DIR__ . '/../includes/fo_i18n.php';

panier_ensure_session();
fo_init_i18n_for_request();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userId = $loggedIn ? (int) ($_SESSION['user_id'] ?? 0) : 0;

if ($userId < 1) {
    echo json_encode(['ok' => true, 'unread' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../../config/Database.php';

$pdo = Database::getConnection();
user_notification_ensure_table($pdo);
$user_notification_run_scheduled_checks($pdo, $userId);

$payload = [
    'ok' => true,
    'unread' => user_notification_count_unread($pdo, $userId),
];

if (!empty($_GET['list'])) {
    $items = [];
    foreach (user_notification_list($pdo, $userId, 40) as $row) {
        $items[] = [
            'id' => (int) ($row['id_notification'] ?? 0),
            'titre' => fo_db((string) ($row['titre'] ?? '')),
            'message' => fo_db((string) ($row['message'] ?? '')),
            'lu' => (int) ($row['lu'] ?? 0),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'date_label' => fo_notification_format_date((string) ($row['created_at'] ?? '')),
        ];
    }
    $payload['items'] = $items;
}

echo json_encode($payload, JSON_UNESCAPED_UNICODE);
