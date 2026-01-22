<?php
// Script pour créer la table event_participants
require __DIR__.'/config.php';

$pdo = db();

try {
    // Créer la table event_participants
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS event_participants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_participation (event_id, user_id),
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    echo "✅ Table event_participants créée avec succès !<br>";
    echo "<a href='/tennis-club-rambouillet/php/dashboard.php'>Retour au dashboard</a>";
} catch(Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
