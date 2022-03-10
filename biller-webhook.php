<?php

// Init Magento
require_once(__DIR__."/app/Mage.php");
umask(0);
Mage::app();

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
  "process"       => [],
];

// Send event via email

$event_type = $data["payload"]["event_type"] ?? "unknown_event";
$order_id   = $data["payload"]["payload"]["order_id"] ?? "unknown_order_id";
$subject    = "Biller webhook event: {$event_type} [{$order_id}]";
$body       = "{$subject}\n\n".print_r($data, true);
$from_email = "klaas@chefstore.nl";
$from_name  = "De Heer Horeca Intel";
$to_email   = "orders@deheerhoreca.nl";

$mail = new Zend_Mail();
$mail->setFrom($from_email, $from_name);
$mail->setSubject($subject);
$mail->setBodyText($body);
$mail->addTo($to_email);

try {
  if($mail->send()) {
    $data["process"]["email_sent"] = true;
  } else {
    $data["process"]["email_sent"] = false;
    $data["process"]["email_exception"] = "none";
  }
} catch(Exception $e) {
  $exception_msg = $e->getMessage() ?? "unknown";
  $data["process"]["email_sent"] = false;
  $data["process"]["email_exception"] = $exception_msg;
}

// Store event in log
// print_r($json);
$json = json_encode($data).PHP_EOL;

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
