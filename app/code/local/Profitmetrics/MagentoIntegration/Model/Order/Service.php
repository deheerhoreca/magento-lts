<?php

class Profitmetrics_MagentoIntegration_Model_Order_Service
{
    const VERSION = '2';
    const VERSION_HEADLESS = '3uh';
    const MODULE_VERSION = '0.0.21'; // @todo implement getting from configs

    /**
     * @var string
     */
    protected $moduleVersion;

    public function sendOrders()
    {
        /** @var Profitmetrics_MagentoIntegration_Helper_Data $profitMetricsHelper */
        $profitMetricsHelper = Mage::helper('profitmetrics');
        if (!Mage::helper('profitmetrics')->isEnabled()) {
            return;
        }

        $orderStatusesToSend = $profitMetricsHelper->getOrderStatusesToSend();
        $orderSendDays = $profitMetricsHelper->getOrderSendDays();

        /** @var \Magento\Sales\Model\Order $order */
        foreach($this->getOrdersToSend($orderStatusesToSend, $orderSendDays) as $order) {
            if (!$this->checkAbleToRetry($order)) {
                continue;
            }

            try {
                if ($this->sendOrderData($order)) {
                    $this->updateOrderSetProfitMetricsSentDate($order);
                } else {
                    $this->updateOrderSetProfitMetricsFailedDate($order);
                }
            } catch (\Exception $exception) {
                $this->updateOrderSetProfitMetricsFailedDate($order);
                Mage::logException($exception);
            }
        }
    }

    /**
     * @param array $orderStatusesToSend
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    protected function getOrdersToSend($orderStatusesToSend = [], $orderSendDays = null)
    {
        /** @var Mage_Sales_Model_Resource_Order_Collection $orderCollection */
        $orderCollection = Mage::getResourceModel('sales/order_collection');
        $orderCollection->addFieldToFilter('main_table.profitmetrics_visitor_id', array('notnull' => true));
        $orderCollection->addFieldToFilter('main_table.profitmetrics_sent_date', array('null' => true));
        
        // DHH CORE HACK -- Module was crashing on trying to send unfinished orders (with store_id=NULL)
        // @note reason for these weird empty orders is unclear but this filter prevents the crashes
        $orderCollection->addFieldToFilter("store_id", 1); // chefstore.nl

        if ($orderStatusesToSend) {
            $orderCollection->addFieldToFilter('main_table.status', ['in' => $orderStatusesToSend]);
        }

        if ($orderSendDays) {
            $minCreatedAtDateTime = Mage::getModel('core/date')->date('Y-m-d H:i:s', '-' . $orderSendDays . ' days');

            if ($oldestVisitorDateTime = $this->getOldestVisitorDate()) {
                $minCreatedAtDateTime = max($minCreatedAtDateTime, $oldestVisitorDateTime);
            }

            if ($moduleInstallationDate = Mage::helper('profitmetrics')->getModuleInstallationDate()) {
                $startDate = max($minCreatedAtDateTime, $moduleInstallationDate);
            }

            $orderCollection->addFieldToFilter('main_table.created_at', ['gteq' => $minCreatedAtDateTime]);
        }

        $orderCollection->getSelect()->joinLeft(
            ['visitor' => $orderCollection->getTable('profitmetrics/tracking')],
            'visitor.entity_id = main_table.profitmetrics_visitor_id',
            [
                'gacid', 'gacid_source', 'gclid', 'fbp', 'fbc', 'cua', 'cip', 't', 'gbraid', 'wbraid', 'ga_session_id',
                'ga_session_count', 'landing_page', 'landing_page_length', 'cc_statistics', 'cc_marketing'
            ]
        );

