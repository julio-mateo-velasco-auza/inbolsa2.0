<?php
function wa_load_env($path) {
  if (!file_exists($path)) return;
  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    if (str_starts_with(trim($line), '#')) continue;
    [$k,$v] = array_pad(explode('=', $line, 2), 2, '');
    $k = trim($k); $v = trim($v);
    if ($k !== '' && getenv($k) === false) {
      putenv("$k=$v"); $_ENV[$k] = $v; $_SERVER[$k] = $v;
    }
  }
}
