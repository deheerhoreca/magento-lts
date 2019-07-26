<?php

if (Mage::helper('core')->isModuleOutputEnabled('FireGento_MageSetup')) {
    Mage::helper('tmcore')->requireOnce('TM/FireCheckout/Helper/Checkout/FireGentoMageSetup.php');
} else {
    class TM_FireCheckout_Helper_Checkout_Abstract extends Mage_Checkout_Helper_Data {}
}
