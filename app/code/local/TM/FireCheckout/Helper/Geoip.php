<?php

class TM_FireCheckout_Helper_Geoip extends Mage_Core_Helper_Abstract
{
    public function detect($remoteAddr)
    {
        if (!$this->isModuleOutputEnabled('TM_Geoip')) {
            return array();
        }

        $record = Mage::helper('tm_geoip')->detect($remoteAddr);
        if (!$record->isValid()) {
            return array();
        }

        $result = array(
            'region_id' => $record->getMagentoRegion()->getId(),
            'city' => $record->getCityName(),
            'postcode' => $record->getPostalCode(),
            'country_id' => $record->getCountryCode(),
        );

        return $result;
    }
}
