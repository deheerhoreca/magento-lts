<?php

class Psc_Paysafecash_Model_Cpay_Session extends Mage_Core_Model_Session_Abstract
{
    public function __construct()
    {
        $this->init('cpay');
    }
}
