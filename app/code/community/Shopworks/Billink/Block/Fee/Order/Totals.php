<?php
class Shopworks_Billink_Block_Fee_Order_Totals extends Shopworks_Billink_Block_Fee_Abstract
{
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
        $this->_order = $parent->getOrder();

        if ($this->_order->getBillinkFee() < 0.01)
        {
            return $this;
        }

        $feeObjExclTax = $this->getFeeObject($this->_order, false);
        $feeObjInclTax = $this->getFeeObject($this->_order, true);

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
