<?php
// Administration des utilisateurs
// - Protégé par require_admin()
// - Actions POST: promouvoir, rétrograder, supprimer
// - Liste les utilisateurs triés par date de création
require __DIR__.'/config.php';
require_admin();
$pdo = db();
$currentUser = current_user();

// Créer les tables si elles n'existent pas
$pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  date DATE NOT NULL,
  court VARCHAR(50) NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_date (date),
  INDEX idx_user_date (user_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  event_date DATE NOT NULL,
  event_time TIME,
  location VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_event_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Gestion de la création d'utilisateur depuis l'admin
$createError = '';
$eventError = '';
$eventSuccess = '';

// Gestion des événements
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_event'){
  $title = trim($_POST['event_title'] ?? '');
  $description = trim($_POST['event_description'] ?? '');
  $event_date = trim($_POST['event_date'] ?? '');
  $event_time = trim($_POST['event_time'] ?? '');
  $location = trim($_POST['event_location'] ?? '');
  
  if(empty($title) || empty($event_date)){
    $eventError = 'Le titre et la date sont obligatoires.';
  } else {
    $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, location) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
      $title,
      $description ?: null,
      $event_date,
      $event_time ?: null,
      $location ?: null
    ]);
    $eventSuccess = 'Événement créé avec succès.';
  }
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_event'){
  $event_id = intval($_POST['event_id'] ?? 0);
  if($event_id > 0){
    $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$event_id]);
    $eventSuccess = 'Événement supprimé.';
  }
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])){
  // Empêcher un admin de modifier ou supprimer son propre compte
  if(in_array($_POST['action'], ['promote', 'demote', 'delete'])){
    $targetUserId = intval($_POST['id'] ?? 0);
    if($targetUserId === intval($currentUser['id'])){
      // Redirection avec message d'erreur
      header('Location: /tennis-club-rambouillet/php/admin.php?error=own_account');
      exit;
    }
  }
  
  if($_POST['action']==='promote' && isset($_POST['id'])){
    $pdo->prepare('UPDATE users SET role="Admin" WHERE id=?')->execute([intval($_POST['id'])]);
  } else if($_POST['action']==='demote' && isset($_POST['id'])){
    $pdo->prepare('UPDATE users SET role="Membre" WHERE id=?')->execute([intval($_POST['id'])]);
  } else if($_POST['action']==='delete' && isset($_POST['id'])){
    $pdo->prepare('DELETE FROM users WHERE id=?')->execute([intval($_POST['id'])]);
  } else if($_POST['action']==='create'){
    $full_name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Membre';
    if(!$full_name || !$email || !$password){
      $createError = 'Veuillez renseigner nom, email et mot de passe.';
    } else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
      $createError = 'Email invalide.';
    } else if(!in_array($role, ['Membre','Admin'], true)){
      $createError = 'Rôle invalide.';
    } else {
      // Vérifie l'unicité de l'email
      $st = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
      $st->execute([$email]);
      if($st->fetch()){
        $createError = 'Un utilisateur existe déjà avec cet email.';
      } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?,?,?,?)')
            ->execute([$email, $hash, $full_name, $role]);
        header('Location: /tennis-club-rambouillet/php/admin.php?created=1');
        exit;
      }
    }
  }
}

$users = $pdo->query('SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();

// Récupérer les réservations en cours et l'historique
$now = date('Y-m-d H:i:s');
$currentBookings = $pdo->query("
  SELECT b.*, u.full_name, u.email 
  FROM bookings b 
  JOIN users u ON b.user_id = u.id 
  WHERE CONCAT(b.date, ' ', b.end_time) > '$now' 
  ORDER BY b.date ASC, b.start_time ASC
")->fetchAll();

$pastBookings = $pdo->query("
  SELECT b.*, u.full_name, u.email 
  FROM bookings b 
  JOIN users u ON b.user_id = u.id 
  WHERE CONCAT(b.date, ' ', b.end_time) <= '$now' 
  ORDER BY b.date DESC, b.start_time DESC
")->fetchAll();

// Récupérer les événements à venir
$events = $pdo->query("
  SELECT * FROM events 
  WHERE event_date >= CURDATE() 
  ORDER BY event_date ASC, event_time ASC
")->fetchAll();

// Récupérer l'historique des événements passés
$pastEvents = $pdo->query("
  SELECT * FROM events 
  WHERE event_date < CURDATE() 
  ORDER BY event_date DESC, event_time DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin – TCR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=Montserrat:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/style.css">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/admin.css">
  <script src="/tennis-club-rambouillet/js/nav-badge.js"></script>
</head><body>
  <header><div class="container header-flex">
    <a href="/tennis-club-rambouillet/index.html"><img src="/tennis-club-rambouillet/img/logo.png" alt="Logo" class="logo"></a>
    <nav><ul>
      <li><a href="/tennis-club-rambouillet/index.html">Accueil</a></li>
      <li><a href="/tennis-club-rambouillet/le-club.html">Le Club</a></li>
      <li><a href="/tennis-club-rambouillet/inscriptions.html">Inscriptions</a></li>
      <li><a href="/tennis-club-rambouillet/terrains.php">Terrains</a></li>
      <li><a href="/tennis-club-rambouillet/php/medias.php">Médias</a></li>
      <li><a href="/tennis-club-rambouillet/boutique.html">Boutique</a></li>
      <li><a href="/tennis-club-rambouillet/contact.html">Contact</a></li>
      <li><a href="/tennis-club-rambouillet/php/dashboard.php">Mon espace</a></li>
    </ul></nav>
  </div></header>

  <section class="hero"><div class="container"><h1>Administration</h1><p>Gestion des comptes utilisateurs, des réservations et des événements.</p></div></section>
  <main class="admin"><div class="container">
    
    <!-- Navigation entre les sections -->
    <div class="admin-navigation">
      <a href="/tennis-club-rambouillet/php/admin-users.php" class="btn">Gérer les utilisateurs</a>
      <a href="/tennis-club-rambouillet/php/admin-bookings.php" class="btn">Gérer les réservations</a>
      <a href="/tennis-club-rambouillet/php/admin-events.php" class="btn">Gérer les événements</a>
      <a href="/tennis-club-rambouillet/php/admin-tournaments.php" class="btn">Gérer les tournois</a>
      <a href="/tennis-club-rambouillet/php/admin-shop.php" class="btn">Gérer la boutique</a>
    </div>
    
    <div style="margin-top:32px;text-align:center">
      <p style="color:#666;font-size:1.1rem">Sélectionnez une section ci-dessus pour commencer.</p>
    </div>
    
    <div class="form-row" style="margin-top:32px;justify-content:center">
      <a class="btn sec" href="/tennis-club-rambouillet/php/logout.php">Se déconnecter</a>
    </div>
  </div></main>

  <footer><div class="container"><p>&copy; 2025 Tennis Club de Rambouillet</p></div></footer>
  <script src="/tennis-club-rambouillet/js/admin.js"></script>
</body></html>