        return $orderCollection;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return bool
     * @throws Zend_Http_Client_Exception
     */
    protected function sendOrderData(Mage_Sales_Model_Order $order)
    {
        $profitMetricsApiUrl = $this->getProfitMetricsApiUrl();
        $parameters = [
            'v' => self::VERSION_HEADLESS,
            'cv' => 'magento-' . Mage::getVersion() . '__' . $this->getModuleVersion(),
            'pid' => Mage::helper('profitmetrics')->getStorePublicId($order->getStore()),
            'o' => $this->getOrderSpecification($order),
            'cc_statistics' => $order->getCcStatistics()? 'true' : 'false',
            'cc_marketing' => $order->getCcMarketing()? 'true' : 'false',
        ];

        if (!Mage::helper('profitmetrics')->getIsHeadlessModeActive()) {
            $parameters['v'] = self::VERSION;
            $parameters['gacid'] = urlencode((string) $order->getGacid());
            $parameters['gacid_source'] = urlencode((string) $order->getGacidSource());
            $parameters['gclid'] = urlencode((string) $order->getGclid());
            $parameters['fbp'] = urlencode((string) $order->getFbp());
            $parameters['fbc'] = urlencode((string) $order->getFbc());
            $parameters['cua'] = urlencode((string) $order->getCua());
            $parameters['cip'] = urlencode((string) $order->getCip());
            $parameters['t'] = urlencode((string)$order->getT());
            $parameters['gbraid'] = urlencode((string) $order->getGbraid());
            $parameters['wbraid'] = urlencode((string) $order->getWbraid());
            $parameters['ga4_sessionid'] = urlencode((string) $order->getData('ga_session_id'));
            $parameters['ga4_sessioncount'] = urlencode((string) $order->getData('ga_session_count'));
            $parameters['landing_page'] = urlencode((string) $order->getLandingPage());
            $parameters['landing_page_length'] = urlencode((string) $order->getLandingPageLength());
        }

        $parameters = array_filter($parameters, static function ($value) {
            return (string) $value !== '';
        });
        $url = Mage::helper('core/url')->addRequestParam($profitMetricsApiUrl, $parameters);

        Mage::log("Profit Metrics API Url: " . $url);

        $client = new Zend_Http_Client($url, array(
            'maxredirects' => 0,
            'timeout'      => 120));


        $response = $client->request(Zend_Http_Client::GET);

        return strpos($response->getBody(), '//unknown') === false && $response->getStatus() === 200;
    }

