<?php
// Administration des utilisateurs
// - Protégé par require_admin()
// - Actions POST: promouvoir, rétrograder, supprimer
// - Liste les utilisateurs triés par date de création
require __DIR__.'/config.php';
require_admin();
$pdo = db();

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

  <section class="hero"><div class="container"><h1>Administration</h1><p>Gestion des comptes utilisateurs et des événements.</p></div></section>
  <main class="admin"><div class="container">
    
    <!-- Création d'événement -->
    <section class="card" aria-label="Créer un événement">
      <h3>Créer un événement</h3>
      <?php if($eventError): ?>
        <p style="color:#b00020;margin:8px 0"><?php echo htmlspecialchars($eventError); ?></p>
      <?php endif; ?>
      <?php if($eventSuccess): ?>
        <p style="color:#1b5e20;margin:8px 0"><?php echo htmlspecialchars($eventSuccess); ?></p>
      <?php endif; ?>
      
      <form method="post" class="create-event" autocomplete="off">
        <input type="hidden" name="action" value="create_event">
        <div class="form-row">
          <div class="form-field">
            <label>Titre de l'événement : *
              <input type="text" name="event_title" required>
            </label>
          </div>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label>Description :
              <textarea name="event_description" rows="3" style="width:100%;padding:10px 12px;border:1px solid #e7dcc9;border-radius:10px;font-family:inherit;resize:vertical"></textarea>
            </label>
          </div>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label>Date : *
              <input type="date" name="event_date" required>
            </label>
          </div>
          <div class="form-field">
            <label>Heure :
              <input type="time" name="event_time">
            </label>
          </div>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label>Lieu :
              <input type="text" name="event_location" placeholder="Ex: Club house, Terrain 1...">
            </label>
          </div>
        </div>
        <div class="form-row" style="margin-top:10px">
          <button class="btn" type="submit">Créer l'événement</button>
        </div>
      </form>
    </section>
    
    <!-- Liste des événements à venir -->
    <section class="card" aria-label="Événements à venir">
      <h3>Événements à venir</h3>
      <?php if(empty($events)): ?>
        <p style="color:#666;margin-top:8px">Aucun événement à venir.</p>
      <?php else: ?>
        <div style="margin-top:16px">
          <?php foreach($events as $event): ?>
            <div style="background:#fff;border:1px solid #e7dcc9;border-radius:8px;padding:12px;margin-bottom:12px">
              <div style="display:flex;justify-content:space-between;align-items:start;gap:12px">
                <div style="flex:1">
                  <h4 style="margin-bottom:4px"><?php echo htmlspecialchars($event['title']); ?></h4>
                  <?php if($event['description']): ?>
                    <p style="color:#666;font-size:0.95rem;margin:4px 0"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                  <?php endif; ?>
                  <div style="display:flex;gap:16px;margin-top:8px;font-size:0.95rem;color:#555">
                    <span>📅 <?php echo date('d/m/Y', strtotime($event['event_date'])); ?></span>
                    <?php if($event['event_time']): ?>
                      <span>🕐 <?php echo substr($event['event_time'], 0, 5); ?></span>
                    <?php endif; ?>
                    <?php if($event['location']): ?>
                      <span>📍 <?php echo htmlspecialchars($event['location']); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <form method="post" style="margin:0">
                  <input type="hidden" name="action" value="delete_event">
                  <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                  <button class="btn warn" type="submit" onclick="return confirm('Supprimer cet événement ?')" style="padding:8px 16px;font-size:0.9rem">Supprimer</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
    
    <!-- Création d'un utilisateur -->
    <section class="card" aria-label="Créer un utilisateur">
      <h3>Créer un utilisateur</h3>
      <?php if($createError){ echo '<p style="color:#b00020;margin:8px 0">'.htmlspecialchars($createError).'</p>'; } ?>
      <?php if(isset($_GET['created'])){ echo '<p style="color:#1b5e20;margin:8px 0">Utilisateur créé avec succès.</p>'; } ?>
      <form method="post" class="create-user" autocomplete="off">
        <input type="hidden" name="action" value="create">
        <div class="form-row"><div class="form-field"><label>Nom complet :<input type="text" name="full_name" required></label></div></div>
        <div class="form-row"><div class="form-field"><label>Email :<input type="email" name="email" required></label></div></div>
        <div class="form-row"><div class="form-field"><label>Mot de passe :<input type="password" name="password" required></label></div></div>
        <div class="form-row"><div class="form-field"><label>Rôle :
          <select name="role">
            <option value="member" selected>Membre</option>
            <option value="admin">Admin</option>
          </select>
        </label></div></div>
        <div class="form-row" style="margin-top:10px">
          <button class="btn" type="submit">Créer l'utilisateur</button>
        </div>
      </form>
    </section>
    <!-- Tableau des utilisateurs avec actions -->
    <table class="table" aria-label="Utilisateurs">
      <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Créé le</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($users as $us): ?>
          <tr>
            <td><?php echo htmlspecialchars($us['full_name']); ?></td>
            <td><?php echo htmlspecialchars($us['email']); ?></td>
            <td><?php echo htmlspecialchars($us['role']); ?></td>
            <td><?php echo htmlspecialchars($us['created_at']); ?></td>
            <td>
              <!-- Formulaire d'actions par ligne (POST) -->
              <!-- Formulaire d'actions par ligne (POST) avec confirmation JS -->
              <form method="post" class="actions">
                <input type="hidden" name="id" value="<?php echo intval($us['id']); ?>">
                <?php if($us['role']!=='admin'): ?>
                  <button class="btn" name="action" value="promote">Promouvoir admin</button>
                <?php else: ?>
                  <button class="btn sec" name="action" value="demote">Rétrograder membre</button>
                <?php endif; ?>
                <button class="btn warn" name="action" value="delete">Supprimer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    
    <!-- Section réservations de terrains -->
    <h2 style="margin-top:32px;margin-bottom:16px">Réservations de terrains</h2>
    
    <div class="card" style="margin-bottom:16px">
      <h3>Réservations en cours (<?php echo count($currentBookings); ?>)</h3>
      <?php if(empty($currentBookings)): ?>
        <p style="color:#666;margin-top:8px">Aucune réservation en cours.</p>
      <?php else: ?>
        <table class="table" style="margin-top:12px">
          <thead>
            <tr>
              <th>Date</th>
              <th>Terrain</th>
              <th>Horaire</th>
              <th>Utilisateur</th>
              <th>Email</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($currentBookings as $b): ?>
              <tr>
                <td><?php echo htmlspecialchars($b['date']); ?></td>
                <td><?php echo htmlspecialchars($b['court']); ?></td>
                <td><?php echo substr($b['start_time'],0,5); ?> – <?php echo substr($b['end_time'],0,5); ?></td>
                <td><?php echo htmlspecialchars($b['full_name']); ?></td>
                <td><?php echo htmlspecialchars($b['email']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
    
    <div class="card">
      <h3>Historique des réservations (<?php echo count($pastBookings); ?>)</h3>
      <?php if(empty($pastBookings)): ?>
        <p style="color:#666;margin-top:8px">Aucune réservation passée.</p>
      <?php else: ?>
        <table class="table" style="margin-top:12px;opacity:0.8">
          <thead>
            <tr>
              <th>Date</th>
              <th>Terrain</th>
              <th>Horaire</th>
              <th>Utilisateur</th>
              <th>Email</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($pastBookings as $b): ?>
              <tr>
                <td><?php echo htmlspecialchars($b['date']); ?></td>
                <td><?php echo htmlspecialchars($b['court']); ?></td>
                <td><?php echo substr($b['start_time'],0,5); ?> – <?php echo substr($b['end_time'],0,5); ?></td>
                <td><?php echo htmlspecialchars($b['full_name']); ?></td>
                <td><?php echo htmlspecialchars($b['email']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
    
    <div class="form-row" style="margin-top:16px">
      <a class="btn" href="/tennis-club-rambouillet/php/logout.php">Se déconnecter</a>
    </div>
  </div></main>

  <footer><div class="container"><p>&copy; 2025 Tennis Club de Rambouillet</p></div></footer>
  <script src="/tennis-club-rambouillet/js/admin.js"></script>
</body></html>