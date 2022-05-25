<?php
    class Amasty_Feed_Model_Attribute_Compound_Url extends Amasty_Feed_Model_Attribute_Compound_Abstract
    {
        function getCompoundData($productData){
            return $this->_feed->getProductUrl($productData['entity_id']);
        }
    }