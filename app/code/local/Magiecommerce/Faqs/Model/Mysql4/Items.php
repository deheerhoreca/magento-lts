<?php
class Magiecommerce_Faqs_Model_Mysql4_Items extends Mage_Core_Model_Mysql4_Abstract
    {
        public function _construct()
        {   
            $this->_init('faqs/items', 'faqs_id');
        }
    }