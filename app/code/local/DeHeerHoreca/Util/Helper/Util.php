<?php

require_once 'vendor/autoload.php';

use Michelf\Markdown;
use Michelf\MarkdownExtra;

$dhh_click_log = [];

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
      "image_size"              => 150,
      "display"                 => normal | mini,
      "skip_info"               => false,
      "skip_usps"               => false,
      "skip_actions"            => false,
      "_blank"                  => false,
      "use_short_product_names" => false,
      "show_category_link"      => false,
      "prefer_rewrite_table"    => false,
    ];
    
    Display usage:
    - mini: related, autorelated, upsell
    - normal: listview
    */
    
    $product_name = $image_label = $_product->getData("name");
    $product_short_name = $_product->getData("name_short");
    // $image_label = $this->stripTags($_product->getData('small_image_label'), null, true);
    // if(empty($image_label)) {
      // $image_label = $this->stripTags($product_name, null, true);
    // }
    
    $brand          = $_product->getAttributeText("manufacturer");
    $sku_seller     = $_product->getData("sku_seller");
    
    /* Interpret options */
    $image_size = $options["image_size"] ?? 150;
    $a_target = null;
    if(isset($options["_blank"]) === true && $options["_blank"] === true) {
      $a_target = " target='_blank'";
    }
    $display_product_name = $product_name;
    // if(isset($options["use_short_product_names"]) === true && $options["use_short_product_names"] === true) {
      // $display_product_name = $product_short_name;
    // }
    $skip_actions = $options["skip_actions"] ?? false;
    
    if(empty($options["display"]) === false) {
      $display = $options["display"];
    } else {
      $display = "normal";
    }
    
    $max_product_usps = 10;
    if($display === "mini") {
      $max_product_usps = 4;
    }
    
    /* Get data */
    if(empty($options["skip_info"]) || $options["skip_info"] === false) {
      $product_info = Mage::helper("deheerhoreca_util/util")->getProductInfo($_product);
    }
    if(empty($options["skip_usps"]) || $options["skip_usps"] === false) {
      $product_usps = Mage::helper("deheerhoreca_util/util")->getProductUsps($_product);
    }
    if(isset($options["show_category_link"]) === true && $options["show_category_link"] === true) {
      $category_info = Mage::helper("deheerhoreca_util/util")->getCategoryFromProduct($_product);
    }
    if(isset($options["prefer_rewrite_table"]) === true && $options["prefer_rewrite_table"] === true) {
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
    $stock_qty              = $stock_data["stock_qty"];
    $in_stock               = $stock_data["in_stock"];
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
    
    $additional_delivery_messages = null;
    
    // if(_dhh_debug()) {
      // echo "<pre>";
      // printr($stock_data);
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
    <a href="<?php echo $product_url; ?>" title="<?php echo $image_label; ?>" class="product-image"<?php echo $a_target; ?>>
      <img class="lazy center" id="product-collection-image-<?php echo $_product->getId(); ?>" data-src="<?php echo $img_url; ?>" alt="<?php echo $image_label; ?>" width="<?php echo $image_size; ?>" height="<?php echo $image_size; ?>" />
    </a>
    <div class="product-info">
      <div class="info">
        <span class="brand-name small fw-600 gray"><?php echo "{$brand} <span class=light-gray>{$sku_seller}</span>"; ?></span>
        <h2 class='product-name ellipsed ellipsed-2'>
          <a href='<?php echo $product_url; ?>' title='<?php echo $this->stripTags($product_name); ?> kopen'<?php echo $a_target; ?>><?php echo $display_product_name; ?></a>
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
  
  public function getProductInfo($_product, $options = []) {
    
    $category_id = $options["category_id"] ?? null;
    
    $product_info = [];
            
    // $width = $attribute_value = $_product->getBreedte();
    // $height = $attribute_value = $_product->getHoogte();
    // $depth = $attribute_value = $_product->getDiepte();
    
    // $width = $width + 0;
    // $height = $height + 0;
    // $depth = $depth + 0;
    
    // $width /= 10;
    // $height /= 10;
    // $depth /= 10;
    
    // if($width > 10) $width = round($width);
    // if($depth > 10) $depth = round($depth);
    // if($height > 10) $height = round($height);
    
    // if($width > 0 && $height > 0 && $depth > 0) {
      // $product_info[] = "B: {$width}cm D: {$depth}cm H: {$height}cm";
    // } else {
      // $attribute_value = $_product->getBreedte();
      // if($attribute_value != '' && $attribute_value > 0) {
        // $product_info[] = "Breedte: ".($attribute_value/10)."cm";
      // }
    // }

    // if($category_id === 72) {
      // $attribute_value = $_product->getInhoudAantalGn();
      // if($attribute_value !='') {
        // $product_info[] = "{$attribute_value} x {$gnvalue}";
      // }
    // }
    
    // $attribute_value = (int) $_product->getData('eenheid');
    // if($attribute_value > 1) {
      // $product_info[] = "{$attribute_value} stuks";
    // }
    
    // $attribute_value = $_product->getAttributeText('size');
    // if(is_scalar($attribute_value) && strlen($attribute_value) > 0) {
      // $product_info[] = "Maat: {$attribute_value}";
    // }
    
    // if($display === "normal") {
      // $attribute_value = $_product->getData('materiaal');
      // if(strlen($attribute_value) > 0) {
        // $product_info[] = "{$attribute_value}";
      // }
    // }
    
    // $code = "uitvoering";
    // $attribute_value = $_product->getResource()->getAttribute($code)->getFrontend()->getValue($_product);
    // if(is_array($attribute_value) === true) {
      // $attribute_value = implode(", ", $attribute_value);
    // }
    // if(strlen($attribute_value) > 0) {
      // $product_info[] = "Uitvoering: {$attribute_value}";
    // }
    
    return $product_info;
  }
  
  // Sync with list.phtml
  public function getProductUsps($_product, $options = []) {
    
    $parent_categories_ids = $options["parent_categories_ids"] ?? [];
    
    $usps = [];
    
    // Size
    $attribute_value = $_product->getAttributeText('size');
    if(is_scalar($attribute_value) && strlen($attribute_value) > 0) {
      $usps[] = "Maat: {$attribute_value}";
    }
    
    // Material
    $attribute_value = $_product->getResource()->getAttribute('material_group')->getFrontend()->getValue($_product);
    if(strlen($attribute_value) > 0) {
      foreach(explode(",", $attribute_value) as $value) {
        $value = str_ireplace(["(Roestvast staal)"], null, $value);
        $usps[] = trim($value);
      }
    }
    
    // Uitvoering
    $attribute_value = $_product->getAttributeText('uitvoering');
    if(is_array($attribute_value) === true) {
      $attribute_value = implode(", ", $attribute_value);
    }
    if(is_scalar($attribute_value) === true && strlen($attribute_value) > 0) {
      $usps[] = $attribute_value;
    }
    
    $attribute_value = $_product->getAttributeText('type_koeling');
    if(empty($attribute_value) === false && $attribute_value !== "N.v.t.") {
      $usps[] = $attribute_value;
    }
    
    // Blikjes
    $attribute_value = (int) $_product->getData("aantal_blikjes");
    if($attribute_value > 0) {
      $usps[] = "{$attribute_value}x 33cl";
    }
    
    // Flessen
    $attribute_value = (int) $_product->getData("aantal_flessen");
    if($attribute_value > 0) {
      $usps[] = "{$attribute_value} flessen";
    }
    
    // Capacity: Wine Bottles
    $attribute_value = (int) $_product->getData("capacity_wine_bottles");
    if($attribute_value > 0) {
      $usps[] = "{$attribute_value} flessen";
    }
    
    // voorraadbunker_kg
    $attribute_value = (int) $_product->getData("voorraadbunker_kg");
    if($attribute_value > 0) {
      $usps[] = "{$attribute_value} kg Bunker";
    }
    
    // icecube_type
    $attribute_value = $_product->getAttributeText('icecube_type');
    if(empty($attribute_value) === false && $attribute_value !== "N.v.t.") {
      $usps[] = $attribute_value;
    }
    
    // ijs_productie
    $attribute_value = (int) $_product->getData("ijs_productie");
    if($attribute_value > 0) {
      $usps[] = "{$attribute_value} kg/24u";
    }
    
    /* GN maat if not in a GN category */
    if(empty($gnvalue) === false
      && empty($category_name) === false
      && strstr($category_name, "GN") === false) {
        if(is_array($gnvalue)) {
          $gnvalue = implode(" ", $gnvalue);
        }
        $usps[] = "{$gnvalue}";
    }
    
    /* Custom highlights */
    $attribute_value = $_product->getData("custom_highlights");
    $parts = explode(",", $attribute_value);
    if(sizeof($parts) > 0) {
      foreach($parts as $part) {
        $part = trim($part);
        if(strlen($part) > 0) {
          $usps[] = trim($part);
        }
      }
    }            
    
    /* Volumes */
    $volume_usp_done = false;
    $attribute_value = $_product->getData("volume_net_liter");
    if($volume_usp_done === false && strlen($attribute_value) > 0) {
      if($attribute_value > 2000) {
        $usps[] = "Netto ".round($attribute_value / 1000, 2)." m<sup>3</sup>";
      } else {
        $usps[] = "Netto ".round($attribute_value, 2)." liter";
      }
      $volume_usp_done = true;
    }
    $attribute_value = $_product->getData("inhoud_liters");
    if($volume_usp_done === false && strlen($attribute_value) > 0) {
      $usps[] = round($attribute_value, 2)." liter";
      $volume_usp_done = true;
    }
    unset($volume_usp_done);
    
    /* Powers */
    $power_usp_done = false;
    $attribute_value = $_product->getData("total_power_watt");
    
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
    if($power_usp_done !== true) {
      $attribute_value = $_product->getData("vermogen");
      if(empty($attribute_value) === false) {
        if($attribute_value < 3) {
          $attribute_value *= 1000;
          if($attribute_value > 0) {
            $usps[] = "{$attribute_value} Watt";
          }
        } else {
          //$attribute_value = str_replace(".", null, $attribute_value);
          //$attribute_value = str_replace(",", ".", $attribute_value);
          $attribute_value = number_format($attribute_value, 1, ",", ".");
          $attribute_value = str_replace(",0", null, $attribute_value);
          if($attribute_value > 0) {
            $usps[] = "{$attribute_value} kW";
          }
        }
      }
    }
    $attribute_value = $_product->getData("vermogen_kw");
    if($power_usp_done !== true && empty($attribute_value) === false) {
      if($attribute_value < 3) {
        $attribute_value *= 1000;
        if($attribute_value > 0) {
          $usps[] = "{$attribute_value} Watt";
        }
      } else {
        $attribute_value = number_format($attribute_value, 1, ",", ".");
        $attribute_value = str_replace(",0", null, $attribute_value);
        if($attribute_value > 0) {
          $usps[] = "{$attribute_value} kW";
        }
      }
    }
    unset($power_usp_done);
    
    /* M3/hour capacity */
    $attribute_value = $_product->getAantalM3Uur();
    if(strlen($attribute_value) > 0) {
      $usps[] = round($attribute_value, 2)." m3/UUR";
    }
    
    /* Warranty */
    $attribute_value = $_product->getAttributeText('garantie');
    if($attribute_value === "24 maanden") {
      $usps[] = "24M Garantie";
    }
    
    /* Cooling Zones */
    $attribute_code = "number_of_cooling_zones";
    $attribute_value = (int) $_product->getResource()->getAttribute($attribute_code)->getFrontend()->getValue($_product);
    if($attribute_value === 2) {
      $usps[] = "Dual-Zone";
    } elseif($attribute_value === 3) {
      $usps[] = "Triple-Zone";
    } elseif($attribute_value > 3) {
      $usps[] = "{$attribute_value} Zones";
    }
    
    /* Cooking Zones */
    $attribute_value = $_product->getCookingZones();
    if(strlen($attribute_value) > 0 && (double) $attribute_value > 1) {
      $usps[] = round($attribute_value, 2)." Zones";
    }
    
    /* Aftap */
    $attribute_value = $_product->getAttributeText('aftap');
    if($attribute_value === "Ja") {
      $usps[] = "Met Aftap";
    }
    
    /* Afsluitbaar */
    $attribute_value = $_product->getAttributeText("afsluitbaar");
    if($attribute_value === "Ja") {
      $usps[] = "Afsluitbaar";
    }
    
    /* Motor */
    $attribute_value = $_product->getAttributeText("motor");
    if($attribute_value === "Ja") {
      $usps[] = "Incl. motor";
    }
    
    /* Insulation thickness */
    $attribute_value = $_product->getAttributeText("isolatiedikte");
    if(is_array($attribute_value)) {
      $attribute_value = implode(", ", $attribute_value);
    }
    if(strlen($attribute_value) > 0) {
      $usps[] = "{$attribute_value} mm";
    }
    
    // Dimensions
    $width          = ($_product->getBreedte() + 0) / 10;
    $height         = ($_product->getHoogte()  + 0) / 10;
    $depth          = ($_product->getDiepte()  + 0) / 10;
    
    if($width  > 10) $width  = round($width);
    if($depth  > 10) $depth  = round($depth);
    if($height > 10) $height = round($height);
    
    if($width > 0 && $height > 0 && $depth > 0) {
      $usps[] = "(B){$width} x (D){$depth} x (H){$height}cm";
    } else {
      $width = $_product->getBreedte();
      if($width != '' && $width > 0) {
        // $usps[] = "Breedte: ".($width/10)."cm";
      }
    }
    
    // Diameter
    $attribute_value = $_product->getData('diameter_mm');
    if($attribute_value != ''){
      $usps[] = "Ø {$attribute_value} mm";
    }
    
    // Length
    $code = "length_mm";
    $attribute_value = $_product->getResource()->getAttribute($code)->getFrontend()->getValue($_product);
    if($attribute_value != '') {
      $attribute_value /= 10;
      $attribute_value = Mage::helper("deheerhoreca_util/util")->trim_decimals($attribute_value);
      $usps[] = "Lengte: {$attribute_value} cm";
    }
    
    // Quantity
    $attribute_value = (int) $_product->getData('eenheid');
    if($attribute_value > 1) {
      $usps[] = "{$attribute_value} stuks";
    }
    
    return $usps; 
  }
  
  public function getStockInfo($product, $context = null) {
    $stock_data = [];
        
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
    
    $txtstockdate = null;
    $real_txtstockdate = $product->getData("txtstockdate");
    if(empty($real_txtstockdate) === false
    // && ($overall_stock_status === "not_sellable" || $overall_stock_status === "backorder" || $manage_stock === false)
    ) {
      if(empty($real_txtstockdate) === false) {
        $datetime1  = new DateTime($real_txtstockdate);
        $now        = new DateTime("now");
        $interval   = $now->diff($datetime1)->format("%a");
        
        if($datetime1 > $now && $interval < 90) {        
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
      $stock_message_short  = "Pre-order";
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
  
  public static function trim_decimals($value, $decimals = 2) {
    if(is_scalar($value) && is_numeric($value)) {
      $value = number_format($value, $decimals, ",", ".");
      $decimal_string = ",".str_repeat("0", $decimals);
      $value = str_replace(",00", null, $value);
    }
    
    return $value;
  }
  
  public static function cleanCategoryName($category_name) {
    return trim(str_replace(["[V]", "[SKIPMENU]"], null, $category_name));
  }
  
  public static function getLeaseRates($price_ex_vat, $time = "daily") {
    $rates = [];
    
    // Using 400 for 500 to be a bit flexible
    
        // if(in_range($price_ex_vat, 2500 , 5000  )) $rates["72"] = $price_ex_vat * (1.95 / 100);
    // elseif(in_range($price_ex_vat, 5000 , 12500 )) $rates["72"] = $price_ex_vat * (1.77 / 100);
    // elseif(in_range($price_ex_vat, 12500, 25000 )) $rates["72"] = $price_ex_vat * (1.74 / 100);
    // elseif(in_range($price_ex_vat, 25000, 150000)) $rates["72"] = $price_ex_vat * (1.72 / 100);
    
        if(in_range($price_ex_vat, 400  , 5000  )) $rates["60"] = $price_ex_vat * (2.43 / 100);
    elseif(in_range($price_ex_vat, 2500 , 5000  )) $rates["60"] = $price_ex_vat * (2.25 / 100);
    elseif(in_range($price_ex_vat, 5000 , 12500 )) $rates["60"] = $price_ex_vat * (2.10 / 100);
    elseif(in_range($price_ex_vat, 12500, 25000 )) $rates["60"] = $price_ex_vat * (2.01 / 100);
    elseif(in_range($price_ex_vat, 25000, 150000)) $rates["60"] = $price_ex_vat * (1.97 / 100);

        // if(in_range($price_ex_vat, 400  , 5000  )) $rates["54"] = $price_ex_vat * (2.55 / 100);
    // elseif(in_range($price_ex_vat, 2500 , 5000  )) $rates["54"] = $price_ex_vat * (2.37 / 100);
    // elseif(in_range($price_ex_vat, 5000 , 12500 )) $rates["54"] = $price_ex_vat * (2.28 / 100);
    // elseif(in_range($price_ex_vat, 12500, 25000 )) $rates["54"] = $price_ex_vat * (2.25 / 100);
    // elseif(in_range($price_ex_vat, 25000, 150000)) $rates["54"] = $price_ex_vat * (2.21 / 100);
    
        if(in_range($price_ex_vat, 400  , 5000  )) $rates["48"] = $price_ex_vat * (2.76 / 100);
    elseif(in_range($price_ex_vat, 2500 , 5000  )) $rates["48"] = $price_ex_vat * (2.57 / 100);
    elseif(in_range($price_ex_vat, 5000 , 12500 )) $rates["48"] = $price_ex_vat * (2.49 / 100);
    elseif(in_range($price_ex_vat, 12500, 25000 )) $rates["48"] = $price_ex_vat * (2.43 / 100);
    elseif(in_range($price_ex_vat, 25000, 150000)) $rates["48"] = $price_ex_vat * (2.40 / 100);
    
        // if(in_range($price_ex_vat, 400  , 5000  )) $rates["42"] = $price_ex_vat * (3.03 / 100);
    // elseif(in_range($price_ex_vat, 2500 , 5000  )) $rates["42"] = $price_ex_vat * (2.94 / 100);
    // elseif(in_range($price_ex_vat, 5000 , 12500 )) $rates["42"] = $price_ex_vat * (2.85 / 100);
    // elseif(in_range($price_ex_vat, 12500, 25000 )) $rates["42"] = $price_ex_vat * (2.83 / 100);
    // elseif(in_range($price_ex_vat, 25000, 150000)) $rates["42"] = $price_ex_vat * (2.77 / 100);
    
        if(in_range($price_ex_vat, 400  , 5000  )) $rates["36"] = $price_ex_vat * (3.40 / 100);
    elseif(in_range($price_ex_vat, 2500 , 5000  )) $rates["36"] = $price_ex_vat * (3.29 / 100);
    elseif(in_range($price_ex_vat, 5000 , 12500 )) $rates["36"] = $price_ex_vat * (3.10 / 100);
    elseif(in_range($price_ex_vat, 12500, 25000 )) $rates["36"] = $price_ex_vat * (3.07 / 100);
    elseif(in_range($price_ex_vat, 25000, 150000)) $rates["36"] = $price_ex_vat * (3.02 / 100);
    
        // if(in_range($price_ex_vat, 400  , 5000  )) $rates["30"] = $price_ex_vat * (4.05 / 100);
    // elseif(in_range($price_ex_vat, 2500 , 5000  )) $rates["30"] = $price_ex_vat * (3.88 / 100);
    // elseif(in_range($price_ex_vat, 5000 , 12500 )) $rates["30"] = $price_ex_vat * (3.81 / 100);
    // elseif(in_range($price_ex_vat, 12500, 25000 )) $rates["30"] = $price_ex_vat * (3.78 / 100);
    // elseif(in_range($price_ex_vat, 25000, 150000)) $rates["30"] = $price_ex_vat * (3.75 / 100);
    
        if(in_range($price_ex_vat, 400  , 5000  )) $rates["24"] = $price_ex_vat * (4.75 / 100);
    elseif(in_range($price_ex_vat, 2500 , 5000  )) $rates["24"] = $price_ex_vat * (4.61 / 100);
    elseif(in_range($price_ex_vat, 5000 , 12500 )) $rates["24"] = $price_ex_vat * (4.51 / 100);
    elseif(in_range($price_ex_vat, 12500, 25000 )) $rates["24"] = $price_ex_vat * (4.35 / 100);
    elseif(in_range($price_ex_vat, 25000, 150000)) $rates["24"] = $price_ex_vat * (4.32 / 100);
    
        if(in_range($price_ex_vat, 400  , 5000  )) $rates["15"] = $price_ex_vat * (7.08 / 100);
    elseif(in_range($price_ex_vat, 2500 , 5000  )) $rates["15"] = $price_ex_vat * (7.03 / 100);
    elseif(in_range($price_ex_vat, 5000 , 12500 )) $rates["15"] = $price_ex_vat * (7.01 / 100);
    elseif(in_range($price_ex_vat, 12500, 25000 )) $rates["15"] = $price_ex_vat * (6.98 / 100);
    elseif(in_range($price_ex_vat, 25000, 150000)) $rates["15"] = $price_ex_vat * (6.93 / 100);
    
    if($time === "daily") {
      $rates = array_map( function($val) { return round(($val * 12) / 365, 2); }, $rates);
      $rates = array_map( function($val) { return max($val, .01); }, $rates); // At least 1 cent per day
    }
    
    return $rates;
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
    if(isset($_SERVER["REMOTE_ADDR"])
    && in_array($_SERVER["REMOTE_ADDR"], ["5.132.21.238", "185.127.111.251", "185.127.111.252", "87.210.61.235"])
    && isset($_GET['nofpc'])) {
      return true;
    }
    return false;
  }
}

function in_range($number, $min, $max, $inclusive = false) {
  if(is_numeric($number) && is_numeric($min) && is_numeric($max)) {
    return $inclusive
      ? ($number >= $min && $number <= $max)
      : ($number >= $min && $number < $max) ;
  }
  return false;
}
