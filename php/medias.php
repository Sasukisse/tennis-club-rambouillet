<?php
require __DIR__.'/config.php';
start_session();

$PASS = get_setting('media_password');
$isLogged = is_logged_in();
$user = $isLogged ? current_user() : null;
$isAdmin = $user && ($user['role'] === 'Admin');

// Récupération des fichiers médias
$mediaDir = realpath(__DIR__.'/../img/medias');
$photos = [];
$videos = [];
if($mediaDir && is_dir($mediaDir)){
  foreach(glob($mediaDir.DIRECTORY_SEPARATOR.'*') as $path){
    if(is_file($path)){
      $name = basename($path);
      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $url = '/tennis-club-rambouillet/img/medias/'.rawurlencode($name);
      if(in_array($ext, ['jpg','jpeg','png','webp','gif'])){
        $photos[] = ['name'=>$name, 'url'=>$url];
      } else if($ext === 'mp4'){
        $videos[] = ['name'=>$name, 'url'=>$url, 'type'=>'file'];
      }
    }
  }
}

// Récupérer les vidéos YouTube
$pdo = db();
$pdo->exec("CREATE TABLE IF NOT EXISTS youtube_videos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  url VARCHAR(500) NOT NULL,
  title VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$youtubeVideos = $pdo->query('SELECT url, title FROM youtube_videos ORDER BY created_at DESC')->fetchAll();
foreach($youtubeVideos as $yt){
  $videos[] = ['name'=>$yt['title'], 'url'=>$yt['url'], 'type'=>'youtube'];
}

if(!$isAdmin){
  // Vérifier si un mot de passe a été configuré
  if(empty($PASS)){
    ?><!DOCTYPE html>
<html lang="fr"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Médias – Non configuré</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=Montserrat:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/style.css">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/medias.css">
</head><body>
  <header><div class="container header-flex">
    <a href="/tennis-club-rambouillet/index.html"><img src="/tennis-club-rambouillet/img/logo.png" alt="Logo" class="logo"></a>
  </div></header>
  <main class="centered-page">
    <div class="message-card">
      <h1>Accès aux Médias</h1>
      <p>L'accès aux médias n'a pas encore été configuré. Veuillez contacter un administrateur pour définir un mot de passe d'accès.</p>
      <a href="/tennis-club-rambouillet/index.html" class="btn">Retour à l'accueil</a>
    </div>
  </main>
  <footer><div class="container"><p>&copy; 2025 Tennis Club de Rambouillet</p></div></footer>
</body></html>
    <?php
    exit;
  }
  if($_SERVER['REQUEST_METHOD']==='POST'){
    $input = $_POST['media_pass'] ?? '';
    if(hash_equals((string)$PASS, (string)$input)){
      $_SESSION['medias_granted'] = true;
      header('Location: /tennis-club-rambouillet/php/medias.php');
      exit;
    } else {
      $error = "Mot de passe incorrect.";
    }
  }
  if(empty($_SESSION['medias_granted'])){
    ?><!DOCTYPE html>
<html lang="fr"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Médias – Accès</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=Montserrat:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/style.css">
  <script src="/tennis-club-rambouillet/js/nav-badge.js"></script>
  <style>
    .gate{padding:28px 0}
    .card{background:#fff;border:1px solid #e7dcc9;border-radius:12px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.03);max-width:460px;margin:0 auto}
    label{display:block;font-weight:600;margin-bottom:6px}
    input[type=password]{width:100%;padding:10px;border:1px solid #eadfca;border-radius:8px}
    .btn{display:inline-block;background:#F95E2D;color:#FFF8E9;font-weight:bold;padding:10px 18px;border-radius:30px;border:2px solid transparent;text-decoration:none;cursor:pointer;transition:.2s}
    .btn:hover{background:#FFF8E9;color:#F95E2D;border-color:#F95E2D}
    .muted{color:#7a6e5a}
    .error{color:#a70000;margin-top:10px}
  </style>
</head><body>
  <header><div class="container header-flex">
    <a href="/tennis-club-rambouillet/index.html"><img src="/tennis-club-rambouillet/img/logo.png" alt="Logo" class="logo"></a>
    <nav><ul>
      <li><a href="/tennis-club-rambouillet/index.html">Accueil</a></li>
      <li><a href="/tennis-club-rambouillet/le-club.html">Le Club</a></li>
      <li><a href="/tennis-club-rambouillet/inscriptions.html">Inscriptions</a></li>
      <li><a href="/tennis-club-rambouillet/terrains.php">Terrains</a></li>
      <li><a href="/tennis-club-rambouillet/php/medias.php" class="active">Médias</a></li>
      <li><a href="/tennis-club-rambouillet/boutique.html">Boutique</a></li>
      <li><a href="/tennis-club-rambouillet/contact.html">Contact</a></li>
      <li><a href="/tennis-club-rambouillet/php/dashboard.php">Mon espace</a></li>
    </ul></nav>
  </div></header>

  <section class="hero"><div class="container"><h1>Accès Médias</h1><p class="muted">Saisissez le mot de passe pour accéder à la galerie.</p></div></section>
  <main class="gate"><div class="container">
    <section class="card">
      <form method="post">
        <label for="media_pass">Mot de passe</label>
        <input type="password" id="media_pass" name="media_pass" required placeholder="Mot de passe">
        <p style="margin-top:12px"><button type="submit" class="btn">Entrer</button></p>
        <?php if(!empty($error)){ echo '<p class="error">'.htmlspecialchars($error).'</p>'; } ?>
      </form>
    </section>
  </div></main>
  <footer><div class="container"><p>&copy; 2025 Tennis Club de Rambouillet</p></div></footer>
</body></html>
<?php
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="fr"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Médias – TCR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=Montserrat:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/style.css">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/medias.css">
  <script src="/tennis-club-rambouillet/js/nav-badge.js"></script>
</head><body>
  <header><div class="container header-flex">
    <a href="/tennis-club-rambouillet/index.html"><img src="/tennis-club-rambouillet/img/logo.png" alt="Logo" class="logo"></a>
    <nav><ul>
      <li><a href="/tennis-club-rambouillet/index.html">Accueil</a></li>
      <li><a href="/tennis-club-rambouillet/le-club.html">Le Club</a></li>
      <li><a href="/tennis-club-rambouillet/inscriptions.html">Inscriptions</a></li>
      <li><a href="/tennis-club-rambouillet/terrains.php">Terrains</a></li>
      <li><a href="/tennis-club-rambouillet/php/medias.php" class="active">Médias</a></li>
      <li><a href="/tennis-club-rambouillet/boutique.html">Boutique</a></li>
      <li><a href="/tennis-club-rambouillet/contact.html">Contact</a></li>
      <li><a href="/tennis-club-rambouillet/php/dashboard.php">Mon espace</a></li>
    </ul></nav>
  </div></header>

  <section class="hero"><div class="container"><h1>Galerie Médias</h1><p>Photos et vidéos du club.</p></div></section>

  <main class="medias"><div class="container">
    <?php if($isAdmin): ?>
      <div style="margin-bottom:18px">
        <a href="/tennis-club-rambouillet/php/medias-admin.php" class="btn">Gérer les médias</a>
      </div>
    <?php endif; ?>
    <section>
      <h2>Photos</h2>
      <div class="media-grid">
        <?php if(empty($photos)): ?>
          <figure class="media-item placeholder">
            <div class="ph">+ Ajoutez vos images ici</div>
          </figure>
        <?php else: foreach($photos as $photo): ?>
          <figure class="media-item">
            <img src="<?php echo htmlspecialchars($photo['url']); ?>" alt="<?php echo htmlspecialchars($photo['name']); ?>">
            <figcaption><?php echo htmlspecialchars($photo['name']); ?></figcaption>
          </figure>
        <?php endforeach; endif; ?>
      </div>
    </section>

    <section style="margin-top:28px">
      <h2>Vidéos</h2>
      <div class="media-grid">
        <?php if(empty($videos)): ?>
          <figure class="media-item placeholder">
            <div class="ph">+ Ajoutez vos vidéos ici</div>
          </figure>
        <?php else: foreach($videos as $video): ?>
          <figure class="media-item">
            <?php if($video['type'] === 'youtube'): ?>
              <iframe width="100%" height="200" src="<?php echo htmlspecialchars($video['url']); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="border-radius:8px"></iframe>
            <?php else: ?>
              <video controls preload="metadata">
                <source src="<?php echo htmlspecialchars($video['url']); ?>" type="video/mp4">
              </video>
            <?php endif; ?>
            <figcaption><?php echo htmlspecialchars($video['name']); ?></figcaption>
          </figure>
        <?php endforeach; endif; ?>
      </div>
    </section>
  </div></main>

  <footer><div class="container"><p>&copy; 2025 Tennis Club de Rambouillet</p></div></footer>
</body></html>
