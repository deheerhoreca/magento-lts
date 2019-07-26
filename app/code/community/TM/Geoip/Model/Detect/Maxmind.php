<?php

require_once 'TM/Geoip/vendor/autoload.php';

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;

class TM_Geoip_Model_Detect_Maxmind
{
    public function detect($ipAddress)
    {
        // $ipAddress = '128.101.101.101';

        try {
            $reader = new Reader($this->_getCityFilepath());
            $record = $reader->city($ipAddress);
        } catch (AddressNotFoundException $e) {
            $record = false;
        } catch (Exception $e) {
            $record = false;
            Mage::logException($e);
        }

        return Mage::getModel('tm_geoip/record_factory')->create($record);
    }

    private function _getCityFilepath()
    {
        return Mage::getBaseDir('var') . '/tm/geoip/' . Mage::getStoreConfig('tm_geoip/maxmind/city');
    }
}
