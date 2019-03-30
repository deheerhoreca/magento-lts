<?php

/**
 * Class Shopworks_Billink_Block_Adminhtml_System_Config_Form_Field_Version
 */
class Shopworks_Billink_Block_Adminhtml_System_Config_Form_Field_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return (string) Mage::getConfig()->getNode()->modules->Shopworks_Billink->version;
    }
}