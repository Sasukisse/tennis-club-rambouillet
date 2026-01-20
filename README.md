# Tennis Club de Rambouillet — Site Web

Ce projet est un site web complet pour le Tennis Club de Rambouillet. Il inclut des pages publiques (Accueil, Le Club, Inscriptions, Terrains, Boutique, Contact) et un espace membre/admin en PHP avec MySQL.

## Structure du projet
- `index.html` — Accueil + carrousel.
- `le-club.html` — Présentation et photos.
- `inscriptions.html` — Infos d’inscription + PDF.
- `terrains.html` — Réservation de terrains (démo, localStorage).
- `boutique.html` — Produits + panier + PayPal (démo).
- `contact.html` — Formulaire de contact (démo).
- `css/` — Styles globaux et spécifiques:
  - `style.css` (global)
  - `boutique.css`, `contact.css` (pages)
- `img/` — Images du site.
- `php/` — Espace membre/admin (PHP):
  - `config.php` (PDO, sessions, guards)
  - `register.php`, `login.php`, `logout.php`
  - `dashboard.php`, `admin.php`
- `db/schema.sql` — Schéma SQL (base `tcr`, table `users`).
- `pdf/` — Documents PDF liés aux inscriptions.

## Prérequis
- Windows + XAMPP (Apache + MySQL)
- Navigateur moderne (Chrome, Edge, Firefox)

## Installation (XAMPP)
1. Placer ce dossier dans `C:\xampp\htdocs\tennis-club-rambouillet`.
2. Démarrer `Apache` et `MySQL` depuis le panneau XAMPP.
3. Créer la base et la table:
   - Ouvrir `http://localhost/phpmyadmin/`.
   - Importer `db/schema.sql` (onglet Importer).
4. Configurer (si besoin) les accès MySQL dans `php/config.php` (`$DB_HOST`, `$DB_USER`, `$DB_PASS`).

## Démarrage
- Site public: `http://localhost/tennis-club-rambouillet/index.html`
- Espace membre: `http://localhost/tennis-club-rambouillet/php/login.php`

## Comptes et rôles
- Inscription: via `register.php`.
- Connexion: via `login.php`.
- Dashboard: `dashboard.php` (infos utilisateur).
- Administration: `admin.php` (promouvoir/rétrograder/supprimer).
- Rôle par défaut: `member`. Pour créer un admin, deux options:
  - Depuis `admin.php`: promouvoir un utilisateur.
  - Directement en SQL: `UPDATE users SET role='admin' WHERE email='...'`.

## Pages et fonctionnalités
- Terrains (`terrains.html`):
  - Réservations en localStorage (hors serveur).
  - Détection des conflits (chevauchement de créneaux) par terrain.
  - Heures d’ouverture: 08:00–22:00.
- Boutique (`boutique.html`):
  - Panier en localStorage.
  - Totaux: sous-total, livraison forfaitaire (5€ si < 50€), total.
  - Bouton démo: récapitulatif + vidage du panier.
  - PayPal: nécessite un `client-id` valide (voir ci-dessous).
- Contact (`contact.html`):
  - Formulaire démo (pas d’envoi serveur): message de confirmation et reset.

## Notes techniques
- Sessions PHP: stockent `$_SESSION['user']` avec `id`, `email`, `full_name`, `role`.
- Sécurité: mots de passe hashés (bcrypt). Pages admin protégées par `require_admin()`.
- Styles: direction artistique cohérente (Barlow Semi Condensed, Montserrat; couleurs, arrondis, ombres douces).

## Dépannage rapide
- Erreur de connexion MySQL: vérifier `php/config.php` et que MySQL est démarré.
- PayPal non affiché: vérifier le client id; le bouton démo reste disponible.
- Réservations/boutique vides: localStorage peut être vidé via les actions.

## Développement
- Les commentaires détaillent le code dans chaque fichier.
- Pour étendre:
  - Terrains: migrer localStorage vers une API PHP/MySQL.
  - Boutique: stocker les commandes en base et sécuriser les notifications PayPal (webhooks).
  - Contact: brancher un envoi serveur (mail) et anti-spam.
