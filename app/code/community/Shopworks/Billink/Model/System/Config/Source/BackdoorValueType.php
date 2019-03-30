<?php

/**
 * Class Shopworks_Billink_Model_System_Config_Source_BackdoorValueType
 */
class Shopworks_Billink_Model_System_Config_Source_BackdoorValueType
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array(
            '0' => 'Keur alle aanvragen af',
            '1' => 'Keur alle aanvragen goed'
        );

        return $options;
    }
}