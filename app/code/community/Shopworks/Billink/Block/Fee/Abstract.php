<?php

abstract class Shopworks_Billink_Block_Fee_Abstract extends Mage_Core_Block_Abstract
{
    const DISPLAY_MODE_EXCL_TAX = 1;
    const DISPLAY_MODE_INCL_TAX = 2;
    const DISPLAY_MODE_BOTH = 3;

    /**
     * @param Mage_Sales_Model_Order $order
     * @param bool $inclTax
     * @return Varien_Object
     */
    protected function getFeeObject($order, $inclTax)
    {
        $feeLabel = Mage::getStoreConfig('payment/billink/fee_label');
        $billinkFeeObj = new Varien_Object();


        if($inclTax)
        {
            $billinkFeeObj->setLabel( $feeLabel . Mage::helper('billink')->__(' (Incl. Tax)'));
            $billinkFeeObj->setValue($order->getBillinkFeeInclTax());
            $billinkFeeObj->setBaseValue($order->getBaseBillinkFeeInclTax());
            $billinkFeeObj->setCode(Shopworks_Billink_Model_Payment_Method::TOTAL_FEE_CODE_INCL_TAX);
        }
        else
        {
            $billinkFeeExclTax = $order->getData('billink_fee_incl_tax') - $order->getData('billink_fee_tax');

            $billinkFeeObj->setLabel( $feeLabel . Mage::helper('billink')->__(' (Excl. Tax)'));
            $billinkFeeObj->setValue($billinkFeeExclTax);
            $billinkFeeObj->setBaseValue($billinkFeeExclTax);
            $billinkFeeObj->setCode(Shopworks_Billink_Model_Payment_Method::TOTAL_FEE_CODE);
        }

        return $billinkFeeObj;
    }
}