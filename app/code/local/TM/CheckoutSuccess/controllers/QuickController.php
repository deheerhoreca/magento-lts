<?php

class TM_CheckoutSuccess_QuickController extends Mage_Core_Controller_Front_Action
{

    /**
     * stack with messages during customer creation
     * @var array
     */
    protected $_messages = array();

    /**
     * Action quick customer registration on succes checkout page
     * @return boolean
     */
    public function registerAction()
    {

        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->_redirect("/");
            return;
        }

        if (!$this->_validateFormKey()) {
            $this->sendJson(
                array('error' => $this->__("Invalid form key."))
            );
            return false;
        }

        $customer = $this->_registerCustomer();
        $this->_welcomeCustomer($customer);
        $this->_dispatchRegisterSuccess($customer);

        $messages = $this->_getMessages();
        $this->sendJson($messages);

        return !isset($messages['error']);

    }

    /**
     * Set response
     * @param  string $response
     */
    public function sendJson($response)
    {
        $this->getResponse()->clearHeaders()
            ->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(json_encode($response));
    }

    /**
     * register customer base on his data from order
     * @return TM_CheckoutSuccess_QuickController
     */
    protected function _registerCustomer()
    {

        $customer = Mage::getModel('customer/customer');
        $orderId = $this->getRequest()->getParam('last_order');
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        if (!$this->getRequest()->isPost() || !$order->getId()) {
            $this->_addMessage(
                $this->__("Something went wrong... We can not find order."),
                'error'
            );
            return;
        }

        $password = $this->getRequest()->getParam('password');
        $email = $order->getCustomerEmail();

        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        $customer->loadByEmail($email);

        if (!$customer->getId()) {
            $customer->setEmail($email);
            $customer->setFirstname($order->getCustomerFirstname());
            $customer->setLastname($order->getCustomerLastname());
            $customer->setPassword($password);
        } else {
            $this->_addMessage(
                $this->__("Customer with email %s aleady exists.", $email),
                'error'
            );
            return;
        }

        try {
            $customer->save();
            $customer->setConfirmation(null);
            $customer->save();
        }
        catch (Exception $ex) {
            $this->_addMessage(
                $this->__("Sorry, but we can not create new customer."),
                'error'
            );
            return;
        }

        $order->setCustomerId($customer->getId());
        try {
            $order->save();
        }
        catch (Exception $ex) {
            $this->_addMessage(
                $this->__("Failed to update order with customer ID."),
                'notice'
            );
        }

        // save Billing Address
        $customerAddress = Mage::getModel('customer/address');
        $customerAddress->setData($order->getBillingAddress()->getData())
            ->setCustomerId($customer->getId())
            ->setIsDefaultBilling('1')
            ->setIsDefaultShipping('0')
            ->setSaveInAddressBook('1');

        try {
            $customerAddress->save();
        }
        catch (Exception $ex) {
            $this->_addMessage(
                $this->__("Failed to save customer billing address."),
                'notice'
            );
        }

        // save Shipping Address
        $customerAddress = Mage::getModel('customer/address');
        $customerAddress->setData($order->getShippingAddress()->getData())
            ->setCustomerId($customer->getId())
            ->setIsDefaultBilling('0')
            ->setIsDefaultShipping('1')
            ->setSaveInAddressBook('1');

        try {
            $customerAddress->save();
        }
        catch (Exception $ex) {
            $this->_addMessage(
                $this->__("Failed to save customer shipping address."),
                'notice'
            );
        }

        $this->_addMessage(
            $this->__("New customer %s successfully created.", $email)
        );

        return $customer;

    }

    /**
     * Add message to stack
     * @param string $message
     * @param string $type
     */
    protected function _addMessage($message, $type = 'success')
    {
        if (!isset($this->_messages[$type])) {
            $this->_messages[$type] = '';
        }

        $this->_messages[$type] .= ' ' . $message;
        return $this;
    }

    /**
     * Get stack of messages
     * @return array
     */
    protected function _getMessages()
    {
        return $this->_messages;
    }

    /**
     * Send new account welcome email.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return string
     */
    protected function _welcomeCustomer(Mage_Customer_Model_Customer $customer)
    {

        if (!$customer->getId()) {
            return $this;
        }

        $customer->sendNewAccountEmail(
            'registered',
            '',
            Mage::app()->getStore()->getId(),
            $this->getRequest()->getPost('password')
        );

        $this->_addMessage($this->__("We sent you email with credentials."));

        return $this;
    }

    /**
     * Dispatch Event
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    protected function _dispatchRegisterSuccess($customer)
    {
        Mage::dispatchEvent(
            'customer_register_success',
            array('account_controller' => $this, 'customer' => $customer)
        );
    }

}
