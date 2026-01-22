<?php
// Page pour voir les participants d'un événement (admin uniquement)
require __DIR__.'/config.php';
require_admin();

$pdo = db();

// Récupérer l'ID de l'événement
$eventId = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if(!$eventId){
  header('Location: admin-events.php');
  exit;
}

// Récupérer les infos de l'événement
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if(!$event){
  header('Location: admin-events.php');
  exit;
}

// Récupérer les participants
$stmt = $pdo->prepare("
  SELECT u.id, u.full_name, u.email, ep.created_at as participation_date
  FROM event_participants ep
  JOIN users u ON ep.user_id = u.id
  WHERE ep.event_id = ?
  ORDER BY ep.created_at DESC
");
$stmt->execute([$eventId]);
$participants = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Participants - <?= htmlspecialchars($event['title']) ?></title>
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
      <h1>Participants à l'événement</h1>
    </div>
  </section>

  <main class="admin">
    <div class="container">
      <section class="card">
        <h2 style="color: #F95E2D; margin-bottom: 20px;"><?= htmlspecialchars($event['title']) ?></h2>
        <div style="display: flex; gap: 24px; flex-wrap: wrap; margin-bottom: 20px; padding: 16px; background: #fff3df; border-radius: 8px;">
          <div><strong>📅 Date :</strong> <?= date('d/m/Y', strtotime($event['event_date'])) ?></div>
          <?php if($event['event_time']): ?>
            <div><strong>🕐 Heure :</strong> <?= date('H:i', strtotime($event['event_time'])) ?></div>
          <?php endif; ?>
          <?php if($event['location']): ?>
            <div><strong>📍 Lieu :</strong> <?= htmlspecialchars($event['location']) ?></div>
          <?php endif; ?>
          <div><strong>👥 Participants :</strong> <?= count($participants) ?></div>
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
          <a href="admin-events.php" class="btn">Retour aux événements</a>
        </div>
      </section>
    </div>
  </main>
</body>
</html>
