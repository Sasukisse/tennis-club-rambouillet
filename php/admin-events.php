<?php
// Gestion des événements
require __DIR__.'/config.php';
require_admin();
$pdo = db();

// Créer la table si elle n'existe pas
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
  <title>Gestion des événements – TCR</title>
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

  <section class="hero"><div class="container"><h1>Gestion des événements</h1><p><a href="/tennis-club-rambouillet/php/admin.php" style="color:#FFF8E9;text-decoration:underline">← Retour au panel admin</a></p></div></section>
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
      <h3>Événements en cours (<?php echo count($events); ?>)</h3>
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
    
    <!-- Historique des événements -->
    <section class="card" aria-label="Historique des événements">
      <h3>Historique des événements (<?php echo count($pastEvents); ?>)</h3>
      <?php if(empty($pastEvents)): ?>
        <p style="color:#666;margin-top:8px">Aucun événement passé.</p>
      <?php else: ?>
        <div style="margin-top:16px;opacity:0.8">
          <?php foreach($pastEvents as $event): ?>
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
              </div>
            </div>
          <?php endforeach; ?>
        </div>
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
