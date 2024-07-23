<?php

class Profitmetrics_MagentoIntegration_Model_System_Config_Backend_Conversion_Booster_Snippet extends Mage_Core_Model_Config_Data
{
    protected $requiredJsonKeys = ['orderValueModifier', 'profitSendToSnippets', 'revenueSendToSnippets'];

    public function _beforeSave()
    {
        if (!($value = $this->getValue())) {
            return parent::_beforeSave();
        }

        try {
            $jsonSnippet = Mage::helper('core')->jsonDecode($value);
        } catch (Zend_Json_Exception $exception) {
            Mage::throwException(
                Mage::helper('core')->__('Conversion Booster Snippet: Provided value is not a valid JSON.')
            );
        }

        foreach ($this->requiredJsonKeys as $key) {
            if (!isset($jsonSnippet[$key])) {
                Mage::throwException(
                    Mage::helper('core')->__(
                        'Conversion Booster Snippet: Provided JSON value does not have the required key: %s',
                        $key
                    )
                );
            }
        }

        return parent::_beforeSave();
    }
}