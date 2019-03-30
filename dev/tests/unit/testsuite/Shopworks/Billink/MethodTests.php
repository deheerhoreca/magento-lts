<?php

/**
 * Class Shopworks_Billink_MethodTests
 */
class Shopworks_Billink_MethodTests extends PHPUnit_Framework_TestCase
{
    /**
     * @var Shopworks_Billink_Model_Payment_Method
     */
    private $_model;

    /**
     * Setup method, is run before every test
     */
    public function setUp()
    {
        $model = Mage::getModel('billink/payment_method');
        if(is_null($model))
        {
            throw new Exception('Billink payment model not loaded');
        }
        $this->_model = $model;
    }

    /**
     * Test if the assignDataMethod sets all session data
     */
    public function test_assignData_Data_SetsBillinkDataInSession()
    {
        //Arrange
        $sex = 'm';
        $checkoutType = 'b';

        $data = new Varien_Object();
        $data['billink_sex'] = $sex;
        $data['billink_checkout_type'] = $checkoutType;

        //Act
        $this->_model->assignData($data);

        //Assert
        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');
        $this->assertEquals($sex, $session->getData(Shopworks_Billink_Model_Payment_Method::SEX_SESSION_INDEX));
        $this->assertEquals($checkoutType, $session->getData(Shopworks_Billink_Model_Payment_Method::CUSTOMER_TYPE_SESSION_INDEX));
    }

    /**
     * Create a birthdate from year, month, day
     */
    public function test_assignData_Data_CombinesDateFields()
    {
        //Arrange
        $day = 21;
        $month = 10;
        $year = 2005;

        $data = new Varien_Object();
        $data['billink_dob_day'] = $day;
        $data['billink_dob_month'] = $month;
        $data['billink_dob_year'] = $year;

        //Act
        $this->_model->assignData($data);

        //Assert
        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');
        $expectedBirthDate = printf('%s-%s-%s', $day, $month, $year);
        $this->assertEquals($expectedBirthDate, $session->getData(Shopworks_Billink_Model_Payment_Method::BIRTHDATE_SESSION_INDEX));
    }
}