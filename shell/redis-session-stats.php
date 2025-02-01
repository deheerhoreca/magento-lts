<?php

// Taken from: vendor/colinmollenhour/magento-redis-session/sessionStats.php
// 
// Usage:
// - mphp -c php.cmd.ini shell/redis-session-stats.php sess_* http_user_agent writes

$server = 'tcp://136.144.183.232:6379';
$db = 1;

PHP_SAPI == 'cli' || die('CLI only.');
require 'app/Mage.php';
Mage::app();

if (empty($argv[1])) {
    die('Must specify session glob pattern. E.g. sess_*');
}
$sessionPattern = $argv[1];
if (empty($argv[2])) {
    die('Must specify group-by key. E.g. http_user_agent, remote_addr, http_secure, http_host, request_uri, is_new_visitor');
}
$groupBy = $argv[2];
if (empty($argv[3])) {
    die('Must specify sort-by parameter. writes or count');
}
$sortBy = $argv[3];

$getSessionData = function ($data, $key) use ($groupBy)
{
    switch ($groupBy) {
        case 'is_new_visitor':
            if (preg_match("/\"$key\";b:([01])/", (string) $data, $matches)) {
                return $matches[1];
            }
            break;
        default: // remote_addr, http_secure, http_host, http_user_agent, request_uri, is_new_visitor
            if (preg_match("/\"$key\";s:\\d+:\"([^\"]+)\"/", (string) $data, $matches)) {
                return $matches[1];
            }
            break;
    }
    return 'N/A';
};

$redisSession = new Cm_RedisSession_Model_Session_Handler();
$client = $redisSession->redisClient(false)->connect();

// $client = new Credis_Client($server);
// $client->select($db);

$groupedData = [];
$cursor = 0;
while(1) {
    try {
        $result = $client->__call('scan', [$cursor, $sessionPattern, 10000]);
        // $result = $client->__call('scan', array($cursor, 'MATCH', $sessionPattern, 'COUNT', 10000));
        [$cursor, $keys] = $result;
    } catch (CredisException $e) {
        if ($e->getMessage() != "unknown command 'scan'") {
            throw $e;
        }
        $keys = $client->keys($sessionPattern);
        $cursor = 0;
    }
    foreach ($keys as $sessionId) {
        $sessionData = $redisSession->inspectSession($sessionId);
        $data = $sessionData['data'];
        $key = $getSessionData($data, $groupBy);
        $groupedData[$key]['count'] ++;
        $groupedData[$key]['writes'] += $sessionData['writes'];
    }
    if ($cursor == 0) {
        break;
    }
}

$sortKeys = [];
foreach ($groupedData as $key => &$stats) {
    $stats['avg'] = $stats['writes'] / $stats['count'];
    $sortKeys[$key] = $sortBy == 'writes' ? $stats['avg'] : $stats['count'];
}
array_multisort($sortKeys, SORT_DESC | SORT_NUMERIC, $groupedData);

echo "Count\tAvgWr\t$groupBy\n";
foreach ($groupedData as $key => $stats) {
    echo "{$stats['count']}\t{$stats['avg']}\t$key\n";
}
echo "\n";
