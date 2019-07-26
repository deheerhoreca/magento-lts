<?php

class TM_CheckoutSuccess_Model_Observer
{

    protected $_isSuccessPage = null;

    public function registerCurrentOrder($observer)
    {
        if (!Mage::helper('checkoutsuccess')->isEnabled()
            || Mage::registry('current_order')
            || Mage::registry('current_recurring_profile')
        ) {

            return;
        }

        // check if it is success page preview
        $orderToPreview = $observer->getControllerAction()->getRequest()
            ->getParam('previewObjectId');
        if ($orderToPreview) {
            $this->_initPreviewSuccessPage($orderToPreview);
        }

        $session = Mage::getSingleton('checkout/session');

        if (!$session->getLastSuccessQuoteId()) {
            return;
        }

        $lastQuoteId = $session->getLastQuoteId();
        $lastOrderId = $session->getLastOrderId();
        $lastRecurringProfiles = $session->getLastRecurringProfileIds();
        if (!$lastQuoteId || (!$lastOrderId && empty($lastRecurringProfiles))) {
            return;
        }

        if ($lastOrderId) {
            $order = Mage::getModel('sales/order')->load($lastOrderId);
            if ($order->getId()) {
                // doublecheck of successfull order loading
                // IWD_OrderManager cause errors (it 'hides' orders)
                Mage::register('current_order', Mage::getModel('sales/order')->load($lastOrderId));
            }
        } elseif ($lastRecurringProfiles) {
            $profileId = current($lastRecurringProfiles);
            Mage::register(
                'current_recurring_profile',
                Mage::getModel('sales/recurring_profile')->load($profileId)
            );
        }
    }

    public function updateLayout($observer)
    {
        if (!Mage::helper('checkoutsuccess')->isEnabled()
            || !$this->isSuccessPage()) {
            return;
        }

        if (Mage::registry('current_order')) {
            Mage::app()->getLayout()->getUpdate()->addHandle('tm_checkoutsuccess_order_view');
        } elseif (Mage::registry('current_recurring_profile')) {
            Mage::app()->getLayout()->getUpdate()->addHandle('tm_checkoutsuccess_recurring_profile_view');
        }
    }

    public function addCustomJsCss($observer)
    {
        if (!Mage::helper('checkoutsuccess')->isEnabled()
            || !$this->isSuccessPage()) {
            return;
        }

        $head = Mage::app()->getLayout()->getBlock('head');
        if (!$head) {
            return $this;
        }

        $design = Mage::getDesign();
        $customFiles = array(
            'skin_css' => array(
                'tm/checkoutsuccess/css/custom.css'
            ),
            'skin_js' => array(
                'tm/checkoutsuccess/js/custom.js'
            )
        );

        foreach ($customFiles as $type => $files) {
            foreach ($files as $file) {
                $fileUrl = $design->getSkinUrl($file);
                // detect path to the file including package and theme
                preg_match('/\/skin\/frontend\/(.+)/', $fileUrl, $matches);
                if (empty($matches[1])) {
                    continue;
                }

                // check is file is actually exists
                $baseDir = Mage::getBaseDir();
                $absolutePath = $baseDir . '/skin/frontend/' . $matches[1];
                if (is_readable($absolutePath)) {
                    $head->addItem($type, $file, "");
                }
            }
        }
    }

    protected function _initPreviewSuccessPage($idToPreview)
    {
        $order = Mage::getModel('sales/order')
            ->loadByIncrementId($idToPreview);
        if (!$order->getId()) {
            $recurringProfile = Mage::getModel('sales/recurring_profile')
                ->load($idToPreview, 'reference_id');
            if (!$recurringProfile->getId()) {
                return;
            }
        }

        $previewAllowed = false;
        $switchSessionName = 'adminhtml';
        $currentSessionId = Mage::getSingleton('core/session')->getSessionId();
        $currentSessionName = Mage::getSingleton('core/session')->getSessionName();
        if ($currentSessionId && $currentSessionName && isset($_COOKIE[$currentSessionName])) {
            if (isset($_COOKIE[$switchSessionName])) {
                $switchSessionId = $_COOKIE[$switchSessionName];
                $this->_switchSession($switchSessionName, $switchSessionId);
                // Now you are in admin session. Get all the details you want using the below format:
                // $whateverData = Mage::getModel('mymodule/session')->getWhateverData();
                if (Mage::getModel('admin/session')->getUser()) {
                    $previewAllowed = true;
                }

                $this->_switchSession($currentSessionName, $currentSessionId);
            }
        }

        if (!$previewAllowed) {
            return;
        }

        $session = Mage::getSingleton('checkout/session');
        if ($order->getId()) {
            $session->setLastSuccessQuoteId($order->getQuoteId());
            $session->setLastQuoteId($order->getQuoteId());
            $session->setLastOrderId($order->getId());
            $session->setLastRecurringProfileIds();
        } elseif ($recurringProfile->getId()) {
            $orderInfo = $recurringProfile->getOrderInfo();
            if (isset($orderInfo['entity_id'])) {
                $session->setLastSuccessQuoteId($orderInfo['entity_id']);
                $session->setLastQuoteId($orderInfo['entity_id']);
                $session->setLastOrderId('');
                $session->setLastRecurringProfileIds(
                    array($recurringProfile->getId())
                );
            }
        }
    }

    // implamantation taken from http://stackoverflow.com/a/14359144
    protected function _switchSession($namespace, $id = null)
    {
        session_write_close();
        $session = Mage::getSingleton('core/session');
        if ($id) {
            $session->setSessionId($id);
        }

        $session
            ->setSkipEmptySessionCheck(true)
            ->start($namespace);
    }

    public function isSuccessPage()
    {
        if ($this->_isSuccessPage === null ) {
            $this->_isSuccessPage = Mage::helper('checkoutsuccess')
                ->isSuccessPage();
        }

        return $this->_isSuccessPage;
    }

}
