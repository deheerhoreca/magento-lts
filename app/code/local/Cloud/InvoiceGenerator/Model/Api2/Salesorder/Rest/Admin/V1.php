<?php

class Cloud_Invoicegenerator_Model_Api2_SalesOrder_Rest_Admin_V1 extends Mage_Api2_Model_Resource {

    public function _retrieve() {
        //retrieve a group name by ID
        $orderid = $this->getRequest()->getParam('id');
        $order = Mage::getModel('sales/order')->load($orderid);

        $payment = $order->getPayment();
        $pOrder = Mage::helper('cloudinvoicegenerator/parser')->parseOrder($order, $payment);

        $orderData = array();
        $orderData['order_id'] = $order->getEntityId();
        $orderData['order_number'] = $order->getIncrementalId();
        $orderData['entity_id'] = $order->getEntityId();
        $orderData['orderobj'] = $pOrder;

        $baddress = $this->_getAddresses($order->getBillingAddressId());
        $order['billingaddress'] = Mage::helper('cloudinvoicegenerator/parser')->parseAddress($baddress);
        $saddress = $this->_getAddresses($order->getShippingAddressId());
        $order['shippingaddress'] = Mage::helper('cloudinvoicegenerator/parser')->parseAddress($saddress);

        $items = $this->_getItems($order);
        $orderData['order_items'] = Mage::helper('cloudinvoicegenerator/parser')->parseOrderitems($items);

        return $orderData;
    }

    public function _create() {
    }

    public function _update() {
    }

    public function _delete() {
    }

    public function _retrieveCollection() {
        $startdate = $this->getRequest()->getParam('startdate');
        $enddate = $this->getRequest()->getParam('enddate');
        
        $ordercollection = Mage::getModel('sales/order')->getCollection();
        $ordercollection->addFieldToFilter('created_at', array('from' => $startdate));
        $ordercollection->addFieldToFilter('created_at', array('to' => $enddate));
  
        $ordersData = array();
        $magentoorders = array();

        foreach ($ordercollection->getItems() as $order) {
            $payment = $order->getPayment();
            $pOrder = Mage::helper('cloudinvoicegenerator/parser')->parseOrder($order, $payment);
            $orderData = array();
            $orderData['order_id'] = $order->getEntityId();
            $orderData['order_number'] = $order->getIncrementalId();
            $orderData['entity_id'] = $order->getEntityId();
            $orderData['orderobj'] = $pOrder;

            $billingaddress = $this->_getAddresses($order->getBillingAddressId());
            $invoiceData['billingaddress'] = Mage::helper('cloudinvoicegenerator/parser')->parseAddress($billingaddress);
            $shippingaddress = $this->_getAddresses($order->getShippingAddressId());
            $invoiceData['shippingaddress'] = Mage::helper('cloudinvoicegenerator/parser')->parseAddress($shippingaddress);
            $items = $this->_getItems($order);
            $orderData['order_items'] = Mage::helper('cloudinvoicegenerator/parser')->parseOrderitems($items);

            $ordersData[] = $orderData;
        }

        return $ordersData;
    }

    public function _multiUpdate() {
    }

    public function _multiDelete() {
    }

    private function _getItems($order) {
        $items = array();

        $collection = $order->getAllItems();

        foreach ($collection as $item) {
            $product=  Mage::getModel('catalog/product')->load($item->getProductId());
            $items[] = $item;
        }
        return $items;
    }

    private function _getAddresses($billing_address_id) {
        $address = Mage::getModel('sales/order_address')->load($billing_address_id);

        return $address;
    }

}
