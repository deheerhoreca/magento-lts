<?php

function _dhh_ips() {
  return [
    // "5.132.21.238",
    // "185.127.111.251",
    // "185.127.111.252",
    // "87.210.61.235",
    // "185.127.111.227",
    // "81.59.51.217",
  ];
}

const DHH_FPC_NAV_KEY     = "DHH_CMS_TOPMENU";
const DHH_FPC_FOOTER_KEY  = "DHH_CMS_FOOTER";

// Cannot use _dhh_debug() due to the ?nofpc requirement
if(isset($_SERVER["REQUEST_METHOD"]) && ($_SERVER["REQUEST_METHOD"] === "GET" || $_SERVER["REQUEST_METHOD"] === "HEAD")
&& isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], _dhh_ips(), true)
) {
  define("DHH_FPC_DEBUG", true);    // Default: false
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
        exit(0);
      }
    }
    
    Mage::helper("deheerhoreca_util/util")->addLabelToClickLog("fpc_cache", "MISS");
    
    Varien_Profiler::stop("DHH::FPC::ServeCachedHTML");
  }
  
  public function clearProductCache($observer) {
    $productId = $observer->getProduct()->getId();
    
    $cache_tags = ["DHH_PRODUCT_{$productId}"];
    
    foreach($observer->getProduct()->getCategoryIds() as $category_id) {
      $cache_tags[] = "DHH_CATEGORY_{$category_id}";
    }
    
    return DeHeerHoreca_Fpc_Helper_Data::clean_by_tags($cache_tags);
  }
  
  public function clearCategoryCache($observer) {
    $category_id = $observer->getEvent()->getCategory()->getId();
    
    if(empty($category_id)) {
      return true;
    }
    
    $cache_tags = ["DHH_CATEGORY_{$category_id}"];
    return DeHeerHoreca_Fpc_Helper_Data::clean_by_tags($cache_tags);
  }
}
