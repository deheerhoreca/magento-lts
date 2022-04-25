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

class Afterpay_Afterpay_Model_Response_Validate_Bankaccount extends Afterpay_Afterpay_Model_Response_Abstract
{
    /**
     * Validate error messages and throw to be caught in
     * @see \Afterpay_Afterpay_Model_Portfolios_Installment_PaymentMethod::validate()
     *
     * @return bool
     * @throws Exception
     */
    protected function _accept()
    {
        $response = $this->_response->return;
        if (!$response->isValid) {
            $errorMsg = 'Bank account field validation error.';

            if (isset($response->riskCheckMessages[0])) {
                $riskMessage = $response->riskCheckMessages[0];
                // make sure to allow translating response codes
                $errorMsg = sprintf(
                    '%s: %s',
                    $this->__($riskMessage->fieldReference),
                    $this->__($riskMessage->message)
                );
            }

            throw new Zend_Validate_Exception($this->__($errorMsg));
        }

        return true;
    }

    protected function _pending()
    {
        return false;
    }

    protected function _validation()
    {
        return false;
    }

    /**
     * Validate error messages and throw to be caught in
     * @see \Afterpay_Afterpay_Model_Portfolios_Installment_PaymentMethod::validate()
     *
     * @return void
     * @throws Zend_Validate_Exception
     */
    protected function _error()
    {
        $response = $this->_response->return;
        $errorMsg = 'Please check bank account details.';

        if (isset($response->messages[0])) {
            $message = $response->messages[0];
            $errorMsg = $message->message;
        }
        throw new Zend_Validate_Exception($this->__($errorMsg));
    }

    protected function _failed()
    {
        return false;
    }

    /**
     * Shortcut for translations
     *
     * @param $str
     * @return mixed
     */
    private function __($str)
    {
        return $this->getHelper()->__($str);
    }
}
