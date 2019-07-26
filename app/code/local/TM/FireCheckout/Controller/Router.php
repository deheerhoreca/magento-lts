<?php

class TM_FireCheckout_Controller_Router extends Mage_Core_Controller_Varien_Router_Abstract
{
    /**
     * Initialize Controller Router
     *
     * @param Varien_Event_Observer $observer
     */
    public function initControllerRouters($observer)
    {
        /* @var $front Mage_Core_Controller_Varien_Front */
        $front = $observer->getEvent()->getFront();
        $front->addRouter('firecheckout', $this);
    }

    /**
     * Validate and Match Page and modify request
     *
     * @param Zend_Controller_Request_Http $request
     * @return bool
     */
    public function match(Zend_Controller_Request_Http $request)
    {
        if (!Mage::isInstalled()) {
            return false;
        }

        $pathInfo = trim($request->getPathInfo(), '/');
        $pathParts = explode('/', $pathInfo);

        if ($pathParts[0] !== Mage::helper('firecheckout')->getFirecheckoutUrlPath()) {
            return false;
        }

        // modified firecheckout url support (via config.xml)
        $moduleName = (string)Mage::app()->getConfig()->getNode(
            'frontend/routers/firecheckout/args/frontName'
        );
        $request->setModuleName($moduleName)
            ->setControllerName('index')
            ->setActionName('index');

        $request->setAlias(
            Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS,
            $pathInfo
        );

        return true;
    }
}
