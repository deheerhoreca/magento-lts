<?php

class Profitmetrics_MagentoIntegration_FeedController extends Mage_Core_Controller_Front_Action
{
    /**
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     * @throws Zend_Controller_Request_Exception
     */
    public function indexAction()
    {
        /** @var Profitmetrics_MagentoIntegration_Helper_Data $helper */
        $helper = Mage::helper('profitmetrics');

        if (!$helper->checkSecretCode($this->getRequest()->getParam('code'))) {
            $this->getResponse()
                ->setBody($helper->__('You are not authorized to request these data'));
            return;
        }

        if(!$helper->isEnabled()) {
            $this->getResponse()
                ->setBody($helper->__('Module is disabled'));
            return;
        }

        $store = null;

        if ($storeCode = $this->getRequest()->getParam('store')) {
            $store = Mage::app()->getStore($storeCode);
        }
        $feedFilePath = $this->getFeedFilePath($store);

        $feedContents = file_get_contents($feedFilePath);

        if (!$feedContents) {
            $this->getResponse()
                ->setBody($helper->__('No feed data'));
            return;
        }

        $this->getResponse()
            ->setHeader('Content-Type', 'text/xml')
            ->setBody(file_get_contents($feedFilePath));
    }

    /**
     * @param $store
     * @return string
     */
    protected function getFeedFilePath($store)
    {
        /** @var Profitmetrics_MagentoIntegration_Helper_Data $helper */
        $helper = Mage::helper('profitmetrics');

        $directoryToExport = Mage::getBaseDir('var') . DS . Profitmetrics_MagentoIntegration_Model_Cron::FEED_DIRECTORY_PATH;

        $feedFileName = $helper->getFeedFileName($store);
        if ($store && ($storeCode = $store->getCode())) {
            $feedFileName = str_replace('{{store}}', $storeCode, $feedFileName);
        }

        return $directoryToExport . DS . $feedFileName;
    }
}
