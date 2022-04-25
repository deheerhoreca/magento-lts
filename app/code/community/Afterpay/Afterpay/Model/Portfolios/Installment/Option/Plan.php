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

/**
 * @method int    getInstallmentProfileNumber
 * @method void   setInstallmentProfileNumber(int $value)
 * @method int    getNumberOfInstallments
 * @method void   setNumberOfInstallments(int $value)
 * @method float  getTotalAmount
 * @method void   setTotalAmount(float $value)
 * @method float  getInterestRate
 * @method void   setInterestRate(float $value)
 * @method float  getEffectiveInterestRate
 * @method void   setEffectiveInterestRate(float $value)
 * @method float  getInstallmentAmount
 * @method void   setInstallmentAmount(float $value)
 * @method float  getBasketAmount
 * @method void   setBasketAmount(float $value)
 * @method float  getFirstInstallmentAmount
 * @method void   setFirstInstallmentAmount(float $value)
 * @method float  getLastInstallmentAmount
 * @method void   setLastInstallmentAmount(float $value)
 * @method float  getEffectiveAnnualPercentageRate
 * @method void   setEffectiveAnnualPercentageRate(float $value)
 * @method float  getTotalInterestAmount
 * @method void   setTotalInterestAmount(float $value)
 * @method float  getStartupFee
 * @method void   setStartupFee(float $value)
 * @method float  getMonthlyFee
 * @method void   setMonthlyFee(float $value)
 * @method string getReadMore
 * @method void   setReadMore(string $value)
 */
class Afterpay_Afterpay_Model_Portfolios_Installment_Option_Plan extends Varien_Object
{
    /**
     * Return installment amount string along with currency symbol
     *
     * @return string
     */
    public function getFormattedInstallmentAmount()
    {
        $helper = Mage::helper('checkout');
        return $helper->formatPrice($this->getInstallmentAmount());
    }

    /**
     * Return installment total amount along with currency
     *
     * @return string
     */
    public function getFormattedTotalAmount()
    {
        $helper = Mage::helper('checkout');
        return $helper->formatPrice($this->getTotalAmount());
    }
}
