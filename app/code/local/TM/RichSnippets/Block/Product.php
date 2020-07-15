<?php

class TM_RichSnippets_Block_Product extends Mage_Core_Block_Template
{
    public function __construct()
     {
         parent::__construct();
         $this->setTemplate('tm/richsnippets/richsnippets_product.phtml');
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
            || !Mage::getStoreConfigFlag('richsnippets/general/enabled')) {
            return '';
        }

        return parent::_toHtml();
    }

    public function getProduct()
    {
        if (null === $this->_product) {
            $this->_product = Mage::registry('product');
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
        $summaryData = Mage::getSingleton('review/review_summary')->setStoreId($storeId)->load($this->getProduct()->getId());
        if ($summaryData['rating_summary']){
            return $summaryData['rating_summary'];
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
        $summaryData = Mage::getSingleton('review/review_summary')->setStoreId($storeId)->load($this->getProduct()->getId());

        return (int)$summaryData['reviews_count'];
    }

    /**
     *
     * Returns product stock status
     *
     * @return string
     */
    public function getStockStatusUrl()
    {
        if ($this->getProduct()->isSaleable() === true){
            $availability = 'http://schema.org/InStock';
        } else {
            $availability = 'http://schema.org/OutOfStock';
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
            if (method_exists($priceModel, 'getTotalPrices')) {
                return $priceModel->getTotalPrices($product);
            }

            if (method_exists($priceModel, 'getPricesDependingOnTax')) { // Magento 1.5 and older
                return $priceModel->getPricesDependingOnTax($product);
            }
        }

        if ($productType instanceof Mage_Catalog_Model_Product_Type_Grouped) {

            $assocProducts = $productType->getAssociatedProductCollection($product)
                ->addMinimalPrice()
                ->setOrder('minimal_price', 'ASC');

            $product = $assocProducts->getFirstItem();

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
        return $this->helper('core')->currency(
            $this->helper('tax')->getPrice(
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
        return $this->helper('core')->currency(
            $this->helper('tax')->getPrice(
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
        $userConditionAttribute = Mage::getStoreConfig('richsnippets/condition/condition_attribute');
        $userConditionAttributeValue = strtolower($this->getAttributeText($userConditionAttribute));

        switch ($userConditionAttributeValue) {
            case strtolower(Mage::getStoreConfig('richsnippets/condition/condition_new_option')):
                return 'http://schema.org/NewCondition';
                break;

            case strtolower(Mage::getStoreConfig('richsnippets/condition/condition_used_option')):
                return 'http://schema.org/UsedCondition';
                break;

            case strtolower(Mage::getStoreConfig('richsnippets/condition/condition_damaged_option')):
                return 'http://schema.org/DamagedCondition';
                break;

            case strtolower(Mage::getStoreConfig('richsnippets/condition/condition_refurbished_option')):
                return 'http://schema.org/RefurbishedCondition';
                break;

            default:
                return 'http://schema.org/NewCondition';
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
      $description = (strlen($this->getProduct()->getDescription()) > 0) ? $this->getProduct()->getDescription() : $this->getProduct()->getName();
      $description = strip_tags($description);
        $data = array(
            '@context'              => 'http://schema.org',
            '@type'                 => 'Product',
            'name'                  => $this->getProduct()->getName(),
            'image'                 => (string)Mage::helper('catalog/image')->init($this->getProduct(), 'image'),
            'description'           => $description,
            'sku'                   => $this->getProduct()->getSku(),
            'brand'                 => $this->getProduct()->getAttributeText('manufacturer'), /* DHH CORE HACK */
            'url'                   => $this->getProduct()->getProductUrl(), // Use canonical url here, don't fuck around with SEO
            'gtin'                  => $this->getProduct()->getResource()->getAttribute('ean')->getFrontend()->getValue($this->getProduct()),
            'mpn'                   => $this->getProduct()->getData("sku_seller"),
            'offers'                => array(
                '@type'             => 'Offer',
                'availability'      => $this->getStockStatusUrl(),
                'priceCurrency'     => Mage::app()->getStore()->getCurrentCurrency()->getCode(),
                'itemCondition'     => "http://schema.org/NewCondition", /* DHH CORE HACK */
                "priceValidUntil"   => date('Y-m-d',strtotime(date("Y-m-d", mktime()) . " + 365 day")), /* DHH CORE HACK */
                'url'               => $this->getProduct()->getProductUrl(), // Use canonical url here, don't fuck around with SEO
                "seller"            => [ /* DHH CORE HACK */
                  "@type"           => "Organization", /* DHH CORE HACK */
                  "name"            => Mage::getStoreConfig('richsnippets/organization/name'), /* DHH CORE HACK */
                ] /* DHH CORE HACK */
            )
        );
        
        /* DHH CORE HACK */
        
        $stock_data           = Mage::helper("deheerhoreca_util/util")->getStockInfo($this->getProduct());
        $overall_stock_status = $stock_data["overall_stock_status"];
        
        switch($overall_stock_status) {
          case "in_stock":
            $data["offers"]["availability"] = "http://schema.org/InStock";
            break;
          case "backorder":
            $data["offers"]["availability"] = "http://schema.org/PreOrder";
            break;
          case "not_sellable":
            $data["offers"]["availability"] = "http://schema.org/OutOfStock";
            break;
          case "eol":
            $data["offers"]["availability"] = "http://schema.org/OutOfStock";
            break;
        }
        
        /* END DHH CORE HACK */

        if ($this->getReviewCount() > 0) {
            $data['aggregateRating']['@type'] = 'AggregateRating';
            $data['aggregateRating']['bestRating'] = '100';
            $data['aggregateRating']['worstRating'] = '0';
            $data['aggregateRating']['ratingValue'] = $this->getRatingSummary();
            $data['aggregateRating']['reviewCount'] = $this->getReviewCount();
            $data['aggregateRating']['ratingCount'] = $this->getReviewCount();
        }

        if (is_array($this->getPriceValues())) {

            $getPriceValues = $this->getPriceValues();

            $data['offers']['@type'] = 'AggregateOffer';
            $data['offers']['lowPrice'] = $this->getConvertedPrice(min($getPriceValues));
            $data['offers']['highPrice'] = $this->getConvertedPrice(max($getPriceValues));

        } else {
            $data['offers']['price'] = $this->getConvertedPrice($this->getPriceValues());
        }

        return Mage::helper('core')->jsonEncode($data);
    }

    /* Microdata code */

    /**
     * Returns array of product snippets data for microdata format
     *
     * @return Array
     */
    public function getMicrodataSnippetsProduct() {
        $data = array(
            'name'                  => $this->getProduct()->getName(),
            'image'                 => (string)Mage::helper('catalog/image')->init($this->getProduct(), 'image')->resize(80),
            'description'           => $this->getProduct()->getShortDescription(),
            'sku'                   => $this->getProduct()->getSku(),
            'offers'                => array(
                '@type'             => 'Offer',
                'availability'      => $this->getStockStatusUrl(),
                'priceCurrency'     => Mage::app()->getStore()->getCurrentCurrency()->getCode(),
                'itemCondition'     => $this->getItemCondition()
            )
        );
        if ($this->getReviewCount() > 0) {
            $data['aggregateRating']['@type'] = 'AggregateRating';
            $data['aggregateRating']['ratingValue'] = $this->getRatingSummary();
            $data['aggregateRating']['reviewCount'] = $this->getReviewCount();
            $data['aggregateRating']['ratingCount'] = $this->getReviewCount();
        }

        if (is_array($this->getPriceValues())) {
            unset($data['offers']['price']);

            $getPriceValues = $this->getPriceValues();

            $data['offers']['@type'] = 'AggregateOffer';
            $data['offers']['lowPrice'] = $this->getConvertedPrice(min($getPriceValues));
            $data['offers']['highPrice'] = $this->getConvertedPrice(max($getPriceValues));

        } else {
            $data['offers']['price'] = $this->getConvertedPrice($this->getPriceValues());
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
            $twitter_card_enabled   = Mage::getStoreConfig('richsnippets/socialcards/twittercard');
            $twitter_login          = Mage::getStoreConfig('richsnippets/social/twitter');
            $currentUrl             = Mage::helper('core/url')->getCurrentUrl();
            $productImage           = (string)Mage::helper('catalog/image')->init($product, 'image')->resize(80);

            if(($twitter_login && $twitter_card_enabled)) {
                $offers = $this->getFormattedPrice($this->getPriceValues());
                if (is_array($this->getPriceValues())){
                    $getPriceValues = $this->getPriceValues();
                    $offers = $this->getFormattedPrice($getPriceValues[0]);
                }
            } else {
                $offers = '';
            }
            if($twitter_login && $twitter_card_enabled) {
                $meta['twitter:card'] = 'product';
                $meta['twitter:url'] = $currentUrl;
                $meta['twitter:title'] = htmlspecialchars($product->getName());

                if($description = $product->getShortDescription()) {
                    $meta['twitter:description'] = htmlspecialchars($description);
                } else {
                    $meta['twitter:description'] = htmlspecialchars($product->getName()) . ' - ' . $offers;
                }

                $meta['twitter:image:src'] = $productImage;
                $meta['twitter:site'] = $twitter_login;
                $meta['twitter:creator'] = $twitter_login;
                $meta['twitter:data1'] = $offers;
                $meta['twitter:label1'] = 'PRICE';

                if($this->getStockStatusUrl()) {
                    if($this->getStockStatusUrl() == 'http://schema.org/InStock') {
                        $meta['twitter:data2'] = 'In Stock';
                        $meta['twitter:label2'] = 'AVAILABILITY';
                    }
                }

            }
            return $meta;
        }
        return false;
    }
}
