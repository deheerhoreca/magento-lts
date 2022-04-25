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

class Afterpay_Afterpay_Block_Portfolios_Checkout_Installment_Form extends Afterpay_Afterpay_Block_Portfolios_Checkout_Form
{
    protected $_template = 'Afterpay/Afterpay/portfolios/checkout/installment/form.phtml';

    /**
     * @return bool
     */
    public function showBankCode()
    {
        return Mage::getStoreConfigFlag('afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_showbankcode');
    }

    /**
     * @return string
     */
    public function getInstallmentDescription()
    {
        return Mage::getStoreConfig('afterpay/afterpay_' . $this->getMethod()->getCode() . '/installment_description');
    }

    /**
     * @return float
     */
    public function getGrandTotal()
    {
        $helper = Mage::helper('checkout');
        $quote = Mage::getSingleton('checkout/cart')->getQuote();

        return $helper->formatPrice($quote->getGrandTotal());
    }

    /**
     * @return Afterpay_Afterpay_Model_Portfolios_Installment_Option_Plan[]
     */
    public function getInstallmentOptions()
    {
        /* @var Afterpay_Afterpay_Model_Portfolios_Installment_PaymentMethod $method */
        $method = $this->getMethod();
        return $method->loadInstallmentOptions();
    }

    /**
     * @return string
     */
    public function getAuthorizationNotice()
    {
        return Mage::getStoreConfig('afterpay/afterpay_' . $this->getMethod()->getCode() . '/authorization_notice');
    }
}
