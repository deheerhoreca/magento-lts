<?php

// declare(strict_types=1);

use \Carbon\CarbonImmutable;
use \Michelf\Markdown;
use \Michelf\MarkdownExtra;
use \Illuminate\Support\Arr;
use \Illuminate\Support\Collection;
use \Illuminate\Support\Number;
use \Illuminate\Support\Str;
use \Illuminate\Support\Stringable;

require_once __DIR__."/strftime_replacement.php";

// These categories are not listed as subcategory tile in listviews
const EXCLUDED_CATEGORY_IDS = [656, 864, 834, 828, 232];

$dhh_click_log = [];

// If we have an unmanaged/fake_managed product, we cannot really say when it will be available again
// Note: In _get_default_stock_profile(), fake_managed suppliers should be in SUPPLIERS_HIDE_STOCK_DETAILS in OpenMage
const SUPPLIERS_HIDE_STOCK_DETAILS = [
  "apexa", "bartscher", "deheerhoreca", "espressions",
  "foster-gamko", "heatmaestro", "hoshizaki", "orionstar",
  "probbqshop", "liebherr", "smeg", "virtus","youcup",
];

// Mage::helper("deheerhoreca_util/util")->__METHOD__()

class DeHeerHoreca_Util_Helper_Util extends Mage_Core_Helper_Abstract {
  
  /*
   * getFullProductUrl() runs into issues when the url including
   * category and excluding category are different in core_url_rewrite.
   * This function attempts to get the URL fast and easy from core_url_rewrite.
   * It should be fallbacked with $product->getProductUrl()
   */
   // ALTER TABLE `prokoeling`.`core_url_rewrite` DROP INDEX `DHH_PRODUCT_ID_GETFULLPRODUCTURLFROMREWRITES`, ADD 
   // INDEX `DHH_PRODUCT_ID_GETFULLPRODUCTURLFROMREWRITES` (`product_id`, `category_id`, `store_id`, `is_system`, `request_path`) USING BTREE;
  public function getFullProductUrlFromRewrites(Mage_Catalog_Model_Product $product, $single = true, int $store_id = -1) {
    $resource       = Mage::getSingleton('core/resource');
    $readConnection = $resource->getConnection('core_read');
    $tableName      = (string)  $resource->getTableName('core_url_rewrite');
    $product_id     = (int)     $product->getId();
    $base_url       = (string)  Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
    if($store_id < 0) {
      $store_id = (int) Mage::app()->getStore()->getStoreId();
    }
    $query          = "SELECT `request_path` FROM `{$tableName}` WHERE product_id='{$product_id}' AND store_id = '{$store_id}' ORDER BY `category_id` IS NULL, '1' ASC, `is_system` DESC";
    
    // Single mode: Return the first URL -- Assume we prefer a URL with a category
    // Custom sorting to prefer system-defined ("primary" category), and in-category URLs, with fallback
    if($single === true) {
      $query    .= " LIMIT 1";
      $results  = $readConnection->fetchAll($query);
      if(empty($results[0]["request_path"]) === false) {
        return $base_url.$results[0]["request_path"];
      }
      return false;
    }
    
    // Return all URLs -- Use sorting to prefer the same URL as above
    // $query    = "SELECT request_path FROM `{$tableName}` WHERE product_id = '{$product_id}' AND store_id = '{$store_id}'";
    $query    .= " LIMIT 10";
    if($results = $readConnection->fetchAll($query)) {
      $urls     = [];
      foreach($results as $result) {
        $urls[] = $base_url.$result["request_path"];
      }
      return $urls;
    }
    
    return false;
  }
  
  public function getFullProductUrlSafe(Mage_Catalog_Model_Product $product, $single = true, int $store_id = -1) {
    $url = Mage::helper("deheerhoreca_util/util")->getFullProductUrlFromRewrites($product, $single, $store_id);
    if($url === false) {
      $url = $product->getProductUrl(); // fallback
    }
    
    return $url;
  }
  
  public function getProductCategory(Mage_Catalog_Model_Product $product = null) {
    $category_ids = $product->getCategoryIds();
    if(empty($category_ids) === false) {
      return dhh_get_cached_category(array_shift($category_ids));
      // return Mage::getModel('catalog/category')->load(array_shift($category_ids));
    }
    
    return false;
  }
  
  public function getFullProductUrl(Mage_Catalog_Model_Product $product = null) {

    // Force display deepest child category as request path.
    $categories = $product->getCategoryCollection();
    $deepCatId = 0;
    $path = '';
    $productPath = false;

    foreach($categories as $category) {
      // Look for the deepest path and save.
      if (substr_count((string) $category->getData('path'), '/') > substr_count((string) $path, '/')) {
        $path = $category->getData('path');
        $deepCatId = $category->getId();
      }
    }

    // Load category.
    // $category = Mage::getModel('catalog/category')->load($deepCatId);
    $category = dhh_get_cached_category($deepCatId);

    // Remove .html from category url_path.
    $categoryPath = str_replace('.html', '',  (string) $category->getData('url_path'));

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
      $path = explode('/', (string) $productUrlPath);
      $productPath = array_pop($path);
    } elseif ($productRequestPath) {
      $path = explode('/', (string) $productRequestPath);
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
      if (substr_count((string) $category->getData('path'), '/') > substr_count((string) $path, '/')) {
        $path = $category->getData('path');
        $deepCatId = (int) $category->getId();
      }
    }
    // $category = Mage::getModel('catalog/category')->load($deepCatId);
    $category = dhh_get_cached_category($deepCatId);
    $category_url = $category->getData('url_path');
    $category_name = $category->getName();
    
