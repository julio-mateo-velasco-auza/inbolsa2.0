<?php
require_once __DIR__ . '/config.php';

function cors_headers() {
  $cfg = require __DIR__ . '/config.php';
  header('Access-Control-Allow-Origin: ' . $cfg['cors_origin']);
  header('Vary: Origin');
  header('Access-Control-Allow-Credentials: true');
  header('Access-Control-Allow-Headers: Content-Type, Authorization');
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  header('Content-Type: application/json; charset=utf-8');
}

function preflight_if_options() {
  if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
  }
}

function json_response($data, $status = 200) {
  http_response_code($status);
  echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function start_session_if_needed() {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  $cfg = require __DIR__ . '/config.php';
  session_name($cfg['session_name'] ?? 'inb_admin');
  session_set_cookie_params([
    'lifetime' => $cfg['session_lifetime'] ?? 86400,
    'path'     => '/',
    'domain'   => '', // mismo host
    'secure'   => (bool)($cfg['session_secure'] ?? false),
    'httponly' => (bool)($cfg['session_http_only'] ?? true),
    'samesite' => $cfg['session_samesite'] ?? 'Lax',
  ]);
  session_start();
}

function require_admin() {
  start_session_if_needed();
  if (empty($_SESSION['admin_id'])) {
    json_response(['error' => 'No autorizado'], 401);
    exit;
  }
}

/** Firma/Verificación HMAC para accessToken QR */
function b64url($data) {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function hmac_sign(array $payload) {
  $cfg = require __DIR__ . '/config.php';
  $secret = $cfg['token_secret'];
  $header = ['alg'=>'HS256','typ'=>'JWT'];
  $enc = b64url(json_encode($header)).'.'.b64url(json_encode($payload));
  $sig = b64url(hash_hmac('sha256', $enc, $secret, true));
  return $enc.'.'.$sig;
}
function hmac_verify($jwt) {
  $cfg = require __DIR__ . '/config.php';
  $secret = $cfg['token_secret'];
  $parts = explode('.', $jwt);
  if (count($parts) !== 3) return null;
  [$h,$p,$s] = $parts;
  $calc = b64url(hash_hmac('sha256', "$h.$p", $secret, true));
  if (!hash_equals($calc, $s)) return null;
  $payload = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
  if (!$payload) return null;
  if (($payload['exp'] ?? 0) < time()) return null;
  return $payload;
}
