<?php

const DEBUG = true;

if(php_sapi_name() !== "cli") {
  header("Location: /");
  exit;
}

ini_set('memory_limit', '8G');
ini_set('display_errors', true);
error_reporting(E_ALL | E_STRICT);

require_once "./app/Mage.php";
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
  echo shell_exec("/opt/plesk/php/7.3/bin/php shell/indexer.php --mode-manual");
}

/* FILTERS */
// $supplier_names = ["veba"];
// $dhh_sku  = "DIA-PCT/10-35WT";
$fromDate = date('1970-01-01 00:00:00');
$toDate   = date('Y-m-d H:i:s', strtotime("-1 hour"));

$collection = Mage::getModel('catalog/product')->getCollection()
  ->addFieldToFilter("type_id", Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
  ->addAttributeToSelect('id')
  // ->setPageSize(10)
  // ->setCurPage(1)
;

if(empty($supplier_names) === false) {
  
  $supplier_attr = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, "supplier");
  $supplier_options = $supplier_attr->getSource()->getAllOptions(false);
  
  foreach($supplier_names as $supplier) {
    reset($supplier_options);        
    foreach($supplier_options as $option) {
      if(strtolower($option['label']) === $supplier) {
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
  echo "From: {$fromDate}".PHP_EOL;
  echo "To: {$toDate}".PHP_EOL;
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
  $product = DeHeerHoreca_Util_Model_Observer::updateProductAfterSave($product);
  // print_r($product->debug());
  $product->save();

  $i++;
  unset($product);
}

echo PHP_EOL."Saved {$i} product(s)".PHP_EOL;

if(DEBUG === false) {
  echo "Reindexing...".PHP_EOL;
  echo shell_exec("/opt/plesk/php/7.3/bin/php shell/indexer.php --mode-realtime");
  echo shell_exec("/opt/plesk/php/7.3/bin/php shell/indexer.php reindexall");
} else {
  echo "Skipping reindex".PHP_EOL;
}
