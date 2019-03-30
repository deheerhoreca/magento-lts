<?php

/**
 * Class Shopworks_Billink_Helper_BillinkFee
 */
class Shopworks_Billink_Helper_BillinkFee
{
    /**
     * Calculate tax for order fee
     *
     * @param $grandTotal
     * @param Mage_Sales_Model_Quote_Address $shippingAddress
     * @param Mage_Sales_Model_Quote_Address $billingAddress
     * @param Mage_Customer_Model_Customer $customer
     * @param Mage_Core_Model_Store $store
     * @return Shopworks_Billink_Helper_BillinkFee_TaxCalculationResult
     */
    public function getTax($grandTotal, $shippingAddress, $billingAddress, $customer,  $store)
    {
        $baseAmount = $this->getBillinkFeeFromConfig($grandTotal, $store);
        $result = new Shopworks_Billink_Helper_BillinkFee_TaxCalculationResult();

        //use magento's tax calculator
        $calculator = Mage::getSingleton('tax/calculation');
        $calculator->setCustomer($customer);

        /** @var Mage_Customer_Model_Group $customerGroupModel */
        $customerGroupModel = Mage::getModel('customer/group');
        $customerTaxClass = $customerGroupModel->getTaxClassId($customer->getGroupId());

        /* @var $calculator Mage_Tax_Model_Calculation */
        $request = $calculator->getRateRequest(
            $shippingAddress,
            $billingAddress,
            $customerTaxClass,
            $store
        );

        $request->setProductClassId($this->_getFeeTaxClassFromConfig($store));
        $result->rate = $calculator->getRate($request);

        if ($result->rate > 0.0)
        {
            //calculate the amounts
            $result->baseTax = $calculator->calcTaxAmount($baseAmount, $result->rate, $this->isBillinkFeeFromConfigIncludingTax($store), true);
            $result->tax = $store->convertPrice($result->baseTax, false);
        }

        $result->applied = $calculator->getAppliedRates($request);

        //Calculate fee excl tax
        if($this->isBillinkFeeFromConfigIncludingTax($store))
        {
            $result->feeExclBaseTax = $baseAmount - $result->baseTax;
            $result->feeExclTax = $baseAmount - $result->tax;
        }
        else
        {
            $result->feeExclBaseTax = $baseAmount;
            $result->feeExclTax = $baseAmount;
        }

        return $result;
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return bool
     */
    public function isBillinkFeeFromConfigIncludingTax($store=null)
    {
        return Mage::getStoreConfig('payment/'.Shopworks_Billink_Model_Payment_Method::PAYMENT_METHOD_BILLINK_CODE.'/fee_includes_tax', $store);
    }

    /**
     * @param float $grandTotal
     * @param Mage_Core_Model_Store $store
     * @return double
     */
    public function getBillinkFeeFromConfig($grandTotal, $store=null)
    {
        $feeAmount = 0;
        $feeRanges = Mage::getStoreConfig('payment/'. Shopworks_Billink_Model_Payment_Method::PAYMENT_METHOD_BILLINK_CODE . '/fee_ranges', $store);
        $feeRanges = unserialize($feeRanges);

        if(is_array($feeRanges))
        {
            foreach ($feeRanges as $range)
            {
                $from = $range['from'];
                $until = $range['until'];
                $isGrandTotalWithinRange = ($grandTotal >= $from) && ($grandTotal < $until);

                if ($isGrandTotalWithinRange)
                {
                    $feeAmount = $range['fee'];
                }
            }
        }

        return $feeAmount;
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return double
     */
    private function _getFeeTaxClassFromConfig($store=null)
    {
        return Mage::getStoreConfig('payment/'. Shopworks_Billink_Model_Payment_Method::PAYMENT_METHOD_BILLINK_CODE . '/fee_tax_class', $store);
    }

    /**
     * The grand total is not set before the totals are calculated, so if we want the total quote amount, we need to
     * calculate this ourselves
     * @param Mage_Sales_Model_Quote $quote
     * @return int
     */
    public function getQuoteTotalInclTax($quote)
    {
        $grandTotal = 0;
        foreach ($quote->getAllItems() as $item)
        {
            $itemPrice = $item->getPriceInclTax() * $item->getQty();
            $itemDiscount = $item->getDiscountAmount();
            $grandTotal += ($itemPrice - $itemDiscount);
        }
        return $grandTotal;
    }
}

class Shopworks_Billink_Helper_BillinkFee_TaxCalculationResult
{
    public $rate;
    public $baseTax;
    public $tax;
    public $applied;
    public $feeExclTax;
    public $feeExclBaseTax;
}