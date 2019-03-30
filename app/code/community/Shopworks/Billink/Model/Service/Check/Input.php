<?php

/**
 * Class Shopworks_Billink_Model_ServiceCheckInput
 */
class Shopworks_Billink_Model_Service_Check_Input
{
    const TYPE_COMPANY = 'b';
    const TYPE_INDIVIDUAL = 'p';

    public $workflowNumber;
    public $companyName;
    public $chamberOfCommerce;
    public $firstName;
    public $lastName;
    public $initials;
    public $phoneNumber;
    public $email;
    public $orderAmount;
    public $type;

    //Billing address
    public $houseNumber;
    public $houseExtension;
    public $postalCode;

    //Delivery address (optional)
    public $deliveryAddressHouseNumber;
    public $deliveryAddressHouseExtension;
    public $deliveryAddressPostalCode;

    /**
     * @var string
     * Birthdate value should be dd--mm-yyyy
     */
    public $birthDate;

    /**
     * @var int
     * Backdoor attribute can only be used in test mode. This indicates what the API should return as a response.
     * $backdoor = 0  -->  The API returns that the client can not be trusted
     * $backdoor = 1  -->  The API returns that the client can be trusted
     */
    public $backdoor = 0;

    /**
     * @return bool
     */
    public function isB2BOrder()
    {
        return $this->type == self::TYPE_COMPANY;
    }
}

