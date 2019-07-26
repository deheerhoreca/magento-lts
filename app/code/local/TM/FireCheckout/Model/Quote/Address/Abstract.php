<?php

if (Mage::helper('core')->isModuleOutputEnabled('OnePica_AvaTax')) {
    Mage::helper('tmcore')->requireOnce('TM/FireCheckout/Model/Quote/Address/OnePicaAvaTax.php');
} else {
    class TM_FireCheckout_Model_Quote_Address_Abstract extends Mage_Sales_Model_Quote_Address {}
}
