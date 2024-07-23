<?php

class Profitmetrics_MagentoIntegration_Model_System_Config_Backend_Cron_Frequency extends Mage_Core_Model_Config_Data
{
    const FEED_CRON_STRING_PATH  = 'crontab/jobs/profitmetrics_products_export/schedule/cron_expr';
    const ORDERS_CRON_STRING_PATH  = 'crontab/jobs/profitmetrics_orders_send/schedule/cron_expr';

    /**
     * @return Mage_Core_Model_Abstract|void
     * @throws Exception
     */
    protected function _afterSave()
    {
        $frequency    = $this->getData('groups/settings/fields/cron_frequency/value');
        $time         = $this->getData('groups/settings/fields/time/value');
        $cronExpression = $this->calculateCronExpression($frequency, $time);

        try {
            Mage::getModel('core/config_data')
                ->load(self::FEED_CRON_STRING_PATH, 'path')
                ->setValue($cronExpression)
                ->setPath(self::FEED_CRON_STRING_PATH)
                ->save();
        } catch (Exception $e) {
            throw new Exception(Mage::helper('cron')->__('Unable to save feed cron expression.'));
        }

        $frequency    = $this->getData('groups/order_cron_settings/fields/cron_frequency/value');
        $time         = $this->getData('groups/order_cron_settings/fields/time/value');
        $cronExpression = $this->calculateCronExpression($frequency, $time);

        try {
            Mage::getModel('core/config_data')
                ->load(self::ORDERS_CRON_STRING_PATH, 'path')
                ->setValue($cronExpression)
                ->setPath(self::ORDERS_CRON_STRING_PATH)
                ->save();
        } catch (Exception $e) {
            throw new Exception(Mage::helper('cron')->__('Unable to save feed cron expression.'));
        }

    }

    /**
     * @param string $frequency
     * @param string $time
     * @return string
     */
    private function calculateCronExpression($frequency, $time)
    {
        $cronExpressionArray = explode(' ', $frequency);

        if ($frequency === Profitmetrics_MagentoIntegration_Model_System_Config_Source_Cron_Frequency::CRON_HOURLY) {
            $cronExpressionArray[0] = $time[0];
        }

        if (in_array(
            $frequency,
            array(
                Profitmetrics_MagentoIntegration_Model_System_Config_Source_Cron_Frequency::CRON_DAILY,
                Profitmetrics_MagentoIntegration_Model_System_Config_Source_Cron_Frequency::CRON_WEEKLY,
                Profitmetrics_MagentoIntegration_Model_System_Config_Source_Cron_Frequency::CRON_MONTHLY
            ),
            true
        )) {
            $cronExpressionArray[0] = (int) $time[0];
            $cronExpressionArray[1] = (int) $time[1];
        }

        return implode(' ', $cronExpressionArray);
    }
}
