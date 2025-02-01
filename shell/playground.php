<?php

const DRYRUN      = false;
const DEBUG       = true;

if (php_sapi_name() !== "cli") {
  header("Location: /");
  exit;
}

// if($_SERVER["REMOTE_ADDR"] !== "85.144.117.179") {
  // header("Location: /");
  // exit;
// }

ini_set("display_errors",true);
error_reporting(E_ALL | E_STRICT);
ini_set("memory_limit", "8G");

require_once __DIR__."/../app/Mage.php";
Mage::setIsDeveloperMode(true);
Mage::app(0);
Mage::init();

// https://stackoverflow.com/questions/36068795/programatically-update-product-price-in-magento-invalid-argument-supplied-for
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

// https://inchoo.net/magento/programming-magento/how-to-delete-magento-product-from-frontend-template-code-from-view-files/
if(Mage::registry("isSecureArea")) {
  Mage::unregister("isSecureArea");
}
Mage::register("isSecureArea", true);


/*********************************************************************************************
* MODIFY ALL PRODUCTS
*********************************************************************************************

$collection = Mage::getModel('catalog/product')
  ->getCollection()
  ->addAttributeToSelect(['sku'])
  ->setStore(0)
  ->addAttributeToFilter('special_price', ['neq' => ''])
  // ->addAttributeToFilter("sku", "BA-601196")
  ->load();

foreach($collection as $_product) {

  $product = Mage::getModel('catalog/product')->load($_product->getId());

  $price          = $product->getPrice();
  $final_price    = $product->getFinalPrice();

  echo "SKU: {$product->getSku()}: price = {$price}, final price = {$final_price}".PHP_EOL;
  if($final_price > 0 && $price == $final_price) {
    $product
      ->setSpecialPrice('')
      ->setSpecialToDate('')
      ->setSpecialFromDate('');

    if(DRYRUN === false) {
      $product->save();
      echo "Saved {$product->getSku()}".PHP_EOL;
    }
  } else {
    echo "No changes necessary".PHP_EOL;
  }

  sleep(0);
}


/*********************************************************************************************
* MANUALLY ADD TRANSACTIONS
*********************************************************************************************/

// $order_id = 100009136;
// $payment_data = [
  // "id"     => uniqid(),
  // "method" => "manual",
// ];

// $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
// print_r($order->debug());

// print_r(addTransactionToOrder($order, $payment_data));

// function addTransactionToOrder($order, $paymentData) {
  // try {
    // // Prepare payment object
    // $payment = $order->getPayment();
    // $payment->setMethod('manual'); 
    // $payment->setLastTransId($paymentData['id']);
    // $payment->setTransactionId($paymentData['id']);
    // $payment->setAdditionalInformation([Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS => (array) $paymentData]);

    // // Formatted price
    // // $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());
    // $formatedPrice = 0;

    // // Prepare transaction
    // $transaction = $this->transactionBuilder->setPayment($payment)
    // ->setOrder($order)
    // ->setTransactionId($paymentData['id'])
    // ->setAdditionalInformation([Transaction::RAW_DETAILS => (array) $paymentData])
    // ->setFailSafe(true)
    // ->build(Transaction::TYPE_CAPTURE);

    // // Add transaction to payment
    // $payment->addTransactionCommentsToOrder($transaction, __('The authorized amount is %1.', $formatedPrice));
    // $payment->setParentTransactionId(null);

    // // Save payment, transaction and order
    // // $payment->save();
    // // $order->save();
    // // $transaction->save();

    // return  $transaction->getTransactionId();

  // } catch (Exception $e) {
    // $this->messageManager->addExceptionMessage($e, $e->getMessage());
  // }
// }



/*********************************************************************************************
* DEBUG OBSERVERS
*********************************************************************************************/

// https://stackoverflow.com/questions/15014154/how-to-fix-magento-1-7-developer-mode/15014983#15014983

// Zend_Debug::dump(
    // Mage::getConfig()->getXpath('//controller_action_predispatch//class')
// );


/*********************************************************************************************
* GET FULL PRODUCT URLS EXPERIMENT
*********************************************************************************************/

/*

$product_id = 19907;

$_product = Mage::getModel('catalog/product')->load($product_id);

echo "<pre>";
$path = getFullProductUrlFromRewrites($_product);
echo "</pre>";

echo $path;

function getFullProductUrlFromRewrites(Mage_Catalog_Model_Product $product) {
  $resource = Mage::getSingleton('core/resource');
  $readConnection = $resource->getConnection('core_read');
  $tableName = $resource->getTableName('core_url_rewrite');
  $product_id = (int) $product->getId();
  $query = "SELECT * FROM `{$tableName}` WHERE product_id = '{$product_id}' AND category_id IS NOT NULL";
  $results = $readConnection->fetchAll($query);
  var_dump($results);

  if(empty($results[0]["request_path"]) === false) {
    return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).$results[0]["request_path"];
  }

  return false;
}

function getFullProductUrl(Mage_Catalog_Model_Product $product = null) { 

  // Force display deepest child category as request path.
  $categories = $product->getCategoryCollection();
  $deepCatId = 0;
  $path = '';
  $productPath = false;

  //var_dump($categories);

  foreach($categories as $category) {
    // Look for the deepest path and save.
    if (substr_count($category->getData('path'), '/') > substr_count($path, '/')) {
      $path = $category->getData('path');
      $deepCatId = $category->getId();
    }
  }

  var_dump($path);
  var_dump($deepCatId);

  // Load category.
  $category = Mage::getModel('catalog/category')->load($deepCatId);

  // Remove .html from category url_path.
  $categoryPath = str_replace('.html', '',  $category->getData('url_path'));

  // Get product url path if set.
  $productUrlPath = $product->getData('url_path');

  // Get product request path if set.
  $productRequestPath = $product->getData('request_path');

  if($_SERVER["REMOTE_ADDR"] === "85.144.117.179") {
    //var_dump($productUrlPath);
  }

  // If URL path is not found, try using the URL key.
  if ($productUrlPath === null && $productRequestPath === null) {
    $productUrlPath = $product->getData('url_key');
  }

  // Now grab only the product path including suffix (if any).
  if ($productUrlPath) {
    $path = explode('/', $productUrlPath);
    $productPath = array_pop($path);
  } elseif ($productRequestPath) {
    $path = explode('/', $productRequestPath);
    $productPath = array_pop($path);
  }

  // Now set product request path to be our full product url including deepest category url path.
  if ($productPath !== false) {
    if ($categoryPath) {
      // Only use the category path is one is found.
      $product->setData('request_path', $categoryPath . '/' . $productPath);
    } else {
      $product->setData('request_path', $productPath);
    }
  }

  var_dump($productPath);

  return $product->getProductUrl();
}

*/
