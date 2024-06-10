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

class Afterpay_Afterpay_Block_Portfolios_Checkout_Form extends Mage_Payment_Block_Form
{
    public $shopName = '';
    public $maxOrderAmountNewCustomers = '&#8364;';
    public $maxOrderAmountReturningCustomers = '&#8364;';
    public $anchorClose = '</a>';
    public $privacyStatementUrl = '';
    public $consumerContactUrl = '<a href="http://www.afterpay.nl/page/consument-contact" target="_blank">';
    public $consumerPageUrl = '<a href="http://www.afterpay.nl/page/consument" target="_blank">';
    public $paymentConditionsUrl = '';
    public $country = 'nlnl';

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
        $this->shopName = $shopName ?: 'deze webshop';

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

        $this->merchantId = Mage::getStoreConfig(
            'afterpay/afterpay_' . $this->getMethod()->getCode() . '/api_merchant_id',
            Mage::app()->getStore()->getId()
        );

        $allowedLocales = [
            'atde',
            'benl',
            'dkda',
            'fifi',
            'dede',
            'nlnl',
            'nonb',
            'sesv',
            'chde'
        ];

        if (in_array($this->country, $allowedLocales)) {
            $this->privacyStatementUrl = $this->formatLink(
                'privacy_statement',
                $this->country,
                $this->merchantId
            );
            $this->paymentConditionsUrl = $this->formatLink(
                'terms_conditions',
                $this->country,
                $this->merchantId,
                $this->getMethod()->getCode()
            );
        } else {
            $this->paymentConditionsUrl = $this->formatLink(
                'terms_conditions',
                $this->country,
                $this->merchantId,
                $this->getMethod()->getCode()
            );
        }

        // Check if portfolio is for Belgium (Dutch).
        if ($this->country == 'benl') {
            $this->consumerContactUrl = '<a href="https://www.afterpay.be/nl/klantenservice/vraag-en-antwoord/" target="_blank">';
            $this->consumerPageUrl = '<a href="https://www.afterpay.be/nl/klantenservice/vraag-en-antwoord/" target="_blank">';
        }

        // Check if portfolio is for Belgium (French).
        if ($this->country == 'befr') {
            $this->consumerContactUrl = '<a href="https://www.afterpay.be/fr/footer/payer-avec-afterpay/service-a-la-clientele" target="_blank">';
            $this->consumerPageUrl = '<a href="https://www.afterpay.be/fr/footer/payer-avec-afterpay/service-a-la-clientele" target="_blank">';
        }

        // Check if portfolio is for Germany.
        if ($this->country == 'dede') {
            $this->consumerContactUrl = '<a href="https://www.afterpay.de/kontakt" target="_blank">';
            $this->consumerPageUrl = '<a href="https://www.afterpay.de/fragen-antworten/" target="_blank">';
        }

        // Check if portfolio is for Austria.
        if ($this->country == 'atde') {
            $this->consumerContactUrl = '<a href="https://www.afterpay.de/kontakt" target="_blank">';
            $this->consumerPageUrl = '<a href="https://www.afterpay.de/fragen-antworten/" target="_blank">';
        }

        // Check if portfolio is for Switzerland.
        if ($this->country == 'chde') {
            $this->consumerContactUrl = '<a href="https://www.afterpay.de/kontakt" target="_blank">';
            $this->consumerPageUrl = '<a href="https://www.afterpay.de/fragen-antworten/" target="_blank">';
        }

        if (Mage::getStoreConfig('afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_type') == 'B2B') {
            $this->paymentConditionsUrl = $this->formatLink(
                'terms_conditions',
                $this->country,
                $this->merchantId,
                $this->getMethod()->getCode()
            );
        }

        // DHH CORE HACK: They run this even if it's all disabled...add ?? safety

        $availablePayments = Mage::getModel('checkout/session')->getData('afterpay_payment_available');
        $legalInfo = $availablePayments[$this->getMethod()->getCode()]['legalInfo'] ?? "";
        $legalInfoText = $legalInfo['text'] ?? "";
        $legalInfoConditionsLink = $legalInfo['termsAndConditionsUrl'] ?? "";
        $legalInfoPrivacyLink = $legalInfo['privacyStatementUrl'] ?? "";

