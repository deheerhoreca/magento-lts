<?php

/**
 * Class Shopworks_Billink_AddressParserController
 */
class Shopworks_Billink_AddressParserController extends Mage_Core_Controller_Front_Action
{
    const BILLING_ADDRESS_TYPE = 'billing';
    const SHIPPING_ADDRESS_TYPE = 'shipping';

    /**
     * Returns the current selected address in Json Format
     */
    public function getAddressAction()
    {
        $type = $this->getRequest()->getParam('type');
        $address = $this->getRequest()->getParam('address');

        //If there is not address parameter, than get the address from the quote
        if(is_null($address))
        {
            if($type == self::BILLING_ADDRESS_TYPE)
            {
                $address = $this->getBillingAddressFromQuote();
            }
            else if($type == self::SHIPPING_ADDRESS_TYPE)
            {
                $address = $this->getShippingAddressFromQuote();
            }
            else
            {
                throw new Exception('Unknown type value');
            }
        }
 
        /** @var Shopworks_Billink_Helper_AddressParser $helper */
        $helper = Mage::helper('billink/AddressParser');
        $result = $helper->parse($address);

        //If the address cannot be parsed, return the entire address as streetname
        if($result->streetName == '')
        {
            $result = new Shopworks_Billink_Helper_AddressParserOutput();
            $result->streetName = $address;
        }

        //Output the result as Json
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($result));
    }

    /**
     * @return string
     */
    private function getBillingAddressFromQuote()
    {
        $address = $this->getQuote()->getBillingAddress();

        if(!is_null($address))
        {
            return $address->getStreetFull();
        }
        else
        {
            return '';
        }
    }

    /**
     * @return string
     */
    private function getShippingAddressFromQuote()
    {
        $address = $this->getQuote()->getShippingAddress();

        if(!is_null($address))
        {
            return $address->getStreetFull();
        }
        else
        {
            return '';
        }
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    private function getQuote()
    {
        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');
        /** @var Mage_Sales_Model_Quote $checkout */
        $quote = $session->getQuote();
        return $quote;
    }
}