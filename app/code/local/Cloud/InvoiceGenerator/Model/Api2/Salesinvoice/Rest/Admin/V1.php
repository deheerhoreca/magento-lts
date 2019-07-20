<?php

class Cloud_Invoicegenerator_Model_Api2_SalesInvoice_Rest_Admin_V1 extends Mage_Api2_Model_Resource {

    public function _retrieve() {
        //retrieve a group name by ID
        $invoiceid = $this->getRequest()->getParam('id');
        $invoice = Mage::getModel('sales/order_invoice')->load($invoiceid);
        $items = $this->_getItems($invoice->getEntityId());
        $invoice['invoice_lines'] = Mage::helper('cloudinvoicegenerator/parser')->parseInvoicelines($items);
        $baddress = $this->_getAddresses($invoice->getBillingAddressId());
        $invoice['billingaddress'] = Mage::helper('cloudinvoicegenerator/parser')->parseAddress($baddress);
        $saddress = $this->_getAddresses($invoice->getShippingAddressId());
        $invoice['shippingaddress'] = Mage::helper('cloudinvoicegenerator/parser')->parseAddress($saddress);
        $order = $this->_getOrder($invoice->getOrderId());
        $invoice['order_number'] = $order->getIncrementalId();

        return $invoice;
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
        
        $invoicecollection = Mage::getModel('sales/order_invoice')->getCollection();
        $invoicecollection->addFieldToFilter('created_at', array('from' => $startdate));
        $invoicecollection->addFieldToFilter('created_at', array('to' => $enddate));
  
        $invoicesData = array();
        $magentoinvoices = array();

        foreach ($invoicecollection->getItems() as $invoice) {
            $order = $invoice->getOrder();
            $payment = $order->getPayment();
            $pInvoice = Mage::helper('cloudinvoicegenerator/parser')->parseInvoice($invoice, $payment);
            $invoiceData['order_id'] = $order->getEntityId();
            $invoiceData = array();
            $invoiceData['entity_id'] = $invoice->getEntityId();
            $invoiceData['invoiceobj'] = $pInvoice;

            $billingaddress = $this->_getAddresses($invoice->getBillingAddressId());
            $invoiceData['billingaddress'] = Mage::helper('cloudinvoicegenerator/parser')->parseAddress($billingaddress);
            $shippingaddress = $this->_getAddresses($invoice->getShippingAddressId());
            $invoiceData['shippingaddress'] = Mage::helper('cloudinvoicegenerator/parser')->parseAddress($shippingaddress);
            $items = $this->_getItems($invoice->getEntityId());
            $invoiceData['invoice_lines'] = Mage::helper('cloudinvoicegenerator/parser')->parseInvoicelines($items);
            $order = $this->_getOrder($invoice->getOrderId());
            $invoiceData['order_number'] = $order->getIncrementalId();

            $invoicesData[] = $invoiceData;
        }

        return $invoicesData;
    }

    public function _multiUpdate() {
    }

    public function _multiDelete() {
    }

    private function _getItems($invoiceid) {
        $items = array();

        $collection = Mage::getResourceModel('sales/order_invoice_item_collection');
        $collection->addFieldToFilter('parent_id', array('eq' => $invoiceid));

        foreach ($collection->getItems() as $item) {
            $product=  Mage::getModel('catalog/product')->load($item->getProductId());
            $items[] = $item;
        }
        return $items;
    }

    private function _getAddresses($billing_address_id) {
        $address = Mage::getModel('sales/order_address')->load($billing_address_id);

        return $address;
    }

    private function _getOrder($orderid) {
        $address = Mage::getModel('sales/order')->load($orderid);

        return $address;
    }

    private function parseInvoice($invoice, $payment) {
        $parsed_invoice = array();
        $parsed_invoice = $invoice;
        return $parsed_invoice;
    }

    private function parseAddress($address) {
        $parsed_address = array();
        $parsed_address = $address;
        return $parsed_address;
    }

    private function parseItems($items) {
        $parsed_items = array();
        $parsed_items = $items;
        return $parsed_items;
    }

}
