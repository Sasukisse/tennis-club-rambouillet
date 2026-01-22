<?php
// Administration des tournois
require __DIR__.'/config.php';
require_admin();

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

$tournamentError = '';
$tournamentSuccess = '';

// Récupérer l'ID du tournoi à éditer
$editId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$editTournament = null;
if($editId > 0){
  $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
  $stmt->execute([$editId]);
  $editTournament = $stmt->fetch();
}

// Gestion de la création d'un tournoi
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_tournament'){
  $title = trim($_POST['tournament_title'] ?? '');
  $description = trim($_POST['tournament_description'] ?? '');
  $start_date = trim($_POST['start_date'] ?? '');
  $end_date = trim($_POST['end_date'] ?? '');
  $location = trim($_POST['location'] ?? '');
  $max_participants = intval($_POST['max_participants'] ?? 0);
  $registration_deadline = trim($_POST['registration_deadline'] ?? '');
  
  if(empty($title) || empty($start_date)){
    $tournamentError = 'Le titre et la date de début sont obligatoires.';
  } else {
    $stmt = $pdo->prepare("INSERT INTO tournaments (title, description, start_date, end_date, location, max_participants, registration_deadline) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
      $title,
      $description ?: null,
      $start_date,
      $end_date ?: null,
      $location ?: null,
      $max_participants > 0 ? $max_participants : null,
      $registration_deadline ?: null
    ]);
    header('Location: /tennis-club-rambouillet/php/admin-tournaments.php?created=1#tournament-list');
    exit;
  }
}

// Gestion de la modification d'un tournoi
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_tournament'){
  $tournament_id = intval($_POST['tournament_id'] ?? 0);
  $title = trim($_POST['tournament_title'] ?? '');
  $description = trim($_POST['tournament_description'] ?? '');
  $start_date = trim($_POST['start_date'] ?? '');
  $end_date = trim($_POST['end_date'] ?? '');
  $location = trim($_POST['location'] ?? '');
  $max_participants = intval($_POST['max_participants'] ?? 0);
  $registration_deadline = trim($_POST['registration_deadline'] ?? '');
  
  if(empty($title) || empty($start_date)){
    $tournamentError = 'Le titre et la date de début sont obligatoires.';
  } else if($tournament_id > 0){
    $stmt = $pdo->prepare("UPDATE tournaments SET title=?, description=?, start_date=?, end_date=?, location=?, max_participants=?, registration_deadline=? WHERE id=?");
    $stmt->execute([
      $title,
      $description ?: null,
      $start_date,
      $end_date ?: null,
      $location ?: null,
      $max_participants > 0 ? $max_participants : null,
      $registration_deadline ?: null,
      $tournament_id
    ]);
    header('Location: /tennis-club-rambouillet/php/admin-tournaments.php?updated=1#tournament-list');
    exit;
  }
}

// Gestion de la suppression d'un tournoi
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_tournament'){
  $tournament_id = intval($_POST['tournament_id'] ?? 0);
  if($tournament_id > 0){
    $pdo->prepare("DELETE FROM tournaments WHERE id = ?")->execute([$tournament_id]);
    header('Location: /tennis-club-rambouillet/php/admin-tournaments.php?deleted=1#tournament-list');
    exit;
  }
}

