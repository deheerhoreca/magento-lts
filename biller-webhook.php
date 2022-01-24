<?php

$output = __DIR__."/biller-webhook.jsonl";
$output_err = __DIR__."/biller-webhook-errors.jsonl";
$php_err = __DIR__."/biller-webhook-errors.jsonl";

ini_set("log_errors", 1);
ini_set("error_log", __DIR__."/{$php_err}");

touch($output);
touch($output_err);
touch($php_err);

$json = file_get_contents("php://input");
$data = json_decode($json, true);

// if(empty($data)) {
  // http_response_code(400);
  // echo "Bad request";
  // exit;
// }

$data = [
  "@timestamp"    => date("c"),
  "remote_addr"   => $_SERVER['REMOTE_ADDR'],
  "forwarded_for" => ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? null),
  "payload"       => (array) $data,
];

$json = json_encode($data).PHP_EOL;

// print_r($json);

try {
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
