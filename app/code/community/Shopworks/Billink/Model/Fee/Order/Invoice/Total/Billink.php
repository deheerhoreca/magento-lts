<?php
class Shopworks_Billink_Model_Fee_Order_Invoice_Total_Billink
extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    
    /**
     * Collect invoice fee total
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return Mage_Sales_Model_Order_Invoice_Total_Abstract
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order = $invoice->getOrder();

        if ($order->getPayment()->getMethodInstance()->getCode() != Shopworks_Billink_Model_Payment_Method::PAYMENT_METHOD_BILLINK_CODE)
        {
            return $this;
        }

        if (!$order->getBillinkFee())
        {
            return $this;
        }

        $invoice->setBillinkFee($order->getBillinkFee());
        $invoice->setBaseBillinkFee($order->getBaseBillinkFee());
        $invoice->setBillinkFeeTax($order->getBillinkFeeTax());
        $invoice->setBaseBillinkFeeTax($order->getBaseBillinkFeeTax());
        $invoice->setBillinkFeeInclTax($order->getBillinkFeeInclTax());
        $invoice->setBaseBillinkFeeInclTax($order->getBaseBillinkFeeInclTax());

        $feeExclTax = $order->getBillinkFeeInclTax() - $order->getBillinkFeeTax();

        $invoice->setGrandTotal($invoice->getGrandTotal() + $feeExclTax);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $feeExclTax);

        return $this;
    }

}
