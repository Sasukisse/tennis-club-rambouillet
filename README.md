# Tennis Club de Rambouillet - Site Web

Ce projet est un site web complet pour le Tennis Club de Rambouillet, réalisé dans le cadre du BTS SIO.  
Il comprend un site public ainsi qu'un espace membre et administrateur développé en PHP avec une base de données MySQL.

---

## Prérequis

Avant de commencer, assurez-vous d'avoir installé les logiciels suivants sur votre machine :

- **XAMPP** (inclut Apache et MySQL) : https://www.apachefriends.org/fr/index.html
- Un navigateur web récent (Chrome, Firefox, Edge…)

---

## Étape 1 - Récupérer le projet

1. Télécharger le fichier .ZIP
2. Renommer le dossier `tennis-club-rambouillet`
3. Déplacer le dossier pour qu'il soit accessible via ce chemin → `C:\xampp\htdocs\tennis-club-rambouillet`

## Étape 2 - Démarrer XAMPP

1. Ouvrez **XAMPP Control Panel** depuis le menu Démarrer ou le Bureau.
2. Cliquez sur **Start** en face de **Apache**.
3. Cliquez sur **Start** en face de **MySQL**.

Les deux services doivent afficher un fond vert, indiquant qu'ils sont bien démarrés.

---

## Étape 3 - Importer la base de données

1. Ouvrez votre navigateur et rendez-vous à l'adresse suivante :

```
http://localhost/phpmyadmin
```

2. Dans le menu de gauche, cliquez sur **Nouvelle base de données**.
3. Dans le champ **Nom de base de données**, saisissez exactement :

```
nafi7014_tennis
```

4. Cliquez sur **Créer**.
5. La base apparaît dans le menu de gauche - cliquez dessus pour la sélectionner.
6. Cliquez sur l'onglet **Importer** en haut de la page.
7. Cliquez sur **Choisir un fichier**, puis sélectionnez le fichier suivant :

```
C:\xampp\htdocs\tennis-club-rambouillet\db\schema.sql
```

8. Faites défiler la page vers le bas et cliquez sur **Importer**.

Toutes les tables et les données sont maintenant en place.

---

## Étape 4 - Accéder au site

Ouvrez votre navigateur et rendez-vous à l'adresse :

```
http://localhost/tennis-club-rambouillet
```

La page d'accueil du Tennis Club de Rambouillet doit s'afficher.

---

## Étape 5 - Se connecter avec le compte administrateur

Un compte administrateur est déjà présent dans la base de données importée.

Rendez-vous sur la page de connexion :

```
http://localhost/tennis-club-rambouillet/php/login.php
```

Utilisez les identifiants suivants :

| Champ         | Valeur                  |
|---------------|-------------------------|
| **Email**     | `jurys.sio@gmail.com`   |
| **Mot de passe** | `Azerty123`          |

Ce compte donne accès à l'ensemble des fonctionnalités d'administration du site.

---

## Pages principales

| Page                  | Adresse                                                                 |
|-----------------------|-------------------------------------------------------------------------|
| Accueil               | `http://localhost/tennis-club-rambouillet/`                             |
| Connexion             | `http://localhost/tennis-club-rambouillet/php/login.php`                |
| Tableau de bord       | `http://localhost/tennis-club-rambouillet/php/dashboard.php`            |
| Administration        | `http://localhost/tennis-club-rambouillet/php/admin.php`                |
| Boutique              | `http://localhost/tennis-club-rambouillet/boutique.html`                |
| Réservation terrains  | `http://localhost/tennis-club-rambouillet/terrains.php`                 |
| Médias                | `http://localhost/tennis-club-rambouillet/medias.html`                  |
| Contact               | `http://localhost/tennis-club-rambouillet/contact.html`                 |

---

## Structure du projet

```
tennis-club-rambouillet/
├── index.html            # Page d'accueil
├── le-club.html          # Présentation du club
├── inscriptions.html     # Informations et tarifs d'inscription
├── terrains.php          # Réservation de terrains
├── boutique.html         # Boutique en ligne
├── medias.html           # Galerie photos et vidéos
├── contact.html          # Formulaire de contact
├── css/                  # Feuilles de style
├── js/                   # Scripts JavaScript
├── img/                  # Images du site
├── pdf/                  # Documents PDF
├── php/                  # Back-end PHP (authentification, API, admin)
│   ├── config.php        # Configuration base de données et sessions
│   ├── login.php         # Connexion
│   ├── register.php      # Inscription
│   ├── dashboard.php     # Espace membre
│   └── admin.php         # Interface d'administration
└── db/
    └── schema.sql        # Dump complet de la base de données
```

---

## Résolution des problèmes courants

**Apache ou MySQL ne démarrent pas dans XAMPP**
→ Redémarrez votre machine et réessayez. Si le problème persiste, un autre logiciel occupe peut-être le port 80 (souvent Skype ou IIS).

**Erreur de connexion à la base de données**
→ Vérifiez que le service MySQL est bien démarré (fond vert dans XAMPP).

**Page "Not Found" (404)**
→ Vérifiez que le dossier du projet est bien nommé `tennis-club-rambouillet` dans `C:\xampp\htdocs\`.
