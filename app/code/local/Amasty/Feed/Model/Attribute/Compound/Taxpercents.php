<?php
    class Amasty_Feed_Model_Attribute_Compound_Taxpercents extends Amasty_Feed_Model_Attribute_Compound_Abstract
    {
        function prepareCollection($collection){
            $collection->joinTaxPercents($this->getFeed()->getStore());
        }
        
        function getCompoundData($productData){
            return number_format($productData['tax_percents'], 2);
        }
    }