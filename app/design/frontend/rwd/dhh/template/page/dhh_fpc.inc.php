<?php

$html = null;
ob_start();

// Just include the parent file
require_once __DIR__."/html/{$template_file}";

$html = ob_get_clean();

if(Mage::helper("deheerhoreca_fpc/data")->is_write_cache_enabled(true) === true) {
  $key = Mage::helper("deheerhoreca_fpc/data")->get_cache_key();
  Mage::helper("deheerhoreca_fpc/data")->save_cached_html($key, $html, true);
}

echo $html;
