<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Model_Command_Invoice extends Amasty_Oaction_Model_Command_Abstract
{ 
    protected $_pdf = null;
    
    public function __construct($type)
    {
        parent::__construct($type);
        $this->_label      = 'Invoice';
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
        $comment = $hlp->__('Invoice created');
        
        foreach ($ids as $id){
            $order     = Mage::getModel('sales/order')->load($id);
            $orderCode = $order->getIncrementId();
            
            try {
                $invoiceCode = Mage::getModel('sales/order_invoice_api_v2')
                    ->create($orderCode, array(), $comment, false, false); 
                    
                $status = Mage::getStoreConfig('amoaction/invoice/status', $order->getStoreId());
                if ($status) {
                    $notify = parent::orderUpdateNotify($status);
                    Mage::getModel('sales/order_api')->addComment($orderCode, $status, '', $notify);
                }

                $invoice = null;       
                if ($invoiceCode && $notifyCustomer){
                    $invoice = Mage::getModel('sales/order_invoice')
                        ->loadByIncrementId($invoiceCode);
                        
                    if ($invoice->getId()) {
                        $invoice
                            ->setEmailSent(true)
                            ->sendEmail(true)
                            ->save();
                    }
                }
                
                $print = Mage::getStoreConfig('amoaction/invoice/print', $order->getStoreId());
                if ($invoiceCode && $print){
                    if (!$invoice){
                        $invoice = Mage::getModel('sales/order_invoice')
                            ->loadByIncrementId($invoiceCode);
                    }
                    
                    if (!isset($this->_pdf)){
                        $this->_pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(array($invoice));
                    } else {
                        $tmp = Mage::getModel('sales/order_pdf_invoice')->getPdf(array($invoice));
                        $this->_pdf->pages = array_merge ($this->_pdf->pages, $tmp->pages);
                    }
                    
                }
                ++$numAffectedOrders; 
            }
            catch (Exception $e) {
                if ('Mage_Api_Exception' == get_class($e)) {
                    $err = $e->getCustomMessage();
                } else {
                    $err = $e->getMessage();
                }
                $this->_errors[] = $hlp->__('Can not invoice order #%s: %s', $orderCode, $err);
            }
            $order = null;
            unset($order); 
        }
        
        if ($numAffectedOrders){
            $success = $hlp->__('Total of %d order(s) have been successfully invoiced.', 
                $numAffectedOrders);
        }         
        
        return $success; 
    }
    
    public function hasResponse()
    {
        return !empty($this->_pdf);
    } 
    
    public function getResponseName()
    {
        return 'invoices_' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') . '.pdf';
    } 
    
    public function getResponseBody()
    {
        return $this->_pdf->render();
    }    
}