/*
  register.js
  Comportements spécifiques à la page d'inscription
  - Rien de critique: exemple focus sur le nom
*/
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    const nameInput = document.querySelector('input[name="full_name"]');
    if(nameInput){ nameInput.focus(); }

    // Validation en direct des mots de passe
    const pwd = document.querySelector('input[name="password"]');
    const pwd2 = document.querySelector('input[name="password_confirm"]');
    const form = document.querySelector('form[method="post"]');
    const errorEl = document.createElement('p');
    errorEl.className = 'error';
    errorEl.style.marginTop = '10px';
    errorEl.style.textAlign = 'center';

    function checkMatch(){
      if(!pwd || !pwd2) return true;
      const ok = pwd.value === pwd2.value;
      if(!ok){
        errorEl.textContent = 'Les mots de passe ne correspondent pas.';
        if(form && !form.contains(errorEl)){
          form.appendChild(errorEl);
        }
        pwd2.classList.add('invalid');
      } else {
        if(errorEl && errorEl.parentNode){ errorEl.parentNode.removeChild(errorEl); }
        pwd2.classList.remove('invalid');
      }
      return ok;
    }

    // N'afficher le message d'erreur que lors de la soumission, mais
    // mettre à jour la bordure en direct pour disparaître dès correction
    if(pwd && pwd2){
      const updateBorder = ()=>{
        if(!pwd || !pwd2) return;
        if(pwd.value === pwd2.value){
          pwd2.classList.remove('invalid');
        } else {
          // ne pas afficher le message ici, seulement marquer la bordure
          pwd2.classList.add('invalid');
        }
      };
      pwd.addEventListener('input', updateBorder);
      pwd2.addEventListener('input', updateBorder);
    }

    // Afficher le message uniquement à la soumission
    if(form){
      form.addEventListener('submit', function(e){
        // Retire un éventuel message précédent
        if(errorEl && errorEl.parentNode){ errorEl.parentNode.removeChild(errorEl); }
        if(!checkMatch()){
          e.preventDefault();
        }
      });
    }
  });
})();
