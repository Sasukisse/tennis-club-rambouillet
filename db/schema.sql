-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mar. 05 mai 2026 à 11:22
-- Version du serveur : 11.4.10-MariaDB
-- Version de PHP : 8.4.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `nafi7014_tennis`
--

CREATE DATABASE IF NOT EXISTS `nafi7014_tennis` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `nafi7014_tennis`;

-- --------------------------------------------------------

--
-- Structure de la table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `court` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `date`, `court`, `start_time`, `end_time`, `created_at`) VALUES
(1, 1, '2026-05-13', 'Salle 1', '18:00:00', '20:00:00', '2026-04-08 08:26:20');

-- --------------------------------------------------------

--
-- Structure de la table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `event_date`, `event_time`, `location`, `created_at`) VALUES
(1, 'Soirée raclette', 'Bonjour à tous,\r\n\r\nNous organisons une soirée raclette le samedi 21 février 2026.\r\n\r\nNous vous attendons nombreux !\r\n\r\nLe club.', '2026-02-21', '20:00:00', 'Club House', '2026-02-19 12:15:00'),
(2, 'Soirée barbecue', 'Bonjour à tous,\r\n\r\nNous organisons une soirée barbecue le vendredi 10 mars 2026 au club afin de passer un moment conviviable tous ensemble.\r\n\r\nLe club', '2026-04-10', '20:00:00', 'Club house', '2026-04-08 10:16:08');

-- --------------------------------------------------------

--
-- Structure de la table `event_participants`
--

CREATE TABLE `event_participants` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `event_participants`
--

INSERT INTO `event_participants` (`id`, `event_id`, `user_id`, `created_at`) VALUES
(1, 1, 1, '2026-02-19 11:15:13'),
(2, 2, 1, '2026-04-08 08:16:15'),
(3, 2, 4, '2026-04-08 08:20:28'),
(4, 2, 3, '2026-04-08 08:20:45'),
(5, 2, 2, '2026-04-08 08:21:10'),
(6, 2, 5, '2026-04-08 08:21:24');

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `has_variants` tinyint(1) DEFAULT 0,
  `variant_types` varchar(100) DEFAULT NULL,
  `color_options` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `description`, `price`, `image`, `has_variants`, `variant_types`, `color_options`, `stock`, `active`, `created_at`) VALUES
(2, 'BALLESTENNISBABOLAT', '4 Balles de Tennis', '4 Balles de Tennis tout-terrain de la marque Babolat.', 7.00, NULL, 0, NULL, NULL, NULL, 1, '2026-02-19 12:30:56'),
(3, 'PULL-MULTICOLORS', 'Pull du club', '100% Coton.\r\nUne seule couleur disponible (noir)', 35.00, NULL, 1, 'size', NULL, NULL, 1, '2026-04-08 10:25:41');

-- --------------------------------------------------------

--
-- Structure de la table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `display_order`, `is_primary`, `created_at`) VALUES
(1, 2, 'prod_6996f470c468e.jpg', 0, 1, '2026-02-19 12:30:56'),
(2, 2, 'prod_6996f470c569d.jpg', 1, 0, '2026-02-19 12:30:56'),
(3, 3, 'prod_69d61105e57cb.jpg', 0, 1, '2026-04-08 10:25:41');

-- --------------------------------------------------------

--
-- Structure de la table `settings`
--

