<?php

/**
 * Copyright (c) 2011-2022  arvato Finance B.V.
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
 * @copyright   Copyright (c) 2011-2022 arvato Finance B.V.
 */

class Afterpay_Afterpay_Model_Request_Payment_Methods_Available extends Afterpay_Afterpay_Model_Request_Ordermanagement
{
    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $quote;

    /**
     * Detects if an element is part of the validation array. It is used to test Cureency, and Language.
     *
     * @param mixed $code
     * @param mixed $allowedCodes
     * @param mixed $default
     * @return void
     */
    private function getValidCode($code, $allowedCodes, $default)
    {
        if (in_array($code, $allowedCodes)) {
            return $code;
        } else {
            return $default;
        }
    }

    /**
     * Returns the current Currency code of the Quote and compare it with the allowed ones.
     *
     * @return void
     */
    private function getCurrencyCode()
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $currencyCode = $quote->getBaseCurrencyCode();
        $allowedCurrencies = array("EUR", "NOK", "SEK", "DKK", "CHF");

        return $this->getValidCode($currencyCode, $allowedCurrencies, "EUR");
    }

    /**
     * Returns the Store Language code
     *
     * @return void
     */
    private function getLanguageCode()
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $languageCode = strtoupper(explode("_", Mage::getStoreConfig('general/locale/code', $quote->getStoreId()))[0]);
        $allowedLocales = array("NO", "SV", "SE", "FI", "DA", "DK", "EN", "DE", "NL", "FR");

        return $this->getValidCode($languageCode, $allowedLocales, "EN");
    }

    /**
     * Generates the Order that will be sent to request the available payment methods
     *
     * @return void
     */
    private function getAvailablePaymentOrder()
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $order = array();

        if ($this->getLanguageCode()) {
            $order['conversationLanguage'] = $this->getLanguageCode();
        }
        $order['country'] = $this->getCountry();
        $order['order']['totalGrossAmount'] = $quote->getGrandTotal();
        $order['order']['totalNetAmount'] = $quote->getSubtotal();

        if ($this->getCurrencyCode()) {
            $order['order']['currency'] = $this->getCurrencyCode();
        }

        return $order;
    }


    /**
     * Returns the Country of the current Quote
     *
     * @return void
     */
    public function getCountry()
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();

        return strtolower($quote->getBillingAddress()->getCountry());
    }

    /**
     * Returns the Mode of the payment method coded passed as argument.
     *
     * @param mixed $methodName
     * @return void
     */
    private function getMethodMode($methodName)
    {
        $modeCode = intval(Mage::getStoreConfig($methodName . "/mode", Mage::app()->getStore()->getId()));

        if ($modeCode == 1) {
            return array('name' => "test", 'code' => $modeCode);
        } else {
            if ($modeCode == 2) {
                return array('name' => "sandbox", 'code' => $modeCode);
            } else {
                return array('name' => "live", 'code' => $modeCode);
            }
        }
    }

    private function isSoapCompatible($path)
    {
        $storeId = Mage::app()->getStore()->getId();
        $fullPath = "afterpay/afterpay_" . $path;
        return (Mage::getStoreConfig($fullPath . "/test_password", $storeId) ||
                Mage::getStoreConfig($fullPath . "/live_password", $storeId)) &&
                Mage::getStoreConfig($fullPath . "/portfolio_id", $storeId) &&
                (bool)intval(Mage::getStoreConfig($fullPath . "/active", $storeId));
    }

    /**
     * Returns a list of local payment methods (Only Enabled ones) by the specific country.
     *
     * @param mixed $country
     * @return void
     */
    private function getLocalMethodsByCountry($country)
    {
        $localMethods = array();
        $storeId = Mage::app()->getStore()->getId();
        $paymentMethodOptions = [
            "_digital_invoice",
            "_digital_invoice_rest",
            "_direct_debit",
            "_direct_debit_rest",
            "_installment_payment",
            "_installment_payment_rest",
            "_business",
            "_business_rest",
            "_payinx_rest"
        ];

        foreach ($paymentMethodOptions as $option) {
            $methodName = 'afterpay_' . $country . $option;
            $configPath = "afterpay/afterpay_" . $methodName;
            $isActive = intval(Mage::getStoreConfig($configPath . "/active", $storeId));

            if ($isActive) {
                $mode = $this->getMethodMode($configPath);
                $localMethods[$methodName] = [
                    "name" => $methodName,
                    "modus" => $mode['name'],
                    "modusCode" => $mode['code'],
                    "authorization" => [
                        "apiKey" => Mage::getStoreConfig($configPath . "/{$mode['name']}_merchant_id", $storeId)
                    ]
                ];
            }
        }

        return $localMethods;
    }

    /**
     * Format the payment method received from the API
     *
     * @param mixed $method
     * @param mixed $country
     * @return void
     */
    private function getRemoteMethod($method, $country)
    {
        $paymentMethod = (array)$method;
        $paymentMethod['legalInfo'] = (array)$paymentMethod['legalInfo'];
        $paymentMethodCode = 'afterpay_' . $country . '_';

        if ($paymentMethod['type'] == "Installment") {
            $paymentMethod['installment'] = (array)$paymentMethod['installment'];
            $paymentMethod['directDebit'] = (array)$paymentMethod['directDebit'];
            $paymentMethodCode .= 'installment_payment';

            return [
                "method" => $paymentMethod,
                "installment" => $paymentMethod['installment'],
                "code" => $paymentMethodCode,
                "type" => strtolower($paymentMethod['type'])
            ];
        } else {
            if ($paymentMethod['type'] == "Invoice") {
                if (isset($paymentMethod['directDebit'])) {
                    $paymentMethod['directDebit'] = (array)$paymentMethod['directDebit'];
                    $paymentMethodCode .= 'direct_debit';
                } else {
                    $paymentMethodCode .= 'digital_invoice';
                }
                // Add _rest only for Netherlands
                if (strpos($paymentMethodCode, '_nl_')) {
                    $paymentMethodCode .= '_rest';
                }
            }
            if ($paymentMethod['type'] == "PayinX") {
                $paymentMethodCode .= 'payinx_rest';
            }
        }
        return [
            "method" => $paymentMethod,
            "code" => $paymentMethodCode,
            "type" => strtolower($paymentMethod['type'])
        ];
    }

    /**
     * Process received payment methos, and validate against local payment methods
     *
     * @param mixed $methods
     * @param mixed $localMethods
     * @param mixed $country
     * @return void
     */
    private function processPaymentMethods($methods, $localMethods, $country)
    {
        $remotePaymentMethods = array();
        $installmentPlans = array();

        foreach ($methods as &$paymentMethod) {
            $remoteMethod = $this->getRemoteMethod($paymentMethod, $country);
            $type = $remoteMethod['type'];
            $allowedTypes = array('installment', 'invoice', 'payinx');

            if (!in_array($type, $allowedTypes)) {
                continue;
            }
            $remotePaymentMethods[$remoteMethod['code']] = $remoteMethod['method'];

            if ($remoteMethod['type'] == 'installment') {
                $installmentPlans[] = $remoteMethod['installment'];
            }
        }

        if (count($installmentPlans) > 0) {
            $remotePaymentMethods[$remoteMethod['code']]['plans'] = $installmentPlans;
        }
        $validMethods = $this->getValidMethods($remotePaymentMethods, $localMethods);

        return array(
            'installment_plans' => $installmentPlans,
            'valid_methods' => $validMethods,
        );
    }

    /**
     * Requests the Available payment Methods to the API
     *
     * @param mixed $defaultCountry
     * @return void
     */
    public function requestAvailablePaymentMethods($defaultCountry = null)
    {
        $country = $defaultCountry ?: $this->getCountry();

        if (!$country) {
            return array();
        }
        $responseProcessor = $this->getResponseProcessor();
        $api = $this->getApiModel();
        $data = $this->getAvailablePaymentOrder();

        Mage::dispatchEvent(
            'afterpay_available_payment_methods_request',
            array('request' => $this, 'data' => $data)
        );

        $localMethods = (array)$this->getLocalMethodsByCountry($country);
        $currentRequestId = sha1($this->getCountry() . json_encode($data) . json_encode(array_keys($localMethods)));
        $previousRequestId = Mage::getModel('checkout/session')->getData('afterpay_previous_request_id');

        if ($previousRequestId === $currentRequestId) {
            return Mage::getModel('checkout/session')->getData('afterpay_payment_available');
        }
        
        $remotePaymentMethods = array(); // DHH FIX

        foreach ($localMethods as $localMethod) {
            /**
             * Skip the Request if the method is in test mode because it is a SOAP mode.
             *
             * TODO: Revise this later for REST requests with test mode
             */
            if ($localMethod['modus'] == 'test') {
                continue;
            }
            $variables = array('merchantId' => $localMethod['authorization']['apiKey']);
            $api->setVars($variables)->setTestMode($localMethod['modusCode']);
            $response = (array)$api->execute($data);
            $remotePaymentMethods = array();

            if ($response['return']->paymentMethods) {
                try {
                    $remotePaymentMethods = $responseProcessor->setResponse($response)
                        ->setRequest($this)
                        ->processResponse();
                } catch (Exception $exception) {
                    $this->logException($exception);
                    throw $exception;
                }
            } else {
              // DHH CORE HACK: Add log message and fail gracefully
              Mage::log("Missing required property 'paymentMethods' in API reponse: ".var_export($response, true), Zend_Log::ERR, "system.log", true);
              return [];
            }

            if (count($remotePaymentMethods) > 0) {
                $processedMethods = $this->processPaymentMethods($remotePaymentMethods, $localMethods, $country);
                $this->updateSessionVariables($processedMethods['installment_plans'],
                    $processedMethods['valid_methods'], $currentRequestId);
                return $processedMethods['valid_methods'];
            }
        }

        $processedMethods = $this->processPaymentMethods($remotePaymentMethods, $localMethods, $country);
        // Disable this for testing APV assignment
        Mage::getModel('checkout/session')->setData('afterpay_payment_available', $processedMethods['valid_methods']);
        return $processedMethods['valid_methods'];
    }

    /**
     * Store Payment methods and Installment plans in the session Variable
     *
     * @param mixed $installmentPlans
     * @param mixed $remotePaymentMethods
     * @param mixed $lastRequestId
     * @return void
     */
    private function updateSessionVariables($installmentPlans, $remotePaymentMethods, $lastRequestId)
    {
        Mage::getModel('checkout/session')->setData('afterpay_installment_plans', $installmentPlans);
        Mage::getModel('checkout/session')->setData('afterpay_payment_available', $remotePaymentMethods);
        Mage::getModel('checkout/session')->setData('afterpay_previous_request_id', $lastRequestId);
    }

    /**
     * Returns a list of payment methods which are active in both local (database) and remote (Api)
     *
     * @param mixed $remoteMethods
     * @param mixed $localMethods
     * @return void
     */
    private function getValidMethods($remoteMethods, $localMethods, $byPassSoap = false)
    {
        $validPaymentMethods = array();

        foreach ($localMethods as $localMethodCode => $localMethod) {
            if (array_key_exists($localMethodCode, $remoteMethods) || $this->isSoapCompatible($localMethodCode)) {
                $validPaymentMethods[$localMethodCode] = $remoteMethods[$localMethodCode];
            }
        }

        return $validPaymentMethods;
    }

    /**
     * @return false|Afterpay_Afterpay_Response_Payment_Methods_Available
     */
    private function getResponseProcessor()
    {
        return Mage::getModel('afterpay/response_payment_methods_available');
    }

    /**
     * @return false|Afterpay_Afterpay_Model_Api_Payment_Methods_Available
     */
    private function getApiModel()
    {
        return Mage::getModel('afterpay/api_payment_methods_available');
    }
}
