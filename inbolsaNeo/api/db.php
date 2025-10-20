<?php
// db.php
declare(strict_types=1);

function db_conn(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $host = '127.0.0.1';
  $port = 3306;
  $db   = 'inbolsa2';      // la que importaste
  $user = 'root';          // o el usuario que creaste en XAMPP
  $pass = '';              // root default en XAMPP suele ser vacío

  $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}
