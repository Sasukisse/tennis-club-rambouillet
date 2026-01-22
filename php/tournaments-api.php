<?php
// API pour récupérer les tournois avec statut de participation
require __DIR__.'/config.php';

header('Content-Type: application/json');

$pdo = db();

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

$userId = is_logged_in() ? current_user()['id'] : null;

// Récupérer les tournois à venir (cette année et suivantes)
$currentYear = date('Y');

if($userId){
  // Pour un utilisateur connecté, inclure le statut de participation
  $stmt = $pdo->prepare("
    SELECT t.*, 
      COUNT(tp.user_id) as participants_count,
      MAX(CASE WHEN tp.user_id = ? THEN 1 ELSE 0 END) as is_participating
    FROM tournaments t
    LEFT JOIN tournament_participants tp ON t.id = tp.tournament_id
    WHERE YEAR(t.start_date) >= ?
    GROUP BY t.id
    ORDER BY t.start_date ASC
  ");
  $stmt->execute([$userId, $currentYear]);
} else {
  // Pour les visiteurs, juste compter les participants
  $stmt = $pdo->prepare("
    SELECT t.*, 
      COUNT(tp.user_id) as participants_count,
      0 as is_participating
    FROM tournaments t
    LEFT JOIN tournament_participants tp ON t.id = tp.tournament_id
    WHERE YEAR(t.start_date) >= ?
    GROUP BY t.id
    ORDER BY t.start_date ASC
  ");
  $stmt->execute([$currentYear]);
}

$tournaments = $stmt->fetchAll();
echo json_encode(['success' => true, 'tournaments' => $tournaments]);