    return [
      "id"        => $deepCatId,
      "url"       => $category_url,
      "name"      => $category_name,
    ];
  }
  
  public function getAvgPrice($_products) {
    $price = 0;
    foreach($_products as $_product) {
      $price += $_product->getPrice();
    }
    if(sizeof($_products) === 0) return false;
    return $price / sizeof($_products);
  }
  
  public function getBrandsPerCategory($category_id, $_products = null) {
    Varien_Profiler::start('DHH_'.self::class."::".__METHOD__);
    $max_amount = 5;
    
    if(!$_products) {
      $_products = Mage::getModel("catalog/category")
        ->load($category_id)
        ->getProductCollection()
        ->addAttributeToSelect("manufacturer")
        ->addAttributeToFilter("status", 1)      // enabled
        ->addAttributeToFilter("visibility", 4)  // visibility in catalog,search
        ->setOrder('popularity', 'ASC')
        ->setPageSize(100);
    }
    
    $manufacturers    = [];
    $manufacturer_col = $_products->getColumnValues("manufacturer");
    $manufacturer_col = array_filter($manufacturer_col);
    
    if(empty($manufacturer_col) === false) {
      $manufacturer_ids = array_count_values(array_filter($manufacturer_col));
      arsort($manufacturer_ids);
      $manufacturer_ids = array_slice($manufacturer_ids, 0, $max_amount, true);
      $manufacturer_ids = array_keys($manufacturer_ids);
      
      if(empty($manufacturer_ids) === false) {
        $_product = $_products->getFirstItem() ?? Mage::getModel('catalog/product');
        foreach($manufacturer_ids as $attribute_option_id) {
          $_product->setData("manufacturer", $attribute_option_id);
          $manufacturers[] = $_product->getAttributeText("manufacturer");
        }
      }
    }
    
    Varien_Profiler::stop('DHH_'.self::class."::".__METHOD__);
    return $manufacturers;
  }
  
  public function markdownToHtmlSafe($string) {
    if(str_contains((string) $string, "<!--markdown-->")) {
      $string = trim(str_replace("<!--markdown-->", "", (string) $string));
      return Mage::helper("deheerhoreca_util/util")->markdownToHtml($string);
    }
    if(str_contains((string) $string, "<!--markdownextra-->")) {
      $string = trim(str_replace("<!--markdownextra-->", "", (string) $string));
      return Mage::helper("deheerhoreca_util/util")->markdownExtraToHtml($string);
    }
    return $string;
  }
  
  public function markdownToHtml($string) {
    if(!is_string($string)) $string = ""; # Prevent type issues with defaultTransform()
    return Markdown::defaultTransform($string);
  }
  
  public function markdownExtraToHtml($string) {
    if(!is_string($string)) $string = ""; # Prevent type issues with defaultTransform()
    return MarkdownExtra::defaultTransform($string);
  }

  public function getBrandUrlSlug($string): string {
    $from     = 'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ';
    $to       = 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY';
    $string   = strtr(utf8_decode((string) $string), utf8_decode($from), $to);
    $string   = strtolower($string);
    $string   = str_replace([" ", "-", "/", "&", "'"], Mage::getStoreConfig('amshopby/seo/special_char'), $string);
    $string   = str_replace(["___", "__"], Mage::getStoreConfig('amshopby/seo/special_char'), $string);
    
    return $string;
  }
  
  public function getUrlSlug($string) {
    $from     = 'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ';
    $to       = 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY';
    $string   = strtr(utf8_decode((string) $string), utf8_decode($from), $to);
    $string   = preg_replace('/[^\w\d\-\ ]/', '', $string);
    $string   = str_replace(' ', '-', (string) $string);
    $string   = trim($string);
    
    return $string;
  }
  
  public function getProductGridHtml($_product, $product_block, $options = []) {
    
    Varien_Profiler::start('DHH_'.self::class."::".__METHOD__."_{$_product->getSku()}");
    
    /*
    $options = [
      "image_size"              => 150,           // Image screen size, in pixels
      "display"                 => normal|mini,   // normal|mini
      "skip_usps"               => false,         // Don't show USPs
      "skip_actions"            => false,         // Don't show actions/buttons
      "_blank"                  => false,         // Open in a new window
      "show_category_link"      => false,         // Show a link to the category
      "prefer_rewrite_table"    => false,         // Get product URL preferring the rewrite table
      "use_short_product_names" => false,         // Use brand + MPN instead of product name @deprecated
      "fast_stock"              => false,         // Base stock data on stock_status product field only
    ];
    
    Display usage:
    - mini: related, autorelated, upsell
    - normal: listview
    */
    
    $product_name             = $_product->getData("name");
    $image_label              = $product_name;
    $display_product_name     = $product_name;
    $brand                    = $_product->getAttributeText("manufacturer");
    $sku_seller               = $_product->getData("sku_seller");
    
    $display                  = $options["display"]                 ?? "normal";
    $a_target                 = $options["_blank"]                  ?? null;
    $image_size               = $options["image_size"]              ?? 150;
    $skip_usps                = $options["skip_usps"]               ?? false;
    $skip_actions             = $options["skip_actions"]            ?? false;
    $show_category_link       = $options["show_category_link"]      ?? false;
    $prefer_rewrite_table     = $options["prefer_rewrite_table"]    ?? false;
    $use_short_product_names  = $options["use_short_product_names"] ?? false;
    $fast_stock               = $options["fast_stock"]              ?? false;
    
    $max_product_usps         = ($display === "mini")               ? 4 : 10;
    
    if(empty($a_target) === false) {
      $a_target = " target='{$a_target}'";
    }
    
    // Get data
    if($skip_usps !== true) {
      $product_usps = Mage::helper("deheerhoreca_util/util")->getProductUsps($_product);
    }
    if($show_category_link === true) {
      $category_info = Mage::helper("deheerhoreca_util/util")->getCategoryFromProduct($_product);
    }
    if($prefer_rewrite_table === true) {
      $product_url = Mage::helper("deheerhoreca_util/util")->getFullProductUrlSafe($_product);
    } else {
      $product_url = $_product->getProductUrl();
    }
    
    $image_dimensions       = 1 * $image_size;
    $max_product_info_items = 3;
    $tagline                = $_product->getTagline();
    $price_html             = $product_block->getPriceHtml($_product, true);
    $price_html             = str_replace(",00", ",-", (string) $price_html);
    $price_html             = str_replace("€", "", $price_html);
    $stock_status           = strtolower((string) _get_product_attribute($_product, "stock_status"));
    
    if($fast_stock === true && empty($stock_status === false)) {
      $stock_message          = $stock_status === "direct leverbaar" ? "Op voorraad" : "Pre-order";
      $stock_message_short    = $stock_message;
      $overall_stock_status   = $stock_status === "direct leverbaar" ? "in_stock" : "backorder";
      $stock_class            = $stock_status === "direct leverbaar" ? "buyblock-usp fw-normal" : "clzsoldout";
    } else {
      $stock_data             = Mage::helper("deheerhoreca_util/util")->getStockInfo($_product);
      $stock_message          = $stock_data["stock_message"];
      $stock_message_short    = $stock_data["stock_message_short"];
      $stock_class            = $stock_data["txtcltcz"];

      // @todo below variables are not used/needed, simplify
      // $in_stock               = $stock_data["in_stock"];
      // $stock_qty              = $stock_data["stock_qty"];
      $backorders             = $stock_data["backorders"];
      $saleable               = $stock_data["saleable"];
      $eol                    = $stock_data["eol"];
      $eol_replacement_sku    = $stock_data["eol_replacement_sku"];
      $manage_stock           = $stock_data["manage_stock"];
      $extra_delivery_time    = $stock_data["extra_delivery_time"];
      $overall_stock_status   = $stock_data["overall_stock_status"];
      $txtstockdate           = $stock_data["txtstockdate"];
      // $levertijd              = $stock_data["levertijd"];
      // $levertijd_tmp_override = $stock_data["levertijd_tmp_override"];
    }
    
    switch($overall_stock_status) {
      case "in_stock":
        // if($display === "mini") {
          // $stock_message = "Op voorraad";
        // }
        break;
      case "backorder":
        $stock_class = "buyblock-usp gray";
        // if($display === "mini") {
          // $stock_message = "Reserveren";
        // }
        break;
      case "not_sellable":
        // if($display === "mini") {
          // $stock_message = "Voorraad";
        // }
      case "eol":
        break;
    }
    
    if($display === "mini") {
      $display_stock_message = $stock_message_short;
    } else {
      $display_stock_message = $stock_message;
    }
    
    $img_id           = "product-collection-image-".$_product->getId();    
    $media_url        = rtrim((string) Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA), "/");
    $image_url        = "{$media_url}/catalog/product{$_product->getThumbnail()}";
    $media_dir        = rtrim((string) Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA), "/");
    $image_path       = "{$media_dir}/catalog/product{$_product->getThumbnail()}";
    if(is_file($image_path)) {
      $col_width        = 200;
      $cdn_img_options  = [
        "identifier"      => $_product->getSku(),
        "fs_path"         => $image_path,
        "url"             => $image_url,
        "width"           => $image_size,
        "height"          => $image_size,
        "lazy"            => true,
        "add_mod_time"    => true,
        "class"           => "center",
        "title"           => $image_label,
        "alt"             => $image_label,
        "id"              => $img_id,
        "relative_url"    => true,
      ];
      $img_html         = Mage::helper("deheerhoreca_util/util")->_cdn_img($cdn_img_options);
    } else {
      // Failure should not happen, but this is a fallback
      $img_url          = $product_block->helper('catalog/image')->init($_product, "thumbnail")->resize($image_dimensions);
      $img_html         = "<img loading='lazy' class='center' id='{$img_id}' src='{$img_url}' alt='{$image_label}' width='{$image_size}' height='{$image_size}'>";
    }
    ?>
    <a href="<?=$product_url?>" title="<?=$image_label?>" class="product-image"<?=$a_target;?>>
      <?=$img_html?>
    </a>
    <div class="product-info">
      <div class="info">
        <span class="brand-name small fw-600 gray"><?="{$brand} <span class=light-gray>{$sku_seller}</span>"?></span>
        <h2 class='product-name ellipsed ellipsed-2'>
          <a href="<?=$product_url?>" title='<?=$this->stripTags($product_name)?> kopen'<?=$a_target?>><?=$display_product_name?></a>
        </h2>
        <?php
        if(isset($tagline)) {
          if($display === "mini") {
            echo "<div class='product-list-tagline'>Onze Keuze</div>";
          } else {
            echo "<div class='product-list-tagline'>{$tagline}</div>";
          }
        }
        
        if(!empty($product_info) && (empty($options["skip_info"]) || $options["skip_info"] === false)) {
          echo "<ul>";
          foreach($product_info as $key => $item) {
            if($key === $max_product_info_items) break;
            echo "<li class='angle_before'>{$item}</li>";
          }
          echo "</ul>";
        }
        
        if(empty($product_usps) === false
        && (empty($options["skip_usps"]) || $options["skip_usps"] === false)) {
          $product_usps = array_splice($product_usps, 0, $max_product_usps); // Take max amount
          echo "<ul class='product-list-highlights inline-list'>";
          $count = sizeof($product_usps);
          foreach($product_usps as $key => $usp) {
            echo "<li><span class='gray'>{$usp}</span></li>";
          }
          echo "</ul>";
        }
        ?>
      </div>
      <?php
      echo $price_html;
      if(0 && $_product->getRatingSummary()) {
        echo $product_block->getReviewsSummaryHtml($_product, 'short');
      }
      
      if($skip_actions !== true) {
        ?>
        <div class="actions">
          <div class="float-left" style="padding-top: 5px;">
            <span class="<?=$stock_class?>"><?=$display_stock_message?></span>
          </div>
          <?php if(!$_product->canConfigure() && $_product->isSaleable()): ?>
            <button type="button" title="<?=$this->quoteEscape($this->__('Add to Cart')) ?>" class="button btn-cart float-right" onclick="setLocation('<?=$product_block->getAddToCartUrl($_product)?>')"><i class="fa fa-shopping-cart"></i></button>
          <?php else: ?>
            <a title="<?=$this->quoteEscape($this->__("Productdetails")) ?>" class="float-right" href="<?=$product_url?>"><?=$product_block->__("Productdetails") ?></a>
          <?php endif?>
        </div>
        <?php
      } else {
        ?>
        <div class="actions">
          <div class="float-left" style="padding-top: 5px;">
            <span class="<?=$stock_class?>"><?=$display_stock_message?></span>
          </div>
        </div>
        <?php
      }
      
      if(isset($category_info["url"])) {
        echo "<div class='' style='text-align:right;'><a class='strong' style='margin-right:.7em' href='/{$category_info["url"]}'>Meer: {$category_info["name"]}</a><i style='padding: 5px 0 0 0;' class='float-right fa fa-arrow-right' aria-hidden='true'></i></div>";
      }
      ?>
      
    </div>
    <?php
    Varien_Profiler::stop('DHH_'.self::class."::".__METHOD__."_{$_product->getSku()}");
  }
  
  // Get the minimum list of attributes to display something
  public static function getProductAttributes(string $which, array $add = []): array {
    
    $attributes = [];
    
    // This should include all attributes that have used_in_product_listing set to true
    if($which === "listview") {
      $attributes = [
        "sku", "sku_seller", "manufacturer", "supplier", "name", "price", "special_price", "stock_status",
              
        "breedte", "hoogte", "diepte", "size", "uitvoering",
        "type_koeling", "aantal_blikjes", "aantal_flessen", "capacity_wine_bottles", "voorraadbunker_kg", "icecube_type",
        "ijs_productie", "custom_highlights", "volume_net_liter", "inhoud_liters", "total_power_watt", "vermogen",
        "vermogen_kw", "aantal_m3_uur", "garantie", "number_of_cooling_zones", "cooking_zones", "aftap", "afsluitbaar",
        "motor", "isolatiedikte", "diameter_mm", "length_mm", "eenheid", "tagline", "small_image", "material_group",
        "blade_length_mm","bottom_shape", "etaleer_oppervlak_m2", "grill_output_watt", "grill_tray_type", "indoor_outdoor",
        "temp_range_from_c", "temp_range_to_c", "magnetron_output_watt", "nonstick_coating", "product_label", "self_closing",
        "thumbnail",
        
        /* "name_short", */
      ];
    }
    
    if($add !== []) {
      $attributes = array_merge($attributes, $add);
    }
    
    return array_unique($attributes);
  }
  
  // @deprecated
  public function getProductInfo($_product, $options = []) {
    return [];
  }
  
  // Central place to keep fallback logic of product description
  public static function _get_product_description(object $_product) {
    $value = $_product->getDescription();
    if(strlen((string) $value) < 10) {
      $value = $_product->getSupplierDescription();
    }
    if(strlen((string) $value) < 10) {
      $value = $_product->getName();
    }
    if(strlen((string) $value) > 0) {
      return $value;
    }
    
    return false;
  }
  
  // Sync with list.phtml
  // NOTICE: Add used attributes to getProductAttributes()
  public function getProductUsps($_product, $options = [], $max_count = 100): array {
    
    // Options
    $parent_categories_ids  = $options["parent_categories_ids"] ?? [];    // @todo what is this?
    $context                = $options["context"]               ?? [];    // @todo implement
    $category_name          = $options["category_name"]         ?? null;  // The name of the category
    
    $usps                   = [];
    
    while(1) {
    
      // Size
      $attribute_code  = "size";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if(is_scalar($attribute_value) && empty($attribute_value) === false) {
        $usps[] = "Maat: {$attribute_value}";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Material -- @todo cut off values after "(" to get short values?
      // @todo aisi_standard, fallback to material_group
      $attribute_code  = "material_group";
      $attribute_value = (array) _get_product_attribute($_product, $attribute_code, false);
      if(empty($attribute_value) === false) {
        foreach($attribute_value as $value) {
          $usps[] = trim(str_ireplace(["(Roestvast staal)"], "", (string) $value));
        }
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Uitvoering
      $attribute_code  = "uitvoering";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if(empty($attribute_value) === false) {
        $usps[] = $attribute_value;
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Gender
      $attribute_code  = "gender";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if(empty($attribute_value) === false) {
        $usps[] = $attribute_value;
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Cooling method
      $attribute_code  = "type_koeling";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if(empty($attribute_value) === false) {
        $usps[] = $attribute_value;
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Capacity: Number of cans
      $attribute_code  = "aantal_blikjes";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if($attribute_value > 0) {
        $attribute_value = intval($attribute_value);
        $usps[] = "{$attribute_value}x 33cl";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Capacity: Bottles
      $attribute_code  = "aantal_flessen";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if($attribute_value > 0) {
        $attribute_value = intval($attribute_value);
        $usps[] = "{$attribute_value}x flesssen";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Capacity: Wine Bottles
      $attribute_code  = "capacity_wine_bottles";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if($attribute_value > 0) {
        $attribute_value = intval($attribute_value);
        $usps[] = "{$attribute_value}x flesssen";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Capacity: Ice Storage kg
      $attribute_code  = "voorraadbunker_kg";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if($attribute_value > 0) {
        $attribute_value = intval($attribute_value);
        $usps[] = "{$attribute_value} kg Bunker";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Capacity: M3/hour
      $attribute_code  = "aantal_m3_uur";
      $attribute_value = doubleval(_get_product_attribute($_product, $attribute_code));
      $attribute_value = Mage::helper("deheerhoreca_util/util")->trim_decimals($attribute_value);
      if($attribute_value > 0) {
        $usps[] = round($attribute_value, 2)." m3/u";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Capacity: Cooling Zones
      $attribute_code = "number_of_cooling_zones";
      $attribute_value = (int) _get_product_attribute($_product, $attribute_code);
      if($attribute_value === 2) {
        $usps[] = "Dual-Zone";
      } elseif($attribute_value === 3) {
        $usps[] = "Triple-Zone";
      } elseif($attribute_value > 3) {
        $usps[] = "{$attribute_value} Zones";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Capacity: Cooking Zones
      $attribute_code = "cooking_zones";
      $attribute_value = (int) _get_product_attribute($_product, $attribute_code);
      if($attribute_value > 1) {
        $usps[] = "{$attribute_value} Zones";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Capacity: Generic
      $attribute_code = "capacity";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if(empty($attribute_value) === false) {
        $usps[] = $attribute_value;
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Capacity: Temperature range
      $attribute_value_1 = intval(_get_product_attribute($_product, "temp_range_from_c"));
      $attribute_value_2 = intval(_get_product_attribute($_product, "temp_range_to_c"));
      if(empty($attribute_value_1) === false && empty($attribute_value_2) === false) {
        $usps[] = "{$attribute_value_1}/{$attribute_value_2}°C";
      } elseif(empty($attribute_value_2) === false) {
        $usps[] = "{$attribute_value_2}°C";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Feature: Ice Cube Type
      $attribute_code  = "icecube_type";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if(empty($attribute_value) === false) {
        $usps[] = $attribute_value;
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Capacity: Ice Cube Production
      $attribute_code  = "ijs_productie";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if($attribute_value > 0) {
        $attribute_value = intval($attribute_value);
        $usps[] = "{$attribute_value} kg/24u";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // GN maat if not in a GN category
      $attribute_code  = "gn_capacity";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if(empty($attribute_value)) {
        $attribute_code  = "gn";
        $attribute_value = _get_product_attribute($_product, $attribute_code);
      }
      if(empty($attribute_value) === false
        && empty($category_name) === false
        && !str_contains((string) $category_name, "GN")) {
          if(is_array($attribute_value)) {
            $attribute_value = implode(" ", $attribute_value);
          }
          $usps[] = $attribute_value;
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // GN Options
      $attribute_code  = "gn_options";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if(empty($attribute_value) === false) {
        $usps[] = $attribute_value;
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Custom highlights
      $attribute_code  = "custom_highlights";
      $attribute_value = (string) _get_product_attribute($_product, $attribute_code, false); // Casting required for explode()
      $parts = explode(",", $attribute_value);
      if(sizeof($parts) > 0) {
        foreach($parts as $part) {
          $part = trim($part);
          if(strlen($part) > 0) {
            $usps[] = trim($part);
          }
        }
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Volumes -- First specialized values and then 1 aggregate value if no specialized are found
      $volume_usp_done = false;
      $attribute_code  = "volume_freezer_liter";
      $attribute_value = (int) _get_product_attribute($_product, $attribute_code);
      if(empty($attribute_value) === false) {
        $usps[] = "Viesinhoud {$attribute_value} liter";
        $volume_usp_done = true;
      }
      $attribute_code  = "volume_refrigerator_liter";
      $attribute_value = (int) _get_product_attribute($_product, $attribute_code);
      if(empty($attribute_value) === false) {
        $usps[] = "Koelinhoud {$attribute_value} liter";
        $volume_usp_done = true;
      }
      $attribute_code  = "volume_net_liter";
      $attribute_value = (double) _get_product_attribute($_product, $attribute_code);
      if($volume_usp_done === false && $attribute_value > 0) {
        if($attribute_value > 2000) {
          $usps[] = "Netto ".round($attribute_value / 1000, 2)." m<sup>3</sup>";
        } else {
          $usps[] = "Netto ".round($attribute_value, 2)." liter";
        }
        $volume_usp_done = true;
      }
      $attribute_code  = "inhoud_liters";
      $attribute_value = (double) _get_product_attribute($_product, $attribute_code);
      if($volume_usp_done === false && $attribute_value > 0) {
        $usps[] = round($attribute_value, 2)." liter";
        $volume_usp_done = true;
      }
      unset($volume_usp_done);
      
      if(sizeof($usps) >= $max_count) break;
      
      // Feature: Warranty
      $attribute_code  = "garantie";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if($attribute_value === "24 maanden") {
        $usps[] = "2 Jaar Garantie";
      } elseif($attribute_value === "36 maanden") {
        $usps[] = "3 Jaar Garantie";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Feature: Indoor/Outdoor
      $attribute_code  = "indoor_outdoor";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if(empty($attribute_value) === false) {
        $usps[] = $attribute_value;
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Feature: Aftap
      $attribute_code  = "aftap";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if($attribute_value === "Ja") {
        $usps[] = "Met Aftap";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Feature: Afsluitbaar
      $attribute_code  = "afsluitbaar";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if($attribute_value === "Ja") {
        $usps[] = "Met Slot";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Feature: Met Motor
      $attribute_code  = "motor";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if($attribute_value === "Ja") {
        $usps[] = "Incl. Motor";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Feature: Pan Bottom Shape
      $attribute_code  = "bottom_shape";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if(empty($attribute_value) === false) {
        $usps[] = $attribute_value;
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Feature: Grill tray type
      $attribute_code  = "grill_tray_type";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if(empty($attribute_value) === false) {
        $usps[] = $attribute_value;
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Feature: Non-Stick Coating
      $attribute_code  = "nonstick_coating";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      $display_value   = "";
      switch($attribute_value) {
        case "Ja, met Teflon":      $display_value = "Teflon"; break;
        case "Ja, met Emaille":     $display_value = "Emaille Non-Stick"; break;
        case "Ja, zonder Coating":  $display_value = "Teflon-free Non-Stick"; break;
        case "Ja, Keramisch":       $display_value = "Keramisch Non-Stick"; break;
      }
      if(empty($display_value) === false) {
        $usps[] = $display_value;
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Feature: Self-Closing
      $attribute_code  = "self_closing";
      $attribute_value = _get_product_attribute($_product, $attribute_code);
      if($attribute_value === "Ja") {
        $usps[] = "Zelfsluitend";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Feature: Insulation thickness mm
      $attribute_code  = "isolatiedikte";
      $attribute_value = (int) _get_product_attribute($_product, $attribute_code);
      if($attribute_value > 0) {
        $usps[] = "Isolatie {$attribute_value} mm";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Dimensions: Diameter
      $attribute_value = doubleval(_get_product_attribute($_product, "diameter")) / 10;
      if($attribute_value > 0) {
        $attribute_value = Mage::helper("deheerhoreca_util/util")->trim_decimals($attribute_value);
        $usps[] = "Ø {$attribute_value} cm";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Dimensions: Lengths
      $attribute_code = "blade_length_mm";
      $attribute_value = doubleval(_get_product_attribute($_product, $attribute_code)) / 10;
      if($attribute_value > 0) {
        $attribute_value = Mage::helper("deheerhoreca_util/util")->trim_decimals($attribute_value);
        $usps[] = "Lemmet {$attribute_value} cm";
      } else {
        $attribute_code = "length_mm";
        $attribute_value = doubleval(_get_product_attribute($_product, $attribute_code)) / 10;
        if($attribute_value > 0) {
          $attribute_value = Mage::helper("deheerhoreca_util/util")->trim_decimals($attribute_value);
          $usps[] = "Lengte {$attribute_value} cm";
        }
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Dimensions: Showcase Area m2
      $attribute_code = "etaleer_oppervlak_m2";
      $attribute_value = doubleval(_get_product_attribute($_product, $attribute_code));
      if($attribute_value > 0) {
        $attribute_value = Mage::helper("deheerhoreca_util/util")->trim_decimals($attribute_value);
        $usps[] = "Etaleeropp. {$attribute_value} m2";
      }
      
      if(sizeof($usps) >= $max_count) break;
      
      // Powers -- First specialized values and then 1 aggregate value if no specialized are found
      $power_usp_done = false;
      $attribute_code  = "grill_output_watt";
      $attribute_value = (int) _get_product_attribute($_product, $attribute_code);
      if(empty($attribute_value) === false) {
        $usps[] = "Grill {$attribute_value} W";
        $power_usp_done = true;
      }
      $attribute_code  = "magnetron_output_watt";
      $attribute_value = (int) _get_product_attribute($_product, $attribute_code);
      if(empty($attribute_value) === false) {
        $usps[] = "Magnetron {$attribute_value} W";
        $power_usp_done = true;
      }
      if(!$power_usp_done) {
        $attribute_code  = "total_power_watt";
        $attribute_value = (int) _get_product_attribute($_product, $attribute_code);
        if(empty($attribute_value) === false && is_numeric($attribute_value)) {
          if($attribute_value  >= 5000) {
            $attribute_value   /= 1000;
            $attribute_value    = number_format($attribute_value, 2, ",", ".");
            if($attribute_value > 0) {
              $usps[] = "{$attribute_value} kW";
            }
          } else {
            $attribute_value = (int) number_format($attribute_value, 0, ",", "");
            if($attribute_value > 0) {
              $usps[] = "{$attribute_value} W";
            }
          }
          $power_usp_done = true;
        }
      }
      if(!$power_usp_done) {
        $attribute_code  = "vermogen";
        $attribute_value = (double) _get_product_attribute($_product, $attribute_code);
        $attribute_value = Mage::helper("deheerhoreca_util/util")->trim_decimals($attribute_value);
        if(empty($attribute_value) === false && is_numeric($attribute_value)) {
          if($attribute_value < 3) {
            $attribute_value *= 1000;
            if($attribute_value > 0) {
              $usps[] = "{$attribute_value} Watt";
            }
          } else {
            $attribute_value = number_format($attribute_value, 1, ",", ".");
            $attribute_value = Mage::helper("deheerhoreca_util/util")->trim_decimals($attribute_value);
            if($attribute_value > 0) {
              $usps[] = "{$attribute_value} kW";
            }
          }
          $power_usp_done = true;
        }
      }
      $attribute_code  = "vermogen_kw";
      $attribute_value = (double) _get_product_attribute($_product, $attribute_code);
      $attribute_value = Mage::helper("deheerhoreca_util/util")->trim_decimals($attribute_value);
      if(!$power_usp_done && empty($attribute_value) === false && is_numeric($attribute_value)) {
        if($attribute_value < 3) {
          $attribute_value *= 1000;
          if($attribute_value > 0) {
            $usps[] = "{$attribute_value} Watt";
          }
        } else {
          $attribute_value = number_format($attribute_value, 1, ",", ".");
          $attribute_value = Mage::helper("deheerhoreca_util/util")->trim_decimals($attribute_value);
          if($attribute_value > 0) {
            $usps[] = "{$attribute_value} kW";
          }
        }
        $power_usp_done = true;
      }
      unset($power_usp_done);
      
      if(sizeof($usps) >= $max_count) break;
      
      // Dimensions: Long string and therefore last (no wrapping)
      $width_cm        = intval(_get_product_attribute($_product, "breedte")) / 10;
      $height_cm       = intval(_get_product_attribute($_product, "hoogte"))  / 10;
      $depth_cm        = intval(_get_product_attribute($_product, "diepte"))  / 10;
      if($width_cm  > 10) $width_cm  = round($width_cm);
      if($height_cm > 10) $height_cm = round($height_cm);
      if($depth_cm  > 10) $depth_cm  = round($depth_cm);    
      if($width_cm > 0 && $depth_cm > 0 && $height_cm > 0) {
        $usps[] = "(B){$width_cm} x (D){$depth_cm} x (H){$height_cm}cm";
      } elseif($width_cm > 0 && $depth_cm > 0) {
        $usps[] = "(B){$width_cm} x (D){$depth_cm}";
      }
      
      break;
    }
    
    // Post-processing
    $usps = array_filter($usps);    
    $usps = array_diff($usps, ["N.v.t."]);
    
    // if(_dhh_debug()) printr(var_dump($usps));
    
    return $usps; 
  }
  
  public function getStockInfo($_product, array $options = []) {
    Varien_Profiler::start('DHH_'.self::class."::".__METHOD__."_{$_product->getSku()}");
    $dhh_sku                = $_product->getSku();
    
    // $product_id             = $_product->getEntityId();
    // $hash                   = md5(json_encode($options));
    // $cache_key              = "DHH_STOCKINFO_PRODUCT_{$product_id}_{$hash}";
    //
    // @TODO FPC BROKEN: GETS SAVED WITHOUT LEVERTIJD FIELD (AND MAYBE OTHER FIELDS?) Then goes missing in Detailview
    //
    // if(Mage::helper("deheerhoreca_fpc/data")->is_read_cache_enabled(true, true, "get_stock_info")) {
    //   if($stock_data = Mage::app()->getCache()->load($cache_key)) {
    //     DeHeerHoreca_Fpc_Helper_Data::log("HIT {$cache_key}");
    //     Varien_Profiler::stop('DHH_'.self::class."::".__METHOD__."_{$dhh_sku}");
    //     return json_decode($stock_data, true);
    //   } else {
    //     DeHeerHoreca_Fpc_Helper_Data::log("MISS {$cache_key}");
    //   }
    // }
    
    $fastmode               = $options["fastmode"]            ?? false;
    $context                = $options["context"]             ?? "";
    
    $stock_data             = [];
    
    // Fast mode, only report overall_stock_status
    if($fastmode && !empty(_get_product_attribute($_product, "stock_status"))) {
      $stock_status_sys = strtolower((string) _get_product_attribute($_product, "stock_status"));
      switch($stock_status_sys) {
        case "direct leverbaar":
          $stock_data["overall_stock_status"] = "in_stock";
          return $stock_data;
        case "n.v.t.":
          $stock_data["overall_stock_status"] = "backorder";
          return $stock_data;
        default:
          // Follow normal flow for now
          Mage::log("{$dhh_sku} Unsupported stock_status: ".d($stock_status_sys), Zend_Log::ERR, "system.log", true);
      }
    }
    
    /* Availability, Stock */
    $stockitem              = $_product->getStockItem();
    // printr($stockitem);
    $stock_message          = null;
    $stock_tooltip          = null;
    $txtcltcz               = null;
    if($stockitem) {
      $stock_qty              = (int) $stockitem->getQty();
      $in_stock               = $stockitem->getIsInStock(); // "0", "1", true
      $backorders             = $stockitem->getBackorders();
      $manage_stock           = ($stockitem->getManageStock() === true || $stockitem->getManageStock() === "1") ? true : false;
      $min_sale_qty           = $stockitem->getMinSaleQty();
    } else {
      $stock_qty              = 0;
      $in_stock               = "0";
      $backorders             = null;
      $manage_stock           = false;
      $min_sale_qty           = 0;
    }
    $in_stock               = ($in_stock === true || $in_stock === "1") ? true : false;
    $saleable               = $_product->isSaleable();
    $extra_delivery_time    = 0;
    $eol                    = ($_product->getEol() === true || $_product->getEol() === "2075") ? true : false;
    $eol_replacement_sku    = $_product->getEolReplacementSku();
    $expected_delivery      = $_product->getResource()->getAttribute("levertijd")->getFrontend()->getValue($_product);
    $levertijd              = $_product->getAttributeText("levertijd");
    $levertijd_tmp_override = $_product->getAttributeText("levertijd_tmp_override");
    $bestelartikel          = $_product->getAttributeText("bestelartikel");
    
    // $calwekdate_min         = $calwekdate_max = null;
    
    $supplier               = $_product->getAttributeText('supplier');
    
    /* Temporarily adjust levertijd during holidays */
    
    /*
    if($supplier === "Hendi" || strstr($productTitle, "Hendi")) {
      $future = strtotime("1 Jan 2020");
      $timefromdb = time();
      $timeleft = $future - $timefromdb;
      $daysleft = round((($timeleft/24)/60)/60);
      
      $extra_delivery_time = (int) $daysleft - 2;
    }
    */
    
    if(strtolower((string) $levertijd_tmp_override) === "n.v.t.") {
      $levertijd_tmp_override = null;
    }

    /* Delivery time text */
    
    $txtstockdate = $interval = null;
    $real_txtstockdate = $_product->getData("txtstockdate");
    if(empty($real_txtstockdate) === false
    // && ($overall_stock_status === "not_sellable" || $overall_stock_status === "backorder" || $manage_stock === false)
    ) {
      if(empty($real_txtstockdate) === false) {
        $datetime1  = new DateTime($real_txtstockdate);
        $now        = new DateTime("now");
        $interval   = $now->diff($datetime1)->format("%a");
        
        // Only show future date if within 4 months
        if($datetime1 > $now && $interval <= (365/3)) {
          $txtstockdate = date("d-m-Y", strtotime((string) $real_txtstockdate));
        }
      }
    }

    // Code also exists in list.phtml and featured.phtml

    if($eol) {
      
      // EOL
      
      $stock_message        = "Niet meer leverbaar";
      $stock_message_short  = "Niet leverbaar";
      $stock_tooltip        = "Dit product is helaas niet meer leverbaar. We helpen u graag met het vinden van een geschikt vervangend product.";
      $delivery_text        = "";
      $txtcltcz             = "clzsoldout";
      $fa_icon              = "clock-o";
      $backorder_needed     = null;      
      $overall_stock_status = "eol";
      $txtstockdate         = null;
      
    } elseif($in_stock === false || $saleable === false) {
      
      // Not sellable
      
      $stock_message        = "Niet op voorraad";
      $stock_message_short  = "Geen voorraad";
      $delivery_text        = "";
      $txtcltcz             = "clzsoldout";
      $fa_icon              = "";
      $backorder_needed     = null;
      $tagline              = null;
      $overall_stock_status = "not_sellable";
      
    } elseif($bestelartikel === "Ja" || ($stock_qty <= 0 && $manage_stock)) {
        
      // Backorder
      
      $stock_message        = "Pre-order";
      $stock_message_short  = "Pre-order";
      $stock_tooltip        = "Momenteel niet op vooraad maar u kunt het pre-orderen. U bent dan de eerste die het product krijgt.";
      $delivery_text        = "Verw. levering: <strong>Pre-order</strong>";
      $txtcltcz             = "buyblock-usp fw-normal gray";
      $fa_icon              = "fa-times";
      $backorder_needed     =  true;
      $overall_stock_status = "backorder";
      $stock_data["bestelartikel"] = $bestelartikel;

    } else {
      
      // In stock
      
      // Date calculation is disabled      
      // If enabled some day, levertijd_tmp_override should be added here as well
      
      // @todo add support for "1 werkdag"
      // if($levertijd === '2-3 weken') {
        // $nmwek_min = 10;
        // $nmwek_max = 15;
      // } elseif($levertijd === '3-4 weken') {
        // $nmwek_min = 15;
        // $nmwek_max = 20;
      // } elseif($levertijd === '4-5 weken') {
        // $nmwek_min = 20;
        // $nmwek_max = 25;
      // } elseif(strstr($levertijd, "werkdagen") !== false) {
        // $nmwek_min = (int) trim(strtok($levertijd, 'werkdagen'));
        // $nmwek_max = $nmwek_min + 1;
      // }

      // if(empty($nmwek_min) === false) {
        
        // $nmwek_min         += $extra_delivery_time;
        // $nmwek_max         += $extra_delivery_time;
        
        // $calwekdate_min   = date('d-m-Y', strtotime("+ {$nmwek_min} weekdays"));
        // $calwekdate_max   = date('d-m-Y', strtotime("+ {$nmwek_max} weekdays"));

        // // Skip holidays: https://stackoverflow.com/questions/5532002/next-business-day-of-given-date-in-php
        // $holidays         = ["01-01-2020", "10-04-2020", "12-04-2020", "13-04-2020", "27-04-2020", "21-05-2020",
                             // "01-06-2020", "25-12-2020", "26-12-2020"];
        // $tz_obj           = new DateTimeZone('Europe/Amsterdam');
        // $today            = new DateTime("now", $tz_obj);
        // $current_hour     = $today->format('H');
        // $i                = 0;
        
        // while(in_array($calwekdate_min, $holidays) !== false) {
          // $i++;
        // }
        // $calwekdate_min   = date('d-m-Y', strtotime($calwekdate_min . ' +' . $i . ' weekday'));
        // $calwekdate_max   = date('d-m-Y', strtotime($calwekdate_max . ' +' . ($i + 1) . ' weekday'));
      // }
      
      $txtcltcz             = "buyblock-usp";
      $fa_icon              = "fa-check-circle";
      
      if($manage_stock === false) {
        $stock_message        = "Pre-order";        // Unmanaged, be careful with overpromising
        $stock_message_short  = "Pre-order";
        $txtcltcz             = "buyblock-usp gray";
        $levertijd            = null;
      } elseif(in_array(strtolower((string) $supplier), SUPPLIERS_HIDE_STOCK_DETAILS, true)) {
        $stock_message        = "Op voorraad";      // Don't specify stock details for these suppliers
        $stock_message_short  = "Op voorraad";
      } elseif($stock_qty < 4) {
        if($context !== "listview") {
          $stock_message        = "<strong>{$stock_qty}</strong> op voorraad";
        } else {
          $stock_message        = "Op voorraad";
        }
        $stock_message_short  = "Op voorraad";
        $txtstockdate         = null;
      } else {
        $stock_message        = "Ruim op voorraad";
        $stock_message_short  = "Op voorraad";
        $txtstockdate         = null;
      }
      
      $backorder_needed     = false;
      $overall_stock_status = "in_stock";
      $stock_data["bestelartikel"] = $bestelartikel;
    }
    
    $stock_data["stock_message"]          = $stock_message;
    $stock_data["stock_message_short"]    = $stock_message_short;
    $stock_data["stock_tooltip"]          = $stock_tooltip;
    $stock_data["txtcltcz"]               = $txtcltcz;
    $stock_data["fa_icon"]                = $fa_icon;
    $stock_data["stock_qty"]              = $stock_qty;
    $stock_data["in_stock"]               = $in_stock;
    $stock_data["backorders"]             = $backorders;
    $stock_data["saleable"]               = $saleable;
    $stock_data["eol"]                    = $eol;
    $stock_data["eol_replacement_sku"]    = $eol_replacement_sku;
    $stock_data["manage_stock"]           = $manage_stock;
    $stock_data["extra_delivery_time"]    = $extra_delivery_time;
    $stock_data["overall_stock_status"]   = $overall_stock_status;
    $stock_data["txtstockdate"]           = $txtstockdate;
    $stock_data["stock_date_days_left"]   = $interval;
    $stock_data["real_txtstockdate"]      = $real_txtstockdate;
    // $stock_data["calwekdate_min"]         = $calwekdate_min;
    // $stock_data["calwekdate_max"]         = $calwekdate_max;
    $stock_data["levertijd"]              = $levertijd;
    $stock_data["levertijd_tmp_override"] = $levertijd_tmp_override;
    $stock_data["min_sale_qty"]           = $min_sale_qty;
    
    // if(Mage::helper("deheerhoreca_fpc/data")->is_write_cache_enabled(true, true, "get_stock_info")) {
    //   if(Mage::app()->getCache()->save(json_encode($stock_data), $cache_key, ["DHH_STOCKINFO", "DHH_PRODUCT_{$product_id}"], 3600 * 7)) {
    //     DeHeerHoreca_Fpc_Helper_Data::log("SAVED {$cache_key}");
    //   }
    // }
    
    Varien_Profiler::stop('DHH_'.self::class."::".__METHOD__."_{$dhh_sku}");
    
    return $stock_data;
  }

  public function addParamToUrl($url, $param) {
    if(str_contains((string) $url,'?')) {
      $url .= "&{$param}";
    } else {
      $url .= "?{$param}";
    }
    
    return $url;
  }
  
  /**
   * Add key/value pair to the click log.
   *
   * @param  mixed $key
   * @param  mixed $val
   * 
   * @return void
   */
  public static function addToClickLog(mixed $key, mixed $val): void {
    global $dhh_click_log;
    $dhh_click_log[$key] = $val;
  }
  
  public static function addLabelToClickLog($key, $val):void {
    global $dhh_click_log;
    $dhh_click_log["labels"][$key] = $val;
  }
  
  public static function getUserIP() {
    if(isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
      $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
      $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    $client  = $_SERVER['HTTP_CLIENT_IP']       ?? "";
    $forward = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? "";
    $remote  = $_SERVER['REMOTE_ADDR']          ?? "";

    if(empty($client) === false && filter_var($client, FILTER_VALIDATE_IP)) {
      $ip = $client;
    } elseif(empty($forward) === false && filter_var($forward, FILTER_VALIDATE_IP)) {
      $ip = $forward;
    } else {
      $ip = $remote;
    }

    return $ip;
  }

  /**
   * Trim trailing zeros from a decimal number.
   *
   * @param  int|float|string|null  $value
   * @param  int                    $decimals
   * @param  string                 $decimal_sep
   * @param  string                 $thousand_sep
   * 
   * @return string|null
   */
  public static function trim_decimals(int|float|string|null $value, int $decimals = 2, string $decimal_sep = ",", string $thousand_sep = "."): string|null {
    if(blank($value)) {
      return $value;
    }
    
    if(is_scalar($value) && is_numeric($value)) {
      $value = (float) $value;
      $value = number_format($value, $decimals, $decimal_sep, $thousand_sep);
      $value = rtrim($value, "0");
      $value = rtrim($value, $decimal_sep);
    }
    
    return $value;
  }
  
  /**
   * Clean category name by removing special tags like [V], [SKIPMENU], [0]
   * which are used to control certain features on the frontend.
   *
   * @param  string $category_name
   *
   * @return string
   */
  public static function cleanCategoryName(string $category_name): string {
    return trim(str_ireplace(["[V]", "[SKIPMENU]", "[0] "], "", (string) $category_name));
  }
  
  public static function getLeaseRates($price_ex_vat, $time = "daily") {
    $rates = [];
    
    // Using 400 for 500 to be a bit flexible
    
        if(in_range($price_ex_vat, 400  , 2500  )) $rates[15] = $price_ex_vat * (7.73 / 100);
    elseif(in_range($price_ex_vat, 2501 , 5000  )) $rates[15] = $price_ex_vat * (7.60 / 100);
    elseif(in_range($price_ex_vat, 5001 , 12500 )) $rates[15] = $price_ex_vat * (7.53 / 100);
    elseif(in_range($price_ex_vat, 12501, 25000 )) $rates[15] = $price_ex_vat * (7.45 / 100);
    elseif(in_range($price_ex_vat, 25001, 150000)) $rates[15] = $price_ex_vat * (7.40 / 100);
    
        if(in_range($price_ex_vat, 400  , 2500  )) $rates[24] = $price_ex_vat * (4.99 / 100);
    elseif(in_range($price_ex_vat, 2501 , 5000  )) $rates[24] = $price_ex_vat * (4.89 / 100);
    elseif(in_range($price_ex_vat, 5001 , 12500 )) $rates[24] = $price_ex_vat * (4.78 / 100);
    elseif(in_range($price_ex_vat, 12501, 25000 )) $rates[24] = $price_ex_vat * (4.72 / 100);
    elseif(in_range($price_ex_vat, 25001, 150000)) $rates[24] = $price_ex_vat * (4.68 / 100);
    
        if(in_range($price_ex_vat, 400  , 2500  )) $rates[36] = $price_ex_vat * (3.46 / 100);
    elseif(in_range($price_ex_vat, 2501 , 5000  )) $rates[36] = $price_ex_vat * (3.36 / 100);
    elseif(in_range($price_ex_vat, 5001 , 12500 )) $rates[36] = $price_ex_vat * (3.30 / 100);
    elseif(in_range($price_ex_vat, 12501, 25000 )) $rates[36] = $price_ex_vat * (3.27 / 100);
    elseif(in_range($price_ex_vat, 25001, 150000)) $rates[36] = $price_ex_vat * (3.23 / 100);
    
        if(in_range($price_ex_vat, 400  , 2500  )) $rates[48] = $price_ex_vat * (2.76 / 100);
    elseif(in_range($price_ex_vat, 2501 , 5000  )) $rates[48] = $price_ex_vat * (2.60 / 100);
    elseif(in_range($price_ex_vat, 5001 , 12500 )) $rates[48] = $price_ex_vat * (2.57 / 100);
    elseif(in_range($price_ex_vat, 12501, 25000 )) $rates[48] = $price_ex_vat * (2.53 / 100);
    elseif(in_range($price_ex_vat, 25001, 150000)) $rates[48] = $price_ex_vat * (2.50 / 100);
    
        if(in_range($price_ex_vat, 400  , 2500  )) $rates[60] = $price_ex_vat * (2.40 / 100);
    elseif(in_range($price_ex_vat, 2501 , 5000  )) $rates[60] = $price_ex_vat * (2.25 / 100);
    elseif(in_range($price_ex_vat, 5001 , 12500 )) $rates[60] = $price_ex_vat * (2.19 / 100);
    elseif(in_range($price_ex_vat, 12501, 25000 )) $rates[60] = $price_ex_vat * (2.13 / 100);
    elseif(in_range($price_ex_vat, 25001, 150000)) $rates[60] = $price_ex_vat * (2.08 / 100);
    
    if($time === "daily") {
      $rates = array_map( fn($val) => round(($val * 12) / 365, 2), $rates);
      $rates = array_map( fn($val) => max($val, .01), $rates); // At least 1 cent per day
    }
    
    return $rates;
  }
  
  /**
   * Determine product label automatically based on conditions.
   * 
   * Also used in app/design/frontend/rwd/dhh/template/easytabs/catalogproductview.phtml.
   *
   * @param  object $_product
   * @param  mixed  $context
   *
   * @return string
   */
  public static function auto_productlabel(object $_product, mixed $context): string {
    if(!empty($_product->getProductLabel())) {
      return (string) $_product->getProductLabel();
    }
    
    if(doubleval($_product->getPrice()) > doubleval($_product->getFinalPrice())) {
      return "SALE";
    }
    
    $supplier_sys = Mage::helper("deheerhoreca_util/util")->get_sys_supplier((string) _get_product_attribute($_product, "supplier"));
    $now = CarbonImmutable::now();
    
    // Hendi promo
    // if($supplier_sys === "hendi" && $now->isBefore("2025-09-01 00:00:00")) {
    //   return "5% Kortingscode";
    // }
    
    // Bartscher promo
    if($supplier_sys === "bartscher" && $now->isBefore("2025-11-01 00:00:00")) {
      return "5% Kortingscode";
    }
    
    // Combisteel promo
    if($supplier_sys === "combisteel" && $now->isBefore("2025-11-01 00:00:00")) {
      return "5% Kortingscode";
    }
    
    // // Diamond promo
    // if($supplier_sys === "diamond" && $now->isBefore("2025-09-25 23:59:59") && in_array($_product->getSku(), [
    //   "DI-WR-LB40-MPD4", "DI-WR-LV50-MPD5", "DI-WR-LVCD-D6", "DI-WR-GN06-1N", "DI-WR-GN12-2N", "DI-WR-GN06-1B", "DI-WR-GN12-2B", "DI-WR-38CS-SW",
    //   "DI-WR-38CA-AW", "DI-WR-38CA-AB", "DI-WR-SP24-A/RX2", "DI-WR-SP41-A/RX2", "DI-WR-LP30-M", "DI-WR-VH12-P2", "DI-WR-VH16-G6", "DI-WR-D12-S2", "DI-WR-D16-B6",
    //   "DI-WR-GRPN-43", "DI-WR-MGVE-45", "DI-WR-RTS/1E", "DI-WR-RTD/2E", "DI-WR-RTS/1G", "DI-WR-RTD/2G", "DI-WR-FCV4-MC", "DI-WR-FCV4-DG", "DI-WR-EF08-S8",
    //   "DI-WR-EF16-D8", "DI-WR-EF10-S1", "DI-WR-EF20-S2"
    // ], true)) {
    //   return "5% Kortingscode";
    // }
    
    // Maxima warranty extension
    if($supplier_sys === "maxima" && $now->isBefore("2025-10-15 00:00:00")) {
      return "Nu: 24m garantie";
    }
    
    return "";
  }
  
  // Builds a YouTube video URL from an ID
  public static function build_product_video_url($string) {
    if(!str_contains("http", (string) $string)) {
      // Best way is to just store the youtube ID and build the URL
      // Attempt to reduce "Multiple video URLs discovered as belonging to this video" by adding https:
      $string = "https://www.youtube-nocookie.com/embed/{$string}?modestbranding=1&loop=0&rel=0&hl=nl&controls=1&origin=https://www.chefstore.nl";
    }
    
    return $string;
  }
  
  // Make an attempt to fix some common issues while displaying comments in adminhtml
  public static function _correct_admin_comment($comment) {
    
    $comment = str_replace(["<br>\n", "<br \>", "<br\>"], "<br>", (string) $comment);
    $comment = nl2br($comment, false);
    $comment = str_replace(["\n"], "", $comment);
    $comment = str_replace(["<br><br><br><br>", "<br><br><br>"], "<br><br>", $comment);
    
    return $comment;
  }
  
  // Centralize building DHH URLs
  // Exists in OpenMage and Intel:
  // - app/code/local/DeHeerHoreca/Util/Helper/Util.php
  // - lib/intel.inc.php
  public static function get_url(string $which, $payload = null) {
    if(strlen((string) $payload)) {
      $payload = urlencode((string) $payload);
    }
    return match ($which) {
      "tools_magento1_order_id" => "https://tools.deheerhoreca.nl/?tool=out&which=magento1_order_id&q={$payload}",
      "tools_magento_product_sku" => "https://tools.deheerhoreca.nl/?tool=out&which=magento_product_sku&q={$payload}",
      "tools_chefstore_product_sku" => "https://tools.deheerhoreca.nl/?tool=out&which=chefstore_product_sku&q={$payload}",
      "tools_supplier_product_sku" => "https://tools.deheerhoreca.nl/?tool=out&which=supplier_product_sku&q={$payload}",
      "magento_order" => "https://www.chefstore.nl/index.php/admin4JN0/sales_order/view/order_id/{$payload}/",
      "magento_product" => "https://www.chefstore.nl/index.php/admin4JN0/catalog_product/edit/id/{$payload}/",
      "tools-intel-product", "tools_intel_product" => "https://tools.deheerhoreca.nl/?tool=products&identifier={$payload}",
      "tools_ii_sku" => "https://tools.deheerhoreca.nl/?tool=image-inspector&sku={$payload}",
      "bol_search_product" => "https://www.bol.com/nl/s/?searchtext={$payload}",
      default => false,
    };
  }
  
  public static function _get_placeholder_image_path() {
    $media_dir  = rtrim((string) Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA), "/");
    $image_path = "{$media_dir}/catalog/product/placeholder/".Mage::getStoreConfig("catalog/placeholder/image_placeholder");
    return $image_path;
  }
  
  public static function _get_placeholder_image_url() {
    $media_url  = rtrim((string) Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA), "/");
    $image_url  = "{$media_url}/catalog/product/placeholder/".Mage::getStoreConfig("catalog/placeholder/image_placeholder");
    return $image_url;
  }
  
  public static function _cdn_img($options) {
    return _cdn_img($options);
  }
  
  public static function get_apm_transaction_name(): string {
    return (string) trim(implode(" ", [
      ($_SERVER["REQUEST_METHOD"] ?? ""),
      (Mage::app()->getFrontController()->getAction()->getFullActionName() ?? "UNKNOWN_ACTION"),
    ]));
  }
  
  public static function get_sys_supplier(string $supplier): string {
    return (string) preg_replace("/\s+/", "", strtolower($supplier));
  }
}
