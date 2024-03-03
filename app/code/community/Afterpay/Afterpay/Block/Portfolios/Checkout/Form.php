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

class Afterpay_Afterpay_Block_Portfolios_Checkout_Form extends Mage_Payment_Block_Form
{
    public $shopName                         = '';
    public $maxOrderAmountNewCustomers       = '&#8364;';
    public $maxOrderAmountReturningCustomers = '&#8364;';
    public $anchorClose                      = '</a>';
    public $privacyStatementUrl              = '<a href="http://www.afterpay.nl/page/privacy-statement" target="_blank">';
    public $consumerContactUrl               = '<a href="http://www.afterpay.nl/page/consument-contact" target="_blank">';
    public $consumerPageUrl                  = '<a href="http://www.afterpay.nl/page/consument" target="_blank">';
    public $paymentConditionsUrl             = '<a href="http://www.afterpay.nl/page/consument-betalingsvoorwaarden" target="_blank" style="margin-top:0; float:none; margin-left:0">';
    public $country                          = 'nlnl';

    protected $_template = 'Afterpay/Afterpay/portfolios/checkout/form.phtml';

    public function __construct()
    {
        parent::__construct();
        
        // If IWD or OSC is used then use different form template
        if (Mage::helper('core')->isModuleEnabled('IWD_Opc')) {
            $this->setTemplate('Afterpay/Afterpay/portfolios/checkout/form-iwd.phtml');
        } elseif (Mage::helper('core')->isModuleEnabled('Idev_OneStepCheckout')) {
            $this->setTemplate('Afterpay/Afterpay/portfolios/checkout/form-idev.phtml');
        }
    }

    public function setBlockData()
    {
        $shopName = Mage::getStoreConfig('general/store_information/name', Mage::app()->getStore()->getId());
        $this->shopName = $shopName ? $shopName : 'deze webshop';

        $newCustomerAmount = Mage::getStoreConfig(
            'afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_max_amount_new_customers',
            Mage::app()->getStore()->getId()
        );

        $returningCustomerAmount = Mage::getStoreConfig(
            'afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_max_amount',
            Mage::app()->getStore()->getId()
        );

        // DHH CORE HACK -- PHP 8.1
        $this->maxOrderAmountNewCustomers .= round((int) $newCustomerAmount, 2);
        $this->maxOrderAmountReturningCustomers .= round((int) $returningCustomerAmount, 2);
        // $this->maxOrderAmountNewCustomers .= round($newCustomerAmount, 2);
        // $this->maxOrderAmountReturningCustomers .= round($returningCustomerAmount, 2);

        $this->country = Mage::getStoreConfig(
            'afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_country',
            Mage::app()->getStore()->getId()
        );

        // Check if portfolio is for Belgium (Dutch).
        if ($this->country == 'benl') {
            $this->privacyStatementUrl             = '<a href="https://www.afterpay.be/nl/klantenservice/privacy-statement/" target="_blank">';
            $this->consumerContactUrl            = '<a href="https://www.afterpay.be/nl/klantenservice/vraag-en-antwoord/" target="_blank">';
            $this->consumerPageUrl                = '<a href="https://www.afterpay.be/nl/klantenservice/vraag-en-antwoord/" target="_blank">';
            $this->paymentConditionsUrl            = '<a href="https://www.afterpay.be/nl/klantenservice/betalingsvoorwaarden/" target="_blank">';
        }

        // Check if portfolio is for Belgium (French).
        if ($this->country == 'befr') {
            $this->privacyStatementUrl             = '<a href="https://www.afterpay.be/fr/footer/a-propos-d-afterpay/declaration-de-confidentialite" target="_blank">';
            $this->consumerContactUrl            = '<a href="https://www.afterpay.be/fr/footer/payer-avec-afterpay/service-a-la-clientele" target="_blank">';
            $this->consumerPageUrl                = '<a href="https://www.afterpay.be/fr/footer/payer-avec-afterpay/service-a-la-clientele" target="_blank">';
            $this->paymentConditionsUrl            = '<a href="https://www.afterpay.be/fr/footer/payer-avec-afterpay/conditions-de-paiement" target="_blank">';
        }

        // Check if portfolio is for Germany.
        if ($this->country == 'dede') {
            $this->privacyStatementUrl             = '<a href="	https://documents.myafterpay.com/privacy-statement/de_de/" target="_blank">';
            $this->consumerContactUrl            = '<a href="https://www.afterpay.de/kontakt" target="_blank">';
            $this->consumerPageUrl                = '<a href="https://www.afterpay.de/fragen-antworten/" target="_blank">';
            $this->paymentConditionsUrl            = '<a href="https://documents.myafterpay.com/consumer-terms-conditions/de_de/" target="_blank">';
        }

        // Check if portfolio is for Austria.
        if ($this->country == 'atde') {
            $this->privacyStatementUrl             = '<a href="https://documents.myafterpay.com/privacy-statement/de_at/" target="_blank">';
            $this->consumerContactUrl            = '<a href="https://www.afterpay.de/kontakt" target="_blank">';
            $this->consumerPageUrl                = '<a href="https://www.afterpay.de/fragen-antworten/" target="_blank">';
            $this->paymentConditionsUrl            = '<a href="https://documents.myafterpay.com/consumer-terms-conditions/de_at/" target="_blank">';
        }

        // Check if portfolio is for Switzerland.
        if ($this->country == 'chde') {
            $this->privacyStatementUrl             = '<a href="https://documents.myafterpay.com/privacy-statement/de_ch/" target="_blank">';
            $this->consumerContactUrl            = '<a href="https://www.afterpay.de/kontakt" target="_blank">';
            $this->consumerPageUrl                = '<a href="https://www.afterpay.de/fragen-antworten/" target="_blank">';
            $this->paymentConditionsUrl            = '<a href="https://documents.myafterpay.com/consumer-terms-conditions/de_ch/" target="_blank">';
        }

        // Check if portfolio is for Sweden.
        if ($this->country == 'sesv') {
            $this->privacyStatementUrl             = '<a href="https://documents.myafterpay.com/privacy-statement/sv_se/" target="_blank">';
            $this->paymentConditionsUrl            = '<a href="https://documents.myafterpay.com/consumer-terms-conditions/sv_se/" target="_blank">';
        }

        // Check if portfolio is for Finland.
        if ($this->country == 'fifi') {
            $this->privacyStatementUrl             = '<a href="https://documents.myafterpay.com/privacy-statement/fi_fi/" target="_blank">';
            $this->paymentConditionsUrl            = '<a href="https://documents.myafterpay.com/consumer-terms-conditions/fi_fi/" target="_blank">';
        }

        // Check if portfolio is for Denmark.
        if ($this->country == 'dkda') {
            $this->privacyStatementUrl             = '<a href="https://documents.myafterpay.com/privacy-statement/da_dk/" target="_blank">';
            $this->paymentConditionsUrl            = '<a href="https://documents.myafterpay.com/consumer-terms-conditions/da_dk/" target="_blank">';
        }

        // Check if portfolio is for Norway.
        if ($this->country == 'nonb') {
            $this->privacyStatementUrl             = '<a href="https://documents.myafterpay.com/privacy-statement/no_no/" target="_blank">';
            $this->paymentConditionsUrl            = '<a href="https://documents.myafterpay.com/consumer-terms-conditions/no_no/" target="_blank">';
        }

        if (Mage::getStoreConfig('afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_type') == 'B2B') {
            $this->paymentConditionsUrl            = '<a href="https://www.afterpay.nl/nl/klantenservice/betalingsvoorwaarden-b2b/" target="_blank">';
        }
    }

