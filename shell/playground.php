<?php

if (php_sapi_name() !== "cli") {
  header("Location: /");
  exit;
}

// if($_SERVER["REMOTE_ADDR"] !== "85.144.117.179") {
  // header("Location: /");
  // exit;
// }

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



/*********************************************************************************************
* CHANGE CATEGORY IMAGES
*********************************************************************************************/

/*
$attribute_id = 45;

$work[] = ["id" => 597, "sku" => "DW486"];
$work[] = ["id" => 465, "sku" => "781395"];
	
// $work[] = ["id" => , "sku" => ""];

$resource = Mage::getSingleton('core/resource');
$writeConnection = $resource->getConnection('core_write');

foreach($work as $item) {

  $image = getMagento1BaseImage($item["sku"]);

  //print_r($image);exit;

  $image_file = basename($image);

  $target = "media/catalog/category/{$image_file}";

  if(copy($image, $target) === true) {
    echo "Copied {$image} to {$target}".PHP_EOL;
  } else {
    echo "Error while copying {$image} to {$target}".PHP_EOL;
  }

  $query = "REPLACE INTO catalog_category_entity_varchar (entity_type_id, attribute_id, store_id, entity_id, `value`) VALUES (3, {$attribute_id}, 0, {$item["id"]}, '{$image_file}');".PHP_EOL;
  
  $writeConnection->query($query);
  
  echo "Query OK".PHP_EOL;
}

function getMagento1BaseImage($sku) {
  $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
  return Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getImage());
}

*/





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
