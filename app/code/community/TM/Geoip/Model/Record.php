<?php

class TM_Geoip_Model_Record
{
    /**
     * @var string
     */
    private $cityName;

    /**
     * @var string
     */
    private $regionCode;

    /**
     * @var string
     */
    private $countryCode;

    /**
     * @var string
     */
    private $postalCode;

    /**
     * @var mixed
     */
    private $serviceRecord;

    /**
     * @var Magento_Directory_Model_Region
     */
    private $magentoRegion;

    /**
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->serviceRecord !== null;
    }

    /**
     * @return string
     */
    public function getCityName()
    {
        return $this->cityName;
    }

    /**
     * @return string
     */
    public function getRegionCode()
    {
        return $this->regionCode;
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @return mixed
     */
    public function getServiceRecord()
    {
        return $this->serviceRecord;
    }

    /**
     * @return Magento_Directory_Model_Region
     */
    public function getMagentoRegion()
    {
        if (null === $this->magentoRegion) {
            $this->magentoRegion = Mage::getModel('directory/region')->loadByCode(
                $this->regionCode,
                $this->countryCode
            );
        }
        return $this->magentoRegion;
    }
}
