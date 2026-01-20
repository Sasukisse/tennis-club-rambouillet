/*
  JS de la page Boutique
  - Gestion du panier (localStorage)
  - Calcul des totaux (sous-total, livraison, total)
  - Rendu de la liste et des totaux
  - Redirection vers checkout
*/
(function(){
  const CART_KEY = 'tcr_cart_v1';
  const SHIPPING_FLAT = 5;
  const FREE_SHIPPING_MIN = 50;
  const BASE = '/tennis-club-rambouillet';
  let loggedIn = false;

  const cartList = document.querySelector('.cart-list');
  const cartEmpty = document.querySelector('.cart-empty');
  const cartSummary = document.querySelector('.cart-summary');
  const sumSubtotal = document.getElementById('sum-subtotal');
  const sumShipping = document.getElementById('sum-shipping');
  const sumTotal = document.getElementById('sum-total');
  
  function notifyCartUpdated(){
    try{ window.dispatchEvent(new Event('tcr-cart-updated')); }catch(_){ }
  }

  const formatEUR = n => n.toLocaleString('fr-FR', {style:'currency', currency:'EUR'});
  function loadCart(){ try{ return JSON.parse(localStorage.getItem(CART_KEY)) || []; }catch(_){ return []; } }
  function saveCart(items){
    localStorage.setItem(CART_KEY, JSON.stringify(items));
    if(loggedIn){
      try{
        fetch(`${BASE}/php/cart.php`, {
          method: 'POST', headers: {'Content-Type':'application/json'},
          body: JSON.stringify({items})
        });
      }catch(_){/* ignore */}
    }
  }
  async function fetchMe(){
    try{ const r = await fetch(`${BASE}/php/me.php`, {credentials:'same-origin'}); const d = await r.json(); return !!(d && d.loggedIn); }catch(_){ return false; }
  }
  async function fetchServerCart(){
    try{ const r = await fetch(`${BASE}/php/cart.php`, {credentials:'same-origin'}); const d = await r.json(); if(Array.isArray(d)) return d; return d && Array.isArray(d.items) ? d.items : []; }catch(_){ return []; }
  }
  function compute(cart){
    const subtotal = cart.reduce((s,i)=> s + i.price * i.qty, 0);
    const shipping = subtotal === 0 ? 0 : (subtotal >= FREE_SHIPPING_MIN ? 0 : SHIPPING_FLAT);
    const total = subtotal + shipping;
    return {subtotal, shipping, total};
  }
  function render(){
    const cart = loadCart();
    const hasItems = cart.length>0;
    cartEmpty.hidden = hasItems;
    cartList.hidden = !hasItems;
    cartSummary.hidden = !hasItems;
    cartList.innerHTML = cart.map((it,idx)=>`
      <li class="cart-item">
        <div>
          <div><strong>${it.title}</strong>${it.size?` – <span class='cart-meta'>Taille: ${it.size}</span>`:''}</div>
          <div class="cart-meta">${it.qty} × ${formatEUR(it.price)}</div>
        </div>
        <div class="cart-actions">
          <input class="cart-qty" type="number" min="1" step="1" value="${it.qty}" data-idx="${idx}">
          <button class="cart-remove" data-idx="${idx}" aria-label="Retirer">Retirer</button>
        </div>
      </li>`).join('');
    const {subtotal, shipping, total} = compute(cart);
    sumSubtotal.textContent = formatEUR(subtotal);
    sumShipping.textContent = formatEUR(shipping);
    sumTotal.textContent = formatEUR(total);

  }

  document.querySelectorAll('.product-card').forEach(card=>{
    const btn = card.querySelector('.btn-add');
    btn.addEventListener('click', ()=>{
      const sku = card.dataset.sku;
      const title = card.querySelector('.product-title').textContent.trim();
      const price = parseFloat(card.querySelector('.product-price').dataset.price);
      const qty = Math.max(1, parseInt(card.querySelector('.product-qty').value || '1',10));
      const type = btn.dataset.type;
      const size = type==='variant' ? card.querySelector('.product-size').value : null;
      const cart = loadCart();
      const existing = cart.find(i => i.sku===sku && i.size===size);
      if(existing){ existing.qty += qty; } else { cart.push({sku, title, price, qty, size}); }
      saveCart(cart); render(); notifyCartUpdated();
    });
  });

  cartList.addEventListener('input', (e)=>{
    if(!e.target.classList.contains('cart-qty')) return;
    const idx = Number(e.target.dataset.idx);
    const qty = Math.max(1, parseInt(e.target.value||'1',10));
    const cart = loadCart();
    cart[idx].qty = qty; saveCart(cart); render(); notifyCartUpdated();
  });
  cartList.addEventListener('click', (e)=>{
    if(!e.target.classList.contains('cart-remove')) return;
    const idx = Number(e.target.dataset.idx);
    const cart = loadCart();
    cart.splice(idx,1); saveCart(cart); render(); notifyCartUpdated();
  });

  const btnCheckout = document.getElementById('btn-checkout');
  btnCheckout.addEventListener('click', (e)=>{
    const cart = loadCart();
    if(cart.length===0){ e.preventDefault(); alert('Votre panier est vide.'); }
  });
  // Force le style inline (anti-cache)
  if (btnCheckout) {
    btnCheckout.style.background = '#F95E2D';
    btnCheckout.style.color = '#ffffff';
    btnCheckout.style.border = '0';
    btnCheckout.style.outline = 'none';
    btnCheckout.style.textDecoration = 'none';
  }

  // Initialisation avec éventuelle fusion serveur
  (async ()=>{
    loggedIn = await fetchMe();
    if(loggedIn){
      const server = await fetchServerCart();
      if(server.length > 0){
        // Source de vérité: serveur -> écrase le local
        try{ localStorage.setItem(CART_KEY, JSON.stringify(server)); }catch(_){ }
      }
      // Si serveur vide, on laisse le local tel quel (pas de push ici)
    } else {
      // Utilisateur déconnecté: vider totalement le panier local
      try{ localStorage.removeItem(CART_KEY); }catch(_){ }
    }
    render(); notifyCartUpdated();
  })();
})();
