<?php

if(empty($_GET["id"]) === true) {
  header("Location: /", true, 301);
  exit;
}

$method         = "aes128";
$iv             = str_repeat("a", openssl_cipher_iv_length($method));
$url            = openssl_decrypt(base64_decode($_GET["id"]), $method, "wijdelengeenurls", 0, $iv);

if(empty($$url) === true) {
  header("Location: /", true, 301);
  exit;
}

header("Location: {$url}", true, 301);
exit;
