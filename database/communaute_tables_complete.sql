-- =============================================================================
-- Communauté HappyBite : tables Post, Commentaire, Story (+ likes / commentaires)
-- Exécutez ce script dans MySQL (phpMyAdmin ou ligne de commande).
-- Remplacez "happybite" par le nom de votre base si besoin.
-- =============================================================================

USE happybite;

-- -----------------------------------------------------------------------------
-- Posts du fil d'actualité (requis pour les commentaires sur les posts)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Post (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contenu TEXT NOT NULL,
    datePublication DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    image VARCHAR(255) NULL,
    nombreLikes INT NOT NULL DEFAULT 0,
    id_utilisateur INT NULL,
    KEY idx_post_id_utilisateur (id_utilisateur),
    CONSTRAINT fk_post_id_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Commentaires liés à un post (erreur "Impossible d'enregistrer..." si absente)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Commentaire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contenu TEXT NOT NULL,
    dateCommentaire DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    post_id INT NOT NULL,
    id_utilisateur INT NULL,
    KEY idx_commentaire_id_utilisateur (id_utilisateur),
    CONSTRAINT fk_commentaire_post FOREIGN KEY (post_id) REFERENCES Post (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_commentaire_id_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Stories (images 24h) — erreur à l'ajout si cette table n'existe pas
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Story (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image VARCHAR(255) NOT NULL,
    dateCreation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur INT NULL,
    KEY idx_story_id_utilisateur (id_utilisateur),
    CONSTRAINT fk_story_id_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- J'aime sur les stories (un like par session / visitor_key)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS StoryLike (
    id INT AUTO_INCREMENT PRIMARY KEY,
    story_id INT NOT NULL,
    visitor_key VARCHAR(64) NOT NULL,
    dateLike DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_story_visitor (story_id, visitor_key),
    CONSTRAINT fk_storylike_story FOREIGN KEY (story_id) REFERENCES Story (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Commentaires sur une story
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS StoryCommentaire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    story_id INT NOT NULL,
    id_utilisateur INT NULL,
    contenu TEXT NOT NULL,
    dateCommentaire DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_storycommentaire_id_utilisateur (id_utilisateur),
    CONSTRAINT fk_storycomment_story FOREIGN KEY (story_id) REFERENCES Story (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_storycommentaire_id_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
