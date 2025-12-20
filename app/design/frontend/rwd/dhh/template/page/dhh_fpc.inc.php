<?php

declare(strict_types=1);

$html = null;
ob_start();
require_once __DIR__."/html/{$template_file}";  // Just include the parent file to capture its output
$html = ob_get_clean();
echo $html;

// Save to Full Page Cache if not prevented
if(!isset($GLOBALS["disable_fpc_save"]))  {
  if(DeHeerHoreca_Fpc_Helper_Data::is_write_cache_enabled(true, false, "full_page_cache")) {
    $key = DeHeerHoreca_Fpc_Helper_Data::get_cache_key();
    $cache_tags = DeHeerHoreca_Fpc_Helper_Data::get_cache_tags();
    if(!empty($GLOBALS["add_fpc_cache_tags"])) {
      $cache_tags = array_merge($cache_tags, (array) $GLOBALS["add_fpc_cache_tags"]);
      unset($GLOBALS["add_fpc_cache_tags"]); // Make sure it does not happen for the next item
    }
    DeHeerHoreca_Fpc_Helper_Data::save_cached_html($key, $html, true, true, $cache_tags); // Deferred by default
  }
} else {
  DeHeerHoreca_Fpc_Helper_Data::log("Full page SAVE blocked for this specific request.");
  unset($GLOBALS["disable_fpc_save"]); // Make sure it does not happen for the next item
}
