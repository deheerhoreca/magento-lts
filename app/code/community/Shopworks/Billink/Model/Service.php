<?php

/**
 * Class Shopworks_Billink_Model_Serivce
 * Wrapper around the Billink API
 */
class Shopworks_Billink_Model_Service
{
    //Api urls
    const BILLINK_TEST_API_URL = 'https://test.billink.nl/api/';
    const BILLINK_LIVE_API_URL = 'https://client.billink.nl/api/';

    /**
     * @var string
     */
    private $_billinkUsername;
    /**
     * @var string
     */
    private $_billinkUid;
    /**
     * @var bool
     */
    private $_initialized;
    /**
     * @var Shopworks_Billink_Model_Service_XmlGenerator
     */
    private $_xmlGenerator;
    /**
     * @var Shopworks_Billink_Helper_Logger
     */
    private $_logger;
    /**
     * @var bool
     */
    private $_isInTestMode;

    /**
     * Constructor (a real constructor does not work very well in Magento)
     *
     * @param string $billinkUsername
     * @param string $billinkUid
     * @param bool $isInTestMode
     */
    public function init($billinkUsername, $billinkUid, $isInTestMode)
    {
        $this->_initialized = true;
        $this->_billinkUsername = $billinkUsername;
        $this->_billinkUid = $billinkUid;

        $this->_xmlGenerator = Mage::getModel('billink/service_xmlGenerator');
        $this->_logger = Mage::helper('billink/Logger');
        $this->_isInTestMode = $isInTestMode;
    }

    /**
     * Call the Billink service to validate the user and order data
     * @param Shopworks_Billink_Model_Service_Check_Input $input
     * @return Shopworks_Billink_Model_Service_Check_Response
     */
    public function check(Shopworks_Billink_Model_Service_Check_Input $input)
    {
        $this->_logger->log('Starting check for: ' . $input->email, Zend_Log::INFO);

        //make xml
        $xmlNodes = array(
            'ACTION'=>'Check',
            'WORKFLOWNUMBER' => $input->workflowNumber,
            'TYPE' => $input->type,
            'FIRSTNAME' => $input->firstName,
            'LASTNAME' => $input->lastName,
            'INITIALS' => $input->initials,
            'HOUSENUMBER' => $input->houseNumber,
            'HOUSEEXTENSION' => $input->houseExtension,
            'POSTALCODE' => $input->postalCode,
            'PHONENUMBER' => $input->phoneNumber,
            'BIRTHDATE' => $input->birthDate,
            'EMAIL' => $input->email,
            'ORDERAMOUNT' => $input->orderAmount,
            'BACKDOOR' => $input->backdoor,
            'DELIVERY_HOUSENUMBER' => $input->deliveryAddressHouseNumber,
            'DELIVERY_HOUSEEXTENSION' => $input->deliveryAddressHouseExtension,
            'DELIVERY_POSTALCODE' => $input->deliveryAddressPostalCode,
            'IP' => $_SERVER['REMOTE_ADDR']
        );

        if($input->isB2BOrder())
        {
            $xmlNodes['COMPANYNAME'] =  $input->companyName;
            $xmlNodes['CHAMBEROFCOMMERCE'] =  $input->chamberOfCommerce;
        }

        $xmlNodes = $this->_addConfigSettings($xmlNodes);

        //request
        $response = $this->_makeRequest('check', $xmlNodes);
        $result  = $this->_processCheckServiceResponse($response);

        return $result;
    }

