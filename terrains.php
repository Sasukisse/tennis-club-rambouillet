<?php
require __DIR__.'/php/config.php';
start_session();
$isLogged = is_logged_in();
$user = null;
$userId = 0;
if($isLogged){
  $user = current_user();
  $userId = $user['id'];
}
?>
<!DOCTYPE html>
<!--
  Page Terrains (réservations)
  Rôle: permettre de réserver un terrain en choisissant la date, le terrain, l'heure de début et la durée.
  Stockage: les réservations sont enregistrées dans localStorage par utilisateur.
  Conflits: la logique JS empêche de réserver un créneau déjà pris sur un même terrain.
-->
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Terrains – Tennis Club de Rambouillet</title>
  <link href="https://fonts.googleapis.com/css?family=Barlow+Semi+Condensed:600,700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/terrains.css" />
</head>
<body>
  <header>
    <div class="container header-flex">
      <a href="index.html">
        <img src="img/logo.png" alt="Logo Tennis Club de Rambouillet" class="logo">
      </a>
      <nav>
        <ul>
          <li><a href="index.html">Accueil</a></li>
          <li><a href="le-club.html">Le Club</a></li>
          <li><a href="inscriptions.html">Inscriptions</a></li>
          <li><a href="terrains.php" class="active" aria-current="page">Terrains</a></li>
          <li><a href="/tennis-club-rambouillet/php/medias.php">Médias</a></li>
          <li><a href="boutique.html">Boutique</a></li>
          <li><a href="contact.html">Contact</a></li>
          <li><a href="php/dashboard.php">Mon espace</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <section class="hero">
    <div class="container">
      <h1>Réserver un terrain</h1>
      <p>Choisissez une date, un terrain et un créneau pour jouer.</p>
    </div>
  </section>

  <!-- Contenu principal: formulaire et liste de réservations -->
  <main class="booking">
    <div class="container">
      <?php if(!$isLogged): ?>
        <!-- Formulaire de connexion pour les utilisateurs non connectés -->
        <div style="max-width:650px;margin:0 auto;background:white;border:1px solid #e7dcc9;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.03)">
          <h2 style="text-align:center;margin-bottom:16px">Connectez-vous pour réserver</h2>
          <p style="text-align:center;color:#666;margin-bottom:20px">Vous devez être connecté pour effectuer une réservation.</p>
          <div style="text-align:center;margin-top:20px">
            <a href="php/login.php" class="btn">Se connecter</a>
            <a href="php/register.php" class="btn" style="background:#202226;margin-left:12px">S'inscrire</a>
          </div>
        </div>
      <?php else: ?>
      <h2 style="text-align:center; margin-bottom:12px;">Nouvelle réservation</h2>
      <form class="form" id="booking-form" autocomplete="off">
        <label>
          Date
          <input type="date" id="bk-date" required>
        </label>
        <label>
          Terrain
          <select id="bk-court" required>
            <option value="Extérieur 1">Extérieur 1</option>
            <option value="Extérieur 2">Extérieur 2</option>
            <option value="Padel">Padel</option>
            <option value="Salle 1">Salle 1</option>
            <option value="Salle 2">Salle 2</option>
            <option value="Salle 3">Salle 3</option>
          </select>
        </label>
        <label>
          Début
          <select id="bk-start" required></select>
        </label>
        <label>
          Durée
          <select id="bk-duration" required>
            <option value="60">1 h</option>
            <option value="90">1 h 30</option>
            <option value="120">2 h</option>
          </select>
        </label>
        <div class="btn-row">
          <button type="submit" class="btn">Réserver</button>
          <button type="button" id="btn-clear-day" class="btn" title="Effacer mes réservations du jour">Effacer la journée</button>
        </div>
      </form>

      <p class="notice">Démonstration hors-ligne: les réservations sont stockées dans votre navigateur.</p>

      <section class="list" aria-label="Mes réservations">
        <h2 style="text-align:center; margin-bottom:12px;">Mes réservations</h2>
        <div id="res-list"></div>
      </section>
      <?php endif; ?>
    </div>
  </main>

  <footer>
    <div class="container">
      <p>&copy; 2025 Tennis Club de Rambouillet — Tous droits réservés</p>
    </div>
  </footer>

  <?php if($isLogged): ?>
  <script>
    // === Réservations (API + Base de données) ===
    const OPEN_HOUR = 8;  // 08:00
    const CLOSE_HOUR = 22; // 22:00
    let bookingsData = [];

    async function loadFromAPI(){
      try{
        const res = await fetch('/tennis-club-rambouillet/php/bookings-api.php');
        const json = await res.json();
        if(json.success){
          bookingsData = json.bookings;
          renderList();
        }
      }catch(e){
        console.error('Erreur de chargement:', e);
      }
    }

    function formatHM(h, m=0){
      return String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0');
    }

    function toMinutes(hhmm){
      const [h,m] = hhmm.split(':').map(Number);
      return h*60+m;
    }

    function todayStr(){
      const d = new Date();
      const yyyy = d.getFullYear();
      const mm = String(d.getMonth()+1).padStart(2,'0');
      const dd = String(d.getDate()).padStart(2,'0');
      return `${yyyy}-${mm}-${dd}`;
    }

    // Remplit la liste des heures de début (de OPEN_HOUR à CLOSE_HOUR-1)
    function populateStartSelect(){
      const sel = document.getElementById('bk-start');
      sel.innerHTML = '';
      for(let h=OPEN_HOUR; h<=CLOSE_HOUR-1; h++){
        const t = formatHM(h,0);
        const opt = document.createElement('option');
        opt.value = t; opt.textContent = t; sel.appendChild(opt);
      }
    }

    // Affiche la liste des réservations en les triant (date, terrain, heure)
    function renderList(){
      const list = document.getElementById('res-list');
      if(bookingsData.length === 0){ list.innerHTML = `<p class="empty">Aucune réservation enregistrée.</p>`; return; }
      list.innerHTML = bookingsData
        .sort((a,b)=> a.date.localeCompare(b.date) || a.court.localeCompare(b.court) || a.start.localeCompare(b.start))
        .map((r)=>{
          return `
          <div class="res-item" data-id="${r.id}">
            <div class="res-col">
              <span class="badge">${r.court}</span>
              <span class="muted">${r.date}</span>
              <span>${r.start} – ${r.end}</span>
            </div>
            <div class="res-col">
              <button class="btn btn-cancel" data-id="${r.id}">Annuler</button>
            </div>
          </div>`;
        }).join('');
    }

    // Ajoute une réservation via l'API
    async function addReservation(date, court, start, durationMin){
      const startMin = toMinutes(start);
      const endMin = startMin + Number(durationMin);
      const end = formatHM(Math.floor(endMin/60), endMin%60);
      
      if(endMin > CLOSE_HOUR*60){
        alert('Le créneau dépasse l\'heure de fermeture.');
        return false;
      }
      
      try{
        const res = await fetch('/tennis-club-rambouillet/php/bookings-api.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({action: 'add', date, court, start, end})
        });
        const json = await res.json();
        
        if(json.success){
          alert('Réservation enregistrée.');
          await loadFromAPI();
          return true;
        } else {
          alert(json.error || 'Erreur lors de la réservation');
          return false;
        }
      }catch(e){
        alert('Erreur de connexion');
        return false;
      }
    }

    // Initialisation: remplit les heures, fixe la date sur aujourd'hui, rend la liste, puis attache les événements
    document.addEventListener('DOMContentLoaded', async ()=>{
      // init
      populateStartSelect();
      const inputDate = document.getElementById('bk-date');
      inputDate.value = todayStr();
      await loadFromAPI();

      // Soumission du formulaire de création de réservation
      document.getElementById('booking-form').addEventListener('submit', async (e)=>{
        e.preventDefault();
        const date = document.getElementById('bk-date').value;
        const court = document.getElementById('bk-court').value;
        const start = document.getElementById('bk-start').value;
        const duration = document.getElementById('bk-duration').value;
        await addReservation(date, court, start, duration);
      });

      // Click sur Annuler: supprime une réservation
      document.getElementById('res-list').addEventListener('click', async (e)=>{
        const btn = e.target.closest('.btn-cancel');
        if(!btn) return;
        const id = Number(btn.dataset.id);
        
        try{
          const res = await fetch('/tennis-club-rambouillet/php/bookings-api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', id})
          });
          const json = await res.json();
          if(json.success){
            await loadFromAPI();
          }
        }catch(e){
          alert('Erreur lors de la suppression');
        }
      });

      // Effacer la journée: supprime toutes les réservations du jour sélectionné
      document.getElementById('btn-clear-day').addEventListener('click', async ()=>{
        const date = document.getElementById('bk-date').value;
        
        try{
          const res = await fetch('/tennis-club-rambouillet/php/bookings-api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete_day', date})
          });
          const json = await res.json();
          if(json.success){
            await loadFromAPI();
          }
        }catch(e){
          alert('Erreur lors de la suppression');
        }
      });
    });
  </script>
  <?php endif; ?>
  <script src="js/nav-badge.js"></script>
</body>
</html>
