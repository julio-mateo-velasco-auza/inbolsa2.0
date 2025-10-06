<?php
$env = getenv('APP_ENV') ?: 'prod';

if ($env === 'docker') {
  return [
    'db' => [
      'host'    => 'db2',
      'port'    => 3306,
      'name'    => 'inbolsa2',
      'user'    => 'inbolsa2',
      'pass'    => 'inbolsa_pwd2',
      'charset' => 'utf8mb4'
    ],
    // Astro dev en 4321
    'cors_origin'         => 'http://localhost:4321',
    'token_secret'        => 'dev_pon_aqui_un_secret_largo_y_unico',
    'access_ttl_seconds'  => 600,
    // Cookies de sesión PHP (admin)
    'session_name'        => 'inb_admin',
    'session_lifetime'    => 86400,
    'session_samesite'    => 'Lax',
    'session_secure'      => false, // en local http, en prod true
    'session_http_only'   => true
  ];
}

return [
  'db' => [
    'host'    => 'localhost',
    'port'    => 3306,
    'name'    => 'inbolsa_db',   // iPage: tu DB real
    'user'    => 'inbolsa_user', // iPage: tu usuario
    'pass'    => 'inbolsa_pwd',  // iPage: tu password
    'charset' => 'utf8mb4'
  ],
  'cors_origin'          => 'https://tu-dominio.com',
  'token_secret'         => 'prod_pon_aqui_un_secret_muy_largo_y_unico',
  'access_ttl_seconds'   => 600,
  'session_name'         => 'inb_admin',
  'session_lifetime'     => 86400,
  'session_samesite'     => 'Lax',
  'session_secure'       => true,
  'session_http_only'    => true
];
