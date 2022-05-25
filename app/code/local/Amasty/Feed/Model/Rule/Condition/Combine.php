<?php
/**
 * @copyright   Copyright (c) 2009-2012 Amasty (http://www.amasty.com)
 */
class Amasty_Feed_Model_Rule_Condition_Combine extends Mage_CatalogRule_Model_Rule_Condition_Combine
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('amafeed/rule_condition_combine');
    }
} 