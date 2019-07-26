<?php

class TM_CheckoutFields_Model_Order_Pdf_Shipment extends Mage_Sales_Model_Order_Pdf_Shipment
{
    /**
     * Draw additional checkout fields
     *
     * @param Zend_Pdf_Page $page
     * @return void
     */
    protected function _drawCheckoutFields(Zend_Pdf_Page $page, $order)
    {
        Mage::helper('checkoutfields/pdf')->drawFields($page, $order, $this);
    }

    /**
     * Wrapped to add additional checkout fields
     *
     * @param Zend_Pdf_Page $page
     * @param Mage_Sales_Model_Order $obj
     * @param bool $putOrderId
     */
    protected function insertOrder(&$page, $obj, $putOrderId = true)
    {
        parent::insertOrder($page, $obj, $putOrderId);

        if (!Mage::getStoreConfigFlag('checkoutfields/print/pdf_packingslip')) {
            return;
        }

        $this->_drawCheckoutFields($page, $obj);
    }
}
