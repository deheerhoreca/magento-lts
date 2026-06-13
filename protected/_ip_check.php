<?php

declare(strict_types=1);

/**
 * IP Check - restrict access to certain IPs only.
 * 
 * Require this file at the start of scripts that should be protected.
 */

// $dhh_ips = [
//   "5.132.21.238", "185.127.111.251", "185.127.111.252", "87.210.61.235",
//   "185.127.111.227", "81.59.51.217", "136.144.183.232", "31.20.126.5", "62.250.253.55",
//   "31.201.36.137",
// ];

// $effective_ip = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER["HTTP_X_FORWARDED_FOR"] ?? $_SERVER["REMOTE_ADDR"] ?? null;

require __DIR__."/../vendor/autoload.php";

if(!isDevIp()) {
  exit;
}

if(!isDevIp()) {
  // header("Location: /");
  echo $_SERVER["REMOTE_ADDR"];
  echo $_SERVER["HTTP_CF_CONNECTING_IP"];
  echo $_SERVER["HTTP_X_FORWARDED_FOR"];
  header("HTTP/1.0 403 Forbidden");
  exit;
}
