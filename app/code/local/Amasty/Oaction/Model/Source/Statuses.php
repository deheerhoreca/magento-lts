<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */ 
class Amasty_Oaction_Model_Source_Statuses {

    public function toOptionArray() 
    {
        $options = array();
        $options[] = array(
            'value' => '',
            'label' => Mage::helper('amoaction')->__('Magento Default')
        );
        
        foreach (Mage::getModel('sales/order_config')->getStatuses() as $k => $v) {
            $options[] = array(
                'value' => $k,
                'label' => $v
            );
        }
        
        return $options;
    }

}