CREATE TABLE `settings` (
  `k` varchar(64) NOT NULL,
  `v` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tournaments`
--

CREATE TABLE `tournaments` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `registration_deadline` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tournaments`
--

INSERT INTO `tournaments` (`id`, `title`, `description`, `start_date`, `end_date`, `location`, `max_participants`, `registration_deadline`, `created_at`) VALUES
(1, 'Tournoi du printemps', 'Bonjour à tous,\r\n\r\nComme chaque année nous organisons notre tournoi du printemps.\r\n\r\nLa cotisation pour participer est de 10€. Elle permettra de financer les balles de tennis ainsi que les lots des gagnants.\r\n\r\nLe club.', '2026-05-02', '2026-05-23', 'Club de Rambouillet', NULL, '2026-04-25', '2026-02-19 12:23:03');

-- --------------------------------------------------------

--
-- Structure de la table `tournament_participants`
--

CREATE TABLE `tournament_participants` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tournament_participants`
--

INSERT INTO `tournament_participants` (`id`, `tournament_id`, `user_id`, `created_at`) VALUES
(4, 1, 1, '2026-04-08 10:17:56'),
(5, 1, 4, '2026-04-08 10:20:32'),
(6, 1, 3, '2026-04-08 10:20:49');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(190) NOT NULL,
  `role` enum('Membre','Admin') NOT NULL DEFAULT 'Membre',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `full_name`, `role`, `created_at`, `updated_at`) VALUES
(1, 'samuel.tardy78@gmail.com', '$2y$10$bx2RIS82yZtAt7AZrOF3beACKtylk.83R.UskISldqfqznk.E/2CS', 'Samuel TARDY', 'Admin', '2026-02-19 12:10:10', NULL),
(2, 'romain@user.fr', '$2y$10$GxEBmUeZdh7CMS3vmPASmu3AVQ0cKJhdDjdh8Y//o3Vu.slYEM3CC', 'Romain Sanjivy', 'Membre', '2026-04-08 10:18:40', NULL),
(3, 'noemie@user.fr', '$2y$10$yU.ouVaZE9YaNzm0vW7sfuxCJJf2Aq0FQqB3prGg5Ld1RxP2wjpsq', 'Noémie Leite', 'Membre', '2026-04-08 10:19:17', NULL),
(4, 'franck@user.fr', '$2y$10$gOKbnfhNrqDS2IFYnR6CK.sIOlfe/KeLUtbBNVgdO7gq1cXAdB0PC', 'Franck TARDY', 'Membre', '2026-04-08 10:19:43', NULL),
(5, 'christine@user.fr', '$2y$10$pVhNyIDXSvIinZvTGAuio.QdgN2cqpOS.JiL4OkSyRGko0RiFNyqG', 'Christine TARDY', 'Membre', '2026-04-08 10:20:06', NULL),
(6, 'jurys.sio@gmail.com', '$2y$10$4AYp7Np4NbYFPvzsYTJTV.rirPsU4WbxZbobo63FcP4W0AHeL9aPy', 'Jury SIO', 'Admin', '2026-05-05 11:14:47', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user_carts`
--

CREATE TABLE `user_carts` (
  `user_id` int(11) NOT NULL,
  `items` longtext NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user_carts`
--

INSERT INTO `user_carts` (`user_id`, `items`, `updated_at`) VALUES
(1, '[]', '2026-04-09 12:37:12');

-- --------------------------------------------------------

--
-- Structure de la table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `user_id` int(11) NOT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `city` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user_profiles`
--

INSERT INTO `user_profiles` (`user_id`, `address_line`, `zip`, `city`) VALUES
(1, '65 rue d\'Angiviller', '78120', 'Rambouillet');

-- --------------------------------------------------------

--
-- Structure de la table `youtube_videos`
--

CREATE TABLE `youtube_videos` (
  `id` int(11) NOT NULL,
  `url` varchar(500) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_user_date` (`user_id`,`date`);

--
-- Index pour la table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_date` (`event_date`);

--
-- Index pour la table `event_participants`
--
ALTER TABLE `event_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_participation` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_sku` (`sku`),
  ADD KEY `idx_active` (`active`);

--
-- Index pour la table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Index pour la table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`k`);

--
-- Index pour la table `tournaments`
--
ALTER TABLE `tournaments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_start_date` (`start_date`);

--
-- Index pour la table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_participation` (`tournament_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `user_carts`
--
ALTER TABLE `user_carts`
  ADD PRIMARY KEY (`user_id`);

--
-- Index pour la table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Index pour la table `youtube_videos`
--
ALTER TABLE `youtube_videos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `event_participants`
--
ALTER TABLE `event_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `youtube_videos`
--
ALTER TABLE `youtube_videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `event_participants`
--
ALTER TABLE `event_participants`
  ADD CONSTRAINT `event_participants_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `tournament_participants`
--
ALTER TABLE `tournament_participants`
  ADD CONSTRAINT `tournament_participants_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tournament_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_carts`
--
ALTER TABLE `user_carts`
  ADD CONSTRAINT `fk_user_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `fk_user_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;