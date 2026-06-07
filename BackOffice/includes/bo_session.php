<?php

declare(strict_types=1);

/**
 * Session dédiée au BackOffice (cookie séparé du FrontOffice).
 */
const BO_SESSION_NAME = 'HAPPYBITE_BO';

function bo_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (session_name() !== BO_SESSION_NAME) {
            session_write_close();
        } else {
            return;
        }
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_name(BO_SESSION_NAME);
        session_start();
    }
}

/** URL relative depuis la racine BackOffice (login.php, logout.php, …). */
function bo_login_path(): string
{
    return 'login.php';
}
