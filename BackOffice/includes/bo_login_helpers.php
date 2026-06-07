<?php

declare(strict_types=1);

/**
 * Vérification mot de passe / colonnes utilisateur (sans dépendre du flux FrontOffice).
 */
function bo_auth_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column'
    );
    $stmt->execute(['table' => $table, 'column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function bo_auth_utilisateur_pk_column(PDO $pdo): string
{
    static $col = null;
    if ($col !== null) {
        return $col;
    }
    if (bo_auth_column_exists($pdo, 'utilisateur', 'id')) {
        $col = 'id';
    } elseif (bo_auth_column_exists($pdo, 'utilisateur', 'id_utilisateur')) {
        $col = 'id_utilisateur';
    } else {
        $col = 'id';
    }

    return $col;
}

function bo_auth_utilisateur_id_from_row(array $row): int
{
    return (int) ($row['id'] ?? $row['id_utilisateur'] ?? 0);
}

function bo_auth_verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/** Écrit uniquement les clés de session BackOffice. */
function bo_auth_apply_login_session(array $user): void
{
    $_SESSION['bo_logged_in'] = true;
    $_SESSION['bo_user_id'] = bo_auth_utilisateur_id_from_row($user);
    $_SESSION['bo_user_prenom'] = (string) ($user['prenom'] ?? '');
    $_SESSION['bo_user_nom'] = (string) ($user['nom'] ?? '');
    $_SESSION['bo_user_email'] = (string) ($user['email'] ?? '');
    $_SESSION['bo_user_role'] = (string) ($user['role'] ?? '');
    $_SESSION['bo_user_statut'] = (string) ($user['statut'] ?? 'actif');
}
