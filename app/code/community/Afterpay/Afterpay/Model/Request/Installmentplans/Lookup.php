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
        // Get the Installment plans from the checkout/session to avoid sendind a second request to the API
        $availableInstallmentPlans = Mage::getModel('checkout/session')->getData('afterpay_installment_plans');

        $plans = array();

        foreach ($availableInstallmentPlans as $planDetails) {
            $installmentPlan = Mage::getModel('afterpay/portfolios_installment_option_plan');

            foreach ($planDetails as $name => $value) {
                $installmentPlan->setDataUsingMethod($name, $value);
            }

            $plans[] = $installmentPlan;
        }

        $data = array(
            'amount' => $this->getCartTotal(),
            'currency' => $this->getCartCurrency(),
            'installment_plans' => $plans
        );

        Mage::dispatchEvent(
            'afterpay_installment_plans_lookup',
            array('request' => $this, 'data' => $data)
        );

        return $plans;

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
}
