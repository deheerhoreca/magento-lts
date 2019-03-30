<?php

/**
 * Class Shopworks_Billink_Model_Billink
 */
class Shopworks_Billink_Model_Payment_Method extends Mage_Payment_Model_Method_Abstract
{
    const TOTAL_FEE_CODE = 'billink_fee';
    const TOTAL_FEE_CODE_INCL_TAX = 'billink_fee_incl_tax';
    const PAYMENT_METHOD_BILLINK_CODE = "billink";
    const PAYMENT_METHOD_DISCOUNT_CODE = "discount";

    //Validation errors are stored in the session. This way they can be used
    //with the OSC checkout. Because OSC ignores the message in the payment exception
    const VALIDATION_MESSAGE = 'billink_validation_message';

    //Session keys
    //If you add keys here, don't forget to add them to the 'cleanSessionData' method
    const CHECK_UUID_SESSION_INDEX = 'billink_check_uuid';
    const CUSTOMER_TYPE_SESSION_INDEX = 'billink_customer_type';
    const BIRTHDATE_SESSION_INDEX = 'bililnk_birthdate';
    const CHAMBER_OF_COMMERCE_SESSION_INDEX = 'billink_chamber_of_commerce';
    const SEX_SESSION_INDEX = 'billink_sex';
    const STREET_SESSION_INDEX = 'billink_street';
    const PHONE_SESSION_INDEX = 'billink_phone';
    const HOUSENUMBER_SESSION_INDEX = 'billink_housenumber';
    const HOUSENUMBER_EXTENSION_SESSION_INDEX = 'billink_housenumber_extensions';
    const DELIVERY_ADDRESS_STREET_SESSION_INDEX = 'billink_delivery_address_street';
    const DELIVERY_ADDRESS_HOUSENUMBER_SESSION_INDEX = 'billink_delivery_address_housenumber';
    const DELIVERY_ADDRESS_HOUSENUMBER_EXTENSION_SESSION_INDEX = 'billink_delivery_address_housenumber_extensions';

    protected $_code = self::PAYMENT_METHOD_BILLINK_CODE;

    protected $_formBlockType = 'billink/form';
    protected $_infoBlockType = 'billink/info';

    /**
     * Is this payment method a gateway (online auth/charge) ?
     */
    protected $_isGateway = true;
    /**
     * Can authorize online?
     */
    protected $_canAuthorize = true;
    /**
     * Can capture funds online?
     */
    protected $_canCapture = true;
    /**
     * Can capture partial amounts online?
     */
    protected $_canCapturePartial = false;
    /**
     * Can refund online?
     */
    protected $_canRefund = false;
    /**
     * Can void transactions online?
     */
    protected $_canVoid = true;
    /**
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal = true;
    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout = true;
    /**
     * Is this payment method suitable for multi-shipping checkout?
     */
    protected $_canUseForMultishipping = true;
    /**
     * Can save credit card information for future processing?
     */
    protected $_canSaveCc = false;

    /**
     * @var Shopworks_Billink_Helper_Billink
     */
    private $_helper;
    /**
     * @var Shopworks_Billink_Helper_BillinkFee
     */
    private $_feeHelper;
    /**
     * @var Shopworks_Billink_Helper_Logger
     */
    private $_logger;
    /**
     * @var Shopworks_Billink_Helper_AddressComparer
     */
    private $_addressComparer;
    /**
     * @var Shopworks_Billink_Model_OrderTotalCalculator
     */
    private $_totalCalculator;

    /** @var Mage_Tax_Model_Config */
    private $_taxConfig;

