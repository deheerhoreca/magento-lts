<?php

class TM_RichSnippets_Block_Product extends Mage_Core_Block_Template
{
    public function __construct()
     {
         parent::__construct();
         $this->setTemplate("tm/richsnippets/richsnippets_product.phtml");
     }
    protected $_product = null;

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->getProduct()
            || !Mage::getStoreConfigFlag("richsnippets/general/enabled")) {
            return "";
        }

        return parent::_toHtml();
    }

    public function getProduct()
    {
        if (null === $this->_product) {
            $this->_product = Mage::registry("product");
            // magento 1.4 fix
            $description = $this->_product->getShortDescription();
            if (null === $description) {
                $this->_product->load($this->_product->getId());
            }
        }
        return $this->_product;
    }

    public function getRatingSummary(){
        $storeId = Mage::app()->getStore()->getId();
        $summaryData = Mage::getSingleton("review/review_summary")->setStoreId($storeId)->load($this->getProduct()->getId());
        if ($summaryData["rating_summary"]){
            return $summaryData["rating_summary"];
        }
        return 0;
    }

    /**
     * Returns product reviews quantity
     *
     * @return int
     */
    public function getReviewCount()
    {
        $storeId = Mage::app()->getStore()->getId();
        $summaryData = Mage::getSingleton("review/review_summary")->setStoreId($storeId)->load($this->getProduct()->getId());

        return (int)$summaryData["reviews_count"];
    }

    /**
     *
     * Returns product stock status
     *
     * @return string
     */
     // @todo add more options: https://developers.google.com/search/docs/appearance/structured-data/product#json-ld
    public function getStockStatusUrl()
    {
        if ($this->getProduct()->isSaleable() === true){
            $availability = "http://schema.org/InStock";
        } else {
            $availability = "http://schema.org/OutOfStock";
        }
        return $availability;
    }

    /**
     * @return mixed Array with min and max values or float
     */
    public function getPriceValues()
    {
        $product     = $this->getProduct();
        $priceModel  = $product->getPriceModel();
        $productType = $product->getTypeInstance();

        if ($productType instanceof Mage_Bundle_Model_Product_Type) {
            if (method_exists($priceModel, "getTotalPrices")) {
                return $priceModel->getTotalPrices($product);
            }

            if (method_exists($priceModel, "getPricesDependingOnTax")) { // Magento 1.5 and older
                return $priceModel->getPricesDependingOnTax($product);
            }
        }

        if ($productType instanceof Mage_Catalog_Model_Product_Type_Grouped) {

            $assocProducts = $productType->getAssociatedProductCollection($product)
                ->addMinimalPrice()
                ->setOrder("minimal_price", "ASC");

            $product = $assocProducts->getFirstItem();

            $groupedProductsPricesArray = []; // DHH CORE HACK
            foreach ($assocProducts as $assocProduct) {
                $groupedProductsPricesArray[] = $assocProduct->getFinalPrice();
            }

            if ($product) {
                return $groupedProductsPricesArray; // Array of all grouped products prices
            }
        }
        $minPrice   = $product->getMinimalPrice();
        $finalPrice = $product->getFinalPrice();
        if ($minPrice && $minPrice < $finalPrice) {
            return array($minPrice, $finalPrice);
        }
        return (float) $finalPrice;
    }

    /**
     * Returns formatted price according to locale
     *
     * Example:
     *  $10.22
     *
     * @param  float $price
     * @return string
     */
    public function getFormattedPrice($price)
    {
        return $this->helper("core")->currency(
            $this->helper("tax")->getPrice(
                $this->getProduct(),
                $price,
                true        // TRUE/FALSE: price with/without VAT
            ),
            true,
            false
        );
    }

    /**
     * Returns converted price according to currency rate
     *
     * Example:
     *  10.22
     *
     * @param  $price Float
     * @return Float
     */
    public function getConvertedPrice($price)
    {
        return $this->helper("core")->currency(
            $this->helper("tax")->getPrice(
                $this->getProduct(),
                $price,
                true        // TRUE/FALSE: price with/without VAT
            ),
            false,
            false
        );
    }

    public function getShortDescription()
    {
        $description = strip_tags($this->getProduct()->getShortDescription());
        $description = str_replace("\"", "'", $description);
        return $description;
    }

    /**
     * Returns products attribute value
     *
     * @param  string $attributeCode
     * @return string
     */
    public function getAttributeText($attributeCode)
    {
        $product   = $this->getProduct();
        $attribute = $product->getResource()
            ->getAttribute($attributeCode);

        if (!$attribute) {
            return false;
        }
        return str_replace("\"", "'", $attribute->getFrontend()->getValue($product));
    }

    /**
     * Returns the item condition.
     *
     * @return     string  The item condition according to Schema.org itemCondition.
     */

    public function getItemCondition()
    {
        $userConditionAttribute = Mage::getStoreConfig("richsnippets/condition/condition_attribute");
        $userConditionAttributeValue = strtolower($this->getAttributeText($userConditionAttribute));

        switch ($userConditionAttributeValue) {
            case strtolower(Mage::getStoreConfig("richsnippets/condition/condition_new_option")):
                return "http://schema.org/NewCondition";
                break;

            case strtolower(Mage::getStoreConfig("richsnippets/condition/condition_used_option")):
                return "http://schema.org/UsedCondition";
                break;

            case strtolower(Mage::getStoreConfig("richsnippets/condition/condition_damaged_option")):
                return "http://schema.org/DamagedCondition";
                break;

            case strtolower(Mage::getStoreConfig("richsnippets/condition/condition_refurbished_option")):
                return "http://schema.org/RefurbishedCondition";
                break;

            default:
                return "http://schema.org/NewCondition";
                break;
        }
    }

    /*JSON code*/

    /**
     * Returns JSON encoded array of product snippets data
     *
     * @return Array
     */
    public function getJsonSnippetsProduct()
    {
        /* DHH CORE HACK */
        
        $_product         = $this->getProduct();
        $sku              = (string) $_product->getSku();
        $name             = (string) substr($_product->getName(), 0, 150);  // Max length not clear, taking 150 from observed warnings
        
        // Fixed fields
        $data = [
          "@context"              => "http://schema.org",
          "@type"                 => "Product",
          "name"                  => $name,
          "sku"                   => $sku,
          // "image"                 => (string) Mage::helper("catalog/image")->init($_product, "image"),
          "url"                   => $_product->getProductUrl(),              // Use canonical url here, don't fuck around with SEO
          "offers"                => [
            "@type"                 => "Offer",
            "availability"          => $this->getStockStatusUrl(),
            "priceCurrency"         => Mage::app()->getStore()->getCurrentCurrency()->getCode(),
            "itemCondition"         => "http://schema.org/NewCondition",
            "priceValidUntil"       => date("Y-m-d", strtotime("+1 year")),   // DHH CORE HACK PHP 8
            "url"                   => $_product->getProductUrl(),            // Use canonical url here, don"t fuck around with SEO
            "seller"                => [
              "@type"               => "Organization",
              "name"                => "Chefstore.nl",
            ]
          ]
        ];
        
        // Optional fields
        
        // image
        $product_image_type = "thumbnail";
        $media              = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $image_url          = "{$media}catalog/product{$_product->getData($product_image_type)}";
        if(!empty($image_url)) {
          $media_dir          = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA);
          $image_path         = "{$media_dir}/catalog/product{$_product->getData($product_image_type)}";
          $cdn_img_options    = [
            "fs_path"           => $image_path,
            "url"               => $image_url,
            "url_only"          => true,
            "width"             => 2048,
            "height"            => 2048,
            "omcatprdlst"       => "omcatprdlst",
            "add_mod_time"      => true,
            "relative_url"      => false,
          ];
          $data["image"]    = Mage::helper("deheerhoreca_util/util")->_cdn_img($cdn_img_options);
        }
        
        // brand
        $data["brand"] = (string) (_get_product_attribute($_product, "manufacturer") ?: "Chefstore.nl");
        
        // @todo:
        // "logo"                  => "", @todo brand logo
        // "asin"                  => "", @todo
        // "category"              => "", @todo
        
        // gtin
        $schema_attr = "gtin";
        $om_attr = "ean";
        if($value = _get_product_attribute($_product, $om_attr)) {
          $data[$schema_attr] = $value;
          unset($value);
        }
        
        // gtin13
        $schema_attr = "gtin13";
        $om_attr = "ean13";
        if($value = _get_product_attribute($_product, $om_attr)) {
          $data[$schema_attr] = $value;
          unset($value);
        }
        
        // mpn
        $schema_key = "mpn";
        if($value = _get_product_attribute($_product, "mpn") ?? _get_product_attribute($_product, "sku_seller")) {
          $data[$schema_key] = $value;
          unset($value);
        }
        
        // description
        $schema_key = "description";
        if($value = Mage::helper("deheerhoreca_util/util")->_get_product_description($_product)) {
          $value = strip_tags($value);
          if(strlen($value) > 0) {
            $data[$schema_key] = $value;
          }
          unset($value);
        }
        
        // width
        $schema_key = "width";
        $om_attr = "breedte";
        if($value = _get_product_attribute($_product, $om_attr)) {
          $data[$schema_key] = [
            "@type"     => "QuantitativeValue",
            "unitCode"  => "MMT",
            "value"     => $value,
          ];
          unset($value);
        }
        
        // depth
        $schema_key = "depth";
        if($value = _get_product_attribute($_product, "diepte") ?? _get_product_attribute($_product, "length_mm")) {
          $data[$schema_key] = [
            "@type"     => "QuantitativeValue",
            "unitCode"  => "MMT",
            "value"     => $value,
          ];
        }
        
        // height
        $schema_key = "height";
        $om_attr = "hoogte";
        if($value = _get_product_attribute($_product, $om_attr)) {
          $data[$schema_key] = [
            "@type"     => "QuantitativeValue",
            "unitCode"  => "MMT",
            "value"     => $value,
          ];
          unset($value);
        }
        
        // // size
        // @todo
        // $schema_key = "size";
        // $value = _get_product_attribute($_product, "size");
        // if(strlen($value) > 0) {
          // $data[$schema_key] = [
            // "@type"     => "QuantitativeValue",
            // "unitCode"  => "MMT",
            // "value"     => $value,
          // ];
          // unset($value);
        // }
        
        // weight
        $schema_key = "weight";
        if($value = _get_product_attribute($_product, "weight")) {
          $data[$schema_key] = [
            "@type"     => "QuantitativeValue",
            "unitCode"  => "KGM",
            "value"     => $value,
          ];
        }
        
        // color
        $schema_key = "color";
        $value = _get_product_attribute($_product, "color");
        if(empty($value)) {
          $value = _get_product_attribute($_product, "colors");
        }
        if(strlen($value) > 0) {
          $data[$schema_key] = $value;
        }
        
        // material
        $schema_key = "material";
        $value = _get_product_attribute($_product, "materiaal");
        if(empty($value)) {
          $value = _get_product_attribute($_product, "material_group");
        }
        if(strlen($value) > 0) {
          $data[$schema_key] = $value;
        }
        
        // countryOfOrigin
        $schema_key = "countryOfOrigin";
        $value = _get_product_attribute($_product, "made_in_country_2code");
        if(strlen($value) > 0) {
          $data[$schema_key] = $value;
        }
        
        // @todo EnergyConsumptionDetails
        // @todo isRelatedTo
        // @todo isSimilarTo
        
        // DHH CORE HACK
        // Offer fields
        $prices = $this->getPriceValues();
        if(is_array($prices) && !empty($prices)) {
          $data["offers"]["@type"] = "AggregateOffer";
          $data["offers"]["lowPrice"] = $this->getConvertedPrice(min($prices));
          $data["offers"]["highPrice"] = $this->getConvertedPrice(max($prices));
        } else {
          $data["offers"]["price"] = $this->getConvertedPrice($prices);
        }
        
        // warranty
        $schema_key = "warranty";
        $value = _get_product_attribute($_product, "garantie");
        $months = "";
        switch($value) {
          case "6 maanden":   $months =  6;  break;
          case "12 maanden":  $months = 12;  break;
          case "24 maanden":  $months = 24;  break;
          case "36 maanden":  $months = 36;  break;
          case "60 maanden":  $months = 60;  break;
        }
        
        if(strlen($months) > 0) {
          $data["offers"][$schema_key] = [
            "@type"     => "WarrantyPromise",
            "durationOfWarranty" => [
              "@type"     => "QuantitativeValue",
              "value"     => "{$months}",
              "unitCode"  => "MON"
            ]
          ];
        }
        
        $schema_key = "priceValidUntil";
        $value      = $_product->getData("special_to_date"); // Need raw value
        if(empty($value) === false && strtotime($value) > time()) {
          $value = date("Y-m-d", strtotime($value));
        }
        if(empty($value)) {
          $value = date("Y-m-d", strtotime("+1 week")); // DHH CORE HACK PHP 8
        }
        if(strlen($value) > 0) {
          $data["offers"][$schema_key] = $value;
        }
        
        $value      = _get_product_attribute($_product, "levertijd_tmp_override");
        if(empty($value) === false && strtotime($value) > time()) {
          $value      = _get_product_attribute($_product, "levertijd");
        }
        
        switch($value) {
          case "1 werkdag":       $min_days = 0; $max_days = 1; break;
          case "1-2 werkdagen":   $min_days = 1; $max_days = 2; break;
          case "1-3 werkdagen":   $min_days = 1; $max_days = 3; break;
          case "1-5 werkdagen":   $min_days = 1; $max_days = 5; break;
          case "2-3 werkdagen":   $min_days = 2; $max_days = 3; break;
          case "3-4 werkdagen":   $min_days = 3; $max_days = 4; break;
          case "3-10 werkdagen":  $min_days = 3; $max_days = 10; break;
          case "4-5 werkdagen":   $min_days = 4; $max_days = 5; break;
          case "5-6 werkdagen":   $min_days = 5; $max_days = 6; break;
          case "6-8 werkdagen":   $min_days = 6; $max_days = 8; break;
          case "7-10 werkdagen":  $min_days = 7; $max_days = 10; break;
          case "8-12 werkdagen":  $min_days = 8; $max_days = 12; break;
          case "10-15 werkdagen": $min_days = 10; $max_days = 15; break;
          case "3-4 weken":       $min_days = 15; $max_days = 20; break;
          case "4-5 weken":       $min_days = 20; $max_days = 25; break;
          default:                $min_days = 1; $max_days = 5; break;
        }
        
        $shipping_cost = ""; // DHH CORE HACK
        if(isset($data["offers"]["price"]) && is_numeric($data["offers"]["price"])) {
          if(($data["offers"]["price"] / 1.21) > Mage::getStoreConfig("carriers/freeshipping/free_shipping_subtotal", $this->getStoreId())) {
            $shipping_cost = 0;
          } else {
            $shipping_cost = 9.95;
          }
        } else {
          Mage::log("{$sku} Product no price while doing getJsonSnippetsProduct()", null, "system.log", true); // DHH CORE HACK
        }
        
        // @todo shippingDetails
        $schema_key= "shippingDetails";
        $data["offers"][$schema_key] = [
          "@type"               => "OfferShippingDetails",
          "shippingDestination" => [
            "@type"               => "DefinedRegion",
            "addressCountry"      => ["BE", "NL"],
          ],
          "shippingRate"        => [
            "@type"               => "MonetaryAmount",
            "value"               => $shipping_cost,
            "currency"            => "EUR"
          ],
          "deliveryTime"        => [
            "@type"               => "ShippingDeliveryTime",
            "businessDays"        => [
              "@type"             => "OpeningHoursSpecification",
              "dayOfWeek"         => [
                "https://schema.org/Monday",
                "https://schema.org/Tuesday",
                "https://schema.org/Wednesday",
                "https://schema.org/Thursday",
                "https://schema.org/Friday"
              ]
            ],
            // "cutoffTime"          => "12:00:15Z", // @todo
            "handlingTime"        => [
              "@type"               => "QuantitativeValue",
              "minValue"            => 0,
              "maxValue"            => 1,
              "unitCode"            => "d"
            ],
            "transitTime"        => [
              "@type"               => "QuantitativeValue",
              "minValue"            => $min_days,
              "maxValue"            => $max_days,
              "unitCode"            => "d"
            ],
          ],
        ];
        
        // Stock fields
        $stock_data           = Mage::helper("deheerhoreca_util/util")->getStockInfo($_product);
        $overall_stock_status = $stock_data["overall_stock_status"];
        
        switch($overall_stock_status) {
          case "in_stock":       $data["offers"]["availability"] = "http://schema.org/InStock";    break;
          case "backorder":      $data["offers"]["availability"] = "http://schema.org/PreOrder";   break;
          case "not_sellable":   $data["offers"]["availability"] = "http://schema.org/OutOfStock"; break;
          case "eol":            $data["offers"]["availability"] = "http://schema.org/OutOfStock"; break;
        }
        
        /* END DHH CORE HACK */

        if ($this->getReviewCount() > 0) {
            $data["aggregateRating"]["@type"] = "AggregateRating";
            $data["aggregateRating"]["bestRating"] = "100";
            $data["aggregateRating"]["worstRating"] = "0";
            $data["aggregateRating"]["ratingValue"] = $this->getRatingSummary();
            $data["aggregateRating"]["reviewCount"] = $this->getReviewCount();
            $data["aggregateRating"]["ratingCount"] = $this->getReviewCount();
        }

        return Mage::helper("core")->jsonEncode($data);
    }

    /* Microdata code */

    /**
     * Returns array of product snippets data for microdata format
     *
     * @return Array
     */
    public function getMicrodataSnippetsProduct() {
        $data = array(
            "name"                  => $this->getProduct()->getName(),
            "image"                 => (string) Mage::helper("catalog/image")->init($this->getProduct(), "image")->resize(80), // @todo replace with cdn
            "description"           => $this->getProduct()->getShortDescription(),
            "sku"                   => $this->getProduct()->getSku(),
            "offers"                => array(
                "@type"             => "Offer",
                "availability"      => $this->getStockStatusUrl(),
                "priceCurrency"     => Mage::app()->getStore()->getCurrentCurrency()->getCode(),
                "itemCondition"     => $this->getItemCondition()
            )
        );
        if ($this->getReviewCount() > 0) {
            $data["aggregateRating"]["@type"] = "AggregateRating";
            $data["aggregateRating"]["ratingValue"] = $this->getRatingSummary();
            $data["aggregateRating"]["reviewCount"] = $this->getReviewCount();
            $data["aggregateRating"]["ratingCount"] = $this->getReviewCount();
        }

        if (is_array($this->getPriceValues())) {
            unset($data["offers"]["price"]);

            $getPriceValues = $this->getPriceValues();

            $data["offers"]["@type"] = "AggregateOffer";
            $data["offers"]["lowPrice"] = $this->getConvertedPrice(min($getPriceValues));
            $data["offers"]["highPrice"] = $this->getConvertedPrice(max($getPriceValues));

        } else {
            $data["offers"]["price"] = $this->getConvertedPrice($this->getPriceValues());
        }

        return $data;
    }


    /**
     * Returns array of product metadata
     *
     * @return Array
     */
    public function getProductMeta() {
        if($product = $this->getProduct()) {
            $meta = array();
            $twitter_card_enabled   = Mage::getStoreConfig("richsnippets/socialcards/twittercard");
            $twitter_login          = Mage::getStoreConfig("richsnippets/social/twitter");
            $currentUrl             = Mage::helper("core/url")->getCurrentUrl();
            $productImage           = (string) Mage::helper("catalog/image")->init($product, "image")->resize(80); // @todo replace with cdn

            if(($twitter_login && $twitter_card_enabled)) {
                $offers = $this->getFormattedPrice($this->getPriceValues());
                if (is_array($this->getPriceValues())){
                    $getPriceValues = $this->getPriceValues();
                    $offers = $this->getFormattedPrice($getPriceValues[0]);
                }
            } else {
                $offers = "";
            }
            if($twitter_login && $twitter_card_enabled) {
                $meta["twitter:card"] = "product";
                $meta["twitter:url"] = $currentUrl;
                $meta["twitter:title"] = htmlspecialchars($product->getName());

                if($description = $product->getShortDescription()) {
                    $meta["twitter:description"] = htmlspecialchars($description);
                } else {
                    $meta["twitter:description"] = htmlspecialchars($product->getName()) . " - " . $offers;
                }

                $meta["twitter:image:src"] = $productImage;
                $meta["twitter:site"] = $twitter_login;
                $meta["twitter:creator"] = $twitter_login;
                $meta["twitter:data1"] = $offers;
                $meta["twitter:label1"] = "PRICE";

                if($this->getStockStatusUrl()) {
                    if($this->getStockStatusUrl() == "http://schema.org/InStock") {
                        $meta["twitter:data2"] = "In Stock";
                        $meta["twitter:label2"] = "AVAILABILITY";
                    }
                }

            }
            return $meta;
        }
        return false;
    }
}
