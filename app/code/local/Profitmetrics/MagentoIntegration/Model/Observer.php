<?php

class Profitmetrics_MagentoIntegration_Model_Observer
{
    const PROFITMETRICS_COOKIE_TRACKING_DATA = 'pmTPTrack';
    const PROFITMETRICS_COOKIE_VISITOR_DATA = 'pmVisitSource';
    const GOOGLE_CLICK_ID_URL_PARAM = 'gclid';
    const FACEBOOK_CLICK_ID_URL_PARAM = 'fbclid';
    const MAX_LENGTH = 100;

    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_lastOrder;

    /**
     * @var array
     */
    protected $_lastOrderIds = array();

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function onSalesOrderLoadAfter(Varien_Event_Observer $observer)
    {
        $lastOrderId = (int)Mage::getSingleton('checkout/session')->getLastOrderId();

        if (!$lastOrderId) {
            return $this;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()->getData('order');

        if ($order && (int)$order->getId() === $lastOrderId) {
            $this->_lastOrder = $order;
        }
    }

    /**
     * @return Mage_Sales_Model_Order|null
     */
    public function getLastOrder()
    {
        return $this->_lastOrder;
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function onMultishippingSuccessAction(Varien_Event_Observer $observer)
    {
        if ($orderIds = $observer->getData('order_ids')) {
            $this->_lastOrderIds = $orderIds;
        }
    }

    /**
     * @return array
     */
    public function getMultishippingOrderIds()
    {
        return $this->_lastOrderIds;
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function onControllerActionPreDispatch(Varien_Event_Observer $observer)
    {
        if (!($trackingData = $this->getTrackingData()) || Mage::helper('profitmetrics/bot')->isBot()) {
            return;
        }

        if (Mage::helper('profitmetrics')->getIsHeadlessModeActive()) {
            return;
        }

        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');

        $visitorId = $customerSession->getData(
            Profitmetrics_MagentoIntegration_Helper_Data::PROFITMETRICS_VISITOR_ID_SESSION_KEY
        );
        $lastUpdateTimestamp = $customerSession->getData(
            Profitmetrics_MagentoIntegration_Helper_Data::PROFITMETRICS_UPDATE_TIMESTAMP_SESSION_KEY
        );

        if ($visitorId && $trackingData['timestamp'] === $lastUpdateTimestamp) {
            return;
        }

        $visitor = Mage::getResourceModel('profitmetrics/tracking_collection')
            ->addFieldToFilter('entity_id', $visitorId)
            ->getFirstItem();

        try {
            if ($trackingData['timestamp'] > $visitor->getTimestamp()) {
                $trackingData = array_filter($trackingData, static function ($value) {
                    return (string) $value !== '';
                });
                
                if (
                    isset($trackingData['fbc'])
                    && $this->facebookClickIdIsExpired($trackingData['fbc'], $visitor->getFbc())
                ) {
                    unset($trackingData['fbc']);
                }

                if (!$trackingData['landing_page'] || $visitor->getLandingPage()) {
                    unset($trackingData['landing_page'], $trackingData['landing_page_length']);
                }

                $visitor->addData($trackingData);

                $visitor->save();
            }
            $customerSession->setData(
                Profitmetrics_MagentoIntegration_Helper_Data::PROFITMETRICS_VISITOR_ID_SESSION_KEY,
                $visitor->getId()
            );
            $customerSession->setData(
                Profitmetrics_MagentoIntegration_Helper_Data::PROFITMETRICS_UPDATE_TIMESTAMP_SESSION_KEY,
                $visitor->getTimestamp()
            );
        } catch (\Exception $exception) {
            Mage::logException($exception);
        }
    }

    /**
     * @param $newFacebookClickId
     * @param $savedFacebookClickId
     * @return bool
     */
    private function facebookClickIdIsExpired($newFacebookClickId, $savedFacebookClickId)
    {
        if (!$savedFacebookClickId || !$newFacebookClickId) {
            return false;
        }

        $newFacebookClickIdTimestamp = $this->getFacebookClickTimestamp($newFacebookClickId);
        $savedFacebookClickIdTimestamp = $this->getFacebookClickTimestamp($savedFacebookClickId);

        return $newFacebookClickIdTimestamp <= $savedFacebookClickIdTimestamp;
    }

    private function getFacebookClickTimestamp($facebookClickId)
    {
        $components = explode('.', $facebookClickId);

        return isset($components[2]) ? $components[2] : 0;
    }

    /**
     * @return array
     */
    public function getTrackingData()
    {
        $profitMetricsTrackingCookieString
            = Mage::getModel('core/cookie')->get(self::PROFITMETRICS_COOKIE_TRACKING_DATA) ?: '';
        $profitMetricsCookieData = array();

        if ($profitMetricsTrackingCookieString) {
            $unserializeResult = json_decode($profitMetricsTrackingCookieString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Mage::log(
                    'Profitmetrics: Error unserializing cookie data. String was: ' . $profitMetricsTrackingCookieString,
                    null,
                    'profitmetrics.log'
                );

                $unserializeResult = [];
            }

            $profitMetricsCookieData = $unserializeResult;
        }

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = Mage::app()->getRequest();
        $currentTimestamp = time();
        $timestamp = $this->getArrayParameter('timestamp', $profitMetricsCookieData);
        $googleClickId = $this->getArrayParameter('gclid', $profitMetricsCookieData);
        $googleClickIdFromUrl = $request->getParam(self::GOOGLE_CLICK_ID_URL_PARAM);

        if (!$googleClickId || $googleClickIdFromUrl !== $googleClickId) {
            $googleClickId = $googleClickIdFromUrl;
            $timestamp = $currentTimestamp;
        }

        if (strlen($googleClickId) > self::MAX_LENGTH) {
            $googleClickId = substr($googleClickId, 0, self::MAX_LENGTH);
        }

        $facebookClickIdFromUrl = $request->getParam(self::FACEBOOK_CLICK_ID_URL_PARAM);
        $facebookClickIdFromProfitMetrics = $this->getArrayParameter('fbc', $profitMetricsCookieData);
        $facebookClickId = $facebookClickIdFromProfitMetrics;

        if ($facebookClickIdFromUrl) {
            $facebookClickId = 'fb.1.'. $currentTimestamp * 1000 . '.' . $facebookClickIdFromUrl;
            $timestamp = $currentTimestamp;
        }

        if (strlen($facebookClickId) > self::MAX_LENGTH) {
            $facebookClickId = substr($facebookClickId, 0, self::MAX_LENGTH);
        }

        $visitorData = [];
        $utmSource = $request->getParam('utm_source');
        $utmCampaign = $request->getParam('utm_campaign');
        $utmMedium = $request->getParam('utm_medium');

        $landingPageUrl = Mage::helper('core/url')->getCurrentUrl();
        $landingPageLength = strlen($landingPageUrl);
        $landingPageUrl = substr($landingPageUrl, 0, 2000);

        if ($utmSource && $utmCampaign && $utmMedium) {
            $timestamp = $currentTimestamp;
            $visitorData = array(
                'utm_source' => $utmSource,
                'utm_campaign' => $utmCampaign,
                'utm_medium' => $utmMedium
            );
        }

        $gbraid = $this->getArrayParameter('gbraid', $profitMetricsCookieData);

        if (!$gbraid) {
            $gbraid = $request->getParam('gbraid');
        }

        $wbraid = $this->getArrayParameter('wbraid', $profitMetricsCookieData);

        if (!$wbraid) {
            $wbraid = $request->getParam('wbraid');
        }

        return [
            'gacid' => $this->getArrayParameter('gacid', $profitMetricsCookieData),
            'gacid_source' => $this->getArrayParameter('gacid_source', $profitMetricsCookieData),
            'gclid' => $googleClickId,
            'fbp' => $this->getArrayParameter('fbp', $profitMetricsCookieData),
            'fbc' => $facebookClickId,
            'cua' => Mage::helper('core/http')->getHttpUserAgent(),
            'cip' => Mage::helper('core/http')->getRemoteAddr(false),
            't' => $visitorData ? json_encode($visitorData, true) : '',
            'timestamp' => $timestamp,
            'gbraid' => $gbraid,
            'wbraid' => $wbraid,
            'ga_session_id' => $this->getArrayParameter('ga4SessionId', $profitMetricsCookieData),
            'ga_session_count' => $this->getArrayParameter('ga4SessionCount', $profitMetricsCookieData),
            'landing_page' => $landingPageUrl,
            'landing_page_length' => $landingPageLength,
            'cc_statistics' => $this->getArrayParameter('cc_statistics', $profitMetricsCookieData),
            'cc_marketing' => $this->getArrayParameter('cc_marketing', $profitMetricsCookieData),
        ];
    }

    /**
     * @param $parameter
     * @param $parameters
     * @return string
     */
    private function getArrayParameter($parameter, $parameters)
    {
        return isset($parameters[$parameter]) && $parameters[$parameter]
            ? (string)$parameters[$parameter]
            : '';
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function onSalesOrderSaveBefore(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()->getData('order');

        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');

        $profitMetricsVisitorId = $customerSession->getData(
            Profitmetrics_MagentoIntegration_Helper_Data::PROFITMETRICS_VISITOR_ID_SESSION_KEY
        );


        if ($order && $profitMetricsVisitorId && !$order->getData('profitmetrics_visitor_id')) {
            $order->setData('profitmetrics_visitor_id', $profitMetricsVisitorId);
        }
    }
}
