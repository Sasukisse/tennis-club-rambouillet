<?php
// Admin Médias: réservé aux administrateurs
require __DIR__.'/config.php';
require_login();
$u = current_user();
if($u['role'] !== 'Admin'){
  header('HTTP/1.1 403 Forbidden');
  echo '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><title>Accès refusé</title></head><body><p>Accès réservé aux administrateurs.</p></body></html>';
  exit;
}

$mediaDir = realpath(__DIR__.'/../img/medias');
if(!$mediaDir){
  $mediaDir = __DIR__.'/../img/medias';
  @mkdir($mediaDir, 0775, true);
}

// Créer la table pour les vidéos YouTube
$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS youtube_videos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  url VARCHAR(500) NOT NULL,
  title VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg = '';
$currentPass = get_setting('media_password');
$passNotSet = empty($currentPass);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  if(isset($_POST['action']) && $_POST['action']==='set_pass'){
    $new = trim($_POST['media_pass'] ?? '');
    if($new===''){
      $msg = 'Mot de passe vide non autorisé.';
    } else if(mb_strlen($new) > 100){
      $msg = 'Mot de passe trop long.';
    } else if(mb_strlen($new) < 6){
      $msg = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
      if(set_setting('media_password', $new)){
        $msg = 'Mot de passe mis à jour avec succès.';
        $passNotSet = false;
      } else {
        $msg = 'Échec de la mise à jour du mot de passe.';
      }
    }
  }
  if(isset($_POST['action']) && $_POST['action']==='upload' && isset($_FILES['file'])){
    $f = $_FILES['file'];
    if($f['error'] === UPLOAD_ERR_OK){
      $name = preg_replace('/[^a-zA-Z0-9._-]/','_', basename($f['name']));
      $target = $mediaDir . DIRECTORY_SEPARATOR . $name;
      $mime = mime_content_type($f['tmp_name']);
      $allowed = ['image/jpeg','image/png','image/webp','video/mp4','image/gif'];
      if(!in_array($mime, $allowed)){
        $msg = 'Type de fichier non autorisé.';
      } else if(move_uploaded_file($f['tmp_name'], $target)){
        $msg = 'Fichier téléchargé: ' . htmlspecialchars($name);
      } else {
        $msg = 'Échec du téléchargement.';
      }
    } else {
      $msg = 'Erreur d\'upload.';
    }
  }
  if(isset($_POST['action']) && $_POST['action']==='delete' && isset($_POST['name'])){
    $name = basename($_POST['name']);
    $target = $mediaDir . DIRECTORY_SEPARATOR . $name;
    if(is_file($target)){
      if(@unlink($target)){
        $msg = 'Fichier supprimé: ' . htmlspecialchars($name);
      } else {
        $msg = 'Suppression impossible.';
      }
    } else {
      $msg = 'Fichier introuvable.';
    }
  }
  if(isset($_POST['action']) && $_POST['action']==='add_youtube'){
    $url = trim($_POST['youtube_url'] ?? '');
    $title = trim($_POST['youtube_title'] ?? '');
    if(empty($url)){
      $msg = 'URL YouTube requise.';
    } else {
      // Extraire l'ID YouTube
      $videoId = '';
      if(preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)){
        $videoId = $matches[1];
        $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
        $st = $pdo->prepare('INSERT INTO youtube_videos (url, title) VALUES (?, ?)');
        $st->execute([$embedUrl, $title ?: 'Vidéo YouTube']);
        $msg = 'Vidéo YouTube ajoutée.';
      } else {
        $msg = 'URL YouTube invalide.';
      }
    }
  }
  if(isset($_POST['action']) && $_POST['action']==='delete_youtube' && isset($_POST['id'])){
    $pdo->prepare('DELETE FROM youtube_videos WHERE id=?')->execute([intval($_POST['id'])]);
    $msg = 'Vidéo YouTube supprimée.';
  }
}

$items = [];
foreach((array)glob($mediaDir.DIRECTORY_SEPARATOR.'*') as $path){
  if(is_file($path)){
    $items[] = basename($path);
  }
}

