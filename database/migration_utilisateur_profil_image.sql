-- Photo de profil communauté : chemin relatif type "uploads/users pictures/nomfichier.ext"
-- À exécuter si la colonne n’existe pas encore.

USE happybite;

ALTER TABLE utilisateur
    ADD COLUMN `profil-image` VARCHAR(512) NULL;
