<?php

class Profitmetrics_MagentoIntegration_Model_System_Config_Source_Cron_Frequency
{

    protected static $_options;

    const CRON_EACH_MINUTE  = '*/1 * * * *';
    const CRON_EACH_2_MINUTES  = '*/2 * * * *';
    const CRON_EACH_5_MINUTES  = '*/5 * * * *';
    const CRON_FOUR_TIMES_HOUR  = '*/15 * * * *';
    const CRON_TWICE_HOUR       = '*/30 * * * *';
    const CRON_HOURLY           = '0 * * * *';
    const CRON_DAILY            = '0 0 */1 * *';
    const CRON_WEEKLY           = '0 0 */7 * *';
    const CRON_MONTHLY          = '0 0 1 * *';

    public function toOptionArray()
    {
        if (!self::$_options) {
            self::$_options = array(
                array(
                    'label' => Mage::helper('cron')->__('Every minute'),
                    'value' => self::CRON_EACH_MINUTE,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Every 2 minutes'),
                    'value' => self::CRON_EACH_2_MINUTES,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Every 5 minutes'),
                    'value' => self::CRON_EACH_5_MINUTES,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Every 15 minutes'),
                    'value' => self::CRON_FOUR_TIMES_HOUR,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Twice a hour'),
                    'value' => self::CRON_TWICE_HOUR,
                ),

                array(
                    'label' => Mage::helper('cron')->__('Hourly'),
                    'value' => self::CRON_HOURLY,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Daily'),
                    'value' => self::CRON_DAILY,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Weekly'),
                    'value' => self::CRON_WEEKLY,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Monthly'),
                    'value' => self::CRON_MONTHLY,
                ),
            );
        }
        return self::$_options;
    }
}
