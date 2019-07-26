<?php

class TM_FireCheckout_Model_System_Config_Source_FormFieldsMode
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'default',
                'label' => Mage::helper('firecheckout')->__('Default')
            ),
            array(
                'value' => 'horizontal',
                'label' => Mage::helper('firecheckout')->__('Horizontal (Label aside of the field)')
            ),
            array(
                'value' => 'wide',
                'label' => Mage::helper('firecheckout')->__('Wide (One field per line)')
            ),
        );
    }
}
