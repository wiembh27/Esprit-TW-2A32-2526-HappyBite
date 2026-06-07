-- Profil santé, suivi journalier et frigo virtuel
-- À exécuter après schema_utilisateur_auth.sql et schema.sql

USE happybite;

CREATE TABLE IF NOT EXISTS profil_sante (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    taille DECIMAL(5, 2) NULL,
    poids_actuel DECIMAL(5, 2) NULL,
    objectif VARCHAR(255) NULL,
    allergenes TEXT NULL,
    carences TEXT NULL,
    maladies TEXT NULL,
    points INT NOT NULL DEFAULT 0,
    date_mise_a_jour TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_profil_utilisateur (id_utilisateur),
    CONSTRAINT fk_profil_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suivi_journalier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_profil_sante INT NOT NULL,
    date_jour DATE NOT NULL,
    poids DECIMAL(5, 2) NULL,
    calories INT NULL,
    sommeil_heures DECIMAL(4, 2) NULL,
    nbr_pas INT NULL,
    nbr_activites_sport INT NOT NULL DEFAULT 0,
    sport_type VARCHAR(50) NOT NULL DEFAULT 'aucune',
    sport_duree_minutes INT NOT NULL DEFAULT 0,
    sport_intensite VARCHAR(20) NOT NULL DEFAULT 'aucune',
    sport_commentaire TEXT NULL,
    hydratation_litre DECIMAL(4, 2) NULL,
    analyse_resultat VARCHAR(30) NULL,
    points_resultat INT NOT NULL DEFAULT 0,
    analyse_commentaire TEXT NULL,
    analysed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_suivi_profil_date (id_profil_sante, date_jour),
    CONSTRAINT fk_suivi_profil FOREIGN KEY (id_profil_sante) REFERENCES profil_sante (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS frigo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_produit INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_frigo_user_produit (id_utilisateur, id_produit),
    CONSTRAINT fk_frigo_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_frigo_produit FOREIGN KEY (id_produit) REFERENCES produit (id_produit)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gamification_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_profil_sante INT NOT NULL,
    date_analyse DATE NOT NULL,
    points_attribues INT NOT NULL,
    resultat VARCHAR(32) NOT NULL,
    commentaire TEXT NULL,
    UNIQUE KEY uq_profil_date (id_profil_sante, date_analyse),
    CONSTRAINT fk_gamif_profil FOREIGN KEY (id_profil_sante) REFERENCES profil_sante (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
