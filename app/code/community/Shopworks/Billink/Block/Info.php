<?php
/**
 * Class Shopworks_Billink_Block_Info
 */
class Shopworks_Billink_Block_Info extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('shopworks_billink/info.phtml');
    }
}