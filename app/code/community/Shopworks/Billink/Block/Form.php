<?php
/**
 * Class Shopworks_Billink_Block_Form
 */
class Shopworks_Billink_Block_Form extends Mage_Payment_Block_Form
{
    /**
     * @var Shopworks_Billink_Helper_AddressComparer
     */
    protected $_addressComparer;
    /**
     * @var Shopworks_Billink_Helper_Billink
     */
    protected $_helper;

    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('shopworks_billink/form.phtml');

        $this->_addressComparer = Mage::helper('billink/AddressComparer');
        $this->_helper = Mage::helper('billink/Billink');
    }

    /**
     * Indiciates if the delivery address fields should be displayed on the billink payment form
     * return bool
     */
    public function showDeliveryAddressFields()
    {
        //For the one page checkout, only shop the fields if the settings is enabled *and* the user has
        //set the flag sameAsBilling to false
        $isSameAsBilling = $this->getQuote()->getShippingAddress()->getSameAsBilling();
        return $this->_helper->isAlternateDeliveryAddressAllowed() && !$isSameAsBilling;
    }

    /**
     * @return bool
     */
    public function disableBillinkPaymentOption()
    {
        return (!$this->_helper->isAlternateDeliveryAddressAllowed() && !$this->isDeliveryAddressEqualToInvoiceAddress());
    }

    /**
     * @return string
     */
    public function getValidationErrorMessage()
    {
        $result = '';

        if(isset($_SESSION[Shopworks_Billink_Model_Payment_Method::VALIDATION_MESSAGE]))
        {
            $result = $_SESSION[Shopworks_Billink_Model_Payment_Method::VALIDATION_MESSAGE];
        }

        return $result;
    }

    /**
     * Only show the phone number field when customers are logged in. Customers that are not logged in can change their
     * phone number in the address fields for the checkout
     * @return bool
     */
    public function showPhoneNumberField()
    {
        return $this->getCustomerSession()->isLoggedIn();
    }

    /**
     * Get the phone number for the customer
     * @return string
     */
    public function getCustomerPhoneNumber()
    {
        //try to get the phonenumber from the session
        $phoneNumber = $this->getFormFieldsFormSession('billink_phone');

        //Else try to get the phone number from the user data
        if($phoneNumber == '' && $this->getCustomerSession()->isLoggedIn())
        {
            $defaultBillingAddress = $this->getCustomerSession()->getCustomer()->getDefaultBillingAddress();
            $quoteBillingAddress = $this->getQuote()->getBillingAddress();
            
            if($quoteBillingAddress && $quoteBillingAddress->getTelephone())
            {
                $phoneNumber = $quoteBillingAddress->getTelephone();
            }
            else if($defaultBillingAddress && $defaultBillingAddress->getTelephone())
            {
                $phoneNumber = $defaultBillingAddress->getTelephone();
            }
        }

        return $phoneNumber;
    }

    /**
     * @return int|null
     */
    public function getBirthDateDay()
    {
        $day = $this->getFormFieldsFormSession('billink_dob_day');
        if(!$day)
        {
            $day = $this->getBirthDateSegment(9,2);
        }
        return $day;
    }

    /**
     * @return int|null
     */
    public function getBirthDateMonth()
    {
        $month = $this->getFormFieldsFormSession('billink_dob_month');
        if(!$month)
        {
            $month =  $this->getBirthDateSegment(6,2);;
        }
        return $month;
    }

    /**
     * @return int|null
     */
    public function getBirthDateYear()
    {
        $year = $this->getFormFieldsFormSession('billink_dob_year');
        if(!$year)
        {
            $year =   $this->getBirthDateSegment(0,4);
        }
        return $year;
    }

    /**
     * Returns the billink form value from session
     * @param string $attribute
     * @return string
     */
    public function getFormFieldsFormSession($attribute)
    {
        $formFieldValue = '';
        
        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');
        $fields = $session->getBillinkPaymentFormFields();

        if(is_array($fields) && isset($fields['payment'][$attribute]))
        {
            $formFieldValue = $fields['payment'][$attribute];
        }
        
        return $formFieldValue;
    }

    /**
     * @param $start
     * @param $length
     * @return int|null
     */
    private function getBirthDateSegment($start, $length)
    {
        $part = 0;
        $birthDate = $this->getBirthDate();
        if($birthDate && strlen($birthDate) > $start + $length)
        {
            $part = (int)substr($birthDate, $start,$length);
        }
        return $part;
    }

    /**
     * @return null
     */
    private function getBirthDate()
    {
        $dob = null;

        if($this->getCustomerSession()->isLoggedIn())
        {
            $dob = $this->getCustomerSession()->getCustomer()->getDob();
        }

        return $dob;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    private function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * @return Mage_Customer_Model_Session
     */
    private function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * @return bool
     */
    private function isDeliveryAddressEqualToInvoiceAddress()
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $this->getQuote();
        $billAddress = $quote->getBillingAddress();
        $deliveryAddress = $quote->getShippingAddress();

        return $this->_addressComparer->areEqual($billAddress, $deliveryAddress);
    }
}