// Récupérer les vidéos YouTube
$youtubeVideos = $pdo->query('SELECT id, url, title, created_at FROM youtube_videos ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Médias – TCR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=Montserrat:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/style.css">
  <script src="/tennis-club-rambouillet/js/nav-badge.js"></script>
  <style>
    .admin{padding:28px 0}
    .card{background:#fff;border:1px solid #e7dcc9;border-radius:12px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.03);margin-bottom:18px}
    .card h3{font-size:1.17rem;font-weight:600;margin-bottom:12px}
    .grid{display:grid;grid-template-columns:1fr;gap:18px}
    @media(min-width:860px){.grid{grid-template-columns:1fr 2fr}}
    .media-list{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
    .media-item{border:1px solid #eadfca;border-radius:8px;padding:8px}
    .media-item img, .media-item video{max-width:100%;display:block;margin-bottom:6px}
    .btn{display:inline-block;background:#F95E2D;color:#FFF8E9;font-family:'Montserrat',Arial,sans-serif;font-size:1rem;font-weight:bold;padding:10px 18px;border-radius:30px;border:2px solid transparent;text-decoration:none;cursor:pointer;transition:.2s}
    .btn:hover{background:#FFF8E9;color:#F95E2D;border-color:#F95E2D;transform:scale(1.03)}
    .muted{color:#7a6e5a;font-size:0.95rem}
    label{font-size:1rem}
    input[type="password"], input[type="file"]{font-size:1rem;font-family:'Montserrat',Arial,sans-serif}
    input[type="password"]{width:100%;padding:10px 12px;border:1px solid #e7dcc9;border-radius:10px}
  </style>
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

  <section class="hero"><div class="container"><h1>Administration des médias</h1><p class="muted">Réservé aux administrateurs</p></div></section>
  <main class="admin"><div class="container grid">
    <section class="card">
      <h3>Mot de passe d'accès</h3>
      <?php if($passNotSet): ?>
        <div style="background:#fff3cd;color:#856404;border:1px solid #ffeaa7;padding:12px;border-radius:8px;margin-bottom:12px">
          <strong>⚠️ Configuration requise :</strong> Aucun mot de passe n'est configuré. Les visiteurs non-administrateurs ne peuvent pas accéder aux médias tant qu'un mot de passe n'est pas défini.
        </div>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="action" value="set_pass">
        <p class="muted">Définir le mot de passe demandé aux non-admins pour accéder à la galerie Médias (minimum 6 caractères).</p>
        <p style="margin-top:8px">
          <label for="media_pass" class="muted">Nouveau mot de passe :</label>
          <input type="password" id="media_pass" name="media_pass" required minlength="6">
        </p>
        <p style="margin-top:12px"><button class="btn" type="submit"><?php echo $passNotSet ? 'Définir le mot de passe' : 'Modifier le mot de passe'; ?></button></p>
      </form>
    </section>
    <section class="card">
      <h3>Ajouter un fichier</h3>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <p class="muted">Types autorisés: JPG, PNG, WEBP, GIF, MP4</p>
        <p style="margin-top:8px"><input type="file" name="file" accept="image/*,video/mp4" required></p>
        <p style="margin-top:12px"><button class="btn" type="submit">Téléverser</button></p>
      </form>
      <?php if($msg){ echo '<p class="muted" style="margin-top:10px">'.htmlspecialchars($msg).'</p>'; } ?>
    </section>
    <section class="card">
      <h3>Ajouter une vidéo YouTube</h3>
      <form method="post">
        <input type="hidden" name="action" value="add_youtube">
        <p class="muted">Collez l'URL d'une vidéo YouTube (ex: https://www.youtube.com/watch?v=xxxxx)</p>
        <p style="margin-top:8px">
          <label for="youtube_url" class="muted">URL YouTube :</label>
          <input type="text" id="youtube_url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." required style="width:100%;padding:10px 12px;border:1px solid #e7dcc9;border-radius:10px;font-family:'Montserrat',Arial,sans-serif;font-size:1rem">
        </p>
        <p style="margin-top:8px">
          <label for="youtube_title" class="muted">Titre (optionnel) :</label>
          <input type="text" id="youtube_title" name="youtube_title" placeholder="Titre de la vidéo" style="width:100%;padding:10px 12px;border:1px solid #e7dcc9;border-radius:10px;font-family:'Montserrat',Arial,sans-serif;font-size:1rem">
        </p>
        <p style="margin-top:12px"><button class="btn" type="submit">Ajouter</button></p>
      </form>
    </section>
    <section class="card">
      <h3>Vidéos YouTube</h3>
      <div class="media-list">
        <?php if(empty($youtubeVideos)): ?>
          <p class="muted">Aucune vidéo YouTube pour le moment.</p>
        <?php else: foreach($youtubeVideos as $yt): ?>
          <div class="media-item">
            <iframe width="100%" height="150" src="<?php echo htmlspecialchars($yt['url']); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            <p class="muted" style="margin:6px 0;font-size:0.9rem"><?php echo htmlspecialchars($yt['title']); ?></p>
            <form method="post">
              <input type="hidden" name="action" value="delete_youtube">
              <input type="hidden" name="id" value="<?php echo intval($yt['id']); ?>">
              <button class="btn" type="submit">Supprimer</button>
            </form>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </section>
    <section class="card">
      <h3>Fichiers présents</h3>
      <div class="media-list">
        <?php if(empty($items)): ?>
          <p class="muted">Aucun fichier pour le moment.</p>
        <?php else: foreach($items as $name): $path = '/tennis-club-rambouillet/img/medias/'.rawurlencode($name); $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION)); ?>
          <div class="media-item">
            <?php if(in_array($ext, ['jpg','jpeg','png','webp','gif'])): ?>
              <img src="<?php echo $path; ?>" alt="<?php echo htmlspecialchars($name); ?>">
            <?php elseif($ext==='mp4'): ?>
              <video controls preload="metadata"><source src="<?php echo $path; ?>" type="video/mp4"></video>
            <?php else: ?>
              <p class="muted">Fichier: <?php echo htmlspecialchars($name); ?></p>
            <?php endif; ?>
            <form method="post" style="margin-top:6px">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="name" value="<?php echo htmlspecialchars($name); ?>">
              <button class="btn" type="submit">Supprimer</button>
            </form>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </section>
  </div></main>

  <footer><div class="container"><p>&copy; 2025 Tennis Club de Rambouillet</p></div></footer>
</body></html>
