-- ============================================================
-- HappyBite — Migration: Nutritionniste, Challenges, Gamification
-- ============================================================

-- 1. Ajouter colonne points_challenge dans utilisateur (si absente)
ALTER TABLE `utilisateur`
  ADD COLUMN IF NOT EXISTS `points_challenge` INT(11) NOT NULL DEFAULT 0;

-- 2. Table Challenge
CREATE TABLE IF NOT EXISTS `challenge` (
  `id`                INT(11) NOT NULL AUTO_INCREMENT,
  `titre`             VARCHAR(255) NOT NULL,
  `description`       TEXT NOT NULL,
  `image`             VARCHAR(255) DEFAULT NULL,
  `statut`            ENUM('disponible','selectionne','termine') NOT NULL DEFAULT 'disponible',
  `dateCreation`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateSelection`     DATE DEFAULT NULL,
  `nutritionnisteId`  INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_challenge_nutritionniste` (`nutritionnisteId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Table ParticipationChallenge
CREATE TABLE IF NOT EXISTS `participation_challenge` (
  `id`                  INT(11) NOT NULL AUTO_INCREMENT,
  `clientId`            INT(11) NOT NULL,
  `challengeId`         INT(11) NOT NULL,
  `photo`               VARCHAR(255) DEFAULT NULL,
  `description`         TEXT DEFAULT NULL,
  `statutValidationIA`  ENUM('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
  `nombreLikes`         INT(11) NOT NULL DEFAULT 0,
  `dateParticipation`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_participation` (`clientId`, `challengeId`),
  KEY `fk_participation_client` (`clientId`),
  KEY `fk_participation_challenge` (`challengeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Table LikeParticipation (un like par utilisateur par participation)
CREATE TABLE IF NOT EXISTS `like_participation` (
  `id`               INT(11) NOT NULL AUTO_INCREMENT,
  `participationId`  INT(11) NOT NULL,
  `userId`           INT(11) NOT NULL,
  `dateLike`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_like_participation` (`participationId`, `userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Table ProduitRoue
CREATE TABLE IF NOT EXISTS `produit_roue` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `nomProduit` VARCHAR(255) NOT NULL,
  `image`      VARCHAR(255) DEFAULT NULL,
  `actif`      TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Table Recompense
CREATE TABLE IF NOT EXISTS `recompense` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `clientId`      INT(11) NOT NULL,
  `produitRoueId` INT(11) NOT NULL,
  `pointsUtilises` INT(11) NOT NULL DEFAULT 300,
  `dateGain`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `statut`        ENUM('en_attente','utilisee') NOT NULL DEFAULT 'en_attente',
  PRIMARY KEY (`id`),
  KEY `fk_recompense_client` (`clientId`),
  KEY `fk_recompense_produit` (`produitRoueId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. Données initiales ProduitRoue
INSERT IGNORE INTO `produit_roue` (`nomProduit`, `image`, `actif`) VALUES
('Smoothie Détox Offert', NULL, 1),
('Salade Healthy Gratuite', NULL, 1),
('Barre Protéinée', NULL, 1),
('Jus de Fruits Frais', NULL, 1),
('Portion de Fruits Secs', NULL, 1),
('Yaourt Bio', NULL, 1),
('Granola Maison', NULL, 1),
('Thé Vert Premium', NULL, 1);
