<?php
// Espace personnel
// - Protégé par require_login()
// - Affiche les informations de l'utilisateur courant
// - Si role=admin, affiche un accès vers l'administration
require __DIR__.'/config.php';
require_login();
$u = current_user();
// Prépare la persistance des informations personnelles (adresse)
$profileMsg = '';
$profileErr = '';
try{
  $pdo = db();
  $pdo->exec("CREATE TABLE IF NOT EXISTS user_profiles (
    user_id INT PRIMARY KEY,
    address_line VARCHAR(255),
    zip VARCHAR(10),
    city VARCHAR(128),
    CONSTRAINT fk_user_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $profile = ['address_line'=>'','zip'=>'','city'=>''];
  $st = $pdo->prepare('SELECT address_line, zip, city FROM user_profiles WHERE user_id=? LIMIT 1');
  $st->execute([intval($u['id'])]);
  $row = $st->fetch();
  if($row){ $profile = $row; }
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='update_address'){
    $address = trim($_POST['address_line'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $city = trim($_POST['city'] ?? '');
    if(mb_strlen($address) > 255) $address = mb_substr($address,0,255);
    if(mb_strlen($zip) > 10) $zip = mb_substr($zip,0,10);
    if(mb_strlen($city) > 128) $city = mb_substr($city,0,128);
    $zipValid = preg_match('/^\d{5}$/', $zip) === 1;
    $cityValid = preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ' . "\\'" . ' \-]{2,128}$/u', $city) === 1;
    if(!$zipValid || !$cityValid){
      $profileErr = 'Code postal: 5 chiffres · Ville: lettres/espaces uniquement.';
      $profile = ['address_line'=>$address,'zip'=>$zip,'city'=>$city];
    } else {
      $st = $pdo->prepare('INSERT INTO user_profiles (user_id,address_line,zip,city) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE address_line=VALUES(address_line), zip=VALUES(zip), city=VALUES(city)');
      $st->execute([intval($u['id']), $address, $zip, $city]);
      $profileMsg = 'Informations mises à jour.';
      $profile = ['address_line'=>$address,'zip'=>$zip,'city'=>$city];
    }
  }
}catch(Throwable $e){ /* silence côté UI */ }
?>
<!DOCTYPE html>
<html lang="fr"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mon espace – TCR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=Montserrat:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/style.css">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/dashboard.css">
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

  <section class="hero"><div class="container"><h1>Bonjour, <?php echo htmlspecialchars($u['full_name']); ?></h1><p>Bienvenue dans votre espace.</p></div></section>
  <main class="dash"><div class="container grid">
    
    <!-- Carte: événements à venir -->
    <section class="card">
      <h3>Événements à venir</h3>
      <div id="events-container">
        <p style="color:#666;margin-top:8px">Chargement...</p>
      </div>
    </section>
    
    <!-- Carte: informations de l'utilisateur -->
    <section class="card"><h3>Mes informations</h3>
      <div class="info-item"><span class="info-label">Email:</span><span><?php echo htmlspecialchars($u['email']); ?></span></div>
      <div class="info-item"><span class="info-label">Rôle:</span><span><?php echo htmlspecialchars($u['role']); ?></span></div>
      <div style="margin-top:20px">
        <h4 style="margin-bottom:12px">Mon adresse</h4>
        <?php if(!empty($profileMsg)){ echo '<p style="color:#1b5e20;margin:6px 0">'.htmlspecialchars($profileMsg).'</p>'; } ?>
        <?php if(!empty($profileErr)){ echo '<p style="color:#b00020;margin:6px 0">'.htmlspecialchars($profileErr).'</p>'; } ?>
        <form method="post" autocomplete="off">
          <input type="hidden" name="action" value="update_address">
          <div class="form-field" style="margin-bottom:12px">
            <label>Adresse</label>
            <input type="text" name="address_line" value="<?php echo htmlspecialchars($profile['address_line'] ?? ''); ?>" placeholder="N°, Rue" required>
          </div>
          <div style="display:grid;grid-template-columns:120px 1fr;gap:12px">
            <div class="form-field">
              <label>Code postal</label>
              <input type="text" name="zip" value="<?php echo htmlspecialchars($profile['zip'] ?? ''); ?>" placeholder="78120" required inputmode="numeric" maxlength="5" pattern="\d{5}" title="5 chiffres">
            </div>
            <div class="form-field">
              <label>Ville</label>
              <input type="text" name="city" value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>" placeholder="Rambouillet" required pattern="^[A-Za-zÀ-ÖØ-öø-ÿ' -]{2,128}$" title="Lettres, espaces, apostrophes et tirets">
            </div>
          </div>
          <div style="margin-top:14px"><button class="btn" type="submit">Enregistrer</button></div>
        </form>
      </div>
      <div style="margin-top:12px"><a class="btn" href="/tennis-club-rambouillet/php/logout.php">Se déconnecter</a></div>
    </section>
    
    <!-- Carte réservations de terrains -->
    <section class="card">
      <h3>Mes réservations de terrains</h3>
      <div id="reservations-container">
        <div style="margin-top:12px">
          <h4 style="font-size:1rem;font-weight:600;margin-bottom:8px">Réservations en cours</h4>
          <div id="current-reservations" style="display:flex;flex-direction:column;gap:8px"></div>
        </div>
        <div style="margin-top:20px">
          <h4 style="font-size:1rem;font-weight:600;margin-bottom:8px">Historique</h4>
          <div id="past-reservations" style="display:flex;flex-direction:column;gap:8px"></div>
        </div>
      </div>
    </section>
    
    <?php if($u['role']==='Admin'): ?>
    <!-- Carte admin visible uniquement pour les administrateurs -->
    <section class="card"><h3>Administration</h3>
      <p style="margin-top:8px">Gérer les utilisateurs du club.</p>
      <div style="margin-top:14px"><a class="btn" href="/tennis-club-rambouillet/php/admin.php">Ouvrir le panel admin</a></div>
    </section>
    <?php endif; ?>
  </div></main>

  <footer><div class="container"><p>&copy; 2025 Tennis Club de Rambouillet - Tous droits réservés</p></div></footer>
  
  <script>
    // Charger et afficher les événements à venir
    (async function(){
      try{
        const res = await fetch('/tennis-club-rambouillet/php/events-api.php');
        const json = await res.json();
        
        const container = document.getElementById('events-container');
        
        if(!json.success || !json.events || json.events.length === 0){
          container.innerHTML = '<p style="color:#666;font-size:0.95rem">Aucun événement à venir.</p>';
          return;
        }
        
        const events = json.events;
        const userId = <?php echo $u['id']; ?>;
        
        container.innerHTML = events.map(e => {
          const date = new Date(e.event_date);
          const dateStr = date.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
          const isParticipating = Boolean(Number(e.is_participating));
          const participantsCount = e.participants_count || 0;
          
          let html = '<div style="background:#f9f9f9;border:1px solid #e7dcc9;border-radius:8px;padding:12px;margin-bottom:12px;position:relative">';
          
          // Bouton Je participe en haut à droite
          html += '<div style="position:absolute;top:12px;right:12px">';
          html += '<button class="btn-participate" data-event-id="' + e.id + '" data-participating="' + isParticipating + '" style="padding:6px 14px;font-size:0.85rem;background:' + (isParticipating ? '#1b5e20' : '#F95E2D') + ';color:#FFF8E9;border:none;border-radius:20px;cursor:pointer;font-family:Montserrat,Arial,sans-serif;font-weight:bold;transition:all 0.2s">';
          html += isParticipating ? 'Annuler' : 'Je participe';
          html += '</button></div>';
          
          html += '<h4 style="margin-bottom:4px;color:#F95E2D;padding-right:130px">' + e.title + '</h4>';
          if(e.description){
            html += '<p style="color:#666;font-size:0.95rem;margin:4px 0">' + e.description.replace(/\n/g, '<br>') + '</p>';
          }
          html += '<div style="display:flex;flex-wrap:wrap;gap:16px;margin-top:8px;font-size:0.95rem;color:#555">';
          html += '<span>📅 ' + dateStr + '</span>';
          if(e.event_time){
            html += '<span>🕐 ' + e.event_time.substring(0, 5) + '</span>';
          }
          if(e.location){
            html += '<span>📍 ' + e.location + '</span>';
          }
          html += '<span>👥 ' + participantsCount + ' participant(s)</span>';
          html += '</div></div>';
          return html;
        }).join('');
        
        // Ajouter les gestionnaires d'événements pour les boutons
        document.querySelectorAll('.btn-participate').forEach(btn => {
          btn.addEventListener('click', async function(){
            const eventId = this.dataset.eventId;
            const isParticipating = this.dataset.participating === 'true';
            const button = this;
            
            // Log pour debug
            console.log('Clic sur bouton - Event ID:', eventId, 'Participe actuellement:', isParticipating, 'Action:', isParticipating ? 'leave' : 'join');
            
            // Désactiver le bouton pendant la requête
            button.disabled = true;
            button.style.opacity = '0.6';
            button.style.cursor = 'wait';
            
            try{
              const res = await fetch('/tennis-club-rambouillet/php/event-participate.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                  event_id: eventId,
                  action: isParticipating ? 'leave' : 'join'
                })
              });
              
              const result = await res.json();
              console.log('Réponse API:', result);
              
              if(result.success){
                // Recharger la page pour avoir l'état exact de la base de données
                location.reload();
              } else {
                alert(result.error || 'Erreur lors de la mise à jour');
                button.style.opacity = '1';
                button.style.cursor = 'pointer';
                button.disabled = false;
              }
            }catch(e){
              console.error('Erreur:', e);
              alert('Erreur de connexion');
              button.style.opacity = '1';
              button.style.cursor = 'pointer';
              button.disabled = false;
            }
          });
        });
      }catch(e){
        console.error('Erreur de chargement des événements:', e);
        document.getElementById('events-container').innerHTML = '<p style="color:#b00020">Erreur de chargement.</p>';
      }
    })();
    
    // Charger et afficher les réservations de terrains depuis l'API
    (async function(){
      try{
        const res = await fetch('/tennis-club-rambouillet/php/bookings-api.php');
        const json = await res.json();
        
        if(!json.success) return;
        
        const data = json.bookings;
        const now = new Date();
        
        // Comparer date ET heure de fin pour déterminer si c'est passé
        const current = data.filter(r => {
          const endDateTime = new Date(r.date + 'T' + r.end + ':00');
          return endDateTime > now;
        }).sort((a,b)=> a.date.localeCompare(b.date) || a.start.localeCompare(b.start));
        
        const past = data.filter(r => {
          const endDateTime = new Date(r.date + 'T' + r.end + ':00');
          return endDateTime <= now;
        }).sort((a,b)=> b.date.localeCompare(a.date) || b.start.localeCompare(a.start));
        
        const currentDiv = document.getElementById('current-reservations');
        const pastDiv = document.getElementById('past-reservations');
        
        if(current.length === 0){
          currentDiv.innerHTML = '<p style="color:#666;font-size:0.95rem">Aucune réservation en cours.</p>';
        } else {
          currentDiv.innerHTML = current.map(r => 
            '<div style="padding:10px;background:#f9f9f9;border:1px solid #e7dcc9;border-radius:8px">' +
            '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">' +
            '<div><strong>' + r.court + '</strong> • ' + r.date + ' • ' + r.start + ' – ' + r.end + '</div>' +
            '</div></div>'
          ).join('');
        }
        
        if(past.length === 0){
          pastDiv.innerHTML = '<p style="color:#666;font-size:0.95rem">Aucune réservation passée.</p>';
        } else {
          pastDiv.innerHTML = past.map(r => 
            '<div style="padding:10px;background:#f9f9f9;border:1px solid #e7dcc9;border-radius:8px;opacity:0.7">' +
            '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">' +
            '<div><strong>' + r.court + '</strong> • ' + r.date + ' • ' + r.start + ' – ' + r.end + '</div>' +
            '</div></div>'
          ).join('');
        }
      }catch(e){
        console.error('Erreur de chargement des réservations:', e);
      }
    })();
  </script>
</body></html>
