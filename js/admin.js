/*
  admin.js
  Comportements spécifiques à l'administration
  - Confirmation des actions sensibles
*/
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('form.actions').forEach(function(form){
      form.addEventListener('submit', function(ev){
        if(!confirm("Confirmer l'action ?")){
          ev.preventDefault();
        }
      });
    });
  });
})();
