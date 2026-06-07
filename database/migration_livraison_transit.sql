-- Durée de trajet calculée sur la carte (OSRM) → heure d'arrivée réelle
USE happybite;

ALTER TABLE livraison
    ADD COLUMN IF NOT EXISTS transit_seconds INT NULL AFTER created_at,
    ADD COLUMN IF NOT EXISTS arrival_at DATETIME NULL AFTER transit_seconds;
