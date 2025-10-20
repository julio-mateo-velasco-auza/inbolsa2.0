<?php
function db_conn() {
  static $pdo = null;
  if ($pdo) return $pdo;
  $cfg = require __DIR__ . '/config.php';
  $dsn = "mysql:host={$cfg['db']['host']};port={$cfg['db']['port']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}";
  $pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}