        if (!$legalInfoText) {
            if ($legalInfoConditionsLink && $legalInfoPrivacyLink) {
                $this->paymentConditionsUrl = '<a href="' . $legalInfoConditionsLink . '" target="_blank">';
                $this->privacyStatementUrl = '<a href="' . $legalInfoPrivacyLink . '" target="_blank">';
            }
        }
    }

    public function formatLink($type, $locale, $merchantId, $methodCode = null)
    {
        $acceptableTypes = [
            'terms_conditions',
            'privacy_statement'
        ];
        $acceptableLocales = [
            'atde' => 'de_at',
            'benl' => 'nl_be',
            'dkda' => 'da_dk',
            'fifi' => 'fi_fi',
            'dede' => 'de_de',
            'nlnl' => 'nl_nl',
            'nonb' => 'no_no',
            'sesv' => 'sv_se',
            'chde' => 'de_ch',
        ];
        $definedCodes = [
            'digital_invoice' => 'invoice',
            'direct_debit' => 'direct_debit',
            'installment_payment' => 'fix_installments',
        ];

        if (empty($merchantId)) {
            $merchantId = 'default';
        }

        $definedLocale = $acceptableLocales[$locale] ?: $acceptableLocales['nlnl'];

        foreach ($definedCodes as $key => $value) {
            if (strpos($methodCode, $key)) {
                $methodCode = $value;
            }
        }

        if (empty($merchantId)) {
            $merchantId = 'default';
        }

        if ($type == $acceptableTypes[0]) {
            return '<a href="https://documents.riverty.com/' . $acceptableTypes[0] . '/payment_methods/' . $methodCode . '/' . $definedLocale . '/' . $merchantId . '" target="_blank">';
        } else {
            return '<a href="https://documents.riverty.com/' . $acceptableTypes[1] . '/checkout/' . $definedLocale . '" target="_blank">';
        }
    }

    public function getMethodLabelAfterHtml()
    {
        $availablePayments = Mage::getModel('checkout/session')->getData('afterpay_payment_available');

        if (isset($availablePayments[$this->getMethod()->getCode()])) {
            $paymentTitle = $availablePayments[$this->getMethod()->getCode()]['title'];
            $paymentFootNote = $availablePayments[$this->getMethod()->getCode()]['tag'];
            $paymentLogo = $availablePayments[$this->getMethod()->getCode()]['logo'];
        } else {
            $shopLocale = Mage::app()->getLocale()->getLocaleCode();
            $paymentTitle = $this->getMethod()->getTitle();
            $paymentFootNote = $this->getMethod()->getFootnote();
            $paymentLogo = "https://cdn.myafterpay.com/logo/AfterPay_logo_checkout.svg";
            $englishLocale = ['en_US', 'en_AU', 'en_CA', 'en_IE', 'en_NZ', 'en_GB'];

            if (Mage::getStoreConfig('afterpay/afterpay_' . $this->getMethod()->getCode() . '/portfolio_type') == 'B2B') {
                if (in_array($shopLocale, $englishLocale)) {
                    if ($paymentTitle === 'Achteraf betalen') {
                        $paymentTitle = 'Invoice';
                        $paymentFootNote = 'Buy now, Pay later';
                    }
                } else {
                    $paymentTitle = $this->getMethod()->getTitle();
                    $paymentFootNote = $this->getMethod()->getFootnote();
                }
            } else {
                if (in_array($shopLocale, $englishLocale)) {
                    if ($paymentTitle === 'Achteraf betalen') {
                        $paymentTitle = 'Invoice - 14 days';
                        $paymentFootNote = 'Buy now, Pay in 14 days';
                    } elseif ($paymentTitle === 'Automatische incasso') {
                        $paymentTitle = 'Direct debit';
                        $paymentFootNote = 'Have the amount conveniently collected from your account';
                    } else {
                        $paymentTitle = $this->getMethod()->getTitle();
                        $paymentFootNote = $this->getMethod()->getFootnote();
                    }
                } else {
                    $paymentTitle = $this->getMethod()->getTitle();
                    $paymentFootNote = $this->getMethod()->getFootnote();
                }
            }
        }

        $labelAfterHtml = '<img src="' . $paymentLogo . '" alt="" style="width:60px; margin-top:3px; vertical-align: middle; float: none; display: initial; margin-right: 5px;" />&nbsp;' . '<p style="margin-left:68px;margin-top:-38px;">' . $paymentTitle;

        if ($this->getMethod()->getFootnote()) {
            $labelAfterHtml .= '<p style="margin-left:68px;margin-top:-9px; font-size:13px;" class = \'afterpay_paymentmethod_label afterpay_paymentmethod_label_'
                . $this->getMethod()->getCode()
                . '\'>' . $paymentFootNote . '</p></p>';
        } else {
            $labelAfterHtml .= '</p>';
        }

        // If IWD is used then use no logo
        if (Mage::helper('core')->isModuleEnabled('IWD_Opc') || Mage::helper('core')->isModuleEnabled('Idev_OneStepCheckout')) {
            $labelAfterHtml = $paymentTitle;
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

    /**
     * @param $methodCode
     * @return mixed
     */
    public function getMethodsLegalStatement($methodCode)
    {
        $availablePayments = Mage::getModel('checkout/session')->getData('afterpay_payment_available');

        return $availablePayments[$methodCode]['legalInfo']['text'];
    }

    /**
     * @param $methodCode
     * @return mixed
     */
    public function getMethodRequiresCustomerConsent($methodCode)
    {
        $availablePayments = Mage::getModel('checkout/session')->getData('afterpay_payment_available');

        return $availablePayments[$methodCode]['legalInfo']['requiresCustomerConsent'];
    }
}
