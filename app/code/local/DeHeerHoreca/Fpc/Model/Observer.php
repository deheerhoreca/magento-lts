<?php

declare(strict_types=1);

// const DHH_FPC_NAV_KEY     = "DHH_CMS_TOPMENU";
// const DHH_FPC_FOOTER_KEY  = "DHH_CMS_FOOTER";

// // Cannot use _dhh_debug() due to the ?nofpc requirement
// if(isset($_SERVER["REQUEST_METHOD"]) && ($_SERVER["REQUEST_METHOD"] === "GET" || $_SERVER["REQUEST_METHOD"] === "HEAD")
// && isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], _dhh_ips(), true)
// ) {
//   define("DHH_FPC_DEBUG", false);   // Default: false
// } else {
//   define("DHH_FPC_DEBUG", false);   // Default: false
// }

// if(isset($_SERVER["HTTP_HOST"]) && str_starts_with((string) $_SERVER["HTTP_HOST"], "dev.")) {
//   define("DHH_FPC_ENABLED", false); // Default: false
// } else {
//   define("DHH_FPC_ENABLED", true);  // Default: true
// }

// if(DHH_FPC_DEBUG) {
//   $verb = $_SERVER["REQUEST_METHOD"] ?? null;
//   DeHeerHoreca_Fpc_Helper_Data::log("---------------------------------------------------------------------");
//   DeHeerHoreca_Fpc_Helper_Data::log("{$verb} ".Mage::helper("core/url")->getCurrentUrl());
// }

class DeHeerHoreca_Fpc_Model_Observer extends Varien_Event_Observer {
  /**
   * Serve cached HTML if available.
   * Observes: controller_action_predispatch event.
   *
   * @param  mixed $observer
   * @return void
   */
  public function ServeCachedHTML($observer): void {
    Varien_Profiler::start("DHH::FPC::ServeCachedHTML");
    $read_cache = Mage::helper("deheerhoreca_fpc/data")->is_read_cache_enabled(true, false, "fpc");
    if($read_cache === false) {
      Mage::helper("deheerhoreca_util/util")->addLabelToClickLog("fpc_cache", "BYPASS");
      Varien_Profiler::stop("DHH::FPC::ServeCachedHTML");
      return;
    }
    $key = Mage::helper("deheerhoreca_fpc/data")->get_cache_key();
    $html = Mage::helper("deheerhoreca_fpc/data")->get_cached_html($key, true, true);
    
    if(!empty($html)) {
      // This normally runs from the observer http_response_send_before, but not in case of an FPC hit:
      $html = Fballiano_CssjsMinify_Model_Observer::minifyCssJs($html);
      if(print($html)) {
        flush();
        Mage::helper("deheerhoreca_util/util")->addLabelToClickLog("fpc_cache", "HIT");
        
        // To allow for closing actions (AoE Profiler is one)
        Mage::dispatchEvent("controller_front_send_response_after");
        
        // Copying from Mage_Core_Model_App::run() finilization code here:
        // -------------------------------------------------------------------------------------
        // Finish the request explicitly, no output allowed beyond this point
        if (php_sapi_name() == 'fpm-fcgi' && function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            flush();
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        try {
            Mage::dispatchEvent('core_app_run_after', ['app' => Mage::app()]); // ! DHH: Altered line
        } catch (Throwable $e) {
            Mage::logException($e);
        }
        // -------------------------------------------------------------------------------------
        
        Varien_Profiler::stop("DHH::FPC::ServeCachedHTML");
        exit(0);
      }
    }
    
    Mage::helper("deheerhoreca_util/util")->addLabelToClickLog("fpc_cache", "MISS");
    Varien_Profiler::stop("DHH::FPC::ServeCachedHTML");
  }
  
  /**
   * Clear product cache on product save.
   * Observes: catalog_product_save_commit_after
   *
   * @param  Varien_Event_Observer $observer
   * @return bool
   */
  public function clearProductCache(Varien_Event_Observer $observer): bool {
    $entityId = $observer->getProduct()->getId();
    if(empty($entityId)) {
      return true;
    }
    $cache_tags = [
      // "DHH_PRODUCT_{$entityId}",
      "PRODUCT_{$entityId}",
    ];
    foreach($observer->getProduct()->getCategoryIds() as $category_id) {
      // $cache_tags[] = "DHH_CATEGORY_{$category_id}";
      $cache_tags[] = "CATEGORY_{$category_id}";
    }
    
    return DeHeerHoreca_Fpc_Helper_Data::cleanCacheByTagsDeferred($cache_tags);
    // return DeHeerHoreca_Fpc_Helper_Data::clean_by_tags($cache_tags);
  }
  
  /**
   * Clear product cache on saving attributes to MULTIPLE products (mass action).
   * Observes: catalog_product_attribute_update_after
   *
   * @param  Varien_Event_Observer $observer
   * @return bool
   */
  public function clearProductsCache(Varien_Event_Observer $observer): bool {
    $product_ids = $observer->getEvent()->getProductIds();
    if(empty($product_ids) || !is_array($product_ids)) {
      return true;
    }
    $cache_tags = [];
    foreach($product_ids as $entityId) {
      // $cache_tags[] = "DHH_PRODUCT_{$entityId}";
      $cache_tags[] = "PRODUCT_{$entityId}";
      
      $product = Mage::getModel("catalog/product")->load($entityId);
      foreach($product->getCategoryIds() as $category_id) {
        // $cache_tags[] = "DHH_CATEGORY_{$category_id}";
        $cache_tags[] = "CATEGORY_{$category_id}";
      }
    }
    
    return DeHeerHoreca_Fpc_Helper_Data::cleanCacheByTagsDeferred($cache_tags);
  }
  
  /**
   * Clear category cache on category save.
   * Observes: catalog_category_save_commit_after
   *
   * @param  Varien_Event_Observer $observer
   * @return bool
   */
  public function clearCategoryCache(Varien_Event_Observer $observer): bool {
    $entityId = $observer->getEvent()->getCategory()->getId();
    if(empty($entityId)) {
      return true;
    }
    $cache_tags = [
      // "DHH_CATEGORY_{$entityId}",
      "CATEGORY_{$entityId}",
    ];
    
    return DeHeerHoreca_Fpc_Helper_Data::cleanCacheByTagsDeferred($cache_tags);
    // return DeHeerHoreca_Fpc_Helper_Data::clean_by_tags($cache_tags);
  }
}
