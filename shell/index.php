<?php

/**
 * Usage:
 * 
 * omphp shell/index.php [index]
 */

// catalog_product_attribute
// catalog_product_price
// catalog_url
// catalog_product_flat
// catalog_category_flat
// catalog_category_product
// catalogsearch_fulltext
// cataloginventory_stock
// dynamiccategory
// tag_summary 

define("MAGENTO_ROOT", __DIR__."/..");

$om_code    = "admin";
$om_app     = "store";

$shortopts  = "i:";
$shortopts .= "d";
$shortopts .= "v";
$shortopts .= "s";

// Bootstrap OM
require MAGENTO_ROOT."/app/Mage.php";

umask(0);
Mage::app($om_code, $om_app)->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

// Sanity
if (isset($_SERVER["REQUEST_METHOD"])) {
  Mage::log("This script cannot be run from Browser. This is the shell script.");
  exit(1);
}

$args = getopt($shortopts);

$args["i"] ?? [];

echo "PID=".getmypid().PHP_EOL;
sleep(10);

foreach((array) $args["i"] as $index) {
  
  $index = preg_replace("/[\W]/", "", $index);
  $indexer ??= Mage::getSingleton("index/indexer");
  
  if($process = $indexer->getProcessByCode($index)) {
    if($process) {
      print("Reindex {$index} started @ ".date("c").PHP_EOL);
      $process->reindexEverything();
      print("Reindex {$index} finished @ ".date("c").PHP_EOL);
    }
  } else {
    print("Failed to reindex {$index}".PHP_EOL);
    exit(1);
  }
}
