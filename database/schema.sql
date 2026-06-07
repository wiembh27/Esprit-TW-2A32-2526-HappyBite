-- Base MySQL pour HappyBite / SmartBite (XAMPP : importer via phpMyAdmin ou mysql CLI)
-- Produit : PK id_produit, nom, prix, image, allergene, benefices, calories, date_ajout (date),
--   id_utilisateur (FK), id_categorie (FK vers categorie).
-- Commande / livraison / lignes : voir aussi schema_commande_livraison.sql
CREATE DATABASE IF NOT EXISTS happybite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE happybite;

CREATE TABLE IF NOT EXISTS categorie (
    id_categorie INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    description TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS produit (
    id_produit INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    prix DECIMAL(10, 2) NOT NULL DEFAULT 0,
    image VARCHAR(255) NOT NULL DEFAULT '',
    allergene TEXT NULL,
    benefices TEXT NULL,
    calories INT NULL,
    date_ajout DATE NULL,
    id_utilisateur INT NOT NULL DEFAULT 1,
    id_categorie INT NOT NULL,
    CONSTRAINT fk_produit_categorie FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recette (
    id_recette INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    calories INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recette_produit (
    id_recette INT NOT NULL,
    id_produit INT NOT NULL,
    PRIMARY KEY (id_recette, id_produit),
    CONSTRAINT fk_rp_recette FOREIGN KEY (id_recette) REFERENCES recette (id_recette)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rp_produit FOREIGN KEY (id_produit) REFERENCES produit (id_produit)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
