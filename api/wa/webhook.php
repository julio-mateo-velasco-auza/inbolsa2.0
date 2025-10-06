<?php
declare(strict_types=1);
require __DIR__.'/config.php';
header('Content-Type: application/json');

/* CORS básico para pruebas locales */
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

/* 1) Verificación (GET) */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $mode = $_GET['hub_mode']  ?? $_GET['hub.mode']  ?? '';
  $token= $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
  $chal = $_GET['hub_challenge']    ?? $_GET['hub.challenge']    ?? '';

  if ($mode === 'subscribe' && hash_equals(WA_VERIFY_TOKEN, $token)) {
    http_response_code(200); echo $chal; exit;
  }
  http_response_code(403); echo json_encode(['error'=>'Invalid verify token']); exit;
}

/* 2) Eventos (POST) */
$raw = file_get_contents('php://input');
file_put_contents(WA_STORAGE.'/incoming.log', date('c').' '.$raw.PHP_EOL, FILE_APPEND);
http_response_code(200);
echo json_encode(['status'=>'ok']);
