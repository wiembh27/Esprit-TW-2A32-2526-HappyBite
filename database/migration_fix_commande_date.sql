-- Colonne de création de commande : corriger DEFAULT si vous voyez 0000-00-00.
-- Adapter USE si la base n’est pas happybite.
USE happybite;

-- Table avec colonne `date` (mot réservé MySQL → backticks) :
ALTER TABLE commande
    MODIFY `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Si votre schéma utilise plutôt date_commande :
-- ALTER TABLE commande
--     MODIFY date_commande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
