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

class Afterpay_Afterpay_Model_Response_Payment_Methods_Available extends Afterpay_Afterpay_Model_Response_Abstract
{

    protected function _construct()
    {
        $this->setHelper(Mage::helper('afterpay'));
    }
    
    /**
     * _accept
     *
     * @return void
     */
    protected function _accept()
    {
        $response = json_decode(json_encode($this->_response), true);
        if (!isset($response['return']['paymentMethods'])) {
            $errorMsg = 'Request did not return any payment method.';

            throw new Zend_Validate_Exception($this->__($errorMsg));
        }

        return (array)$response['return']['paymentMethods'];
    }

    /**
     * Process the response of the Available Payment Methods Request
     *
     * @return void
     */
    public function processResponse()
    {
        if (is_null($this->_response)) {
            Mage::throwException($this->__('No response was available'));
        }

        if ($this->_response === false) {
            $this->_debugEmail .= "An error occurred in building or sending the API request.. \n";
            return $this->_error();
        }

        $this->_debugEmail .= "Verified as authentic \n\n";

        $requiredAction = $this->_parseResponse();
        $this->_debugEmail .= 'Parsed response: ' . $requiredAction . "\n";
        $this->_debugEmail .= "Dispatching custom order processing event... \n";
        Mage::dispatchEvent(
            'afterpay_response_payment_methods_available',
            array(
                'model'         => $this,
                'order'         => $this->_order,
                'response'      => $this->_response,
            )
        );

        $return = $this->_requiredAction($requiredAction);

        $this->sendDebugEmail();

        return $return;
    }
    
    /**
     * _pending
     *
     * @return void
     */
    protected function _pending()
    {
        return false;
    }
    
    /**
     * _validation
     *
     * @return void
     */
    protected function _validation()
    {
        return false;
    }
    
    /**
     * _error
     *
     * @return void
     */
    protected function _error()
    {
        $response = $this->_response->return;
        $errorMsg = 'Please check your API credentials, selected country and connection.';

        if (isset($response->messages[0])) {
            $message = $response->messages[0];
            $errorMsg = $message->message;
        }
        throw new Zend_Validate_Exception($this->__($errorMsg));
    }
    
    /**
     * _failed
     *
     * @return void
     */
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
