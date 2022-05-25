<?php
    class Amasty_Feed_Model_Attribute_Compound_Parentid extends Amasty_Feed_Model_Attribute_Compound_Abstract
    {
        function getCompoundData($productData){
            return $productData['parent_id'];
        }
    }