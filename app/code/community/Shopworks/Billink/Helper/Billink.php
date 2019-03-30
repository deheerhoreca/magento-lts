<?php

/**
 * Class Shopworks_Billink_Helper_Billink
 */
class Shopworks_Billink_Helper_Billink
{
    /**
     * Determine if the Billink module is used for the current Quote{
     * @return bool
     */
    public function isBillinkUsedForCheckout()
    {
        $billinkIsUsed = false;

        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');
        $quote = $session->getQuote();

        if(!is_null($quote) && !is_null($quote->getPayment()))
        {
            $usedPaymentMethodCode = $quote->getPayment()->getData('method');
            $billinkIsUsed = ($usedPaymentMethodCode == Shopworks_Billink_Model_Payment_Method::PAYMENT_METHOD_BILLINK_CODE);
        }

        return $billinkIsUsed;
    }

    /**
     * @params return if this module can be used
     */
    public function isReadyToUse()
    {
        $isActive = Mage::getStoreConfig('payment/billink/active');
        $isReady = $isActive && $this->isBillinkAuthenticated();
        
        return $isReady;
    }

    /**
     * Returns if the Billink module is authenticated
     */
    public function isBillinkAuthenticated()
    {
        $username = Mage::getStoreConfig('payment/billink/billink_name');
        $id = Mage::getStoreConfig('payment/billink/billink_id');
        
        $isBillinkAuthenticated = !(empty($username) && !empty($id));
        return $isBillinkAuthenticated;
    }

    /**
     * Create an instance of the Billink service object
     * @param null $storeId
     * @return Shopworks_Billink_Model_Service
     */
    public function getService($storeId = null)
    {
        /** @var Shopworks_Billink_Model_Service $service */
        $service = Mage::getModel('billink/service');
        $service->init(
            Mage::getStoreConfig('payment/billink/billink_name', $storeId),
            Mage::getStoreConfig('payment/billink/billink_id', $storeId),
            $this->isInTestMode($storeId)
        );

        return $service;
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isInTestMode($storeId = null)
    {
        return (bool)Mage::getStoreConfig('payment/billink/enable_testmode', $storeId);
    }

    /**
     * @return bool
     */
    public function isAlternateDeliveryAddressAllowed()
    {
        return (bool)Mage::getStoreConfig('payment/billink/is_alternate_delivery_address_allowed');
    }
}