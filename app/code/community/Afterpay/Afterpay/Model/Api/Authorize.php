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

class Afterpay_Afterpay_Model_Api_Authorize extends Afterpay_Afterpay_Model_Api_Abstract
{
    protected $_isB2B = false;

    public function getIsB2B()
    {
        return $this->_isB2B;
    }

    public function setIsB2B($isB2B = false)
    {
        $this->_isB2B = $isB2B;
        return $this;
    }

    public function authorizationRequest()
    {
        if ($this->_isB2B) {
            $this->_addAfterPayB2BOrder();
        } else {
            $this->addDirectDebitPaymentData();
            $this->addInstallmentPaymentData();
            $this->_addAfterPayB2COrder();
        }
        return $this->doRequest();
    }

    private function setOrder($orderType) {
        $this->_afterpay->set_order(
            $this->addPlugingProviderData($this->_afterpay_order),
            $orderType
        );
    }


    protected function _addAfterPayB2BOrder()
    {
        // Set the billing address
        $this->_addBillToAddress();

        // Set the shipping address
        $this->_addShipToAddress();

        // Set the company information
        $this->_addCompany();

        // Add the order lines
        $this->_addOrderLines();

        // Setup the AfterPay Submerchant ID if available
        if (isset($this->_vars['apiMerchantId'])) {
            $this->_afterpay_order['apiMerchantId'] = $this->_vars['apiMerchantId'];
        }

        // Set up the additional information
        $this->_afterpay_order['ordernumber'] = $this->_vars['orderNumber'];
        $this->_afterpay_order['currency'] = $this->_currency;
        $this->_afterpay_order['ipaddress'] = $this->_vars['ipAddress'];

        // Set the order information to the AfterPay Object
        $this->setOrder('B2B');
    }

    protected function _addAfterPayB2COrder()
    {
        // Set the billing address
        $this->_addBillToAddress();

        // Set the shipping address
        $this->_addShipToAddress();

        // Add the order lines
        $this->_addOrderLines();

        // Setup the AfterPay Submerchant ID if available
        if (isset($this->_vars['apiMerchantId'])) {
            $this->_afterpay_order['apiMerchantId'] = $this->_vars['apiMerchantId'];
        }

        // Set up the additional information
        $this->_afterpay_order['ordernumber'] = $this->_vars['orderNumber'];
        $this->_afterpay_order['bankaccountnumber'] = $this->_vars['bankAccountNumber'];
        $this->_afterpay_order['currency'] = $this->_currency;
        $this->_afterpay_order['ipaddress'] = $this->_vars['ipAddress'];
        $this->_afterpay_order['profileTrackingId'] = $this->_vars['billing']['session'];

        // Set the order information to the AfterPay Object
        $this->setOrder('B2C');
    }

    protected function _addBillToAddress()
    {
        $this->_afterpay_order['billtoaddress']['city']                  = $this->_vars['billingAddress']['city'];
        $this->_afterpay_order['billtoaddress']['housenumber']           = $this->_vars['billingAddress']['houseNumber'];
        $this->_afterpay_order['billtoaddress']['housenumberaddition']   = $this->_vars['billingAddress']['houseNumberAddition'];
        $this->_afterpay_order['billtoaddress']['isocountrycode']        = $this->_vars['billingAddress']['isoCountryCode'];
        $this->_afterpay_order['billtoaddress']['postalcode']            = $this->_vars['billingAddress']['postalCode'];
        $this->_afterpay_order['billtoaddress']['streetname']            = $this->_vars['billingAddress']['streetName'];

        $this->_afterpay_order['billtoaddress']['referenceperson']['dob']         = $this->_vars['billing']['dob'];
        $this->_afterpay_order['billtoaddress']['referenceperson']['email']       = $this->_vars['billing']['emailAddress'];
        $this->_afterpay_order['billtoaddress']['referenceperson']['gender']      = $this->_vars['billing']['gender'];
        $this->_afterpay_order['billtoaddress']['referenceperson']['initials']    = $this->_vars['billing']['initials'];
        $this->_afterpay_order['billtoaddress']['referenceperson']['isolanguage'] = $this->_vars['billing']['isoLanguage'];
        $this->_afterpay_order['billtoaddress']['referenceperson']['firstname']   = $this->_vars['billing']['firstname'];
        $this->_afterpay_order['billtoaddress']['referenceperson']['lastname']    = $this->_vars['billing']['lastname'];
        $this->_afterpay_order['billtoaddress']['referenceperson']['phonenumber'] = $this->_vars['billing']['phonenumber'];
        $this->_afterpay_order['billtoaddress']['referenceperson']['ssn']         = $this->_vars['billing']['ssn'];
    }

