<?php

class TM_FireCheckout_Helper_Layout extends Mage_Core_Helper_Abstract
{
    const CONFIG_PATH_ENABLED = 'firecheckout/design/smart_layout';

    const CONFIG_PATH_LAYOUT = 'firecheckout/general/layout';

    /**
     * @return null|Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    /**
     * Provide most suitable layout for better UX
     *
     * @return string
     */
    public function getLayout()
    {
        $layout = Mage::getStoreConfig(self::CONFIG_PATH_LAYOUT);

        if (!Mage::getStoreConfig(self::CONFIG_PATH_ENABLED)) {
            return $layout;
        }

        if (in_array($layout, array('col2-set'))) {
            $customer = $this->getCustomer();
            if ($customer && $customer->getAddresses()) {
                return 'col2-set-sticky';
            }
        }

        return $layout;
    }
}
