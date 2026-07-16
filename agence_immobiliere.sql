-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 15 juil. 2026 à 18:14
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
-- Base de données : `agence_immobiliere`
--

-- --------------------------------------------------------

--
-- Structure de la table `biens`
--

CREATE TABLE `biens` (
  `id_bien` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `adresse` varchar(255) NOT NULL,
  `prix_mensuel` decimal(12,2) NOT NULL,
  `superficie` decimal(10,2) NOT NULL,
  `nb_chambres` int(11) NOT NULL DEFAULT 0,
  `nb_pieces` int(11) NOT NULL DEFAULT 0,
  `type` enum('Appartement','Villa') NOT NULL,
  `date_ajout` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `biens`
--

INSERT INTO `biens` (`id_bien`, `titre`, `description`, `adresse`, `prix_mensuel`, `superficie`, `nb_chambres`, `nb_pieces`, `type`, `date_ajout`) VALUES
(1, 'Appartement moderne aux Almadies', 'Bel appartement lumineux avec vue mer, proche commodites.', 'Almadies, Dakar', 350000.00, 85.00, 2, 4, 'Appartement', '2026-07-11 10:34:55'),
(2, 'Villa avec piscine a Saly', 'Grande villa avec jardin et piscine, ideale pour familles.', 'Saly Portudal, Mbour', 750000.00, 220.00, 4, 7, 'Villa', '2026-07-11 10:34:55'),
(3, 'Studio meuble au Plateau', 'Studio fonctionnel en centre-ville, ideal jeune actif.', 'Plateau, Dakar', 180000.00, 35.00, 1, 2, 'Appartement', '2026-07-11 10:34:55');

-- --------------------------------------------------------

--
-- Structure de la table `favoris`
--

CREATE TABLE `favoris` (
  `id_client` int(11) NOT NULL,
  `id_bien` int(11) NOT NULL,
  `date_ajout` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `images`
--

CREATE TABLE `images` (
  `id_image` int(11) NOT NULL,
  `chemin_fichier` varchar(255) NOT NULL,
  `is_principal` tinyint(1) NOT NULL DEFAULT 0,
  `id_bien` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `images`
--

INSERT INTO `images` (`id_image`, `chemin_fichier`, `is_principal`, `id_bien`) VALUES
(1, 'appartement_default.jpg', 1, 1),
(2, 'villa_default.jpg', 1, 2),
(3, 'appartement_default.jpg', 1, 3);

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

CREATE TABLE `reservations` (
  `id_reservation` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `statut` enum('En attente','Valid├®','Annul├®') NOT NULL DEFAULT 'En attente',
  `date_demande` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_client` int(11) NOT NULL,
  `id_bien` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id_user` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('Client','Commercial') NOT NULL DEFAULT 'Client',
  `statut` enum('Actif','Inactif') NOT NULL DEFAULT 'Actif',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_user`, `nom`, `prenom`, `email`, `telephone`, `mot_de_passe`, `role`, `statut`, `date_creation`) VALUES
(1, 'Aidara', 'Oumou', 'admin@immoagence.sn', '770000000', '$2y$10$7tdXyDgSOeittNxd8Tt1.uEAS8IXrRazA98ivJIiJzD6kmnc6OGSW', 'Commercial', 'Actif', '2026-07-11 10:34:55'),
(2, 'Diop', 'Awa', 'client@test.sn', '771111111', '$2y$10$7tdXyDgSOeittNxd8Tt1.uEAS8IXrRazA98ivJIiJzD6kmnc6OGSW', 'Client', 'Actif', '2026-07-11 10:34:55');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `biens`
--
ALTER TABLE `biens`
  ADD PRIMARY KEY (`id_bien`);

--
-- Index pour la table `favoris`
--
ALTER TABLE `favoris`
  ADD PRIMARY KEY (`id_client`,`id_bien`),
  ADD KEY `fk_favoris_bien` (`id_bien`);

--
-- Index pour la table `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id_image`),
  ADD KEY `fk_images_bien` (`id_bien`);

--
-- Index pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id_reservation`),
  ADD KEY `fk_resa_client` (`id_client`),
  ADD KEY `fk_resa_bien` (`id_bien`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `biens`
--
ALTER TABLE `biens`
  MODIFY `id_bien` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `images`
--
ALTER TABLE `images`
  MODIFY `id_image` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id_reservation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `favoris`
--
ALTER TABLE `favoris`
  ADD CONSTRAINT `fk_favoris_bien` FOREIGN KEY (`id_bien`) REFERENCES `biens` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_favoris_client` FOREIGN KEY (`id_client`) REFERENCES `utilisateurs` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `fk_images_bien` FOREIGN KEY (`id_bien`) REFERENCES `biens` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_resa_bien` FOREIGN KEY (`id_bien`) REFERENCES `biens` (`id_bien`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resa_client` FOREIGN KEY (`id_client`) REFERENCES `utilisateurs` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
