<?php

declare(strict_types=1);

/**
 * Copiez ce fichier en config/mail.php et renseignez votre compte Gmail.
 * Utilisez un « mot de passe d'application » Google (pas votre mot de passe Gmail normal).
 * https://myaccount.google.com/apppasswords
 */
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_user' => 'votre.email@gmail.com',
    'smtp_pass' => 'xxxx xxxx xxxx xxxx',
    'from_email' => 'votre.email@gmail.com',
    'from_name' => 'HappyBite',
];
