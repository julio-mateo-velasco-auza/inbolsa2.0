<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/middleware.php';

function random_code($len = 16) {
  return rtrim(strtr(base64_encode(random_bytes($len)), '+/', '-_'), '=');
}

/** ----------------------------------------------------------------
 *  Emite un accessToken HMAC desde un code válido
 * ----------------------------------------------------------------*/
function qr_issue_token(string $code): string {
  $cfg = require __DIR__ . '/config.php';
  $ttl = (int)($cfg['access_ttl_seconds'] ?? 600);
  $payload = [
    'code' => $code,
    'iat'  => time(),
    'exp'  => time() + $ttl
  ];
  return hmac_sign($payload);
}

/** -------------------- Crear QR (ADMIN) -------------------- */
function route_qr_create() {
  cors_headers();
  preflight_if_options();
  require_admin();

  $input = json_decode(file_get_contents('php://input'), true) ?? [];
  $type    = isset($input['type']) ? substr((string)$input['type'], 0, 32) : 'default';
  $payload = isset($input['payload']) ? (string)$input['payload'] : null;

  $expiresAtInput = isset($input['expiresAt']) ? trim((string)$input['expiresAt']) : null;
  $usageLimitRaw  = isset($input['usageLimit']) ? $input['usageLimit'] : null;

  $expiresSql = null;
  if ($expiresAtInput !== null && $expiresAtInput !== '') {
    $ts = strtotime($expiresAtInput);
    if ($ts === false) {
      json_response(['error' => 'expiresAt inválido. Usa ISO 8601 o "YYYY-MM-DD HH:MM:SS".'], 400);
    }
    $expiresSql = date('Y-m-d H:i:s', $ts);
  }

  $usageLimit = null;
  if ($usageLimitRaw !== null && $usageLimitRaw !== '' && (int)$usageLimitRaw > 0) {
    $usageLimit = (int)$usageLimitRaw;
  }

  $code = random_code(16);
  $pdo  = db_conn();

  try {
    $stmt = $pdo->prepare(
      'INSERT INTO qr_codes (code, type, payload, status, usage_count, usage_limit, expires_at, created_by)
       VALUES (?, ?, ?, "active", 0, ?, ?, ?)'
    );
    $stmt->execute([$code, $type, $payload, $usageLimit, $expiresSql, $_SESSION['admin_id'] ?? null]);
  } catch (PDOException $e) {
    json_response(['error' => 'DB', 'detail' => $e->getMessage()], 500);
  }

  $id = (int)$pdo->lastInsertId();
  json_response(['id' => $id, 'code' => $code, 'urlEjemplo' => '/?qr=' . $code], 201);
}

/** -------------------- Validar QR (JSON) -------------------- */
function route_qr_validate() {
  cors_headers();
  preflight_if_options();

  $code = isset($_GET['code']) ? (string)$_GET['code'] : '';
  if (!$code) json_response(['valid' => false, 'reason' => 'Falta code']);

  $pdo = db_conn();
  $stmt = $pdo->prepare('SELECT * FROM qr_codes WHERE code = ? LIMIT 1');
  $stmt->execute([$code]);
  $qr = $stmt->fetch();

  if (!$qr)                       json_response(['valid' => false, 'reason' => 'No existe']);
  if ($qr['status'] !== 'active') json_response(['valid' => false, 'reason' => 'Revocado']);
  if (!empty($qr['expires_at']) && strtotime($qr['expires_at']) < time()) {
    json_response(['valid' => false, 'reason' => 'Expirado']);
  }
  if (!empty($qr['usage_limit']) && (int)$qr['usage_count'] >= (int)$qr['usage_limit']) {
    json_response(['valid' => false, 'reason' => 'Límite de uso superado']);
  }

  // log + contador
  $ip = $_SERVER['REMOTE_ADDR'] ?? null;
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
  $pdo->prepare('INSERT INTO qr_access_log (qr_code_id, ip, user_agent) VALUES (?,?,?)')
      ->execute([(int)$qr['id'], $ip, $ua]);
  $pdo->prepare('UPDATE qr_codes SET usage_count = usage_count + 1 WHERE id = ?')
      ->execute([(int)$qr['id']]);

  $accessToken = qr_issue_token($code);
  json_response(['valid' => true, 'accessToken' => $accessToken, 'ttlSeconds' => (int)(require __DIR__ . '/config.php')['access_ttl_seconds']]);
}

