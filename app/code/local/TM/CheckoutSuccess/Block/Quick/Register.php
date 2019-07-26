<?php

class TM_CheckoutSuccess_Block_Quick_Register
    extends TM_CheckoutSuccess_Block_Template
{

    public function getPostActionUrl()
    {
        return $this->helper('checkoutsuccess')->getQuickRegisterUrl();
    }

    public function existCustomerForEmailInOrder()
    {
        $block = $this->getSuccessBlock();
        if (!$block) {
            return false;
        }

        $order = Mage::getModel('sales/order')
            ->loadByIncrementId($block->getOrderId());
        if (!$order->getId()) {
            // order not found; perhaps this is recurring payment
            return true;
        }

        if (!$order->getCustomerEmail()) {
            return false;
        }

        $customer = Mage::getModel('customer/customer');
        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        $customer->loadByEmail($order->getCustomerEmail());
        if (!$customer->getId()) {
            return false;
        }

        return true;
    }

    public function _toHtml()
    {
        if (!$this->existCustomerForEmailInOrder()) {
            return parent::_toHtml();
        }
    }

}
