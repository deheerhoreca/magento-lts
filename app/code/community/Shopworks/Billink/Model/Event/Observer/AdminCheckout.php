<?php

class Shopworks_Billink_Model_Event_Observer_AdminCheckout
{
    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     * @throws Exception
     */
    public function submitAllAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();
        $method = $order->getPayment()->getMethod();

        if ($method != Shopworks_Billink_Model_Payment_Method::PAYMENT_METHOD_BILLINK_CODE)
        {
            return $this;
        }

        /** @var Shopworks_Billink_Model_Payment_Method $paymentInstance */
        $paymentInstance = $order->getPayment()->getMethodInstance();
        if($paymentInstance)
        {
            $paymentInstance->createPayment();
        }

        return $this;
    }
}

