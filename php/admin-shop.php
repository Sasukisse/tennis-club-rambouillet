<?php
// Administration de la boutique
require __DIR__.'/config.php';
require_admin();

$pdo = db();

// Créer la table des produits si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(255),
  has_variants TINYINT(1) DEFAULT 0,
  variant_types VARCHAR(100) DEFAULT NULL,
  color_options VARCHAR(255) DEFAULT NULL,
  stock INT DEFAULT NULL,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sku (sku),
  INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ajouter les colonnes si elles n'existent pas
try {
  $pdo->exec("ALTER TABLE products ADD COLUMN variant_types VARCHAR(100) DEFAULT NULL");
} catch(Exception $e) { }
try {
  $pdo->exec("ALTER TABLE products ADD COLUMN color_options VARCHAR(255) DEFAULT NULL");
} catch(Exception $e) { }

// Créer la table des images de produits
$pdo->exec("CREATE TABLE IF NOT EXISTS product_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  display_order INT DEFAULT 0,
  is_primary TINYINT(1) DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$productError = '';
$productSuccess = '';

// Récupérer l'ID du produit à éditer
$editId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$editProduct = null;
$editProductImages = [];
if($editId > 0){
  $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
  $stmt->execute([$editId]);
  $editProduct = $stmt->fetch();
  
  // Récupérer les images du produit
  if($editProduct){
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order");
    $stmt->execute([$editId]);
    $editProductImages = $stmt->fetchAll();
  }
}

// Gestion de la création d'un produit
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_product'){
  $sku = strtoupper(trim($_POST['product_sku'] ?? ''));
  $name = trim($_POST['product_name'] ?? '');
  $description = trim($_POST['product_description'] ?? '');
  $price = floatval($_POST['product_price'] ?? 0);
  $image = '';
  
  $has_variants = isset($_POST['has_variants']) ? 1 : 0;
  $variant_types = '';
  $color_options = '';
  if($has_variants){
    $types = [];
    if(isset($_POST['variant_size'])) $types[] = 'size';
    if(isset($_POST['variant_color'])){
      $types[] = 'color';
      $color_options = trim($_POST['color_options'] ?? '');
    }
    $variant_types = implode(',', $types);
  }
  $stock = trim($_POST['product_stock'] ?? '');
  $active = isset($_POST['active']) ? 1 : 0;
  
  if(empty($sku) || empty($name) || $price <= 0){
    $productError = 'Le SKU, le nom et le prix sont obligatoires.';
  } else {
    // Vérifier l'unicité du SKU
    $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
    $stmt->execute([$sku]);
    if($stmt->fetch()){
      $productError = 'Un produit avec ce SKU existe déjà.';
    } else {
      $stmt = $pdo->prepare("INSERT INTO products (sku, name, description, price, image, has_variants, variant_types, color_options, stock, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->execute([
        $sku,
        $name,
        $description ?: null,
        $price,
        $image ?: null,
        $has_variants,
        $variant_types ?: null,
        $color_options ?: null,
        $stock !== '' ? intval($stock) : null,
        $active
      ]);
      
      $productId = $pdo->lastInsertId();
      
      // Gestion de l'upload multiple d'images
      if(isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])){
        $uploadDir = __DIR__ . '/../img/produits/';
        if(!is_dir($uploadDir)){ mkdir($uploadDir, 0755, true); }
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        $fileCount = count($_FILES['product_images']['name']);
        for($i = 0; $i < $fileCount; $i++){
          if($_FILES['product_images']['error'][$i] === UPLOAD_ERR_OK){
            $ext = strtolower(pathinfo($_FILES['product_images']['name'][$i], PATHINFO_EXTENSION));
            if(in_array($ext, $allowedExts)){
              $filename = uniqid('prod_') . '.' . $ext;
              if(move_uploaded_file($_FILES['product_images']['tmp_name'][$i], $uploadDir . $filename)){
                $isPrimary = ($i === 0) ? 1 : 0; // Première image = image principale
                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, display_order, is_primary) VALUES (?, ?, ?, ?)");
                $stmt->execute([$productId, $filename, $i, $isPrimary]);
              }
            }
          }
        }
      }
      
      header('Location: /tennis-club-rambouillet/php/admin-shop.php?created=1#product-list');
      exit;
    }
  }
}

