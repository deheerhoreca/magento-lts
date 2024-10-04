<?php

function _dhh_ips() {
  return ["5.132.21.238", "185.127.111.251", "185.127.111.252", "87.210.61.235", "185.127.111.227", "81.59.51.217"];
}

const DHH_FPC_NAV_KEY     = "FPC_cms_block_topmenu";
const DHH_FPC_FOOTER_KEY  = "FPC_cms_block_footer";

// Cannot use _dhh_debug() due to the ?nofpc requirement
if(isset($_SERVER["REQUEST_METHOD"]) && ($_SERVER["REQUEST_METHOD"] === "GET" || $_SERVER["REQUEST_METHOD"] === "HEAD")
&& isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], _dhh_ips(), true)
) {
  define("DHH_FPC_DEBUG", false);   // Default: false
} else {
  define("DHH_FPC_DEBUG", false);   // Default: false
}

if(isset($_SERVER["HTTP_HOST"]) && str_starts_with((string) $_SERVER["HTTP_HOST"], "dev.")) {
  define("DHH_FPC_ENABLED", false); // Default: false
} else {
  define("DHH_FPC_ENABLED", true);  // Default: true
}

if(DHH_FPC_DEBUG) {
  $verb = $_SERVER["REQUEST_METHOD"] ?? null;
  DeHeerHoreca_Fpc_Helper_Data::log("---------------------------------------------------------------------");
  DeHeerHoreca_Fpc_Helper_Data::log("{$verb} ".Mage::helper("core/url")->getCurrentUrl());
}

class DeHeerHoreca_Fpc_Model_Observer extends Varien_Event_Observer {
  
  public function ServeCachedHTML($observer) {
    
    Varien_Profiler::start("DHH::FPC::ServeCachedHTML");
    
    $formKeyPlaceholder = "<!-- fpc form_key_placeholder -->";
    $read_cache = Mage::helper("deheerhoreca_fpc/data")->is_read_cache_enabled(true, false, "fpc");
    
    if($read_cache === false) {
      Mage::helper("deheerhoreca_util/util")->addLabelToClickLog("fpc_cache", "BYPASS");
      Varien_Profiler::stop("DHH::FPC::ServeCachedHTML");
      return;
    }
    
    $key = Mage::helper("deheerhoreca_fpc/data")->get_cache_key();
    $html = Mage::helper("deheerhoreca_fpc/data")->get_cached_html($key, true, true);

    if(empty($html) === false) {
     if(print($html)) {
        flush();
        Mage::helper("deheerhoreca_util/util")->addLabelToClickLog("fpc_cache", "HIT");
        // To allow for closing actions (AoE Profiler is one)
        Mage::dispatchEvent("controller_front_send_response_after");
        Varien_Profiler::stop("DHH::FPC::ServeCachedHTML");
        exit;
      }
    }
    
    Mage::helper("deheerhoreca_util/util")->addLabelToClickLog("fpc_cache", "MISS");
    
    Varien_Profiler::stop("DHH::FPC::ServeCachedHTML");
  }
  
  public function clearProductCache($observer) {
    
    $productId  = $observer->getProduct()->getId();
    
    // @todo Why do we need to put e6b_ when cleaning, but not when writing?
    $cache_tags = ["e6b_PRODUCT_{$productId}", "PRODUCT_{$productId}"];
    
    foreach($observer->getProduct()->getCategoryIds() as $category_id) {
      $cache_tags[] = "e6b_CATEGORY_{$category_id}";
      $cache_tags[] = "CATEGORY_{$category_id}";
    }
    
    return self::_clean_by_keys($cache_tags);
    
    // return false;
    
    // $patterns   = [];
    
    // /* catalog_product_view */
    // $patterns[] = "zc:k:e6b_FPC_catalog_product_view_{$productId}_*";
    // $patterns[] = "zc:k:e6b_eke_ogmeta_product_{$productId}_";
    // $patterns[] = "zc:k:e6b_tm_richsnippets_product_json_{$productId}_";
    
    // /* catalog_category_view */
    // $category_ids = $observer->getProduct()->getCategoryIds();
    // if(!empty($category_ids) && is_array($category_ids)) {
    //   foreach($category_ids as $category_id) {
    //     // From self::clearCategoryCache()
    //     $patterns[]   = "zc:k:e6b_FPC_catalog_category_view_{$category_id}_*";
    //     $patterns[]   = "zc:k:e6b_eke_ogmeta_category_{$category_id}_";
    //   }
    // }
    
    // $result     = clean_fpc_pattern($patterns, false);
    
    // return true;
  }
  
