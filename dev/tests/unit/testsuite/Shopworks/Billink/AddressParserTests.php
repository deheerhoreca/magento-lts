<?php

/**
 * Class AddressParserTests
 */
class AddressParserTests extends PHPUnit_Framework_TestCase
{

    public function test_Address_WithoutExtension()
    {
        //Arrange
        $addresss = 'Julianalaan 21';

        //Act
        $result = $this->getHelper()->parse($addresss);

        //Assert
        $this->assertEquals($result->streetName, 'Julianalaan');
        $this->assertEquals($result->houseNumber, '21');
        $this->assertEquals($result->houseNumberExtension, '');
    }

    public function test_Address_WithExtension()
    {
        //Arrange
        $addresss = 'Arcaciastraat 21bis';

        //Act
        $result = $this->getHelper()->parse($addresss);

        //Assert
        $this->assertEquals($result->streetName, 'Arcaciastraat');
        $this->assertEquals($result->houseNumber, '21');
        $this->assertEquals($result->houseNumberExtension, 'bis');
    }

    public function test_Address_WithNumberInStreet()
    {
        //Arrange
        $addresss = '24 oktoberplein 9';

        //Act
        $result = $this->getHelper()->parse($addresss);

        //Assert
        $this->assertEquals($result->streetName, '24 oktoberplein');
        $this->assertEquals($result->houseNumber, '9');
        $this->assertEquals($result->houseNumberExtension, '');
    }

    public function test_Address_WithSpaceBetweenHousenumberAndExtension()
    {
        //Arrange
        $addresss = 'Amsterdamseweg 43 a';

        //Act
        $result = $this->getHelper()->parse($addresss);

        //Assert
        $this->assertEquals($result->streetName, 'Amsterdamseweg');
        $this->assertEquals($result->houseNumber, '43');
        $this->assertEquals($result->houseNumberExtension, 'a');
    }

    public function test_Address_WithLineBreak()
    {
        //Arrange
        $addresss = "Amsterdamseweg\n 43 a";

        //Act
        $result = $this->getHelper()->parse($addresss);

        //Assert
        $this->assertEquals($result->streetName, 'Amsterdamseweg');
        $this->assertEquals($result->houseNumber, '43');
        $this->assertEquals($result->houseNumberExtension, 'a');
    }

    public function test_Address_WithHousenumberWithMinusSign()
    {
        //Arrange
        $addresss = "Amsterdamseweg 42-43";

        //Act
        $result = $this->getHelper()->parse($addresss);

        //Assert
        $this->assertEquals($result->streetName, 'Amsterdamseweg');
        $this->assertEquals($result->houseNumber, '42-43');
        $this->assertEquals($result->houseNumberExtension, '');
    }

    public function test_Address_WithHousenumberWithExtension()
    {
        //Arrange
        $addresss = "Amsterdamseweg 42-43 a";

        //Act
        $result = $this->getHelper()->parse($addresss);

        //Assert
        $this->assertEquals($result->streetName, 'Amsterdamseweg');
        $this->assertEquals($result->houseNumber, '42-43');
        $this->assertEquals($result->houseNumberExtension, 'a');
    }

    public function test_Address_StreetNameWithMinusSign()
    {
        //Arrange
        $addresss = "1-7-4 Dual Ampstreet 130 A";

        //Act
        $result = $this->getHelper()->parse($addresss);

        //Assert
        $this->assertEquals($result->streetName, '1-7-4 Dual Ampstreet');
        $this->assertEquals($result->houseNumber, '130');
        $this->assertEquals($result->houseNumberExtension, 'A');
    }

    public function test_Address_HouseExtensionWithNumber()
    {
        //Arrange
        $addresss = "Terheydenlaan 320 B3";

        //Act
        $result = $this->getHelper()->parse($addresss);

        //Assert
        $this->assertEquals($result->streetName, 'Terheydenlaan');
        $this->assertEquals($result->houseNumber, '320');
        $this->assertEquals($result->houseNumberExtension, 'B3');
    }

    public function test_Address_StreetWithNumberAndFollowedByChar()
    {
        //Arrange
        $addresss = "245e oosterkade 9";

        //Act
        $result = $this->getHelper()->parse($addresss);

        //Assert
        $this->assertEquals($result->streetName, '245e oosterkade');
        $this->assertEquals($result->houseNumber, '9');
        $this->assertEquals($result->houseNumberExtension, '');
    }

    /**
     * @return Shopworks_Billink_Helper_AddressParser
     */
    private function getHelper()
    {
        $helper = Mage::helper('billink/AddressParser');
        return $helper;
    }
}