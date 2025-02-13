<?php

class Profitmetrics_MagentoIntegration_Block_Conversion_Booster_Snippet extends Mage_Core_Block_Template
{
    const XML_PATH_CONVERSION_BOOSTER_SNIPPET = 'profitmetrics/settings/conversion_booster_snippet';
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $lastRealOrder;

    /**
     * @var array
     */
    protected $conversionBoosterSnippet;

    /**
     * Get Last Real Order
     * @return Mage_Sales_Model_Order
     */
    private function getLastRealOrder()
    {
        if (!$this->lastRealOrder) {
            /** @var Mage_Checkout_Model_Session $session */
            $session = Mage::getSingleton('checkout/session');

            if ($lastRealOrderId = $session->getLastRealOrderId()) {
                $this->lastRealOrder = Mage::getModel('sales/order')->loadByIncrementId($lastRealOrderId);
            }
        }

        return $this->lastRealOrder;
    }

    /**
     * Get Order Currency
     * @return string
     */
    public function getOrderCurrency()
    {
        $order = $this->getLastRealOrder();

        if (!$order || !$order->getId()) {
            return '';
        }

        return $order->getOrderCurrencyCode();
    }

    /**
     * Get Value
     * return float
     */
    public function getOrderGrandTotal()
    {
        $order = $this->getLastRealOrder();

        if (!$order || !$order->getId()) {
            return '';
        }

        return $order->getGrandTotal();
    }

    /**
     * Get Order Value Modifier
     * @return float
     */
    public function getOrderValueModifier()
    {
        $snippet = $this->getConversionBoosterSnippet();

        return isset($snippet['orderValueModifier']) ? (float) $snippet['orderValueModifier'] : 0.0;
    }

    /**
     * Get Transaction Id
     * @return string
     */
    public function getTransactionId()
    {
        $order = $this->getlastRealOrder();

        if (!$order || !$order->getId()) {
            return '';
        }

        return $order->getIncrementId();
    }

    /**
     * Get Revenue Send To Snippets
     * @return string[]
     */
    public function getRevenueSendToSnippets()
    {
        $snippet = $this->getConversionBoosterSnippet();

        return isset($snippet['revenueSendToSnippets']) ? $snippet['revenueSendToSnippets'] : [];
    }

    /**
     * Get Profit Send To Snippets
     *
     * @return array
     */
    public function getProfitSendToSnippets()
    {
        $snippet = $this->getConversionBoosterSnippet();

        return isset($snippet['profitSendToSnippets']) ? $snippet['profitSendToSnippets'] : [];
    }

    /**
     * Get is Conversion Booster Configured
     *
     * @return bool
     */
    public function isConversionBoosterConfigured()
    {
        return count($this->getConversionBoosterSnippet()) >= 3;
    }


    /**
     * @return array
     */
    protected function getConversionBoosterSnippet()
    {
        if (!isset($this->conversionBoosterSnippet)) {
            $conversionBoosterSnippetJson = (string)Mage::getStoreConfig(self::XML_PATH_CONVERSION_BOOSTER_SNIPPET);

            if (!$conversionBoosterSnippetJson) {
                $this->conversionBoosterSnippet = [];

                return $this->conversionBoosterSnippet;
            }

            try {
                $this->conversionBoosterSnippet = Mage::helper('core')->jsonDecode($conversionBoosterSnippetJson);
            } catch (Zend_Json_Exception $exception) {
                $this->conversionBoosterSnippet = [];
            }
        }

        return $this->conversionBoosterSnippet;
    }
}