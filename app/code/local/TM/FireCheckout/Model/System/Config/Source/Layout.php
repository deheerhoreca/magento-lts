<?php

class TM_FireCheckout_Model_System_Config_Source_Layout
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'col1-set',        'label' => Mage::helper('firecheckout')->__('1 column')),
            array('value' => 'col1-expanded',   'label' => Mage::helper('firecheckout')->__('1 column (Expanded)')),
            array('value' => 'col2-set',        'label' => Mage::helper('firecheckout')->__('2 columns')),
            array('value' => 'col2-set-alt',    'label' => Mage::helper('firecheckout')->__('2 columns alternative (Wide Shipping and Payment Sections)')),
            array('value' => 'col2-set-sticky', 'label' => Mage::helper('firecheckout')->__('2 columns with sticky order review')),
            array('value' => 'col3-set',        'label' => Mage::helper('firecheckout')->__('3 columns'))
        );
    }
}
