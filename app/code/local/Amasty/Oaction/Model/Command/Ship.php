<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Model_Command_Ship extends Amasty_Oaction_Model_Command_Abstract
{ 
    public function __construct($type)
    {
        parent::__construct($type);
        $this->_label      = 'Ship';
        $this->_fieldLabel = 'Notify Customer';
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
        $comment = $hlp->__('Shipment created');
        $carriers = $this->_getPost('carrier');
        $numbers  = $this->_getPost('tracking');
        $titles   = array();
        $notifyCustomer = $val;

        if (Mage::getStoreConfig('amoaction/ship/comment')){
            $titles = $this->_getPost('comment');
        }
        
        foreach ($ids as $id){
            $order     = Mage::getModel('sales/order')->load($id);
            $orderCode = $order->getIncrementId();
            
            try {
                $shipmentCode = Mage::getModel('sales/order_shipment_api_v2')
                    ->create($orderCode, array(), $comment, false, false); 
                    
                //add tracking if any
                if ($shipmentCode && !empty($carriers[$id]) && !empty($numbers[$id])) {
                    if (!Mage::getStoreConfig('amoaction/ship/comment')){
                        $titles[$id] = $this->_getTitleByCode($carriers[$id], $order->getStoreId());
                    } elseif ('' == $titles[$id]
                        && Mage::getStoreConfig('amoaction/ship/force_title')) {
                        $titles[$id] = $this->_getTitleByCode($carriers[$id], $order->getStoreId());
                    }
                    Mage::getModel('sales/order_shipment_api_v2')->addTrack(
                        $shipmentCode,                                                           
                        $carriers[$id],                                                        
                        $titles[$id],         
                        $numbers[$id]                                                      
                    );                        
                }                    
                
                //update status    
                $status = Mage::getStoreConfig('amoaction/ship/status', $order->getStoreId());    
                if ($status) {
                    $notify = parent::orderUpdateNotify($status);
                    Mage::getModel('sales/order_api')->addComment($orderCode, $status, '', $notify);
                }

                if ($shipmentCode && $notifyCustomer){ 
                    $shipment = Mage::getModel('sales/order_shipment')
                        ->loadByIncrementId($shipmentCode);
                        
                    if ($shipment->getId()) {
                        $shipment
                            ->setEmailSent(true)
                            ->sendEmail(true)
                            ->save();
                    }
                    $shipment = null;
                    unset($shipment);
                } 
                
                ++$numAffectedOrders;           
            } catch (Exception $e) {
                if ('Mage_Api_Exception' == get_class($e)) {
                    $err = $e->getCustomMessage();
                } else {
                    $err = $e->getMessage();
                }
                $this->_errors[] = $hlp->__('Can not ship order #%s: %s', $orderCode, $err);
            }
            $order = null;
            unset($order); 
        }
        
        if ($numAffectedOrders){
            $success = $hlp->__('Total of %d order(s) have been successfully shipped.', 
                $numAffectedOrders);
        }         
        
        return $success; 
    }
    
    
    private function _getPost($field)
    {
        $data = array();
        $post = explode(',', Mage::app()->getRequest()->getPost($field));
        foreach ($post as $line) {
            if (false !== strpos($line, '|')) {
                list($id, $code) = explode('|', $line);
                $data[$id] = $code;
            }
        }
        return $data;
    }
   
    private function _getTitleByCode($code, $storeId)
    {
        if ($code == 'custom') {
            $title = Mage::getStoreConfig('amoaction/ship/title', $storeId);
        } else {
            $carriers = Mage::getModel('amoaction/source_carriers')->toOptionArray($storeId);
            foreach ($carriers as $carrier) {
                if ($code == $carrier['value']) {
                    $title = $carrier['label'];
                    break;
                }
            }
        }
        if (!$title)
            $title = $code;
            
        return $title;
    }
}