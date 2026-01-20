<?php
// Page de connexion
// - Valide email/mot de passe
// - Vérifie le couple dans la base
// - Stocke l’utilisateur en session et redirige vers le dashboard
require __DIR__.'/config.php';
start_session();
// Redirige vers le dashboard si déjà connecté (via session ou cookie remember)
if(is_logged_in()){
  header('Location: /tennis-club-rambouillet/php/dashboard.php');
  exit;
}

$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $email = strtolower(trim($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';
  if(!$email || !$password){ $error = 'Renseignez vos identifiants.'; }
  else {
    try{
      $pdo = db();
      $stmt = $pdo->prepare('SELECT id,email,password_hash,full_name,role FROM users WHERE email=? LIMIT 1');
      $stmt->execute([$email]);
      $u = $stmt->fetch();
      if(!$u || !password_verify($password, $u['password_hash'])){
        $error = 'Email ou mot de passe incorrect.';
      } else {
        $_SESSION['user'] = ['id'=>$u['id'],'email'=>$u['email'],'full_name'=>$u['full_name'],'role'=>$u['role']];
        // Active la persistance de connexion (remember me)
        remember_login($u['id']);
        header('Location: /tennis-club-rambouillet/php/dashboard.php'); exit;
      }
    } catch(Throwable $e){ $error = 'Erreur serveur.'; }
  }
}
?>
<!DOCTYPE html>
<html lang="fr"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion – TCR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=Montserrat:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/style.css">
  <!-- Styles spécifiques à la page de connexion -->
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/login.css">
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
      <li><a href="/tennis-club-rambouillet/php/dashboard.php" class="active">Mon espace</a></li>
    </ul></nav>
  </div></header>

  <section class="hero"><div class="container"><h1>Se connecter</h1><p>Accédez à votre espace membre.</p></div></section>
  <!-- Zone principale: formulaire de connexion -->
  <main class="auth"><div class="container">
    <section class="card">
      <!-- Formulaire de connexion: saisie email + mot de passe -->
      <form method="post" autocomplete="off">
        <div class="form-row">
          <div class="form-field"><label>Email :<input type="email" name="email" required></label></div>
        </div>
        <div class="form-row" style="margin-top:8px">
          <div class="form-field"><label>Mot de passe :<input type="password" name="password" required></label></div>
        </div>
        <div class="form-row" style="margin-top:14px"><button class="btn" type="submit">Connexion</button>
          <a class="btn" href="/tennis-club-rambouillet/php/register.php" style="background:#202226;color:#FFF8E9">Créer un compte</a></div>
        <?php if(isset($_GET['registered'])){ echo '<p style="text-align:center;color:#1b5e20;margin-top:12px">Compte créé. Vous pouvez vous connecter.</p>'; } ?>
        <!-- Affichage de l'erreur d'authentification si présente -->
        <?php if($error){ echo '<p class="error">'.htmlspecialchars($error).'</p>'; } ?>
      </form>
    </section>
  </div></main>
  <!-- Pied de page -->
  <footer><div class="container"><p>&copy; 2025 Tennis Club de Rambouillet</p></div></footer>
  <!-- Script spécifique à la page de connexion (petits comportements côté client) -->
  <script src="/tennis-club-rambouillet/js/login.js"></script>
</body></html>