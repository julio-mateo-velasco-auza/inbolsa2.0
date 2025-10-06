<?php
declare(strict_types=1);
require __DIR__.'/config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$to   = $in['to']   ?? '';
$type = $in['type'] ?? 'template';

if (!$to) { http_response_code(400); echo json_encode(['error'=>'Missing "to"']); exit; }

$payload = ['messaging_product'=>'whatsapp', 'to'=>$to];
if ($type === 'text') {
  $text = trim($in['text'] ?? '');
  if ($text === '') { http_response_code(400); echo json_encode(['error'=>'Missing "text"']); exit; }
  $payload['type']='text'; $payload['text']=['preview_url'=>false,'body'=>$text];
} else {
  // template
  $name = $in['template'] ?? 'hello_world';
  $lang = $in['lang'] ?? 'es';
  $payload['type']='template';
  $payload['template']=['name'=>$name,'language'=>['code'=>$lang]];
}

$url = WA_GRAPH_BASE.'/'.WA_PHONE_ID.'/messages';

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer '.WA_TOKEN,
    'Content-Type: application/json'
  ],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 30
]);
$res = curl_exec($ch);
$err = curl_error($ch);
$code= curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

file_put_contents(WA_STORAGE.'/send.log',
  date('c')." [$code] $res".($err? " ERR:$err":'').PHP_EOL, FILE_APPEND);

http_response_code($code ?: 500);
echo $res ?: json_encode(['error'=>$err ?: 'unknown']);
