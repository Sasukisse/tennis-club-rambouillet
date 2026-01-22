<?php
// Page des compétitions et tournois à venir
require __DIR__.'/config.php';

$pdo = db();

// Créer la table des tournois si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS tournaments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  start_date DATE NOT NULL,
  end_date DATE,
  location VARCHAR(255),
  max_participants INT,
  registration_deadline DATE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_start_date (start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Récupérer les tournois à venir (cette année et suivantes)
$currentYear = date('Y');
$tournaments = $pdo->query("
  SELECT * FROM tournaments 
  WHERE YEAR(start_date) >= $currentYear
  ORDER BY start_date ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Compétitions & Tournois - TCR</title>
  <link href="https://fonts.googleapis.com/css?family=Barlow+Semi+Condensed:600,700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/style.css">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/admin.css">
  <script src="/tennis-club-rambouillet/js/nav-badge.js"></script>
</head>
<body>
  <header>
    <div class="container header-flex">
      <a href="/tennis-club-rambouillet/index.html"><img src="/tennis-club-rambouillet/img/logo.png" alt="Logo" class="logo"></a>
      <nav>
        <ul>
          <li><a href="/tennis-club-rambouillet/index.html">Accueil</a></li>
          <li><a href="/tennis-club-rambouillet/le-club.html">Le Club</a></li>
          <li><a href="/tennis-club-rambouillet/inscriptions.html">Inscriptions</a></li>
          <li><a href="/tennis-club-rambouillet/terrains.php">Terrains</a></li>
          <li><a href="/tennis-club-rambouillet/php/medias.php">Médias</a></li>
          <li><a href="/tennis-club-rambouillet/boutique.html">Boutique</a></li>
          <li><a href="/tennis-club-rambouillet/contact.html">Contact</a></li>
          <li><a href="/tennis-club-rambouillet/php/dashboard.php">Mon espace</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <section class="hero">
    <div class="container">
      <h1>Compétitions & Tournois</h1>
      <p>Découvrez les tournois à venir organisés par le Tennis Club de Rambouillet</p>
    </div>
  </section>

  <main class="admin">
    <div class="container">
      <?php if(empty($tournaments)): ?>
        <div class="card">
          <p style="text-align:center;color:#666;padding:40px">Aucun tournoi prévu pour le moment. Revenez bientôt !</p>
        </div>
      <?php else: ?>
        <?php foreach($tournaments as $tournament): ?>
          <div class="card" style="margin-bottom:20px">
            <h2 style="color:#F95E2D;margin-bottom:12px"><?= htmlspecialchars($tournament['title']) ?></h2>
            
            <?php if($tournament['description']): ?>
              <p style="color:#666;margin-bottom:16px;line-height:1.6"><?= nl2br(htmlspecialchars($tournament['description'])) ?></p>
            <?php endif; ?>
            
            <div style="display:flex;flex-wrap:wrap;gap:20px;margin-top:16px;padding:16px;background:#fff3df;border-radius:8px">
              <div>
                <strong>📅 Date de début :</strong> <?= date('d/m/Y', strtotime($tournament['start_date'])) ?>
              </div>
              
              <?php if($tournament['end_date']): ?>
                <div>
                  <strong>📅 Date de fin :</strong> <?= date('d/m/Y', strtotime($tournament['end_date'])) ?>
                </div>
              <?php endif; ?>
              
              <?php if($tournament['location']): ?>
                <div>
                  <strong>📍 Lieu :</strong> <?= htmlspecialchars($tournament['location']) ?>
                </div>
              <?php endif; ?>
              
              <?php if($tournament['max_participants']): ?>
                <div>
                  <strong>👥 Participants max :</strong> <?= $tournament['max_participants'] ?>
                </div>
              <?php endif; ?>
              
              <?php if($tournament['registration_deadline']): ?>
                <div>
                  <strong>⏰ Date limite d'inscription :</strong> <?= date('d/m/Y', strtotime($tournament['registration_deadline'])) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <footer>
    <div class="container">
      <p>&copy; 2025 Tennis Club de Rambouillet</p>
    </div>
  </footer>
</body>
</html>
