<?php

class TM_FireCheckout_Model_Observer
{
    public function addToCartComplete(Varien_Event_Observer $observer)
    {
        if ($observer->getRequest()->getParam('return_url')) {
            return; // paypal express checkout button was clicked
        }

        $generalConfig = Mage::getStoreConfig('firecheckout/general');
        if (($generalConfig['enabled'] && $generalConfig['redirect_to_checkout'])
            || $observer->getRequest()->getParam('firecheckout')) {

            $observer->getResponse()
                ->setRedirect(
                    Mage::helper('firecheckout/url')->getCheckoutUrl()
                );
            Mage::getSingleton('checkout/session')->setNoCartRedirect(true);
        }
    }

    public function addAdditionalFieldsToResponseFrontend(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('payment/authorizenet_directpost/active')) {
            return Mage::getSingleton('authorizenet/directpost_observer')->addAdditionalFieldsToResponseFrontend($observer);
        }
        return $this;
    }

    /**
     * Saves customer comment and delivery date to quote
     */
    public function adminhtmlAddAdditionalFields($observer)
    {
        $quote   = $observer->getOrderCreateModel()->getQuote();
        $request = $observer->getRequest();

        if (isset($request['firecheckout_customer_comment'])) {
            $quote->setFirecheckoutCustomerComment($request['firecheckout_customer_comment']);
        }

        if (!isset($request['delivery_date']) || !is_array($request['delivery_date'])) {
            return;
        }

        $firecheckout = Mage::getModel('firecheckout/type_standard')->setQuote($quote);
        $result = $firecheckout->saveDeliveryDate($request['delivery_date'], false);

        if (is_array($result)) {
            throw new Exception($result['message']);
        }
    }

    public function validateAddressInformationIfRequired($observer)
    {
        $controller = $observer->getControllerAction();
        $request = $controller->getRequest();
        if ($request->getParam('force_validation')) {
            return $this->validateAddressInformation($observer);
        }
        return $this;
    }

    public function validateAddressInformation($observer)
    {
        if (!Mage::getStoreConfigFlag('firecheckout/address_verification/enabled')) {
            return $this;
        }

        $controller       = $observer->getControllerAction();
        $request          = $controller->getRequest();
        $skipVerification = $request->getQuery('skip-address-verification');
        $block            = $controller->getLayout()
            ->createBlock('firecheckout/address_validator')
            ->setValidator(Mage::getModel('firecheckout/address_validator_usps'));

        $billing = $request->getPost('billing', array());
        if (!$request->getPost('billing_address_id')
            && !$this->_canSkipAddressVerification($billing, $skipVerification)) {

            $block->getValidator()->addAddress($billing, 'billing');
        }

        if (!isset($billing['use_for_shipping']) || !$billing['use_for_shipping']) {
            $shipping = $request->getPost('shipping', array());
            if (!$request->getPost('shipping_address_id')
                && !$this->_canSkipAddressVerification($shipping, $skipVerification)) {

                $block->getValidator()->addAddress($shipping, 'shipping');
            }
        }

        if (!$block->getValidator()->isValid()) {
            $result = array();
            $result['error'] = true;
            $result['body']['content'] = $block->toHtml();
            $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
            $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            return;
        }

        return $this;
    }

    protected function _canSkipAddressVerification($address, $skipVerification)
    {
        if (!isset($address['country_id'])
            || 'US' != $address['country_id']
            || !isset($address['region_id'])) {

            return true;
        }

        $session = Mage::getSingleton('checkout/session');
        $key = md5(implode('_', array(
            'FIRECHECKOUT_ADDRESS_VERIFICATION_SKIP_',
            $address['street'][0],
            isset($address['street'][1]) ? $address['street'][1] : '',
            $address['city'],
            $address['region_id'],
            $address['postcode']
        )));

        if ($session->hasData($key)) { // previously marked as verified
            return true;
        }

        if ($skipVerification) {
            $session->setData($key, 1);
            return true;
        }

        return false;
    }

    public function addCustomJsCss($observer)
    {
        $request = Mage::app()->getRequest();
        if ($request->getRouteName() !== 'firecheckout'
            || $request->getControllerName() !== 'index'
            || $request->getActionName() !== 'index') {

            return;
        }

        $head = Mage::app()->getLayout()->getBlock('head');
        if (!$head) {
            return $this;
        }

        $design = Mage::getDesign();
        $customFiles = array(
            'skin_css' => array(
                'tm/firecheckout/css/custom.css'
            ),
            'skin_js' => array(
                'tm/firecheckout/js/custom.js'
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

    /**
     * This method fixes not working address save, when city or street is hidden
     * in firecheckout settings.
     *
     * @param  Varien_Event_Observer $observer
     * @return
     */
    public function ignoreHardcodedAddressValidationErrors($observer)
    {
        $address = $observer->getAddress();

        if ($address instanceof TM_FireCheckout_Model_Quote_Address_Abstract) {
            return;
        }

        // fix not-working address save on customer account page when some fields are hidden
        $address->setShouldIgnoreValidation(true);
    }

    /**
     * Magento forgot to set PasswordCreatedAt property when
     * paypal_express is used (@see app/code/core/Mage/Paypal/Model/Express/Checkout::_prepareNewCustomerQuote)
     *
     * @param  Varien_Event_Observer $observer
     */
    public function fixCustomerRegistration($observer)
    {
        $customer = $observer->getTarget();
        $session = Mage::getSingleton('checkout/session');
        $checkout = Mage::getModel('firecheckout/type_standard');

        $sessionValidatorData = $session->getData('_session_validator_data');
        if ($customer
            && $checkout->getCheckoutMethod() === 'register'
            && $sessionValidatorData
            && isset($sessionValidatorData['session_expire_timestamp'])
        ) {
            $passwordCreatedTime = $sessionValidatorData['session_expire_timestamp']
                - Mage::getSingleton('core/cookie')->getLifetime();
            $customer->setPasswordCreatedAt($passwordCreatedTime);
        }
    }
}
