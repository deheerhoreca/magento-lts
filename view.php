<?php

if(empty($_GET["id"]) === true) {
  header("Location: /", true, 301);
}

$method         = "aes128";
$iv             = str_repeat("a", openssl_cipher_iv_length($method));
$url            = openssl_decrypt(base64_decode($_GET["id"]), $method, "wijdelengeenurls", 0, $iv);

header("Location: {$url}", true, 301);
