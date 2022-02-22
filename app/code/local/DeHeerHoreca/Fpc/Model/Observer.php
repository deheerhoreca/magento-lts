<?php

if($_SERVER['REQUEST_METHOD'] === 'GET') {
  define('DHH_FPC_DEBUG', false);
} else {
  define('DHH_FPC_DEBUG', false);
}

if(substr($_SERVER['HTTP_HOST'], 0, 3) === "dev") {
  define("DHH_FPC_ENABLED", false);
} else {
  define("DHH_FPC_ENABLED", true);
}

const FPC_LOG = "./var/log/fpc.json";

class DeHeerHoreca_Fpc_Model_Observer extends Varien_Event_Observer {
  
  public function ServeCachedHTML($observer) {

    $formKeyPlaceholder = "<!-- fpc form_key_placeholder -->";
    $read_cache = Mage::helper("deheerhoreca_fpc/data")->is_read_cache_enabled(true);
    
    if($read_cache === false) {
      Mage::helper("deheerhoreca_util/util")->addLabelToClickLog("fpc_cache", "BYPASS");
      return;
    }
    
    $key = Mage::helper("deheerhoreca_fpc/data")->get_cache_key();
    $html = Mage::helper("deheerhoreca_fpc/data")->get_cached_html($key, false, true);

    if(empty($html) === false) {
     if(print($html)) {
        flush();
        Mage::helper("deheerhoreca_util/util")->addLabelToClickLog("fpc_cache", "HIT");
        // To allow for closing actions (AoE Profiler is one)
        Mage::dispatchEvent('controller_front_send_response_after');
        exit;
      }
    }
    
    Mage::helper("deheerhoreca_util/util")->addLabelToClickLog("fpc_cache", "MISS");
  }
  
  public function clearProductCache($observer) {
    $product = $observer->getProduct();
    $productId = $product->getId();
    $patterns = [];
    
    /* catalog_product_view */
    $patterns[] = "zc:k:e6b_FPC_catalog_product_view_{$productId}_*";
    // $result  = shell_exec("redis-cli --scan --pattern {$pattern} | xargs -I% redis-cli unlink \"%\"");
    
    /* catalog_category_view */
    $category_ids = $product->getCategoryIds();
    if(empty($category_ids) === false && is_array($category_ids) === true) {
      foreach($category_ids as $category_id) {
        $patterns[] = "zc:k:e6b_FPC_catalog_category_view_{$category_id}_*";
        // $result  = shell_exec("redis-cli --scan --pattern {$pattern} | xargs -I% redis-cli unlink \"%\"");
      }
    }
    
    $result = clean_fpc($patterns, true);
    
    Mage::getSingleton('core/session')->addSuccess("FPC patterns cleared: ".print_r($result, true));
    
    return true;
  }
  
  public function clearCategoryCache($observer) {
    $category     = $observer->getEvent()->getCategory();
    $category_id  = $category->getId();
    
    if(empty($category_id)) {
      return true;
    }
    
    $pattern = "zc:k:e6b_FPC_catalog_category_view_{$category_id}_*";
    $result  = clean_fpc($pattern, true);
    // $result  = shell_exec("redis-cli --scan --pattern {$pattern} | xargs -I% redis-cli unlink \"%\"");
    
    Mage::getSingleton('core/session')->addSuccess("FPC patterns cleared: ".print_r($result, true));
    
    return true;
  }
  
}

// @todo add option to unlink or delete
// @todo add higher level options
// Sync with intel's version
function clean_fpc($patterns, $nowait = false) {
  $patterns = (array) $patterns;
  $patterns = array_unique($patterns);
  $result = [];
  foreach($patterns as $pattern) {
    
    // if(strlen($pattern) < 5) {
      // logger("Refusing to clear FPC cache pattern with less than 5 characters: {$pattern}", "ERROR");
      // continue;
    // }
    
    // logger("Cleaning FPC pattern: {$pattern}", "VERBOSE");
    
    // Old style, slow:
    // $cmd = "redis-cli --scan --pattern \"{$pattern}_*\" | xargs redis-cli unlink"; // unlink is non-blocking
    
    // New style, with LUA:
    // defaultKey is there to supress "Wrong number of args calling Redis command From Lua script" if there are no matching keys
    $cmd = "redis-cli EVAL \"return redis.call('del', 'defaultKey', unpack(redis.call('keys', ARGV[1])))\" 0 \"{$pattern}*\"*";
    
    // Optionally dont wait for output:
    if($nowait === true) {
      $cmd .= " > /dev/null 2>&1 &";
      usleep(50 * 1000); // In batch, we might loose connection to Redis if we add too many connections, so wait 50ms
    }
    
    // if(VERY_VERBOSE) {
      // printr($cmd);
    // }
    
    $result[$pattern]  = shell_exec($cmd);
    
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
  
  return $result;
}
