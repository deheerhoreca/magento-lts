<?php

namespace Afterpay;

 /**
 * Copyright (c) 2017  arvato Finance B.V.
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
 * @name        AfterPay Class
 * @author      AfterPay (support@afterpay.nl)
 * @description PHP Library to connect with AfterPay Post Payment services
 * @copyright   Copyright (c) 2017 arvato Finance B.V.
 */


abstract class Client
{
    /**
     * @var array $authorization
     */
    protected $authorization = [];

    /**
     * @var bool $orderManagement
     */
    protected $orderManagement = false;

    /**
     * @var int $totalOrderAmount
     */
    protected $totalOrderAmount = 0;

    /**
     * @var array $orderLines
     */
    protected $orderLines = [];

    /**
     * @var string $webServiceUrl
     */
    protected $webServiceUrl;

    /**
     * @var string $country
     */
    protected $country = 'NL';

    /**
     * @var string $orderType
     */
    protected $orderType;

    /**
     * @var string $orderAction
     */
    protected $orderAction;

    /**
     * @var string $orderTypeName
     */
    protected $orderTypeName;

    /**
     * @var string $orderTypeFunction
     */
    protected $orderTypeFunction;

    /**
     * @var array $orderRequest
     */
    protected $orderRequest;
    /**
     * @var array|object $orderResult
     */
    protected $orderResult;

    /**
     * @var string $mode
     */
    protected $mode;

    /**
     * @var array $order
     */
    protected $order = [];

    /**
     * @var string $billToAddress
     */
    protected $billToAddress;

    /**
     * @var string $shipToAddress
     */
    protected $shipToAddress;

    /**
     * @var array $debugLog
     */
    protected $debugLog;

    /**
     * Function to create order line
     *
     * @param int $productId
     * @param string $description
     * @param int $quantity
     * @param int $unitPrice
     * @param $vatCategory
     * @param int $vatAmount
     * @param int $googleProductCategoryId
     * @param string $googleProductCategory
     * @param string $productUrl
     */
    abstract public function createOrderLine(
        $productId,
        $description,
        $quantity,
        $unitPrice,
        $vatCategory = null,
        $vatAmount = null,
        $googleProductCategoryId = null,
        $googleProductCategory = null,
        $productUrl = null
    );

    /**
     * If order management is used set action to true;
     *
     * @param string $action
     */
    abstract public function setOrderManagement($action);

    /**
     * @param array $authorization
     */
    abstract protected function setAuthorization($authorization);

    /**
     * @param string $country
     * @param string $mode
     * @return null|string
     */
    abstract protected function getWebserviceUrl($country, $mode);

    /**
     * Process request to SOAP/REST webservice
     *
     * @param array $authorization
     * @param string $mode
     */
    abstract protected function doRequest($authorization, $mode);

    /**
     * Sets mode, options are test or live
     *
     * @param string $mode
     */
    abstract protected function setMode($mode);

    /**
     * Getter for order result
     *
     * @return object
     */
    abstract public function getOrderResult();

    /**
     * Getter for mode
     *
     * @return string
     */
    abstract public function getMode();

    /**
     * Getter for authorization
     *
     * @return array
     */
    abstract public function getAuthorization();

    /**
     * Getter for order
     *
     * @return array
     */
    abstract public function getOrder();

    /**
     * Getter for debugLog
     *
     * @return array
     */
    public function getDebugLog() {
        return $this->debugLog;
    }

    /**
     * Function to create debug line
     *
     * @param string $title
     * @param string $json
     * @param string $xml
     */
    public function createDebugLine(
        $title,
        $json = null,
        $xml = null,
        $object = null
    )
    {
        $datetime = date("Y-m-d H:i:s");
        $this->debugLog .= $datetime . ': ' . $title . "\n";
        if (!is_null($json)) {
            $this->debugLog .= json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n";
        }
        if (!is_null($xml)) {
            $out = $this->formatXmlString($xml);
            $this->debugLog .= print_r($out, true) . "\n";
        }
        if (!is_null($object)) {
            $this->debugLog .= var_export($object, true) . "\n";
        }
        $this->debugLog .= "\n";
    }

    /**
     * Function markup xml to make it readable in debug
     *
     * @param string $xml
     */
    private function formatXmlString($xml){
        $xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml);
        $token = strtok($xml, "\n");
        $result = '';
        $pad = 0;
        $matches = array();
        while ($token !== false) :
            if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) :
                $indent=0;
            elseif (preg_match('/^<\/\w/', $token, $matches)) :
                $pad--;
                $indent = 0;
            elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) :
                $indent = 1;
            else :
                $indent = 0;
            endif;
            $line = str_pad($token, strlen($token)+$pad, ' ', STR_PAD_LEFT);
            $result .= $line . "\n";
            $token = strtok("\n");
            $pad += $indent;
        endwhile;
        return $result;
    }
}