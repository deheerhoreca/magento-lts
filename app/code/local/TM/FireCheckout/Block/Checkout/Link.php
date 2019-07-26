<?php

class TM_FireCheckout_Block_Checkout_Link extends Mage_Core_Block_Template
{
    public function getCheckoutUrl()
    {
        return $this->helper('firecheckout')->getFirecheckoutUrl();
    }

    public function isDisabled()
    {
        return !Mage::getSingleton('checkout/session')->getQuote()->validateMinimumAmount();
    }

    public function isPossibleFireCheckout()
    {
        return $this->helper('firecheckout')->canFireCheckout();
    }
}
