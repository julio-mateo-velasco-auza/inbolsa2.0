<?php
// api/bootstrap.php
$config = require __DIR__ . '/config.php';

// Sesiones
session_name($config['session']['name']);
session_set_cookie_params([
  'lifetime' => $config['session']['lifetime'],
  'path'     => $config['session']['path'],
  'secure'   => $config['session']['secure'],
  'httponly' => $config['session']['httponly'],
  'samesite' => $config['session']['samesite'],
]);
session_start();

// Conexión PDO compartida
function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $cfg = require __DIR__ . '/config.php';
  $dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $cfg['db']['host'], $cfg['db']['port'], $cfg['db']['name'], $cfg['db']['charset']
  );
  $pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}

// Helper JSON
function json($data, int $code=200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}
