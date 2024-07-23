<?php

class Profitmetrics_MagentoIntegration_Model_System_Config_Source_Configurable_Product_Price
{
    const CONFIGURABLE_PRICE_SOURCE_CONFIGURABLE = 1;
    const CONFIGURABLE_PRICE_SOURCE_MIN_SIMPLE = 2;

    protected static $_options;

    public function toOptionArray()
    {
        if (!self::$_options) {
            self::$_options = array(
                array(
                    'label' => Mage::helper('profitmetrics')->__('Get from parent configurable product'),
                    'value' => self::CONFIGURABLE_PRICE_SOURCE_CONFIGURABLE,
                ),
                array(
                    'label' => Mage::helper('profitmetrics')->__('Calculate the minimum from related simple options'),
                    'value' => self::CONFIGURABLE_PRICE_SOURCE_MIN_SIMPLE,
                ),
            );
        }
        return self::$_options;
    }
}
