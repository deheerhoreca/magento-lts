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
 * @copyright   Copyright (c) 2011-2022 arvato Finance B.V.
 */

class Afterpay_Afterpay_Model_Api_Payment_Methods_Available extends Afterpay_Afterpay_Model_Api_Abstract
{    
    /**
     * Send the request to get the available payment methods
     *
     * @param  mixed $requestData
     * @return void
     */
    public function execute($requestData)
    {
        $this->_afterpay->setRest();
        $this->_afterpay->set_ordermanagement('available_payment_methods');
        $requestData = $this->addPlugingProviderData($requestData);
        $this->_afterpay->set_order($requestData, 'OM');

        return $this->doRequest();
    }
}
