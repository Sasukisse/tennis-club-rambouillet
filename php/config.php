<?php
//
// Fichier de configuration et utilitaires de session/accès
// - db(): crée et mémorise une connexion PDO MySQL
// - start_session(): initialise la session si nécessaire
// - is_logged_in()/current_user(): helpers autour de $_SESSION
// - require_login()/require_admin(): protections de pages
//
// Config de connexion MySQL
$DB_HOST = '127.0.0.1';
$DB_NAME = 'tcr';
$DB_USER = 'root';
$DB_PASS = '';

function db(){
  static $pdo = null;
  if($pdo){ return $pdo; }
  global $DB_HOST,$DB_NAME,$DB_USER,$DB_PASS;
  $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}

function start_session(){
  if(session_status() === PHP_SESSION_NONE){ session_start(); }
  // Tentative d'auto-connexion via cookie "remember me"
  try_autologin();
}

function is_logged_in(){ start_session(); return isset($_SESSION['user']); }
function current_user(){ start_session(); return $_SESSION['user'] ?? null; }
function require_login(){ if(!is_logged_in()){ header('Location: /tennis-club-rambouillet/php/login.php'); exit; } }
function require_admin(){ require_login(); if((current_user()['role'] ?? '') !== 'Admin'){ http_response_code(403); echo 'Accès refusé'; exit; } }

// --- Persistance de connexion ("remember me") ---
// Secret HMAC pour signer le cookie (à personnaliser en prod)
$REMEMBER_SECRET = 'change-me-please-very-secret';

function remember_login($userId, $days = 30){
  global $REMEMBER_SECRET;
  $exp = time() + ($days * 86400);
  $payload = $userId.'|'.$exp;
  $sig = hash_hmac('sha256', $payload, $REMEMBER_SECRET);
  $cookie = base64_encode($payload.'|'.$sig);
  $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
  setcookie('tcr_remember', $cookie, [
    'expires' => $exp,
    'path' => '/tennis-club-rambouillet/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}

function forget_login(){
  $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
  setcookie('tcr_remember', '', [
    'expires' => time() - 3600,
    'path' => '/tennis-club-rambouillet/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}

function try_autologin(){
  if(isset($_SESSION['user'])){ return; }
  if(empty($_COOKIE['tcr_remember'])){ return; }
  global $REMEMBER_SECRET;
  $raw = base64_decode($_COOKIE['tcr_remember'], true);
  if(!$raw){ return; }
  $parts = explode('|', $raw);
  if(count($parts) !== 3){ return; }
  list($userId, $exp, $sig) = $parts;
  if(!ctype_digit((string)$userId) || !ctype_digit((string)$exp)){ return; }
  if((int)$exp < time()){ return; }
  $expected = hash_hmac('sha256', $userId.'|'.$exp, $REMEMBER_SECRET);
  if(!hash_equals($expected, $sig)){ return; }
  try{
    $pdo = db();
    $st = $pdo->prepare('SELECT id,email,full_name,role FROM users WHERE id=? LIMIT 1');
    $st->execute([intval($userId)]);
    $u = $st->fetch();
    if($u){
      $_SESSION['user'] = ['id'=>$u['id'],'email'=>$u['email'],'full_name'=>$u['full_name'],'role'=>$u['role']];
      // Prolonge la durée du cookie à chaque visite
      remember_login($u['id']);
    }
  } catch(Throwable $e){ /* silence */ }
}

// --- Settings (clé/valeur) ---
function settings_init(){
  try{
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
      k VARCHAR(64) PRIMARY KEY,
      v TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch(Throwable $e){ /* ignore */ }
}

function get_setting($key, $default=null){
  try{
    settings_init();
    $pdo = db();
    $st = $pdo->prepare('SELECT v FROM settings WHERE k=? LIMIT 1');
    $st->execute([$key]);
    $row = $st->fetch();
    if($row && array_key_exists('v',$row)) return $row['v'];
  } catch(Throwable $e){ /* ignore */ }
  return $default;
}

function set_setting($key, $value){
  try{
    settings_init();
    $pdo = db();
    $st = $pdo->prepare('INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)');
    $st->execute([$key, $value]);
    return true;
  } catch(Throwable $e){ return false; }
}

?>