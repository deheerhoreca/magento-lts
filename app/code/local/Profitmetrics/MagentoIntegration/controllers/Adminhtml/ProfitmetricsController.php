<?php

class Profitmetrics_MagentoIntegration_Adminhtml_ProfitmetricsController extends Mage_Adminhtml_Controller_Action
{
    const FEED_FAILED_REFRESH_TIMEOUT = 300;// 5 minutes
    const DEFAULT_LIMIT_DAYS = 5;
    const MASS_RESEND_LIMIT = 250;

    public function generatefeedAction()
    {
        /** @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('adminhtml/session');

        /** @var Profitmetrics_MagentoIntegration_Helper_Data $profitmetricsHelper */
        $profitmetricsHelper = Mage::helper('profitmetrics');

        if(!Mage::getSingleton('admin/session')->isLoggedIn()) {
            Mage::getSingleton('core/session')->addError(
                $profitmetricsHelper->__('You are not authorized to request this. Please log in to Magento admin')
            );
            return $this->_redirectReferer();
        }

        $flag = Mage::getModel(
            'core/flag',
            array(
                'flag_code' => Profitmetrics_MagentoIntegration_Model_Cron::CORE_FLAG_KEY_PROFITMETRICS_RUNNING
            )
        )->loadSelf();
        if ($flag->getId() && !$this->isFeedGeneratedDateExpired($flag)) {
            Mage::getSingleton('adminhtml/session')->addError(
                $profitmetricsHelper->__('Feed is being generated now. Please wait.')
            );
            return $this->_redirectReferer();
        }

        $cron = Mage::getModel('profitmetrics/cron');

        try {
            $cron->exportProductData();
            $session->addSuccess($profitmetricsHelper->__('Feeds are successfully generated.'));
        } catch (Exception $exception) {
            $session->addError(
                Mage::helper('profitmetrics')->__(
                    'Unable to generate feeds. Please check system logs. Error details:'
                )
                . $exception->getMessage()
            );
            Mage::logException($exception);
        }

        $this->_redirectReferer();
    }

    /**
     * @param Mage_Core_Model_Flag $flag
     * @return bool
     */
    protected function isFeedGeneratedDateExpired(Mage_Core_Model_Flag $flag)
    {
        $currentTimestamp = Mage::getModel('core/date')->timestamp();

        if (!($lastFeedGenerateTimestamp = $flag->getFlagData())) {
            return false;
        }

        return $currentTimestamp - $lastFeedGenerateTimestamp > self::FEED_FAILED_REFRESH_TIMEOUT;
    }

    public function sendOrdersAction()
    {
        /** @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('adminhtml/session');
        /** @var Profitmetrics_MagentoIntegration_Helper_Data $profitMetricsHelper */
        $profitMetricsHelper = Mage::helper('profitmetrics');

        try {
            Mage::getModel('profitmetrics/order_service')->sendOrders();
            $session->addSuccess($profitMetricsHelper->__('Orders data has been sent to ProfitMetrics server'));
        } catch (Exception $exception) {
            $session->addError(
                $profitMetricsHelper->__(
                    'Unable to send data to ProfitMetrics. Please check system logs. Error details:'
                )
                . $exception->getMessage()
            );

            Mage::logException($exception);
        }

        $this->_redirectReferer();
    }

    /**
     * @return void
     */
    public function massOrdersResendAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());

        if (!empty($orderIds)) {
            /** @var Mage_Sales_Model_Resource_Order_Collection $ordersCollection */
            $ordersCollection = Mage::getResourceModel('sales/order_collection')
                ->addFieldToFilter('entity_id', ['in' => $orderIds]);
            /** @var Profitmetrics_MagentoIntegration_Helper_Data $profitMetricsHelper */
            $profitMetricsHelper = Mage::helper('profitmetrics');

            if (!($startDate = $profitMetricsHelper->getOldestVisitorDate())) {
                $lastDaysNumber = $profitMetricsHelper->getOrderSendDays() ?: self::DEFAULT_LIMIT_DAYS;
                $startDate = Mage::getModel('core/date')->date(null, '-' . $lastDaysNumber . ' days');
            }

            $ordersCollection->addFieldToFilter('created_at', ['gteq' => $startDate]);
            $ordersCollection->setPageSize(self::MASS_RESEND_LIMIT);

            $transaction = Mage::getModel('core/resource_transaction');
            $scheduledOrdersCount = 0;

            /** @var Mage_Sales_Model_Order $order */
            foreach ($ordersCollection as $order) {
                $order->setData('profitmetrics_sent_date', null);
                $transaction->addObject($order);
                $scheduledOrdersCount++;
            }

            try {
                $transaction->save();
                $this->_getSession()->addSuccess(
                    $this->__(
                        'A total of %1 order(s) have been scheduled to be resent to ProfitMetrics.',
                        $scheduledOrdersCount
                    )
                );
            } catch (\Exception $exception) {
                Mage::logException($exception);
                $this->_getSession()->addSuccess(
                    $this->__(
                        'A total of %1 record(s) haven\'t been scheduled for sending to ProfitMetrics. Please see server logs for more details.',
                        $scheduledOrdersCount
                    )
                );
            }
        }

        $this->_redirect('adminhtml/sales_order/');
    }

    /**
     * @return Profitmetrics_MagentoIntegration_Adminhtml_ProfitmetricsController|void
     */
    public function generateCustomersAction()
    {
        // DHH CORE HACK -- 1G crashed the script
        ini_set("memory_limit", "4G");
        
        try {
            $fileName = Mage::helper('profitmetrics/customerExport')->createCsv();
            $path = Mage::getBaseDir('var') . DS . 'export';
            $file = $path . DS . $fileName;
            return $this->_prepareDownloadResponse(
                $fileName, file_get_contents($file)
            );

        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirect('*/*/');
    }
}
