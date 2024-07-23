<?php

class Profitmetrics_Magentointegration_Block_System_Config_Form_Field_ReadonlyField
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml($element)
    {
        $element->setDisabled('disabled');

        return parent::_getElementHtml($element);
    }
}