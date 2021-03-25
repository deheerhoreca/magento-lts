<?php

class Psc_Paysafecash_Block_Cpay_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $this->setTemplate('paysafecash/cpay/form.phtml');
        parent::_construct();
    }
}