// Gestion de la modification d'un produit
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_product'){
  $product_id = intval($_POST['product_id'] ?? 0);
  $sku = strtoupper(trim($_POST['product_sku'] ?? ''));
  $name = trim($_POST['product_name'] ?? '');
  $description = trim($_POST['product_description'] ?? '');
  $price = floatval($_POST['product_price'] ?? 0);
  $image = '';
  
  $has_variants = isset($_POST['has_variants']) ? 1 : 0;
  $variant_types = '';
  $color_options = '';
  if($has_variants){
    $types = [];
    if(isset($_POST['variant_size'])) $types[] = 'size';
    if(isset($_POST['variant_color'])){
      $types[] = 'color';
      $color_options = trim($_POST['color_options'] ?? '');
    }
    $variant_types = implode(',', $types);
  }
  $stock = trim($_POST['product_stock'] ?? '');
  $active = isset($_POST['active']) ? 1 : 0;
  
  if(empty($sku) || empty($name) || $price <= 0){
    $productError = 'Le SKU, le nom et le prix sont obligatoires.';
  } else if($product_id > 0){
    // Vérifier l'unicité du SKU (sauf pour ce produit)
    $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
    $stmt->execute([$sku, $product_id]);
    if($stmt->fetch()){
      $productError = 'Un autre produit avec ce SKU existe déjà.';
    } else {
      $stmt = $pdo->prepare("UPDATE products SET sku=?, name=?, description=?, price=?, image=?, has_variants=?, variant_types=?, color_options=?, stock=?, active=? WHERE id=?");
      $stmt->execute([
        $sku,
        $name,
        $description ?: null,
        $price,
        $image ?: null,
        $has_variants,
        $variant_types ?: null,
        $color_options ?: null,
        $stock !== '' ? intval($stock) : null,
        $active,
        $product_id
      ]);
      
      // Gestion de l'upload multiple d'images
      if(isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])){
        $uploadDir = __DIR__ . '/../img/produits/';
        if(!is_dir($uploadDir)){ mkdir($uploadDir, 0755, true); }
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Récupérer le dernier display_order
        $stmt = $pdo->prepare("SELECT MAX(display_order) as max_order FROM product_images WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $maxOrder = $stmt->fetchColumn() ?: -1;
        
        $fileCount = count($_FILES['product_images']['name']);
        for($i = 0; $i < $fileCount; $i++){
          if($_FILES['product_images']['error'][$i] === UPLOAD_ERR_OK){
            $ext = strtolower(pathinfo($_FILES['product_images']['name'][$i], PATHINFO_EXTENSION));
            if(in_array($ext, $allowedExts)){
              $filename = uniqid('prod_') . '.' . $ext;
              if(move_uploaded_file($_FILES['product_images']['tmp_name'][$i], $uploadDir . $filename)){
                $maxOrder++;
                // Vérifier s'il existe déjà une image principale
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_primary = 1");
                $stmt->execute([$product_id]);
                $hasPrimary = $stmt->fetchColumn() > 0;
                $isPrimary = !$hasPrimary && $i === 0 ? 1 : 0;
                
                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, display_order, is_primary) VALUES (?, ?, ?, ?)");
                $stmt->execute([$product_id, $filename, $maxOrder, $isPrimary]);
              }
            }
          }
        }
      }
      
      header('Location: /tennis-club-rambouillet/php/admin-shop.php?updated=1#product-list');
      exit;
    }
  }
}

