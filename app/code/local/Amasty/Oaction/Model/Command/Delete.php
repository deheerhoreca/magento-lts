<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Model_Command_Delete extends Amasty_Oaction_Model_Command_Abstract
{ 
    public function __construct($type)
    {
        parent::__construct($type);
        $this->_label = 'Delete';
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
        
        $numAffectedOrders = 0;

        $hlp = Mage::helper('amoaction'); 
        
        foreach ($ids as $id){
            $order = Mage::getModel('sales/order')->load($id);
            try {
                $order->delete(); 
                ++$numAffectedOrders;          
            }
            catch (Exception $e) {
                $err = $e->getCustomMessage() ? $e->getCustomMessage() : $e->getMessage();
                $this->_errors[] = $hlp->__(
                    'Can not delete order #%s: %s', $order->getIncrementId(), $err);
            }
            $order = null;
            unset($order); 
        }
        
        if ($numAffectedOrders){
            $success = $hlp->__('Total of %d order(s) have been successfully deleted.', 
                $numAffectedOrders);
        }         
        
        return $success; 
    }

    
}