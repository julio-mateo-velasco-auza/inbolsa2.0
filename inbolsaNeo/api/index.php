<?php
// htdocs/inbolsaNeo/api/index.php
declare(strict_types=1);

// Config básica
$BASE = '/inbolsaNeo';      // SI MUEVES A RAÍZ, CAMBIA A '/'
$COOKIE_PATH = $BASE;       // misma ruta para la cookie de sesión

// Sesión con path correcto (evita que no “pegue”)
session_set_cookie_params([
  'path' => $COOKIE_PATH,
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();

// Simple 404 JSON
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => false, 'error' => 'Not Found', 'path' => $_SERVER['REQUEST_URI']]);
