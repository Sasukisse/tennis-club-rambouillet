(function(){
  try{
    const raw = sessionStorage.getItem('tcr_last_order');
    if(!raw) return;
    const data = JSON.parse(raw);
    const text = `${data.itemCount} article${data.itemCount>1?'s':''} • Total: ` + (Number(data.total)||0).toLocaleString('fr-FR',{style:'currency',currency:'EUR'});
    const sumEl = document.getElementById('order-summary');
    if (sumEl) sumEl.textContent = text;
    if (data.orderId) {
      const idEl = document.getElementById('order-id');
      if (idEl) idEl.textContent = `Commande ${data.orderId}`;
    }
    sessionStorage.removeItem('tcr_last_order');
  }catch(_){ /* noop */ }
})();
