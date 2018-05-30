<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */ 
class Amasty_Oaction_Model_Source_Carriers {

    public function toOptionArray($storeId = null)
    {
        $options = array();
        $options[] = array(
            'value' => 'custom',
            'label' => Mage::helper('amoaction')->__('Custom')
        );
        
        foreach (Mage::getSingleton('shipping/config')->getAllCarriers($storeId) as $k => $carrier) {
            if ($carrier->isTrackingAvailable()) {
                $options[] = array(
                    'value' => $k,
                    'label' => $carrier->getConfigData('title'),
                );
            }             
        }
        
        return $options;
    }
}