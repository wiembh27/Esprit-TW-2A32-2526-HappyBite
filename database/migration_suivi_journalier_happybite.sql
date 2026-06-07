-- ============================================================
-- HappyBite - Migration amélioration suivi journalier
-- Objectif :
-- 1. Autoriser un suivi pour aujourd'hui ou une date passée
-- 2. Ajouter une vraie séance sportive
-- 3. Préparer l'analyse IA automatique
-- 4. Préparer les points automatiques +10 / 0 / -10
-- 5. Ne rien supprimer pour ne pas casser le projet existant
-- ============================================================

ALTER TABLE suivi_journalier
ADD COLUMN IF NOT EXISTS date_jour DATE NULL AFTER id_profil_sante;

-- Si d'anciens suivis n'ont pas de date_jour,
-- on essaie de les remplir avec created_at si elle existe.
-- Si ton projet n'a pas created_at, cette requête peut être ignorée.
UPDATE suivi_journalier
SET date_jour = DATE(created_at)
WHERE date_jour IS NULL
AND created_at IS NOT NULL;

-- Si certains suivis n'ont toujours pas de date,
-- on met la date du jour pour éviter les valeurs NULL.
UPDATE suivi_journalier
SET date_jour = CURDATE()
WHERE date_jour IS NULL;

ALTER TABLE suivi_journalier
MODIFY COLUMN date_jour DATE NOT NULL;

-- Ancienne colonne à garder :
-- nbr_activites_sport ne doit PAS être supprimée.
-- Elle restera là pour compatibilité avec les anciens fichiers.

ALTER TABLE suivi_journalier
ADD COLUMN IF NOT EXISTS sport_type VARCHAR(50) NOT NULL DEFAULT 'aucune' AFTER nbr_activites_sport;

ALTER TABLE suivi_journalier
ADD COLUMN IF NOT EXISTS sport_duree_minutes INT NOT NULL DEFAULT 0 AFTER sport_type;

ALTER TABLE suivi_journalier
ADD COLUMN IF NOT EXISTS sport_intensite VARCHAR(20) NOT NULL DEFAULT 'aucune' AFTER sport_duree_minutes;

ALTER TABLE suivi_journalier
ADD COLUMN IF NOT EXISTS sport_commentaire TEXT NULL AFTER sport_intensite;

ALTER TABLE suivi_journalier
ADD COLUMN IF NOT EXISTS analyse_resultat VARCHAR(30) NULL AFTER sport_commentaire;

ALTER TABLE suivi_journalier
ADD COLUMN IF NOT EXISTS points_resultat INT NOT NULL DEFAULT 0 AFTER analyse_resultat;

ALTER TABLE suivi_journalier
ADD COLUMN IF NOT EXISTS analyse_commentaire TEXT NULL AFTER points_resultat;

ALTER TABLE suivi_journalier
ADD COLUMN IF NOT EXISTS analysed_at DATETIME NULL AFTER analyse_commentaire;

-- Empêcher les doublons :
-- un même profil santé ne peut avoir qu'un seul suivi par date.
-- Important :
-- si cette commande échoue, c'est qu'il existe déjà des doublons.
-- Dans ce cas, il faudra les corriger avant d'ajouter la contrainte.
ALTER TABLE suivi_journalier
ADD UNIQUE KEY unique_suivi_profil_date (id_profil_sante, date_jour);

ALTER TABLE profil_sante
ADD COLUMN IF NOT EXISTS points INT NOT NULL DEFAULT 0;
