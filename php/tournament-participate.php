<?php
// API pour gérer la participation aux tournois
require __DIR__.'/config.php';
require_login();

header('Content-Type: application/json');

$pdo = db();
$user = current_user();
$userId = $user['id'];

// Créer la table tournament_participants si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS tournament_participants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_participation (tournament_id, user_id),
  FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Lire les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if(!isset($input['tournament_id']) || !isset($input['action'])){
  echo json_encode(['success' => false, 'error' => 'Données manquantes']);
  exit;
}

$tournamentId = intval($input['tournament_id']);
$action = $input['action']; // 'join' ou 'leave'

try {
  if($action === 'join'){
    // Vérifier si le tournoi existe et n'est pas complet
    $stmt = $pdo->prepare("SELECT max_participants FROM tournaments WHERE id = ?");
    $stmt->execute([$tournamentId]);
    $tournament = $stmt->fetch();
    
    if(!$tournament){
      echo json_encode(['success' => false, 'error' => 'Tournoi introuvable']);
      exit;
    }
    
    if($tournament['max_participants']){
      $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM tournament_participants WHERE tournament_id = ?");
      $countStmt->execute([$tournamentId]);
      $count = $countStmt->fetch()['count'];
      
      if($count >= $tournament['max_participants']){
        echo json_encode(['success' => false, 'error' => 'Le tournoi est complet']);
        exit;
      }
    }
    
    // Ajouter la participation
    $stmt = $pdo->prepare("INSERT IGNORE INTO tournament_participants (tournament_id, user_id) VALUES (?, ?)");
    $stmt->execute([$tournamentId, $userId]);
    echo json_encode(['success' => true, 'message' => 'Participation enregistrée']);
  } else if($action === 'leave'){
    // Retirer la participation
    $stmt = $pdo->prepare("DELETE FROM tournament_participants WHERE tournament_id = ? AND user_id = ?");
    $stmt->execute([$tournamentId, $userId]);
    echo json_encode(['success' => true, 'message' => 'Participation annulée']);
  } else {
    echo json_encode(['success' => false, 'error' => 'Action invalide']);
  }
} catch(Exception $e){
  echo json_encode(['success' => false, 'error' => 'Erreur base de données: ' . $e->getMessage()]);
}
