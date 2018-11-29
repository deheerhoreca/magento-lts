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

    return $product->getProductUrl();
  }
}
