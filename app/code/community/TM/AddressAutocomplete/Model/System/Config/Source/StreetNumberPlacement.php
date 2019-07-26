<?php

class TM_AddressAutocomplete_Model_System_Config_Source_StreetNumberPlacement
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'line1_start', 'label' => Mage::helper('addressautocomplete')->__('Address Line 1 Start')),
            array('value' => 'line1_end',   'label' => Mage::helper('addressautocomplete')->__('Address Line 1 End')),
            array('value' => 'line2',       'label' => Mage::helper('addressautocomplete')->__('Address Line 2')),
        );
    }
}
