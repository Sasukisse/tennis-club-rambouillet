<?php
// Gestion des réservations
require __DIR__.'/config.php';
require_admin();
$pdo = db();

// Créer la table si elle n'existe pas
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
?>
<!DOCTYPE html>
<html lang="fr"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestion des réservations – TCR</title>
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

  <section class="hero"><div class="container"><h1>Gestion des réservations</h1><p><a href="/tennis-club-rambouillet/php/admin.php" style="color:#FFF8E9;text-decoration:underline">← Retour au panel admin</a></p></div></section>
  <main class="admin"><div class="container">
    
    <section class="card" style="margin-bottom:16px">
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
    </section>
    
    <section class="card">
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
    </section>
    
    <div class="form-row" style="margin-top:16px">
      <a class="btn" href="/tennis-club-rambouillet/php/admin.php">← Retour au panel admin</a>
      <a class="btn" href="/tennis-club-rambouillet/php/logout.php">Se déconnecter</a>
    </div>
  </div></main>

  <footer><div class="container"><p>&copy; 2025 Tennis Club de Rambouillet</p></div></footer>
  <script src="/tennis-club-rambouillet/js/admin.js"></script>
</body></html>
