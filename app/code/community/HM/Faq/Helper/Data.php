<?php
/**
 * FAQ accordion for Magento

 */

/**
 * FAQ accordion for Magento
 *
 * Website: www.hiremagento.com 
 * Email: hiremagento@gmail.com
 */
class HM_Faq_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Returns config data
     * 
     * @param string $field Requested field
     * @return array config Configuration information
     */
    public function getConfigData($field)
    {
        $path = 'faq/config/' . $field;
        $config = Mage::getStoreConfig($path, Mage::app()->getStore());
        return $config;
    }
}
