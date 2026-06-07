-- Visage enregistré pour connexion Face ID (chemin fichier JPEG)
USE happybite;

ALTER TABLE utilisateur
ADD COLUMN face_auth_image VARCHAR(255) NULL;
