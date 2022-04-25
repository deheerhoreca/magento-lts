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

class Afterpay_Afterpay_Model_Api_Refund extends Afterpay_Afterpay_Model_Api_Abstract
{
    public function refundRequest()
    {
        $this->_afterpay->set_ordermanagement('refund_partial');
        $this->_addInvoiceLines();
        $this->_afterpay_order['invoicenumber']         = $this->_vars['invoiceId'];
        $this->_afterpay_order['ordernumber']           = $this->_vars['orderNumber'];
        $this->_afterpay_order['creditinvoicenumber']   = $this->_vars['invoiceId'];
        $this->_afterpay_order['billtoaddress']['isocountrycode'] = $this->_country;

        // Setup the AfterPay Submerchant ID if available
        if (isset($this->_vars['apiMerchantId'])) {
            $this->_afterpay_order['apiMerchantId'] = $this->_vars['apiMerchantId'];
        }

        $this->_afterpay->set_order($this->_afterpay_order, 'OM');
        return $this->doRequest();
    }
    
    protected function _addInvoiceLines()
    {
        $invoiceLines = array();

        if (!array_key_exists('orderLines', $this->_vars)) {
            return false;
        }

        foreach ($this->_vars['orderLines'] as $line) {
            if (empty($line)) {
                continue;
            }

            $name         = preg_replace("/[^a-zA-Z0-9\_\-\s]/i", "", $line['articleDescription']);
            $sku          = $line['articleId'];
            $qty          = $line['quantity'];
            $price        = $line['unitPrice'];
            $tax_category = $line['vatCategory'];
            $vat_amount   = $line['vatAmount'];

            $this->_afterpay->create_order_line($sku, $name, $qty, $price, $tax_category, $vat_amount);
        }

        return $invoiceLines;
    }
}
