<?php

/**
 * Class Shopworks_Billink_PaymentFormController
 */
class Shopworks_Billink_PaymentFormController extends Mage_Core_Controller_Front_Action
{
    /**
     * sets the payment form fields in session
     */
    public function setFormFieldsAction()
    {
        $params = $this->getRequest()->getParams();

        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');
        $session->setBillinkPaymentFormFields($params);

        //Output the result as Json
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode(array('success' => true)));
    }

}
