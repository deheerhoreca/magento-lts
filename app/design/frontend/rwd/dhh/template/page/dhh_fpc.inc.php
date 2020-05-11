<?php

require_once __DIR__."/dhh_fpc.conf.php";

$write_cache = true;
$read_cache = true;

// Outqualification
$totalItemsInCart = Mage::helper('checkout/cart')->getItemsCount();
if($totalItemsInCart > 0) {
  $write_cache = false;
  $read_cache = false;
}
if(Mage::getSingleton('customer/session')->isLoggedIn()) {
  $write_cache = false;
  $read_cache = false;
}
if(isset($_GET['nofpc'])) {
  $write_cache = false;
  $read_cache = false;
}
if(isset($_GET['refreshfpc'])) {
  $write_cache = true;
  $read_cache = false;
}
if(DHH_FPC_ENABLED === false) {
  $write_cache = false;
  $read_cache = false;
}
if($_SERVER['REQUEST_METHOD'] !== 'GET') {
  $write_cache = false;
  $read_cache = false;
}

$_cacheKey = null;
if($read_cache === true || $write_cache === true) {
  $cache_key_input = get_cache_url();
  $_cacheKey = 'QUICKNDIRTYFPC-'.base64_encode($cache_key_input);
  //print_r("Cache URL: {$cache_key_input} - Cache Key: {$_cacheKey}");
}

$_html = null;

if($read_cache === true) {
  // Check if there is a cached version:
  $_html = Mage::app()->getCache()->load($_cacheKey);

  if($_html) {
    
    // Do not cache 404 content
    if(strstr($_html, $error404tag) !== false) {
      $_html = null;
      $read_cache = false;
      $write_cache = false;
    }
    
    // Handle form_key (CSRF protection)
    $formKey = Mage::getSingleton('core/session')->getFormKey();
    if($formKey) {
      $_html = str_replace($formKeyPlaceholder, $formKey, $_html);
    }
  }
}

if(headers_sent() === FALSE) {
  $r = "-r";
  $w = "-w";
  if($write_cache === true) $w = "+w";
  if($read_cache === true) $r = "+r";
  header("qnd-fpc-settings: {$r} {$w}");
}

if(empty($_html) === false) {
  if(headers_sent() === FALSE) {
    $bytes = strlen($_html);
    header("X-FPC: Hit");
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

    Mage::app()->getCache()->save($_html_normalized, $_cacheKey, ["quickndirtyfpc"], FPC_TTL);

    if(headers_sent() === FALSE) {
      header("X-FPC: Saved");
    }
  }

  echo $_html;
}
