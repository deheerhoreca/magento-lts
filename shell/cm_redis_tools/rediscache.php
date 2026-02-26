<?php

/*
Modified version of rediscli.php to clean up old tags that are not used anymore. It will also go through all the tags and check if the members of the tags are still used by OpenMage. If not, it will remove the member from the tag. This is useful to keep the stats on the dashboards more in line with the actual cache coverage.
*/

declare(strict_types=1);

define("SET_TAGS", "zc:tags");

$redis = new Redis();
$redis->connect("136.144.183.232", 6379);
$redis->select(0);  // select FPC

$tags                 = $redis->sMembers(SET_TAGS);
$tags_count           = count($tags);
$empty_tags_count     = 0;
$known_members        = [];
$membership_count     = 0;
$pruned_members_count = 0;

foreach($tags as $tag) {
	$tag              = "zc:ti:" . $tag;
	$tag_members      = $redis->sMembers($tag);
	$members_count    = 0;
  $set_member_count = count($tag_members);
  
  if($set_member_count > 100) {
    continue;
  }
  
  if($set_member_count > 1000) {
    echo "({$set_member_count})";
  } else {
    echo "|";
  }
	
	foreach ($tag_members as $tag_member) {
		if(!isset($known_members[$tag_member])) {
      $known_members[$tag_member] = $redis->exists("zc:k:{$tag_member}");
      usleep(1000); // 1ms breather
    }
    if($known_members[$tag_member]) {
      $members_count++;
      $membership_count++;
    } else {
      $pruned_members_count++;
      $redis->sRem($tag, $tag_member);
      echo "-";
    }
	}
	
	if($members_count == 0) {
		echo "0";
		$empty_tags_count++;
	} else {
		echo "+";
	}
}

$existing_keys = array_filter($known_members);
echo "\n\nTags: {$tags_count}, empty tags: {$empty_tags_count}, keys: ".count($existing_keys).", memberships: ".$membership_count.", missing memberships pruned: {$pruned_members_count}\n";
