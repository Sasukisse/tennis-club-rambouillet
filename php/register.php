<?php
// Page d'inscription
// - Valide les champs (nom, email, mot de passe)
// - Vérifie l'unicité de l'email
// - Hash le mot de passe (bcrypt)
// - Crée l'utilisateur puis redirige vers la connexion
require __DIR__.'/config.php';
start_session();

$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $full_name = trim($_POST['full_name'] ?? '');
  $email = strtolower(trim($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';
  $password_confirm = $_POST['password_confirm'] ?? '';
  if(!$full_name || !$email || !$password){
    $error = 'Veuillez remplir tous les champs.';
  } else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    $error = 'Email invalide.';
  } else if($password !== $password_confirm){
    $error = 'Les mots de passe ne correspondent pas.';
  } else {
    try{
      $pdo = db();
      $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
      $stmt->execute([$email]);
      if($stmt->fetch()){
        $error = 'Un compte existe déjà avec cet email.';
      } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare('INSERT INTO users (email, password_hash, full_name) VALUES (?,?,?)')
            ->execute([$email, $hash, $full_name]);
        header('Location: /tennis-club-rambouillet/php/login.php?registered=1');
        exit;
      }
    } catch(Throwable $e){ $error = 'Erreur serveur.'; }
  }
}
?>
<!DOCTYPE html>
<html lang="fr"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inscription – TCR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=Montserrat:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/style.css">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/register.css">
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
      <li><a href="/tennis-club-rambouillet/contact.html">Contact</a></li>
      <li><a href="/tennis-club-rambouillet/boutique.html">Boutique</a></li>
      <li><a href="/tennis-club-rambouillet/php/dashboard.php">Mon espace</a></li>
    </ul></nav>
  </div></header>

  <section class="hero"><div class="container"><h1>Créer un compte</h1><p>Accédez à votre espace membre.</p></div></section>
  <main class="auth"><div class="container">
    <section class="card">
      <!-- Formulaire d'inscription -->
      <form method="post" autocomplete="off">
        <div class="form-row">
          <div class="form-field"><label>Nom complet :<input type="text" name="full_name" required></label></div>
        </div>
        <div class="form-row">
          <div class="form-field"><label>Email :<input type="email" name="email" required></label></div>
        </div>
        <div class="form-row"><div class="form-field"><label>Mot de passe :<input type="password" name="password" required></label></div></div>
        <div class="form-row"><div class="form-field"><label>Confirmer le mot de passe :<input type="password" name="password_confirm" required></label></div></div>
        <div class="form-row" style="margin-top:14px"><button class="btn" type="submit">S'inscrire</button>
          <a class="btn" href="/tennis-club-rambouillet/php/login.php" style="background:#202226;color:#FFF8E9">Se connecter</a></div>
        <!-- Affichage des erreurs éventuelles -->
        <?php if($error){ echo '<p class="error">'.htmlspecialchars($error).'</p>'; } ?>
      </form>
    </section>
  </div></main>
  <footer><div class="container"><p>&copy; 2025 Tennis Club de Rambouillet</p></div></footer>
  <script src="/tennis-club-rambouillet/js/register.js"></script>
</body></html>
