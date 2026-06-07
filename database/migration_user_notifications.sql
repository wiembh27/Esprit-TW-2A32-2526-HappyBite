-- Notifications utilisateur (bienvenue, etc.)
USE happybite;

CREATE TABLE IF NOT EXISTS user_notification (
    id_notification INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    type_notif VARCHAR(32) NOT NULL DEFAULT 'info',
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_lu (id_utilisateur, lu),
    INDEX idx_user_created (id_utilisateur, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