    /**
     * Call the Billink service to confirm an order
     * @param Shopworks_Billink_Model_Service_Order_Input $input
     * @return Shopworks_Billink_Model_Service_Order_Result
     * @throws Exception
     */
    public function placeOrder(Shopworks_Billink_Model_Service_Order_Input $input)
    {
        $this->_logger->log('Placing order for: ' . $input->email, Zend_Log::INFO);

        //make xml
        $xmlNodes = array(
            'ACTION'=>'Order',
            'WORKFLOWNUMBER' => $input->workflowNumber,
            'BIRTHDATE' => $input->birthDate,
            'ORDERNUMBER' => $input->orderNumber,
            'DATE' => $input->orderDate,
            'TYPE' => $input->type,
            'FIRSTNAME' => $input->firstName,
            'LASTNAME' => $input->lastName,
            'INITIALS' => $input->initials,
            'STREET' => $input->street,
            'HOUSENUMBER' => $input->houseNumber,
            'HOUSEEXTENSION' => $input->houseExtension,
            'POSTALCODE' => $input->postalCode,
            'COUNTRYCODE' => $input->countryCode,
            'CITY' => $input->city,
            'DELIVERYSTREET' => $input->deliverStreet,
            'DELIVERYHOUSENUMBER' => $input->deliverHouseNumber,
            'DELIVERYHOUSEEXTENSION' => $input->deliverHouseNumberExtension,
            'DELIVERYPOSTALCODE' => $input->deliveryPostalCode,
            'DELIVERYCOUNTRYCODE' => $input->deliveryCountryCode,
            'DELIVERYCITY' => $input->deliverCity,
            'DELIVERYADDRESSCOMPANYNAME' => $input->deliveryAddressCompanyName,
            'DELIVERYADDRESSFIRSTNAME' => $input->deliveryAddressFirstName,
            'DELIVERYADDRESSLASTNAME' => $input->deliveryAddressLastName,
            'PHONENUMBER' => $input->phoneNumber,
            'EMAIL' => $input->email,
            'SEX' => $input->sex,
            'IP' => $_SERVER['REMOTE_ADDR'],
            'ADITIONALTEXT' => '', //unused
            'VARIABLE1' => '',
            'VARIABLE2' => '',  //unused
            'VARIABLE3' => '',  //unused
            'VATNUMBER' => $input->vatNumber,
            'CHECKUUID' => $input->checkUuid,
            'VALIDATEORDER' => $input->doOnlyValidation ? 'Y' : 'N'
        );

        //Type specific xml
        if($input->isB2BOrder())
        {
            $xmlNodes['CHAMBEROFCOMMERCE'] = $input->chamberOfCommerceNumber;
            $xmlNodes['COMPANYNAME'] =  $input->companyName;
        }

        //Append order items
        $itemsXml = array();
        foreach($input->getOrderItems() as $orderItem)
        {
            /** @var Shopworks_Billink_Model_Service_Order_Input_Item $item */
            $xmlItem = array(
                'CODE' => $orderItem->code,
                'DESCRIPTION' => $orderItem->description,
                'BTW' => intval($orderItem->taxPercentage),
                'ORDERQUANTITY' => $orderItem->quantity
            );

            //Validate pricetype, we can only send one price to rule them all.
            switch($orderItem->priceType)
            {
                case Shopworks_Billink_Model_Service_Order_Input_Item::PRICE_INCL_TAX:
                    $xmlItem['PRICEINCL'] = floatval($orderItem->price);
                    break;
                case Shopworks_Billink_Model_Service_Order_Input_Item::PRICE_EXCL_TAX:
                    $xmlItem['PRICEEXCL'] = floatval($orderItem->price);
                    break;
                default:
                    throw new Exception('Unknown price type');
            }

            $itemsXml[] = array('ITEM' => $xmlItem);
        }
        $xmlNodes['ORDERITEMS'] = $itemsXml;

        //Add config settings
        $xmlNodes = $this->_addConfigSettings($xmlNodes);

        //request
        $response = $this->_makeRequest('order', $xmlNodes);
        $result = $this->_processOrderServiceResponse($response);

        return $result;
    }

    /**
     * Start a Billink workflow
     * The Billink API can handle multiple ivoices at once. But this is not usefull in Magento. Therefore
     * we make a seperate api call per invoice
     *
     * @param string $magentoOrderIncrementId
     * @param string $workflowNumber
     * @return Shopworks_Billink_Model_Service_StartWorkflow_Result
     * @throws Exception
     */
    public function startWorkflow($magentoOrderIncrementId, $workflowNumber)
    {
        //make xml
        $xmlNodes = array(
            'ACTION' => 'activate order',
            'INVOICES' => array(
                array(
                    'ITEM' => array(
                        'INVOICENUMBER' => $magentoOrderIncrementId,
                        'WORKFLOWNUMBER' => $workflowNumber
                    )
                )
            )
        );

        //Add config settings
        $xmlNodes = $this->_addConfigSettings($xmlNodes);

        $response = $this->_makeRequest('start-workflow', $xmlNodes);
        $responseObj = $this->_processStartWorkflowResult($response);

        return $responseObj;
    }

    /**
     * Convert the XML response to an object
     * @param string $responseXml
     * @return \Shopworks_Billink_Model_Service_Check_Response
     * @throws Exception
     */
    private function _processCheckServiceResponse($responseXml)
    {
        /** @var Shopworks_Billink_Model_Service_Check_Response $response */
        $response = Mage::getModel("billink/service_check_response");

        $xmlObj = new Varien_Simplexml_Config();
        $xmlObj->loadString($responseXml);

        $result = $xmlObj->getNode('RESULT')->__toString();

        switch($result)
        {
            //Error result
            case 'ERROR' :
                $code = $xmlObj->getNode('ERROR/CODE')->__toString();
                $description = $xmlObj->getNode('ERROR/DESCRIPTION')->__toString();
                $response->setError($code, $description);
                $this->_logger->log($description . '('.$code.')', Zend_Log::ERR);
                break;
            //Succesfull result
            case 'MSG' :
                //The advice code determines  the customer can use the Billink service
                $code = $xmlObj->getNode('MSG/CODE')->__toString();
                switch($code)
                {
                    //the customer is not to be trusted!
                    case '501' :
                    case '607' :
                        $response->setSuccess(false, $code);
                        break;
                    //The customer is trusted by Billink
                    case '500' :
                        $uuid = $xmlObj->getNode('UUID')->__toString();
                        $response->setSuccess(true, $code, $uuid);
                        break;
                    default:
                        throw new Exception('Unexpected result from service');
                }

                break;
            //Unexpected result (should never happen of course)
            default:
                throw new Exception('Unexpected result from service');
        }

        return $response;
    }

