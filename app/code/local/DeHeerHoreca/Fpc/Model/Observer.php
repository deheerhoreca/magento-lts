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
      return;
    }
    
    $key = Mage::helper("deheerhoreca_fpc/data")->get_cache_key();
    $html = Mage::helper("deheerhoreca_fpc/data")->get_cached_html($key, false, true);

    if(empty($html) === false) {
     if(print($html)) {
        flush();
        // To allow for closing actions (AoE Profiler is one)
        Mage::dispatchEvent('controller_front_send_response_after');
        exit;
      }
    }
  }
}
