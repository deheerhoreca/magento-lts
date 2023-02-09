<?php

require_once 'vendor/autoload.php';

use Michelf\Markdown;
use Michelf\MarkdownExtra;

// These categories are not listed as subcategory tile in listviews
const EXCLUDED_CATEGORY_IDS = [656, 864, 834, 828, 232];

$dhh_click_log = [];

class DeHeerHoreca_Util_Helper_Util extends Mage_Core_Helper_Abstract {
  
  /*
   * getFullProductUrl() runs into issues when the url including
   * category and excluding category are different in core_url_rewrite.
   * This function attempts to get the URL fast and easy from core_url_rewrite.
   * It should be fallbacked with $product->getProductUrl()
   */
  public function getFullProductUrlFromRewrites(Mage_Catalog_Model_Product $product, $single = true) {
    $resource       = Mage::getSingleton('core/resource');
    $readConnection = $resource->getConnection('core_read');
    $tableName      = $resource->getTableName('core_url_rewrite');
    $product_id     = (int) $product->getId();
    $base_url       = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
    $store_id       = (int) Mage::app()->getStore()->getStoreId();
    
    // Return the first URL -- assume we want a category
    if($single === true) {
      $query    = "SELECT request_path FROM `{$tableName}` WHERE product_id = '{$product_id}' AND category_id IS NOT NULL";
      $results  = $readConnection->fetchAll($query);
      if(empty($results[0]["request_path"]) === false) {
        return $base_url.$results[0]["request_path"];
      }
      return false;
    }
    
    // Return multiple URLs if they exist -- including without category
    $query    = "SELECT request_path FROM `{$tableName}` WHERE product_id = '{$product_id}' AND store_id = '{$store_id}'";
    $results  = $readConnection->fetchAll($query);
    if(empty($results) === false) {
      $urls     = [];
      foreach($results as $result) {
        $urls[] = $base_url.$result["request_path"];
      }
      return $urls;
    }
    
    return false;
  }
  
  public function getFullProductUrlSafe(Mage_Catalog_Model_Product $product, $single = true) {
    $url = Mage::helper("deheerhoreca_util/util")->getFullProductUrlFromRewrites($product, $single);
    if($url === false) {
      $url = $product->getProductUrl(); //fallback
    }
    
    return $url;
  }
  
  public function getProductCategory(Mage_Catalog_Model_Product $product = null) {
    $category_ids = $product->getCategoryIds();
    if(empty($category_ids) === false) {
      return Mage::getModel('catalog/category')->load(array_shift($category_ids));
    }
    
    return false;
  }
  
