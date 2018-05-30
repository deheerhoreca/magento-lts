<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Model_Command_Capture extends Amasty_Oaction_Model_Command_Abstract
{ 
    public function __construct($type)
    {
        parent::__construct($type);
        $this->_label      = 'Capture';
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
        $notifyCustomer = $val;

        $hlp = Mage::helper('amoaction'); 
        $comment = $hlp->__('Invoice captured');
        
        foreach ($ids as $id){
            $order     = Mage::getModel('sales/order')->load($id);
            $orderCode = $order->getIncrementId();
            
            try {
                $allInvoices = $order->getInvoiceCollection();
                if (!count($allInvoices)){
                    $this->_errors[] = $hlp->__('Order #%s has no invoices', $orderCode);
                    continue;                    
                }
                
                foreach ($allInvoices as $invoice) {
                    $invoiceCode = $invoice->getIncrementId();
                    if (!$invoice->canCapture()){
                        $this->_errors[] = $hlp->__('Can not capture invoice #%s', $invoiceCode);
                        continue;
                    }
                    
                    //BEGIN modification: For compatibility with Klarna
                    if ('true' == (string)Mage::getConfig()->getNode('modules/Klarna_KlarnaPaymentModule/active')) {
                        $forRequest = array();
                        foreach ($invoice->getAllItems() as $item) {
                            if (!array_key_exists($item->getOrderItemId(), $forRequest)) {
                                $forRequest[$item->getOrderItemId()] = $item->getQty();
                            }
                        }
                        $invoiceItems = array('items' => $forRequest);
                        if (Mage::app()->getRequest()->hasInvoice()) {
                            $invoiceData = Mage::app()->getRequest()->getInvoice();
                            Mage::app()->getRequest()->setInvoice(array_merge($invoiceData, $invoiceItems));
                        } else {
                            Mage::app()->getRequest()->setInvoice($invoiceItems);
                        }
                    }
                    //END modification: For compatibility with Klarna
                    
                    $isOk = Mage::getModel('sales/order_invoice_api_v2')
                        ->capture($invoiceCode);   
                    
                    if ($isOk && $notifyCustomer){   
                        Mage::getModel('sales/order_invoice_api_v2')
                            ->addComment($invoiceCode, $comment, true, true);                        
                    }
                    
                    //BEGIN modification: For compatibility with Klarna
                    if ('true' == (string)Mage::getConfig()->getNode('modules/Klarna_KlarnaPaymentModule/active')) {
                        $invoiceItems = array('items' => array());
                        if (Mage::app()->getRequest()->hasInvoice()) {
                            $invoiceData = Mage::app()->getRequest()->getInvoice();
                            Mage::app()->getRequest()->setInvoice(array_merge($invoiceData, $invoiceItems));
                        } else {
                            Mage::app()->getRequest()->setInvoice($invoiceItems);
                        }
                    }
                    //END modification: For compatibility with Klarna
                }
                
                //update status    
                $status = Mage::getStoreConfig('amoaction/capture/status', $order->getStoreId());    
                if ($status) {
                    $notify = parent::orderUpdateNotify($status);
                    Mage::getModel('sales/order_api')->addComment($orderCode, $status, '', $notify);
                }
                
                ++$numAffectedOrders;
            }
            catch (Exception $e) {
                if ('Mage_Api_Exception' == get_class($e)) {
                    $err = $e->getCustomMessage();
                } else {
                    $err = $e->getMessage();
                }
                $this->_errors[] = $hlp->__('Can not capture invoice for order #%s: %s', $orderCode, $err);
            }
            $order = null;
            unset($order); 
        }
        
        if ($numAffectedOrders){
            $success = $hlp->__('Total of %d order(s) have been successfully captured.', 
                $numAffectedOrders);
        }         
        
        return $success; 
    }
}