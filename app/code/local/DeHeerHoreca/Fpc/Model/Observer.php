<?php

declare(strict_types=1);

class DeHeerHoreca_Fpc_Model_Observer extends Varien_Event_Observer {
  
  /**
   * @return DeHeerHoreca_Fpc_Helper_Data
   */
  public function helper(): DeHeerHoreca_Fpc_Helper_Data {
    return Mage::helper("deheerhoreca_fpc/data");
  }
  
  /**
   * Serve cached HTML if available.
   * Observes: controller_action_predispatch event.
   *
   * @param  mixed $observer
   * @return void
   */
  public function ServeCachedHTML(Varien_Event_Observer $observer): void {
    Varien_Profiler::start("DHH::FPC::ServeCachedHTML");
    $dhhHelperUtil = getOmDhhUtilHelper();
    $dhhHelperFpc  = Mage::helper("deheerhoreca_fpc/data");
    
    // If FPC is disabled, skip:
    if(!$dhhHelperFpc->is_read_cache_enabled(true, false, "fpc")) {
      $dhhHelperUtil->addLabelToClickLog("fpc_cache", "BYPASS");
      Varien_Profiler::stop("DHH::FPC::ServeCachedHTML");
      return;
    }
    
    $key  = $dhhHelperFpc->get_cache_key();
    $html = $dhhHelperFpc->get_cached_html($key, true, true);
    
    if(!empty($html)) {
      // This normally runs from the observer http_response_send_before, but not in case of an FPC hit:
      $html = Fballiano_CssjsMinify_Model_Observer::minifyCssJs($html);
      if(print($html)) {
        flush();
        $dhhHelperUtil->addLabelToClickLog("fpc_cache", "HIT");
        
        // To allow for closing actions (AoE Profiler is one)
        Mage::dispatchEvent("controller_front_send_response_after");
        
        // Copying from Mage_Core_Model_App::run() finilization code here:
        // -------------------------------------------------------------------------------------
        // Finish the request explicitly, no output allowed beyond this point
        if (php_sapi_name() == "fpm-fcgi" && function_exists("fastcgi_finish_request")) {
            fastcgi_finish_request();
        } else {
          flush();
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
          session_write_close();
        }
        
        try {
          Mage::dispatchEvent("core_app_run_after", ["app" => Mage::app()]); // ! DHH: Altered line
        } catch (Throwable $e) {
          Mage::logException($e);
        }
        // -------------------------------------------------------------------------------------
        Varien_Profiler::stop("DHH::FPC::ServeCachedHTML");
        exit(0);
      }
    }
    
    $dhhHelperUtil->addLabelToClickLog("fpc_cache", "MISS");
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
  }
  
  /**
   * Implements https://github.com/OpenMage/magento-lts/issues/1105 without installing that old extension.
   * Observes: controller_action_layout_load_before
   *
   * Removes per-item layout handles from cacheable pages, like PRODUCT_123 and CATEGORY_456.
   * This prevents OpenMage from creating a large number of cache entries and cache tags for each product/category page.
   * This effectively disables custom design layouts per product/category, regardless of full page cache being enabled or not.
   * But this feature is not used and its fields are disabled in the product edit form.
   *
   * @param Varien_Event_Observer $observer
   * @return void
   */
  public static function removePerItemLayoutHandleInCache(Varien_Event_Observer $observer): void {
    return; // Disabled for now, to test if it causes any issues.
    $handles = $observer->getEvent()->getLayout()->getUpdate()->getHandles();
    foreach($handles as $key => $handle) {
      if(preg_match("/^PRODUCT_\d+$/", $handle) || preg_match("/^CATEGORY_\d+$/", $handle)) {
        $observer->getEvent()->getLayout()->getUpdate()->removeHandle($handle);
      }
    }
    $handles = $observer->getEvent()->getLayout()->getUpdate()->getHandles();
  }
  
  /**
   * Observe all models so that a cached page can be associated with all model instances
   * loaded in the course of page rendering.
   *
   * @see https://github.com/colinmollenhour/Cm_Diehard/blob/master/code/Model/Observer.php
   *
   * @param Varien_Event_Observer $observer
   * @return void
   */
  public function modelLoadAfter(Varien_Event_Observer $observer) {
    if($tags = $observer->getObject()->getCacheIdTags()) {
      $this->helper()->addTags($tags);
    }
  }
}
