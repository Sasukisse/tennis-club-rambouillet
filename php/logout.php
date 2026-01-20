<?php
// Déconnexion: détruit la session et redirige vers la page de connexion
require __DIR__.'/config.php';
start_session();
// Supprime le cookie de persistance
forget_login();
session_destroy();
header('Location: /tennis-club-rambouillet/php/login.php');
exit;
