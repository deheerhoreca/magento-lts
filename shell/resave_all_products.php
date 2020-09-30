<?php

if (php_sapi_name() !== "cli") {
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

echo shell_exec("/opt/plesk/php/7.3/bin/php shell/indexer.php --mode-manual");

const DEBUG = false;

// Filters
// <option value="1300">Bartscher</option>
// <option value="1294">Combisteel</option>
// <option value="1585">Culimat</option>
// <option value="1794">De Heer Horeca</option>
// <option value="1798">De Jong Luchttechniek</option>
// <option value="1895">Desinfectietoren.nl</option>
// <option value="1297">Diamond</option>
// <option value="1295">Domest</option>
// <option value="1787">Emga</option>
// <option value="1790">Gastro-Inox</option>
// <option value="1293">Gastronoble</option>
// <option value="1618">Hendi</option>
// <option value="1301">Hoshizaki</option>
// <option value="1797">KamadoSheriff</option>
// <option value="1302">Naomi-Grills</option>
// <option value="1299">Saro</option>
// <option value="1298">Scancool</option>
// <option value="1793">SousVide Supreme</option>
// <option value="1296">Virtus Mastro</option>

// $supplier_ids = [1294];
// $sku = "DE314";
$fromDate = date('1970-01-01 00:00:00');
$toDate   = date('Y-m-d H:i:s', strtotime("-1 day"));

$collection = Mage::getModel('catalog/product')->getCollection()
  ->addFieldToFilter("type_id", Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
  ->addAttributeToSelect('id')
  ->setPageSize(1000000)
  ->setCurPage(1);

if(empty($supplier_ids) === false) {
  $ors = [];
  foreach($supplier_ids as $supplier_id) {
    $ors[] = ['eq' => $supplier_id];
  }
  $collection->addFieldToFilter('supplier', [$ors]);
}

if(empty($fromDate) === false && empty($toDate) === false) {
  echo "From: {$fromDate}".PHP_EOL;
  echo "To: {$toDate}".PHP_EOL;
  $collection->addFieldToFilter('updated_at', [
    'from'  => $fromDate,
    'to'    => $toDate,
    'date'  => true,
  ]);
}

if(empty($sku) === false) {
  $collection->addFieldToFilter('sku', array('eq' => $sku));
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
  
  $product->save();
  $i++;
  unset($product);
}

echo PHP_EOL."Saved {$i} product(s)".PHP_EOL;

echo "Reindexing...".PHP_EOL;
echo shell_exec("/opt/plesk/php/7.3/bin/php shell/indexer.php --mode-realtime");
echo shell_exec("/opt/plesk/php/7.3/bin/php shell/indexer.php reindexall");