// Gestion de la mise à jour de l'ordre des images
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reorder_images'){
  header('Content-Type: application/json');
  $imageOrder = json_decode(file_get_contents('php://input'), true);
  if($imageOrder && isset($imageOrder['order'])){
    try{
      $pdo->beginTransaction();
      foreach($imageOrder['order'] as $index => $imageId){
        $stmt = $pdo->prepare("UPDATE product_images SET display_order = ?, is_primary = ? WHERE id = ?");
        $stmt->execute([$index, ($index === 0 ? 1 : 0), intval($imageId)]);
      }
      $pdo->commit();
      echo json_encode(['success' => true]);
    } catch(Exception $e){
      $pdo->rollBack();
      echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
  }
  echo json_encode(['success' => false]);
  exit;
}

// Gestion de la suppression d'une image
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_image'){
  $image_id = intval($_POST['image_id'] ?? 0);
  if($image_id > 0){
    $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ?");
    $stmt->execute([$image_id]);
    $imageData = $stmt->fetch();
    if($imageData){
      $uploadDir = __DIR__ . '/../img/produits/';
      if(file_exists($uploadDir . $imageData['image_path'])){
        unlink($uploadDir . $imageData['image_path']);
      }
      $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$image_id]);
    }
    header('Location: /tennis-club-rambouillet/php/admin-shop.php?edit=' . ($_POST['product_id'] ?? '') . '&image_deleted=1');
    exit;
  }
}

// Gestion de la suppression d'un produit
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_product'){
  $product_id = intval($_POST['product_id'] ?? 0);
  if($product_id > 0){
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$product_id]);
    header('Location: /tennis-club-rambouillet/php/admin-shop.php?deleted=1#product-list');
    exit;
  }
}

// Récupérer tous les produits
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestion de la boutique – TCR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700&family=Montserrat:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/style.css">
  <link rel="stylesheet" href="/tennis-club-rambouillet/css/admin.css">
  <style>
    /* Style personnalisé pour les checkboxes */
    .custom-checkbox {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      user-select: none;
      margin: 0;
      padding: 0;
    }
    .custom-checkbox input[type="checkbox"] {
      appearance: none;
      -webkit-appearance: none;
      width: 22px;
      height: 22px;
      border: 2px solid #d0c4b0;
      border-radius: 5px;
      background: #fff;
      cursor: pointer;
      position: relative;
      transition: all 0.15s ease;
      flex-shrink: 0;
      margin: 0;
    }
    .custom-checkbox input[type="checkbox"]:hover {
      border-color: #F95E2D;
      box-shadow: 0 0 0 3px rgba(249, 94, 45, 0.1);
    }
    .custom-checkbox input[type="checkbox"]:checked {
      background: #F95E2D;
      border-color: #F95E2D;
    }
    .custom-checkbox input[type="checkbox"]:checked::after {
      content: '';
      position: absolute;
      left: 50%;
      top: 50%;
      width: 5px;
      height: 10px;
      border: solid white;
      border-width: 0 2.5px 2.5px 0;
      transform: translate(-50%, -60%) rotate(45deg);
    }
    .custom-checkbox span {
      margin: 0;
      font-size: 0.95rem;
      line-height: 1.4;
      color: #333;
    }
  </style>
  <script src="/tennis-club-rambouillet/js/nav-badge.js"></script>
