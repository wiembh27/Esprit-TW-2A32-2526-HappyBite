-- =============================================================================
-- HappyBite — Jeu de données démo complet (ESPRIT / GitHub)
-- Import UNIQUE dans phpMyAdmin ou : mysql -u root < happybite_full_demo.sql
-- Ne pas combiner avec install.bat / seed_demo.sql
-- Comptes fictifs — mot de passe : password
-- Contenu : produits, recettes, posts, ~30 défis, commandes, profils santé…
-- =============================================================================
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 05 juin 2026 à 22:30
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `happybite`
--

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `id_categorie` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categorie`
--

INSERT INTO `categorie` (`id_categorie`, `nom`, `description`) VALUES
(1, 'Non classé', 'Catégorie utilisée automatiquement pour les produits sans catégorie'),
(2, 'Fruits de mer', 'Produits issus de la mer comme poissons et crustacés'),
(6, 'Fruits', 'Fruits frais et naturels'),
(11, 'Légumes', 'Aliments d’origine végétale riches en vitamines'),
(13, 'Produits laitiers', 'Produits dérivés du lait'),
(14, 'Boisson', 'Liquides destinés à la consommation'),
(15, 'Dessert', 'fgvhjukiolpoijhuygtf');

-- --------------------------------------------------------

--
-- Structure de la table `challenge`
--

CREATE TABLE `challenge` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `statut` enum('disponible','selectionne','termine') NOT NULL DEFAULT 'disponible',
  `dateCreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateSelection` date DEFAULT NULL,
  `nutritionnisteId` int(11) NOT NULL,
  `regle_ia` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `challenge`
--

INSERT INTO `challenge` (`id`, `titre`, `description`, `image`, `statut`, `dateCreation`, `dateSelection`, `nutritionnisteId`, `regle_ia`) VALUES
(1, '5 Fruits et Légumes par Jour', 'Consommer au moins 5 portions de fruits et légumes dans la journée.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(2, 'Hydratation Parfaite', 'Boire au moins 2 litres d\'eau aujourd\'hui.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(3, 'Petit Déjeuner Healthy', 'Préparer un petit déjeuner équilibré et nutritif.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(4, 'Zéro Soda', 'Passer toute la journée sans boissons gazeuses.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(5, 'Assiette Colorée', 'Composer un repas avec au moins 4 couleurs différentes de fruits ou légumes.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(6, 'Collation Saine', 'Remplacer une collation industrielle par un fruit ou des noix.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(7, 'Journée Sans Fast-Food', 'Éviter totalement les fast-foods pendant 24 heures.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(8, 'Smoothie Maison', 'Préparer un smoothie maison à base de fruits frais.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(9, 'Défi Salade Complète', 'Créer une salade équilibrée contenant protéines, légumes et fibres.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(10, 'Réduction du Sucre', 'Éviter les sucreries et desserts industriels pendant une journée.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(11, 'Repas Fait Maison', 'Préparer un repas complet à la maison.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(12, '10000 Pas', 'Marcher au moins 10 000 pas aujourd\'hui.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(13, 'Légumes au Dîner', 'Ajouter au moins deux légumes différents au dîner.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(14, 'Fruit du Matin', 'Commencer la journée avec un fruit frais.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(15, 'Défi Yaourt Nature', 'Remplacer un dessert sucré par un yaourt nature.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(16, 'Cuisine Vapeur', 'Préparer un repas avec une cuisson vapeur.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(17, 'Une Journée Sans Chips', 'Éviter complètement les chips et snacks salés.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(18, 'Poisson Santé', 'Manger une portion de poisson aujourd\'hui.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(19, 'Défi Fibres', 'Atteindre un apport élevé en fibres grâce aux légumes et céréales complètes.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(20, 'Lunch Équilibré', 'Préparer un déjeuner équilibré avec protéines, glucides et légumes.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(21, 'Noix et Amandes', 'Consommer une petite poignée de fruits secs non salés.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(22, 'Défi Thé Vert', 'Boire au moins une tasse de thé vert sans sucre.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(23, 'Repas Sans Friture', 'Manger uniquement des aliments non frits aujourd\'hui.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(24, 'Légume Inconnu', 'Essayer un légume que vous mangez rarement.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(25, 'Défi Oméga-3', 'Consommer un aliment riche en oméga-3.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(26, 'Assiette 50% Légumes', 'Faire en sorte que la moitié de votre assiette soit composée de légumes.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(27, 'Journée Sans Bonbons', 'Ne consommer aucun bonbon ou confiserie aujourd\'hui.', NULL, 'selectionne', '2026-06-04 22:33:56', '2026-06-05', 25, NULL),
(28, 'Petit Déjeuner Protéiné', 'Inclure une source de protéines au petit déjeuner.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL),
(29, 'Défi Fruits Rouges', 'Consommer une portion de fruits rouges dans la journée.', NULL, 'termine', '2026-06-04 22:33:56', '2026-06-04', 25, NULL),
(30, 'Menu Healthy Complet', 'Créer une journée complète de repas équilibrés du matin au soir.', NULL, 'disponible', '2026-06-04 22:33:56', NULL, 25, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `commande`
--

CREATE TABLE `commande` (
  `id_commande` int(11) NOT NULL,
  `date` date NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `modePaiement` varchar(50) DEFAULT NULL,
  `reduction` decimal(5,2) DEFAULT NULL,
  `id_livraison` int(11) DEFAULT NULL,
  `id_utilisateur` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commande`
--

