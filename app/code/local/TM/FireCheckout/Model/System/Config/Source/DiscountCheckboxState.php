<?php

class TM_FireCheckout_Model_System_Config_Source_DiscountCheckboxState
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'unchecked', 'label' => Mage::helper('firecheckout')->__('Not checked (Discount form is hidden)')),
            array('value' => 'checked',   'label' => Mage::helper('firecheckout')->__('Checked (Discount form is visible)')),
            array('value' => 'hidden',    'label' => Mage::helper('firecheckout')->__('Hidden (Discount form is visible)'))
        );
    }
}
