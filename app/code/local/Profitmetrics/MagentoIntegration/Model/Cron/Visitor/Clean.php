<?php

class Profitmetrics_MagentoIntegration_Model_Cron_Visitor_Clean
{
    public function cleanOutdatedVisitors()
    {
        /** @var Profitmetrics_MagentoIntegration_Model_Resource_Tracking_Collection $visitorCollection */
        $visitorCollection = Mage::getResourceModel('profitmetrics/tracking_collection');
        $timeToLive = Mage::helper('profitmetrics')->getTrackingDataLifetimeDays();
        $visitorTimestampThreshold = Mage::getModel('core/date')->timestamp() - $timeToLive * 24 * 60 * 60;
        $visitorCollection->addFieldToFilter('timestamp', ['lteq' => $visitorTimestampThreshold]);
        $deleteQueryExpression = $visitorCollection->getSelect()->deleteFromSelect('main_table');
        $visitorCollection->getConnection()->query($deleteQueryExpression);
    }
}