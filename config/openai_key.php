<?php

declare(strict_types=1);

/**
 * Charge config/secrets.php une seule fois (clé OpenAI pour XAMPP / Windows sans variable d’environnement).
 */
if (!defined('HB_OPENAI_BOOTSTRAP')) {
    define('HB_OPENAI_BOOTSTRAP', true);
    $secrets = __DIR__ . '/secrets.php';
    if (is_file($secrets)) {
        require_once $secrets;
    }
}

function hb_openai_api_key(): string
{
    $trim = static function ($v): string {
        if (!is_string($v)) {
            return '';
        }
        $t = trim($v);
        return $t;
    };

    /** Clé parfois collée depuis un commentaire JS/TS (//sk-...). */
    $stripCommentPrefix = static function (string $v): string {
        if ($v !== '' && str_starts_with($v, '//')) {
            return trim(substr($v, 2));
        }

        return $v;
    };

    $v = $stripCommentPrefix($trim(getenv('OPENAI_API_KEY') ?: ''));
    if ($v !== '') {
        return $v;
    }
    if (isset($_ENV['OPENAI_API_KEY'])) {
        $v = $stripCommentPrefix($trim((string) $_ENV['OPENAI_API_KEY']));
        if ($v !== '') {
            return $v;
        }
    }
    if (isset($_SERVER['OPENAI_API_KEY'])) {
        $v = $stripCommentPrefix($trim((string) $_SERVER['OPENAI_API_KEY']));
        if ($v !== '') {
            return $v;
        }
    }

    // Fichier texte (une ligne) — pratique sous XAMPP sans toucher au PHP
    $keyFile = __DIR__ . DIRECTORY_SEPARATOR . 'openai.key';
    if (is_readable($keyFile)) {
        $raw = @file_get_contents($keyFile);
        if (is_string($raw) && $raw !== '') {
            foreach (preg_split('/\R/u', $raw) as $line) {
                $line = $stripCommentPrefix($trim($line));
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                return $line;
            }
        }
    }

    if (defined('OPENAI_API_KEY')) {
        $v = $stripCommentPrefix($trim((string) constant('OPENAI_API_KEY')));
        if ($v !== '') {
            return $v;
        }
    }

    return '';
}

function hb_gemini_api_key(): string
{
    $trim = static function ($v): string {
        return is_string($v) ? trim($v) : '';
    };

    $v = $trim(getenv('GEMINI_API_KEY') ?: '');
    if ($v !== '') {
        return $v;
    }
    if (isset($_ENV['GEMINI_API_KEY'])) {
        $v = $trim((string) $_ENV['GEMINI_API_KEY']);
        if ($v !== '') {
            return $v;
        }
    }
    if (isset($_SERVER['GEMINI_API_KEY'])) {
        $v = $trim((string) $_SERVER['GEMINI_API_KEY']);
        if ($v !== '') {
            return $v;
        }
    }
    if (defined('GEMINI_API_KEY')) {
        $v = $trim((string) constant('GEMINI_API_KEY'));
        if ($v !== '' && $v !== 'collez-votre-cle-gemini-ici') {
            return $v;
        }
    }

    return '';
}