    protected function updateOrderSetProfitMetricsSentDate(Mage_Sales_Model_Order $order)
    {
        try {
            /** @var Magento_Db_Adapter_Pdo_Mysql $connection */
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $connection->update(
                Mage::getSingleton('core/resource')->getTableName('sales_flat_order'),
                ['profitmetrics_sent_date' => Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s')],
                'entity_id = ' . $order->getId()
            );
        } catch (\Exception $exception) {
            Mage::logException($exception);
        }
    }

    protected function updateOrderSetProfitMetricsFailedDate(Mage_Sales_Model_Order $order)
    {
        try {
            /** @var Magento_Db_Adapter_Pdo_Mysql $connection */
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $connection->update(
                Mage::getSingleton('core/resource')->getTableName('sales_flat_order'),
                [
                    'profitmetrics_failure_date' => Mage::getModel('core/date')->gmtDate(
                        'Y-m-d H:i:s',
                        '- 0 minutes'
                    ),
                    'profitmetrics_failure_count' => $order->getProfitmetricsFailureCount() + 1
                ],
                'entity_id = ' . $order->getId()
            );
        } catch (\Exception $exception) {
            Mage::logException($exception);
        }
    }

    /**
     * @return string
     */
    private function getProfitMetricsApiUrl()
    {
        switch (Mage::helper('profitmetrics')->getProfitMetricsApiEndpointType()) {
            case 'testinst1':
                $endpointUrl = 'https://testinst1.int.profitmetrics.io/l.php';
                break;
            case 'testinst2':
                $endpointUrl = 'https://testinst2.int.profitmetrics.io/l.php';
                break;
            case 'testlocal':
                $endpointUrl = 'http://m1-profitmetrics.allbugs.info/l.php';
                break;
            case 'live':
            default:
                $endpointUrl = 'https://my.profitmetrics.io/l.php';
                break;
        }

        return $endpointUrl;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    private function getOrderSpecification(Mage_Sales_Model_Order $order)
    {
        return urlencode($this->getOrderDataJson($order));
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getOrderData(Mage_Sales_Model_Order $order)
    {
        if (!$order->getId()) {
            return array();
        }

        $itemsData = array();
        $simpleProductIdsByOrderItemId = array();

        /** @var Mage_Sales_Model_Quote_Item|Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllItems() as $item) {
            // skipping simple items which has configurable parent ones
            if (
                ($parentItem = $item->getParentItem())
                && ($product = $parentItem->getProduct())
                && ($product->getTypeId() === 'configurable')
            ) {
                $simpleProductIdsByOrderItemId[$parentItem->getId()] = $item->getProductId();
                continue;
            }

            if (($item->getProductType() === 'bundle')) {
                continue;
            }

            // DHH CORE HACK: Replace useless internal ID with DHH SKU so it makes sense in the ProfitMetrics dashboard
            // It seems ProfitMetrics has locked the SKU as its product ID field and it won't change by adding pm:id to the feed
            $itemsData[] = array(
                'orderItemId' => $item->getId(),
                // 'sku' => $item->getProductId(),
                'sku' => $item->getSku(),
                'qty' => (int) $item->getQtyOrdered(),
                'priceExVat' => (float)sprintf('%5.2f', $item->getPrice())
            );
        }

        foreach ($itemsData as $key => $item) {
            $orderItemId = $item['orderItemId'];
            $productId = $item['sku'];

            if (isset($simpleProductIdsByOrderItemId[$orderItemId])) {
                $productId = $simpleProductIdsByOrderItemId[$orderItemId];
            }

            $itemsData[$key]['sku'] = (string)$productId;
            unset($itemsData[$key]['orderItemId']);
        }

        return array(
            'id' => (string) $order->getIncrementId(),
            'orderEmail' => $order->getCustomerEmail(),
            'customerPhone' => $order->getBillingAddress()->getTelephone(),
            'currency' => $order->getStore()->getCurrentCurrencyCode(),
            'priceShippingExVat' => (float) $order->getShippingAmount(),
            'priceTotalExVat' => (float) sprintf('%5.2f', $order->getGrandTotal() - $order->getTaxAmount()),
            'priceTotalInclVat' => (float)sprintf('%5.2f', $order->getGrandTotal()),
            'shippingMethod' => $order->getShippingMethod(),
            'paymentMethod' => $order->getPayment()->getMethod(),
            'products' => $itemsData,
            'shippingCountry' => $order->getShippingAddress() ? $order->getShippingAddress()->getCountryId() : null,
            'shippingZipcode' => $order->getShippingAddress() ? $order->getShippingAddress()->getPostcode() : null,
            'shippingWeight' => $order->getWeight() * Mage::helper('profitmetrics')->getWeightMultiplier(),
            'voucherCode' => $order->getCouponCode(),
            'ts' => Mage::getModel('core/date')->gmtTimestamp($order->getCreatedAt())
        );
    }

    /**
     * @param string $data
     * @return string
     */
    public function escapeData($data)
    {
        return str_replace('"', '\"', $data);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getOrderDataJson(Mage_Sales_Model_Order $order)
    {
        if (!$order || !$order->getId()) {
            return '';
        }

        return Mage::helper('core')->jsonEncode($this->getOrderData($order));
    }

    /**
     * @return string
     */
    protected function getOldestVisitorDate()
    {
        $oldestVisitorDate = '';
        /** @var Profitmetrics_MagentoIntegration_Model_Resource_Tracking_Collection $visitorCollection */
        $visitorCollection = Mage::getResourceModel('profitmetrics/tracking_collection');
        $visitorCollection->setOrder('created_at', 'ASC');
        $visitorCollection->setPageSize(1);
        $oldestVisitor = $visitorCollection->getFirstItem();

        if ($oldestVisitor->getCreatedAt()) {
            $oldestVisitorDate = $oldestVisitor->getCreatedAt();
        }

        return $oldestVisitorDate;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    protected function checkAbleToRetry(Mage_Sales_Model_Order $order)
    {
        if (!($failureCount = $order->getProfitmetricsFailureCount())) {
            return true;
        }

        if (!($failureDate = $order->getProfitmetricsFailureDate())) {
            return true;
        }

        switch ($failureCount) {
            case 1:
                $minutesDelay = 5;
                break;
            case 2:
                $minutesDelay = 60;
                break;
            case 3:
                $minutesDelay = 180;
                break;
            default:
                $minutesDelay = 540;
        }

        $dateForRetry = Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s', '-' . $minutesDelay . ' minutes');

        return $failureDate <= $dateForRetry;
    }

    /**
     * @return string
     */
    public function getModuleVersion()
    {
        if (!isset($this->moduleVersion)) {
            $this->moduleVersion = (string)Mage::getConfig()->getNode('modules/Profitmetrics_MagentoIntegration/version');
        }

        return $this->moduleVersion;
    }
}
