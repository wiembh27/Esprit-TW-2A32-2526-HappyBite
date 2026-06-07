-- Lie les commandes Front Office à l’utilisateur connecté (suivi / track par compte).
-- Exécuter une fois sur la base happybite.

USE happybite;

ALTER TABLE commande
    ADD COLUMN id_utilisateur INT NULL AFTER id_livraison;

CREATE INDEX idx_commande_id_utilisateur ON commande (id_utilisateur);
