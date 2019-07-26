<?php

class TM_CheckoutSuccess_Block_Html_Bottom
    extends TM_CheckoutSuccess_Block_Template
{

    public function getOrder()
    {
        if (!$this->hasData('order')) {
            $order = Mage::getModel('sales/order')
                ->loadByIncrementId($this->getSuccessBlock()->getOrderId());
            $this->setData('order', $order);
        }

        return $this->getData('order');
    }

    public function getRecurringProfile()
    {
        if (!$this->hasData('recurring_profile')) {
            $recurringProfile = current(
                $this->getSuccessBlock()->getRecurringProfiles()
            );
            $this->setData('recurring_profile', $recurringProfile);
        }

        return $this->getData('recurring_profile');
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->getOrder()->getId()) {
            $processor = Mage::getModel('checkoutsuccess/html_filter_order')
                ->setSalesObject($this->getOrder());
        } else {
            $processor = Mage::getModel('checkoutsuccess/html_filter_recurring')
                ->setSalesObject($this->getRecurringProfile());
        }

        $bottomHtml = Mage::getStoreConfig('checkoutsuccess/general/bottom_html');
        return $processor->filter($bottomHtml);
    }

}
