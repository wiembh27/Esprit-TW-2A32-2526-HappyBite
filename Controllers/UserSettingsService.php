<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

/** @return array<int, string> */
function user_settings_supported_languages(): array
{
    return ['fr' => 'Français', 'en' => 'English'];
}

/** @return array<int, string> */
function user_settings_supported_modes(): array
{
    return ['light' => 'light', 'dark' => 'dark'];
}

function user_settings_table_exists(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'settings'"
    );
    $cache = (int) $stmt->fetchColumn() > 0;

    return $cache;
}

/**
 * @return array{mode: string, language: string}|null
 */
function user_settings_get(PDO $pdo, int $userId): ?array
{
    if ($userId <= 0 || !user_settings_table_exists($pdo)) {
        return null;
    }
    $stmt = $pdo->prepare(
        'SELECT mode, language FROM settings WHERE id_utilisateur = :uid LIMIT 1'
    );
    $stmt->execute(['uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    $lang = strtolower(trim((string) ($row['language'] ?? '')));
    $mode = strtolower(trim((string) ($row['mode'] ?? '')));
    if (!isset(user_settings_supported_languages()[$lang])) {
        $lang = 'fr';
    }
    if (!isset(user_settings_supported_modes()[$mode])) {
        $mode = 'light';
    }

    return ['mode' => $mode, 'language' => $lang];
}

function user_settings_save(PDO $pdo, int $userId, string $mode, string $language): bool
{
    if ($userId <= 0 || !user_settings_table_exists($pdo)) {
        return false;
    }
    $lang = strtolower(trim($language));
    $mode = strtolower(trim($mode));
    if (!isset(user_settings_supported_languages()[$lang])) {
        $lang = 'fr';
    }
    if (!isset(user_settings_supported_modes()[$mode])) {
        $mode = 'light';
    }

    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare('DELETE FROM settings WHERE id_utilisateur = :uid');
        $del->execute(['uid' => $userId]);
        $ins = $pdo->prepare(
            'INSERT INTO settings (id_utilisateur, mode, language) VALUES (:uid, :mode, :lang)'
        );
        $ins->execute(['uid' => $userId, 'mode' => $mode, 'lang' => $lang]);
        $pdo->commit();

        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return false;
    }
}

function user_settings_apply_to_session(?array $row): void
{
    $langs = user_settings_supported_languages();
    $modes = user_settings_supported_modes();
    if ($row === null) {
        $_SESSION['fo_lang'] = 'fr';
        $_SESSION['fo_mode'] = 'light';
        return;
    }
    $lang = strtolower((string) ($row['language'] ?? 'fr'));
    $mode = strtolower((string) ($row['mode'] ?? 'light'));
    $_SESSION['fo_lang'] = isset($langs[$lang]) ? $lang : 'fr';
    $_SESSION['fo_mode'] = isset($modes[$mode]) ? $mode : 'light';
}

function user_settings_load_for_user(PDO $pdo, int $userId): void
{
    user_settings_apply_to_session(user_settings_get($pdo, $userId));
}