  private static function _clean_by_keys(string|array $cache_tags) {
    $cache_tags = (array) $cache_tags;
    
    if(DHH_FPC_DEBUG) {
      $cache_keys = Mage::app()->getCache()->getIdsMatchingAnyTags($cache_tags);
      Mage::log("Cleaning cache tags: ".var_export($cache_tags, true).". Matched keys: ".var_export($cache_keys, true), Zend_Log::DEBUG, "verbose.txt", true);
    }
    
    $response = Mage::app()->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $cache_tags);
    if(DHH_FPC_DEBUG) {
      Mage::log("Response: ".var_export($response, true), Zend_Log::DEBUG, "verbose.txt", true);
    }
    
    return $response;
  }
  
  public function clearCategoryCache($observer) {
    $category_id  = $observer->getEvent()->getCategory()->getId();
    
    if(empty($category_id)) {
      return true;
    }
    
    $cache_tags   = ["e6b_CATEGORY_{$category_id}"];
    $cache_tags[] = "CATEGORY_{$category_id}";
    return self::_clean_by_keys($cache_tags);
    
    // if(!empty($cache_tag) && Mage::app()->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, [$cache_tag])) {
    //   // Mage::getSingleton("core/session")->addSuccess("Redis cleared by Tag {$cache_tag}");
    //   return true;
    // }
  
    // return false;
    
    // $category     = $observer->getEvent()->getCategory();
    // $category_id  = $category->getId();
    
    // // Also to self::clearProductCache()
    // $patterns     = [];
    // $patterns[]   = "zc:k:e6b_FPC_catalog_category_view_{$category_id}_*";
    // $patterns[]   = "zc:k:e6b_eke_ogmeta_category_{$category_id}_";
    
    // $result       = clean_fpc_pattern($patterns, false);
    
    // return true;
  }
}

// Sync:
// deheerhoreca-magento/app/code/local/DeHeerHoreca/Fpc/Model/Observer.php
// deheerhoreca-intel/lib/intel.inc.php
function clean_fpc_pattern($patterns, $nowait = false) {
  $patterns = (array) $patterns;
  $result   = [];
  
  // if(Mage::helper("deheerhoreca_fpc/data")->is_write_cache_enabled(true, true, "tm_richsnippets")) {
  //   if(Mage::app()->getCache()->save(serialize($json), $cache_key, $cache_tags, 86400 * 7)) {
  //     DeHeerHoreca_Fpc_Helper_Data::log("SAVED {$cache_key}, ".strlen((string) $json)." chars");
  //   }
  // }
  
  foreach($patterns as $pattern) {
    
    if(strlen((string) $pattern) < 5) {
      // logger("Refusing to clear FPC cache pattern with less than 5 characters: {$pattern}", "ERROR");
      continue;
    }
    
    // logger("Cleaning FPC pattern: {$pattern}", "VERBOSE");
    
    // Old style, slow:
    // $cmd = "redis-cli --scan --pattern \"{$pattern}_*\" | xargs redis-cli unlink"; // unlink is non-blocking
    
    // New style, with LUA:
    // defaultKey is there to supress "Wrong number of args calling Redis command From Lua script" if there are no matching keys
    $cmd = "redis-cli EVAL \"return redis.call('del', 'defaultKey', unpack(redis.call('keys', ARGV[1])))\" 0 \"{$pattern}*\"*";
    
    // Optionally dont wait for output:
    if($nowait === true) {
      $cmd .= " > /dev/null 2>&1 &";
      msleep(50); // In batch, we might loose connection to Redis if we add too many connections, so wait a bit
      if(exec($cmd) !== false) {
        $result[$pattern] = "OK";
      } else {
        $result[$pattern] = "NOK";
      }
    } else {
      $result[$pattern] = shell_exec($cmd);
    }
    
    // if(DRYRUN === false) {
      // [$return, $output, $result_code] = safe_exec_full_output($cmd);
      // $result[] = [
        // "cmd"         => $cmd,
        // "pattern"     => $pattern,
        // "result"      => $return,
        // "result_code" => $result_code,
        // "output"      => $output,
        // "nowait"      => $nowait,
      // ];
    // }
  }
  
  if(sizeof($result) === 1) {
    $result = array_pop($result);
  }
  
  return $result;
}

function msleep(int $time): void {
  usleep($time * 1000);
}
