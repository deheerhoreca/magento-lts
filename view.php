<?php

declare(strict_types=1);

use \Elastic\Apm\ElasticApm;
use \Chefstore\ElasticApmHelper;

require_once __DIR__."/lib/Chefstore/ElasticApmHelper.php";

if(extension_loaded("elastic_apm") && class_exists(ElasticApm::class)) {
  try {
    $transaction = ElasticApm::getCurrentTransaction();
    if(is_object($transaction)) {
      $http_method = \trim(\strip_tags((string) $_SERVER["REQUEST_METHOD"]));
      $transaction->setName("{$http_method} view.php");
      $transaction->setType("frontend");
      $transaction->context()->setLabel("store_id", 1);
      $transaction->context()->setLabel("sapi", PHP_SAPI);
    }
  } catch(\Throwable $e) {
    // Do nothing
  }
}

if(!isset($_GET["id"]) || empty((string) $_GET["id"])) {
  header("Location: /", true, 302);
  exit;
}

$method   = "aes128";
$iv       = str_repeat("a", openssl_cipher_iv_length($method));
$id       = \trim(\strip_tags((string) $_GET["id"])); // Same process as JBStr::clean()
$id       = (string) base64_decode($id);
$url      = openssl_decrypt($id, $method, "wijdelengeenurls", 0, $iv);

if(isset($_GET["debug"])) {
  print_r($url);
  exit;
}

if(empty($url)) {
  header("Location: /?csredir=vid", true, 302);
  exit;
}

header("Location: {$url}", true, 302);
exit;