    protected function _addShipToAddress()
    {
        if (is_array($this->_vars) && isset($this->_vars['isVirtual']) && $this->_vars['isVirtual'] == 1) {
            // if an order is virtual then use billing address as shipping address
            $this->_afterpay_order['shiptoaddress'] = $this->_afterpay_order['billtoaddress'];
            return;
        }

        $this->_afterpay_order['shiptoaddress']['city']                  = $this->_vars['shippingAddress']['city'];
        $this->_afterpay_order['shiptoaddress']['housenumber']           = $this->_vars['shippingAddress']['houseNumber'];
        $this->_afterpay_order['shiptoaddress']['housenumberaddition']   = $this->_vars['shippingAddress']['houseNumberAddition'];
        $this->_afterpay_order['shiptoaddress']['isocountrycode']        = $this->_vars['shippingAddress']['isoCountryCode'];
        $this->_afterpay_order['shiptoaddress']['postalcode']            = $this->_vars['shippingAddress']['postalCode'];
        $this->_afterpay_order['shiptoaddress']['streetname']            = $this->_vars['shippingAddress']['streetName'];

        $this->_afterpay_order['shiptoaddress']['referenceperson']['dob']         = $this->_vars['shipping']['dob'];
        $this->_afterpay_order['shiptoaddress']['referenceperson']['email']       = $this->_vars['shipping']['emailAddress'];
        $this->_afterpay_order['shiptoaddress']['referenceperson']['gender']      = $this->_vars['shipping']['gender'];
        $this->_afterpay_order['shiptoaddress']['referenceperson']['initials']    = $this->_vars['shipping']['initials'];
        $this->_afterpay_order['shiptoaddress']['referenceperson']['isolanguage'] = $this->_vars['shipping']['isoLanguage'];
        $this->_afterpay_order['shiptoaddress']['referenceperson']['firstname']   = $this->_vars['shipping']['firstname'];
        $this->_afterpay_order['shiptoaddress']['referenceperson']['lastname']    = $this->_vars['shipping']['lastname'];
        $this->_afterpay_order['shiptoaddress']['referenceperson']['phonenumber'] = $this->_vars['shipping']['phonenumber'];
        $this->_afterpay_order['shiptoaddress']['referenceperson']['ssn']         = $this->_vars['shipping']['ssn'];
    }

    protected function _addOrderLines()
    {
        foreach ($this->_vars['orderLines'] as $line) {
            if (empty($line)) {
                continue;
            }
            if (!isset($line['vatAmount'])) {
                $line['vatAmount'] = 0;
            }
            if (!isset($line['groupId'])) {
                $line['groupId'] = null;
            }


            $name           = $line['articleDescription'];
            $sku            = $line['articleId'];
            $qty            = $line['quantity'];
            $price          = $line['unitPrice'];
            $tax_category   = $line['vatCategory'];
            $vat_amount     = $line['vatAmount'];
            $product_url    = isset($line['productUrl']) ? $line['productUrl'] : '';
            $image_url      = isset($line['imageUrl']) ? $line['imageUrl'] : '';
            $group_id       = isset($line['groupId']) ? $line['groupId'] : '';

            $this->_afterpay->create_order_line(
                $sku,
                $name,
                $qty,
                $price,
                $tax_category,
                $vat_amount,
                null,
                null,
                $product_url,
                $image_url,
                $group_id
            );
        }
    }
    
    protected function _addCompany()
    {
        $this->_afterpay_order['company']['cocnumber'] = $this->_vars['company']['cocNumber'];
        $this->_afterpay_order['company']['companyname'] = $this->_vars['company']['companyName'];
    }

    private function addInstallmentPaymentData()
    {
        if ($this->isInstallmentPaymentMethod()) {
            $this->_afterpay_order['installment'] = array(
                'profileNo' => $this->_vars['profileNo']
            );

            if (isset($this->_vars['bankCode'])) {
                $this->_afterpay_order['installment']['bankCode'] = $this->_vars['bankCode'];
            }
            if (isset($this->_vars['bankAccountNumber'])) {
                $this->_afterpay_order['installment']['bankAccount'] = $this->_vars['bankAccountNumber'];
            }
        }
    }

    private function addDirectDebitPaymentData()
    {
        if ($this->isDirectDebitPaymentMethod()) {
            if (isset($this->_vars['bankCode'])) {
                $this->_afterpay_order['directDebit']['bankCode'] = $this->_vars['bankCode'];
            }
            if (isset($this->_vars['bankAccountNumber'])) {
                $this->_afterpay_order['directDebit']['bankAccount'] = $this->_vars['bankAccountNumber'];
            }
        }
    }

    /**
     * @return bool
     */
    private function isInstallmentPaymentMethod()
    {
        return isset($this->_vars['profileNo']);
    }

    private function isDirectDebitPaymentMethod()
    {
        return isset($this->_vars['bankAccountNumber']) && !isset($this->_vars['profileNo']);
    }
}
