<?php
// public/api/wa/config.php
return [
  // Debe coincidir EXACTO con el que pongas en Meta al verificar el webhook
  'verify_token'     => 'MI_VERIFY_TOKEN_SUPER_SECRETO',

  // Tu Permanent Token de Meta (el largo que pegaste)
  'waba_token'       => 'EA...TU_TOKEN_META...',

  // Phone Number ID (Meta > WhatsApp > Getting Started)
  'phone_number_id'  => 'TU_PHONE_NUMBER_ID',

  // Log simple para depurar (queda dentro de /public/storage)
  'log_file'         => __DIR__ . '/../../storage/chat.log',
];
