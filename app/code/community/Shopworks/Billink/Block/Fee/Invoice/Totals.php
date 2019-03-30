<?php
class Shopworks_Billink_Block_Fee_Invoice_Totals extends Shopworks_Billink_Block_Fee_Abstract
{
    private $_invoice;

    /**
     * Add a total for the billink fee
     * 
     * @see app\design\adminhtml\default\default\layout\billink.xml
     * @see app\design\frontend\base\default\layout\billink.xml
     * @return Shopworks_Billink_Block_Fee_Order_Totals
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $display = (int)Mage::getStoreConfig('tax/sales_display/subtotal', Mage::app()->getStore()->getId());

        $this->_invoice = $parent->getInvoice();

        //When the Klarna module is used, this invoice cannot be extracted from the parent block. In that case we can
        //fetch it from the registry
        if(!$this->_invoice)
        {
            $this->_invoice = Mage::registry('current_invoice');
        }

        if ($this->_invoice->getBillinkFee() < 0.01)
        {
            return $this;
        }

        $feeObjExclTax = $this->getFeeObject($this->_invoice, false);
        $feeObjInclTax = $this->getFeeObject($this->_invoice, true);

        if ($display === Shopworks_Billink_Block_Fee_Abstract::DISPLAY_MODE_INCL_TAX)
        {
            $parent->addTotalBefore($feeObjInclTax, 'shipping');
        }
        elseif ($display === Shopworks_Billink_Block_Fee_Abstract::DISPLAY_MODE_EXCL_TAX)
        {
            $parent->addTotalBefore($feeObjExclTax, 'shipping');
        }
        else//display incl and excl
        {
            $parent->addTotalBefore($feeObjExclTax, 'shipping');
            $parent->addTotalBefore($feeObjInclTax, 'shipping');
        }

        return $this;
    }
}
