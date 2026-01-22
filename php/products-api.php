<?php
// API pour récupérer les produits de la boutique
require __DIR__.'/config.php';

header('Content-Type: application/json');

$pdo = db();

// Récupérer les produits actifs
$stmt = $pdo->query("SELECT * FROM products WHERE active = 1 ORDER BY created_at DESC");
$products = $stmt->fetchAll();

// Pour chaque produit, récupérer ses images
foreach($products as &$product){
  $stmt = $pdo->prepare("SELECT image_path, is_primary FROM product_images WHERE product_id = ? ORDER BY display_order");
  $stmt->execute([$product['id']]);
  $images = $stmt->fetchAll();
  $product['images'] = $images;
}

echo json_encode(['success' => true, 'products' => $products]);
