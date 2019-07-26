<?php

class TM_FireCheckout_Helper_Url extends Mage_Checkout_Helper_Url
{
    /**
     * Retrieve checkout url
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        $helper = Mage::helper('firecheckout');
        if ($helper->canFireCheckout()) {
            return $helper->getFirecheckoutUrl();
        }
        return parent::getCheckoutUrl();
    }
}
