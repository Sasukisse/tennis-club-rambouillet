(function(){
  const CART_KEY = 'tcr_cart_v1';
  const SHIPPING_FLAT = 5;
  const FREE_SHIPPING_MIN = 50;
  const BASE = '/tennis-club-rambouillet';
  let loggedIn = false;
  const formatEUR = n => n.toLocaleString('fr-FR', {style:'currency', currency:'EUR'});

  function loadCart(){ try{ return JSON.parse(localStorage.getItem(CART_KEY)) || []; }catch(_){ return []; } }
  function compute(cart){
    const subtotal = cart.reduce((s,i)=> s + i.price * i.qty, 0);
    const shipping = subtotal === 0 ? 0 : (subtotal >= FREE_SHIPPING_MIN ? 0 : SHIPPING_FLAT);
    const total = subtotal + shipping;
    return {subtotal, shipping, total};
  }
  function renderOrder(){
    const cart = loadCart();
    const list = document.getElementById('order-list');
    list.innerHTML = cart.map(i=>`<li style="padding:6px 0;border-bottom:1px dashed rgba(0,0,0,.08)"><strong>${i.title}</strong>${i.size?` – <span class='muted'>Taille ${i.size}</span>`:''}<br><span class='muted'>${i.qty} × ${formatEUR(i.price)}</span></li>`).join('');
    const {subtotal, shipping, total} = compute(cart);
    document.getElementById('sum-subtotal').textContent = formatEUR(subtotal);
    document.getElementById('sum-shipping').textContent = formatEUR(shipping);
    document.getElementById('sum-total').textContent = formatEUR(total);
  }

  async function fetchMe(){
    try{ const r = await fetch(`${BASE}/php/me.php`, {credentials:'same-origin'}); const d = await r.json(); return !!(d && d.loggedIn); }catch(_){ return false; }
  }
  async function fetchServerCart(){
    try{ const r = await fetch(`${BASE}/php/cart.php`, {credentials:'same-origin'}); const d = await r.json(); if(Array.isArray(d)) return d; return d && Array.isArray(d.items) ? d.items : []; }catch(_){ return []; }
  }

  document.addEventListener('DOMContentLoaded', async ()=>{
    loggedIn = await fetchMe();
    if(loggedIn){
      const server = await fetchServerCart();
      if(server.length > 0){
        // Source de vérité: serveur -> écrase le local
        try{ localStorage.setItem(CART_KEY, JSON.stringify(server)); }catch(_){ }
      }
      // Si serveur vide, on garde le local et on ne push pas ici
    } else {
      // Utilisateur déconnecté: vider totalement le panier local sur checkout aussi
      try{ localStorage.removeItem(CART_KEY); }catch(_){ }
    }
    renderOrder();
    const numberInput = document.getElementById('card-number');
    if(numberInput){
      // Bloque les caractères non numériques et limite à 16
      numberInput.addEventListener('input', ()=>{
        const digitsOnly = numberInput.value.replace(/\D+/g, '');
        numberInput.value = digitsOnly.slice(0,16);
      });
      numberInput.addEventListener('keypress', (ev)=>{
        const code = ev.key;
        if(!/[0-9]/.test(code)){
          ev.preventDefault();
        }
      });
    }
    document.getElementById('card-form').addEventListener('submit', (e)=>{
      e.preventDefault();
      // Validation stricte: 16 chiffres pour le numéro de carte
      const cardNumber = (numberInput ? numberInput.value : '').trim();
      if(cardNumber.length !== 16){
        document.getElementById('pay-status').textContent = 'Erreur: le numéro de carte doit contenir exactement 16 chiffres.';
        return;
      }
      document.getElementById('pay-status').textContent = 'Paiement en cours…';
      setTimeout(()=>{
        document.getElementById('pay-status').textContent = 'Paiement validé. Merci !';
        const nm = document.getElementById('addr-name').value.trim();
        const ln = document.getElementById('addr-line').value.trim();
        const zp = document.getElementById('addr-zip').value.trim();
        const ct = document.getElementById('addr-city').value.trim();
        const addr = [nm, ln, zp + ' ' + ct].filter(Boolean).join(' — ');
        document.getElementById('addr-text').textContent = addr;
        document.getElementById('address-summary').hidden = false;
        const cart = loadCart();
        const itemCount = cart.reduce((n,i)=> n + i.qty, 0);
        const { total } = compute(cart);
        const orderId = 'TCR-' + Date.now().toString().slice(-6) + '-' + Math.floor(Math.random()*1000).toString().padStart(3,'0');
        const summary = { itemCount, total, orderId };
        try{ sessionStorage.setItem('tcr_last_order', JSON.stringify(summary)); }catch(_){ }
        localStorage.removeItem(CART_KEY);
        if(loggedIn){
          try{ fetch(`${BASE}/php/cart.php`, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({items: []})}); }catch(_){ }
        }
        window.location.href = 'success.html';
      }, 1200);
    });
  });
})();
