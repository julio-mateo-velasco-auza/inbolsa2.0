<?php
// api/qr/config.php
// Configuración y helpers SOLO para el flujo QR/privado (PHP 7.4 compatible).

function qr_cfg() {
  $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

  return [
    'cookie_name'        => 'qrauth',
    'cookie_ttl_seconds' => 24 * 60 * 60, // 24h
    'cookie_path'        => '/',
    'cookie_domain'      => '',           // mismo host
    'cookie_secure'      => $secure,
    'cookie_samesite'    => 'Lax',        // permite redirect del QR
  ];
}

/** Coloca la cookie de acceso privado con el código del QR validado. */
function qr_set_cookie($code) {
  $cfg = qr_cfg();
  setcookie(
    $cfg['cookie_name'],
    $code,
    [
      'expires'  => time() + $cfg['cookie_ttl_seconds'],
      'path'     => $cfg['cookie_path'],
      'domain'   => $cfg['cookie_domain'],
      'secure'   => $cfg['cookie_secure'],
      'httponly' => false,               // el front la lee para mostrar pestaña Productos
      'samesite' => $cfg['cookie_samesite'],
    ]
  );
}

/** Limpia la cookie de acceso privado. */
function qr_clear_cookie() {
  $cfg = qr_cfg();
  setcookie(
    $cfg['cookie_name'],
    '',
    [
      'expires'  => 0,
      'path'     => $cfg['cookie_path'],
      'domain'   => $cfg['cookie_domain'],
      'secure'   => $cfg['cookie_secure'],
      'httponly' => false,
      'samesite' => $cfg['cookie_samesite'],
    ]
  );
}

/** Devuelve el valor de la cookie (o cadena vacía). */
function qr_cookie_value() {
  $cfg = qr_cfg();
  return isset($_COOKIE[$cfg['cookie_name']]) ? (string)$_COOKIE[$cfg['cookie_name']] : '';
}
