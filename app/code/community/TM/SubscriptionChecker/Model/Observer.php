<?php

class TM_SubscriptionChecker_Model_Observer
{
    public function onBeforeConfigView($observer)
    {
        $helper = Mage::helper('subscriptionchecker');
        $section = $observer->getControllerAction()->getRequest()->getParam('section');
        if (!$helper->canValidateConfigSection($section)) {
            return;
        }

        $result = $helper->validateSubscription($section);
        if (is_array($result) && isset($result['error'])) {
            if (isset($result['response'])) {
                $link = Mage::helper('tmcore/debug')->preparePopup(
                    $result['response'],
                    'SwissUpLabs subscription validation response'
                );
                $result['error'] .= ' | ' . $link;
            }
            Mage::getSingleton('adminhtml/session')->addError($result['error']);
        }
    }

    public function onBeforeConfigSave($observer)
    {
        $helper = Mage::helper('subscriptionchecker');
        $section = $observer->getControllerAction()->getRequest()->getParam('section');
        if (!$helper->canValidateConfigSection($section)) {
            return;
        }

        $result = $helper->validateSubscription($section);
        if (is_array($result) && isset($result['error'])) {
            // Mage::getSingleton('adminhtml/session')->addError($result['error']);
            $controller = $observer->getControllerAction();
            $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
            $controller->getResponse()->setRedirect(
                Mage::helper('adminhtml')->getUrl(
                    '*/*/edit',
                    array(
                        '_current' => array('section', 'website', 'store')
                    )
                )
            );
        }
    }
}
