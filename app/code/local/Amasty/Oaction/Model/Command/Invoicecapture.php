<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Model_Command_Invoicecapture extends Amasty_Oaction_Model_Command_Invoice
{ 
    public function __construct($type)
    {
        parent::__construct($type);
        $this->_label = 'Invoice > Capture';
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
            $command = Amasty_Oaction_Model_Command_Abstract::factory('capture');
            $success .= '<br />' . $command->execute($ids, $val);
        }
        
        return $success; 
    }

    protected function _getDefault()
    {
        return (int)Mage::getStoreConfig('amoaction/invoice/notify')
            && (int)Mage::getStoreConfig('amoaction/capture/notify');
    }
}