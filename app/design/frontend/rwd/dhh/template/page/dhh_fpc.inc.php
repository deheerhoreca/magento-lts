<?php

$html = null;
ob_start();

// Just include the parent file
require_once __DIR__."/html/{$template_file}";

$html = ob_get_clean();

if(Mage::helper("deheerhoreca_fpc/data")->is_write_cache_enabled(true)) {
  $key = Mage::helper("deheerhoreca_fpc/data")->get_cache_key();
  $tags = Mage::helper("deheerhoreca_fpc/data")->get_cache_tags();
  if(!empty($GLOBALS["add_fpc_cache_tags"])) {
    $tags = array_merge($tags, (array) $GLOBALS["add_fpc_cache_tags"]);
    unset($GLOBALS["add_fpc_cache_tags"]); // Make sure it does not happen for the next item
  }
  Mage::helper("deheerhoreca_fpc/data")->save_cached_html($key, $html, tags: $tags);
}

echo $html;
