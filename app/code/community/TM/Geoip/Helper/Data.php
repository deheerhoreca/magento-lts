<?php

class TM_Geoip_Helper_Data extends Mage_Core_Helper_Abstract
{
    private $_memo = [];

    /**
     * @param  string $ipAddress [optional]
     * @return TM_Geoip_Model_Record
     */
    public function detect($ipAddress = false)
    {
        if (!Mage::getStoreConfigFlag('tm_geoip/general/enabled')) {
            return Mage::getModel('tm_geoip/record_factory')->create();
        }

        if (!$ipAddress) {
            $ipAddress = Mage::helper('core/http')->getRemoteAddr();
        }

        if (!isset($this->_memo[$ipAddress])) {
            $maxmind = Mage::getModel('tm_geoip/detect_maxmind');
            $this->_memo[$ipAddress] = $maxmind->detect($ipAddress);
        }

        return $this->_memo[$ipAddress];
    }
}
