<?php

// From: https://github.com/colinmollenhour/Cm_Cache_Backend_Redis/blob/master/stats.php

require __DIR__ . '/../vendor/autoload.php';

$server = 'tcp://136.144.183.232:6379';
$db     = 0;
$limit  = 50;

if(str_starts_with((string) $_SERVER["HOSTNAME"] ?? "", "dev.")) {
  $server = 'tcp://127.0.0.1:6379';
}

array_shift($argv);
while ($arg = array_shift($argv)) {
  switch ($arg) {
    case '--db':
      $db = intval(array_shift($argv));
      break;
    case '--server':
      $server = array_shift($argv);
      break;
    case '--limit':
      $limit = array_shift($argv);
      break;
    default:
      die("Unrecognized argument '$arg'.\nUsage: path/to/stats.php [--server tcp://127.0.0.1:6378] [--db 0] [--limit 20]\n");
  }
}

$client = new Credis_Client($server);
$client->select($db);

$tagStats = [];
foreach($client->sMembers(Cm_Cache_Backend_Redis::SET_TAGS) as $tag) {
  if(preg_match('/^\w{3}_MAGE$/', (string) $tag)) {
    continue;
  }
  $ids = $client->sMembers(Cm_Cache_Backend_Redis::PREFIX_TAG_IDS . $tag);
  $tagSizes = [];
  $missing = 0;
  foreach($ids as $id) {
    // $data = $client->hGet(Cm_Cache_Backend_Redis::PREFIX_KEY.$id, Cm_Cache_Backend_Redis::FIELD_DATA);
    // $size = strlen($data);
    $size = 1;
    if($size) {
      $tagSizes[] = $size;
    } else {
      $missing++;
    }
  }
  if($tagSizes) {
    $tagStats[$tag] = [
      'count' => count($tagSizes),
      'min' => min($tagSizes),
      'max' => max($tagSizes),
      'avg size' => round(array_sum($tagSizes) / count($tagSizes)),
      'total size' => array_sum($tagSizes),
      'missing' => $missing,
    ];
  }
}

function _format_bytes($a_bytes) {
  if($a_bytes < 1024) {
    return $a_bytes . ' B';
  } elseif($a_bytes < 1048576) {
    return round($a_bytes / 1024, 0) . ' KB';
  } else {
    return round($a_bytes / 1048576, 0) . ' MB';
  }
}

function printStats($data, $key, $limit) {
  echo "Top $limit tags by " . ucwords((string) $key) . "\n";
  echo "------------------------------------------------------------------------------------\n";
  $sort = [];
  foreach($data as $tag => $stats) {
    $sort[$tag] = $stats[$key];
  }
  array_multisort($sort, SORT_DESC, $data);
  $i = 0;
  $fmt = "%-40s| %-8s| %-15s| %-15s\n";
  printf($fmt, 'Tag', 'Count', 'Avg Size', 'Total Size');
  foreach($data as $tag => $stats) {
    $tag = substr((string) $tag, 4);
    if(++$i > $limit) {
      break;
    }
    $avg = _format_bytes($stats['avg size']);
    $total = _format_bytes($stats['total size']);
    printf($fmt, $tag, $stats['count'], $avg, $total);
  }
  echo "\n";
}

// Top 20 by total size
// printStats($tagStats, 'total size', $limit);

// Top 20 by average size
// printStats($tagStats, 'avg size', $limit);

// Top 20 by count
printStats($tagStats, 'count', $limit);
