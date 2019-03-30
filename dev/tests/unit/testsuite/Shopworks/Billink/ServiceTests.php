<?php

/**
 * Class Shopworks_Billink_ServiceTests
 *
 * Becuase the code is dependent on this API it is important it works as exptected. But
 * unfortunately the Billink documentation is outdated, so we cannot trust it. This test class check if the Billink
 * services that we are using do what they are supposed to do.
 */
class Shopworks_Billink_ServiceTests extends PHPUnit_Framework_TestCase
{
    const BILLINK_NAME = 'shopworks';
    const BILLINK_ID = 'd84bf167e5a1189839954ef33ede6d2e0cb49a0a';
    const WORKFLOW_NUMBER = 1;
    const TEST_MODE_ENABLED = true;

    /**
     * @var Shopworks_Billink_Model_Service
     */
    private $_service;

    /**
     * Setup is run before every test.
     * Takes care of initializing the service client
     * @throws Exception
     */
    public function setUp()
    {
        /** @var Shopworks_Billink_Model_Service $service */
        $service = Mage::getModel('billink/Service');
        if(!$service)
        {
            throw new Exception('Billink model not loaded');
        }
        $service->init(self::BILLINK_NAME, self::BILLINK_ID, self::WORKFLOW_NUMBER, self::TEST_MODE_ENABLED);
        $this->_service = $service;
    }

    /**
     * Test that checks if the credentials that are used in this class are still valid
     * If this test fails, you should check the credentials at the top of this class
     */
    public function test_validate_validInput_ReturnsNoError()
    {
        //Arrange
        $input = $this->createCorrecChecktInput();

        //Act
        $result = $this->_service->check($input);

        //Assert
        $this->assertFalse($result->hasError(), 'The request returned an error: ' . $result->getDescription());
    }

    public function test_validate_unknownClientUserName_ReturnsCode101()
    {
        //Arrange
        $this->_service->init('random not existing username (or so we hope) xqzxdcu3123', self::BILLINK_ID, self::TEST_MODE_ENABLED, null);

        //Act
        $result = $this->_service->check($this->createCorrecChecktInput());

        //Assert
        $this->assertTrue($result->hasError());
        $this->assertEquals(101, $result->getCode());
    }

    public function test_validate_noWorkflowNumber_ReturnsCode104()
    {
        //Arrange
        $this->_service->init(self::BILLINK_NAME, self::BILLINK_ID, self::TEST_MODE_ENABLED, null);
        $input = $this->createCorrecChecktInput();

        //Act
        $result = $this->_service->check($input);

        //Assert
        $this->assertTrue($result->hasError());
        $this->assertEquals(104, $result->getCode());
    }

    public function test_validate_wrongWorkflowNumber_ReturnsCode105()
    {
        //Arrange
        $this->_service->init(self::BILLINK_NAME, self::BILLINK_ID, self::TEST_MODE_ENABLED, -1);
        $input = $this->createCorrecChecktInput();

        //Act
        $result = $this->_service->check($input);

        //Assert
        $this->assertTrue($result->hasError());
        $this->assertEquals(105, $result->getCode());
    }

    public function test_validate_clientIsTrusted_ReturnsCorrectResultObject()
    {
        //Arrange
        $input = $this->createCorrecChecktInput();
        $input->backdoor = "1";

        //Act
        $result = $this->_service->check($input);

        //Assert
        $this->assertFalse($result->hasError(), printf('Error returned (%) %', $result->getCode(), $result->getDescription() ));
        $this->assertTrue($result->isCustomerAllowed(), 'Customer is not trusted');
        $this->assertFalse($result->isCustomerRefused(), 'Customer allowed and refused contradict each other');
    }

    public function test_validate_clientIsNotTrusted_ReturnsCorrectResultObject()
    {
        //Arrange
        $input = $this->createCorrecChecktInput();
        $input->backdoor = "0";

        //Act
        $result = $this->_service->check($input);

        //Assert
        $this->assertFalse($result->hasError(), printf('Error returned (%) %', $result->getCode(), $result->getDescription() ));
        $this->assertTrue($result->isCustomerRefused(), 'Customer is not refused (but should be)');
        $this->assertFalse($result->isCustomerAllowed(), 'Customer is trusted (but should not be)');
    }

    public function test_placeOrder_DataIsComplete_ServiceResultInSuccess()
    {
        //Arrange
        $checkInput = $this->createCorrecChecktInput();
        $checkResult = $this->_service->check($checkInput);
        if($checkResult->isCustomerRefused() || $checkResult->hasError())
        {
            throw new Exception('Check failed... this should not be possible, check the other tests for an explanation');
        }
        $placeOrderInput = $this->createCorrectPlaceOrderInput($checkResult->getUuid());

        //Act
        $result = $this->_service->placeOrder($placeOrderInput);

        //Assert
        $this->assertFalse($result->hasError(), 'Placing the order failed: ' . $result->getCode() . ': ' . $result->getDescription());
    }

