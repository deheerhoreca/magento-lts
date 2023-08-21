<?php
// DHH CORE HACK -- Partially patched to master (v20)
/**
 * OpenMage
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available at https://opensource.org/license/osl-3-0-php
 *
 * @category   Mage
 * @package    Mage_GoogleAnalytics
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://www.magento.com)
 * @copyright  Copyright (c) 2022 The OpenMage Contributors (https://www.openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * GoogleAnalitics Page Block
 *
 * @category   Mage
 * @package    Mage_GoogleAnalytics
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_GoogleAnalytics_Block_Ga extends Mage_Core_Block_Template
{
    /**
     * @deprecated after 1.4.1.1
     * @see self::_getOrdersTrackingCode()
     * @return string
     */
    public function getQuoteOrdersHtml()
    {
        return '';
    }

    /**
     * @deprecated after 1.4.1.1
     * self::_getOrdersTrackingCode()
     * @return string
     */
    public function getOrderHtml()
    {
        return '';
    }

    /**
     * @deprecated after 1.4.1.1
     * @see _toHtml()
     * @return string
     */
    public function getAccount()
    {
        return '';
    }

    /**
     * Get a specific page name (may be customized via layout)
     *
     * @return string
     */
    public function getPageName()
    {
        return $this->_getData('page_name') ?? '';
    }

    /**
     * Render regular page tracking javascript code
     * The custom "page name" may be set from layout or somewhere else. It must start from slash.
     *
     * @param string $accountId
     * @return string
     * @deprecated
     */
    protected function _getPageTrackingCodeUniversal($accountId)
    {
        return '';
    }

    /**
     * Render regular page tracking javascript code
     * The custom "page name" may be set from layout or somewhere else. It must start from slash.
     *
     * @link http://code.google.com/apis/analytics/docs/gaJS/gaJSApiBasicConfiguration.html#_gat.GA_Tracker_._trackPageview
     * @link http://code.google.com/apis/analytics/docs/gaJS/gaJSApi_gaq.html
     * @param string $accountId
     * @return string
     * @deprecated
     */
    protected function _getPageTrackingCodeAnalytics($accountId)
    {
        return '';
    }

    /**
     * Render regular page tracking javascript code
     * The custom "page name" may be set from layout or somewhere else. It must start from slash.
     *
     * @param string $accountId
     * @return string
     */
    protected function _getPageTrackingCode($accountId)
    {
        /** @var Mage_GoogleAnalytics_Helper_Data $helper */
        $helper = $this->helper('googleanalytics');
        if ($helper->isUseAnalytics4()) {
            return $this->_getPageTrackingCodeAnalytics4($accountId);
        }

        return '';
    }
    
    /**
     * Render regular page tracking javascript code
     *
     * @link https://developers.google.com/tag-platform/gtagjs/reference
     * @param string $accountId
     * @return string
     */
    protected function _getPageTrackingCodeAnalytics4($accountId)
    {
        $trackingCode = "
gtag('js', new Date());
";
        if (1 || !$this->helper('googleanalytics')->isDebugModeEnabled()) { // DHH CORE HACK
            $trackingCode .= "
gtag('config', '{$this->jsQuoteEscape($accountId)}');
";
        } else {
            $trackingCode .= "
gtag('config', '{$this->jsQuoteEscape($accountId)}', {'debug_mode':true});
";
        }

        //add user_id
        if (1 && Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $trackingCode.= "
gtag('set', 'user_id', '{$customer->getId()}');
";
        }

        if (0 && $this->helper('googleanalytics')->isDebugModeEnabled()) {
            $this->helper('googleanalytics')->log($trackingCode);
        }

        return $trackingCode;
    }

    /**
     * Render information about specified orders and their items
     *
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function _getOrdersTrackingCodeUniversal()
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }
        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('entity_id', ['in' => $orderIds]);
        $result = [];
        $result[] = "ga('require', 'ecommerce')";
        foreach ($collection as $order) {
            $result[] = sprintf(
                "ga('ecommerce:addTransaction', {
'id': '%s',
'affiliation': '%s',
'revenue': '%s',
'tax': '%s',
'shipping': '%s'
});",
                $order->getIncrementId(),
                $this->jsQuoteEscape(Mage::app()->getStore()->getFrontendName()),
                $order->getBaseGrandTotal(),
                $order->getBaseTaxAmount(),
                $order->getBaseShippingAmount()
            );
            foreach ($order->getAllVisibleItems() as $item) {
                $result[] = sprintf(
                    "ga('ecommerce:addItem', {
'id': '%s',
'sku': '%s',
'name': '%s',
'category': '%s',
'price': '%s',
'quantity': '%s'
});",
                    $order->getIncrementId(),
                    $this->jsQuoteEscape($item->getSku()),
                    $this->jsQuoteEscape($item->getName()),
                    null, // there is no "category" defined for the order item
                    $item->getBasePrice(),
                    $item->getQtyOrdered()
                );
            }
            $result[] = "ga('ecommerce:send');";
        }
        return implode("\n", $result);
    }

    /**
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function _getOrdersTrackingCodeAnalytics4()
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return '';
        }
        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('entity_id', ['in' => $orderIds]);
        $result = [];
        /** @var Mage_Sales_Model_Order $order */
        foreach ($collection as $order) {
            $orderData = [
                'currency' => $order->getBaseCurrencyCode(),
                'transaction_id' => $order->getIncrementId(),
                'value' => (double) number_format($order->getBaseGrandTotal(), 2, '.', ''),
                'coupon' => strtoupper((string)$order->getCouponCode()),
                'shipping' => (double) number_format($order->getBaseShippingAmount(), 2, '.', ''),
                'tax' => (double) number_format($order->getBaseTaxAmount(), 2, '.', ''),
                'items' => []
            ];

            /** @var Mage_Sales_Model_Order_Item $item */
            foreach ($order->getAllVisibleItems() as $item) {
                $_item = [
                    'item_id' => $item->getSku(),
                    'item_name' => $item->getName(),
                    'quantity' => (int) $item->getQtyOrdered(),
                    'price' => (double) number_format($item->getBasePrice(), 2, '.', ''),
                    'discount' => (double) number_format($item->getBaseDiscountAmount(), 2, '.', '')
                ];
                $_product = Mage::getModel('catalog/product')->load($item->getProductId());
                if ($_product->getAttributeText('manufacturer')) {
                    $_item['item_brand'] = $_product->getAttributeText('manufacturer');
                }
                
                array_push($orderData['items'], $_item);
            }
            $result[] = "gtag('event', 'purchase', " . json_encode($orderData, JSON_THROW_ON_ERROR) . ");";
        }
        return implode("\n", $result);
    }

    /**
     * Render information about specified orders and their items
     *
     * @link http://code.google.com/apis/analytics/docs/gaJS/gaJSApiEcommerce.html#_gat.GA_Tracker_._addTrans
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function _getOrdersTrackingCodeAnalytics()
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }
        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('entity_id', ['in' => $orderIds]);
        $result = [];
        foreach ($collection as $order) {
            if ($order->getIsVirtual()) {
                $address = $order->getBillingAddress();
            } else {
                $address = $order->getShippingAddress();
            }
            $result[] = sprintf(
                "_gaq.push(['_addTrans', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']);",
                $order->getIncrementId(),
                $this->jsQuoteEscape(Mage::app()->getStore()->getFrontendName()),
                $order->getBaseGrandTotal(),
                $order->getBaseTaxAmount(),
                $order->getBaseShippingAmount(),
                $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($address->getCity())),
                $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($address->getRegion())),
                $this->jsQuoteEscape(Mage::helper('core')->escapeHtml($address->getCountry()))
            );
            foreach ($order->getAllVisibleItems() as $item) {
                $result[] = sprintf(
                    "_gaq.push(['_addItem', '%s', '%s', '%s', '%s', '%s', '%s']);",
                    $order->getIncrementId(),
                    $this->jsQuoteEscape($item->getSku()),
                    $this->jsQuoteEscape($item->getName()),
                    null, // there is no "category" defined for the order item
                    $item->getBasePrice(),
                    $item->getQtyOrdered()
                );
            }
            $result[] = "_gaq.push(['_trackTrans']);";
        }
        return implode("\n", $result);
    }

    /**
     * Render IP anonymization code for page tracking javascript code
     *
     * @return string
     */
    protected function _getAnonymizationCode()
    {
        if (!Mage::helper('googleanalytics')->isIpAnonymizationEnabled()) {
            return '';
        }

        /** @var Mage_GoogleAnalytics_Helper_Data $helper */
        $helper = $this->helper('googleanalytics');
        if ($helper->isUseUniversalAnalytics()) {
            return $this->_getAnonymizationCodeUniversal();
        }

        return $this->_getAnonymizationCodeAnalytics();
    }

    /**
     * Render IP anonymization code for page tracking javascript universal analytics code
     *
     * @return string
     */
    protected function _getAnonymizationCodeUniversal()
    {
        return "ga('set', 'anonymizeIp', true);";
    }

    /**
     * Render IP anonymization code for page tracking javascript google analytics code
     *
     * @return string
     */
    protected function _getAnonymizationCodeAnalytics()
    {
        return "_gaq.push (['_gat._anonymizeIp']);";
    }

    /**
     * Is ga available
     *
     * @return bool
     */
    protected function _isAvailable()
    {
        return Mage::helper('googleanalytics')->isGoogleAnalyticsAvailable();
    }

    /**
     * Render GA tracking scripts
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_isAvailable()) {
            return '';
        }
        return parent::_toHtml();
    }
}