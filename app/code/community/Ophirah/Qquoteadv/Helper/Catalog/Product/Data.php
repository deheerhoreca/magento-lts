<?php
/**
 *
 * CART2QUOTE CONFIDENTIAL
 * __________________
 *
 *  [2009] - [2020] Cart2Quote B.V.
 *  All Rights Reserved.
 *
 * NOTICE OF LICENSE
 *
 * All information contained herein is, and remains
 * the property of Cart2Quote B.V. and its suppliers,
 * if any.  The intellectual and technical concepts contained
 * herein are proprietary to Cart2Quote B.V.
 * and its suppliers and may be covered by European and Foreign Patents,
 * patents in process, and are protected by trade secret or copyright law.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Cart2Quote B.V.
 *
 * @category    Ophirah
 * @package     Qquoteadv
 * @copyright   Copyright (c) 2020 Cart2Quote B.V. (https://www.cart2quote.com)
 * @license     https://www.cart2quote.com/ordering-licenses(https://www.cart2quote.com)
 */

/**
 * Class Ophirah_Qquoteadv_Helper_Catalog_Product_Data
 */
class Ophirah_Qquoteadv_Helper_Catalog_Product_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Retrieve url for add product to cart
     * Will return product view page URL if product has required options
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $additional
     * @return string
     */
    public function getUrlAdd2QquoteadvList(Mage_Catalog_Model_Product $product, $additional = [])
    {
        $quoteAdvUrlPath = 'qquoteadv/index';
        //check if there are no required options
        $hasRequiredOptions = false;
        $request = new Varien_Object(['qty' => 1]);
        $resultPrepare = $product->getTypeInstance(true)->prepareForCartAdvanced($request, $product, null);
        if (is_string($resultPrepare)) {
            $hasRequiredOptions = true;
        }

        if ($product->getTypeInstance(true)->hasRequiredOptions($product) || $hasRequiredOptions) {
            $url = $product->getProductUrl();
            $link = (strpos($url, '?') !== false) ? '&' : '?';
            return $url . $link . 'options=cart&c2qredirect=1';
        }
        return $this->getUrlAdd2QquoteadvById($product->getId());
    }

    /**
     * Get add to quote url for a product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $additional
     * @return mixed
     */
    public function getUrlAdd2Qquoteadv(Mage_Catalog_Model_Product $product, $additional = [])
    {
        return $this->getUrlAdd2QquoteadvById($product->getId());
    }

    /**
     * Get the add to quote url by product id
     *
     * @param $productId
     * @return mixed
     */
    public function getUrlAdd2QquoteadvById($productId)
    {
        $quoteAdvUrlPath = 'qquoteadv/index';
        $url = "addItem";
        if (Mage::getStoreConfig('qquoteadv_quote_frontend/catalog/ajax_add') && Mage::helper('qquoteadv')->checkQuickQuote() != "1") $url = "addItemAjax";
        return Mage::getUrl($quoteAdvUrlPath . '/' . $url . '/', ["product" => $productId, '_secure' => Mage::app()->getStore()->isCurrentlySecure()]);
    }

    /**
     * Get the onclick action for the add to quote button
     *
     * @param $productId
     * @return string
     */
    public function getAddToQuoteAction($productId)
    {
        $isAjax = Mage::getStoreConfig('qquoteadv_quote_frontend/catalog/ajax_add');
        $url = $this->getUrlAdd2QquoteadvById($productId);
        $actionQuote = "addQuote('" . $url . "', $isAjax );";

        if (Mage::helper('qquoteadv')->checkQuickQuote()) {
            // Set Quick Quote Action
            $actionQuote =
                "getProductInfo('".
                Mage::helper('qquoteadv/catalog_product_data')->getQuickQuoteProductUrl($productId).
                "'); ";
        }

        return $actionQuote;
    }

    /**
     * Function that can compare bundles based on the same product
     *
     * @param $product_id
     * @param $options1
     * @param $options2
     * @return bool
     */
    public function compareBundles($product_id, $options1, $options2)
    {
        $product = Mage::getModel('catalog/product')->load($product_id);
        if ($product instanceof Mage_Catalog_Model_Product) {
            $product2 = clone $product;

            $product->getTypeInstance()->prepareForCartAdvanced(new Varien_Object(unserialize($options1)), $product);
            $product2->getTypeInstance()->prepareForCartAdvanced(new Varien_Object(unserialize($options2)), $product2);

            $identity1 = $product->getCustomOption('bundle_identity');
            $identity2 = $product2->getCustomOption('bundle_identity');

            if ($identity2 != null) {
                if (($identity1->getValue()) == ($identity2->getValue())) {
                    return true;
                }
            }

        }

        return false;
    }

    /**
     * Function that can compare configurables based on the same product
     *
     * @param $product_id
     * @param $options1
     * @param $options2
     * @return bool
     */
    public function compareConfigurable($product_id, $options1, $options2)
    {
        $product = Mage::getModel('catalog/product')->load($product_id);
        if ($product instanceof Mage_Catalog_Model_Product) {
            $product2 = clone $product;

            $product->getTypeInstance()->prepareForCartAdvanced(new Varien_Object(unserialize($options1)), $product);
            $product2->getTypeInstance()->prepareForCartAdvanced(new Varien_Object(unserialize($options2)), $product2);

            $identity1 = $product->getCustomOption('attributes');
            $identity2 = $product2->getCustomOption('attributes');

            if ($identity1 instanceof Mage_Catalog_Model_Product_Configuration_Item_Option &&
                $identity2 instanceof Mage_Catalog_Model_Product_Configuration_Item_Option
            ) {
                if ($identity1->getValue() == $identity2->getValue()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Calculate new image sizes from original ratio
     * Supports both Mage image object as image files
     *
     * @param  $image
     * @param null $width
     * @param null $height
     * @return array
     */
    public function getItemPictureDimensions($image, $width = null, $height = null)
    {
        // Define variables
        $return = [];
        $newRatio = null;

        // Original image size
        // Mage image object
        if (is_object($image) && ($image instanceof Mage_Catalog_Helper_Image)) {
            $orgWidth = (int)$image->getOriginalWidth();
            $orgHeight = (int)$image->getOriginalHeight();
        }

        // Zend PDF image object
        if (is_object($image) && ($image instanceof Zend_Pdf_Resource_Image)) {
            $orgWidth = (int)$image->getPixelWidth();
            $orgHeight = (int)$image->getPixelHeight();
        }

        // File
        if (is_file($image)) {
            list($orgWidth, $orgHeight, $type, $attr) = getimagesize($image);
        }

        if (isset($orgWidth) && isset($orgHeight)) {
            // Calculate original ratio
            $originalRatio = $orgWidth / $orgHeight;

            $newWidth = $orgWidth;
            $newHeight = $orgHeight;

            // Width is largest size
            if ($originalRatio > 1) {
                if (!$width == null && (int)$width > 0) {
                    $newWidth = $width;
                    $newHeight = $width / $originalRatio;
                } elseif (!$height == null && (int)$height > 0) {
                    $newWidth = $height;
                    $newHeight = $height / $originalRatio;
                }
                // Height is largest size
            } else {
                if (!$height == null && (int)$height > 0) {
                    $newWidth = $height * $originalRatio;
                    $newHeight = $height;
                } elseif (!$width == null && (int)$width > 0) {
                    $newWidth = $width * $originalRatio;
                    $newHeight = $width;
                }
            }

            $return['width'] = (int)$newWidth;
            $return['height'] = (int)$newHeight;
        }

        return $return;

    }

    /**
     * Get the add to quote url by product id
     *
     * @param $productId
     * @return string
     * @throws \Mage_Core_Model_Store_Exception
     */
    public function getQuickQuoteProductUrl($productId)
    {
        return Mage::getUrl(
            'qquoteadv/index/quickquoteview',
            [
                "product" => $productId,
                '_secure'=> Mage::app()->getStore()->isCurrentlySecure()
            ]
        );
    }

    /**
     * Function that checks if a given child product can show an image instead of the parent product
     *
     * @param null $childProduct
     * @return bool
     */
    public function canShowImageOfChildProduct($childProduct = null)
    {
        if (!$childProduct || !$childProduct->getData('thumbnail')
            || ($childProduct->getData('thumbnail') == 'no_selection')
            || (Mage::getStoreConfig(
                    Mage_Checkout_Block_Cart_Item_Renderer_Configurable::CONFIGURABLE_PRODUCT_IMAGE
                ) == Mage_Checkout_Block_Cart_Item_Renderer_Configurable::USE_PARENT_IMAGE)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Function that returns the product object to get the image from
     *
     * @param $product
     * @param null|Mage_Catalog_Model_Product $childProduct
     * @return Mage_Catalog_Model_Product
     */
    public function getImageProduct($product, $childProduct = null)
    {
        //check if child product is available and not the same as product
        if (($childProduct === null) || $product->getId() == $childProduct->getId()) {
            return $product;
        }

        //make sure child product has an image and is allowed to be shown
        if (!$this->canShowImageOfChildProduct($childProduct)) {
            return $product;
        }

        //if everything is ok, return child product for the product image
        return $childProduct;
    }

    /**
     *  For configurable products,
     *  get configured simple product
     *
     * @param int $productQuoteId
     * @param null|\Mage_Catalog_Model_Product $product
     * @param null|\Ophirah_Qquoteadv_Model_Qqadvproductt $quoteadvProduct
     * @param bool $reload
     * @return Mage_Catalog_Model_Product
     */
    public function getConfChildProduct(
        $productQuoteId,
        $product = null,
        $quoteadvProduct = null,
        $reload = true
    ) {
        $returnProduct = null;
        $childProduct = null;

        //only load quoteadv product when it isn't given
        if ($quoteadvProduct == null) {
            $quoteadvProduct = unserialize(Mage::getModel('qquoteadv/qqadvproduct')
                ->load($productQuoteId)
                ->getAttribute()
            );
        } else {
            if (!is_array($quoteadvProduct)) {
                $quoteadvProduct = unserialize($quoteadvProduct->getAttribute());
            }
        }

        //only load product when it isn't given
        if ($product == null && isset($quoteadvProduct['product'])) {
            $product = Mage::getModel('catalog/product')->load($quoteadvProduct['product']);
        }
        $returnProduct = $product;

        //only load child product on configurable
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $childProduct = Mage::getModel('catalog/product_type_configurable')
                ->getProductByAttributes($quoteadvProduct['super_attribute'], $product);

            if ($childProduct != null) {
                $returnProduct = $childProduct;
            }
        }

        //only reload when required (to avoid using collection items with less data)
        if ($reload) {
            $returnProduct = Mage::getModel('catalog/product')->load($returnProduct->getId());
        }

        return $returnProduct;
    }
}