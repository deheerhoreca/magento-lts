<?php

if(defined('DHH_FPC_ENABLED') === false) {
  if(substr($_SERVER['HTTP_HOST'], 0, 3) === "dev") {
    define("DHH_FPC_ENABLED", false);
  } else {
    define("DHH_FPC_ENABLED", true);
  }
}

if(defined('DHH_FPC_DEBUG') === false) {
  define('DHH_FPC_DEBUG', false);
}

$html = null;
ob_start();

// Just include the parent file
require_once __DIR__."/html/{$template_file}";

$html = ob_get_clean();

$write_cache = Mage::helper("deheerhoreca_fpc/data")->is_write_cache_enabled(true);

if($write_cache === true) {
  $key = Mage::helper("deheerhoreca_fpc/data")->get_cache_key();
  Mage::helper("deheerhoreca_fpc/data")->save_cached_html($key, $html, true);
}

echo $html;
