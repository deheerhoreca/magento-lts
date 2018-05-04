<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */ 
class Amasty_Oaction_Model_Source_Commands
{
    public function toOptionArray()
    {
        $options = array();
        
        // magento wants at least one option to be selected
        $options[] = array(
            'value' => '',
            'label' => '',
            
        ); 
        $types = array('invoice', 'invoicecapture', 'invoiceship', 'invoicecaptureship', 'captureship', 'capture', 'ship', 'status', 'comment');
        foreach ($types as $type){
            $command = Amasty_Oaction_Model_Command_Abstract::factory($type);  
            $options[] = array(
                'value' => $type,
                'label' => Mage::helper('amoaction')->__($command->getLabel()),
                
            );
        }   
        return $options;
    }
}