    /**
     * @param string $responseXml
     * @return Shopworks_Billink_Model_Service_Order_Result
     * @throws Exception
     */
    private function _processOrderServiceResponse($responseXml)
    {
        /** @var Shopworks_Billink_Model_Service_Order_Result $response */
        $response = Mage::getModel("billink/service_order_result");

        $xmlObj = new Varien_Simplexml_Config();
        $xmlObj->loadString($responseXml);

        $result = $xmlObj->getNode('RESULT')->__toString();

        switch($result)
        {
            //Error result
            case 'ERROR' :
                $code = $xmlObj->getNode('ERROR/CODE')->__toString();
                $description = $xmlObj->getNode('ERROR/DESCRIPTION')->__toString();
                $response->setError($code, $description);
                $this->_logger->log($description . '('.$code.')', Zend_Log::ERR);
                break;
            //Succesfull result
            case 'MSG' :
                $code = $xmlObj->getNode('MSG/CODE')->__toString();
                if($code == '200')
                {
                    $response->setSuccess();
                }
                else
                {
                    throw new Exception('Unexpected result from service');
                }

                break;
            //Unexpected result (should never happen of course)
            default:
                throw new Exception('Unexpected result from service');
        }
        return $response;
    }

    /**
     * @param string $responseXml
     * @throws Exception
     * @return Shopworks_Billink_Model_Service_StartWorkflow_Result
     */
    private function _processStartWorkflowResult($responseXml)
    {
        /** @var Shopworks_Billink_Model_Service_StartWorkflow_Result $result */
        $result = Mage::getModel("billink/service_startWorkflow_result");

        $xmlObj = new Varien_Simplexml_Config();
        $xmlObj->loadString($responseXml);

        $responseResult = $xmlObj->getNode('RESULT')->__toString();

        switch($responseResult)
        {
            //Error result
            case 'ERROR' :
                $code = $xmlObj->getNode('ERROR/CODE')->__toString();
                $description = $xmlObj->getNode('ERROR/DESCRIPTION')->__toString();
                $result->setError($code, $description);
                $this->_logger->log($description . '('.$code.')', Zend_Log::ERR);
                break;
            //Succesfull result
            case 'MSG' :
                //We ignore the status updates per invoice, the only thing what matters to us, is that the workflow
                //has succesfully started
                $result->setSuccess();
                break;
            //Unexpected result (should never happen of course)
            default:
                throw new Exception('Unexpected result from service');
        }

        return $result;
    }

    /**
     * @param string $serviceName
     * @return string
     */
    private function _getSerivceUrl($serviceName)
    {
        $baseUrl = $this->_isInTestMode ? self::BILLINK_TEST_API_URL : self::BILLINK_LIVE_API_URL;
        return $baseUrl . $serviceName;
    }

    /**
     * Send a CURL request
     * @param string $serviceName
     * @param array $xmlAsArray
     * @throws Exception
     * @return string
     */
    private function _makeRequest($serviceName, $xmlAsArray)
    {
        //request
        $url = $this->_getSerivceUrl($serviceName);
        $xml = $this->_createApiXml($xmlAsArray);

        //Check if the module is initialized
        if(!$this->_initialized)
        {
            throw new Exception('Module not initialized');
        }

        $this->_logger->log('Making call to: ' . $url, Zend_Log::INFO);

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,    //Do not enable this option, this will cause the module to fail when openbase restriction is in effect
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: text/xml",
                "Content-Length: " . strlen($xml)
            )
        );

        $cUrl = curl_init($url);
        curl_setopt_array($cUrl, $options);

        $response = curl_exec($cUrl);

        if($response !== false)
        {
            $info = curl_getinfo($cUrl);
            $responseBody = substr($response, $info['header_size']);
        }
        else
        {
            $error = curl_error($cUrl);
            $this->_logger->log('Failed to call: ' . $url . '. cUrl exception: ' . $error, Zend_Log::ERR);
            throw new Exception('Could not connect to Billink service');
        }

        return $responseBody;
    }

    /**
     * Adds mandatory settings like Billing Id and such
     * @param $xmlNodes
     */
    private function _addConfigSettings($xmlNodes)
    {
        $xmlNodes['VERSION'] = 'BILLINK2.0';
        $xmlNodes['CLIENTUSERNAME'] = $this->_billinkUsername;
        $xmlNodes['CLIENTID'] = $this->_billinkUid;
        return $xmlNodes;
    }

    /**
     * Creates the xml for the Billink API
     * @param $xmlNodes
     * @return string
     */
    private function _createApiXml($xmlNodes)
    {
        return $this->_xmlGenerator->createXml('API', $xmlNodes);
    }
}
