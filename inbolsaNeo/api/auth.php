<?php
declare(strict_types=1);

// === Ajusta estos dos si mueves el sitio ===
$BASE = '/inbolsaNeo';
$COOKIE_PATH = $BASE;

// Sesión (path correcto para que el navegador la envíe de vuelta)
session_set_cookie_params([
  'path' => $COOKIE_PATH,
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$route  = $_GET['route'] ?? '';   // login | me | logout

// Utilidad rápida para leer JSON
function read_json(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?? '', true);
  return is_array($data) ? $data : [];
}

switch ($route) {
  case 'login':
    if ($method !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit; }

    $payload = read_json();
    $email = trim($payload['email'] ?? '');
    $password = (string)($payload['password'] ?? '');

    // TODO: Reemplaza por tu validación real en MySQL
    // Mientras tanto, acepta cualquier valor no vacío
    if ($email === '' || $password === '') {
      http_response_code(400);
      echo json_encode(['ok'=>false,'error'=>'Email y password requeridos']);
      exit;
    }

    // “Login” de prueba: guarda un id en sesión
    $_SESSION['uid'] = $email;
    echo json_encode(['ok'=>true, 'user'=>['email'=>$email]]);
    exit;

  case 'me':
    if (!empty($_SESSION['uid'])) {
      echo json_encode(['ok'=>true, 'auth'=>true, 'user'=>['email'=>$_SESSION['uid']]]);
    } else {
      http_response_code(401);
      echo json_encode(['ok'=>false, 'auth'=>false]);
    }
    exit;

  case 'logout':
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
    }
    session_destroy();
    echo json_encode(['ok'=>true, 'message'=>'logged out']);
    exit;

  default:
    http_response_code(404);
    echo json_encode(['ok'=>false, 'error'=>'Unknown auth route']);
    exit;
}