/** -------------------- Lista (ADMIN) -------------------- */
function route_qr_list() {
  cors_headers();
  preflight_if_options();
  require_admin();

  $pdo = db_conn();
  // Selección mínima para evitar errores si no existe created_at en tu tabla
  $rows = $pdo->query("
    SELECT
      id, code, type, payload, status, usage_count, usage_limit, expires_at
    FROM qr_codes
    ORDER BY id DESC
    LIMIT 50
  ")->fetchAll();

  json_response(['items' => $rows]);
}

/** -------------------- Revocar (ADMIN) -------------------- */
function route_qr_revoke() {
  cors_headers();
  preflight_if_options();
  require_admin();

  $input = json_decode(file_get_contents('php://input'), true) ?? [];
  $code = isset($input['code']) ? (string)$input['code'] : '';
  if (!$code) json_response(['error' => 'Falta code'], 400);

  $pdo = db_conn();
  $stmt = $pdo->prepare('UPDATE qr_codes SET status = "revoked" WHERE code = ?');
  $stmt->execute([$code]);
  if ($stmt->rowCount() === 0) json_response(['error' => 'No existe'], 404);

  json_response(['ok' => true]);
}

/** -------------------- Payload por token -------------------- */
function route_access_payload() {
  cors_headers();
  preflight_if_options();

  $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  $token = null;
  if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) $token = trim($m[1]);
  if (!$token) $token = $_GET['accessToken'] ?? null;
  if (!$token) json_response(['ok'=>false,'error' => 'Falta accessToken'], 400);

  $tk = hmac_verify($token);
  if (!$tk || empty($tk['code'])) json_response(['ok'=>false,'error' => 'Token inválido o expirado'], 401);

  $pdo = db_conn();
  $stmt = $pdo->prepare('SELECT code, status, expires_at, payload FROM qr_codes WHERE code = ? LIMIT 1');
  $stmt->execute([$tk['code']]);
  $qr = $stmt->fetch();
  if (!$qr) json_response(['ok'=>false,'error' => 'No existe'], 404);
  if ($qr['status'] !== 'active') json_response(['ok'=>false,'error' => 'Revocado'], 403);
  if (!empty($qr['expires_at']) && strtotime($qr['expires_at']) < time()) json_response(['ok'=>false,'error' => 'Expirado'], 403);

  $data = null;
  if (!empty($qr['payload'])) {
    $dec = json_decode($qr['payload'], true);
    $data = (json_last_error() === JSON_ERROR_NONE) ? $dec : ['payload' => $qr['payload']];
  }

  json_response(['ok' => true, 'code' => $qr['code'], 'data' => $data]);
}

/** -------------------- Abrir QR (redirige con token) -------------------- */
function route_qr_open() {
  // Redirige a “/” con ?token=... y deja cookies-pista para header/video
  $code = isset($_GET['code']) ? (string)$_GET['code'] : '';
  if (!$code) { http_response_code(400); echo 'Falta code'; return; }

  $pdo = db_conn();
  $stmt = $pdo->prepare('SELECT * FROM qr_codes WHERE code = ? LIMIT 1');
  $stmt->execute([$code]);
  $qr = $stmt->fetch();

  if (!$qr || $qr['status'] !== 'active' ||
      (!empty($qr['expires_at']) && strtotime($qr['expires_at']) < time()) ||
      (!empty($qr['usage_limit']) && (int)$qr['usage_count'] >= (int)$qr['usage_limit'])) {
    http_response_code(302);
    header('Location: /?qr=invalid');
    return;
  }

  // log + contador
  $ip = $_SERVER['REMOTE_ADDR'] ?? null;
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
  $pdo->prepare('INSERT INTO qr_access_log (qr_code_id, ip, user_agent) VALUES (?,?,?)')
      ->execute([(int)$qr['id'], $ip, $ua]);
  $pdo->prepare('UPDATE qr_codes SET usage_count = usage_count + 1 WHERE id = ?')
      ->execute([(int)$qr['id']]);

  // Cookies de “pista” (no sensibles) para que el SSR muestre modo privado
  $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
  setcookie('qrauth', '1', [
    'expires'  => time() + 3600,
    'path'     => '/',
    'secure'   => $isHttps,
    'httponly' => false,
    'samesite' => 'Lax'
  ]);
  setcookie('priv_mode', '1', [
    'expires'  => time() + 3600,
    'path'     => '/',
    'secure'   => $isHttps,
    'httponly' => false,
    'samesite' => 'Lax'
  ]);

  // Genera token y redirige a HOME con ?token=...
  $accessToken = qr_issue_token($code);
  header('Location: /?token=' . rawurlencode($accessToken), true, 302);
  exit;
}
