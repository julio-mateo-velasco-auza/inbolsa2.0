<?php
// api/config.php
return [
  'db' => [
    // XAMPP local
    'host'    => '127.0.0.1',
    'port'    => 3306,
    'name'    => 'inbolsa2',
    'user'    => 'inbolsa2',
    'pass'    => 'inbolsa_pwd2',
    'charset' => 'utf8mb4',
  ],

  // Sesión (ajusta al subfolder donde sirves el dist)
  'session' => [
    'name'      => 'inb_admin',
    'lifetime'  => 86400,      // 1 día
    'path'      => '/inbolsaNeo', // MUY IMPORTANTE: subcarpeta de despliegue
    'secure'    => false,      // true en producción HTTPS
    'httponly'  => true,
    'samesite'  => 'Lax',
  ],

  // Clave para tokens/CSRF si los usas
  'secret' => 'cambia-esta-clave-en-produccion',
];
