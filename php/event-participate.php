<?php
// API pour gérer la participation aux événements
require __DIR__.'/config.php';
require_login();

header('Content-Type: application/json');

$pdo = db();
$user = current_user();
$userId = $user['id'];

// Lire les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if(!isset($input['event_id']) || !isset($input['action'])){
  echo json_encode(['success' => false, 'error' => 'Données manquantes']);
  exit;
}

$eventId = intval($input['event_id']);
$action = $input['action']; // 'join' ou 'leave'

try {
  if($action === 'join'){
    // Ajouter la participation
    $stmt = $pdo->prepare("INSERT IGNORE INTO event_participants (event_id, user_id) VALUES (?, ?)");
    $stmt->execute([$eventId, $userId]);
    echo json_encode(['success' => true, 'message' => 'Participation enregistrée']);
  } else if($action === 'leave'){
    // Retirer la participation
    $stmt = $pdo->prepare("DELETE FROM event_participants WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$eventId, $userId]);
    echo json_encode(['success' => true, 'message' => 'Participation annulée']);
  } else {
    echo json_encode(['success' => false, 'error' => 'Action invalide']);
  }
} catch(Exception $e){
  echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
