<?php

/**
 * Class Shopworks_Billink_Model_Event_Observer_Invoice
 */
class Shopworks_Billink_Model_Event_Observer_Invoice
{
    /**
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function createInvoice($observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();

        $isInvoiceEnabled = (boolean)Mage::getStoreConfig('payment/billink/create_invoice', $order->getStoreId());

        $isBillinkUsed = false;
        if($order->getPayment() && $order->getPayment()->getMethodInstance())
        {
            $isBillinkUsed = $order->getPayment()->getMethodInstance()->getCode() == Shopworks_Billink_Model_Payment_Method::PAYMENT_METHOD_BILLINK_CODE;
        }

        //Only create the invoice if billink is used for this order and creation of invoices is enabled
        if($order->canInvoice() && $isInvoiceEnabled && $isBillinkUsed)
        {
            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            $transactionSave->save();
        }
    }
}