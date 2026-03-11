<?php

/*
openmage shell/cm_redis_tools/rediscache.php

Modified version of rediscli.php to clean up old tags that are not used anymore.
It will also go through all the tags and check if the members of the tags are still used by OpenMage.
If not, it will remove the member from the tag.
This is useful to keep the stats on the dashboards more in line with the actual cache coverage.
*/

declare(strict_types=1);

define("CRON", isset($_SERVER["FAKE_CRON"]) || (!isset($_SERVER["SSH_CLIENT"]) && (\PHP_SAPI === 'cli' && \defined('STDOUT') && \defined('STDERR')) && !isset($_SERVER["NOTCRON"])));
define("SET_TAGS", "zc:tags");

$redis = new Redis();
$redis->connect("136.144.183.232", 6379);
$redis->select(0);  // select FPC

$tags                 = $redis->sMembers(SET_TAGS);
$tags_count           = count($tags);
$known_keys           = [];
$membership_count     = 0;
$removedMembersCount  = 0;
$empty_tags_deleted   = 0;

// Get all keys in one go to avoid multiple roundtrips to Redis.
// Expensive, but ultimately faster than checking each key individually in the loop below.
$all_keys             = $redis->keys("zc:k:*");
$known_keys           = array_fill_keys($all_keys, true);

asort($all_keys);
asort($tags);
ksort($known_keys);

// print_r($known_keys);exit;

$toRemoveFromTagsSet = [];

foreach($tags as $tag) {
	$fullTag          = "zc:ti:".$tag;
	$tag_members      = $redis->sMembers($fullTag);
	$members_count    = 0;
  $set_member_count = count($tag_members);
  $to_remove        = [];
  
  // Non-existing SET or empty SET, can be safely removed from SET_TAGS
  if($set_member_count === 0) {
    $toRemoveFromTagsSet[] = $tag;
    if(!CRON) echo "*";
    continue;
  }
  
  $membership_count += $set_member_count;
  
	foreach($tag_members as $tag_member) {
    $full_key = "zc:k:".$tag_member;
    
    if(!array_key_exists($full_key, $known_keys) || !$known_keys[$full_key]) {
      $to_remove[] = $tag_member;
      if(!CRON) echo "-";
      // if(!CRON) print_r("\nMissing set member: {$tag_member}\n");
    } else {
      $members_count++;
      if(!CRON) echo " ";
    }
	}
  
  if($to_remove !== []) {
    // $removed = count($to_remove); // (during dryrun)
    $removed = $redis->sRem($fullTag, ...$to_remove);
    $removedMembersCount += $removed;
    $to_remove = [];
  }
}

// Remove empty/missing tags from SET_TAGS SET
if($toRemoveFromTagsSet !== []) {
  $toRemoveFromTagsSet  = array_unique($toRemoveFromTagsSet);
  $empty_tags_count     = count($toRemoveFromTagsSet);
  // $empty_tags_deleted   = $empty_tags_count; // Assuming all tags in $toRemoveFromTagsSet are deleted (during dryrun)
  $empty_tags_deleted   = $redis->sRem(SET_TAGS, ...$toRemoveFromTagsSet);
  if(!CRON) print_r("\nRemoved {$empty_tags_deleted} empty tags from ".SET_TAGS.": ".implode(", ", $toRemoveFromTagsSet)."\n");
}

$existing_keys = array_filter($known_keys);
echo "\n\nTags count: {$tags_count}, empty tags deleted from zc:tags: {$empty_tags_deleted}, keys count: ".count($existing_keys).", memberships count: ".$membership_count.", missing members removed: {$removedMembersCount}\n";
