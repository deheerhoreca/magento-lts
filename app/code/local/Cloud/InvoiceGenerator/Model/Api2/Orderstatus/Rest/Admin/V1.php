<?php

class Cloud_Invoicegenerator_Model_Api2_Orderstatus_Rest_Admin_V1 extends Mage_Api2_Model_Resource {

    public function _retrieve() {
    }

    public function _create() {
    }

    public function _update() {
    }

    public function _delete() {
    }

    public function _retrieveCollection() {
        $statuscollection = Mage::getModel('sales/order_status')->getCollection();

        $statuses = array();

        foreach ($statuscollection->getItems() as $orderstatus) {
            $statusData = array();
            $statusData['status'] = $orderstatus->getStatus();
            $statusData['label'] = $orderstatus->getLabel();

            $statusesData[] = $statusData;
        }

        return $statusesData;
    }

    public function _multiUpdate() {
    }

    public function _multiDelete() {
    }

}
