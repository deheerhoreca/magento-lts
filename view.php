<?php

if(!isset($_GET["id"]) || empty((string) $_GET["id"])) {
  header("Location: /", true, 302);
  exit;
}

$method         = "aes128";
$iv             = str_repeat("a", openssl_cipher_iv_length($method));
$url            = openssl_decrypt(base64_decode((string) $_GET["id"]), $method, "wijdelengeenurls", 0, $iv);

if(empty($url)) {
  header("Location: /", true, 302);
  exit;
}

header("Location: {$url}", true, 302);
exit;
