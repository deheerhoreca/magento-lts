<?php

declare(strict_types=1);

// Primarily store in remote storage, as well as local storage.
$pri_output = "/mnt/bigstorage/intel/output/integration/freshdesk/freshdesk-webhook.ndjson";
$sec_output = __DIR__."/freshdesk-webhook.ndjson";
$output_err = __DIR__."/freshdesk-webhook-error.ndjson";

$json       = file_get_contents("php://input");
$data       = json_decode($json, true);
$data       = [
  "@timestamp"    => date("c"),
  "remote_addr"   => $_SERVER['REMOTE_ADDR'],
  "forwarded_for" => ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? null),
  "payload"       => (array) $data,
  "process"       => [],
];

// Store event in log
$json = json_encode($data).PHP_EOL;

try {
  // Local storage first
  touch($sec_output);
  file_put_contents($sec_output, $json, FILE_APPEND);
  touch($pri_output);
  file_put_contents($pri_output, $json, FILE_APPEND);
  http_response_code(202);
  echo "OK";
  exit(0);
} catch(Exception $e) {
  // Write the captured data to the error log
  file_put_contents($output_err, $json, FILE_APPEND);
  $json = json_encode($e->getMessage());
  // Also log the exception message
  file_put_contents($output_err, $json, FILE_APPEND);
}

http_response_code(500);
echo "ERROR";
exit(1);