</head>
<body>
  <header>
    <div class="container header-flex">
      <a href="/tennis-club-rambouillet/index.html"><img src="/tennis-club-rambouillet/img/logo.png" alt="Logo" class="logo"></a>
      <nav>
        <ul>
          <li><a href="/tennis-club-rambouillet/index.html">Accueil</a></li>
          <li><a href="/tennis-club-rambouillet/le-club.html">Le Club</a></li>
          <li><a href="/tennis-club-rambouillet/inscriptions.html">Inscriptions</a></li>
          <li><a href="/tennis-club-rambouillet/terrains.php">Terrains</a></li>
          <li><a href="/tennis-club-rambouillet/php/medias.php">Médias</a></li>
          <li><a href="/tennis-club-rambouillet/boutique.html">Boutique</a></li>
          <li><a href="/tennis-club-rambouillet/contact.html">Contact</a></li>
          <li><a href="/tennis-club-rambouillet/php/dashboard.php">Mon espace</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <section class="hero">
    <div class="container">
      <h1>Gestion de la boutique</h1>
      <p>Créez et gérez les produits de la boutique</p>
    </div>
  </section>

  <main class="admin">
    <div class="container">
      <!-- Formulaire de création/modification -->
      <section class="card" aria-label="<?= $editProduct ? 'Modifier le produit' : 'Créer un produit' ?>">
        <h3><?= $editProduct ? 'Modifier le produit' : 'Créer un produit' ?></h3>
        <?php if($productError): ?>
          <p style="color:#b00020;margin:8px 0"><?= htmlspecialchars($productError) ?></p>
        <?php endif; ?>
        <?php if(isset($_GET['updated'])): ?>
          <p style="color:#1b5e20;margin:8px 0">✓ Produit modifié avec succès.</p>
        <?php endif; ?>
        
        <form method="post" class="create-event" autocomplete="off" enctype="multipart/form-data">
          <input type="hidden" name="action" value="<?= $editProduct ? 'update_product' : 'create_product' ?>">
          <?php if($editProduct): ?>
            <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
          <?php endif; ?>
          
          <div class="form-row">
            <div class="form-field">
              <label>SKU (identifiant unique) : *
                <input type="text" name="product_sku" value="<?= $editProduct ? htmlspecialchars($editProduct['sku']) : '' ?>" required placeholder="Ex: BALL-4P">
              </label>
            </div>
            <div class="form-field">
              <label>Nom du produit : *
                <input type="text" name="product_name" value="<?= $editProduct ? htmlspecialchars($editProduct['name']) : '' ?>" required>
              </label>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-field">
              <label>Description :
                <textarea name="product_description" rows="3" style="width:100%;padding:10px 12px;border:1px solid #e7dcc9;border-radius:10px;font-family:inherit;resize:vertical"><?= $editProduct ? htmlspecialchars($editProduct['description']) : '' ?></textarea>
              </label>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-field">
              <label>Prix (€) : *
                <input type="number" name="product_price" value="<?= $editProduct ? htmlspecialchars($editProduct['price']) : '' ?>" required min="0" step="0.01" placeholder="8.00">
              </label>
            </div>
            <div class="form-field">
              <label>Stock disponible :
                <input type="number" name="product_stock" value="<?= $editProduct && $editProduct['stock'] !== null ? htmlspecialchars($editProduct['stock']) : '' ?>" min="0" placeholder="Laisser vide si illimité">
              </label>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-field">
              <label>Images du produit (plusieurs fichiers possibles) :
                <input type="file" name="product_images[]" id="product_images" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="padding:8px" multiple>
                <span style="color:#666;font-size:0.85rem;display:block;margin-top:4px">Sélectionnez une ou plusieurs images. Glissez pour réorganiser l'ordre.</span>
              </label>
              <div id="image-preview" style="display:none;margin-top:12px">
                <label style="display:block;margin-bottom:8px;font-weight:500">Aperçu des nouvelles images : <span style="color:#666;font-size:0.85rem;font-weight:normal">(glissez pour réorganiser)</span></label>
                <div id="preview-container" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px"></div>
              </div>
            </div>
          </div>
          
          <?php if($editProduct && !empty($editProductImages)): ?>
            <div class="form-row">
              <div class="form-field" style="width:100%">
                <label style="display:block;margin-bottom:8px">Images actuelles : <span style="color:#666;font-size:0.85rem;font-weight:normal">(glissez pour réorganiser)</span></label>
                <div id="existing-images" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;margin-top:8px">
                  <?php foreach($editProductImages as $img): ?>
                    <div class="existing-image-item" draggable="true" data-image-id="<?= $img['id'] ?>" style="position:relative;border:1px solid #e7dcc9;border-radius:6px;padding:8px;background:#fff;cursor:move">
                      <img src="/tennis-club-rambouillet/img/produits/<?= htmlspecialchars($img['image_path']) ?>" alt="Image produit" style="width:100%;height:100px;object-fit:cover;border-radius:4px;pointer-events:none">
                      <span class="primary-badge" style="position:absolute;top:12px;left:12px;background:#1b5e20;color:#fff;padding:2px 6px;border-radius:4px;font-size:0.75rem;font-weight:500;display:<?= $img['is_primary'] ? 'block' : 'none' ?>">Principale</span>
                      <form method="post" style="margin-top:8px">
                        <input type="hidden" name="action" value="delete_image">
                        <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                        <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
                        <button class="btn warn" type="submit" onclick="return confirm('Supprimer cette image ?')" style="width:100%;padding:6px;font-size:0.8rem">Supprimer</button>
                      </form>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>
          
          <div class="form-row" style="gap:28px;align-items:center">
            <label class="custom-checkbox">
              <input type="checkbox" name="has_variants" id="has_variants" <?= $editProduct && $editProduct['has_variants'] ? 'checked' : '' ?>>
              <span>Produit avec des variantes (tailles, couleurs...)</span>
            </label>
            <label class="custom-checkbox">
              <input type="checkbox" name="active" <?= $editProduct ? ($editProduct['active'] ? 'checked' : '') : 'checked' ?>>
              <span>Produit actif (visible sur la boutique)</span>
            </label>
          </div>
          
          <div id="variant-options" style="display:<?= $editProduct && $editProduct['has_variants'] ? 'block' : 'none' ?>;margin-top:16px;padding:16px;background:#f9f9f9;border:1px solid #e7dcc9;border-radius:8px">
            <label style="display:block;margin-bottom:12px;font-weight:500;color:#333">Type de variantes :</label>
            <div style="display:flex;gap:24px;margin-bottom:16px">
              <label class="custom-checkbox">
                <input type="checkbox" name="variant_size" <?= $editProduct && strpos($editProduct['variant_types'] ?? '', 'size') !== false ? 'checked' : '' ?>>
                <span>Taille (S, M, L, XL)</span>
              </label>
              <label class="custom-checkbox">
                <input type="checkbox" name="variant_color" id="variant_color" <?= $editProduct && strpos($editProduct['variant_types'] ?? '', 'color') !== false ? 'checked' : '' ?>>
                <span>Couleur</span>
              </label>
            </div>
            
            <div id="color-options-field" style="display:<?= $editProduct && strpos($editProduct['variant_types'] ?? '', 'color') !== false ? 'block' : 'none' ?>">
              <label style="display:block;margin-bottom:6px;font-weight:500;color:#333">Couleurs disponibles :</label>
              <input type="text" name="color_options" id="color_options_input" value="<?= $editProduct ? htmlspecialchars($editProduct['color_options'] ?? '') : '' ?>" placeholder="Ex: Bleu, Rouge, Vert, Noir" style="width:100%;padding:10px 12px;border:1px solid #e7dcc9;border-radius:8px;font-family:inherit">
              <span style="color:#666;font-size:0.85rem;display:block;margin-top:4px">Séparez les couleurs par des virgules</span>
            </div>
          </div>
          
          <div class="form-row" style="margin-top:10px">
            <button class="btn" type="submit"><?= $editProduct ? 'Modifier le produit' : 'Créer le produit' ?></button>
            <?php if($editProduct): ?>
              <a href="/tennis-club-rambouillet/php/admin-shop.php" class="btn sec">Annuler</a>
            <?php endif; ?>
          </div>
        </form>
      </section>
      
      <!-- Liste des produits -->
      <section class="card" aria-label="Liste des produits" id="product-list">
        <h3>Produits (<?= count($products) ?>)</h3>
        <?php if(isset($_GET['created'])): ?>
          <p style="color:#1b5e20;margin:8px 0">✓ Produit créé avec succès.</p>
        <?php endif; ?>
        <?php if(isset($_GET['deleted'])): ?>
          <p style="color:#1b5e20;margin:8px 0">✓ Produit supprimé.</p>
        <?php endif; ?>
        
        <?php if(empty($products)): ?>
          <p style="color:#666;margin-top:8px">Aucun produit créé.</p>
        <?php else: ?>
          <div style="margin-top:16px">
            <?php foreach($products as $product): ?>
              <div style="background:#fff;border:1px solid #e7dcc9;border-radius:8px;padding:12px;margin-bottom:12px<?= !$product['active'] ? ';opacity:0.6' : '' ?>">
                <div style="display:flex;justify-content:space-between;align-items:start;gap:12px">
                  <div style="flex:1">
                    <h4 style="margin-bottom:4px">
                      <?= htmlspecialchars($product['name']) ?>
                      <?= !$product['active'] ? '<span style="color:#b00020;font-size:0.85rem;font-weight:normal"> (Inactif)</span>' : '' ?>
                    </h4>
                    <?php if($product['description']): ?>
                      <p style="color:#666;font-size:0.95rem;margin:4px 0"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    <?php endif; ?>
                    <div style="display:flex;flex-wrap:wrap;gap:16px;margin-top:8px;font-size:0.95rem;color:#555">
                      <span><strong>SKU:</strong> <?= htmlspecialchars($product['sku']) ?></span>
                      <span><strong>💰 Prix:</strong> <?= number_format($product['price'], 2, ',', ' ') ?> €</span>
                      <?php if($product['stock'] !== null): ?>
                        <span><strong>📦 Stock:</strong> <?= $product['stock'] ?></span>
                      <?php endif; ?>
                      <?php if($product['has_variants']): ?>
                        <span>🏷️ Produit avec des variantes</span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div style="display:flex;gap:8px;margin:0">
                    <a href="/tennis-club-rambouillet/php/admin-shop.php?edit=<?= $product['id'] ?>" class="btn" style="padding:8px 16px;font-size:0.9rem">Modifier</a>
                    <form method="post" style="margin:0">
                      <input type="hidden" name="action" value="delete_product">
                      <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                      <button class="btn warn" type="submit" onclick="return confirm('Supprimer ce produit ?')" style="padding:8px 16px;font-size:0.9rem">Supprimer</button>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
      
      <div class="form-row" style="margin-top:16px">
        <a class="btn" href="/tennis-club-rambouillet/php/admin.php">← Retour au panel admin</a>
        <a class="btn" href="/tennis-club-rambouillet/php/logout.php">Se déconnecter</a>
      </div>
    </div>
  </main>

  <footer>
    <div class="container">
      <p>&copy; 2025 Tennis Club de Rambouillet</p>
    </div>
  </footer>

  <script>
  // Prévisualisation des images sélectionnées avec drag & drop
  let selectedFiles = [];
  
  document.getElementById('product_images').addEventListener('change', function(e){
    selectedFiles = Array.from(e.target.files);
    renderPreview();
  });
  
  function renderPreview(){
    const previewSection = document.getElementById('image-preview');
    const previewContainer = document.getElementById('preview-container');
    
    if(selectedFiles.length === 0){
      previewSection.style.display = 'none';
      previewContainer.innerHTML = '';
      return;
    }
    
    previewSection.style.display = 'block';
    previewContainer.innerHTML = '';
    
    selectedFiles.forEach((file, index) => {
      const reader = new FileReader();
      reader.onload = function(event){
        const div = document.createElement('div');
        div.className = 'preview-image-item';
        div.draggable = true;
        div.dataset.fileIndex = index;
        div.style.cssText = 'position:relative;border:1px solid #e7dcc9;border-radius:6px;padding:8px;background:#fff;cursor:move';
        
        const img = document.createElement('img');
        img.src = event.target.result;
        img.alt = file.name;
        img.style.cssText = 'width:100%;height:100px;object-fit:cover;border-radius:4px;pointer-events:none';
        
        const badge = document.createElement('span');
        badge.className = 'primary-badge';
        badge.textContent = 'Principale';
        badge.style.cssText = 'position:absolute;top:12px;left:12px;background:#1b5e20;color:#fff;padding:2px 6px;border-radius:4px;font-size:0.75rem;font-weight:500;display:' + (index === 0 ? 'block' : 'none');
        
        const filename = document.createElement('div');
        filename.textContent = file.name;
        filename.style.cssText = 'margin-top:8px;font-size:0.8rem;color:#666;word-break:break-all;text-align:center';
        
        div.appendChild(badge);
        div.appendChild(img);
        div.appendChild(filename);
        previewContainer.appendChild(div);
        
        // Attacher les événements drag & drop
        setupDragDrop(div, previewContainer, true);
      };
      reader.readAsDataURL(file);
    });
  }
  
  // Configuration du drag & drop
  function setupDragDrop(element, container, isPreview){
    element.addEventListener('dragstart', function(e){
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/html', this.innerHTML);
      this.style.opacity = '0.4';
      element.classList.add('dragging');
    });
    
    element.addEventListener('dragend', function(e){
      this.style.opacity = '1';
      element.classList.remove('dragging');
      if(isPreview) updateFileOrder();
      else updateExistingImageOrder();
    });
    
    element.addEventListener('dragover', function(e){
      if(e.preventDefault) e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      
      const dragging = container.querySelector('.dragging');
      if(dragging && dragging !== this){
        const box = this.getBoundingClientRect();
        const offset = e.clientY - box.top;
        if(offset > box.height / 2){
          this.parentNode.insertBefore(dragging, this.nextSibling);
        } else {
          this.parentNode.insertBefore(dragging, this);
        }
      }
      return false;
    });
  }
  
  // Mettre à jour l'ordre des fichiers en prévisualisation
  function updateFileOrder(){
    const items = document.querySelectorAll('.preview-image-item');
    const newOrder = [];
    items.forEach((item, index) => {
      const oldIndex = parseInt(item.dataset.fileIndex);
      newOrder.push(selectedFiles[oldIndex]);
      item.dataset.fileIndex = index;
      
      // Mettre à jour le badge "Principale"
      const badge = item.querySelector('.primary-badge');
      badge.style.display = index === 0 ? 'block' : 'none';
    });
    selectedFiles = newOrder;
    
    // Recréer le DataTransfer pour l'input file
    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    document.getElementById('product_images').files = dt.files;
  }
  
  // Mettre à jour l'ordre des images existantes via AJAX
  function updateExistingImageOrder(){
    const items = document.querySelectorAll('.existing-image-item');
    const order = [];
    items.forEach((item, index) => {
      order.push(item.dataset.imageId);
      
      // Mettre à jour le badge "Principale"
      const badge = item.querySelector('.primary-badge');
      badge.style.display = index === 0 ? 'block' : 'none';
    });
    
    fetch('/tennis-club-rambouillet/php/admin-shop.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({action: 'reorder_images', order: order})
    })
    .then(res => res.json())
    .then(data => {
      if(data.success){
        console.log('Ordre mis à jour');
      }
    })
    .catch(err => console.error('Erreur:', err));
  }
  
  // Initialiser le drag & drop sur les images existantes
  document.addEventListener('DOMContentLoaded', function(){
    const existingContainer = document.getElementById('existing-images');
    if(existingContainer){
      const items = existingContainer.querySelectorAll('.existing-image-item');
      items.forEach(item => setupDragDrop(item, existingContainer, false));
    }
    
    // Gérer l'affichage/masquage des options de variantes
    const hasVariantsCheckbox = document.getElementById('has_variants');
    const variantOptions = document.getElementById('variant-options');
    
    if(hasVariantsCheckbox && variantOptions){
      hasVariantsCheckbox.addEventListener('change', function(){
        variantOptions.style.display = this.checked ? 'block' : 'none';
      });
    }
    
    // Gérer l'affichage/masquage du champ couleurs
    const variantColorCheckbox = document.getElementById('variant_color');
    const colorOptionsField = document.getElementById('color-options-field');
    
    if(variantColorCheckbox && colorOptionsField){
      variantColorCheckbox.addEventListener('change', function(){
        colorOptionsField.style.display = this.checked ? 'block' : 'none';
      });
    }
  });
  </script>
</body>
</html>
