-- À exécuter sur la base happybite (après schema.sql)
USE happybite;

-- Livraison : PK id_livraison, livraison_date (date prévue), statut
CREATE TABLE IF NOT EXISTS livraison (
    id_livraison INT AUTO_INCREMENT PRIMARY KEY,
    livraison_date DATE NOT NULL,
    statut VARCHAR(100) NOT NULL DEFAULT 'En préparation'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS commande (
    id_commande INT AUTO_INCREMENT PRIMARY KEY,
    `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10, 2) NOT NULL DEFAULT 0,
    modePaiement VARCHAR(50) NULL,
    reduction DECIMAL(10, 2) NOT NULL DEFAULT 0,
    id_livraison INT NULL,
    CONSTRAINT fk_commande_livraison FOREIGN KEY (id_livraison) REFERENCES livraison (id_livraison)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Utilisateur connecté (suivi par compte / track.php) :
-- Voir migration_commande_id_utilisateur.sql (colonne id_utilisateur NULL + index).

CREATE TABLE IF NOT EXISTS commande_produit (
    id_commande_produit INT AUTO_INCREMENT PRIMARY KEY,
    id_commande INT NOT NULL,
    id_produit INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    prix_unitaire DECIMAL(10, 2) NOT NULL,
    CONSTRAINT fk_cp_commande FOREIGN KEY (id_commande) REFERENCES commande (id_commande)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_cp_produit FOREIGN KEY (id_produit) REFERENCES produit (id_produit)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Si vous aviez l’ancienne table livraison (adresse, date_prevue), exécutez plutôt :
-- ALTER TABLE livraison DROP COLUMN adresse;
-- ALTER TABLE livraison CHANGE date_prevue livraison_date DATE NOT NULL;
