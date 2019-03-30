<?php

/**
 * Class Shopworks_Billink_Model_ServicePlaceOrderInput
 *
 * Holds all the input for the call to the Billink 'order' service
 */
class Shopworks_Billink_Model_Service_Order_Input
{
    const TYPE_COMPANY = 'b';
    const TYPE_INDIVIDUAL = 'p';

    const SEX_MAN = 'm';
    const SEX_WOMAN = 'w';

    /**
     * List with items for this order
     * @var array
     */
    private $_orderItems = array();

    /**
     * The magento increment ID
     * @var string
     */
    public $orderNumber;

    public $workflowNumber;
    public $orderDate;
    public $type;
    public $initials;
    public $sex;
    public $phoneNumber;
    public $email;
    public $adittionalText;
    public $vatNumber;
    public $chamberOfCommerceNumber;

    //Billing address
    public $street;
    public $houseNumber;
    public $houseExtension;
    public $postalCode;
    public $countryCode;
    public $city;
    public $companyName;
    public $firstName;
    public $lastName;

    //Delivery address
    public $deliverStreet;
    public $deliverHouseNumber;
    public $deliverHouseNumberExtension;
    public $deliveryPostalCode;
    public $deliveryCountryCode;
    public $deliverCity;
    public $deliveryAddressCompanyName;
    public $deliveryAddressFirstName;
    public $deliveryAddressLastName;

    /**
     * @var string
     * Birthdate value should be dd-mm-yyyy
     */
    public $birthDate;

    /**
     * Uuid returned by the check service
     * @var string
     */
    public $checkUuid;

    /**
     * If this flag is enabled, the order is only validated by Billink, but not created
     * @var bool
     */
    public $doOnlyValidation = false;

    /**
     * Add an item
     *
     * @param string $sku
     * @param string $description
     * @param int $quantity
     * @param float $price
     * @param string $priceType *Use class constants*
     * @param int $taxPercentage
     * @throws Exception
     */
    public function addOrderItem($sku, $description, $quantity, $price, $priceType, $taxPercentage)
    {
        $item = new Shopworks_Billink_Model_Service_Order_Input_Item();
        $item->code = $sku;
        $item->description = $description;
        $item->quantity = $quantity;
        $item->taxPercentage = $taxPercentage;
        $item->priceType = $priceType;
        $item->price = $price;

        $this->_orderItems[] = $item;
    }

    /**
     * @return array
     */
    public function getOrderItems()
    {
       return $this->_orderItems;
    }

    /**
     * @return bool
     */
    public function isB2BOrder()
    {
        return $this->type == self::TYPE_COMPANY;
    }
}

/**
 * Class Shopworks_Billink_Model_ServicePlaceOrderInputItem *
 */
class Shopworks_Billink_Model_Service_Order_Input_Item
{
    const PRICE_INCL_TAX = 'price_incl_tax';
    const PRICE_EXCL_TAX = 'price_excl_tax';

    /**
     * @var string *Use class constants*
     * Indicates is the price is incl, or excl taxes
     */
    public $priceType;

    public $code;
    public $description;
    public $quantity;
    public $price;
    public $taxPercentage;
}