    public function test_placeOrder_UnknownWorkflowNumber_ReturnsErrorCode402()
    {
        //Arrange
        $checkInput = $this->createCorrecChecktInput();
        $checkUuid = $this->_service->check($checkInput)->getUuid();
        $placeOrderInput = $this->createCorrectPlaceOrderInput($checkUuid);
        $this->_service->init(self::BILLINK_NAME, self::BILLINK_ID, self::TEST_MODE_ENABLED, -1);   //Hopefully an unexisting workflow number

        //Act
        $result = $this->_service->placeOrder($placeOrderInput);

        //Assert
        $this->assertTrue($result->hasError(), 'There should be an error...');
        $this->assertEquals($result->getCode(), '402', 'Error description: ' . $result->getDescription());
    }

    public function test_startWorkflow_UnknownOrder_ReturnsCode704()
    {
        //Arrange

        //Act
        $result = $this->_service->startWorkflow('some random order nr that does not exists');

        //Assert
        $this->assertTrue($result->hasError(), 'There should be an error...');
        $this->assertEquals($result->getCode(), '402', 'Error description: ' . $result->getDescription());
    }

    public function test_startWorkflow_ValidData_ReturnsSuccess()
    {
        //Arrange
        $checkInput = $this->createCorrecChecktInput();
        $checkResult = $this->_service->check($checkInput);
        if($checkResult->isCustomerRefused() || $checkResult->hasError())
        {
            throw new Exception('Check failed... this should not be possible, check the other tests for an explanation: '.$checkResult->getDescription().' ('.$checkResult->getCode().')');
        }
        $placeOrderInput = $this->createCorrectPlaceOrderInput($checkResult->getUuid());
        $orderResult = $this->_service->placeOrder($placeOrderInput);
        if($orderResult->hasError())
        {
            throw new Exception('Order placing failed... this should not be possible, check the other tests for an explanation');
        }

        //Act
        $result = $this->_service->startWorkflow($placeOrderInput->orderNumber);

        //Assert
        $this->assertFalse($result->hasError(), 'Starting the order workflow has failed: ' . $result->getCode() . ': ' . $result->getDescription());
    }


    /**
     * @param string $checkUuid
     * @return Shopworks_Billink_Model_Service_Order_Input
     */
    private function createCorrectPlaceOrderInput($checkUuid)
    {
        /** @var Shopworks_Billink_Model_Service_Order_Input $input */
        $input = Mage::getModel('billink/service_order_input');

        $timestamp = new DateTime();
        $input->orderNumber = 'UT:' . $timestamp->format('Y-m-d H:i:s.u');
        $input->orderDate = '2014-1-1';
        $input->type = Shopworks_Billink_Model_Service_Order_Input::TYPE_INDIVIDUAL;
        $input->firstName = 'UNIT';
        $input->lastName = 'TEST';
        $input->sex = Shopworks_Billink_Model_Service_Order_Input::SEX_MAN;
        $input->street = 'Amsterdamseweg 43, 3812 RP Amersfoort';
        $input->houseNumber = '43';
        $input->houseExtension = '';
        $input->postalCode = '3812RP';
        $input->city = 'Amersfoort';
        $input->phoneNumber = '06-12345678';
        $input->email = 'tim.breedveld@shopworks.nl';
        $input->checkUuid = $checkUuid;
        $input->birthDate = '1984-09-24';

        $input->addOrderItem('111', 'Omschrijving', '3', '10', '12,10', '21');

        return $input;
    }


    /**
     * Create a valid input object that be used for the Billink check service
     * @return Shopworks_Billink_Model_Service_Check_Input
     */
    private function createCorrecChecktInput()
    {
        /** @var Shopworks_Billink_Model_Service_Check_Input $input */
        $input = Mage::getModel('billink/service_check_input');
        $input->birthDate= '1-1-1971';
        $input->chamberOfCommerce = '';
        $input->companyName = '';
        $input->email = 'tim.breedveld@shopworks.nl';
        $input->firstName = 'UNIT';
        $input->houseExtension = '';
        $input->houseNumber = '21';
        $input->lastName = 'TEST';
        $input->orderAmount = '250';
        $input->phoneNumber = '0612345678';
        $input->postalCode = '3417GJ';
        $input->type = Shopworks_Billink_Model_Service_Check_Input::TYPE_INDIVIDUAL;
        $input->backdoor = 1;

        return $input;
    }
}