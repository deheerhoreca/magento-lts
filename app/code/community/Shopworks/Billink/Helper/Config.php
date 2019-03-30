<?php

/**
 * Class Shopworks_Billink_Helper_Config
 * Interface for Billink config settings
 */
class Shopworks_Billink_Helper_Config extends Mage_Core_Helper_Abstract
{
    /**
     * @param bool $isCompany
     * @return string
     */
    public function getWorkflowNumber($isCompany)
    {
        if($isCompany)
        {
            $workflowNumber = Mage::getStoreConfig('payment/billink/billink_workflow_number_business');
        }
        else
        {
            $workflowNumber = Mage::getStoreConfig('payment/billink/billink_workflow_number_personal');
        }
        return $workflowNumber;
    }

    /**
     * @return bool
     */
    public function isBillinkDisabledForHighOrderAmounts()
    {
        return (bool)Mage::getStoreConfig('payment/billink/maximum_amount_limit_enabled');
    }

    /**
     * @return float
     */
    public function getMaximumAmountLimit()
    {
        return  (float)Mage::getStoreConfig('payment/billink/maximum_amount_limit');
    }

    /**
     * @return string
     */
    public function getBackdoorValue()
    {
        return Mage::getStoreConfig('payment/billink/check_backdoor_value');
    }

    /**
     * @return bool
     */
    public function isTotalCheckEnabled()
    {
        return (bool)Mage::getStoreConfig('payment/billink/enable_total_check');
    }

    /**
     * @return string
     */
    public function getOrderStatusAfterPayment()
    {
        return Mage::getStoreConfig('payment/billink/order_status');
    }

    /**
     * @return string (multiselect, comma seperated string)
     */
    public function getExcludedCustomerGroups()
    {
        return Mage::getStoreConfig('payment/billink/disable_customer_group');
    }
}