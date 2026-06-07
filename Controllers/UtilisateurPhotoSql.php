<?php

declare(strict_types=1);

/**
 * Colonne `profil-image` (chemin type uploads/users pictures/...) et repli sur profile_photo.
 */
function utilisateur_sql_auteur_photo_expr(PDO $pdo): string
{
    static $expr = null;
    if ($expr !== null) {
        return $expr;
    }
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
    );
    $stmt->execute(['t' => 'utilisateur', 'c' => 'profil-image']);
    $hasProfilImage = (int) $stmt->fetchColumn() > 0;
    $stmt->execute(['t' => 'utilisateur', 'c' => 'profile_photo']);
    $hasProfilePhoto = (int) $stmt->fetchColumn() > 0;
    $parts = [];
    if ($hasProfilImage) {
        $parts[] = "NULLIF(TRIM(u.`profil-image`), '')";
    }
    if ($hasProfilePhoto) {
        $parts[] = "NULLIF(TRIM(u.profile_photo), '')";
    }
    $expr = $parts !== []
        ? 'COALESCE(' . implode(', ', $parts) . ') AS auteur_photo'
        : 'CAST(NULL AS CHAR) AS auteur_photo';
    return $expr;
}

/** Colonnes présentes sur utilisateur pour la photo (ordre de priorité lecture). */
function utilisateur_photo_db_columns(PDO $pdo): array
{
    static $cols = null;
    if ($cols !== null) {
        return $cols;
    }
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
    );
    $cols = [];
    foreach (['profil-image', 'profile_photo'] as $c) {
        $stmt->execute(['t' => 'utilisateur', 'c' => $c]);
        if ((int) $stmt->fetchColumn() > 0) {
            $cols[] = $c;
        }
    }
    return $cols;
}

function utilisateur_table_pk_column(PDO $pdo): string
{
    static $col = null;
    if ($col !== null) {
        return $col;
    }
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
    );
    $stmt->execute(['t' => 'utilisateur', 'c' => 'id']);
    if ((int) $stmt->fetchColumn() > 0) {
        $col = 'id';
        return $col;
    }
    $stmt->execute(['t' => 'utilisateur', 'c' => 'id_utilisateur']);
    $col = (int) $stmt->fetchColumn() > 0 ? 'id_utilisateur' : 'id';
    return $col;
}

/** Chemin relatif projet (ex. uploads/users pictures/…) ou URL absolue. */
function utilisateur_fetch_profile_relative_path(PDO $pdo, int $userId): ?string
{
    if ($userId <= 0) {
        return null;
    }
    try {
        $cols = utilisateur_photo_db_columns($pdo);
        if ($cols === []) {
            return null;
        }
        $pk = utilisateur_table_pk_column($pdo);
        $select = [];
        foreach ($cols as $c) {
            $select[] = $c === 'profil-image' ? '`profil-image`' : $c;
        }
        $q = $pdo->prepare('SELECT ' . implode(', ', $select) . " FROM utilisateur WHERE `{$pk}` = :id LIMIT 1");
        $q->bindValue(':id', $userId, PDO::PARAM_INT);
        $q->execute();
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        foreach ($cols as $c) {
            if (!empty($row[$c])) {
                return (string) $row[$c];
            }
        }
        return null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Attribut src pour &lt;img&gt; depuis une page sous FrontOffice/ (racine site = parent de FrontOffice).
 */
function utilisateur_nav_profile_img_src(?string $relPathFromDb): ?string
{
    if ($relPathFromDb === null || trim($relPathFromDb) === '') {
        return null;
    }
    $p = trim($relPathFromDb);
    if (preg_match('#^https?://#i', $p)) {
        return str_replace(' ', '%20', $p);
    }
    return str_replace(' ', '%20', '../' . ltrim($p, '/'));
}

/**
 * Enregistre un fichier uploadé sous uploads/users pictures/ (même logique que l’inscription).
 *
 * @param array<string, mixed> $file entrée $_FILES['profile_photo']
 */
function utilisateur_handle_profile_photo_upload(array $file): ?string
{
    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'users pictures' . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }
    if (empty($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
        return null;
    }
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $mime = (string) ($file['type'] ?? '');
    if (!in_array($mime, $allowed, true)) {
        return null;
    }
    if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return null;
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($ext === '' || !preg_match('/^(jpe?g|png|gif|webp)$/i', $ext)) {
        return null;
    }
    $filename = 'profile_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $filepath = $uploadDir . $filename;
    if (move_uploaded_file((string) $file['tmp_name'], $filepath)) {
        return 'uploads/users pictures/' . $filename;
    }

    return null;
}
