<?php

// Emergency cache clearing:
if(isset($_GET["jatognietdan"])) {
  opcache_reset();
  echo "opcache reset";
  exit;
}

$dhh_ips = [
  "5.132.21.238", "185.127.111.251", "185.127.111.252", "87.210.61.235",
  "185.127.111.227", "81.59.51.217", "136.144.183.232", "31.20.126.5", "62.250.253.55",
];

if(!isset($_SERVER["REMOTE_ADDR"]) || !in_array($_SERVER["REMOTE_ADDR"], $dhh_ips)) {
  header("Location: /");
  exit;
}

require_once __DIR__."/../vendor/amnuts/opcache-gui/index.php";
