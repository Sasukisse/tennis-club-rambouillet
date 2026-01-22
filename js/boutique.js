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
          <div><strong>${it.title}</strong>${it.variant?` – <span class='cart-meta'>${it.variant}</span>`:''}</div>
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

  function attachProductListeners(){
    document.querySelectorAll('.product-card').forEach(card=>{
      const btn = card.querySelector('.btn-add');
      btn.addEventListener('click', ()=>{
        const sku = card.dataset.sku;
        const title = card.querySelector('.product-title').textContent.trim();
        const price = parseFloat(card.querySelector('.product-price').dataset.price);
        const qty = Math.max(1, parseInt(card.querySelector('.product-qty').value || '1',10));
        const type = btn.dataset.type;
        
        let variant = '';
        if(type === 'variant'){
          const sizeSelect = card.querySelector('.product-size');
          const colorSelect = card.querySelector('.product-color');
          const size = sizeSelect ? sizeSelect.value : null;
          const color = colorSelect ? colorSelect.value : null;
          
          if(size && color){
            variant = `${size} - ${color}`;
          } else if(size){
            variant = size;
          } else if(color){
            variant = color;
          }
        }
        
        const cart = loadCart();
        const existing = cart.find(i => i.sku === sku && i.variant === variant);
        if(existing){ 
          existing.qty += qty; 
        } else { 
          cart.push({sku, title, price, qty, variant}); 
        }
        saveCart(cart); render(); notifyCartUpdated();
      });
    });
  }

  // Chargement des produits depuis l'API
  async function loadProducts(){
    const container = document.getElementById('products-container');
    try{
      const res = await fetch(`${BASE}/php/products-api.php`);
      const data = await res.json();
      if(!data.success || !data.products || data.products.length === 0){
        container.innerHTML = '<p style="text-align:center;color:#666;padding:40px">Aucun produit disponible pour le moment.</p>';
        return;
      }
      container.innerHTML = data.products.map(p => {
        // Gestion des images multiples
        let imageHtml = '';
        if(p.images && p.images.length > 0){
          // Utiliser l'image principale ou la première
          const primaryImage = p.images.find(img => img.is_primary) || p.images[0];
          imageHtml = `<img src="img/produits/${primaryImage.image_path}" alt="${p.name}" class="product-media" width="1200" height="800" loading="lazy">`;
          
          // Si plusieurs images, ajouter des miniatures
          if(p.images.length > 1){
            imageHtml += '<div style="display:flex;gap:4px;margin-top:8px;justify-content:center">';
            p.images.forEach((img, idx) => {
              imageHtml += `<img src="img/produits/${img.image_path}" alt="${p.name} ${idx+1}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #e7dcc9;cursor:pointer;opacity:${img.is_primary?'1':'0.6'}" onclick="this.closest('.product-card').querySelector('.product-media').src='img/produits/${img.image_path}';this.parentElement.querySelectorAll('img').forEach(i=>i.style.opacity='0.6');this.style.opacity='1'">`;
            });
            imageHtml += '</div>';
          }
        } else {
          imageHtml = '<img src="img/produits/placeholder.jpg" alt="' + p.name + '" class="product-media" width="1200" height="800" loading="lazy">';
        }
        
        const price = parseFloat(p.price).toFixed(2);
        const priceFormatted = price.replace('.', ',');
        
        if(p.has_variants){
          const variantTypes = (p.variant_types || '').split(',');
          const hasSize = variantTypes.includes('size');
          const hasColor = variantTypes.includes('color');
          
          let variantSelectors = '';
          
          // Sélecteur de taille
          if(hasSize){
            variantSelectors += `
              <select class="product-size" aria-label="Taille">
                <option value="S">S</option>
                <option value="M" selected>M</option>
                <option value="L">L</option>
                <option value="XL">XL</option>
              </select>
            `;
          }
          
          // Sélecteur de couleur
          if(hasColor && p.color_options){
            const colors = p.color_options.split(',').map(c => c.trim()).filter(c => c);
            if(colors.length > 0){
              variantSelectors += `
                <select class="product-color" aria-label="Couleur">
                  ${colors.map((color, idx) => `<option value="${color}" ${idx === 0 ? 'selected' : ''}>${color}</option>`).join('')}
                </select>
              `;
            }
          }
          
          // Produit avec variantes (tailles et/ou couleurs)
          return `
            <article class="product-card" data-sku="${p.sku}">
              ${imageHtml}
              <h3 class="product-title">${p.name}</h3>
              <p class="product-desc">${p.description || ''}</p>
              <span class="product-price" data-price="${price}">${priceFormatted} €</span>
              <div class="product-row">
                ${variantSelectors}
                <label>Qté :<input class="product-qty" type="number" value="1" min="1" step="1"></label>
              </div>
              <button class="btn-add" data-type="variant">Ajouter au panier</button>
            </article>
          `;
        } else {
          // Produit simple
          return `
            <article class="product-card" data-sku="${p.sku}">
              ${imageHtml}
              <h3 class="product-title">${p.name}</h3>
              <p class="product-desc">${p.description || ''}</p>
              <span class="product-price" data-price="${price}">${priceFormatted} €</span>
              <div class="product-row">
                <label>Qté :<input class="product-qty" type="number" value="1" min="1" step="1"></label>
              </div>
              <button class="btn-add" data-type="simple">Ajouter au panier</button>
            </article>
          `;
        }
      }).join('');
      attachProductListeners();
    }catch(err){
      console.error('Erreur lors du chargement des produits:', err);
      container.innerHTML = '<p style="text-align:center;color:#d32f2f;padding:40px">Erreur lors du chargement des produits.</p>';
    }
  }

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
    await loadProducts();
    render(); notifyCartUpdated();
  })();
})();
