<?php
/**
 * @copyright   Copyright (c) 2009-2012 Amasty (http://www.amasty.com)
 */ 
class Amasty_Feed_Model_Rule extends Mage_CatalogRule_Model_Rule
{
    public function _construct()
    {
        parent::_construct();
        
    }
    
    public function getConditionsInstance()
    {
        return Mage::getModel('amfeed/rule_condition_combine');
    }
}