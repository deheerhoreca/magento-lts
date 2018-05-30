<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Model_Command_Status extends Amasty_Oaction_Model_Command_Abstract
{ 
    public function __construct($type)
    {
        parent::__construct($type);
        $this->_label      = 'Change Status';
        $this->_fieldLabel = 'To';
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
            $orderCode = $order->getIncrementId();

            if (Mage::getStoreConfig('amoaction/status/check_state')) {
                $state = $order->getState();
                $statuses = Mage::getModel('sales/order_status')
                    ->getCollection()
                    ->addStateFilter($state)
                    ->toOptionHash();
                if (Mage::helper('core')->isModuleEnabled('Amasty_Orderstatus')) {
                    $customStatuses = Mage::getModel('amorderstatus/status')->getCollection();
                    $customStatuses->getSelect()
                        ->where('parent_state LIKE ?', '' . $state . ',%')
                        ->orWhere('parent_state LIKE ?', '%,' . $state . ',%')
                        ->orWhere('parent_state LIKE ?', '%,' . $state . '');
                    
                    foreach ($customStatuses as $customStatus) {
                        $statuses[$state . '_' .$customStatus->getAlias()] = $customStatus->getStatus();
                    }
                }
                
                if (!array_key_exists($val, $statuses)) {
                    $err = $hlp->__('Selected status does not correspond to the state of order.');
                    $this->_errors[] = $hlp->__(
                        'Can not update order #%s: %s', $orderCode, $err);
                    continue;
                }
            }

            try {
                $notify = Mage::getStoreConfig('amoaction/status/notify', $order->getStoreId());
                if (!$notify) {
                    $notify = parent::orderUpdateNotify($val);
                }
                Mage::getModel('sales/order_api')->addComment($orderCode, $val, '', $notify);
                ++$numAffectedOrders;          
            }
            catch (Exception $e) {
                if ('Mage_Api_Exception' == get_class($e)) {
                    $err = $e->getCustomMessage();
                } else {
                    $err = $e->getMessage();
                }
                $this->_errors[] = $hlp->__('Can not update order #%s: %s', $orderCode, $err);
            }
            $order = null;
            unset($order); 
        }
        
        if ($numAffectedOrders){
            $success = $hlp->__('Total of %d order(s) have been successfully updated.', 
                $numAffectedOrders);
        }         
        
        return $success; 
    }

    protected function _getValueField($title)
    {
        $field = array('amoaction_value' => array(
            'name'   => 'amoaction_value',
            'type'   => 'select',
            'class'  => 'required-entry',
            'label'  => $title,
            'values' => Mage::getModel('sales/order_config')->getStatuses(),
        )); 
        return $field;       
    }    
}