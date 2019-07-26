<?php

class TM_CheckoutSuccess_Helper_Data extends Mage_Core_Helper_Abstract
{

    protected $_modules = array(
        'checkout' =>  'onepage/success',
        'onestepcheckout' => 'index/success',
    );

    public function isEnabled()
    {
        return Mage::getStoreConfigFlag('checkoutsuccess/general/enabled');
    }

    public function getQuickRegisterUrl()
    {
        $baseUrl = rtrim(
            Mage::getBaseUrl(
                Mage_Core_Model_Store::URL_TYPE_LINK,
                $this->_getRequest()->isSecure()
            ),
            '/'
        );
        return $baseUrl . '/checkoutsuccess/quick/register';
    }

    public function getConfigCmsBlock1()
    {
        return Mage::getStoreConfig('checkoutsuccess/mockupsets/block1');
    }

    public function getConfigCmsBlock2()
    {
        return Mage::getStoreConfig('checkoutsuccess/mockupsets/block2');
    }

    public function getConfigCmsBlock3()
    {
        return Mage::getStoreConfig('checkoutsuccess/mockupsets/block3');
    }

    public function getConfigCmsBlock4()
    {
        return Mage::getStoreConfig('checkoutsuccess/mockupsets/block4');
    }

    public function isSuccessPage()
    {
        $request = $this->_getRequest();
        $name = $request->getModuleName();
        if (isset($this->_modules[$name])) {
            $controllerAction = $request->getControllerName()
                . '/'
                . $request->getActionName();
            return $this->_modules[$name] == $controllerAction;
        }

        return false;
    }

}
