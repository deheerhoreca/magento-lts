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

class Afterpay_Afterpay_Model_Request_Installmentplans_Lookup extends Afterpay_Afterpay_Model_Request_Ordermanagement
{
    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $quote;

    /**
     * Query Afterpay service for installment option plans
     *
     * @param Mage_Payment_Model_Info $paymentInfo
     * @return Afterpay_Afterpay_Model_Portfolios_Installment_Option_Plan[]|false
     * @throws Varien_Exception
     */
    public function sendLookupRequest()
    {
        $responseProcessor = $this->getResponseProcessor();
        $api = $this->getApiModel();

        $variables = $this->shopVariables();
        $api->setVars($variables)->setTestMode($this->getTestMode());
        $data = array(
            'amount' => $this->getCartTotal(),
            'currency' => $this->getCartCurrency()
        );
        Mage::dispatchEvent(
            'afterpay_lookup_request_addcustomvars',
            array('request' => $this, 'data' => $data)
        );

        try {
            $response = $api->execute($data);
            return $responseProcessor->setResponse($response)->setRequest($this)
                ->processResponse();
        } catch (Exception $exception) {
            $this->logException($exception);
            return array();
        }
    }

    /**
     * @return float
     */
    private function getCartTotal()
    {
        return $this->getQuote()->getGrandTotal();
    }

    /**
     * @return string
     */
    private function getCartCurrency()
    {
        return $this->getQuote()->getQuoteCurrencyCode();
    }

    /**
     * @return false|Afterpay_Afterpay_Model_Response_Installmentplans_Lookup
     */
    private function getResponseProcessor()
    {
        return Mage::getModel('afterpay/response_installmentplans_lookup');
    }

    /**
     * @return false|Afterpay_Afterpay_Model_Api_Installmentplans_Lookup
     */
    private function getApiModel()
    {
        return Mage::getModel('afterpay/api_installmentplans_lookup');
    }
}
