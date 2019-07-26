<?php

class TM_AddressAutocomplete_Helper_Data extends Mage_Core_Helper_Data
{
    const CONFIG_STREET_NUMBER_PLACEMENT = 'addressautocomplete/general/street_number_placement';

    public function getJsIncludes()
    {
        return $this->getInlineJs() . "\n" . $this->getJsLibrary();
    }

    public function getInlineJs()
    {
        $regions = Mage::helper('directory')->getRegionJson();
        $streetNumberPlacement = Mage::getStoreConfig(self::CONFIG_STREET_NUMBER_PLACEMENT);

        return sprintf(
            '<script type="text/javascript">AddressAutocomplete.setConfig(%s)</script>',
            $this->jsonEncode(
                array(
                    'regions' => $this->jsonDecode($regions),
                    'street_number_placement' => $streetNumberPlacement
                )
            )
        );
    }

    public function getJsLibrary()
    {
        $params = array(
            'key'       => Mage::getStoreConfig('addressautocomplete/general/api_key'),
            'libraries' => 'places',
            'callback'  => 'AddressAutocomplete.init',
            'language'  => str_replace('_', '-', Mage::app()->getLocale()->getLocaleCode())
        );
        return sprintf(
            '<script type="text/javascript" src="%s" async defer></script>',
            sprintf(
                'https://maps.googleapis.com/maps/api/js?%s',
                http_build_query($params)
            )
        );
    }
}
