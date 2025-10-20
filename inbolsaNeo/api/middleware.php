<?php
// middleware.php
declare(strict_types=1);

function start_session_if_needed(): void {
  if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
      'lifetime' => 0,
      'path'     => '/inbolsaNeo', // <<< IMPORTANTE: coincide con la carpeta base
      'domain'   => '',            // mismo host
      'secure'   => false,         // en localhost; en iPage ponlo true (HTTPS)
      'httponly' => true,
      'samesite' => 'Lax',
    ]);
    session_name('INBOLSASESSID');
    session_start();
  }
}

function json_response($data, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

function cors_headers(): void {
  // Como usas MISMO ORIGEN, no necesitas CORS. Si algún día front/back están en dominios distintos,
  // descomenta y ajusta:
  // header('Access-Control-Allow-Origin: http://TU-ORIGEN');
  // header('Access-Control-Allow-Credentials: true');
  // header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
  // header('Access-Control-Allow-Methods: GET,POST,OPTIONS,PUT,DELETE,PATCH');
}

function preflight_if_options(): void {
  if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    cors_headers();
    http_response_code(204);
    exit;
  }
}
