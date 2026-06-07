-- Clé unique par notification (évite les doublons livraison, rappels santé, etc.)
USE happybite;

ALTER TABLE user_notification
    ADD COLUMN IF NOT EXISTS ref_key VARCHAR(96) NULL AFTER type_notif;

CREATE UNIQUE INDEX IF NOT EXISTS idx_user_notification_ref
    ON user_notification (id_utilisateur, ref_key);
