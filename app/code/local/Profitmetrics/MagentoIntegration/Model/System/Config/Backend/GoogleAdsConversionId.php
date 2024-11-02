<?php

class Profitmetrics_MagentoIntegration_Model_System_Config_Backend_GoogleAdsConversionId extends Mage_Core_Model_Config_Data
{
    public function _beforeSave()
    {
        $value = (string)$this->getValue();

        if ($value && !preg_match('/^AW-[0-9]{4,12}$/', $value)) {
            Mage::throwException(
                Mage::helper('core')->__(
                    'Google Ads Conversion ID: Please enter the correct value, in a format: AW-123456. Code should contain 4-12 digits.'
                )
            );

        }

        return parent::_beforeSave();
    }
}