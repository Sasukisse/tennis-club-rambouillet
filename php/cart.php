<?php
require __DIR__.'/config.php';
start_session();
require_login();
header('Content-Type: application/json');

try{
  $pdo = db();
  // Crée la table si elle n'existe pas
  $pdo->exec("CREATE TABLE IF NOT EXISTS user_carts (
    user_id INT PRIMARY KEY,
    items LONGTEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_carts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

  $uid = intval(current_user()['id']);

  if($_SERVER['REQUEST_METHOD'] === 'GET'){
    $st = $pdo->prepare('SELECT items FROM user_carts WHERE user_id=? LIMIT 1');
    $st->execute([$uid]);
    $row = $st->fetch();
    $items = [];
    if($row){
      $decoded = json_decode($row['items'], true);
      if(is_array($decoded)){ $items = $decoded; }
    }
    echo json_encode(['items' => $items]);
    exit;
  }

  if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if(!is_array($data) || !isset($data['items']) || !is_array($data['items'])){
      http_response_code(400);
      echo json_encode(['error' => 'Bad request']);
      exit;
    }
    // Normalise: ne garder que les champs attendus par item
    $norm = [];
    foreach($data['items'] as $it){
      if(!is_array($it)) continue;
      $sku = isset($it['sku']) ? (string)$it['sku'] : '';
      $title = isset($it['title']) ? (string)$it['title'] : '';
      $price = isset($it['price']) ? (float)$it['price'] : 0.0;
      $qty = isset($it['qty']) ? max(1, (int)$it['qty']) : 1;
      $size = isset($it['size']) ? ($it['size'] !== '' ? (string)$it['size'] : null) : null;
      if($sku === '') continue;
      $norm[] = ['sku'=>$sku,'title'=>$title,'price'=>$price,'qty'=>$qty,'size'=>$size];
    }
    $json = json_encode($norm, JSON_UNESCAPED_UNICODE);
    $pdo->prepare('INSERT INTO user_carts (user_id, items) VALUES (?,?) ON DUPLICATE KEY UPDATE items=VALUES(items), updated_at=CURRENT_TIMESTAMP')
        ->execute([$uid, $json]);
    echo json_encode(['ok'=>true]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
} catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['error' => 'Server error']);
}
