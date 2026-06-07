-- User UI preferences (one row per user: replace on language change)
CREATE TABLE IF NOT EXISTS settings (
    id_utilisateur INT NOT NULL PRIMARY KEY,
    mode VARCHAR(16) NOT NULL DEFAULT 'light',
    language VARCHAR(8) NOT NULL DEFAULT 'fr',
    CONSTRAINT fk_settings_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
