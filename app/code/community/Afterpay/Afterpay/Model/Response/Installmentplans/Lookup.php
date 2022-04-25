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

class Afterpay_Afterpay_Model_Response_Installmentplans_Lookup extends Afterpay_Afterpay_Model_Response_Abstract
{
    /**
     * In case of success - assign retrieved data to objects and return
     * @return array
     */
    protected function _accept()
    {
        $availableInstallmentPlans = $this->_response->return->availableInstallmentPlans;
        $result = array();
        foreach ($availableInstallmentPlans as $plan) {
            $result[] = $this->assignObjectValues($plan, $this->getOptionModel());
        }

        return $result;
    }

    protected function _pending()
    {
        return false;
    }

    protected function _validation()
    {
        return false;
    }

    protected function _error()
    {
        return false;
    }

    protected function _failed()
    {
        return false;
    }

    /**
     * @return Afterpay_Afterpay_Model_Portfolios_Installment_Option_Plan|false|Mage_Core_Model_Abstract
     */
    private function getOptionModel()
    {
        return Mage::getModel('afterpay/portfolios_installment_option_plan');
    }

    /**
     * @param $installmentPlan
     * @param Afterpay_Afterpay_Model_Portfolios_Installment_Option_Plan $model
     * @return Afterpay_Afterpay_Model_Portfolios_Installment_Option_Plan
     */
    private function assignObjectValues($installmentPlan, $model)
    {
        foreach (get_object_vars($installmentPlan) as $name => $value) {
            $model->setDataUsingMethod($name, $value);
        }

        return $model;
    }
}
