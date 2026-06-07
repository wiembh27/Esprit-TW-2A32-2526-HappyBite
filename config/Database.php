<?php

declare(strict_types=1);

/**
 * PDO singleton. Configure via environment variables or edit defaults for XAMPP.
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $name = getenv('DB_NAME') ?: 'happybite';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '';

            $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        return self::$pdo;
    }
}

/**
 * Backward-compatibility adapter for legacy controllers/views
 * still calling Config::getConnexion().
 */
class Config
{
    public static function getConnexion(): PDO
    {
        return Database::getConnection();
    }
}

require_once __DIR__ . '/openai_key.php';
