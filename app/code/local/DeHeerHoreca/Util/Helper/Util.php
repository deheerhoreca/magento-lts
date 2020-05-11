<?php

require_once 'vendor/autoload.php';

use Michelf\Markdown;

class DeHeerHoreca_Util_Helper_Util extends Mage_Core_Helper_Abstract
{
  
  /*
   * getFullProductUrl() runs into issues when the url including
   * category and excluding category are different in core_url_rewrite.
   * This function attempts to get the URL fast and easy from core_url_rewrite.
   * It should be fallbacked with $product->getProductUrl()
   */
  public function getFullProductUrlFromRewrites(Mage_Catalog_Model_Product $product) {
    $resource = Mage::getSingleton('core/resource');
    $readConnection = $resource->getConnection('core_read');
    $tableName = $resource->getTableName('core_url_rewrite');
    $product_id = (int) $product->getId();
    $query = "SELECT * FROM `{$tableName}` WHERE product_id = '{$product_id}' AND category_id IS NOT NULL";
    $results = $readConnection->fetchAll($query);
    
    if(empty($results[0]["request_path"]) === false) {
      return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).$results[0]["request_path"];
    }
    
    return false;
  }
  
  public function getFullProductUrlSafe(Mage_Catalog_Model_Product $product) {
    $url = Mage::helper("deheerhoreca_util/util")->getFullProductUrlFromRewrites($product);
    if($url === false) {
      $url = $product->getProductUrl(); //fallback
    }
    
    return $url;
  }
  
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
  
  public function sanitizeForFilename($string) {
    // Remove anything which isn't a word, whitespace, number
    // or any of the following caracters -_~,;[]().
    // If you don't need to handle multi-byte characters
    // you can use preg_replace rather than mb_ereg_replace
    // Thanks @Łukasz Rysiak!
    $output = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $string);
    // Remove any runs of periods (thanks falstro!)
    $output = mb_ereg_replace("([\.]{2,})", '', $string);
    return strtolower($output);
  }

  public function markdownToHtmlSafe($string) {
    if(strstr($string, "<!--markdown-->") !== false) {
      $string = trim(str_replace("<!--markdown-->", null, $string));
      return Mage::helper("deheerhoreca_util/util")->markdownToHtml($string);
    }
    return $string;
  }

  public function markdownToHtml($string) {
    return Markdown::defaultTransform($string);
  }

  public function getBrandUrlSlug($url) {
    $url = strtolower($url);
    $url = str_replace([" ", "-", "/", "&"], Mage::getStoreConfig('amshopby/seo/special_char'), $url);
    $url = str_replace(["___", "__"], Mage::getStoreConfig('amshopby/seo/special_char'), $url);
    $url = iconv('UTF-8', 'ASCII//TRANSLIT', $url);
    
    return $url;
  }

}

if(function_exists('printr') === false) {
  function printr($expr, $return = false) {
    $ret = null;
    if(is_array($expr) && !sizeof($expr)) {
      return;
    }
    if(php_sapi_name() !== "cli") {
      $ret .= "<pre>";
    }
    $ret .= print_r($expr, true);
    if(php_sapi_name() !== "cli") {
      $ret .= "</pre>";
    } else {
      $ret .= PHP_EOL;
    }
    if($return) {
      return $return;
    }
    echo $ret;
  }
}

if(function_exists("sanitizeForFilename") === FALSE) {
  function sanitizeForFilename($string) {
    // Remove anything which isn't a word, whitespace, number
    // or any of the following caracters -_~,;[]().
    // If you don't need to handle multi-byte characters
    // you can use preg_replace rather than mb_ereg_replace
    // Thanks @Łukasz Rysiak!
    $output = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $string);
    // Remove any runs of periods (thanks falstro!)
    $output = mb_ereg_replace("([\.]{2,})", '', $string);
    return strtolower($output);
  }
}
