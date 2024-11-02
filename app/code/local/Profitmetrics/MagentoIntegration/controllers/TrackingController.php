<?php

class Profitmetrics_MagentoIntegration_TrackingController extends Mage_Core_Controller_Front_Action
{
    /**
     * @throws Mage_Core_Exception
     */
    public function indexAction()
    {
        $trackingData = Mage::getModel('profitmetrics/observer')->getTrackingData();
        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');

        $visitorId = $customerSession->getData(
            Profitmetrics_MagentoIntegration_Helper_Data::PROFITMETRICS_VISITOR_ID_SESSION_KEY
        );
        $lastUpdateTimestamp = $customerSession->getData(
            Profitmetrics_MagentoIntegration_Helper_Data::PROFITMETRICS_UPDATE_TIMESTAMP_SESSION_KEY
        );

        if (!$visitorId || $trackingData['timestamp'] !== $lastUpdateTimestamp) {
            $lastUpdateTimestamp = '';
        }

        $this->getResponse()->setBody((string) $lastUpdateTimestamp);
    }
}