    /** @var  Shopworks_Billink_Helper_Config */
    private $_billinkConfigHelper;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->_helper = Mage::helper('billink/Billink');
        $this->_feeHelper = Mage::helper('billink/BillinkFee');
        $this->_logger = Mage::helper('billink/Logger');
        $this->_addressComparer = Mage::helper('billink/AddressComparer');
        $this->_taxConfig = Mage::getModel('tax/config');
		$this->_totalCalculator = Mage::getModel('billink/OrderTotalCalculator');
        $this->_billinkConfigHelper = Mage::helper('billink/Config');
    }


    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return bool|void
     */
    public function isAvailable($quote = null)
    {
        $isEnabled = $this->_helper->isReadyToUse() && parent::isAvailable($quote);

        //Disable billink for high order amounts
        if (!is_null($quote) && $this->_billinkConfigHelper->isBillinkDisabledForHighOrderAmounts())
        {
            if($this->getQuoteAmount($quote) > $this->_billinkConfigHelper->getMaximumAmountLimit())
            {
                $isEnabled = false;
            }
        }

        return $isEnabled;
    }

    /**
     * Assign extra payment data to info model instance. Called before validate
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        $continue = $this->beforeAssignData();
        if(!$continue)
        {
            return;
        }

        if (!($data instanceof Varien_Object))
        {
            $data = new Varien_Object($data);
        }

        //Todo, refactor to store data in infoInstance: $this->getInfoInstance()
        //Store the extra data in the session so we can use it througout the checkout process
        $session = $this->_getSession();
        $session->setData(self::STREET_SESSION_INDEX, $data['billink_street']);
        $session->setData(self::HOUSENUMBER_SESSION_INDEX, $data['billink_housenumber']);
        $session->setData(self::HOUSENUMBER_EXTENSION_SESSION_INDEX, $data['billink_housenumber_extension']);

        $session->setData(self::DELIVERY_ADDRESS_STREET_SESSION_INDEX, $data['billink_delivery_street']);
        $session->setData(self::DELIVERY_ADDRESS_HOUSENUMBER_SESSION_INDEX, $data['billink_delivery_housenumber']);
        $session->setData(self::DELIVERY_ADDRESS_HOUSENUMBER_EXTENSION_SESSION_INDEX, $data['billink_delivery_housenumber_extension']);

        $session->setData(self::PHONE_SESSION_INDEX, $data['billink_phone']);
        $session->setData(self::BIRTHDATE_SESSION_INDEX, $data['billink_dob_day'] . '-' . $data['billink_dob_month'] . '-' . $data['billink_dob_year']);
        $session->setData(self::CHAMBER_OF_COMMERCE_SESSION_INDEX, $data['billink_chamberofcommerce']);
        $session->setData(self::CUSTOMER_TYPE_SESSION_INDEX, $data['billink_checkout_type']);
        $session->setData(self::SEX_SESSION_INDEX, $data['billink_sex']);

        return $this;
    }

    /**
     * This method is called when the client selects a payment method. It is called after after the assignData method
     * We check the customers data with the Billink 'Check' service to see if he can use the Billink payment method
     *
     * @throws Mage_Payment_Exception
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function validate()
    {
        $continue = $this->beforeValidate();
        if(!$continue)
        {
            return $this;
        }

        $this->_logger->log('payment->validate', Zend_Log::INFO);
        parent::validate();

        $quote = $this->_getQuote();

        /** @var Shopworks_Billink_Model_Service_Check_Input $validateRequestInput */
        $validateRequestInput = Mage::getModel('billink/service_check_input');

        $session = $this->_getSession();

        $address = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        $isCompany = $session->getData(self::CUSTOMER_TYPE_SESSION_INDEX) == Shopworks_Billink_Model_Service_Check_Input::TYPE_COMPANY;
        $workflowNumber = $this->_billinkConfigHelper->getWorkflowNumber($isCompany);
        $quote->setData('billink_workflow_number', $workflowNumber);

        $validateRequestInput->workflowNumber = $workflowNumber;

        $quoteEmail = ($quote->getCustomerEmail()) ? $quote->getCustomerEmail(): $address->getEmail();
        if(!$quoteEmail)
        {
            throw new Exception('billink: email address missing.');
        }

        $validateRequestInput->email = $quoteEmail;
        $validateRequestInput->firstName = $address->getFirstname();
        $validateRequestInput->companyName = $address->getCompany();
        $validateRequestInput->lastName = $address->getLastname();
        $validateRequestInput->orderAmount = $quote->getBaseGrandTotal();
        $validateRequestInput->phoneNumber = $this->getPhone($address);
        $validateRequestInput->birthDate = $session->getData(self::BIRTHDATE_SESSION_INDEX);
        $validateRequestInput->chamberOfCommerce = $session->getData(self::CHAMBER_OF_COMMERCE_SESSION_INDEX);
        $validateRequestInput->type = $session->getData(self::CUSTOMER_TYPE_SESSION_INDEX);

        //Billing address
        $validateRequestInput->postalCode = $address->getPostcode();
        $validateRequestInput->houseNumber = $session->getData(self::HOUSENUMBER_SESSION_INDEX);
        $validateRequestInput->houseExtension = $session->getData(self::HOUSENUMBER_EXTENSION_SESSION_INDEX);

        //Shipping address
        if(!is_null($shippingAddress) && !$shippingAddress->getSameAsBilling())
        {
            $validateRequestInput->deliveryAddressPostalCode = $shippingAddress->getPostcode();
            $validateRequestInput->deliveryAddressHouseNumber = $session->getData(self::DELIVERY_ADDRESS_HOUSENUMBER_SESSION_INDEX);
            $validateRequestInput->deliveryAddressHouseExtension = $session->getData(self::DELIVERY_ADDRESS_HOUSENUMBER_EXTENSION_SESSION_INDEX);
        }

        //Apply test settings
        if ($this->_helper->isInTestMode())
        {
            $validateRequestInput->backdoor = $this->_billinkConfigHelper->getBackdoorValue();
        }

        //Validate that the delivery address and the invoice address are the same
        //This is already checked on the frontend, so this is just to make sure no one tries to be sneaky.
        if(!$this->_helper->isAlternateDeliveryAddressAllowed())
        {
            if (!$this->_addressComparer->areEqual($address, $shippingAddress))
            {
                $this->_logger->log('Devlivery address and shipping address are not equal', Zend_Log::ALERT);
                throw new Mage_Payment_Exception('Het verzendadres and het factuur adres mogen niet verschillend zijn');
            }
        }

        //Clear uuid
        $session->unsetData(self::CHECK_UUID_SESSION_INDEX);

        //Call service
        $service = $this->_helper->getService();
        $validationResult = $service->check($validateRequestInput);

        //Handle result
        if ($validationResult->hasError() || $validationResult->isCustomerRefused())
        {
            $this->throwPaymentException($this->_translateBillinkMessage($validationResult->getCode(), 'check'));
        }
        //Validation succeeded
        else
        {
            if ($validationResult->isCustomerAllowed())
            {
                $salesOrder = $this->_getSalesOrder($quote->getId());

                //Store the uuid of the check. We need this later to add it to the order
                $session->setData(self::CHECK_UUID_SESSION_INDEX, $validationResult->getUuid());

                //Also validate the place order call, as this requires more data and could possibly throw an error
                $placeOrderInput = $this->_createPlaceOrderInput($quote, $salesOrder);
                $placeOrderInput->doOnlyValidation = true;
                //Set dummy data for order
                $placeOrderInput->orderNumber = 'dummy';
                $placeOrderInput->orderDate = date('Y-m-d');

                //Validate order amount
                $isTotalCheckEnabled = $this->_billinkConfigHelper->isTotalCheckEnabled();
                if($isTotalCheckEnabled)
                {
                    $calculatedOrderTotal = $this->_totalCalculator->calculateTotal($placeOrderInput);
                    $orderTotal = $quote->getGrandTotal();

                    if(!$this->_floatsAreEqual($calculatedOrderTotal, $orderTotal))
                    {
                        $this->_logger->log('Order totals do not match -> calculated: '.$calculatedOrderTotal.' - grand total: '.$orderTotal.')', Zend_Log::CRIT);
                        $this->throwPaymentException($this->_translateBillinkMessage('totals_do_not_match', 'order'));
                    }
                }

                //Validate order placement
                $placeOrderResult = $service->placeOrder($placeOrderInput);
                if ($placeOrderResult->hasError())
                {
                    $this->throwPaymentException($this->_translateBillinkMessage($placeOrderResult->getCode(), 'order'));
                }
            }
        }

        return $this;
    }

    /**
     * This method is called *after* the order is created
     * @throws Exception
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $this->_logger->log('payment->getOrderPlaceRedirectUrl', Zend_Log::INFO);
        $this->createPayment();
    }

    /**
     * Send payment to Billink
     * @throws Exception
     */
    public function createPayment()
    {
        $quote = $this->_getQuote();
        $salesOrder = $this->_getSalesOrder($quote->getId());
        $input = $this->_createPlaceOrderInput($quote, $salesOrder);

        //Set order state pending
        $salesOrder->setData('state', Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $salesOrder->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $salesOrder->save();

        //Call service
        $service = $this->_helper->getService($salesOrder->getStoreId());
        $result = $service->placeOrder($input);

        //Handle result
        if ($result->hasError())
        {
            //Set order state pending
            $salesOrder->setData('state', Mage_Sales_Model_Order::STATE_CANCELED);
            $salesOrder->setStatus(Mage_Sales_Model_Order::STATE_CANCELED);
            $salesOrder->addStatusHistoryComment('Could not place Billink order: (' . $result->getCode() . ') ' . $result->getDescription(), false);
            $salesOrder->save();

            throw new Exception('Order failed');
        }

        //Set order state pending
        $salesOrder->setData('state', $this->_billinkConfigHelper->getOrderStatusAfterPayment());
        $salesOrder->setStatus($this->_billinkConfigHelper->getOrderStatusAfterPayment());
        $salesOrder->save();

        //Increase counter
        $this->increaseTransactionCounter($salesOrder);

        $this->_cleanSessionData();
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param Mage_Sales_Model_Order $salesOrder
     * @return Shopworks_Billink_Model_Service_Order_Input
     */
    private function _createPlaceOrderInput($quote, $salesOrder)
    {
        /** @var Shopworks_Billink_Model_Service_Order_Input $placeOrderInput */
        $placeOrderInput = Mage::getModel('billink/service_order_input');

        $session = $this->_getSession();
        $billingAddress = $quote->getBillingAddress();

        $isCompany = $session->getData(self::CUSTOMER_TYPE_SESSION_INDEX) == Shopworks_Billink_Model_Service_Order_Input::TYPE_COMPANY;
        $placeOrderInput->workflowNumber = $this->_billinkConfigHelper->getWorkflowNumber($isCompany);

        $quoteEmail = ($quote->getCustomerEmail()) ? $quote->getCustomerEmail(): $billingAddress->getEmail();
        if(!$quoteEmail)
        {
            throw new Exception('billink: email address missing.');
        }

        $placeOrderInput->email = $quoteEmail;
        $placeOrderInput->phoneNumber = $this->getPhone($billingAddress);
        $placeOrderInput->birthDate = $session->getData(self::BIRTHDATE_SESSION_INDEX);
        $placeOrderInput->chamberOfCommerceNumber = $session->getData(self::CHAMBER_OF_COMMERCE_SESSION_INDEX);
        $placeOrderInput->type = $session->getData(self::CUSTOMER_TYPE_SESSION_INDEX);
        $placeOrderInput->sex = $session->getData(self::SEX_SESSION_INDEX);

        //Billing address
        $placeOrderInput->firstName = $billingAddress->getFirstname();
        $placeOrderInput->lastName = $billingAddress->getLastname();
        $placeOrderInput->companyName = $billingAddress->getCompany();
        $placeOrderInput->postalCode = $billingAddress->getPostcode();
        $placeOrderInput->city = $billingAddress->getCity();
        $placeOrderInput->countryCode = $billingAddress->getCountryId();
        $placeOrderInput->street = $session->getData(self::STREET_SESSION_INDEX);
        $placeOrderInput->houseExtension = $session->getData(self::HOUSENUMBER_EXTENSION_SESSION_INDEX);
        $placeOrderInput->houseNumber = $session->getData(self::HOUSENUMBER_SESSION_INDEX);

        //Shipping address
        $shippingAddress = $quote->getShippingAddress();
        if(!is_null($shippingAddress) && !$shippingAddress->getSameAsBilling())
        {
            $placeOrderInput->deliverStreet = $session->getData(self::DELIVERY_ADDRESS_STREET_SESSION_INDEX);
            $placeOrderInput->deliverHouseNumber = $session->getData(self::DELIVERY_ADDRESS_HOUSENUMBER_SESSION_INDEX);
            $placeOrderInput->deliverHouseNumberExtension = $session->getData(self::DELIVERY_ADDRESS_HOUSENUMBER_EXTENSION_SESSION_INDEX);
            $placeOrderInput->deliveryPostalCode = $shippingAddress->getPostcode();
            $placeOrderInput->deliveryCountryCode = $shippingAddress->getCountryId();
            $placeOrderInput->deliverCity = $shippingAddress->getCity();
            $placeOrderInput->deliveryAddressCompanyName = $shippingAddress->getCompany();
            $placeOrderInput->deliveryAddressFirstName = $shippingAddress->getFirstname();
            $placeOrderInput->deliveryAddressLastName = $shippingAddress->getLastname();
        }

        //Check uuid
        $placeOrderInput->checkUuid = $session->getData(self::CHECK_UUID_SESSION_INDEX);

        //Add order details
        $placeOrderInput->orderNumber = $salesOrder->getIncrementId();
        $placeOrderInput->orderDate = $salesOrder->getData('created_at');

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($quote->getAllItems() as $item)
        {
            //Items with a parent id are options, so skip them
            if ($item->getData("parent_item_id"))
            {
                continue;
            }

            //Determine the price to send
            if($this->_taxConfig->priceIncludesTax())
            {
                $priceType = Shopworks_Billink_Model_Service_Order_Input_Item::PRICE_INCL_TAX;
                $price = $item->getPriceInclTax();
            }
            else
            {
                $priceType = Shopworks_Billink_Model_Service_Order_Input_Item::PRICE_EXCL_TAX;
                $price = $item->getPrice();
            }

            //Add order line
            $placeOrderInput->addOrderItem(
                $item->getSku(),
                $item->getName(),
                $item->getQty(),
                $price,
                $priceType,
                $item->getTaxPercent()
            );

            //Add line for discount
            if ($item->getDiscountAmount())
            {
                $discountAmount = 0 - $item->getDiscountAmount();

                $applyDiscountOnPriceInclTax = $this->_taxConfig->discountTax();
                if($applyDiscountOnPriceInclTax)
                {
                    $discountPriceType = Shopworks_Billink_Model_Service_Order_Input_Item::PRICE_INCL_TAX;
                    $discountTax = 0;   //Do not charge tax over the discount
                }
                else
                {
                    $discountPriceType = Shopworks_Billink_Model_Service_Order_Input_Item::PRICE_EXCL_TAX;
                    $discountTax = $item->getTaxPercent();
                }

                $placeOrderInput->addOrderItem(
                    $salesOrder->getCouponCode(),
                    $item->getName(),
                    1,
                    $discountAmount,
                    $discountPriceType,
                    $discountTax
                );
            }
        }

        $this->_addBillinkCostsLineToOrderInput($placeOrderInput, $quote);
        $this->_addShippingCostToOrderInput($placeOrderInput, $quote);

        return $placeOrderInput;
    }

    /**
     * @param Shopworks_Billink_Model_Service_Order_Input $placeOrderInput
     * @param Mage_Sales_Model_Quote $quote
     */
    private function _addBillinkCostsLineToOrderInput($placeOrderInput, $quote)
    {
        $totals = $quote->getTotals();
        /** @var Mage_Sales_Model_Quote_Address_Total_Abstract $total */
        foreach ($totals as $total)
        {
            if($total->getCode() == 'billink_total')
            {
                if($this->_feeHelper->isBillinkFeeFromConfigIncludingTax())
                {
                    $priceType = Shopworks_Billink_Model_Service_Order_Input_Item::PRICE_INCL_TAX;
                }
                else
                {
                    $priceType = Shopworks_Billink_Model_Service_Order_Input_Item::PRICE_EXCL_TAX;
                }

                $quoteTotal = $this->_feeHelper->getQuoteTotalInclTax($quote);
                $feeTax = $this->_feeHelper->getTax($quoteTotal, $quote->getShippingAddress(), $quote->getBillingAddress(), $quote->getCustomer(), $quote->getStore());

                $placeOrderInput->addOrderItem(
                    '',
                    $total->getTitle(),
                    1,
                    $this->_feeHelper->getBillinkFeeFromConfig($quoteTotal),
                    $priceType,
                    $feeTax->rate
                );
            }
        }
    }

    /**
     * @param Shopworks_Billink_Model_Service_Order_Input $placeOrderInput
     * @param Mage_Sales_Model_Quote $quote
     */
    private function _addShippingCostToOrderInput($placeOrderInput, $quote)
    {
        //add shipping costs seperate to get correct tax amount
        $store = $quote->getStore();
        /** @var Mage_Tax_Model_Calculation $taxCalculation */
        $taxCalculation = Mage::getModel('tax/calculation');

        $taxRateId = $this->_taxConfig->getShippingTaxClass($store);
        $request = $taxCalculation->getRateRequest(null, null, null, $store);
        $request->setProductClassId($taxRateId);
        $percent = $taxCalculation->getRate($request);

        if($this->_taxConfig->shippingPriceIncludesTax())
        {
            $priceType = Shopworks_Billink_Model_Service_Order_Input_Item::PRICE_INCL_TAX;
            $price = $quote->getShippingAddress()->getShippingInclTax();
        }
        else
        {
            $priceType = Shopworks_Billink_Model_Service_Order_Input_Item::PRICE_EXCL_TAX;
            $price = $quote->getShippingAddress()->getShippingAmount();
        }

        $placeOrderInput->addOrderItem(
            '',
            $quote->getShippingAddress()->getShippingDescription(),
            1,
            $price,
            $priceType,
            $percent
        );
    }

    /**
     * Throw payment exception
     * The error message should be displayed in the billink payment form so the user can see whats wrong
     *
     * @param $errorMessage
     * @throws Mage_Payment_Exception
     */
    private function throwPaymentException($errorMessage)
    {
        //Because OSC does not show the errorMessage from the exception, we also store the error in the session. This
        //way we can collect it from the session and display it in the form.
        $_SESSION[self::VALIDATION_MESSAGE] = $errorMessage;

        //Throw the exception
        $errorMessageField = 'p_method_' . self::PAYMENT_METHOD_BILLINK_CODE;
        throw new Mage_Payment_Exception($errorMessage, $errorMessageField);
    }

    /**
     * Clean session data
     */
    private function _cleanSessionData()
    {
        $session = $this->_getSession();
        $session->unsetData(self::CHECK_UUID_SESSION_INDEX);
        $session->unsetData(self::STREET_SESSION_INDEX);
        $session->unsetData(self::HOUSENUMBER_SESSION_INDEX);
        $session->unsetData(self::HOUSENUMBER_EXTENSION_SESSION_INDEX);
        $session->unsetData(self::DELIVERY_ADDRESS_STREET_SESSION_INDEX);
        $session->unsetData(self::DELIVERY_ADDRESS_HOUSENUMBER_SESSION_INDEX);
        $session->unsetData(self::DELIVERY_ADDRESS_HOUSENUMBER_EXTENSION_SESSION_INDEX);
        $session->unsetData(self::PHONE_SESSION_INDEX);
        $session->unsetData(self::CUSTOMER_TYPE_SESSION_INDEX);
        $session->unsetData(self::CHAMBER_OF_COMMERCE_SESSION_INDEX);
        $session->unsetData(self::BIRTHDATE_SESSION_INDEX);
        $session->unsetData(self::SEX_SESSION_INDEX);
        $session->unsetData(self::VALIDATION_MESSAGE);
    }

    /**
     * @param string $code
     * @param $service
     * @return string
     */
    private function _translateBillinkMessage($code, $service)
    {
        $messageId = 'billink_' . $service . '_error_code_' . $code;
        $message = $this->_translate($messageId);
        $isNoTranslationForMessage = $message == $messageId;

        //Fallback when there is no translation
        if ($isNoTranslationForMessage)
        {
            $this->_logger->log('Unexpected error code returned ' . $service . ' service (code: ' . $code . ')', Zend_Log::ALERT);
            $message = 'Er is een onbekende fout opgetreden (code ' . $service . '-' . $code . '). Neem contact op met de beheerder of selecteer een andere betaalmethode.';
        }

        return $message;
    }

    /**
     * @param Mage_Sales_Model_Order $salesOrder
     */
    private function increaseTransactionCounter($salesOrder)
    {
        try
        {
            /** @var Shopworks_Billink_Model_TransactionCounter $counter */
            $counter = Mage::getModel('billink/transactionCounter');
            $counter->sendTransaction($salesOrder->getStore()->getUrl(), $salesOrder->getGrandTotal());
        }
        catch(Exception $e)
        {
            //Do not allow exceptions to interfere with the ordering process
            $this->_logger->log("Error sending transaction: " . $e->getMessage(), Zend_Log::ERR);
        }
    }

    /**
     * @param string $string
     * @return string
     */
    private function _translate($string)
    {
        return Mage::helper('billink')->__($string);
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    private function _getSession()
    {
        if(Mage::app()->getStore()->isAdmin())
        {
            $session = Mage::getSingleton('adminhtml/session_quote');
        }
        else
        {
          $session = Mage::getSingleton('checkout/session');
        }
        return $session;
    }

    /**
     * @param string $quoteId
     * @return Mage_Sales_Model_Order
     */
    private function _getSalesOrder($quoteId)
    {
        /** @var Mage_Sales_Model_Order $salesOrder */
        $orderModel = Mage::getModel('sales/order');
        $salesOrder = $orderModel->getCollection()
            ->addFieldToFilter('quote_id', array('eq' => $quoteId))
            ->addAttributeToSort('entity_id', 'desc')
            ->getFirstItem();

        return $salesOrder;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    private function _getQuote()
    {
        $session = $this->_getSession();
        return $session->getQuote();
    }

    /**
     * Check if the phone number is in the session. If not, it returns the phone number from the address
     * @param Mage_Sales_Model_Quote_Address $address
     * @return string
     */
    private function getPhone($address)
    {
        $phoneFromSession = $this->_getSession()->getData(self::PHONE_SESSION_INDEX);
        if($phoneFromSession)
        {
            return $phoneFromSession;
        }
        else
        {
            return $address->getTelephone();
        }
    }

    /**
     * You should never directly compare floats
     * see: http://php.net/manual/en/language.types.float.php
     * @param float $a
     * @param float $b
     * @return bool
     */
    private function _floatsAreEqual($a, $b)
    {
        return (abs(($a-$b)/$b) < 0.00001);
    }

    /**
     * Can be owerwritten in submodules
     * @return bool
     */
    protected function beforeAssignData()
    {
        return true;
    }

    /**
     * Can be owerwritten in submodules
     * @return bool
     */
    protected function beforeValidate()
    {
        return true;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return float
     */
    private function getQuoteAmount($quote)
    {
        //Get the items costs
        $itemCosts = 0;
        $items = $quote->getAllVisibleItems();
        /** @var Mage_Sales_Model_Quote_Item $item */
        foreach ($items as $item)
        {
            $itemCosts += $item->getRowTotalInclTax();
        }

        //Get the shipping costs
        $shippingCosts = $quote->getShippingAddress()->getShippingInclTax();

        return $itemCosts + $shippingCosts;
    }
}
