<?php

/*
 * https://raw.githubusercontent.com/papertank/magento-php-sitemap/master/sitemap.php
 */

error_reporting(E_ALL);
set_time_limit(0);
ini_set('memory_limit', '4g');

if(php_sapi_name() !== "cli") {
  header("Location: /");
  exit;
}

$page_priority        = '1';
$category_priority    = '0.5';
$product_priority     = '0.5';

$longopts = [
  "which::",
];

$options = getopt("", $longopts);

$which = "all";

if(empty($options["which"]) === false) {
  $which = $options["which"];
}

$do = [
  "categories"      => true,
  "products"        => true,
  "pages"           => true,
  "blogs"           => true,
  "brands"          => true,
  "product_images"  => true,
];

$sitemap_file = dirname(__FILE__).'/../sitemap_watdachtjezelf.xml';

if($which === "categories") {
  $do = [
    "categories"      => true,
    "products"        => false,
    "pages"           => false,
    "blogs"           => false,
    "brands"          => true,
    "product_images"  => false,
  ];
  
  $sitemap_file = dirname(__FILE__).'/../sitemap_watdachtjezelf_categories.xml';
}

if($which === "other") {
  $do = [
    "categories"      => false,
    "products"        => false,
    "pages"           => true,
    "blogs"           => true,
    "brands"          => false,
    "product_images"  => false,
  ];
  
  $sitemap_file = dirname(__FILE__).'/../sitemap_watdachtjezelf_other.xml';
}

touch($sitemap_file);

require_once(dirname(__FILE__).'/../app/Mage.php');
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
          Mage_Catalog_Model_Product_Visibility::VISIBILITY_SEARCH
        ]
      );
    
    foreach($collection as $product) {
      //$url = Mage::helper("deheerhoreca_util/util")->getFullProductUrlFromRewrites($product);
      //if($url === false) {
        $url = $product->getProductUrl(); //fallback
      //}
      //echo $product->getId().":".$url.PHP_EOL;
      $images = [];
      
      /* Images */
      
      if($do["product_images"] === true) {
        
		    // $image      = Mage::getResourceModel('catalog/product')->getAttributeRawValue($product->getId(), 'image', $storeId);
		    // $imageLoc   = '';
		    // $imageTitle = '';

		    // if ($image) {
			    // $imageLoc   = str_replace('index.php/', '', Mage::getURL('media/catalog/product') . substr($image, 1));
			    // $imageTitle = $product->getName();
          // $images[] = ["url" => $imageLoc, "title" => $imageTitle, "caption" => $imageTitle];
		    // }

		    // $_product = Mage::getModel('catalog/product')->load($product->getId());
		    // $_images = $_product->getMediaGalleryImages();
        
        $attributes = $product->getTypeInstance(true)->getSetAttributes($product);
        $media_gallery = $attributes['media_gallery'];
        $backend = $media_gallery->getBackend();
        $backend->afterLoad($product); 
        $mediaGallery = $product->getMediaGalleryImages();
        
		    // foreach($_images as $image) {
        foreach($product->getMediaGalleryImages() as $image) {
			    // if($image->getUrl() == $imageLoc) continue;
          // print_r($image);
          // $label = (empty($image->getLabel()) ? $product->getName() : $image->getLabel());
          $label = $product->getName();
          $images[] = ["url" => $image->getUrl(), "title" => $label, "caption" => $label];
        }
        
		    // unset($_product);
      }
      
      $sitemap->addUrl($url, $product_priority, $product->getUpdatedAt(), $images);
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
      if(strstr($page->getIdentifier(),"no-route") !== false) {
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
      //print_r($post->getData());
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
  
  `xmllint --format sitemap_watdachtjezelf.xml > sitemap_watdachtjezelf_formatted.xml`;

} catch( Exception $e ) {
  die($e->getMessage());
}

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
  
  public function addUrl($loc, $priority = '1', $lastmod = NULL, $images = [])
  {
    $data = [
      'loc'       => $loc,
      'priority'  => $priority,
      'lastmod'   => ( $lastmod ? $this->formatDate($lastmod) : NULL ),
      'images'    => $images,
    ];
    $this->urls[] = $data;
    
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
    fwrite($this->file, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'.PHP_EOL);
    
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
    $image = (sizeof($url["images"]) > 0 ? $this->getImageXml($url["images"]) : null);
    fwrite($this->file, "<url><loc>{$url['loc']}</loc><priority>{$url['priority']}</priority>{$lastmod}{$image}</url>".PHP_EOL);
  }
  
  private function getImageXml($images) {
    $string = PHP_EOL;
    foreach($images as $image) {
      $title = (isset($image["title"]) ? "<image:title>".$this->xmlEscape($image["title"])."</image:title>" : null);
      $caption = (isset($image["caption"]) ? "<image:caption>".$this->xmlEscape($image["caption"])."</image:caption>" : null);
      $string .= "<image:image><image:loc>".$this->xmlEscape($image["url"])."</image:loc>{$title}{$caption}</image:image>";
    }
    return $string;
  }
  
  function xmlEscape($string) {
    return str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $string);
  }
}
