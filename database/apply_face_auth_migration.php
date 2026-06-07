<?php

/**
 * Ajoute utilisateur.face_auth_image si elle est absente (Face ID).
 * Exécuter une fois : php database/apply_face_auth_migration.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getConnection();

$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
);
$stmt->execute(['t' => 'utilisateur', 'c' => 'face_auth_image']);
if ((int) $stmt->fetchColumn() > 0) {
    echo "OK : colonne face_auth_image existe déjà.\n";
    exit(0);
}

$pdo->exec('ALTER TABLE utilisateur ADD COLUMN face_auth_image VARCHAR(255) NULL');
echo "OK : colonne face_auth_image ajoutée.\n";
exit(0);
