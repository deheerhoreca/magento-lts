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
 *
 * */

require_once Mage::getBaseDir('lib') . '/Afterpay/vendor/autoload.php';

class Afterpay_Afterpay_Block_Onepage_Payment_Methods extends Mage_Checkout_Block_Onepage_Payment_Methods
{
    protected $validPaymentMethods = array();

    /**
     * This is the method responsible for checking which method can be used.
     *
     * @param  mixed $method
     * @return void
     */
    protected function _canUseMethod($method)
    {
        $backendEnabled = $method && $method->canUseCheckout() && parent::_canUseMethod($method);

        $methodCode = $method->getCode();

        if(strpos($methodCode,'afterpay') !== false) {
            return $backendEnabled && array_key_exists($methodCode, $this->validPaymentMethods);
        } else {
            return $backendEnabled;
        }
    }

    /**
     * We are overriding the default getMethods in order to include the available payment methods functionality
     * Checking the payment availability in the API response
     * If a payment method is not avialble this function will exclude it from the native mehthods
     *
     * @return array
     */
    public function getMethods()
    {
        $methods = $this->getData('methods');

        if ($methods === null) {

            // Start the process of geting payment methods
            $availablePaymentMethodsRequestModel = $this->getAvailablePaymentMethodsRequestModel();
            $this->validPaymentMethods = $availablePaymentMethodsRequestModel->requestAvailablePaymentMethods();

            $quote = $this->getQuote();
            $store = $quote ? $quote->getStoreId() : null;
            $methods = array();

            foreach ($this->helper('payment')->getStoreMethods($store, $quote) as $method) {
                if ($this->_canUseMethod($method) && $method->isApplicableToQuote(
                    $quote,
                    Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL
                )) {
                    $this->_assignMethod($method);
                    $methods[] = $method;
                }
            }
            $this->setData('methods', $methods);
        }
        return $methods;
    }

    /**
     * getAvailablePaymentMethodsRequestModel
     *
     * @return Afterpay_Afterpay_Model_Request_Payment_Methods_Available
     */
    public function getAvailablePaymentMethodsRequestModel() {
        return Mage::getModel('afterpay/request_payment_methods_available');
    }
}
