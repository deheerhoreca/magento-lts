<?php

/*
openmage shell/cm_redis_tools/rediscache.php

Modified version of rediscli.php to clean up old tags that are not used anymore.
It will also go through all the tags and check if the members of the tags are still used by OpenMage.
If not, it will remove the member from the tag.
This is useful to keep the stats on the dashboards more in line with the actual cache coverage.
*/

declare(strict_types=1);

define("SET_TAGS", "zc:tags");

$redis = new Redis();
$redis->connect("136.144.183.232", 6379);
$redis->select(0);  // select FPC

$tags                 = $redis->sMembers(SET_TAGS);
$tags_count           = count($tags);
$empty_tags_count     = 0;
$known_keys           = [];
$membership_count     = 0;
$pruned_members_count = 0;

// Get all keys in one go to avoid multiple roundtrips to Redis.
// Expensive, but ultimately faster than checking each key individually in the loop below.
$all_keys             = $redis->keys("zc:k:*");
$known_keys           = array_fill_keys($all_keys, true);

foreach($tags as $tag) {
	$tag              = "zc:ti:".$tag;
	$tag_members      = $redis->sMembers($tag);
	$members_count    = 0;
  $set_member_count = count($tag_members);
  $to_remove        = [];
  
	foreach($tag_members as $tag_member) {
		// if(!isset($known_keys[$tag_member])) {
    //   $known_keys[$tag_member] = $redis->exists("zc:k:{$tag_member}");
    //   usleep(500); // .5ms breather
    // }
    if(!array_key_exists($tag_member, $known_keys) || !$known_keys[$tag_member]) {
      $members_count++;
      $membership_count++;
      // echo " ";
    } else {
      $to_remove[] = $tag_member;
      // echo "-";
    }
	}
  
  if($to_remove !== []) {
    $removed = $redis->sRem($tag, ...$to_remove);
    // echo $removed."x";
    $pruned_members_count += $removed;
    $to_remove = [];
  }
	
	if($members_count == 0) {
		// echo "0";
		$empty_tags_count++;
	} else {
		// echo "+";
	}
  
  // echo ".";
}

$existing_keys = array_filter($known_keys);
echo "\n\nTags: {$tags_count}, empty tags: {$empty_tags_count}, known cache keys: ".count($existing_keys).", memberships: ".$membership_count.", missing memberships pruned: {$pruned_members_count}\n";
