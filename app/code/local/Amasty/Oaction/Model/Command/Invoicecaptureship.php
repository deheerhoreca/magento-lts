<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Model_Command_Invoicecaptureship extends Amasty_Oaction_Model_Command_Invoicecapture
{ 
    public function __construct($type)
    {
        parent::__construct($type);
        $this->_label = 'Invoice > Capture > Ship';
    } 
        
    /**
     * Executes the command
     *
     * @param array $ids product ids
     * @param string $val field value
     * @return string success message if any
     */    
    public function execute($ids, $val)
    {
        $success = parent::execute($ids, $val);
        if ($success){
            $command = Amasty_Oaction_Model_Command_Abstract::factory('ship');
            $success .= '<br />' . $command->execute($ids, $val);
        }
        
        return $success; 
    }

    protected function _getDefault()
    {
        return (int)Mage::getStoreConfig('amoaction/invoice/notify')
            && (int)Mage::getStoreConfig('amoaction/capture/notify')
            && (int)Mage::getStoreConfig('amoaction/ship/notify');
    }
}