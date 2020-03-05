<?php
error_reporting(E_ALL | E_STRICT);
define('MAGENTO_ROOT', getcwd()."/..");
$mageFilename = MAGENTO_ROOT . '/app/Mage.php';
require_once $mageFilename;
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
Mage::app();

$products = Mage::getModel("catalog/product")->getCollection();
$products->addAttributeToSelect('sku');
//$products->addAttributeToSelect('category_ids');
$products->addAttributeToFilter('status', 1); //optional for only enabled products
$products->addAttributeToFilter('visibility', 4); //optional for products only visible in catalog and search
$products->addAttributeToSelect('image');

$file = "export_products-".date("Ymdhis").".csv";
$fp        = fopen($file, "w");

$csvHeader = array(
  "sku",
  //"category_ids",
  //"category_name",
  "base_image",
  "extra_image1",
  "extra_image2",
  "extra_image3",
  "extra_image4",
  "extra_image5",
  "extra_image6",
  "extra_image7",
  "extra_image8",
  "extra_image9",
);
fputcsv($fp, $csvHeader, ",");

//$cat_name  = array();
//$cat_array = array();
//$cat       = Mage::getModel('catalog/category');
foreach($products as $product) {
  
  $product = Mage::getModel('catalog/product')->load($product->getId());
  
  $sku         = $product->getSku();
  //$categoryIds = implode('|', $product->getCategoryIds()); //change the category separator if needed
  //$cat_name    = array();
  //$cat_array   = $product->getCategoryIds();
  $base_image  = $product->getImage();
  //print_r($cat_array);
  /*
  for ($k = 0; $k < count($cat_array); $k++) {
    $id = $cat_array[$k];
    $cat->load($id);
    $cat_name[] = $cat->getName();
  }
  $_cate_name = implode('|', $cat_name);
  */
  
  $extra_images = [];
  $extra_images[0] = "";
  $extra_images[1] = "";
  $extra_images[2] = "";
  $extra_images[3] = "";
  $extra_images[4] = "";
  $extra_images[5] = "";
  $extra_images[6] = "";
  $extra_images[7] = "";
  $extra_images[8] = "";
  $extra_images[9] = "";
  $extra_images[10] = "";
  $i = 0;
  foreach ($product->getMediaGalleryImages() as $image) {
    $file_name = $image->getFile();
    //print_r($file_name);
    //echo PHP_EOL;
    if($file_name !== $base_image) {
      $extra_images[$i] = "media/catalog/product".$file_name;
      $i++;
    }
  }
  
  //$extra_images = array_unique($extra_images);

  $data = array(
    $sku,
    //$categoryIds,
    //$_cate_name,
    "media/catalog/product".$base_image,
    $extra_images[0],
    $extra_images[1],
    $extra_images[2],
    $extra_images[3],
    $extra_images[4],
    $extra_images[5],
    $extra_images[6],
    $extra_images[7],
    $extra_images[8],
    $extra_images[9],
    $extra_images[10],
  );
  
  //print_r($data);
  //echo PHP_EOL;
  
  fputcsv($fp, $data, ";");
}
fclose($fp);

echo "Exported to {$file}".PHP_EOL;
