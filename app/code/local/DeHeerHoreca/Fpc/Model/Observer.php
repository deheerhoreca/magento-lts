<?php

const DHH_FPC_DEBUG = false;

if(substr($_SERVER['HTTP_HOST'], 0, 3) === "dev") {
  define("DHH_FPC_ENABLED", false);
} else {
  define("DHH_FPC_ENABLED", true);
}

class DeHeerHoreca_Fpc_Model_Observer extends Varien_Event_Observer {
  
  public function ServeCachedHTML($observer) {

    $formKeyPlaceholder = "<!-- fpc form_key_placeholder -->";
    $read_cache = true;

    // Outqualification
    if(Mage::helper('checkout/cart')->getItemsCount() > 0) {
      $read_cache = false;
    } elseif(Mage::getSingleton('customer/session')->isLoggedIn()) {
      $read_cache = false;
    } elseif(isset($_GET['nofpc'])) {
      $read_cache = false;
    } elseif(isset($_GET['refreshfpc'])) {
      $read_cache = false;
    } elseif(DHH_FPC_ENABLED === false) {
      $read_cache = false;
    } elseif($_SERVER['REQUEST_METHOD'] !== 'GET') {
      $read_cache = false;
    } elseif(in_array(Mage::app()->getFrontController()->getAction()->getFullActionName(),
        ["checkout_cart_index", "cms_index_noRoute"])
      ) {
      $read_cache = false;
    } elseif(strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "checkout")) {
      $read_cache = false;
    }

    $_cacheKey = $cache_key_url = null;
    if($read_cache === true) {
      $cache_key_url = Mage::helper("deheerhoreca_fpc/data")->get_cache_url();
      $cache_key_prefix = Mage::helper("deheerhoreca_fpc/data")->get_cache_prefix();
      
      $_cacheKey = "QUICKNDIRTYFPC-{$cache_key_prefix}-".base64_encode($cache_key_url);
      if(DHH_FPC_DEBUG === true) {
        print_r("Cache URL: {$cache_key_url}<br />Cache Key: {$_cacheKey}");
      }
    }

    $_html = null;

    if($read_cache === true) {
      // Check if there is a cached version:
      $_html = Mage::app()->getCache()->load($_cacheKey);

      if(empty($_html) === false) {
        // Handle form_key (CSRF protection)
        $formKey = Mage::getSingleton('core/session')->getFormKey();
        if(empty($formKey) === false) {
          if(DHH_FPC_DEBUG === true) {
            print_r("<br />Form key: {$formKey}");
          }
          $_html = str_replace($formKeyPlaceholder, $formKey, $_html);
        }
      }
    }

    if(empty($_html) === false) {
      if(headers_sent() === false) {
        $bytes = strlen($_html);
        header("X-FPC: Hit {$cache_key_url}");
        if(DHH_FPC_DEBUG === true) {
          print_r("<br />Cache: HIT");
        }
      }
      if(print($_html)) {
        exit;
      }
    }
  }
}
