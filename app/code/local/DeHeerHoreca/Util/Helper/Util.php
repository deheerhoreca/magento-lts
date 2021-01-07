<?php

require_once 'vendor/autoload.php';

use Michelf\Markdown;
use Michelf\MarkdownExtra;

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
    */
    
    $product_name = $_product->getData("name");
    $product_short_name = $_product->getData("name_short");
    $image_label = $this->stripTags($_product->getData('small_image_label'), null, true);
    if(empty($image_label)) {
      $image_label = $this->stripTags($product_name, null, true);
    }
    
    /* Interpret options */
    $image_size = $options["image_size"] ?? 150;
    if(empty($options["skip_info"]) || $options["skip_info"] === false) {
      $product_info = Mage::helper("deheerhoreca_util/util")->getProductInfo($_product);
    }
    if(empty($options["skip_usps"]) || $options["skip_usps"] === false) {
      $product_usps = Mage::helper("deheerhoreca_util/util")->getProductUsps($_product);
    }
    $a_target = null;
    if(isset($options["_blank"]) === true && $options["_blank"] === true) {
      $a_target = " target='_blank'";
    }
    $display_product_name = $product_name;
    if(isset($options["use_short_product_names"]) === true && $options["use_short_product_names"] === true) {
      $display_product_name = $product_short_name;
    }
    if(isset($options["show_category_link"]) === true && $options["show_category_link"] === true) {
      $category_info = Mage::helper("deheerhoreca_util/util")->getCategoryFromProduct($_product);
    }
    if(isset($options["prefer_rewrite_table"]) === true && $options["prefer_rewrite_table"] === true) {
      $product_url = Mage::helper("deheerhoreca_util/util")->getFullProductUrlSafe($_product);
    } else {
      $product_url            = $_product->getProductUrl();
    }
    $skip_actions = $options["skip_actions"] ?? false;
    
    if(empty($options["display"]) === false) {
      $display = $options["display"];
    } else {
      $display = "normal";
    }
    
    /* Get all variables */    
    $image_dimensions       = 1 * $image_size;
    $max_product_info_items = 3;
    $tagline                = $_product->getTagline();
    $price_html             = $product_block->getPriceHtml($_product, true);
    $price_html             = str_replace(",00", ",-", $price_html);
    $price_html             = str_replace("€", null, $price_html);
    
    $stock_data             = Mage::helper("deheerhoreca_util/util")->getStockInfo($_product);
      
    $stock_message          = $stock_data["stock_message"];
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
      
    switch($overall_stock_status) {
      case "in_stock":
        if($display === "mini") {
          $stock_message = "Voorraad";
        }
        break;
      case "backorder":
        $stock_class = "buyblock-usp dark-color";
        if($display === "mini") {
          $stock_message = "Nabestelling";
        }
        break;
      case "not_sellable":
        if($display === "mini") {
          $stock_message = "Voorraad";
        }
      case "eol":
        break;
    }
    
    $img_url = $product_block->helper('catalog/image')->init($_product, 'small_image')->resize($image_dimensions);
    
    ?>
    <a href="<?php echo $product_url; ?>" title="<?php echo $image_label; ?>" class="product-image"<?php echo $a_target; ?>>
      <img class='lazy center' id='product-collection-image-<?php echo $_product->getId(); ?>'
        data-src='<?php echo $img_url; ?>'
        alt='<?php echo $image_label; ?>' width='<?php echo $image_size; ?>' height='<?php echo $image_size; ?>' />
    </a>
    <div class="product-info">
      <div class="info">
        <h2 class='product-name'>
          <a href='<?php echo $product_url; ?>' title='<?php echo $this->stripTags($product_name); ?> kopen'<?php echo $a_target; ?>><?php echo $display_product_name; ?></a>
        </h2>
        <?php
        if(isset($tagline) === true) {
          echo "<div class='product-list-tagline'>{$tagline}</div>";
        }
        
        if(empty($product_info) === false && (empty($options["skip_info"]) || $options["skip_info"] === false)) {
          echo "<ul>";
          foreach($product_info as $key => $item) {
            if($key === $max_product_info_items) break;
            echo "<li class='angle_before'>{$item}</li>";
          }
          echo "</ul>";
        }
        
        if(empty($product_usps) === false && (empty($options["skip_usps"]) || $options["skip_usps"] === false)) {
          echo "<ul class='product-list-highlights'>";
          foreach($product_usps as $usp) {
            echo "<li>{$usp}</li>";
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
            <span class="<?php echo $stock_class; ?>"><?php echo $stock_message; ?></span>
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
            
    $width = $attribute_value = $_product->getBreedte();
    $height = $attribute_value = $_product->getHoogte();
    $depth = $attribute_value = $_product->getDiepte();
    
    $width = $width + 0;
    $height = $height + 0;
    $depth = $depth + 0;
    
    $width /= 10;
    $height /= 10;
    $depth /= 10;
    
    if($width > 10) $width = round($width);
    if($depth > 10) $depth = round($depth);
    if($height > 10) $height = round($height);
    
    if($width > 0 && $height > 0 && $depth > 0) {
      $product_info[] = "B: {$width}cm D: {$depth}cm H: {$height}cm";
    } else {
      $attribute_value = $_product->getBreedte();
      if($attribute_value != '' && $attribute_value > 0) {
        $product_info[] = "Breedte: ".($attribute_value/10)."cm";
      }
    }

    if($category_id === 72) {
      $attribute_value = $_product->getInhoudAantalGn();
      if($attribute_value !='') {
        $product_info[] = "{$attribute_value} x {$gnvalue}";
      }
    }
    
    $attribute_value = (int) $_product->getData('eenheid');
    if($attribute_value > 1) {
      $product_info[] = "{$attribute_value} stuks";
    }
    
    $attribute_value = $_product->getAttributeText('size');
    if(is_scalar($attribute_value) && strlen($attribute_value) > 0) {
      $product_info[] = "Maat: {$attribute_value}";
    }
    
    if($display === "normal") {
      $attribute_value = $_product->getData('materiaal');
      if(strlen($attribute_value) > 0) {
        $product_info[] = "{$attribute_value}";
      }
    }
    
    $code = "uitvoering";
    $attribute_value = $_product->getResource()->getAttribute($code)->getFrontend()->getValue($_product);
    if(is_array($attribute_value) === true) {
      $attribute_value = implode(", ", $attribute_value);
    }
    if(strlen($attribute_value) > 0) {
      $product_info[] = "Uitvoering: {$attribute_value}";
    }
    
    return $product_info;
  }

  public function getProductUsps($_product, $options = []) {
    
    $parent_categories_ids = $options["parent_categories_ids"] ?? [];
    
    $usps = [];
    
    /* Type koeling */
    $attribute_value = $_product->getAttributeText('type_koeling');
    if($attribute_value != ''){
      $usps[] = "{$attribute_value}";
    }
      
    /* Afsluitbaar */
    $attribute_value = $_product->getAttributeText("afsluitbaar");
    if($attribute_value === "Ja") {
      $usps[] = "Afsluitbaar";
    }
    
    /* Blikjes */
    $attribute_value = (int) $_product->getData("aantal_blikjes");
    if($attribute_value > 0) {
      $usps[] = "{$attribute_value}x 33cl";
    }
    
    /* Flessen */
    $attribute_value = (int) $_product->getData("aantal_flessen");
    if($attribute_value > 0) {
      $usps[] = "{$attribute_value} flessen";
    }
    
    /* Capacity: Wine Bottels */
    $attribute_value = (int) $_product->getData("capacity_wine_bottles");
    if($attribute_value > 0) {
      $usps[] = "{$attribute_value} flessen";
    }
    
    /* GN maat if not in a GN category */
    if(empty($gnvalue) === false
      && empty($category_name) === false
      && strstr($category_name, "GN") === false) {
        if(is_array($gnvalue)) {
          $gnvalue = implode(" ", $gnvalue);
        }
        $usps[] = "{$gnvalue} GN";
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
    
    /* Inhoud */
    $attribute_value = $_product->getInhoudLiters();
    if(strlen($attribute_value) > 0) {
      $usps[] = round($attribute_value, 2)." liter";
    }
    
    /* Vermogen */
    if(in_array(3, $parent_categories_ids) === false) { // skip "Koelingen" and everything underneath
      $attribute_value = $_product->getVermogen();
      if(empty($attribute_value) === false && $attribute_value > 0) {
        if($attribute_value < 3) {
          $attribute_value *= 1000;
          $usps[] = "{$attribute_value} Watt";
        } else {
          //$attribute_value = str_replace(".", null, $attribute_value);
          //$attribute_value = str_replace(",", ".", $attribute_value);
          $attribute_value = number_format($attribute_value, 1, ",", ".");
          $attribute_value = str_replace(",0", null, $attribute_value);
          $usps[] = "{$attribute_value} kW";
        }
      }
    }
    
    /* Gas Vermogen */
    $attribute_value = $_product->getVermogenKw();
    if(empty($attribute_value) === false && $attribute_value > 0) {
      if($attribute_value < 3) {
        $attribute_value *= 1000;
        $usps[] = "{$attribute_value} Watt";
      } else {
        $attribute_value = number_format($attribute_value, 1, ",", ".");
        $attribute_value = str_replace(",0", null, $attribute_value);
        $usps[] = "{$attribute_value} kW";
      }
    }
    
    /* M3/hour capacity */
    $attribute_value = $_product->getAantalM3Uur();
    if(strlen($attribute_value) > 0) {
      $usps[] = round($attribute_value, 2)." m3";
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
    
    return $usps; 
  }
  
  public function getStockInfo($product) {
    $stock_data = [];
    
    /* Availability, Stock */
    $stock_message          = null;
    $txtcltcz               = null;
    $stock_qty              = (int) $product->getStockItem()->getQty();
    $in_stock               = $product->getStockItem()->getIsInStock(); // "0", "1", true
    $in_stock               = ($in_stock === true || $in_stock === "1") ? true : false;
    $backorders             = $product->getStockItem()->getBackorders();
    $saleable               = $product->isSaleable();
    $eol                    = ($product->getEol() === true || $product->getEol() === "2075") ? true : false;
    $eol_replacement_sku    = $product->getEolReplacementSku();
    $manage_stock           = ($product->getStockItem()->getManageStock() === true || $product->getStockItem()->getManageStock() === "1") ? true : false;
    $extra_delivery_time    = 0;
    $expected_delivery      = $product->getResource()->getAttribute("levertijd")->getFrontend()->getValue($product);
    $levertijd              = $product->getAttributeText("levertijd");
    $levertijd_tmp_override = $product->getAttributeText("levertijd_tmp_override");
    $bestelartikel          = $product->getAttributeText("bestelartikel");
    $min_sale_qty           = $product->getStockItem()->getMinSaleQty();
    
    $calwekdate_min         = $calwekdate_max = null;
    
    /* Temporarily adjust levertijd during holidays */
    
    /*
    $supplier = $product->getAttributeText('supplier');
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

    // Code also exists in list.phtml and featured.phtml

    if($eol === true) {
      
      // EOL
      
      $stock_message        = "Nooit meer leverbaar";
      $delivery_text        = "";
      $txtcltcz             = 'clzsoldout';
      $backorder_needed     = null;      
      $overall_stock_status = "eol";
      
    } elseif($in_stock === false || $saleable === false) {
      
      // Not sellable
      
      $stock_message        = "Niet op voorraad, bel voor alternatieven";
      $delivery_text        = "";
      $txtcltcz             = 'clzsoldout';
      $backorder_needed     = null;
      $tagline              = null;
      $overall_stock_status = "not_sellable";
      
    } elseif($bestelartikel === "Ja" || ($stock_qty <= 0 && $manage_stock === true)) {
        
      // Backorder
      
      $stock_message        = "Op nabestelling, of bel voor alternatieven";
      $delivery_text        = "Verw. levering: <strong>Op aanvraag</strong>";
      $txtcltcz             = 'clzinstocktemp';
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
      
      if($manage_stock === false || $stock_qty === 100) {
        // 100 is a special value, when we don't have an exact quantity
        $stock_message      = "Op voorraad";
      } elseif($stock_qty > 10) {
        $stock_message      = "<strong>10+</strong> op voorraad";
      } elseif($stock_qty <= 5) {
        $stock_message      = "Nog maar <strong>{$stock_qty}</strong> op voorraad";
      } else {
        $stock_message      = "<strong>{$stock_qty}</strong> op voorraad";
      }
      $txtcltcz             = 'buyblock-usp';
      $backorder_needed     = false;
      $overall_stock_status = "in_stock";
      
      $stock_data["bestelartikel"] = $bestelartikel;
      
    }
    
    // $txtstockdate = $product->getData('txtstockdate');
    $real_txtstockdate = $product->getData('txtstockdate');
    if($overall_stock_status === "not_sellable" || $overall_stock_status === "backorder") {
      if(empty($real_txtstockdate) === false) {
        $datetime1  = new DateTime($real_txtstockdate);
        $now        = new DateTime("now");
        $interval   = $now->diff($datetime1)->format("%a");
        
        if($datetime1 > $now && $interval < 90) {        
          $txtstockdate = date("d-m-Y", strtotime($real_txtstockdate));
        }
      }
    }
    
    $stock_data["stock_message"]          = $stock_message;
    $stock_data["txtcltcz"]               = $txtcltcz;
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
    
    // if($_SERVER["REMOTE_ADDR"] === "185.127.111.251" && isset($_GET['nofpc'])) {
      // echo "<pre>";
      // printr($stock_data);
      // echo "</pre>";
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
