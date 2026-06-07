<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bo_session.php';

bo_session_start();

$params = session_get_cookie_params();
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    setcookie(
        BO_SESSION_NAME,
        '',
        time() - 42000,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        !empty($params['secure']),
        !empty($params['httponly'])
    );
}

session_destroy();

header('Location: login.php');
exit;
