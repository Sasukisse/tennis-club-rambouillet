<?php
// API REST pour la gestion des événements
// - GET : récupère tous les événements à venir
// - POST : crée un nouvel événement (admin uniquement)
// - DELETE : supprime un événement (admin uniquement)
require __DIR__.'/config.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = db();

// Créer la table si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  event_date DATE NOT NULL,
  event_time TIME,
  location VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_event_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$method = $_SERVER['REQUEST_METHOD'];

// GET : récupérer les événements à venir
if($method === 'GET'){
  $stmt = $pdo->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC, event_time ASC");
  $events = $stmt->fetchAll();
  echo json_encode(['success' => true, 'events' => $events]);
  exit;
}

// Pour POST et DELETE, vérifier que l'utilisateur est admin
if(!is_logged_in()){
  http_response_code(401);
  echo json_encode(['success' => false, 'error' => 'Non authentifié']);
  exit;
}

$user = current_user();
if($user['role'] !== 'Admin'){
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'Accès refusé']);
  exit;
}

// POST : créer un événement
if($method === 'POST'){
  $input = json_decode(file_get_contents('php://input'), true);
  
  $title = trim($input['title'] ?? '');
  $description = trim($input['description'] ?? '');
  $event_date = trim($input['event_date'] ?? '');
  $event_time = trim($input['event_time'] ?? '');
  $location = trim($input['location'] ?? '');
  
  if(empty($title) || empty($event_date)){
    echo json_encode(['success' => false, 'error' => 'Titre et date requis']);
    exit;
  }
  
  // Validation de la date
  if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $event_date)){
    echo json_encode(['success' => false, 'error' => 'Format de date invalide']);
    exit;
  }
  
  $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, location) VALUES (?, ?, ?, ?, ?)");
  $stmt->execute([
    $title,
    $description ?: null,
    $event_date,
    $event_time ?: null,
    $location ?: null
  ]);
  
  echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
  exit;
}

// DELETE : supprimer un événement
if($method === 'DELETE'){
  $input = json_decode(file_get_contents('php://input'), true);
  $id = intval($input['id'] ?? 0);
  
  if($id <= 0){
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
  }
  
  $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
  $stmt->execute([$id]);
  
  echo json_encode(['success' => true]);
  exit;
}

// Méthode non supportée
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Méthode non supportée']);
