<?php

class TM_CheckoutSuccess_Model_Html_Filter_Recurring
    extends TM_CheckoutSuccess_Model_Html_Filter_Abstract
{

    public function getRecurring()
    {
        return $this->getSalesObject();
    }

    /**
     * External recurring profile ID directive
     *
     * @return string
     */
    public function orderIdDirective()
    {
        return $this->getRecurring()->getReferenceId();
    }

    /**
     * Order amount directive
     *
     * @return float
     */
    public function orderAmountDirective()
    {
        return number_format($this->getRecurring()->getInitAmount(), 2);
    }

    public function currencyDirective()
    {
        return $this->getRecurring()->getCurrencyCode();
    }

    public function paymentCodeDirective()
    {
        return $this->getRecurring()->getMethodCode();
    }

    public function paymentTitleDirective()
    {
        return Mage::helper('payment')
            ->getMethodInstance($this->paymentCodeDirective())
            ->getTitle();
    }

    /**
     * Order billing address directive
     *
     * @return string
     */
    public function orderBillingAddressDirective()
    {
        $addressData = unserialize(
            $this->getRecurring()->getData('billing_address_info')
        );
        return $this->processAddressData($addressData);
    }
}
