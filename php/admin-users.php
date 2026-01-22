<?php
// Gestion des utilisateurs
require __DIR__.'/config.php';
require_admin();
$pdo = db();
$currentUser = current_user();

// Gestion de la création d'utilisateur
$createError = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])){
  // Empêcher un admin de modifier ou supprimer son propre compte
  if(in_array($_POST['action'], ['promote', 'demote', 'delete'])){
    $targetUserId = intval($_POST['id'] ?? 0);
    if($targetUserId === intval($currentUser['id'])){
      header('Location: /tennis-club-rambouillet/php/admin-users.php?error=own_account');
      exit;
    }
  }
  
  if($_POST['action']==='promote' && isset($_POST['id'])){
    $pdo->prepare('UPDATE users SET role="Admin" WHERE id=?')->execute([intval($_POST['id'])]);
  } else if($_POST['action']==='demote' && isset($_POST['id'])){
    $pdo->prepare('UPDATE users SET role="Membre" WHERE id=?')->execute([intval($_POST['id'])]);
  } else if($_POST['action']==='delete' && isset($_POST['id'])){
    $pdo->prepare('DELETE FROM users WHERE id=?')->execute([intval($_POST['id'])]);
  } else if($_POST['action']==='create'){
    $full_name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Membre';
    if(!$full_name || !$email || !$password){
      $createError = 'Veuillez renseigner nom, email et mot de passe.';
    } else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
      $createError = 'Email invalide.';
    } else if(!in_array($role, ['Membre','Admin'], true)){
      $createError = 'Rôle invalide.';
    } else {
      $st = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
      $st->execute([$email]);
      if($st->fetch()){
        $createError = 'Un utilisateur existe déjà avec cet email.';
      } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?,?,?,?)')
            ->execute([$email, $hash, $full_name, $role]);
        header('Location: /tennis-club-rambouillet/php/admin-users.php?created=1');
        exit;
      }
    }
  }
}

$users = $pdo->query('SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestion des utilisateurs – TCR</title>
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

  <section class="hero"><div class="container"><h1>Gestion des utilisateurs</h1><p><a href="/tennis-club-rambouillet/php/admin.php" style="color:#FFF8E9;text-decoration:underline">← Retour au panel admin</a></p></div></section>
  <main class="admin"><div class="container">
    
    <!-- Création d'un utilisateur -->
    <section class="card" aria-label="Créer un utilisateur">
      <h3>Créer un utilisateur</h3>
      <?php if($createError){ echo '<p style="color:#b00020;margin:8px 0">'.htmlspecialchars($createError).'</p>'; } ?>
      <?php if(isset($_GET['created'])){ echo '<p style="color:#1b5e20;margin:8px 0">Utilisateur créé avec succès.</p>'; } ?>
      <?php if(isset($_GET['error']) && $_GET['error'] === 'own_account'){ echo '<p style="color:#b00020;margin:8px 0">Vous ne pouvez pas modifier ou supprimer votre propre compte.</p>'; } ?>
      <form method="post" class="create-user" autocomplete="off">
        <input type="hidden" name="action" value="create">
        <div class="form-row"><div class="form-field"><label>Nom complet :<input type="text" name="full_name" required></label></div></div>
        <div class="form-row"><div class="form-field"><label>Email :<input type="email" name="email" required></label></div></div>
        <div class="form-row"><div class="form-field"><label>Mot de passe :<input type="password" name="password" required></label></div></div>
        <div class="form-row"><div class="form-field"><label>Rôle :
          <select name="role">
            <option value="Membre" selected>Membre</option>
            <option value="Admin">Admin</option>
          </select>
        </label></div></div>
        <div class="form-row" style="margin-top:10px">
          <button class="btn" type="submit">Créer l'utilisateur</button>
        </div>
      </form>
    </section>
    
    <!-- Tableau des utilisateurs -->
    <section class="card">
      <h3>Utilisateurs actuels (<?php echo count($users); ?>)</h3>
      <table class="table" style="margin-top:12px">
        <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Créé le</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach($users as $us): ?>
            <tr>
              <td><?php echo htmlspecialchars($us['full_name']); ?></td>
              <td><?php echo htmlspecialchars($us['email']); ?></td>
              <td><?php echo htmlspecialchars($us['role']); ?></td>
              <td><?php echo htmlspecialchars($us['created_at']); ?></td>
              <td>
                <?php if(intval($us['id']) === intval($currentUser['id'])): ?>
                  <span style="color:#666;font-style:italic">Moi</span>
                <?php else: ?>
                  <form method="post" class="actions">
                    <input type="hidden" name="id" value="<?php echo intval($us['id']); ?>">
                    <?php if($us['role']!=='Admin'): ?>
                      <button class="btn" name="action" value="promote">Promouvoir admin</button>
                    <?php else: ?>
                      <button class="btn sec" name="action" value="demote">Rétrograder membre</button>
                    <?php endif; ?>
                    <button class="btn warn" name="action" value="delete" onclick="return confirm('Supprimer cet utilisateur ?')">Supprimer</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
    
    <div class="form-row" style="margin-top:16px">
      <a class="btn" href="/tennis-club-rambouillet/php/admin.php">← Retour au panel admin</a>
      <a class="btn" href="/tennis-club-rambouillet/php/logout.php">Se déconnecter</a>
    </div>
  </div></main>

  <footer><div class="container"><p>&copy; 2025 Tennis Club de Rambouillet</p></div></footer>
  <script src="/tennis-club-rambouillet/js/admin.js"></script>
</body></html>
