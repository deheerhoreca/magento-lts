<?php

class TM_CheckoutSuccess_Model_Html_Filter_Order
    extends TM_CheckoutSuccess_Model_Html_Filter_Abstract
{

    public function getOrder()
    {
        return $this->getSalesObject();
    }

    /**
     * External order ID directive
     *
     * @return string
     */
    public function orderIdDirective()
    {
        return $this->getOrder()->getIncrementId();
    }

    /**
     * Order amount directive
     *
     * @return float
     */
    public function orderAmountDirective()
    {
        return number_format($this->getOrder()->getGrandTotal(), 2);
    }

    /**
     * Order base amount directive
     *
     * @return float
     */
    public function orderBaseAmountDirective()
    {
        return number_format($this->getOrder()->getBaseGrandTotal(), 2);
    }

    public function currencyDirective()
    {
        return $this->getOrder()->getOrderCurrencyCode();
    }

    public function customerEmailDirective()
    {
        return $this->getOrder()->getCustomerEmail();
    }

    public function paymentCodeDirective()
    {
        return $this->getOrder()->getPayment()->getMethodInstance()->getCode();
    }

    public function paymentTitleDirective()
    {
        return $this->getOrder()->getPayment()->getMethodInstance()->getTitle();
    }

    public function shippingCodeDirective()
    {
        return $this->getOrder()->getShippingMethod();
    }

    public function shippingTitleDirective()
    {
        return $this->getOrder()->getShippingDescription();
    }

    /**
     * Order billing address directive
     *
     * @return string
     */
    public function orderBillingAddressDirective()
    {
        return $this->processAddressData(
            $this->getOrder()->getBillingAddress()->getData()
        );
    }
    /**
     * Order shiping address directive
     *
     * @return string
     */
    public function orderShippingAddressDirective()
    {
        return $this->processAddressData(
            $this->getOrder()->getShippingAddress()->getData()
        );
    }
}
