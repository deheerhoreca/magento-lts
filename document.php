<?php

// /document/{dhh_sku}-{brand}-{sku}-{document_type}-{document_seo_keywords_slug}.{document_extension}

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\VarDumper\VarDumper;

// Config
const DOCUMENTS_CACHE_DIR = __DIR__."/media/dhh/documents";
const STORE_ID            = 1;

// Bootstrap
if(!is_dir(DOCUMENTS_CACHE_DIR)) {
  mkdir(DOCUMENTS_CACHE_DIR, 0775, $recursive = true);
}

require_once __DIR__."/app/Mage.php";
Mage::app();
Mage::init();
Mage::app()->setCurrentStore(STORE_ID);
Mage::log("OpenMage initialized", null, "system.log", true);

// Run
dump($_SERVER);
dump($_ENV);
phpinfo();
