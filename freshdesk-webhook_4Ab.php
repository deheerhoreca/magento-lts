<?php

declare(strict_types=1);

function appendNdjsonWithLock(string $path, string $line, int $timeoutMicros = 5_000_000, int $retryDelayMicros = 100_000): void {
  $handle = fopen($path, "c");
  if($handle === false) {
    throw new RuntimeException(sprintf("Unable to open output file: %s", $path));
  }
  
  try {
    $deadline     = microtime(true) + ($timeoutMicros / 1_000_000);
    $lockAcquired = false;
    
    do {
      if(flock($handle, LOCK_EX | LOCK_NB)) {
        $lockAcquired = true;
        break;
      }
      
      usleep($retryDelayMicros);
    } while (microtime(true) < $deadline);
    
    if(!$lockAcquired) {
      throw new RuntimeException(sprintf("Timed out waiting for output file lock: %s", $path));
    }
    
    if(fseek($handle, 0, SEEK_END) !== 0) {
      throw new RuntimeException(sprintf("Unable to seek output file: %s", $path));
    }
    
    if(fwrite($handle, $line) === false) {
      throw new RuntimeException(sprintf("Unable to write output file: %s", $path));
    }
    
    fflush($handle);
  } finally {
    flock($handle, LOCK_UN);
    fclose($handle);
  }
}

// Primarily store in remote storage, and provide a local fallback path to not lose the message.
// ! The fallback data source is not picked up by any consumers, and there is no process to retry writing it to the primary.
// @todo Build a check into intel's OpenMage status checks to detect if the fallback file has any data, and alert.

$pri_output = "/mnt/bigstorage/intel/output/integration/freshdesk/freshdesk-webhook.ndjson";
$output_err = __DIR__."/freshdesk-webhook-error.ndjson";
$json       = file_get_contents("php://input");
$data       = [
  "@timestamp"    => date("c"),
  "remote_addr"   => $_SERVER["REMOTE_ADDR"],
  "forwarded_for" => ($_SERVER["HTTP_X_FORWARDED_FOR"] ?? null),
  "payload"       => (array) json_decode($json, true),
  "process"       => [],
];
$json       = json_encode($data).PHP_EOL;

try {
  appendNdjsonWithLock($pri_output, $json);
  http_response_code(202);
  echo "OK";
  exit(0);
} catch (Throwable $e) {
  appendNdjsonWithLock($output_err, $json);
  $json = json_encode($e->getMessage()).PHP_EOL;
  appendNdjsonWithLock($output_err, $json);
}

http_response_code(500);
echo "ERROR";
exit(1);
