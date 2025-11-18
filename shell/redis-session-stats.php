<?php

// Taken from: vendor/colinmollenhour/magento-redis-session/sessionStats.php
// php -c etc/php.cmd.ini shell/redis-session-stats.php sess_* http_user_agent writes

use \Illuminate\Support\Str;

if(php_sapi_name() !== "cli") {
  header("Location: /");
  exit;
}

putenv("MAGE_IS_DEVELOPER_MODE=1");

define("MAGENTO_ROOT", getcwd());

$server = "tcp://136.144.183.232:6379";
$db = 1;

PHP_SAPI == "cli" || die("CLI only.");

require "app/bootstrap.php";
require "app/Mage.php";

Mage::setIsDeveloperMode(true);
Mage::app(0);
Mage::init();

ini_set("display_errors", true);
ini_set("display_startup_errors", true);
error_reporting(E_ALL | E_STRICT);
ini_set("memory_limit", "1G");

if(empty($argv[1])) die("Must specify session glob pattern. E.g. sess_*");
$sessionPattern = $argv[1];

if(empty($argv[2])) die("Must specify group-by key. E.g. http_user_agent, remote_addr, http_secure, http_host, request_uri, is_new_visitor");
$groupBy = $argv[2];

if(empty($argv[3])) {
  die("Must specify sort-by parameter. writes or count");
}
$sortBy = $argv[3];

$getSessionData = function($data, $key) use($groupBy) {
  switch ($groupBy) {
    case "is_new_visitor":
      if(preg_match("/\"$key\";b:([01])/", (string) $data, $matches)) {
        return $matches[1];
      }
      break;
    default: // remote_addr, http_secure, http_host, http_user_agent, request_uri, is_new_visitor
      if(preg_match("/\"$key\";s:\\d+:\"([^\"]+)\"/", (string) $data, $matches)) {
        return $matches[1];
      }
      break;
  }
  return "N/A";
};

$redisSession = new Cm_RedisSession_Model_Session_Handler();
$client = $redisSession->redisClient(false)->connect();

$groupedData = [];
$cursor = 0;

while(1) {
  $keys = $client->keys($sessionPattern);
  $cursor = 0;
  
  foreach($keys as $sessionId) {
    $sessionData = $redisSession->inspectSession($sessionId);
    if(!isset($sessionData["data"])) {
      dump($sessionData);
      continue;
    }
    
    $key = $getSessionData($sessionData["data"], $groupBy);
    $groupedData[$key] ??= ["user_agent" => Str::limit($key, 150)];
    $groupedData[$key]["count"] ??= 0;
    $groupedData[$key]["count"]++;
    $groupedData[$key]["writes"] ??= 0;
    $groupedData[$key]["writes"] += $sessionData["writes"];
  }
  if($cursor == 0) {
    break;
  }
}

$sortKeys = [];
foreach($groupedData as $key => &$stats) {
  $stats["avg"] = round($stats["writes"] / $stats["count"]);
  $sortKeys[$key] = $sortBy == "writes" ? $stats["writes"] : $stats["count"];
}

// dump($sortKeys);

array_multisort($sortKeys, SORT_DESC | SORT_NUMERIC, $groupedData);

// dump($groupBy);

echo array_to_table($groupedData);

// echo "Count\tAvgWr\t$groupBy\n";
// foreach($groupedData as $key => $stats) {
//   echo "{$stats["count"]}\t{$stats["avg"]}\t$key\n";
// }
// echo "\n";
