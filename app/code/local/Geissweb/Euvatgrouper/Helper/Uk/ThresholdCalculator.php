<?php

/**
 * Calculate UK VAT Threshold
 */
class Geissweb_Euvatgrouper_Helper_Uk_ThresholdCalculator extends Mage_Core_Helper_Abstract
{

    /**
     * @var Mage_Sales_Model_Quote|null
     */
    public $currentQuote;

    /**
     * @var Mage_Directory_Helper_Data
     */
    public $priceCurrency;

    /**
     * @var string
     */
    public $currentStoreCurrency;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->currentQuote         = Mage::getModel('checkout/session')->getQuote();
        $this->priceCurrency        = Mage::helper('directory');
        $this->currentStoreCurrency = Mage::app()->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * @return bool
     */
    public function isDeliveryToUk()
    {
        if ($this->currentQuote !== null) {
            if (!$this->currentQuote->getIsVirtual()) {
                $basedOnAddress = $this->currentQuote->getShippingAddress();
                Mage::log("UkThresholdCalculator country is " . $basedOnAddress->getCountryId(), null, 'euvatenhanced.log');
                return $basedOnAddress->getCountryId() === 'GB';
            }
        }

        return false;
    }

    /**
     * @param int|float $threshold
     *
     * @return bool
     */
    public function isCurrentCartAbove($threshold)
    {
        if ($this->currentQuote !== null) {
            return $this->getPriceInGBP($this->currentQuote->getSubtotalWithDiscount()) > $threshold;
        }

        return false;
    }

    /**
     * @param float $price
     *
     * @return float
     */
    public function getPriceInGBP($price)
    {
        if ($this->isNeedToConvert()) {
            $gbp        = Mage::getModel('directory/currency')->load('GBP');
            $priceInGBP = $this->priceCurrency->currencyConvert($price, $this->currentStoreCurrency, $gbp);
            Mage::log("UkThresholdCalculator converted $price from $this->currentStoreCurrency in GBP: $priceInGBP", null, 'euvatenhanced.log');
            return $priceInGBP;
        }

        Mage::log("UkThresholdCalculator price is $price", null, 'euvatenhanced.log');
        return $price;
    }

    /**
     * @return bool
     */
    private function isNeedToConvert()
    {
        return $this->currentStoreCurrency !== 'GBP';
    }
}
