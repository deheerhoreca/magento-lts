<?php

/**
 * Class Shopworks_Billink_Block_PageHeader
 */
class Shopworks_Billink_Block_PageHeader extends Mage_Core_Block_Template
{
    /**
     * @var Shopworks_Billink_Helper_Billink
     */
    private $_helper;

    /**
     * Magento constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('shopworks_billink/pageheader.phtml');
        $this->_helper = Mage::helper('billink/Billink');
    }

    /**
     * @return bool
     */
    public function isBillinkReadyToUse()
    {
        return $this->_helper->isReadyToUse();
    }

    /**
     * @return bool
     */
    public function isAlternateDeliveryAddressAllowed()
    {
        return $this->_helper->isAlternateDeliveryAddressAllowed();
    }
}