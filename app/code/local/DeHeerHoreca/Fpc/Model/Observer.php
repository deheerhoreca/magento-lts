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
    
    /* catalog_product_view */
    $pattern = "*QUICKNDIRTYFPC_catalog_product_view_{$productId}_*";
    $result .= shell_exec("redis-cli --scan --pattern {$pattern} | xargs -I% redis-cli unlink \"%\"");
    // Mage::getSingleton('core/session')->addSuccess("FPC Cache cleared: {$result}x catalog_product_view");
    
    /* catalog_category_view */
    $category_ids = $product->getCategoryIds();
    $result = null;
    if(empty($category_ids) === false) {
      foreach($category_ids as $category_id) {
        $pattern = "*QUICKNDIRTYFPC_catalog_category_view_{$category_id}_*";
        $result .= shell_exec("redis-cli --scan --pattern {$pattern} | xargs -I% redis-cli unlink \"%\"");
      }
    }
    // Mage::getSingleton('core/session')->addSuccess("FPC Cache cleared: {$result}x catalog_category_view");
    
    return true;
  }
  
}
