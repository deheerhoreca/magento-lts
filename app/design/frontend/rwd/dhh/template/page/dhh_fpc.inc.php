<?php

if(defined('DHH_FPC_ENABLED') === false) {
  if(substr($_SERVER['HTTP_HOST'], 0, 3) === "dev") {
    define("DHH_FPC_ENABLED", true);
  } else {
    define("DHH_FPC_ENABLED", true);
  }
}

if(defined('DHH_FPC_DEBUG') === false) {
  define('DHH_FPC_DEBUG', false);
}

$formKeyPlaceholder = "<!-- fpc form_key_placeholder -->";

$write_cache = true;
$excluded_controllers = ["checkout_cart_index", "cms_index_noRoute"];

// Outqualification
$totalItemsInCart = Mage::helper('checkout/cart')->getItemsCount();
if($totalItemsInCart > 0) {
  $write_cache = false;
}
if(Mage::getSingleton('customer/session')->isLoggedIn()) {
  $write_cache = false;
}
if(isset($_GET['nofpc'])) {
  $write_cache = false;
}
if(isset($_GET['refreshfpc'])) {
  $write_cache = true;
}
if(DHH_FPC_ENABLED === false) {
  $write_cache = false;
}
if($_SERVER['REQUEST_METHOD'] !== 'GET') {
  $write_cache = false;
}
if(
  (in_array(Mage::app()->getFrontController()->getAction()->getFullActionName(), $excluded_controllers)) ||
  (strstr(Mage::app()->getFrontController()->getAction()->getFullActionName(), "checkout"))
  ) {
    $write_cache = false;
}

$_cacheKey = $cache_key_url = null;
if($write_cache === true) {
  $cache_key_url = Mage::helper("deheerhoreca_fpc/data")->get_cache_url();
  $cache_key_prefix = Mage::helper("deheerhoreca_fpc/data")->get_cache_prefix();
  
  $_cacheKey = "QUICKNDIRTYFPC-{$cache_key_prefix}-".base64_encode($cache_key_url);
  if(DHH_FPC_DEBUG === true) {
    print_r("Cache URL: {$cache_key_url}<br />Cache Key: {$_cacheKey}");
  }
}

$_html = null;

if(empty($_html) === false) {
  if(headers_sent() === false) {
    $bytes = strlen($_html);
    header("X-FPC: Hit {$cache_key_url}");
    if(DHH_FPC_DEBUG === true) {
      print_r("<br />Cache: HIT");
    }
  }
  echo $_html;
} else {
  ob_start();

  // Just include the parent file
  require_once __DIR__."/html/{$template_file}";

  $_html = ob_get_clean();

  if($write_cache === true) {

    // Handle form_key (CSRF protection)
    $formKey = Mage::getSingleton('core/session')->getFormKey();
    if ($formKey) {
      $_html_normalized = str_replace($formKey, $formKeyPlaceholder, $_html);
    }

    Mage::app()->getCache()->save($_html_normalized, $_cacheKey, ["quickndirtyfpc"], 86400);

    if(headers_sent() === false) {
      header("X-FPC: Saved {$cache_key_url}");
      if(DHH_FPC_DEBUG === true) {
        print_r("<br />Cache: SAVED");
      }
    }
  } else {
    if(headers_sent() === false) {
      header("X-FPC: No Cache");
      if(DHH_FPC_DEBUG === true) {
        print_r("<br />Cache: NO CACHE");
      }
    }
  }

  echo $_html;
}
