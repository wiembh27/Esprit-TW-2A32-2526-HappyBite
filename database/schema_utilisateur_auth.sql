-- Auth / utilisateur (connexion, inscription, Face ID WebAuthn)
-- Exécuter sur la base happybite (sans supprimer les autres tables).

USE happybite;

CREATE TABLE IF NOT EXISTS utilisateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prenom VARCHAR(100) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL,
    motDePasse VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client', 'nutritionniste', 'fournisseur') NOT NULL DEFAULT 'client',
    statut ENUM('actif', 'bloqué', 'inactif') NOT NULL DEFAULT 'actif',
    budget DECIMAL(10,2) NOT NULL DEFAULT 0,
    description TEXT NULL,
    referral_code VARCHAR(32) NULL,
    profile_photo VARCHAR(255) NULL,
    `profil-image` VARCHAR(512) NULL,
    face_auth_image VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_utilisateur_email (email),
    KEY idx_role (role),
    KEY idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS webauthn_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    credential_id VARCHAR(512) NOT NULL,
    public_key TEXT NULL,
    attestation_raw MEDIUMBLOB NULL,
    client_data_json MEDIUMBLOB NULL,
    sign_count INT NOT NULL DEFAULT 0,
    transports VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_webauthn_user (user_id),
    KEY idx_webauthn_cred (credential_id(191)),
    CONSTRAINT fk_webauthn_utilisateur FOREIGN KEY (user_id) REFERENCES utilisateur (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
