<?php

/**
 * @copyright  For copyright and license information, read the COPYING.txt file.
 * @link       /COPYING.txt
 * @license    Open Software License (OSL 3.0)
 * @package    Mage_Persistent
 */

/**
 * Persistent Session Observer
 *
 * @package    Mage_Persistent
 */
class Mage_Persistent_Model_Observer_Session
{
    /**
     * Create/Update and Load session when customer log in
     */
    public function synchronizePersistentOnLogin(Varien_Event_Observer $observer)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $observer->getEvent()->getCustomer();
        // Check if customer is valid (remove persistent cookie for invalid customer)
        if (!$customer || !$customer->getId() || !Mage::helper('persistent/session')->isRememberMeChecked()) {
            Mage::getModel('persistent/session')->removePersistentCookie();
            return;
        }

        $persistentLifeTime = Mage::helper('persistent')->getLifeTime();
        // Delete persistent session, if persistent could not be applied
        if (Mage::helper('persistent')->isEnabled() && ($persistentLifeTime <= 0)) {
            // Remove current customer persistent session
            Mage::getModel('persistent/session')->deleteByCustomerId($customer->getId());
            return;
        }

        $sessionModel = Mage::helper('persistent/session')->getSession();

        // Check if session is wrong or not exists, so create new session
        if (!$sessionModel->getId() || ($sessionModel->getCustomerId() != $customer->getId())) {
            $sessionModel = Mage::getModel('persistent/session')
                ->setLoadExpired()
                ->loadByCustomerId($customer->getId());
            if (!$sessionModel->getId()) {
                $sessionModel = Mage::getModel('persistent/session')
                    ->setCustomerId($customer->getId())
                    ->save();
            }

            Mage::helper('persistent/session')->setSession($sessionModel);
        }

        // Set new cookie
        if ($sessionModel->getId()) {
            Mage::getSingleton('core/cookie')->set(
                Mage_Persistent_Model_Session::COOKIE_NAME,
                $sessionModel->getKey(),
                $persistentLifeTime,
            );
        }
    }

    /**
     * Unload persistent session (if set in config)
     */
    public function synchronizePersistentOnLogout(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('persistent')->isEnabled() || !Mage::helper('persistent')->getClearOnLogout()) {
            return;
        }

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $observer->getEvent()->getCustomer();
        // Check if customer is valid
        if (!$customer || !$customer->getId()) {
            return;
        }

        Mage::getModel('persistent/session')->removePersistentCookie();

        // Unset persistent session
        Mage::helper('persistent/session')->setSession(null);
    }

    /**
     * Synchronize persistent session info
     */
    public function synchronizePersistentInfo(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('persistent')->isEnabled() || !Mage::helper('persistent/session')->isPersistent()) {
            return;
        }

        $sessionModel = Mage::helper('persistent/session')->getSession();

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $observer->getEvent()->getFront()->getRequest();

        // Quote Id could be changed only by logged in customer
        if (Mage::getSingleton('customer/session')->isLoggedIn()
            || ($request && $request->getActionName() == 'logout' && $request->getControllerName() == 'account')
        ) {
            $sessionModel->save();
        }
    }

    /**
     * Set Checked status of "Remember Me"
     */
    public function setRememberMeCheckedStatus(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('persistent')->canProcess($observer)
            || !Mage::helper('persistent')->isEnabled() || !Mage::helper('persistent')->isRememberMeEnabled()
        ) {
            return;
        }

        $controllerAction = $observer->getEvent()->getControllerAction();
        if ($controllerAction) {
            $rememberMeCheckbox = $controllerAction->getRequest()->getPost('persistent_remember_me');
            Mage::helper('persistent/session')->setRememberMeChecked((bool) $rememberMeCheckbox);
            if ($controllerAction->getFullActionName() == 'checkout_onepage_saveBilling'
                    || $controllerAction->getFullActionName() == 'customer_account_createpost'
            ) {
                Mage::getSingleton('checkout/session')->setRememberMeChecked((bool) $rememberMeCheckbox);
            }
        }
    }

    /**
     * Renew persistent cookie
     */
    public function renewCookie(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('persistent')->canProcess($observer)
            || !Mage::helper('persistent')->isEnabled() || !Mage::helper('persistent/session')->isPersistent()
        ) {
            return;
        }

        $controllerAction = $observer->getEvent()->getControllerAction();

        if (Mage::getSingleton('customer/session')->isLoggedIn()
            || $controllerAction->getFullActionName() == 'customer_account_logout'
        ) {
            Mage::getSingleton('core/cookie')->renew(
                Mage_Persistent_Model_Session::COOKIE_NAME,
                Mage::helper('persistent')->getLifeTime(),
            );
        }
    }
}