INSERT INTO `commande` (`id_commande`, `date`, `total`, `modePaiement`, `reduction`, `id_livraison`, `id_utilisateur`) VALUES
(170, '2026-05-18', 55.00, 'paypal', 0.00, 105, NULL),
(171, '2026-05-19', 89.00, 'cash', 5.00, 106, NULL),
(172, '2026-05-20', 102.00, 'carte', 10.00, 107, NULL),
(173, '2026-05-21', 66.50, 'paypal', 0.00, 108, NULL),
(174, '2026-05-22', 144.00, 'cash', 10.00, 109, NULL),
(175, '2026-05-23', 190.00, 'carte', 20.00, 110, NULL),
(176, '2026-05-24', 73.00, 'paypal', 0.00, 111, NULL),
(177, '2026-05-25', 81.50, 'cash', 5.00, 112, NULL),
(178, '2026-05-26', 132.00, 'carte', 10.00, 113, NULL),
(179, '2026-05-27', 99.00, 'paypal', 0.00, 114, NULL),
(180, '2026-05-28', 110.00, 'cash', 5.00, 115, NULL),
(181, '2026-05-29', 250.00, 'carte', 25.00, 116, NULL),
(182, '2026-05-30', 62.00, 'paypal', 0.00, 117, NULL),
(183, '2026-05-31', 170.00, 'cash', 15.00, 118, NULL),
(184, '2026-04-01', 93.00, 'carte', 5.00, 119, NULL),
(185, '2026-04-02', 125.00, 'paypal', 10.00, 120, NULL),
(186, '2026-04-03', 88.00, 'cash', 0.00, 121, NULL),
(187, '2026-04-04', 205.00, 'carte', 20.00, 122, NULL),
(188, '2026-04-05', 74.50, 'paypal', 0.00, 123, NULL),
(189, '2026-04-06', 98.00, 'cash', 5.00, 124, NULL),
(190, '2026-04-07', 143.00, 'carte', 10.00, 125, NULL),
(191, '2026-04-08', 59.00, 'paypal', 0.00, 126, NULL),
(192, '2026-04-09', 182.00, 'cash', 15.00, 127, NULL),
(193, '2026-04-10', 77.00, 'carte', 0.00, 128, NULL),
(194, '2026-04-11', 136.00, 'paypal', 10.00, 129, NULL),
(195, '2026-04-12', 94.00, 'cash', 5.00, 130, NULL),
(196, '2026-04-13', 220.00, 'carte', 20.00, 131, NULL),
(197, '2026-04-14', 68.00, 'paypal', 0.00, 132, NULL),
(198, '2026-04-15', 115.00, 'cash', 10.00, 133, NULL),
(199, '2026-04-16', 154.00, 'carte', 15.00, 134, NULL),
(200, '2026-04-17', 83.00, 'paypal', 5.00, 135, NULL),
(201, '2026-04-18', 201.00, 'cash', 20.00, 136, NULL),
(202, '2026-04-19', 92.50, 'carte', 0.00, 137, NULL),
(203, '2026-04-20', 145.00, 'paypal', 10.00, 138, NULL),
(204, '2026-04-21', 71.00, 'cash', 5.00, 139, NULL),
(205, '2026-04-22', 160.00, 'carte', 15.00, 140, NULL),
(206, '2026-04-23', 89.00, 'paypal', 0.00, 141, NULL),
(207, '2026-04-24', 212.00, 'cash', 20.00, 142, NULL),
(208, '2026-04-25', 76.00, 'carte', 5.00, 143, NULL),
(209, '2026-04-26', 134.00, 'paypal', 10.00, 144, NULL),
(211, '2026-04-28', 120.00, 'cash', 10.00, 146, NULL),
(212, '2026-04-29', 98.50, 'carte', 5.00, 147, NULL),
(213, '2026-04-30', 140.00, 'paypal', 15.00, 148, NULL),
(214, '2026-03-01', 66.00, 'cash', 0.00, 149, NULL),
(215, '2026-03-02', 180.00, 'carte', 20.00, 150, NULL),
(216, '2026-03-03', 92.00, 'paypal', 5.00, 151, NULL),
(217, '2026-03-04', 110.00, 'cash', 10.00, 152, NULL),
(218, '2026-03-05', 155.00, 'carte', 15.00, 153, NULL),
(219, '2026-03-06', 84.00, 'paypal', 0.00, 154, NULL),
(220, '2026-05-01', 95.00, 'paypal', 5.00, 155, NULL),
(221, '2026-05-02', 120.00, 'cash', 10.00, 156, NULL),
(222, '2026-05-03', 88.00, 'carte', 0.00, 157, NULL),
(223, '2026-05-04', 140.00, 'paypal', 15.00, 158, NULL),
(224, '2026-05-05', 72.00, 'cash', 0.00, 159, NULL),
(225, '2026-05-06', 180.00, 'carte', 20.00, 160, NULL),
(226, '2026-05-07', 65.00, 'paypal', 0.00, 161, NULL),
(227, '2026-05-08', 110.00, 'cash', 10.00, 162, NULL),
(228, '2026-05-09', 98.00, 'carte', 5.00, 163, NULL),
(229, '2026-05-10', 155.00, 'paypal', 15.00, 164, NULL),
(230, '2026-05-11', 84.00, 'cash', 0.00, 165, NULL),
(231, '2026-05-12', 200.00, 'carte', 20.00, 166, NULL),
(232, '2026-05-13', 90.00, 'paypal', 5.00, 167, NULL),
(233, '2026-05-14', 130.00, 'cash', 10.00, 168, NULL),
(234, '2026-05-15', 76.00, 'carte', 0.00, 169, NULL),
(235, '2026-05-16', 175.00, 'paypal', 15.00, 170, NULL),
(236, '2026-05-17', 68.00, 'cash', 0.00, 171, NULL),
(237, '2026-05-18', 115.00, 'carte', 10.00, 172, NULL),
(238, '2026-05-19', 92.00, 'paypal', 5.00, 173, NULL),
(239, '2026-05-20', 160.00, 'cash', 15.00, 174, NULL),
(240, '2026-05-21', 78.00, 'carte', 0.00, 175, NULL),
(241, '2026-05-22', 210.00, 'paypal', 20.00, 176, NULL),
(242, '2026-05-23', 88.00, 'cash', 5.00, 177, NULL),
(243, '2026-05-24', 145.00, 'carte', 10.00, 178, NULL),
(244, '2026-05-25', 70.00, 'paypal', 0.00, 179, NULL),
(245, '2026-05-26', 190.00, 'cash', 20.00, 180, NULL),
(246, '2026-05-27', 96.00, 'carte', 5.00, 181, NULL),
(247, '2026-05-28', 135.00, 'paypal', 10.00, 182, NULL),
(248, '2026-05-29', 82.00, 'cash', 0.00, 183, NULL),
(249, '2026-05-30', 170.00, 'carte', 15.00, 184, NULL),
(250, '2026-05-01', 75.00, 'paypal', 0.00, 185, NULL),
(251, '2026-05-02', 125.00, 'cash', 10.00, 186, NULL),
(252, '2026-05-03', 99.00, 'carte', 5.00, 187, NULL),
(253, '2026-05-04', 150.00, 'paypal', 15.00, 188, NULL),
(254, '2026-05-05', 86.00, 'cash', 0.00, 189, NULL),
(255, '2026-05-06', 220.00, 'carte', 20.00, 190, NULL),
(256, '2026-05-07', 91.00, 'paypal', 5.00, 191, NULL),
(257, '2026-05-08', 138.00, 'cash', 10.00, 192, NULL),
(258, '2026-05-09', 74.00, 'carte', 0.00, 193, NULL),
(259, '2026-05-10', 165.00, 'paypal', 15.00, 194, NULL),
(260, '2026-05-11', 89.00, 'cash', 5.00, 195, NULL),
(261, '2026-05-12', 205.00, 'carte', 20.00, 196, NULL),
(262, '2026-05-13', 93.00, 'paypal', 5.00, 197, NULL),
(263, '2026-05-14', 142.00, 'cash', 10.00, 198, NULL),
(264, '2026-05-15', 80.00, 'carte', 0.00, 199, NULL),
(265, '2026-05-16', 185.00, 'paypal', 15.00, 200, NULL),
(266, '2026-05-17', 72.00, 'cash', 0.00, 201, NULL),
(267, '2026-05-18', 118.00, 'carte', 10.00, 202, NULL),
(268, '2026-05-19', 95.00, 'paypal', 5.00, 203, NULL),
(269, '2026-05-20', 172.00, 'cash', 15.00, 204, NULL),
(270, '2026-05-07', 85.00, 'carte', 0.00, 205, NULL),
(271, '2026-05-08', 195.00, 'paypal', 20.00, 206, NULL),
(272, '2026-05-09', 97.00, 'cash', 5.00, 207, NULL),
(273, '2026-05-10', 148.00, 'carte', 10.00, 208, NULL),
(274, '2026-05-11', 76.00, 'paypal', 0.00, 209, NULL),
(275, '2026-05-12', 215.00, 'cash', 20.00, 210, NULL),
(276, '2026-05-13', 94.00, 'carte', 5.00, 211, NULL),
(277, '2026-05-14', 136.00, 'paypal', 10.00, 212, NULL),
(278, '2026-05-15', 79.00, 'cash', 0.00, 213, NULL),
(279, '2026-05-16', 168.00, 'carte', 15.00, 214, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `commande_produit`
--

CREATE TABLE `commande_produit` (
  `id_commande_produit` int(11) NOT NULL,
  `id_commande` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `prix_unitaire` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commentaire`
--

CREATE TABLE `commentaire` (
  `id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `dateCommentaire` datetime NOT NULL DEFAULT current_timestamp(),
  `post_id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commentaire`
--

INSERT INTO `commentaire` (`id`, `contenu`, `dateCommentaire`, `post_id`, `id_utilisateur`) VALUES
(5, 'yum', '2026-06-05 22:07:33', 24, 2);

-- --------------------------------------------------------

--
-- Structure de la table `face_auth`
--

CREATE TABLE `face_auth` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `face_descriptor` text NOT NULL,
  `face_image` varchar(255) DEFAULT NULL,
  `face_template` text DEFAULT NULL,
  `quality_score` decimal(5,2) DEFAULT 0.00,
  `trust_score` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `face_auth_logs`
--

CREATE TABLE `face_auth_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT 'verify',
  `success` tinyint(4) DEFAULT 0,
  `confidence_score` decimal(5,2) DEFAULT NULL,
  `trust_score` decimal(5,2) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `frigo`
--

CREATE TABLE `frigo` (
  `id_utilisateur` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `date_ajout` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gamification_log`
--

CREATE TABLE `gamification_log` (
  `id` int(11) NOT NULL,
  `id_profil_sante` int(11) NOT NULL,
  `date_analyse` date NOT NULL,
  `points_attribues` int(11) NOT NULL,
  `resultat` varchar(32) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `suivi_journalier_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `like_participation`
--

CREATE TABLE `like_participation` (
  `id` int(11) NOT NULL,
  `participationId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `dateLike` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `like_participation`
--

INSERT INTO `like_participation` (`id`, `participationId`, `userId`, `dateLike`) VALUES
(1, 1, 2, '2026-06-05 03:56:04');

-- --------------------------------------------------------

--
-- Structure de la table `livraison`
--

CREATE TABLE `livraison` (
  `id_livraison` int(11) NOT NULL,
  `date` date NOT NULL,
  `statut` varchar(50) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `transit_seconds` int(11) DEFAULT NULL,
  `arrival_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `livraison`
--

INSERT INTO `livraison` (`id_livraison`, `date`, `statut`, `id_utilisateur`, `created_at`, `transit_seconds`, `arrival_at`) VALUES
(95, '2026-05-15', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(96, '2026-05-14', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(97, '2026-05-15', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(98, '2026-05-16', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(99, '2026-05-17', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(100, '2026-05-18', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(101, '2026-05-19', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(102, '2026-06-04', 'Livrée', NULL, '2026-06-02 02:52:24', 1405, '2026-06-04 03:15:49'),
(103, '2026-05-21', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(104, '2026-05-22', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(105, '2026-05-23', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(106, '2026-05-24', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(107, '2026-05-25', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(108, '2026-05-26', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(109, '2026-05-27', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(110, '2026-05-28', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(111, '2026-05-29', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(112, '2026-05-30', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(113, '2026-05-31', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(114, '2026-04-01', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(115, '2026-04-02', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(116, '2026-04-03', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(117, '2026-04-04', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(118, '2026-04-05', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(119, '2026-04-06', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(120, '2026-04-07', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(121, '2026-04-08', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(122, '2026-04-09', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(123, '2026-04-10', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(124, '2026-04-11', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(125, '2026-04-12', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(126, '2026-04-13', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(127, '2026-04-14', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(128, '2026-04-15', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(129, '2026-04-16', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(130, '2026-04-17', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(131, '2026-04-18', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(132, '2026-04-19', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(133, '2026-04-20', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(134, '2026-04-21', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(135, '2026-04-22', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(136, '2026-04-23', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(137, '2026-04-24', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(138, '2026-04-25', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(139, '2026-04-26', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(140, '2026-04-27', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(141, '2026-04-28', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(142, '2026-04-29', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(143, '2026-04-30', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(144, '2026-03-01', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(145, '2026-05-13', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(146, '2026-03-03', 'Annulée', NULL, '2026-06-02 02:52:24', NULL, NULL),
(147, '2026-03-04', 'Annulée', NULL, '2026-06-02 02:52:24', NULL, NULL),
(148, '2026-03-05', 'Annulée', NULL, '2026-06-02 02:52:24', NULL, NULL),
(149, '2026-03-06', 'Annulée', NULL, '2026-06-02 02:52:24', NULL, NULL),
(150, '2026-03-07', 'Annulée', NULL, '2026-06-02 02:52:24', NULL, NULL),
(151, '2026-03-08', 'Annulée', NULL, '2026-06-02 02:52:24', NULL, NULL),
(152, '2026-03-09', 'Annulée', NULL, '2026-06-02 02:52:24', NULL, NULL),
(153, '2026-03-10', 'Annulée', NULL, '2026-06-02 02:52:24', NULL, NULL),
(154, '2026-03-11', 'Annulée', NULL, '2026-06-02 02:52:24', NULL, NULL),
(155, '2026-05-06', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(156, '2026-05-07', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(157, '2026-05-08', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(158, '2026-05-09', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(159, '2026-05-10', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(160, '2026-05-11', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(161, '2026-05-12', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(162, '2026-05-13', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(163, '2026-05-14', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(164, '2026-05-15', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(165, '2026-05-16', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(166, '2026-05-17', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(167, '2026-05-18', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(168, '2026-05-19', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(169, '2026-05-20', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(170, '2026-05-21', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(171, '2026-05-22', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(172, '2026-05-23', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(173, '2026-05-24', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(174, '2026-05-25', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(175, '2026-05-26', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(176, '2026-05-27', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(177, '2026-05-28', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(178, '2026-05-29', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(179, '2026-05-30', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(180, '2026-05-31', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(181, '2026-05-08', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(182, '2026-05-09', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(183, '2026-05-10', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(184, '2026-05-11', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(185, '2026-05-12', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(186, '2026-05-13', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(187, '2026-05-14', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(188, '2026-05-15', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(189, '2026-05-16', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(190, '2026-05-17', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(191, '2026-05-18', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(192, '2026-05-19', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(193, '2026-05-20', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(194, '2026-05-21', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(195, '2026-05-22', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(196, '2026-05-23', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(197, '2026-05-24', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(198, '2026-05-25', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(199, '2026-05-26', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(200, '2026-05-27', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(201, '2026-05-28', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(202, '2026-05-29', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(203, '2026-05-30', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(204, '2026-05-31', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(205, '2026-05-12', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(206, '2026-05-13', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(207, '2026-05-14', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(208, '2026-05-15', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(209, '2026-05-16', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(210, '2026-05-17', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(211, '2026-05-18', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(212, '2026-05-19', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(213, '2026-05-20', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(214, '2026-05-21', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(215, '2026-05-17', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(216, '2026-05-18', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(217, '2026-05-19', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(218, '2026-05-21', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(219, '2026-05-25', 'En cours', NULL, '2026-06-02 02:52:24', NULL, NULL),
(220, '2026-06-04', 'Livrée', NULL, '2026-06-02 02:52:24', 1406, '2026-06-04 03:15:50'),
(221, '2026-06-04', 'Livrée', NULL, '2026-06-02 03:49:19', 1404, '2026-06-04 04:12:43'),
(222, '2026-06-05', 'En préparation', NULL, '2026-06-03 20:01:52', 1405, '2026-06-05 20:25:17'),
(223, '2026-06-05', 'En préparation', NULL, '2026-06-03 22:55:47', 1405, '2026-06-05 23:19:12');

-- --------------------------------------------------------

--
-- Structure de la table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `success` tinyint(4) DEFAULT 0,
  `attempt_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `login_time` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `success` tinyint(1) DEFAULT 0,
  `email_attempted` varchar(120) DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `login_time`, `ip_address`, `user_agent`, `success`, `email_attempted`, `logout_time`) VALUES
(1, NULL, '2026-04-21 19:52:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `loyalty_referrals`
--

CREATE TABLE `loyalty_referrals` (
  `id` int(11) NOT NULL,
  `referrer_id` int(11) NOT NULL,
  `referee_id` int(11) NOT NULL,
  `referral_code` varchar(20) NOT NULL,
  `first_order_at` datetime DEFAULT NULL,
  `first_order_rewarded` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `loyalty_transactions`
--

CREATE TABLE `loyalty_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reference_user_id` int(11) DEFAULT NULL,
  `type` enum('order','referral_bonus','milestone_bonus','redeem','manual') NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `category` varchar(80) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `participation_challenge`
--

CREATE TABLE `participation_challenge` (
  `id` int(11) NOT NULL,
  `clientId` int(11) NOT NULL,
  `challengeId` int(11) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `statutValidationIA` enum('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
  `nombreLikes` int(11) NOT NULL DEFAULT 0,
  `dateParticipation` datetime NOT NULL DEFAULT current_timestamp(),
  `score_ia` tinyint(4) DEFAULT NULL,
  `raison_ia` text DEFAULT NULL,
  `message_ia` text DEFAULT NULL,
  `bonus_top1_given` tinyint(1) NOT NULL DEFAULT 0,
  `validation_ai_message` text DEFAULT NULL,
  `validation_ai_score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `participation_challenge`
--

INSERT INTO `participation_challenge` (`id`, `clientId`, `challengeId`, `photo`, `description`, `statutValidationIA`, `nombreLikes`, `dateParticipation`, `score_ia`, `raison_ia`, `message_ia`, `bonus_top1_given`, `validation_ai_message`, `validation_ai_score`) VALUES
(1, 2, 27, 'uploads/participations/1780624535_18f5c47cf5c10ee1.png', 'i ate so many stawbarries', 'valide', 1, '2026-06-05 03:55:44', NULL, NULL, NULL, 1, 'Bravo, votre photo respecte bien le challenge sans bonbons. Score IA : 90/100', 90);

-- --------------------------------------------------------

--
-- Structure de la table `post`
--

CREATE TABLE `post` (
  `id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `datePublication` datetime NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `nombreLikes` int(11) DEFAULT 0,
  `id_utilisateur` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `post`
--

INSERT INTO `post` (`id`, `contenu`, `datePublication`, `image`, `nombreLikes`, `id_utilisateur`) VALUES
(24, 'best spaguetti ever!!!!!!!!!!🥰❤️🤌', '2026-05-09 22:27:53', '69ff98c94b035-healthyspaghettibolo_80401_16x9.jpg', 4, 2),
(25, 'my lunchbox!!!!!!!', '2026-05-09 22:29:05', '69ff99114a608-healthy-sushi-1024x658.jpg', 2, 3),
(26, 'yummmm!!!! 😋', '2026-05-09 22:31:02', '69ff992b319e5-ai.jpg', 2, 4),
(27, 'les ingrédients pour ma salade!!!!', '2026-05-09 22:33:32', '69ff9a1ce5902-14-Healthy-foods-for-healthy-diet.jpeg', 2, 5),
(29, 'pizza', '2026-05-14 15:10:13', '6a05c9ae096be-ai.jpg', 1, 6);

-- --------------------------------------------------------

--
-- Structure de la table `produit`
--

CREATE TABLE `produit` (
  `id_produit` int(11) NOT NULL,
  `id_categorie` int(11) NOT NULL,
  `allergene` text DEFAULT NULL,
  `benefices` text DEFAULT NULL,
  `calories` int(11) DEFAULT NULL,
  `date_ajout` date DEFAULT curdate(),
  `nom` varchar(150) NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `promo` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produit`
--

INSERT INTO `produit` (`id_produit`, `id_categorie`, `allergene`, `benefices`, `calories`, `date_ajout`, `nom`, `prix`, `image`, `id_utilisateur`, `promo`) VALUES
(3, 13, 'Gluten,Lactose,Sulfites,Sucre élevé,Sel élevé', 'Protéines', 42, '2026-04-09', 'Lait écrémé', 2.80, '1775778865_lait-poudre-ecreme.png', 24, NULL),
(4, 13, 'Lactose', 'Calcium,Protéines', 59, '2026-04-09', 'Yaourt nature', 2.20, '1775778851_100017701.jpg', 24, NULL),
(5, 11, NULL, 'Vitamine A,Fibres', 41, '2026-04-09', 'Carotte', 0.90, '1775778835_1-2.png', 24, NULL),
(6, 11, 'Sucre élevé,Sel élevé', 'Vitamine C,Fer,Fibres', 34, '2026-04-09', 'Brocoli', 1.80, '1775778820_Sans-titre-41.png', 24, NULL),
(9, 14, NULL, 'Vitamine C', 45, '2026-04-09', 'Jus d orange', 2.50, '1775778752_images__9_.jpg', 24, NULL),
(11, 13, 'Lactose', 'Calcium,Protéines', 98, '2026-04-09', 'Fromage blanc', 3.50, '1775778705_images__8_.jpg', 24, NULL),
(13, 14, 'Sulfites', 'Vitamine C,Calcium,Magnésium', 70, '2026-04-10', 'Lait amende', 13.50, '1776296890_1031b2f1ae2995c5e2d4131bea96707b.jpeg', 24, NULL),
(24, 6, '', 'Vitamine A,Vitamine B,Vitamine C,Vitamine D', 20, '2026-04-22', 'Pomme', 7.00, '1776810716_pomme-rouge-scaled.webp', 24, 6.00),
(25, 2, '', 'Protéines', 100, '2026-04-22', 'Samon', 45.00, '1776811389_scottish-salmon.jpg', 24, NULL),
(27, 15, 'Gluten', 'Vitamine A,Vitamine B,Vitamine C,Vitamine D,Protéines', 20, '2026-04-23', 'barre proteinée', 5.00, '1776898621_images__16_.jpg', 24, 4.50),
(28, 6, 'Sucre élevé', 'Vitamine A,Vitamine B,Vitamine D', 0, '2026-05-09', 'Fraise', 7.50, '1778352961_fraise.png', 24, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `produit_roue`
--

CREATE TABLE `produit_roue` (
  `id` int(11) NOT NULL,
  `nomProduit` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produit_roue`
--

INSERT INTO `produit_roue` (`id`, `nomProduit`, `image`, `actif`) VALUES
(1234, 'Pomme gratuite', NULL, 1),
(1235, 'Brocoli gratuit', NULL, 1),
(1236, 'Salade de fruit gratuite', NULL, 1),
(1237, '+10 points santé', NULL, 1),
(1238, '-10 points santé', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `profil_sante`
--

CREATE TABLE `profil_sante` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `taille` decimal(5,2) DEFAULT NULL,
  `poids_actuel` decimal(5,2) DEFAULT NULL,
  `objectif` varchar(100) DEFAULT NULL,
  `allergenes` text DEFAULT NULL,
  `carences` text DEFAULT NULL,
  `maladies` text DEFAULT NULL,
  `date_mise_a_jour` datetime DEFAULT current_timestamp(),
  `points` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `profil_sante`
--

INSERT INTO `profil_sante` (`id`, `id_utilisateur`, `taille`, `poids_actuel`, `objectif`, `allergenes`, `carences`, `maladies`, `date_mise_a_jour`, `points`) VALUES
(15, 2, 160.00, 60.00, 'Perte de poids', '[\"Sucre\"]', '[\"Vitamine C\",\"Vitamine D\"]', '[\"Cholestérol\"]', '2026-06-05 17:34:12', 0);

-- --------------------------------------------------------

--
-- Structure de la table `recette`
--

CREATE TABLE `recette` (
  `id_recette` int(11) NOT NULL,
  `description` text NOT NULL,
  `calories` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `nom` varchar(150) NOT NULL,
  `mise_en_avant` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `recette`
--

INSERT INTO `recette` (`id_recette`, `description`, `calories`, `image`, `nom`, `mise_en_avant`) VALUES
(1, 'Recette légère à base de fruits frais.', 42, '1776808383_i26162-salade-de-fruits-d-ete-facile.jpg', 'Salade de fruits', 0),
(2, 'Boisson fruitée simple et énergisante.', 60, '1776808357_Firefly_Smoothie-pomme-banane-153740.jpg', 'Smoothie banane pomme', 1),
(5, 'Collation rapide et riche en protéines.', 98, '1776808264_images__12_.jpg', 'Snack pain fromage blanc', 0),
(6, 'rtfgyuhijoplghjukljhugtf', 191, '1776808239_images__11_.jpg', 'Cheese cake', 1),
(7, 'defrgthyjuergtr', 273, '1776899519_salade-russe-traditionnelle-du-patriarche-recette-authentique-et-savoureuse-13545.webp', 'Salade Russe 0%', 0);

-- --------------------------------------------------------

--
-- Structure de la table `recette_produit`
--

CREATE TABLE `recette_produit` (
  `id_recette` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `recette_produit`
--

INSERT INTO `recette_produit` (`id_recette`, `id_produit`) VALUES
(1, 3),
(5, 11),
(6, 3),
(6, 4),
(6, 13),
(6, 27),
(7, 5),
(7, 6),
(7, 11),
(7, 25);

-- --------------------------------------------------------

--
-- Structure de la table `recompense`
--

CREATE TABLE `recompense` (
  `id` int(11) NOT NULL,
  `clientId` int(11) NOT NULL,
  `produitRoueId` int(11) NOT NULL,
  `pointsUtilises` int(11) NOT NULL DEFAULT 300,
  `dateGain` datetime NOT NULL DEFAULT current_timestamp(),
  `statut` enum('en_attente','utilisee') NOT NULL DEFAULT 'en_attente',
  `typeGain` varchar(50) DEFAULT NULL,
  `nomGain` varchar(255) DEFAULT NULL,
  `pointsGagnes` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `recompense`
--

INSERT INTO `recompense` (`id`, `clientId`, `produitRoueId`, `pointsUtilises`, `dateGain`, `statut`, `typeGain`, `nomGain`, `pointsGagnes`) VALUES
(5, 21, 6, 100, '2026-06-05 03:46:19', 'utilisee', 'recette', 'Cheese cake', 0),
(6, 21, 3, 100, '2026-06-05 03:46:19', 'utilisee', 'produit', 'Lait écrémé', 0),
(7, 21, 0, 100, '2026-06-05 03:46:19', 'utilisee', 'points', '+100 points santé', 100),
(8, 2, 4, 100, '2026-06-05 19:15:13', 'utilisee', 'produit', 'Yaourt nature', 0);

-- --------------------------------------------------------

--
-- Structure de la table `settings`
--

CREATE TABLE `settings` (
  `id_utilisateur` int(11) NOT NULL,
  `mode` varchar(20) NOT NULL,
  `language` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `settings`
--

INSERT INTO `settings` (`id_utilisateur`, `mode`, `language`) VALUES
(2, 'dark', 'en'),
(24, 'light', 'fr');

-- --------------------------------------------------------

--
-- Structure de la table `story`
--

CREATE TABLE `story` (
  `id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `dateCreation` datetime NOT NULL DEFAULT current_timestamp(),
  `id_utilisateur` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `story`
--

INSERT INTO `story` (`id`, `image`, `dateCreation`, `id_utilisateur`) VALUES
(10, 'uploads/stories/1780685684_56e0e5f340651eb6.png', '2026-06-05 20:54:44', 2);

-- --------------------------------------------------------

--
-- Structure de la table `storycommentaire`
--

CREATE TABLE `storycommentaire` (
  `id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `dateCommentaire` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `storylike`
--

CREATE TABLE `storylike` (
  `id` int(11) NOT NULL,
  `story_id` int(11) NOT NULL,
  `visitor_key` varchar(64) NOT NULL,
  `dateLike` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `suivi_journalier`
--

CREATE TABLE `suivi_journalier` (
  `id` int(11) NOT NULL,
  `id_profil_sante` int(11) NOT NULL,
  `date_jour` date DEFAULT NULL,
  `poids` decimal(5,2) DEFAULT NULL,
  `calories` int(11) DEFAULT NULL,
  `sommeil_heures` decimal(4,2) DEFAULT NULL,
  `nbr_pas` int(11) DEFAULT NULL,
  `nbr_activites_sport` int(11) DEFAULT NULL,
  `hydratation_litre` varchar(30) DEFAULT NULL,
  `type_activite` varchar(50) DEFAULT NULL,
  `duree_seance_minutes` smallint(6) DEFAULT 0,
  `intensite` varchar(20) DEFAULT NULL,
  `commentaire_sport` text DEFAULT NULL,
  `points_resultat` tinyint(4) DEFAULT NULL,
  `analyse_resultat` varchar(20) DEFAULT NULL,
  `analyse_commentaire` text DEFAULT NULL,
  `analysed_at` datetime DEFAULT NULL,
  `sport_type` varchar(50) DEFAULT 'aucune',
  `sport_duree_minutes` int(11) NOT NULL DEFAULT 0,
  `sport_duree` int(11) DEFAULT 0,
  `sport_intensite` varchar(20) DEFAULT 'aucune',
  `sport_commentaire` text DEFAULT NULL,
  `commentaire` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `suivi_journalier`
--

INSERT INTO `suivi_journalier` (`id`, `id_profil_sante`, `date_jour`, `poids`, `calories`, `sommeil_heures`, `nbr_pas`, `nbr_activites_sport`, `hydratation_litre`, `type_activite`, `duree_seance_minutes`, `intensite`, `commentaire_sport`, `points_resultat`, `analyse_resultat`, `analyse_commentaire`, `analysed_at`, `sport_type`, `sport_duree_minutes`, `sport_duree`, `sport_intensite`, `sport_commentaire`, `commentaire`) VALUES
(20, 15, '2026-06-05', 59.98, 150, 8.00, 11000, 1, '1.5_2L', NULL, 0, NULL, NULL, 10, 'bonne_journee', 'Great health day: +10 points.\n\nNutrition: calories look very low. Even for weight loss, avoid excessive restriction. Keep simple, complete meals: vegetables, protein, and moderate starch.\n\nHydration: very good balance. Your water intake fits a normal day.\n\nSport & recovery: your session is well supported by good sleep — a positive combination.\n\nSport: good session. Moderate, regular activity is often more sustainable than occasional intense effort.\n\nSleep: great job — sleep supports recovery and appetite control.\n\nAllergen caution: avoid foods listed on your profile: Sucre.\n\nDeficiencies: as you noted Vitamine C, Vitamine D, vary meals with nutrient-rich foods.\n\nHealth: as you noted Cholestérol, avoid extreme diet changes and progress carefully.\n\nMotivation: Well done — your day shows real progress. Keep it up; even small repeated habits make a big difference.', '2026-06-05 22:09:26', 'marche', 60, 0, 'moyenne', 'good', NULL),
(21, 15, '2026-06-04', 55.00, 150, 8.00, 11000, 1, '1.5_2L', NULL, 0, NULL, NULL, 10, 'bonne_journee', 'Great health day: +10 points.\n\nNutrition & activity: the day looks fairly balanced. Calories are reasonable and activity supports your health.\n\nHydration: very good balance. Your water intake fits a normal day.\n\nSport & recovery: your session is well supported by good sleep — a positive combination.\n\nSport: good session. Moderate, regular activity is often more sustainable than occasional intense effort.\n\nSleep: great job — sleep supports recovery and appetite control.\n\nMotivation: Well done — your day shows real progress. Keep it up; even small repeated habits make a big difference.', '2026-06-05 19:08:53', 'natation', 40, 0, 'moyenne', NULL, NULL),
(22, 15, '2026-06-03', 57.00, 200, 8.00, 11000, 1, '1.5_2L', NULL, 0, NULL, NULL, 10, 'bonne_journee', 'Great health day: +10 points.\n\nNutrition & activity: the day looks fairly balanced. Calories are reasonable and activity supports your health.\n\nHydration: very good balance. Your water intake fits a normal day.\n\nSport & recovery: your session is well supported by good sleep — a positive combination.\n\nSport: good session. Moderate, regular activity is often more sustainable than occasional intense effort.\n\nSleep: great job — sleep supports recovery and appetite control.\n\nMotivation: Well done — your day shows real progress. Keep it up; even small repeated habits make a big difference.', '2026-06-05 19:10:18', 'marche', 60, 0, 'moyenne', NULL, NULL),
(23, 15, '2026-06-02', 55.00, 150, 8.00, 10900, 1, '1.5_2L', NULL, 0, NULL, NULL, 10, 'bonne_journee', 'Great health day: +10 points.\n\nNutrition: calories look very low. Even for weight loss, avoid excessive restriction. Keep simple, complete meals: vegetables, protein, and moderate starch.\n\nHydration: very good balance. Your water intake fits a normal day.\n\nSport & recovery: your session is well supported by good sleep — a positive combination.\n\nSport: good session. Moderate, regular activity is often more sustainable than occasional intense effort.\n\nSleep: great job — sleep supports recovery and appetite control.\n\nAllergen caution: avoid foods listed on your profile: Sucre.\n\nDeficiencies: as you noted Vitamine C, Vitamine D, vary meals with nutrient-rich foods.\n\nHealth: as you noted Cholestérol, avoid extreme diet changes and progress carefully.\n\nMotivation: Well done — your day shows real progress. Keep it up; even small repeated habits make a big difference.', '2026-06-05 19:11:47', 'danse', 120, 0, 'moyenne', NULL, NULL),
(24, 15, '2026-06-01', 55.00, 150, 8.00, 10900, 1, '1.5_2L', NULL, 0, NULL, NULL, 10, 'bonne_journee', 'Great health day: +10 points.\n\nNutrition: calories look very low. Even for weight loss, avoid excessive restriction. Keep simple, complete meals: vegetables, protein, and moderate starch.\n\nHydration: very good balance. Your water intake fits a normal day.\n\nSport & recovery: your session is well supported by good sleep — a positive combination.\n\nSleep: great job — sleep supports recovery and appetite control.\n\nAllergen caution: avoid foods listed on your profile: Sucre.\n\nDeficiencies: as you noted Vitamine C, Vitamine D, vary meals with nutrient-rich foods.\n\nHealth: as you noted Cholestérol, avoid extreme diet changes and progress carefully.\n\nMotivation: Well done — your day shows real progress. Keep it up; even small repeated habits make a big difference.', '2026-06-05 19:12:18', 'yoga', 90, 0, 'elevee', NULL, NULL),
(25, 15, '2026-05-31', 55.00, 150, 8.00, 10900, 1, '1.5_2L', NULL, 0, NULL, NULL, 10, 'bonne_journee', 'Great health day: +10 points.\n\nNutrition: calories look very low. Even for weight loss, avoid excessive restriction. Keep simple, complete meals: vegetables, protein, and moderate starch.\n\nHydration: very good balance. Your water intake fits a normal day.\n\nSport & recovery: your session is well supported by good sleep — a positive combination.\n\nSport: good session. Moderate, regular activity is often more sustainable than occasional intense effort.\n\nSleep: great job — sleep supports recovery and appetite control.\n\nAllergen caution: avoid foods listed on your profile: Sucre.\n\nDeficiencies: as you noted Vitamine C, Vitamine D, vary meals with nutrient-rich foods.\n\nHealth: as you noted Cholestérol, avoid extreme diet changes and progress carefully.\n\nMotivation: Well done — your day shows real progress. Keep it up; even small repeated habits make a big difference.', '2026-06-05 19:13:20', 'velo', 180, 0, 'moyenne', NULL, NULL),
(26, 15, '2026-05-30', 55.00, 150, 8.00, 10900, 1, '1.5_2L', NULL, 0, NULL, NULL, 10, 'bonne_journee', 'Great health day: +10 points.\n\nNutrition: calories look very low. Even for weight loss, avoid excessive restriction. Keep simple, complete meals: vegetables, protein, and moderate starch.\n\nHydration: very good balance. Your water intake fits a normal day.\n\nSport & recovery: your session is well supported by good sleep — a positive combination.\n\nSleep: great job — sleep supports recovery and appetite control.\n\nAllergen caution: avoid foods listed on your profile: Sucre.\n\nDeficiencies: as you noted Vitamine C, Vitamine D, vary meals with nutrient-rich foods.\n\nHealth: as you noted Cholestérol, avoid extreme diet changes and progress carefully.\n\nMotivation: Well done — your day shows real progress. Keep it up; even small repeated habits make a big difference.', '2026-06-05 19:14:06', 'natation', 60, 0, 'elevee', NULL, NULL),
(27, 15, '2026-05-29', 55.00, 150, 8.00, 10900, 1, 'plus_2L', NULL, 0, NULL, NULL, 10, 'bonne_journee', 'Great health day: +10 points.\n\nNutrition: calories look very low. Even for weight loss, avoid excessive restriction. Keep simple, complete meals: vegetables, protein, and moderate starch.\n\nHydration: good hydration today. Especially positive if you walked or exercised.\n\nSport & recovery: your session is well supported by good sleep — a positive combination.\n\nSport: good session. Moderate, regular activity is often more sustainable than occasional intense effort.\n\nSleep: great job — sleep supports recovery and appetite control.\n\nAllergen caution: avoid foods listed on your profile: Sucre.\n\nDeficiencies: as you noted Vitamine C, Vitamine D, vary meals with nutrient-rich foods.\n\nHealth: as you noted Cholestérol, avoid extreme diet changes and progress carefully.\n\nMotivation: Well done — your day shows real progress. Keep it up; even small repeated habits make a big difference.', '2026-06-05 19:20:58', 'course', 180, 0, 'moyenne', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user_notification`
--

CREATE TABLE `user_notification` (
  `id_notification` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `type_notif` varchar(32) NOT NULL DEFAULT 'info',
  `ref_key` varchar(96) DEFAULT NULL,
  `titre` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_notification`
--

INSERT INTO `user_notification` (`id_notification`, `id_utilisateur`, `type_notif`, `ref_key`, `titre`, `message`, `lu`, `created_at`) VALUES
(39, 24, 'welcome', 'welcome', 'Bienvenue sur HappyBite', 'Bonjour happybite ! Nous sommes ravis de vous compter parmi nous. Explorez les produits, recettes et votre espace santé pour bien démarrer.', 0, '2026-06-05 16:39:28'),
(40, 25, 'welcome', 'welcome', 'Bienvenue sur HappyBite', 'Bonjour happybite ! Nous sommes ravis de vous compter parmi nous. Explorez les produits, recettes et votre espace santé pour bien démarrer.', 0, '2026-06-05 16:42:35'),
(41, 2, 'sante', 'sante_promo:2026-W23', 'Découvrez l\'espace Santé', 'Créez votre profil santé sur HappyBite : fixez vos objectifs (poids, calories, hydratation), suivez votre quotidien et recevez des conseils adaptés à votre profil.', 0, '2026-06-05 16:50:34'),
(42, 2, 'sante', 'sante_suivi_reminder:2026-06-05', 'Rappel suivi journalier', 'Cela fait plus de 24 h sans suivi journalier. Enregistrez vos calories, sommeil, pas et hydratation pour garder un œil sur vos objectifs santé.', 0, '2026-06-05 16:50:46');

-- --------------------------------------------------------

--
-- Structure de la table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_utilisateur` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `motDePasse` varchar(255) NOT NULL,
  `role` enum('admin','client','nutritionniste','fournisseur') NOT NULL DEFAULT 'client',
  `statut` enum('actif','bloqué','inactif') NOT NULL DEFAULT 'actif',
  `budget` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `referral_code` varchar(20) DEFAULT NULL,
  `referred_by` int(11) DEFAULT NULL,
  `loyalty_points` int(11) DEFAULT 0,
  `order_count` int(11) DEFAULT 0,
  `referral_count` int(11) DEFAULT 0,
  `first_order_at` datetime DEFAULT NULL,
  `first_order_rewarded` tinyint(4) DEFAULT 0,
  `reward_5_referrals` tinyint(4) DEFAULT 0,
  `reward_10_referrals` tinyint(4) DEFAULT 0,
  `reward_20_referrals` tinyint(4) DEFAULT 0,
  `face_auth_image` varchar(255) DEFAULT NULL,
  `profil-image` varchar(255) DEFAULT NULL,
  `points_challenge` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id_utilisateur`, `nom`, `prenom`, `email`, `motDePasse`, `role`, `statut`, `budget`, `description`, `created_at`, `updated_at`, `reset_token`, `reset_expires`, `referral_code`, `referred_by`, `loyalty_points`, `order_count`, `referral_count`, `first_order_at`, `first_order_rewarded`, `reward_5_referrals`, `reward_10_referrals`, `reward_20_referrals`, `face_auth_image`, `profil-image`, `points_challenge`) VALUES
(1, 'Admin', 'HappyBite', 'admin@happybite.tn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'actif', NULL, 'Compte administrateur principal', '2026-05-10 02:51:26', '2026-05-10 02:57:44', NULL, NULL, 'ADMIN001', NULL, 0, 0, 0, NULL, 0, 0, 0, 0, NULL, NULL, 0),
(2, 'Ben Ali', 'Sarra', 'sarra.benali@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'actif', 350.00, 'Cliente intéressée par alimentation saine', '2026-05-11 01:37:48', '2026-05-11 02:07:15', NULL, NULL, 'SAR001', NULL, 120, 8, 1, '2026-04-20 00:00:00', 1, 0, 0, 0, NULL, 'uploads/users pictures/istockphoto-1759448630-612x612.jpg', 0),
(3, 'Trabelsi', 'Aya', 'aya.trabelsi@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'actif', 420.00, 'Passionnée de nutrition et fitness', '2026-05-11 01:37:48', '2026-05-11 02:07:38', NULL, NULL, 'AYA002', NULL, 200, 12, 2, '2026-04-18 00:00:00', 1, 0, 0, 0, NULL, 'uploads/users pictures/photo-1611432579699-484f7990b127.png', 0),
(4, 'Mansouri', 'Youssef', 'youssef.mansouri@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'actif', 500.00, 'Sportif et amateur de repas protéinés', '2026-05-11 01:37:48', '2026-05-11 02:07:49', NULL, NULL, 'YOU003', NULL, 340, 18, 3, '2026-04-15 00:00:00', 1, 0, 0, 0, NULL, 'uploads/users pictures/close-up-portrait-of-smiling-handsome-young-caucasian-man-face-looking-at-camera-on-isolated-light-gray-studio-background-photo.jpg', 0),
(5, 'Jebali', 'Mohamed', 'mohamed.jebali@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'actif', 275.00, 'Client fidèle de HappyBite', '2026-05-11 01:37:48', '2026-05-11 02:08:18', NULL, NULL, 'MOH004', NULL, 90, 6, 0, '2026-04-22 00:00:00', 1, 0, 0, 0, NULL, 'uploads/users pictures/istockphoto-1289461335-612x612.jpg', 0),
(6, 'Ksibi', 'Amine', 'amine.ksibi@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'actif', 390.00, 'Intéressé par les produits bio', '2026-05-11 01:37:48', '2026-05-11 02:08:46', NULL, NULL, 'AMI005', NULL, 160, 9, 1, '2026-04-25 00:00:00', 1, 0, 0, 0, NULL, 'uploads/users pictures/young-bearded-man-with-striped-shirt_273609-5677.png', 0),
(24, 'fournisseur', 'happybite', 'fournisseur@happybite.tn', '$2y$10$9GnKgnjxKD2gX2r5ZAYWzeMHWB8rmN.uNUczD6ee2bpINO1bzQx8u', 'fournisseur', 'actif', 0.00, 'fournisseur de page qui ajoute des produit pour les clients', '2026-06-05 14:39:28', '2026-06-05 14:39:28', NULL, NULL, 'HB8AC5875B', NULL, 0, 0, 0, NULL, 0, 0, 0, 0, NULL, NULL, 0),
(25, 'nutritionniste', 'happybite', 'nutritionniste@happybite.tn', '$2y$10$k6W7HNMkE2TL6p7MNu2wYekiu4nwYD96eY2U9VpkSjiZ/Jn6o/OOi', 'nutritionniste', 'actif', 0.00, 'nutritionniste qui ajoute des challenges pour les participants', '2026-06-05 14:42:35', '2026-06-05 14:42:35', NULL, NULL, 'HB2B5BFFC2', NULL, 0, 0, 0, NULL, 0, 0, 0, 0, NULL, NULL, 0);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`id_categorie`);

--
-- Index pour la table `challenge`
--
ALTER TABLE `challenge`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_challenge_nutritionniste` (`nutritionnisteId`);

--
-- Index pour la table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`id_commande`),
  ADD KEY `fk_livraison` (`id_livraison`),
  ADD KEY `fk_commande_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `commande_produit`
--
ALTER TABLE `commande_produit`
  ADD PRIMARY KEY (`id_commande_produit`),
  ADD KEY `fk_commande` (`id_commande`),
  ADD KEY `fk_produit` (`id_produit`);

--
-- Index pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_commentaire_post` (`post_id`),
  ADD KEY `fk_commentaire_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `face_auth`
--
ALTER TABLE `face_auth`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_face_auth_user` (`user_id`);

--
-- Index pour la table `face_auth_logs`
--
ALTER TABLE `face_auth_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_face_auth_logs_user` (`user_id`);

--
-- Index pour la table `frigo`
--
ALTER TABLE `frigo`
  ADD PRIMARY KEY (`id_utilisateur`,`id_produit`),
  ADD KEY `fk_frigo_produit` (`id_produit`);

--
-- Index pour la table `gamification_log`
--
ALTER TABLE `gamification_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_profil_date` (`id_profil_sante`,`date_analyse`);

--
-- Index pour la table `like_participation`
--
ALTER TABLE `like_participation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_like_participation` (`participationId`,`userId`);

--
-- Index pour la table `livraison`
--
ALTER TABLE `livraison`
  ADD PRIMARY KEY (`id_livraison`),
  ADD KEY `fk_livraison_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `loyalty_referrals`
--
ALTER TABLE `loyalty_referrals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_referrer` (`referrer_id`),
  ADD KEY `fk_referee` (`referee_id`);

--
-- Index pour la table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_loyalty_user` (`user_id`);

--
-- Index pour la table `participation_challenge`
--
ALTER TABLE `participation_challenge`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_participation` (`clientId`,`challengeId`),
  ADD KEY `fk_participation_client` (`clientId`),
  ADD KEY `fk_participation_challenge` (`challengeId`);

--
-- Index pour la table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_post_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`id_produit`),
  ADD KEY `fk_produit_categorie` (`id_categorie`),
  ADD KEY `fk_produit_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `produit_roue`
--
ALTER TABLE `produit_roue`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `profil_sante`
--
ALTER TABLE `profil_sante`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `recette`
--
ALTER TABLE `recette`
  ADD PRIMARY KEY (`id_recette`);

--
-- Index pour la table `recette_produit`
--
ALTER TABLE `recette_produit`
  ADD PRIMARY KEY (`id_recette`,`id_produit`),
  ADD KEY `fk_recette_produit_produit` (`id_produit`);

--
-- Index pour la table `recompense`
--
ALTER TABLE `recompense`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recompense_client` (`clientId`),
  ADD KEY `fk_recompense_produit` (`produitRoueId`);

--
-- Index pour la table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id_utilisateur`);

--
-- Index pour la table `story`
--
ALTER TABLE `story`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_story_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `storycommentaire`
--
ALTER TABLE `storycommentaire`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_storycomment_story` (`story_id`);

--
-- Index pour la table `storylike`
--
ALTER TABLE `storylike`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_story_visitor` (`story_id`,`visitor_key`);

--
-- Index pour la table `suivi_journalier`
--
ALTER TABLE `suivi_journalier`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_profil_sante` (`id_profil_sante`);

--
-- Index pour la table `user_notification`
--
ALTER TABLE `user_notification`
  ADD PRIMARY KEY (`id_notification`),
  ADD UNIQUE KEY `idx_user_notification_ref` (`id_utilisateur`,`ref_key`),
  ADD KEY `idx_user_lu` (`id_utilisateur`,`lu`),
  ADD KEY `idx_user_created` (`id_utilisateur`,`created_at`);

--
-- Index pour la table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`preference_key`);

--
-- Index pour la table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token` (`token`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `id_categorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `challenge`
--
ALTER TABLE `challenge`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pour la table `commande`
--
ALTER TABLE `commande`
  MODIFY `id_commande` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=289;

--
-- AUTO_INCREMENT pour la table `commande_produit`
--
ALTER TABLE `commande_produit`
  MODIFY `id_commande_produit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=392;

--
-- AUTO_INCREMENT pour la table `commentaire`
--
ALTER TABLE `commentaire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `face_auth`
--
ALTER TABLE `face_auth`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `face_auth_logs`
--
ALTER TABLE `face_auth_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gamification_log`
--
ALTER TABLE `gamification_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `like_participation`
--
ALTER TABLE `like_participation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `livraison`
--
ALTER TABLE `livraison`
  MODIFY `id_livraison` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=224;

--
-- AUTO_INCREMENT pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `loyalty_referrals`
--
ALTER TABLE `loyalty_referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `participation_challenge`
--
ALTER TABLE `participation_challenge`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `post`
--
ALTER TABLE `post`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pour la table `produit`
--
ALTER TABLE `produit`
  MODIFY `id_produit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `produit_roue`
--
ALTER TABLE `produit_roue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1239;

--
-- AUTO_INCREMENT pour la table `profil_sante`
--
ALTER TABLE `profil_sante`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `recette`
--
ALTER TABLE `recette`
  MODIFY `id_recette` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `recompense`
--
ALTER TABLE `recompense`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `story`
--
ALTER TABLE `story`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `storycommentaire`
--
ALTER TABLE `storycommentaire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `storylike`
--
ALTER TABLE `storylike`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `suivi_journalier`
--
ALTER TABLE `suivi_journalier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `user_notification`
--
ALTER TABLE `user_notification`
  MODIFY `id_notification` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT pour la table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `fk_commande_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_livraison` FOREIGN KEY (`id_livraison`) REFERENCES `livraison` (`id_livraison`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `commande_produit`
--
ALTER TABLE `commande_produit`
  ADD CONSTRAINT `fk_commande` FOREIGN KEY (`id_commande`) REFERENCES `commande` (`id_commande`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_produit` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id_produit`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD CONSTRAINT `fk_commentaire_post` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_commentaire_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `face_auth`
--
ALTER TABLE `face_auth`
  ADD CONSTRAINT `fk_face_auth_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `face_auth_logs`
--
ALTER TABLE `face_auth_logs`
  ADD CONSTRAINT `fk_face_auth_logs_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `frigo`
--
ALTER TABLE `frigo`
  ADD CONSTRAINT `fk_frigo_produit` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id_produit`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_frigo_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `livraison`
--
ALTER TABLE `livraison`
  ADD CONSTRAINT `fk_livraison_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `loyalty_referrals`
--
ALTER TABLE `loyalty_referrals`
  ADD CONSTRAINT `fk_referee` FOREIGN KEY (`referee_id`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD CONSTRAINT `fk_loyalty_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `fk_post_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `produit`
--
ALTER TABLE `produit`
  ADD CONSTRAINT `fk_produit_categorie` FOREIGN KEY (`id_categorie`) REFERENCES `categorie` (`id_categorie`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_produit_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`);

--
-- Contraintes pour la table `profil_sante`
--
ALTER TABLE `profil_sante`
  ADD CONSTRAINT `fk_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `recette_produit`
--
ALTER TABLE `recette_produit`
  ADD CONSTRAINT `fk_recette_produit_produit` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id_produit`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_recette_produit_recette` FOREIGN KEY (`id_recette`) REFERENCES `recette` (`id_recette`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rp_produit` FOREIGN KEY (`id_produit`) REFERENCES `produit` (`id_produit`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rp_recette` FOREIGN KEY (`id_recette`) REFERENCES `recette` (`id_recette`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `fk_settings_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `story`
--
ALTER TABLE `story`
  ADD CONSTRAINT `fk_story_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `storycommentaire`
--
ALTER TABLE `storycommentaire`
  ADD CONSTRAINT `fk_storycomment_story` FOREIGN KEY (`story_id`) REFERENCES `story` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `storylike`
--
ALTER TABLE `storylike`
  ADD CONSTRAINT `fk_storylike_story` FOREIGN KEY (`story_id`) REFERENCES `story` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `suivi_journalier`
--
ALTER TABLE `suivi_journalier`
  ADD CONSTRAINT `fk_profil_sante` FOREIGN KEY (`id_profil_sante`) REFERENCES `profil_sante` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
