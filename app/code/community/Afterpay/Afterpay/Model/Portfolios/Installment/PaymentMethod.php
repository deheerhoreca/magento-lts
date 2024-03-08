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

class Afterpay_Afterpay_Model_Portfolios_Installment_PaymentMethod extends Afterpay_Afterpay_Model_Portfolios_Abstract
{
    protected $_code = 'afterpay_de_installment_payment';
    protected $_formBlockType = 'afterpay/portfolios_checkout_installment_form';

    /**
     * @var Afterpay_Afterpay_Model_Portfolios_Installment_Option_Plan[]
     */
    private $installmentPlanOptions;

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        return parent::isAvailable($quote) && $this->loadInstallmentOptions();
    }

    /**
     * @return Afterpay_Afterpay_Model_Portfolios_Installment_Option_Plan[]|false
     */
    public function loadInstallmentOptions()
    {
        if (!$this->installmentPlanOptions) {
            $lookup = $this->lookupModel();
            $lookup->setMethod($this);
            $this->installmentPlanOptions = $lookup->sendLookupRequest();
        }

        return $this->installmentPlanOptions;
    }

    /**
     * Extended validation to submit the actual bankAccount values to Afterpay service
     * @return void
     * @throws Mage_Core_Exception
     */
    public function validate()
    {
        $paymentInfo = $this->getInfoInstance();
        // validate store currency
        if (!in_array(Mage::app()->getStore()->getCurrentCurrencyCode(), $this->getAllowedCurrencies(), true)) {
            Mage::throwException(Mage::helper('afterpay')->__('Please check configured store currency'));
        }

        // if field is not shown, no need to validate with service
        if ($this->bankCodeEnabled($this->getCode())) {
            try {
                $this->validatePresence($paymentInfo->getAdditionalInformation());

                /* @var Afterpay_Afterpay_Model_Request_Validate_Bankaccount $validator */
                $validator = Mage::getModel('afterpay/request_validate_bankaccount');
                $validator->sendValidateRequest($paymentInfo);
            } catch (Exception $exception) {
                Mage::throwException($exception->getMessage());
            }
        }
    }

    /**
     * Before submit to service, make sure there is data to validate
     *
     * @param array $additionalInformation
     * @return void
     * @throws Zend_Validate_Exception
     */
    private function validatePresence($additionalInformation)
    {
        if (!isset($additionalInformation['bankaccount'])
            || !isset($additionalInformation['bankcode'])
            || empty($additionalInformation['bankaccount'])
            || empty($additionalInformation['bankcode'])
        ) {
            throw new Zend_Validate_Exception(Mage::helper('core')->__('Please check bank account details.'));
        }
    }

    /**
     * @return false|Afterpay_Afterpay_Model_Request_Installmentplans_Lookup
     */
    private function lookupModel()
    {
        return Mage::getModel('afterpay/request_installmentplans_lookup');
    }

    /**
     * Whether config allows showing bankcode field
     *
     * @param $code
     * @return bool
     */
    private function bankCodeEnabled($code)
    {
        return Mage::getStoreConfigFlag(sprintf('afterpay/%s/portfolio_showbankaccount', $code));
    }
}
