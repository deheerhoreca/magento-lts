<?php
/**
 *
 * CART2QUOTE CONFIDENTIAL
 * __________________
 *
 *  [2009] - [2020] Cart2Quote B.V.
 *  All Rights Reserved.
 *
 * NOTICE OF LICENSE
 *
 * All information contained herein is, and remains
 * the property of Cart2Quote B.V. and its suppliers,
 * if any.  The intellectual and technical concepts contained
 * herein are proprietary to Cart2Quote B.V.
 * and its suppliers and may be covered by European and Foreign Patents,
 * patents in process, and are protected by trade secret or copyright law.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Cart2Quote B.V.
 *
 * @category    Ophirah
 * @package     Qquoteadv
 * @copyright   Copyright (c) 2020 Cart2Quote B.V. (https://www.cart2quote.com)
 * @license     https://www.cart2quote.com/ordering-licenses(https://www.cart2quote.com)
 */

/**
 * Class Ophirah_Qquoteadv_Model_Client
 */
class Ophirah_Qquoteadv_Model_Client
{
    const TIMEOUT = 2; //timeout in seconds to stop blocking

    /**
     * @var
     */
    protected $_client;

    /**
     * @var string
     */
    protected $_ophirah_uri;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->_ophirah_uri = 'https://dashboard.cart2quote.com/stats-api/ws.php';
    }

    /**
     * Function that gets or instantiates a HTTP client
     *
     * @return Varien_Http_Client
     */
    private function _getClient()
    {
        if (!$this->_client instanceof Varien_Http_Client) {
            $this->_client = new Varien_Http_Client();
        }

        return $this->_client;
    }

    /**
     * Function that prepares the given params to be send using json
     *
     * @param $params
     * @return array
     */
    private function _prepareParams($params)
    {
        try {
            $encParams = Zend_Json::encode($params);
        } catch (Exception $e) {
            $message = 'can not json encode params:' . $e->getMessage();
            Mage::log('Message: ' .$message, null, 'c2q.log', true);
            Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            $encParams = Zend_Json::encode(['error', 'encoding']);
        }

        return ['qquoteadv_params' => $encParams];
    }

    /**
     * Function that sends a logging request
     *
     * @param $params
     * @return bool
     */
    public function sendRequest($params)
    {
        $params = $this->_prepareParams($params);

        $client = $this->_getClient()
            ->setUri($this->_ophirah_uri)
            ->setMethod(Zend_Http_Client::POST)
            ->resetParameters()
            ->setParameterPost($params)
            ->setConfig(['timeout' => self::TIMEOUT]);
        try {
            $response = $client->request('POST');
            $result = Zend_Json::decode($response->getBody());
        } catch (Exception $e) {
            $result = false;
            // DHH CORE HACK
            // Mage::log('Exception: ' .$e->getMessage(), null, 'c2q_exception.log', true);
            Mage::log('Exception: ' .$e->__toString(), null, 'c2q_exception.log', true);
            Mage::log("Cart2Quote debug params: ".var_export($params, true), null, 'verbose.txt', true);
            Mage::log("Cart2Quote debug reponse: ".var_export($response, true), null, 'verbose.txt', true);
        }
        return $result;
    }
}
