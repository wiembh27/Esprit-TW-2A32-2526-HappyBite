-- Tables pour les fonctionnalités sociales (posts et commentaires)
-- À exécuter sur la base happybite

USE happybite;

-- Table des posts
CREATE TABLE IF NOT EXISTS Post (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contenu TEXT NOT NULL,
    datePublication DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    image VARCHAR(255) NULL,
    nombreLikes INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Table des commentaires
CREATE TABLE IF NOT EXISTS Commentaire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contenu TEXT NOT NULL,
    dateCommentaire DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    post_id INT NOT NULL,
    CONSTRAINT fk_commentaire_post FOREIGN KEY (post_id) REFERENCES Post (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;