--
-- Schéma SQL du Tennis Club de Rambouillet
--
-- Base: tcr (utf8mb4 pour bien gérer les accents/emoji)
CREATE DATABASE IF NOT EXISTS tcr CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE tcr;

-- Table des utilisateurs
-- Champs:
--  - email: unique, identifiant de connexion
--  - password_hash: mot de passe haché (bcrypt)
--  - full_name: nom affiché
--  - role: 'member' ou 'admin' pour les permissions
--  - created_at/updated_at: dates de suivi
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(190) NOT NULL,
  role ENUM('Membre','Admin') NOT NULL DEFAULT 'Membre',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL
) ENGINE=InnoDB;

-- Table des événements à venir
-- Champs:
--  - title: titre de l'événement
--  - description: description détaillée (optionnel)
--  - event_date: date de l'événement
--  - event_time: heure de l'événement (optionnel)
--  - location: lieu de l'événement (optionnel)
--  - created_at: date de création
CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  event_date DATE NOT NULL,
  event_time TIME,
  location VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_event_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;