<?php

/*
 * https://raw.githubusercontent.com/papertank/magento-php-sitemap/master/sitemap.php
 */

$sitemap_file = dirname(__FILE__).'/../sitemap_watdachtjezelf.xml';

$page_priority = '1';
$category_priority = '0.5';
$product_priority = '0.5';

class PT_Magento_Sitemap {

  protected $file;
  protected $filename;

  protected $urls;
  
  public function __construct($filename)
  { 
    $this->urls = array();
    $this->filename = $filename;
  }
  
  public function formatDate($datetime)
  {
    $timestamp = strtotime($datetime);
    return date('Y-m-d', $timestamp);
  }
  
  public function addUrl($loc, $priority = '1', $lastmod = NULL)
  {
    $this->urls[] = array(
      'loc' => $loc,
      'priority' => $priority,
      'lastmod' => ( $lastmod ? $this->formatDate($lastmod) : NULL ),
    );
    
    return true;
  }
  
  public function generate()
  {
    if ( ! $this->file ) {
      $this->openFile();
    }
  
    if ( ! $this->urls ) {
      return false;
    }
  
    foreach ( $this->urls as $url )  {
      $this->writeUrl($url);
    }
    
    $this->closeFile();
    
    return true;
  }
  
  private function openFile()
  {
    $this->file = fopen($this->filename, 'w');
    
    if ( ! $this->file ) {
      throw new Exception('Sitemap file '.$file.' is not writable');
      return false;
    }
    
    fwrite($this->file, '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL);
    fwrite($this->file, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL);
    
    return true;
  }
  
  private function closeFile()
  {
    if ( $this->file ) {
      fwrite($this->file, "</urlset>");
      fclose($this->file);
    }
     
    return true;
  }
  
  private function writeUrl($url)
  {
    $lastmod = ($url['lastmod'] ? "<lastmod>{$url['lastmod']}</lastmod>" : null);
    fwrite($this->file, "<url><loc>{$url['loc']}</loc><priority>{$url['priority']}</priority>{$lastmod}</url>".PHP_EOL);
  }
}

// make sure we don't time out
error_reporting(E_ALL);
set_time_limit(0);

if(php_sapi_name() !== "cli") {
  header("Location: /");
  exit;
}

$do = [
  "categories"    => true,
  "products"      => true,
  "pages"         => true,
  "blogs"         => true,
  "brands"        => true,
];

require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app();
    
try {

  $sitemap = new PT_Magento_Sitemap($sitemap_file);
  
  /* Categories */
  
  if($do["categories"] === true) {
    $collection = Mage::getModel('catalog/category')
      ->getCollection()
      ->addAttributeToSelect('*')
      ->addIsActiveFilter();
      
    foreach($collection as $category) {
      $sitemap->addUrl($category->getUrl(), $category_priority, $category->getUpdatedAt());
    }
    unset($collection);
  }
  
  /* Products */
  
  if($do["products"] === true) {
    $collection = Mage::getModel('catalog/product')
      ->getCollection()
      ->addAttributeToSelect('*')
      ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
      ->addAttributeToFilter('visibility',
        [
          Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
          Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
        ]
      );
    
    foreach($collection as $product) {
      //$url = Mage::helper("deheerhoreca_util/util")->getFullProductUrlFromRewrites($product);
      //if($url === false) {
        $url = $product->getProductUrl(); //fallback
      //}
      //echo $product->getId().":".$url.PHP_EOL;
      $sitemap->addUrl($url, $product_priority, $product->getUpdatedAt());
    }
    unset($collection);
  }
  
  /* Pages */
  
  if($do["pages"] === true) {
    $collection = Mage::getModel('cms/page')
      ->getCollection()
      ->addStoreFilter(Mage::app()->getStore()->getId())
      ->addFieldToFilter('is_active',1);
    
    foreach($collection as $page) {
      if(substr($page->getIdentifier(), 0, 5) === "home-") {
        continue;
      }
      $sitemap->addUrl(Mage::getBaseUrl().$page->getIdentifier(), $page_priority, $page->getUpdateTime());
    }
    unset($collection);
  }
  
  /* Blogs */
  
  if($do["blogs"] === true) {
    
    $collection = Mage::getModel('blog/blog')->getCollection()
        ->addPresentFilter()
        ->addEnableFilter(AW_Blog_Model_Status::STATUS_ENABLED)
    ;
    
    foreach($collection as $post) {
      $url = Mage::getBaseUrl()."blog/".$post->getIdentifier();
      print_r($post->getData());
      $sitemap->addUrl($url, $page_priority, $post->getCreatedTime());
    }
    unset($collection);
    
  }
  
  /* Brands */
  
  if($do["brands"] === true) {
    $name           = 'manufacturer';
    $attributeInfo  = Mage::getResourceModel('eav/entity_attribute_collection')->setCodeFilter($name)->getFirstItem();
    $attributeId    = $attributeInfo->getAttributeId();
    $attribute      = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
    $manufacturers  = $attribute ->getSource()->getAllOptions(false);
    
    usort($manufacturers, function($a, $b) {
      return $a['label'] <=> $b['label'];
    });
    
    foreach($manufacturers as $manufacturer) {
      $brand_name   = $manufacturer['label'];
      $brand_slug   = Mage::helper("deheerhoreca_util/util")->getBrandUrlSlug($brand_name);
      $url          = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)."{$brand_slug}.html";
      $sitemap->addUrl($url, $page_priority);    
    }
    
    unset($manufacturers);
  }
  
  // Generate and write the sitemap.
  if($sitemap->generate()) {
    echo "Wrote sitemap successfully".PHP_EOL;
  } else {
    echo "Error while writing sitemap".PHP_EOL;
  }


} catch( Exception $e ) {
  die($e->getMessage());
}
