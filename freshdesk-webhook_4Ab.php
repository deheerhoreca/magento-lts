<?php

declare(strict_types=1);

$output = __DIR__."/freshdesk-webhook.ndjson";
$json   = file_get_contents("php://input");
$data   = json_decode($json, true);
$data   = [
  "@timestamp"    => date("c"),
  "remote_addr"   => $_SERVER['REMOTE_ADDR'],
  "forwarded_for" => ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? null),
  "payload"       => (array) $data,
  "process"       => [],
];

// Store event in log
$json = json_encode($data).PHP_EOL;

try {
  touch($output);
  if(file_put_contents($output, $json, FILE_APPEND)) {
    http_response_code(202);
    echo "OK";
    exit;
  } else {
    http_response_code(500);
  }
} catch(Exception $e) {
  $json = json_encode($e->getMessage());
  if(file_put_contents($output_err, $json, FILE_APPEND)) {
    http_response_code(500);
  } else {
    http_response_code(500);
  }
}
