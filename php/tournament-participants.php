<?php
// Page pour voir les participants d'un tournoi (admin uniquement)
require __DIR__.'/config.php';
require_admin();

$pdo = db();

// Récupérer l'ID du tournoi
$tournamentId = isset($_GET['tournament_id']) ? intval($_GET['tournament_id']) : 0;

if(!$tournamentId){
  header('Location: admin-tournaments.php');
  exit;
}

// Récupérer les infos du tournoi
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = ?");
$stmt->execute([$tournamentId]);
$tournament = $stmt->fetch();

if(!$tournament){
  header('Location: admin-tournaments.php');
  exit;
}

// Récupérer les participants
$stmt = $pdo->prepare("
  SELECT u.id, u.full_name, u.email, tp.created_at as participation_date
  FROM tournament_participants tp
  JOIN users u ON tp.user_id = u.id
  WHERE tp.tournament_id = ?
  ORDER BY tp.created_at DESC
");
$stmt->execute([$tournamentId]);
$participants = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Participants - <?= htmlspecialchars($tournament['title']) ?></title>
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
      <h1>Participants au tournoi</h1>
    </div>
  </section>

  <main class="admin">
    <div class="container">
      <section class="card">
        <h2 style="color: #F95E2D; margin-bottom: 20px;"><?= htmlspecialchars($tournament['title']) ?></h2>
        <div style="display: flex; gap: 24px; flex-wrap: wrap; margin-bottom: 20px; padding: 16px; background: #fff3df; border-radius: 8px;">
          <div><strong>📅 Date de début :</strong> <?= date('d/m/Y', strtotime($tournament['start_date'])) ?></div>
          <?php if($tournament['end_date']): ?>
            <div><strong>📅 Date de fin :</strong> <?= date('d/m/Y', strtotime($tournament['end_date'])) ?></div>
          <?php endif; ?>
          <?php if($tournament['location']): ?>
            <div><strong>📍 Lieu :</strong> <?= htmlspecialchars($tournament['location']) ?></div>
          <?php endif; ?>
          <div><strong>👥 Participants :</strong> <?= count($participants) ?><?= $tournament['max_participants'] ? ' / ' . $tournament['max_participants'] : '' ?></div>
          <?php if($tournament['registration_deadline']): ?>
            <div><strong>⏰ Limite d'inscription :</strong> <?= date('d/m/Y', strtotime($tournament['registration_deadline'])) ?></div>
          <?php endif; ?>
        </div>

        <?php if(count($participants) > 0): ?>
          <table class="table">
            <thead>
              <tr>
                <th>Nom complet</th>
                <th>Email</th>
                <th>Date d'inscription</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($participants as $p): ?>
                <tr>
                  <td><?= htmlspecialchars($p['full_name']) ?></td>
                  <td><?= htmlspecialchars($p['email']) ?></td>
                  <td><?= date('d/m/Y à H:i', strtotime($p['participation_date'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p style="text-align: center; color: #666; padding: 40px;">Aucun participant pour le moment.</p>
        <?php endif; ?>

        <div style="margin-top: 30px; text-align: center;">
          <a href="admin-tournaments.php" class="btn">Retour aux tournois</a>
        </div>
      </section>
    </div>
  </main>
</body>
</html>
