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

class Afterpay_Afterpay_Model_Sources_PortfolioCountry extends Varien_Object
{
    public function toOptionArray()
    {
        $array = array(
            array('value' => 'atde', 'label' => Mage::helper('afterpay')->__('Austria')),
            array('value' => 'benl', 'label' => Mage::helper('afterpay')->__('Belgium (dutch)')),
            array('value' => 'befr', 'label' => Mage::helper('afterpay')->__('Belgium (french)')),
            array('value' => 'dkda', 'label' => Mage::helper('afterpay')->__('Denmark')),
            array('value' => 'fifi', 'label' => Mage::helper('afterpay')->__('Finland')),
            array('value' => 'dede', 'label' => Mage::helper('afterpay')->__('Germany')),
            array('value' => 'nlnl', 'label' => Mage::helper('afterpay')->__('Netherlands')),
            array('value' => 'nonb', 'label' => Mage::helper('afterpay')->__('Norway')),
            array('value' => 'sesv', 'label' => Mage::helper('afterpay')->__('Sweden')),
            array('value' => 'chde', 'label' => Mage::helper('afterpay')->__('Switzerland')),
        );
        return $array;
    }
}
