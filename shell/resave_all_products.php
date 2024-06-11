<?php

if(php_sapi_name() !== "cli") {
  header("Location: /");
  exit;
}

const DEBUG = false;
// const N     = 10;

/* FILTERS */
// $supplier_names = ["combisteel"];
$dhh_sku  = "DI-BFX-2R/08-R2";
$fromDate = date('1970-01-01 00:00:00');
$toDate   = date('Y-m-d H:i:s', strtotime("-1 day"));

ini_set('memory_limit', '8G');
ini_set('display_errors', true);
error_reporting(E_ALL | E_STRICT);

require_once __DIR__."/../app/Mage.php";
Mage::setIsDeveloperMode(true);
Mage::app(0);
Mage::init();

// https://stackoverflow.com/questions/36068795/programatically-update-product-price-in-magento-invalid-argument-supplied-for
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

// https://inchoo.net/magento/programming-magento/how-to-delete-magento-product-from-frontend-template-code-from-view-files/
if(Mage::registry('isSecureArea')) {
  Mage::unregister('isSecureArea');
}
Mage::register('isSecureArea', true);

if(DEBUG === false) {
  echo "Setting indexers to manual...".PHP_EOL;
  shell_exec("/opt/plesk/php/7.4/bin/php shell/indexer.php --mode-manual cataloginventory_stock");
  shell_exec("/opt/plesk/php/7.4/bin/php shell/indexer.php --mode-manual catalog_product_attribute");
  shell_exec("/opt/plesk/php/7.4/bin/php shell/indexer.php --mode-manual catalog_product_flat");
  shell_exec("/opt/plesk/php/7.4/bin/php shell/indexer.php --mode-manual catalog_product_price");
}

$collection = Mage::getModel('catalog/product')->getCollection()
  ->addFieldToFilter("type_id", Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
  ->addAttributeToSelect('id');

if(defined("N")) {
  $collection->setPageSize(N)->setCurPage(1);
}

if(empty($supplier_names) === false) {
  
  $supplier_attr = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, "supplier");
  $supplier_options = $supplier_attr->getSource()->getAllOptions(false);
  
  foreach($supplier_names as $supplier) {
    reset($supplier_options);        
    foreach($supplier_options as $option) {
      if(strtolower((string) $option['label']) === $supplier) {
        echo "Supplier: {$option['label']}".PHP_EOL;
        $collection->addAttributeToFilter("supplier", $option['value']);
        break 1;
      }
    }
  }
}

if(empty($supplier_ids) === false) {
  $ors = [];
  foreach($supplier_ids as $supplier_id) {
    $ors[] = ['eq' => $supplier_id];
  }
  $collection->addFieldToFilter('supplier', [$ors]);
}

if(empty($dhh_sku) === true && empty($fromDate) === false && empty($toDate) === false) {
  echo "Date From: {$fromDate}".PHP_EOL;
  echo "Date To: {$toDate}".PHP_EOL;
  $collection->addFieldToFilter('updated_at', [
    'from'  => $fromDate,
    'to'    => $toDate,
    'date'  => true,
  ]);
}

if(empty($dhh_sku) === false) {
  $collection->addFieldToFilter('sku', array('eq' => $dhh_sku));
}

$size = $collection->getSize();
$i = 0;

foreach($collection as $product) {
  if($i % 100 === 0) {
    echo "{$i}/{$size}".PHP_EOL;
  }
  
  if(DEBUG === true) {
    echo $product->getSku().PHP_EOL;
  }
  
  $product = Mage::getModel('catalog/product')->load($product->getId());
  $product->setIsChanged(true);
  $product->setDataChanges(true);
  $product->setData("_hasDataChanges", true);
  
  $product = DeHeerHoreca_Util_Model_Observer::updateProductBeforeSave($product);
  $product->save();
  $product = DeHeerHoreca_Util_Model_Observer::updateProductAfterSave($product);

  $i++;
  unset($product);
}

echo PHP_EOL."Saved {$i} product(s)".PHP_EOL;

if(DEBUG === false) {
  echo "Re-enabling realtime indexing...".PHP_EOL;
  shell_exec("/opt/plesk/php/7.4/bin/php shell/indexer.php --mode-realtime cataloginventory_stock");
  shell_exec("/opt/plesk/php/7.4/bin/php shell/indexer.php --mode-realtime catalog_product_attribute");
  shell_exec("/opt/plesk/php/7.4/bin/php shell/indexer.php --mode-realtime catalog_product_flat");
  shell_exec("/opt/plesk/php/7.4/bin/php shell/indexer.php --mode-realtime catalog_product_price");
  
  echo "Reindexing...".PHP_EOL;
  shell_exec("/opt/plesk/php/7.4/bin/php shell/indexer.php --reindex cataloginventory_stock");
  shell_exec("/opt/plesk/php/7.4/bin/php shell/indexer.php --reindex catalog_product_attribute");
  shell_exec("/opt/plesk/php/7.4/bin/php shell/indexer.php --reindex catalog_product_flat");
  shell_exec("/opt/plesk/php/7.4/bin/php shell/indexer.php --reindex catalog_product_price");
} else {
  echo "Skipping reindex, did not disable it".PHP_EOL;
}
