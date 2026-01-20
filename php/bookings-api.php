<?php
// API pour gérer les réservations de terrains
require __DIR__.'/config.php';
require_login();

header('Content-Type: application/json');

$pdo = db();
$user = current_user();
$userId = $user['id'];

// Créer la table si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  date DATE NOT NULL,
  court VARCHAR(50) NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_date (date),
  INDEX idx_user_date (user_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$method = $_SERVER['REQUEST_METHOD'];

if($method === 'GET'){
  // Récupérer toutes les réservations de l'utilisateur
  $stmt = $pdo->prepare('SELECT id, date, court, start_time as start, end_time as end FROM bookings WHERE user_id = ? ORDER BY date ASC, start_time ASC');
  $stmt->execute([$userId]);
  $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Convertir les heures au format HH:MM
  foreach($bookings as &$b){
    $b['start'] = substr($b['start'], 0, 5);
    $b['end'] = substr($b['end'], 0, 5);
  }
  
  echo json_encode(['success' => true, 'bookings' => $bookings]);
  
} else if($method === 'POST'){
  $data = json_decode(file_get_contents('php://input'), true);
  $action = $data['action'] ?? '';
  
  if($action === 'add'){
    // Ajouter une réservation
    $date = $data['date'] ?? '';
    $court = $data['court'] ?? '';
    $start = $data['start'] ?? '';
    $end = $data['end'] ?? '';
    
    if(!$date || !$court || !$start || !$end){
      echo json_encode(['success' => false, 'error' => 'Données manquantes']);
      exit;
    }
    
    // Vérifier les conflits
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM bookings WHERE date = ? AND court = ? AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))');
    $stmt->execute([$date, $court, $end, $start, $end, $start, $start, $end]);
    $conflict = $stmt->fetch();
    
    if($conflict['count'] > 0){
      echo json_encode(['success' => false, 'error' => 'Conflit: le terrain est déjà réservé sur ce créneau']);
      exit;
    }
    
    // Insérer la réservation
    $stmt = $pdo->prepare('INSERT INTO bookings (user_id, date, court, start_time, end_time) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $date, $court, $start, $end]);
    
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    
  } else if($action === 'delete'){
    // Supprimer une réservation
    $id = intval($data['id'] ?? 0);
    
    if(!$id){
      echo json_encode(['success' => false, 'error' => 'ID manquant']);
      exit;
    }
    
    // Vérifier que la réservation appartient à l'utilisateur
    $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    
    echo json_encode(['success' => true]);
    
  } else if($action === 'delete_day'){
    // Supprimer toutes les réservations d'un jour
    $date = $data['date'] ?? '';
    
    if(!$date){
      echo json_encode(['success' => false, 'error' => 'Date manquante']);
      exit;
    }
    
    $stmt = $pdo->prepare('DELETE FROM bookings WHERE date = ? AND user_id = ?');
    $stmt->execute([$date, $userId]);
    
    echo json_encode(['success' => true]);
    
  } else {
    echo json_encode(['success' => false, 'error' => 'Action inconnue']);
  }
  
} else {
  echo json_encode(['success' => false, 'error' => 'Méthode non supportée']);
}
