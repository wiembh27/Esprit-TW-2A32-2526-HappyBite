-- Horodatage de création pour le suivi automatique (2 j préparation + trajet calculé sur la carte)
USE happybite;

ALTER TABLE livraison
    ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER statut;
