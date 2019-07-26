<?php

class TM_CheckoutSuccess_Block_Template
    extends Mage_Core_Block_Template
{

    public function getSuccessBlock()
    {
        return $this->getLayout()->getBlock('checkout.success');
    }

}
