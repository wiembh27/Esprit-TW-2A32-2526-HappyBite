-- Données de démonstration HappyBite
-- Mot de passe pour tous les comptes ci-dessous : password

USE happybite;

INSERT INTO utilisateur (prenom, nom, email, motDePasse, role, statut, description)
VALUES (
    'Admin',
    'HappyBite',
    'admin@happybite.tn',
    '$2y$10$LEjGHe4oQv54poYRwRbIkOMgKq2LpBU7QqMAcub9PXlbTJqv6DmTC',
    'admin',
    'actif',
    'Compte démo administrateur — Back Office'
)
ON DUPLICATE KEY UPDATE
    prenom = VALUES(prenom),
    nom = VALUES(nom),
    motDePasse = VALUES(motDePasse),
    role = VALUES(role),
    statut = VALUES(statut),
    description = VALUES(description);

INSERT INTO utilisateur (prenom, nom, email, motDePasse, role, statut, description)
VALUES (
    'Demo',
    'Fournisseur',
    'fournisseur@happybite.tn',
    '$2y$10$LEjGHe4oQv54poYRwRbIkOMgKq2LpBU7QqMAcub9PXlbTJqv6DmTC',
    'fournisseur',
    'actif',
    'Compte démo fournisseur — gestion de ses produits'
)
ON DUPLICATE KEY UPDATE
    prenom = VALUES(prenom),
    nom = VALUES(nom),
    motDePasse = VALUES(motDePasse),
    role = VALUES(role),
    statut = VALUES(statut),
    description = VALUES(description);

INSERT INTO utilisateur (prenom, nom, email, motDePasse, role, statut, description)
VALUES (
    'Demo',
    'Nutritionniste',
    'nutritionniste@happybite.tn',
    '$2y$10$LEjGHe4oQv54poYRwRbIkOMgKq2LpBU7QqMAcub9PXlbTJqv6DmTC',
    'nutritionniste',
    'actif',
    'Compte démo nutritionniste — création de défis'
)
ON DUPLICATE KEY UPDATE
    prenom = VALUES(prenom),
    nom = VALUES(nom),
    motDePasse = VALUES(motDePasse),
    role = VALUES(role),
    statut = VALUES(statut),
    description = VALUES(description);

INSERT INTO categorie (nom, description) VALUES
    ('Fruits', 'Fruits frais et de saison'),
    ('Légumes', 'Légumes verts et racines'),
    ('Produits laitiers', 'Lait, yaourt, fromage'),
    ('Épicerie', 'Produits secs et conserves')
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT INTO produit (nom, prix, image, allergene, benefices, calories, date_ajout, id_utilisateur, id_categorie)
SELECT 'Pomme Golden', 2.50, '', 'Aucun', 'Fibres, vitamine C', 52, CURDATE(), u.id, c.id_categorie
FROM utilisateur u, categorie c
WHERE u.email = 'admin@happybite.tn' AND c.nom = 'Fruits'
  AND NOT EXISTS (SELECT 1 FROM produit p WHERE p.nom = 'Pomme Golden');

INSERT INTO produit (nom, prix, image, allergene, benefices, calories, date_ajout, id_utilisateur, id_categorie)
SELECT 'Brocoli', 3.20, '', 'Aucun', 'Antioxydants, vitamine K', 34, CURDATE(), u.id, c.id_categorie
FROM utilisateur u, categorie c
WHERE u.email = 'admin@happybite.tn' AND c.nom = 'Légumes'
  AND NOT EXISTS (SELECT 1 FROM produit p WHERE p.nom = 'Brocoli');

INSERT INTO produit (nom, prix, image, allergene, benefices, calories, date_ajout, id_utilisateur, id_categorie)
SELECT 'Yaourt nature', 1.80, '', 'Lactose', 'Probiotiques, calcium', 61, CURDATE(), u.id, c.id_categorie
FROM utilisateur u, categorie c
WHERE u.email = 'admin@happybite.tn' AND c.nom = 'Produits laitiers'
  AND NOT EXISTS (SELECT 1 FROM produit p WHERE p.nom = 'Yaourt nature');

INSERT INTO recette (nom, description, calories)
SELECT 'Salade pomme-brocoli', 'Mélange croquant de pommes et brocolis vapeur, assaisonné au citron.', 180
WHERE NOT EXISTS (SELECT 1 FROM recette WHERE nom = 'Salade pomme-brocoli');

-- Produit du fournisseur démo (visible dans List-Produit-Fournisseur.php)
INSERT INTO produit (nom, prix, image, allergene, benefices, calories, date_ajout, id_utilisateur, id_categorie)
SELECT 'Huile d''olive extra vierge', 12.90, '', 'Aucun', 'Oméga-9, antioxydants', 884, CURDATE(), u.id, c.id_categorie
FROM utilisateur u, categorie c
WHERE u.email = 'fournisseur@happybite.tn' AND c.nom = 'Épicerie'
  AND NOT EXISTS (SELECT 1 FROM produit p WHERE p.nom = 'Huile d''olive extra vierge');

INSERT INTO produit (nom, prix, image, allergene, benefices, calories, date_ajout, id_utilisateur, id_categorie)
SELECT 'Amandes bio', 8.50, '', 'Fruits à coque', 'Protéines, magnésium', 579, CURDATE(), u.id, c.id_categorie
FROM utilisateur u, categorie c
WHERE u.email = 'fournisseur@happybite.tn' AND c.nom = 'Épicerie'
  AND NOT EXISTS (SELECT 1 FROM produit p WHERE p.nom = 'Amandes bio');

-- Défi exemple créé par le nutritionniste démo
INSERT INTO challenge (titre, description, image, statut, dateCreation, nutritionnisteId)
SELECT
    'Smoothie vert du matin',
    'Préparez un smoothie avec au moins 2 légumes verts et partagez une photo.',
    NULL,
    'disponible',
    NOW(),
    u.id
FROM utilisateur u
WHERE u.email = 'nutritionniste@happybite.tn'
  AND NOT EXISTS (SELECT 1 FROM challenge c WHERE c.titre = 'Smoothie vert du matin');
