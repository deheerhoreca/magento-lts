<?php

class Shopworks_Billink_Model_Fee_Quote_Address_Total_Billink
    extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    const BILLINK_QUOTE_FEE_BEFORE_FETCH_EVENT_NAME = 'billink_quote_fee_before_fetch';

    /**
     * Code for payment method check
     */
    protected $_paymentMethodCode = null;

    /**
     * @var Shopworks_Billink_Helper_BillinkFee
     */
    private $_feeHelper;
    
    public function __construct()
    {
        $this->setCode(Shopworks_Billink_Model_Payment_Method::TOTAL_FEE_CODE);
        $this->_paymentMethodCode = Shopworks_Billink_Model_Payment_Method::PAYMENT_METHOD_BILLINK_CODE;

        $this->_feeHelper = Mage::helper('billink/BillinkFee');
    }
    
    /**
     * Add billink totals information to address object
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return Mage_Sales_Model_Quote_Address_Total_Shipping
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        parent::fetch($address);

        if (Mage::getStoreConfig("payment/{$this->_paymentMethodCode}/fee_enabled", $address->getQuote()->getStore()))
        {
            //Set amount excl tax
            $amount = $address->getBillinkFeeInclTax() - $address->getBillinkFeeTax();

            if ($amount != 0)
            {
                $result = new Varien_Object(array(
                    'amount' => $amount,
                    'address' => $address,
                ));
                Mage::dispatchEvent(self::BILLINK_QUOTE_FEE_BEFORE_FETCH_EVENT_NAME, array(
                    'result'=>$result
                ));

                $address->addTotal(array(
                    'code' => $this->getCode(),
                    'title' => $this->getLabel(),
                    'value' => $result->getAmount()
                ));
            }
        }

        return $this;
    }
    
    /**
     * Collect totals information for billink costs
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return Mage_Sales_Model_Quote_Address_Total_Shipping
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);
        
        //only collect if items are availible
        $items = $this->_getAddressItems($address);
        if (!count($items))
        {
            return $this;
        }

        //get the quote and store for calculations
        $quote = $address->getQuote();
        $store = $quote->getStore();

        if (!Mage::getStoreConfig("payment/{$this->_paymentMethodCode}/fee_enabled", $store))
        {
            return $this;
        }

        //Reset all fields
        $address->setBillinkFee();
        $address->setBaseBillinkFee();
        $address->setBillinkFeeInclTax();
        $address->setBaseBillinkFeeInclTax();
        $address->setBillinkFeeTax();
        $address->setBaseBillinkFeeTax();
        
        //check if the current payment method is billink
        $paymentMethod = $address->getQuote()->getPayment()->getMethod();
        if ($paymentMethod && $paymentMethod == $this->_paymentMethodCode)
        {
            /** @var Shopworks_Billink_Helper_BillinkFee $helper */
            $feeTax = $this->_feeHelper->getTax(
                $this->_feeHelper->getQuoteTotalInclTax($quote),
                $quote->getShippingAddress(),
                $quote->getBillingAddress(),
                $quote->getCustomer(),
                $store
            );

            //Set billink fee on adress
            $address->setBillinkFee($feeTax->feeExclTax + $feeTax->tax);
            $address->setBaseBillinkFee($feeTax->feeExclBaseTax);
            $address->setBillinkFeeInclTax($feeTax->feeExclTax + $feeTax->tax);
            $address->setBaseBillinkFeeInclTax($feeTax->feeExclBaseTax + $feeTax->baseTax);
            $address->setBillinkFeeTax($feeTax->tax);
            $address->setBaseBillinkFeeTax($feeTax->baseTax);

            $this->_saveAppliedTaxes(
                $address,
                $feeTax->applied,
                $feeTax->tax,
                $feeTax->baseTax,
                $feeTax->rate
            );

            /**
             * Update the total amounts.
             */
            $address->setTaxAmount($address->getTaxAmount() + $feeTax->tax);
            $address->setBaseTaxAmount($address->getBaseTaxAmount() + $feeTax->baseTax);

            /**
             * Update the address' grand total amounts.
             */
            $address->setBaseGrandTotal($address->getBaseGrandTotal() + $feeTax->feeExclTax + $feeTax->tax);
            $address->setGrandTotal($address->getGrandTotal() +  $feeTax->feeExclTax + $feeTax->tax);
        }

        return $this;
    }


    /**
     * Get label (displayed on frontend)
     *
     * @return string
     */
    public function getLabel()
    {
        return Mage::getStoreConfig('payment/billink/fee_label');
    }

    /**
     * Collect applied tax rates information on address level
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @param   array $applied
     * @param   float $amount
     * @param   float $baseAmount
     * @param   float $rate
     */
    protected function _saveAppliedTaxes(Mage_Sales_Model_Quote_Address $address,
                                         $applied, $amount, $baseAmount, $rate)
    {
        $previouslyAppliedTaxes = $address->getAppliedTaxes();
        $process = count($previouslyAppliedTaxes);

        foreach ($applied as $row)
        {
            if ($row['percent'] == 0)
            {
                continue;
            }

            if (!isset($previouslyAppliedTaxes[$row['id']]))
            {
                $row['process'] = $process;
                $row['amount'] = 0;
                $row['base_amount'] = 0;
                $previouslyAppliedTaxes[$row['id']] = $row;
            }

            if (!is_null($row['percent']))
            {
                $row['percent'] = $row['percent'] ? $row['percent'] : 1;
                $rate = $rate ? $rate : 1;

                $appliedAmount = $amount / $rate * $row['percent'];
                $baseAppliedAmount = $baseAmount / $rate * $row['percent'];
            }
            else
            {
                $appliedAmount = 0;
                $baseAppliedAmount = 0;
                foreach ($row['rates'] as $rate) {
                    $appliedAmount += $rate['amount'];
                    $baseAppliedAmount += $rate['base_amount'];
                }
            }

            if ($appliedAmount || $previouslyAppliedTaxes[$row['id']]['amount'])
            {
                $previouslyAppliedTaxes[$row['id']]['amount'] += $appliedAmount;
                $previouslyAppliedTaxes[$row['id']]['base_amount'] += $baseAppliedAmount;
            }
            else
            {
                unset($previouslyAppliedTaxes[$row['id']]);
            }
        }
        $address->setAppliedTaxes($previouslyAppliedTaxes);
    }
}