// Récupérer tous les tournois
$tournaments = $pdo->query("SELECT * FROM tournaments ORDER BY start_date ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestion des tournois – TCR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=Montserrat:wght@400;500&display=swap" rel="stylesheet">
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
      <h1>Gestion des tournois</h1>
      <p>Créez et gérez les compétitions du club</p>
    </div>
  </section>

  <main class="admin">
    <div class="container">
      <!-- Formulaire de création/modification -->
      <section class="card" aria-label="<?= $editTournament ? 'Modifier le tournoi' : 'Créer un tournoi' ?>">
        <h3><?= $editTournament ? 'Modifier le tournoi' : 'Créer un tournoi' ?></h3>
        <?php if($tournamentError): ?>
          <p style="color:#b00020;margin:8px 0"><?= htmlspecialchars($tournamentError) ?></p>
        <?php endif; ?>
        <?php if(isset($_GET['updated'])): ?>
          <p style="color:#1b5e20;margin:8px 0">✓ Tournoi modifié avec succès.</p>
        <?php endif; ?>
        
        <form method="post" class="create-event" autocomplete="off">
          <input type="hidden" name="action" value="<?= $editTournament ? 'update_tournament' : 'create_tournament' ?>">
          <?php if($editTournament): ?>
            <input type="hidden" name="tournament_id" value="<?= $editTournament['id'] ?>">
          <?php endif; ?>
          
          <div class="form-row">
            <div class="form-field">
              <label>Titre du tournoi : *
                <input type="text" name="tournament_title" value="<?= $editTournament ? htmlspecialchars($editTournament['title']) : '' ?>" required>
              </label>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-field">
              <label>Description :
                <textarea name="tournament_description" rows="4" style="width:100%;padding:10px 12px;border:1px solid #e7dcc9;border-radius:10px;font-family:inherit;resize:vertical"><?= $editTournament ? htmlspecialchars($editTournament['description']) : '' ?></textarea>
              </label>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-field">
              <label>Date de début : *
                <input type="date" name="start_date" value="<?= $editTournament ? htmlspecialchars($editTournament['start_date']) : '' ?>" required>
              </label>
            </div>
            <div class="form-field">
              <label>Date de fin :
                <input type="date" name="end_date" value="<?= $editTournament && $editTournament['end_date'] ? htmlspecialchars($editTournament['end_date']) : '' ?>">
              </label>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-field">
              <label>Lieu :
                <input type="text" name="location" value="<?= $editTournament ? htmlspecialchars($editTournament['location']) : '' ?>" placeholder="Ex: Club de Rambouillet">
              </label>
            </div>
            <div class="form-field">
              <label>Participants max :
                <input type="number" name="max_participants" value="<?= $editTournament ? htmlspecialchars($editTournament['max_participants']) : '' ?>" min="0" placeholder="Laisser vide si illimité">
              </label>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-field">
              <label>Date limite d'inscription :
                <input type="date" name="registration_deadline" value="<?= $editTournament && $editTournament['registration_deadline'] ? htmlspecialchars($editTournament['registration_deadline']) : '' ?>">
              </label>
            </div>
          </div>
          
          <div class="form-row" style="margin-top:10px">
            <button class="btn" type="submit"><?= $editTournament ? 'Modifier le tournoi' : 'Créer le tournoi' ?></button>
            <?php if($editTournament): ?>
              <a href="/tennis-club-rambouillet/php/admin-tournaments.php" class="btn sec">Annuler</a>
            <?php endif; ?>
          </div>
        </form>
      </section>
      
      <!-- Liste des tournois -->
      <section class="card" aria-label="Liste des tournois" id="tournament-list">
        <h3>Tournois (<?= count($tournaments) ?>)</h3>
        <?php if(isset($_GET['created'])): ?>
          <p style="color:#1b5e20;margin:8px 0">✓ Tournoi créé avec succès.</p>
        <?php endif; ?>
        <?php if(isset($_GET['deleted'])): ?>
          <p style="color:#1b5e20;margin:8px 0">✓ Tournoi supprimé.</p>
        <?php endif; ?>
        
        <?php if(empty($tournaments)): ?>
          <p style="color:#666;margin-top:8px">Aucun tournoi créé.</p>
        <?php else: ?>
          <div style="margin-top:16px">
            <?php foreach($tournaments as $tournament): ?>
              <div style="background:#fff;border:1px solid #e7dcc9;border-radius:8px;padding:12px;margin-bottom:12px">
                <div style="display:flex;justify-content:space-between;align-items:start;gap:12px">
                  <div style="flex:1">
                    <h4 style="margin-bottom:4px"><?= htmlspecialchars($tournament['title']) ?></h4>
                    <?php if($tournament['description']): ?>
                      <p style="color:#666;font-size:0.95rem;margin:4px 0"><?= nl2br(htmlspecialchars($tournament['description'])) ?></p>
                    <?php endif; ?>
                    <div style="display:flex;flex-wrap:wrap;gap:16px;margin-top:8px;font-size:0.95rem;color:#555">
                      <span>📅 Début: <?= date('d/m/Y', strtotime($tournament['start_date'])) ?></span>
                      <?php if($tournament['end_date']): ?>
                        <span>📅 Fin: <?= date('d/m/Y', strtotime($tournament['end_date'])) ?></span>
                      <?php endif; ?>
                      <?php if($tournament['location']): ?>
                        <span>📍 <?= htmlspecialchars($tournament['location']) ?></span>
                      <?php endif; ?>
                      <?php if($tournament['max_participants']): ?>
                        <span>👥 Max: <?= $tournament['max_participants'] ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div style="display:flex;gap:8px;margin:0">
                    <a href="/tennis-club-rambouillet/php/admin-tournaments.php?edit=<?= $tournament['id'] ?>" class="btn" style="padding:8px 16px;font-size:0.9rem">Modifier</a>
                    <form method="post" style="margin:0">
                      <input type="hidden" name="action" value="delete_tournament">
                      <input type="hidden" name="tournament_id" value="<?= $tournament['id'] ?>">
                      <button class="btn warn" type="submit" onclick="return confirm('Supprimer ce tournoi ?')" style="padding:8px 16px;font-size:0.9rem">Supprimer</button>
                    </form>
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
    </div>
  </main>

  <footer>
    <div class="container">
      <p>&copy; 2025 Tennis Club de Rambouillet</p>
    </div>
  </footer>
</body>
</html>
