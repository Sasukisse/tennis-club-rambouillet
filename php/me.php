<?php
require __DIR__.'/config.php';
start_session();
$u = current_user();
header('Content-Type: application/json');
echo json_encode([
  'loggedIn' => (bool)$u,
  'user' => $u ? [
    'id' => $u['id'],
    'email' => $u['email'],
    'full_name' => $u['full_name'],
    'role' => $u['role'],
  ] : null,
]);
