/*
  login.js
  Comportements spécifiques à la page de connexion
  - Rien de dynamique pour l'instant (formulaire classique)
  - Exemple: focus sur le champ email au chargement
*/
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    const emailInput = document.querySelector('input[name="email"]');
    if(emailInput){ emailInput.focus(); }
  });
})();
