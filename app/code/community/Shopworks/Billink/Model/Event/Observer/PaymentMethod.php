<?php

/**
 * Class Shopworks_Billink_Model_Event_Observer_PaymentMethod
 */
class Shopworks_Billink_Model_Event_Observer_PaymentMethod
{
    const BILLINK_PAYMENT_METHOD_CODE = 'billink';
    const NOT_LOGGED_IN_GROUP_ID = 0;

    /**
     * @param Varien_Event_Observer $observer
     * @return void
     * Event location: \Mage_Payment_Model_Method_Abstract::isAvailable
     * @throws Mage_Core_Model_Store_Exception
     */
    public function setPaymentMethod($observer)
    {
        if (Mage::app()->getStore()->isAdmin())
        {
            return;
        }

        $excludedCustomerGroups = $this->getExcludedCustomerGroups();
        $customerGroupId = $this->getCustomerGroupId();

        // Event fires for every individual payment method, values are therefore changed
        $paymentMethod = $observer->getEvent()->getMethodInstance();
        $paymentResult = $observer->getEvent()->getResult();

        if ($paymentMethod->getCode() == self::BILLINK_PAYMENT_METHOD_CODE)
        {
            if ($excludedCustomerGroups && in_array($customerGroupId, $excludedCustomerGroups))
            {
                $paymentResult->isAvailable = false;
            }
        }
    }

    /**
     * @return array|bool
     */
    protected function getExcludedCustomerGroups()
    {
        /** @var Shopworks_Billink_Helper_Config $billinkConfig */
        $billinkConfig = Mage::helper('billink/Config');

        // Returns a comma seperated list (magento backedn type = multiselect)
        $excludedCustomerGroups = $billinkConfig->getExcludedCustomerGroups();
        $excludedCustomerGroups = $excludedCustomerGroups == ''
            ? false
            : explode(',', $billinkConfig->getExcludedCustomerGroups());

        return $excludedCustomerGroups;
    }

    /**
     * @return int
     */
    protected function getCustomerGroupId()
    {
        /** @var Mage_Customer_Helper_Data $customerHelper */
        $customerHelper = Mage::helper('customer');

        // When getCustomer()->getGroupId() is not set, it sets it to the default group id (which can be anything).
        // Here we set it to the NOT_LOGGED_IN_GROUP_ID which is always 0
        $customerGroupId = $customerHelper->isLoggedIn()
            ? $customerHelper->getCustomer()->getGroupId()
            : self::NOT_LOGGED_IN_GROUP_ID;

        return $customerGroupId;
    }
}
