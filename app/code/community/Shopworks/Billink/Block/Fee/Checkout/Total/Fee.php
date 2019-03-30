<?php

class Shopworks_Billink_Block_Fee_Checkout_Total_Fee extends Mage_Checkout_Block_Total_Default
{
    const DISPLAY_MODE_EXCL = 1;
    const DISPLAY_MODE_INCL = 2;
    const DISPLAY_MODE_BOTH = 3;

    const DISPLAY_MODE_BILLINK_FEE_CONFIG_PATH = 'payment/billink/cart_display';

    /**
     * @var string
     */
    protected $_template = 'shopworks_billink/checkout/fee.phtml';

    /**
     * @return int
     */
    public function getDisplayMode()
    {
        $displayMode = (int) Mage::getStoreConfig(self::DISPLAY_MODE_BILLINK_FEE_CONFIG_PATH, $this->_store);
        return $displayMode;
    }

    /**
     * @param boolean $inclTax
     * @return string
     */
    public function getTaxLabel($inclTax = false)
    {
        $taxLabel = Mage::helper('tax')->getIncExcTaxLabel($inclTax);
        return $taxLabel;
    }

    /**
     * @param bool $inclTax
     * @return bool
     */
    public function getValue($inclTax = false)
    {
        $address = $this->getTotal()->getAddress();
        $feeValue = $address->getBillinkFeeInclTax();  //incl tax

        if (!$inclTax)
        {
            $feeValue = $feeValue - $address->getBillinkFeeTax();
        }

        return $feeValue;
    }
}