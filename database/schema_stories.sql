-- Table des Stories
-- À exécuter sur la base happybite

CREATE TABLE IF NOT EXISTS Story (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image VARCHAR(255) NOT NULL,
    dateCreation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
