<?php

class DeHeerHoreca_Util_Helper_Util extends Mage_Core_Helper_Abstract
{
  public function getFullProductUrl(Mage_Catalog_Model_Product $product = null) {

    // Force display deepest child category as request path.
    $categories = $product->getCategoryCollection();
    $deepCatId = 0;
    $path = '';
    $productPath = false;

    foreach($categories as $category) {
      // Look for the deepest path and save.
      if (substr_count($category->getData('path'), '/') > substr_count($path, '/')) {
        $path = $category->getData('path');
        $deepCatId = $category->getId();
      }
    }

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
    
    if($_SERVER["REMOTE_ADDR"] === "85.144.117.179") {
      //var_dump($productUrlPath);
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

    return $product->getProductUrl();
  }
  
  public function getCategoryFromProduct(Mage_Catalog_Model_Product $product) {
    $categories = $product->getCategoryCollection();
    $deepCatId = 0;
    $path = null;

    foreach($categories as $category) {
      // Look for the deepest path and save.
      if (substr_count($category->getData('path'), '/') > substr_count($path, '/')) {
        $path = $category->getData('path');
        $deepCatId = (int) $category->getId();
      }
    }
    $category = Mage::getModel('catalog/category')->load($deepCatId);
    $category_url = $category->getData('url_path');
    $category_name = $category->getName();
    
    return [
      "id"        => $deepCatId,
      "url"       => $category_url,
      "name"      => $category_name,
    ];
  }
  
  public function getAvgPrice($_productCollection) {
    $price = 0;
    foreach($_productCollection as $_product) {
      $price += $_product->getPrice();
    }
    if(sizeof($_productCollection) === 0) return false;
    return $price / sizeof($_productCollection);
  }

  public function getBrandsPerCategory($category_id) {
    $max_amount = 6;
    
    $products = Mage::getModel('catalog/category')->load($category_id)
      ->getProductCollection()
      ->addAttributeToSelect('manufacturer')  // add all attributes - optional
      ->addAttributeToFilter('status', 1)     // enabled
      ->addAttributeToFilter('visibility', 4); //visibility in catalog,search
    
    $manufacturers = [];
    foreach($products as $product) {
      if(isset($manufacturers[$product->getAttributeText("manufacturer")]) === false) {
        $manufacturers[$product->getAttributeText("manufacturer")] = 0;
      }
      $manufacturers[$product->getAttributeText("manufacturer")]++;
    }  
    arsort($manufacturers);
    $manufacturers = array_slice($manufacturers, 0, $max_amount, true);
    $manufacturers = array_keys($manufacturers);
    
    return $manufacturers;
  }
}