    public function getMethodLabelAfterHtml()
    {
	// DHH CORE HACK
        $labelAfterHtml = $this->getMethod()->getTitle().'<img src="//static.pay.nl/payment_profiles/20x20/2561.png" style="padding-right:10px;" alt="AfterPay" class="v-middle"/>';

        if ($this->getMethod()->getFootnote()) {
            $labelAfterHtml .= '<span class = \'afterpay_paymentmethod_label afterpay_paymentmethod_label_'
                        . $this->getMethod()->getCode()
                        . '\'>' . $this->getMethod()->getFootnote() . '</span>';
        }

        // If IWD is used then use no logo
        if (Mage::helper('core')->isModuleEnabled('IWD_Opc') || Mage::helper('core')->isModuleEnabled('Idev_OneStepCheckout')) {
            $labelAfterHtml = $this->getMethod()->getTitle();
        }

        return $labelAfterHtml;
    }

    public function hasMethodTitle()
    {
        return true;
    }

    public function getMethodTitle()
    {
        if (Mage::helper('core')->isModuleEnabled('Idev_OneStepCheckout')) {
            return $this->getMethod()->getTitle();
        } else {
            return '';
        }
    }

    public function isB2B()
    {
        if (Mage::getStoreConfig('afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_type') == 'B2B') {
            return true;
        }
        return false;
    }

    public function showBankaccount()
    {
        if (Mage::getStoreConfig('afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_showbankaccount') == '1') {
            return true;
        }
        return false;
    }

    public function showGender()
    {
        if (Mage::getStoreConfig('afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_showgender') == '1') {
            return true;
        }
        return false;
    }

    public function showPhonenumber()
    {
        if (Mage::getStoreConfig('afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_showphonenumber') == '1') {
            return true;
        }
        return false;
    }

    public function showSsn()
    {
        if (Mage::getStoreConfig('afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_showssn') == '1') {
            return true;
        }
        return false;
    }

    public function useProfiletracking()
    {
        if (Mage::getStoreConfig('afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_useprofiletracking') == '1') {
            return true;
        }
        return false;
    }

    public function getProfiletrackingClientId()
    {
        return Mage::getStoreConfig('afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_profiletrackingclientid');
    }

    public function showDob()
    {
        if (Mage::getStoreConfig('afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_showdob') == '1') {
            return true;
        }
        return false;
    }

    public function getCompany()
    {
        $billingAddress = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress();
        
        return $billingAddress->getCompany();
    }

    /**
     * @return bool
     */
    public function showBankCode()
    {
        return Mage::getStoreConfigFlag('afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_showbankcode');
    }
}
