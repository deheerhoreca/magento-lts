<?php

function _dhh_ips() {
  return ["5.132.21.238", "185.127.111.251", "185.127.111.252", "87.210.61.235", "185.127.111.227", "81.59.51.217"];
}

// Cannot use _dhh_debug() due to the ?nofpc requirement
if($_SERVER["REQUEST_METHOD"] === "GET"
&& isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], _dhh_ips(), true)
) {
  define("DHH_FPC_DEBUG", false); // Set to true to enable logging
} else {
  define("DHH_FPC_DEBUG", false); // Should always be false unless wired in
}

if(substr($_SERVER["HTTP_HOST"], 0, 3) === "dev") {
  define("DHH_FPC_ENABLED", false);
} else {
  define("DHH_FPC_ENABLED", true);
}

const DHH_FPC_NAV_KEY = "FPC_cms_block_topmenu";

if(DHH_FPC_DEBUG === true) {
  $verb = $_SERVER["REQUEST_METHOD"] ?? null;
  DeHeerHoreca_Fpc_Helper_Data::log("---------------------------------------------------------------------");
  DeHeerHoreca_Fpc_Helper_Data::log("{$verb} ".Mage::helper("core/url")->getCurrentUrl());
}

class DeHeerHoreca_Fpc_Model_Observer extends Varien_Event_Observer {
  
  public function ServeCachedHTML($observer) {
    
    Varien_Profiler::start("DHH::FPC::ServeCachedHTML");
    
    $formKeyPlaceholder = "<!-- fpc form_key_placeholder -->";
    $read_cache = Mage::helper("deheerhoreca_fpc/data")->is_read_cache_enabled(true);
    
    if($read_cache === false) {
      Mage::helper("deheerhoreca_util/util")->addLabelToClickLog("fpc_cache", "BYPASS");
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
        exit;
      }
    }
    
    Mage::helper("deheerhoreca_util/util")->addLabelToClickLog("fpc_cache", "MISS");
    
    Varien_Profiler::stop("DHH::FPC::ServeCachedHTML");
  }
  
  public function clearProductCache($observer) {
    $product = $observer->getProduct();
    $productId = $product->getId();
    $patterns = [];
    
    /* catalog_product_view */
    $patterns[] = "zc:k:e6b_FPC_catalog_product_view_{$productId}_*";
    
    /* catalog_category_view */
    $category_ids = $product->getCategoryIds();
    if(empty($category_ids) === false && is_array($category_ids) === true) {
      foreach($category_ids as $category_id) {
        $patterns[] = "zc:k:e6b_FPC_catalog_category_view_{$category_id}_*";
      }
    }
    
    $result = clean_fpc_pattern($patterns, false);    
    // Mage::getSingleton("core/session")->addSuccess("FPC patterns cleared: ".clean_fpc_pattern_result_to_string($result));
    
    return true;
  }
  
  public function clearCategoryCache($observer) {
    $category     = $observer->getEvent()->getCategory();
    $category_id  = $category->getId();
    
    if(empty($category_id)) {
      return true;
    }
    
    $pattern = "zc:k:e6b_FPC_catalog_category_view_{$category_id}_*";
    $result  = clean_fpc_pattern($pattern, false);
    // Mage::getSingleton("core/session")->addSuccess("FPC patterns cleared: ".clean_fpc_pattern_result_to_string($result));
    
    return true;
  }
}

function clean_fpc_pattern_result_to_string($result) {
  $outputs = [];
  $result = (array) $result;
  foreach($result as $pattern => $return) {
    $outputs[] = "{$pattern}: {$return}";
  }
  
  return implode(", ", $outputs);
}

// Sync:
// deheerhoreca-magento/app/code/local/DeHeerHoreca/Fpc/Model/Observer.php
// deheerhoreca-intel/lib/intel.inc.php
function clean_fpc_pattern($patterns, $nowait = false) {
  $patterns = (array) $patterns;
  $result = [];
  foreach($patterns as $pattern) {
    
    if(strlen($pattern) < 5) {
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
      msleep(100); // In batch, we might loose connection to Redis if we add too many connections, so wait a bit
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
