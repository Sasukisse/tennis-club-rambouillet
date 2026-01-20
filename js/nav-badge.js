(function(){
  const BASE = '/tennis-club-rambouillet';
  const CART_KEY = 'tcr_cart_v1';
  let loggedIn = false;
  let badgeEl = null;

  function getBoutiqueLink(){
    return document.querySelector('nav a[href$="boutique.html"]');
  }
  function loadCart(){ try{ return JSON.parse(localStorage.getItem(CART_KEY)) || []; }catch(_){ return []; } }
  function countItems(items){ return items.reduce((n,i)=> n + (parseInt(i.qty||1,10)||1), 0); }
  async function fetchMe(){ try{ const r = await fetch(`${BASE}/php/me.php`, {credentials:'same-origin'}); const d = await r.json(); return !!(d && d.loggedIn); }catch(_){ return false; } }

  function ensureBadge(link){
    if(!link) return null;
    if(!badgeEl){
      // Réutilise un badge existant si présent pour éviter les doublons
      badgeEl = document.getElementById('nav-cart-badge');
      if(!badgeEl){
        badgeEl = document.createElement('span');
        badgeEl.id = 'nav-cart-badge';
        badgeEl.className = 'cart-badge';
        link.appendChild(badgeEl);
      }
    }
    return badgeEl;
  }
  function update(){
    const link = getBoutiqueLink();
    const badge = ensureBadge(link);
    if(!badge) return;
    const items = loadCart();
    const count = countItems(items);
    // Si pas connecté, retirer complètement le badge
    if(!loggedIn){
      try{ badge.remove(); }catch(_){}
      badgeEl = null;
      return;
    }
    // Connecté: afficher uniquement si count>0
    badge.textContent = String(count);
    badge.hidden = !(count>0);
  }

  document.addEventListener('DOMContentLoaded', async function(){
    loggedIn = await fetchMe();
    update();
  });
  window.addEventListener('storage', function(e){
    if(e.key === CART_KEY){ update(); }
  });
  // Rafraîchit le badge quand le panier change dans la même page
  window.addEventListener('tcr-cart-updated', function(){ update(); });
  // Met à jour la connexion quand on revient d'une autre page
  document.addEventListener('visibilitychange', async function(){
    if(document.visibilityState === 'visible'){
      loggedIn = await fetchMe();
      update();
    }
  });
})();
