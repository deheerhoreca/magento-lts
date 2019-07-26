<?php

class TM_SubscriptionChecker_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Cache ID for last check time, that returns success
     */
    const LAST_CHECK_TIME_ID  = 'subscriptionchecker_lastcheck';

    public function getFrequency()
    {
        return 24 * 3600;
    }

    public function getLastCheckTime()
    {
        return Mage::app()->loadCache(self::LAST_CHECK_TIME_ID);
    }

    public function updateLastCheckTime()
    {
        Mage::app()->saveCache(time(), self::LAST_CHECK_TIME_ID);
    }

    public function canValidateConfigSection($section)
    {
        $config = Mage::getConfig()->loadModulesConfiguration('system.xml');
        $sections = $config->getNode('sections');
        $tab = (string)$sections->$section->tab;

        $tabsToValidate = array(
            'templates_master',
            // 'tm_checkout'
        );
        if (!in_array($tab, $tabsToValidate)) {
            return false;
        }

        if (($this->getFrequency() + $this->getLastCheckTime()) > time()) {
            return false;
        }

        $ignoredSections = Mage::app()
            ->getConfig()
            ->getNode('default/subscriptionchecker/ignored_sections');

        if (!$ignoredSections) {
            return true;
        }
        foreach ($ignoredSections->children() as $node) {
            if ($section === $node->getName()) {
                return false;
            }
        }
        return true;
    }

    public function validateSubscription($configSection = false)
    {
        $module = Mage::getModel('tmcore/module');
        $module->load('Swissup_Subscription');
        if ($configSection) {
            $module->setConfigSection($configSection);
        }

        $result = array();
        if (!$module->getIdentityKey()) {
            $url = Mage::helper('adminhtml')->getUrl('*/subscriptionchecker_subscription/index');
            $result['error'] = Mage::helper('subscriptionchecker')->__(
                'Please %s SwissUpLabs subscription to use this module',
                sprintf(
                    "<a href='{$url}'>%s</a>",
                    Mage::helper('subscriptionchecker')->__('activate')
                )
            );
        } else {
            $result = $module->validateLicense();
            if (is_array($result) && isset($result['error'])) {
                // try to translate remote response
                $result['error'] = call_user_func_array(array(Mage::helper('tmcore'), '__'), $result['error']);
            } else {
                $this->updateLastCheckTime();
            }
        }

        return $result;
    }
}
