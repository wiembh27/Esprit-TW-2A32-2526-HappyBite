<?php

declare(strict_types=1);

/**
 * Ensures suivi_journalier + profil_sante columns exist (idempotent, MySQL/MariaDB).
 */
function fo_sante_db_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $statements = [
        "ALTER TABLE suivi_journalier ADD COLUMN IF NOT EXISTS date_jour DATE NULL AFTER id_profil_sante",
        "ALTER TABLE suivi_journalier ADD COLUMN IF NOT EXISTS sport_type VARCHAR(50) NOT NULL DEFAULT 'aucune' AFTER nbr_activites_sport",
        "ALTER TABLE suivi_journalier ADD COLUMN IF NOT EXISTS sport_duree_minutes INT NOT NULL DEFAULT 0 AFTER sport_type",
        "ALTER TABLE suivi_journalier ADD COLUMN IF NOT EXISTS sport_intensite VARCHAR(20) NOT NULL DEFAULT 'aucune' AFTER sport_duree_minutes",
        "ALTER TABLE suivi_journalier ADD COLUMN IF NOT EXISTS sport_commentaire TEXT NULL AFTER sport_intensite",
        "ALTER TABLE suivi_journalier ADD COLUMN IF NOT EXISTS analyse_resultat VARCHAR(30) NULL AFTER sport_commentaire",
        "ALTER TABLE suivi_journalier ADD COLUMN IF NOT EXISTS points_resultat INT NOT NULL DEFAULT 0 AFTER analyse_resultat",
        "ALTER TABLE suivi_journalier ADD COLUMN IF NOT EXISTS analyse_commentaire TEXT NULL AFTER points_resultat",
        "ALTER TABLE suivi_journalier ADD COLUMN IF NOT EXISTS analysed_at DATETIME NULL AFTER analyse_commentaire",
        'ALTER TABLE profil_sante ADD COLUMN IF NOT EXISTS points INT NOT NULL DEFAULT 0',
    ];

    foreach ($statements as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            // Ignore if syntax unsupported; manual migration may be required.
        }
    }
}
