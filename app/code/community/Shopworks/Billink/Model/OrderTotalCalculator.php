<?php

/**
 * Class Shopworks_Billink_OrderTotalCalculator
 */
class Shopworks_Billink_Model_OrderTotalCalculator
{
    /**
     * @param Shopworks_Billink_Model_Service_Order_Input $input
     * @return float
     */
    public function calculateTotal(Shopworks_Billink_Model_Service_Order_Input $input)
    {
        $orderAmount = 0;

        /** @var Shopworks_Billink_Model_Service_Order_Input_Item  $item */
        foreach($input->getOrderItems() as $item)
        {
            if($item->priceType == Shopworks_Billink_Model_Service_Order_Input_Item::PRICE_INCL_TAX)
            {
                $itemPriceInclTax = $item->price;
            }
            else
            {
                $itemPriceInclTax = $item->price + ($item->price / 100 * $item->taxPercentage);
            }

            //round price for each row
            $rowTotal = round($item->quantity * $itemPriceInclTax, 2);
            $orderAmount += $rowTotal;
        }

        return $orderAmount;
    }
}