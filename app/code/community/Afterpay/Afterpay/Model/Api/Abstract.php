<?php
/**
 * Copyright (c) 2011-2020  arvato Finance B.V.
 *
 * AfterPay reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of AfterPay.
 *
 * Disclaimer:
 * THIS NOTICE MAY NOT BE REMOVED FROM THE PROGRAM BY ANY USER THEREOF.
 * THE PROGRAM IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE PROGRAM OR THE USE OR OTHER DEALINGS
 * IN THE PROGRAM.
 *
 * @category    AfterPay
 * @package     Afterpay_Afterpay
 * @copyright   Copyright (c) 2011-2020 arvato Finance B.V.
 */
// Include AfterPay PHP Library
require_once Mage::getBaseDir('lib') . '/Afterpay/vendor/autoload.php';

class Afterpay_Afterpay_Model_Api_Abstract extends Mage_Core_Model_Abstract
{
    protected $_testMode = 'sandbox';
    protected $_vars;
    protected $_method;
    protected $_debugEmail;
    protected $_country;
    protected $_currency;

    // AfterPay Object to store all the necessary information for the request
    public $_afterpay;

    // AfterPay Order array to store specific order information
    public $_afterpay_order;

    public function __construct()
    {
        // Setup the AfterPay Object
        $this->_afterpay = new \Afterpay\Afterpay();
        $this->_afterpay_order = array();
    }

    public function getTestMode()
    {
        return $this->_testMode;
    }

    public function setTestMode($testMode)
    {
        switch ($testMode) {
            case 0:
                $this->_testMode = 'live';
                break;
            case 1:
                $this->_testMode = 'test';
                break;
            case 2:
            default:
                $this->_testMode = 'sandbox';
                break;
        }
        return $this;
    }

    public function getVars()
    {
        return $this->_vars;
    }

    public function setVars($vars = array())
    {
        $this->_vars = $vars;
        return $this;
    }

    public function getCountry()
    {
        return $this->_country;
    }

    public function setCountry($country = 'nlnl')
    {
        switch ($country) {
            // The Netherlands
            case 'nlnl':
                $this->_country = 'NL';
                $this->_currency = 'EUR';
                break;
            // Belgium
            case 'benl':
            case 'befr':
                $this->_country = 'BE';
                $this->_currency = 'EUR';
                break;
            // The Netherlands
            case 'nlnl-rest':
                $this->_country = 'NL';
                $this->_currency = 'EUR';
                $this->_afterpay->setRest();
                break;
            // Belgium
            case 'benl-rest':
            case 'befr-rest':
                $this->_country = 'BE';
                $this->_currency = 'EUR';
                $this->_afterpay->setRest();
                break;
            // Germany
            case 'dede':
                $this->_country = 'DE';
                $this->_currency = 'EUR';
                $this->_afterpay->setRest();
                break;
            // Austria
            case 'atde':
                $this->_country = 'AT';
                $this->_currency = 'EUR';
                $this->_afterpay->setRest();
                break;
            // Switserland
            case 'chde':
                $this->_country = 'CH';
                $this->_currency = 'CHF';
                $this->_afterpay->setRest();
                break;
            // Sweden
            case 'sesv':
                $this->_country = 'SE';
                $this->_currency = 'SEK';
                $this->_afterpay->setRest();
                break;
            // Denmark
            case 'dkda':
                $this->_country = 'DK';
                $this->_currency = 'DKK';
                $this->_afterpay->setRest();
                break;
            // Finland
            case 'fifi':
                $this->_country = 'FI';
                $this->_currency = 'EUR';
                $this->_afterpay->setRest();
                break;
            // Norway
            case 'nonb':
                $this->_country = 'NO';
                $this->_currency = 'NOK';
                $this->_afterpay->setRest();
                break;
            default:
                $this->_country = 'NL';
                break;
        }
        return $this;
    }

    public function getMethod()
    {
        return $this->_method;
    }

    public function setMethod($method = '')
    {
        $this->_method = $method;
        return $this;
    }
    
    /**
     * getPlugingProviderData
     *
     * @return void
     */
    private function getPlugingProviderData()
    {
        $path = __DIR__ . "./../../etc/config.xml";
        $configXmlContent = json_decode(json_encode(simplexml_load_string(file_get_contents($path, true))), true);
        $modules = $configXmlContent["modules"];
        $afterPayModule = $modules["Afterpay_Afterpay"];
        $moduleVersion = $afterPayModule["version"];

        return array(
            "pluginProvider" => "Arvato",
            "pluginVersion" => $moduleVersion,
            "shopUrl" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
            "shopPlatform" => "Magento",
            "shopPlatformVersion" => Mage::getVersion()
        );
    }
    
    /**
     * addPlugingProviderData
     *
     * @param  mixed $order
     * @return void
     */
    public function addPlugingProviderData($order) {

        if(isset($order['additionalData'])){
            return $order;
        }

        // Add additionalData (Plugin PRovider Data) to the current order payload
        $order['additionalData'] = $this->getPlugingProviderData();

        return $order;
    }

    public function doRequest()
    {
        // DHH CORE HACK
        // $authorisation['merchantid'] = $this->_vars['merchantId'];
        // $authorisation['portfolioid'] = $this->_vars['portfolioId'];
        // $authorisation['password'] = $this->_vars['password'];
        // $authorisation['apiKey'] = $this->_vars['merchantId'];
        $authorisation['merchantid'] = $this->_vars['merchantId']   ?? "";
        $authorisation['portfolioid'] = $this->_vars['portfolioId'] ?? "";
        $authorisation['password'] = $this->_vars['password']       ?? "";
        $authorisation['apiKey'] = $this->_vars['merchantId']       ?? "";

        // Check test or live modus
        $mode = $this->getTestMode();

        $this->_afterpay->do_request($authorisation, $mode);
        return $this->_afterpay->order_result;
    }
}
