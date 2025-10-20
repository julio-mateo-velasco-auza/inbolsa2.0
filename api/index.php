<?php
declare(strict_types=1);

require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/qr.php';

// --------- Arranque común ---------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Health simple (útil para probar contenedor)
if ($uri === '/api/health') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => true, 'ts' => time()]);
  exit;
}

// --------- AUTH ---------
if ($uri === '/api/auth/login'  && $method === 'POST') { route_auth_login();  exit; }
if ($uri === '/api/auth/logout' && $method === 'POST') { route_auth_logout(); exit; }
if ($uri === '/api/auth/me'     && $method === 'GET')  { route_auth_me();    exit; }

// --------- QR / ACCESS ---------
if ($uri === '/api/qr/create'    && $method === 'POST') { route_qr_create();    exit; }   // admin
if ($uri === '/api/qr/list'      && $method === 'GET')  { route_qr_list();      exit; }   // admin
if ($uri === '/api/qr/revoke'    && $method === 'POST') { route_qr_revoke();    exit; }   // admin
if ($uri === '/api/qr/validate'  && $method === 'GET')  { route_qr_validate();  exit; }   // público
if ($uri === '/api/qr/open'      && $method === 'GET')  { route_qr_open();      exit; }   // público (set cookie y redirige a /)
if ($uri === '/api/access/payload' && $method === 'GET'){ route_access_payload();exit; }   // requiere accessToken

// --------- CORS preflight genérico para /api/* ---------
if (strpos($uri, '/api/') === 0 && $method === 'OPTIONS') {
  cors_headers(); preflight_if_options();
}

// --------- 404 por defecto ---------
header('Content-Type: application/json; charset=utf-8');
http_response_code(404);
echo json_encode(['error' => 'Not Found', 'uri' => $uri]);
