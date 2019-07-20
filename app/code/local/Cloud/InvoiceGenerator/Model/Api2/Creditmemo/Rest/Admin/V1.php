<?php

class Cloud_Invoicegenerator_Model_Api2_Creditmemo_Rest_Admin_V1 extends Mage_Api2_Model_Resource {

    public function _retrieve() {       
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
        
        $invoicecollection = Mage::getResourceModel('sales/order_creditmemo_collection');
        $invoicecollection->addFieldToFilter('created_at', array('from' => $startdate));
        $invoicecollection->addFieldToFilter('created_at', array('to' => $enddate));
        
        $creditmemosData = array();
        
        foreach($invoicecollection->getItems() as $credmemo) {
            $order = $credmemo->getOrder();
            $pCreditmemo = Mage::helper('cloudinvoicegenerator/parser')->parseCreditmemo($credmemo);
            $creditmemoData['order_id'] = $order->getEntityId();
            $creditmemoData = array();
            $creditmemoData['entity_id'] = $credmemo->getEntityId();
            $creditmemoData['creditmemoobj'] = $pCreditmemo;

            $address = $this->_getAddresses($credmemo->getBillingAddressId());
            $creditmemoData['address'] = Mage::helper('cloudinvoicegenerator/parser')->parseAddress($address);
            $items = $this->_getItems($credmemo->getEntityId());
            $creditmemoData['creditmemo_lines'] = Mage::helper('cloudinvoicegenerator/parser')->parseCreditmemolines($items);
            $order = $this->_getOrder($credmemo->getOrderId());
            $creditmemoData['order_number'] = $order->getIncrementalId();

            $creditmemosData[] = $creditmemoData;
        }
        return $creditmemosData;       
    }

    public function _multiUpdate() {
    }

    public function _multiDelete() {
    }

    private function _getItems($creditmemoid) {
        $items = array();

        $collection = Mage::getResourceModel('sales/order_creditmemo_item_collection');
        $collection->addFieldToFilter('parent_id', array('eq' => $creditmemoid));

        foreach ($collection->getItems() as $item) {
            $items[] = $item;
        }
        return $items;
    }

    private function _getAddresses($billing_address_id) {
        $address = Mage::getModel('sales/order_address')->load($billing_address_id);

        return $address;
    }

    private function _getOrder($orderid) {
        $order = Mage::getModel('sales/order')->load($orderid);

        return $order;
    }
}
