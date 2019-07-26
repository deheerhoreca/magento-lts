<?php

class TM_Geoip_Model_Record_Factory
{
    public function create($serviceRecord = false)
    {
        return Mage::getModel('tm_geoip/record', $this->extract($serviceRecord));
    }

    /**
     * @param  mixed $serviceRecord
     * @return array
     */
    private function extract($serviceRecord)
    {
        $options = [
            'cityName' => '',
            'regionCode' => '',
            'countryCode' => '',
            'postalCode' => '',
            'serviceRecord' => $serviceRecord,
        ];

        if ($serviceRecord instanceof \GeoIp2\Model\City) {
            $options['cityName'] = $serviceRecord->city->name;
            $options['regionCode'] = $serviceRecord->mostSpecificSubdivision->isoCode;
            $options['countryCode'] = $serviceRecord->country->isoCode;
            $options['postalCode'] = $serviceRecord->postal->code;
        }

        return $options;
    }
}