  public function getFullProductUrl(Mage_Catalog_Model_Product $product = null) {

    // Force display deepest child category as request path.
    $categories = $product->getCategoryCollection();
    $deepCatId = 0;
    $path = '';
    $productPath = false;
    
    // if($_SERVER["REMOTE_ADDR"] === "185.127.111.251" && isset($_GET['nofpc'])) {
      // echo "<pre>";
      // print_r($crumbs);
      // echo "</pre>";
    // }

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
  
  // @todo can this go faster?
  public function getBrandsPerCategory($category_id) {
    $max_amount = 6;
    
    $products = Mage::getModel("catalog/category")->load($category_id)
      ->getProductCollection()
      ->addAttributeToSelect("manufacturer")   // add all attributes - optional
      ->addAttributeToFilter("status", 1)      // enabled
      ->addAttributeToFilter("visibility", 4); // visibility in catalog,search
    
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
    if(strstr($string, "<!--markdownextra-->") !== false) {
      $string = trim(str_replace("<!--markdownextra-->", null, $string));
      return Mage::helper("deheerhoreca_util/util")->markdownExtraToHtml($string);
    }
    return $string;
  }
  
  public function markdownToHtml($string) {
    return Markdown::defaultTransform($string);
  }
  
  public function markdownExtraToHtml($string) {
    return MarkdownExtra::defaultTransform($string);
  }

  public function getBrandUrlSlug($url) {
    $url = strtolower($url);
    $url = str_replace([" ", "-", "/", "&"], Mage::getStoreConfig('amshopby/seo/special_char'), $url);
    $url = str_replace(["___", "__"], Mage::getStoreConfig('amshopby/seo/special_char'), $url);
    $url = iconv('UTF-8', 'ASCII//TRANSLIT', $url);
    
    return $url;
  }
  
  public function getProductGridHtml($_product, $product_block, $options = []) {
    
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
    $max_product_usps         = ($display === "mini")               ? 4 : 10;
    
    // @warning name_short is not configured for display in categories anymore
    // if($use_short_product_names === true) {
      // $product_short_name   = $_product->getData("name_short");
      // if(strlen($product_short_name) > 0) $display_product_name = $product_short_name;
    // }
    
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
    $price_html             = str_replace(",00", ",-", $price_html);
    $price_html             = str_replace("€", null, $price_html);
    
    $stock_data             = Mage::helper("deheerhoreca_util/util")->getStockInfo($_product);
    $stock_message          = $stock_data["stock_message"];
    $stock_message_short    = $stock_data["stock_message_short"];
    $stock_class            = $stock_data["txtcltcz"];
    
    // @todo below variables are not used/needed, simplify
    $in_stock               = $stock_data["in_stock"];
    $stock_qty              = $stock_data["stock_qty"];
    $backorders             = $stock_data["backorders"];
    $saleable               = $stock_data["saleable"];
    $eol                    = $stock_data["eol"];
    $eol_replacement_sku    = $stock_data["eol_replacement_sku"];
    $manage_stock           = $stock_data["manage_stock"];
    $extra_delivery_time    = $stock_data["extra_delivery_time"];
    $overall_stock_status   = $stock_data["overall_stock_status"];
    $txtstockdate           = $stock_data["txtstockdate"];
    $calwekdate_min         = $stock_data["calwekdate_min"];
    $calwekdate_max         = $stock_data["calwekdate_max"];
    $levertijd              = $stock_data["levertijd"];
    $levertijd_tmp_override = $stock_data["levertijd_tmp_override"];
    
    // if(_dhh_debug()) {
      // echo "<pre>";
      // var_dump($stock_data);
      // echo "</pre>";
    // }
    
    switch($overall_stock_status) {
      case "in_stock":
        // if($display === "mini") {
          // $stock_message = "Op voorraad";
        // }
        break;
      case "backorder":
        $stock_class = "buyblock-usp";
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
    
    $img_url = $product_block->helper('catalog/image')->init($_product, 'small_image')->resize($image_dimensions);
    
    ?>
    <a href="<?php echo $product_url; ?>" title="<?php echo $image_label; ?>" class="product-image"<?php echo $a_target;?>>
      <img loading=lazy class="center" id="product-collection-image-<?php echo $_product->getId(); ?>" src="<?php echo $img_url; ?>" alt="<?php echo $image_label; ?>" width="<?php echo $image_size; ?>" height="<?php echo $image_size; ?>">
    </a>
    <div class="product-info">
      <div class="info">
        <span class="brand-name small fw-600 gray"><?php echo "{$brand} <span class=light-gray>{$sku_seller}</span>"; ?></span>
        <h2 class='product-name ellipsed ellipsed-2'>
          <a href='<?php echo $product_url; ?>' title='<?php echo $this->stripTags($product_name); ?> kopen'<?php echo $a_target;?>><?php echo $display_product_name; ?></a>
        </h2>
        <?php
        if(isset($tagline) === true) {
          if($display === "mini") {
            echo "<div class='product-list-tagline'>Onze Keuze</div>";
          } else {
            echo "<div class='product-list-tagline'>{$tagline}</div>";
          }
        }
        
        if(empty($product_info) === false && (empty($options["skip_info"]) || $options["skip_info"] === false)) {
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
            <span class="<?php echo $stock_class; ?>"><?php echo $display_stock_message; ?></span>
          </div>
          <?php if(!$_product->canConfigure() && $_product->isSaleable()): ?>
            <button type="button" title="<?php echo $this->quoteEscape($this->__('Add to Cart')) ?>" class="button btn-cart float-right" onclick="setLocation('<?php echo $product_block->getAddToCartUrl($_product) ?>')">
              <i class="fa fa-shopping-cart"></i>
            </button>
          <?php else: ?>
            <a title="<?php echo $this->quoteEscape($this->__("Productdetails")) ?>" class="float-right" href="<?php echo $product_url; ?>"><?php echo $product_block->__("Productdetails") ?></a>
          <?php endif; ?>
        </div>
        <?php
      } else {
        ?>
        <div class="actions">
          <div class="float-left" style="padding-top: 5px;">
            <span class="<?php echo $stock_class; ?>"><?php echo $display_stock_message; ?></span>
          </div>
        </div>
        <?php
      }
      
      if(isset($category_info["url"]) === true) {
        echo "<div class='' style='text-align:right;'><a class='strong' style='margin-right:.7em' href='/{$category_info["url"]}'>Meer: {$category_info["name"]}</a><i style='padding: 5px 0 0 0;' class='float-right fa fa-arrow-right' aria-hidden='true'></i></div>";
      }
      ?>
      
    </div>
    <?php
  }
  
  // Get the minimum list of attributes to display something
  public function getProductAttributes(string $which): array {
    
    // This should include all attributes that have used_in_product_listing set to true
    if($which === "listview") {
      return ["sku", "sku_seller", "manufacturer", "supplier", "name", "price", "special_price",
              
              "breedte", "hoogte", "diepte", "size", "uitvoering",
              "type_koeling", "aantal_blikjes", "aantal_flessen", "capacity_wine_bottles", "voorraadbunker_kg", "icecube_type",
              "ijs_productie", "custom_highlights", "volume_net_liter", "inhoud_liters", "total_power_watt", "vermogen",
              "vermogen_kw", "aantal_m3_uur", "garantie", "number_of_cooling_zones", "cooking_zones", "aftap", "afsluitbaar",
              "motor", "isolatiedikte", "diameter_mm", "length_mm", "eenheid", "tagline", "small_image", "material_group",
              "blade_length_mm","bottom_shape", "etaleer_oppervlak_m2", "grill_output_watt", "grill_tray_type", "indoor_outdoor",
              "temp_range_from_c", "temp_range_to_c", "magnetron_output_watt", "nonstick_coating", "product_label", "self_closing",
              
              /* "name_short", */
      ];
    }
    
    return [];
  }
  
  // @deprecated
  public function getProductInfo($_product, $options = []) {
    return [];
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
          $usps[] = trim(str_ireplace(["(Roestvast staal)"], "", $value));
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
      if(empty($attribute_value) === true) {
        $attribute_code  = "gn";
        $attribute_value = _get_product_attribute($_product, $attribute_code);
      }
      if(empty($attribute_value) === false
        && empty($category_name) === false
        && strstr($category_name, "GN") === false) {
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
      $attribute_value = _get_product_attribute($_product, $attribute_code, false);
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
        $usps[] = "Afsluitbaar";
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
      if($power_usp_done !== true) {
        $attribute_code  = "total_power_watt";
        $attribute_value = (int) _get_product_attribute($_product, $attribute_code);
        if(empty($attribute_value) === false) {
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
      if($power_usp_done !== true) {
        $attribute_code  = "vermogen";
        $attribute_value = (double) _get_product_attribute($_product, $attribute_code);
        $attribute_value = Mage::helper("deheerhoreca_util/util")->trim_decimals($attribute_value);
        if(empty($attribute_value) === false) {
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
      if($power_usp_done !== true && empty($attribute_value) === false) {
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
  
  public function getStockInfo($product, array $options = []) {
    $fastmode               = $options["fastmode"]            ?? false;
    $context                = $options["context"]             ?? null;
    
    $stock_data             = [];
        
    /* Availability, Stock */
    $stockitem              = $product->getStockItem();
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
    $saleable               = $product->isSaleable();
    $extra_delivery_time    = 0;
    $eol                    = ($product->getEol() === true || $product->getEol() === "2075") ? true : false;
    $eol_replacement_sku    = $product->getEolReplacementSku();
    $expected_delivery      = $product->getResource()->getAttribute("levertijd")->getFrontend()->getValue($product);
    $levertijd              = $product->getAttributeText("levertijd");
    $levertijd_tmp_override = $product->getAttributeText("levertijd_tmp_override");
    $bestelartikel          = $product->getAttributeText("bestelartikel");
    
    $calwekdate_min         = $calwekdate_max = null;
    
    $supplier               = $product->getAttributeText('supplier');
    
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
    
    if(strtolower($levertijd_tmp_override) === "n.v.t.") {
      $levertijd_tmp_override = null;
    }

    /* Delivery time text */
    
    $txtstockdate = $interval = null;
    $real_txtstockdate = $product->getData("txtstockdate");
    if(empty($real_txtstockdate) === false
    // && ($overall_stock_status === "not_sellable" || $overall_stock_status === "backorder" || $manage_stock === false)
    ) {
      if(empty($real_txtstockdate) === false) {
        $datetime1  = new DateTime($real_txtstockdate);
        $now        = new DateTime("now");
        $interval   = $now->diff($datetime1)->format("%a");
        
        // Only show future date if within 4 months
        if($datetime1 > $now && $interval <= (365/3)) {
          $txtstockdate = date("d-m-Y", strtotime($real_txtstockdate));
        }
      }
    }

    // Code also exists in list.phtml and featured.phtml

    if($eol === true) {
      
      // EOL
      
      $stock_message        = "Nooit meer leverbaar";
      $stock_message_short  = "Niet leverbaar";
      $stock_tooltip        = "Dit product is verlopen en helaas niet meer leverbaar.";
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
      
    } elseif($bestelartikel === "Ja" || ($stock_qty <= 0 && $manage_stock === true)) {
        
      // Backorder
      
      $stock_message        = "Pre-order nu!";
      $stock_message_short  = "Pre-order";
      $stock_tooltip        = "Momenteel niet op vooraad maar u kunt het pre-orderen. U bent dan de eerste die het product krijgt.";
      $delivery_text        = "Verw. levering: <strong>Op aanvraag</strong>";
      $txtcltcz             = "buyblock-usp fw-normal";
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
        $stock_message        = "Op aanvraag";    // Unmanaged, be careful with overpromising
        $stock_message_short  = "Op aanvraag";
        $levertijd            = null;             // If we have an unmanaged product, we cannot really say when it will be available again
      } elseif(in_array($supplier, ["Bartscher"]) === true) {
        $stock_message        = "Op voorraad";    // Don't specify stock details for these suppliers
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
    
    // if(_dhh_debug()) {
      // printr($stock_data);
    // }
    
    return $stock_data;
  }

  public function addParamToUrl($url, $param) {
    if(strpos($url,'?') !== false) {
      $url .= "&{$param}";
    } else {
      $url .= "?{$param}";
    }
    
    return $url;
  }
  
  public static function addToClickLog($key, $val):void {
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
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP)) {
      $ip = $client;
    } elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
      $ip = $forward;
    } else {
      $ip = $remote;
    }

    return $ip;
  }
  
  public static function trim_decimals($value, $decimals = 2, $decimal_sep = ",", $thousand_sep = ".") {
    if(is_scalar($value) && is_numeric($value)) {
      $value = number_format($value, $decimals, $decimal_sep, $thousand_sep);
      $decimal_string = ",".str_repeat("0", $decimals);
      // $value = str_replace(",00", "", $value);
      $value = rtrim($value, "0");
      $value = rtrim($value, $decimal_sep);
    }
    
    return $value;
  }
  
  public static function cleanCategoryName($category_name) {
    return trim(str_replace(["[V]", "[SKIPMENU]"], null, $category_name));
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
    elseif(in_range($price_ex_vat, 12501, 25000 )) $rates[24] = $price_ex_vat * (4.68 / 100);
    elseif(in_range($price_ex_vat, 25001, 150000)) $rates[24] = $price_ex_vat * (4.63 / 100);
    
        if(in_range($price_ex_vat, 400  , 2500  )) $rates[36] = $price_ex_vat * (3.46 / 100);
    elseif(in_range($price_ex_vat, 2501 , 5000  )) $rates[36] = $price_ex_vat * (3.36 / 100);
    elseif(in_range($price_ex_vat, 5001 , 12500 )) $rates[36] = $price_ex_vat * (3.27 / 100);
    elseif(in_range($price_ex_vat, 12501, 25000 )) $rates[36] = $price_ex_vat * (3.23 / 100);
    elseif(in_range($price_ex_vat, 25001, 150000)) $rates[36] = $price_ex_vat * (3.20 / 100);
    
        if(in_range($price_ex_vat, 400  , 2500  )) $rates[48] = $price_ex_vat * (2.76 / 100);
    elseif(in_range($price_ex_vat, 2501 , 5000  )) $rates[48] = $price_ex_vat * (2.57 / 100);
    elseif(in_range($price_ex_vat, 5001 , 12500 )) $rates[48] = $price_ex_vat * (2.55 / 100);
    elseif(in_range($price_ex_vat, 12501, 25000 )) $rates[48] = $price_ex_vat * (2.49 / 100);
    elseif(in_range($price_ex_vat, 25001, 150000)) $rates[48] = $price_ex_vat * (2.46 / 100);
    
        if(in_range($price_ex_vat, 400  , 2500  )) $rates[60] = $price_ex_vat * (2.43 / 100);
    elseif(in_range($price_ex_vat, 2501 , 5000  )) $rates[60] = $price_ex_vat * (2.25 / 100);
    elseif(in_range($price_ex_vat, 5001 , 12500 )) $rates[60] = $price_ex_vat * (2.15 / 100);
    elseif(in_range($price_ex_vat, 12501, 25000 )) $rates[60] = $price_ex_vat * (2.06 / 100);
    elseif(in_range($price_ex_vat, 25001, 150000)) $rates[60] = $price_ex_vat * (2.02 / 100);
    
    if($time === "daily") {
      $rates = array_map( function($val) { return round(($val * 12) / 365, 2); }, $rates);
      $rates = array_map( function($val) { return max($val, .01); }, $rates); // At least 1 cent per day
    }
    
    return $rates;
  }
  
  public static function auto_productlabel($_product, $context) {
    if(empty($_product->getProductLabel()) === false) {
      return $_product->getProductLabel();
    }
    // if($_product->getAttributeText("supplier") === "Gastronoble") {
      // if($context === "detail") {
        // return "+5% Extra Korting met code <span style='font-family: sans-serif;'><strong>GASTRONOBLE5</strong></span>";
      // } else {
        // return "+5% Extra Korting";
      // }
    // }
  }
  
  // Builds a YouTube video URL from an ID
  public static function build_product_video_url($string) {
    if(strstr("http", $string) === false) {
      // Best way is to just store the youtube ID and build the URL
      $string = "//www.youtube-nocookie.com/embed/{$string}?modestbranding=1&loop=0&rel=0&hl=nl&controls=1&origin=https://www.chefstore.nl";
    }
    
    return $string;
  }
  
  // Make an attempt to fix some common issues while displaying comments in adminhtml
  public static function _correct_admin_comment($comment) {
    
    $comment = str_replace(["<br>\n", "<br \>", "<br\>"], "<br>", $comment);
    $comment = nl2br($comment, false);
    $comment = str_replace(["\n"], "", $comment);
    $comment = str_replace(["<br><br><br><br>", "<br><br><br>"], "<br><br>", $comment);
    
    return $comment;
  }
  
  // Centralize building DHH URLs
  public static function get_url(string $which, $id = "") {
    if(empty($id) === false) {
      $id = urlencode($id);
    }
    switch($which) {
      case "tool_mage_order":
        return "https://tools.deheerhoreca.nl/?tool=Li90b29scy9uZXJkc3R1ZmYvb3V0L291dC5waHA=&which=magento1_order_id&q={$id}";
      case "tool_mage_product":
        return "https://tools.deheerhoreca.nl/?tool=Li90b29scy9uZXJkc3R1ZmYvb3V0L291dC5waHA=&which=magento_product_sku&q={$id}";
      case "tool_cs_product":
        return "https://tools.deheerhoreca.nl/?tool=Li90b29scy9uZXJkc3R1ZmYvb3V0L291dC5waHA=&which=chefstore_product_sku&q={$id}";
      case "tool_supplier_product":
        return "https://tools.deheerhoreca.nl/?tool=Li90b29scy9uZXJkc3R1ZmYvb3V0L291dC5waHA=&which=supplier_product_sku&q={$id}";
      case "tool_intel_product":
        return "https://tools.deheerhoreca.nl/?tool=Li90b29scy9jYXRhbG9nL2ludGVsLXByb2R1Y3QvaW50ZWwtcHJvZHVjdC5waHA%3D&identifier={$id}";
      case "bol_search_product":
        return "https://www.bol.com/nl/s/?searchtext={$id}";
    }
    
    return null;
  }  
}

if(function_exists('_get_product_attribute') === false) {
  function _get_product_attribute($_product, string $attribute_code, bool $implode_arrays = true) {
    if(is_object($_product) === false) {
      return null;
    }
    $attribute = $_product->getResource()->getAttribute($attribute_code);
    if(!$attribute) {
      if(_dhh_debug()) {
        echo "Attribute '{$attribute_code}' does not exist";
      }
      Mage::log("_get_product_attribute: Attribute '{$attribute_code}' does not exist", null, "exception.log", true);
      return null;
    }
    $value = $attribute->getFrontend()->getValue($_product);
    if($implode_arrays === true && is_array($value) === true) {
      $value = implode(", ", $value);
    }
    
    return $value;
  }
}

if(function_exists('printr') === false) {
  function printr($expr, $return = false) {
    $ret = null;
    if(is_array($expr) && !sizeof($expr)) {
      return;
    }
    if(php_sapi_name() !== "cli") {
      $ret .= "<pre style='white-space: pre-wrap; word-wrap:break-word;'>";
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

if(function_exists("sanitizeForFilename") === false) {
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

if(function_exists("_dhh_debug") === false) {
  function _dhh_debug() {
    if(isset($_GET['nofpc'])
    && isset($_SERVER["REMOTE_ADDR"])
    && in_array($_SERVER["REMOTE_ADDR"], _dhh_ips())) {
      return true;
    }
    return false;
  }
}

function _dhh_getselect($collection) {
  return $collection->getSelect()->__toString();
}

function in_range($number, $min, $max, $inclusive = false) {
  if(is_numeric($number) && is_numeric($min) && is_numeric($max)) {
    return $inclusive
      ? ($number >= $min && $number <= $max)
      : ($number >= $min && $number < $max) ;
  }
  return false;
}

// Also declared in intel
if(function_exists("_getAlternativeEans") === false) {
  function _getAlternativeEans($ean) {
    $eans = (array) $ean;
    if(strlen($ean) === 13) {
      $eans[] = sprintf("%014d", $ean);
    }
    if(substr($ean, 0, 1) === "0") {
      $eans[] = substr($ean, 1);
    }
    if(substr($ean, 0, 2) === "00") {
      $eans[] = substr($ean, 2);
    }
    if(substr($ean, 0, 3) === "000") {
      $eans[] = substr($ean, 3);
    }
    
    return $eans;
  }
}
