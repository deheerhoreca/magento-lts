<?php

class Cloud_Invoicegenerator_Model_Api2_Shoppaymentmethod_Rest_Admin_V1 extends Mage_Api2_Model_Resource {

    public function _retrieve() {
    }

    public function _create() {
    }

    public function _update() {
    }

    public function _delete() {
    }

    public function _retrieveCollection() {
        $pmcollection = Mage::getModel('core/config_data')->getCollection();
        $pmcollection->addFieldToFilter('path', array('like' => 'payment/%/active'));
        $pmcollection->addFieldToFilter('value', array('equal' => 1));
  
        $magpms = array();

        foreach ($pmcollection->getItems() as $pm) {
            $path = $pm->getPath();
            $pmcode = $this->getPaymentcode($path);
            $pmtitle = $this->getTitle($pmcode);
           
            $pm = array();
            $pm['id'] = $pmcode;
            $pm['name'] = $pmtitle;
            $magpms[] = $pm;
        }

        return $magpms;
    }

    public function _multiUpdate() {
    }

    public function _multiDelete() {
    }

    private function getTitle($pm) {
        $pmtitle = '';

        $collection = Mage::getModel('core/config_data')->getCollection();
        $collection->addFieldToFilter('path', array('eq' => 'payment/' . $pm . '/title'));

        foreach ($collection->getItems() as $item) {
            $pmtitle = (string)$item->getValue();
        }
        return $pmtitle;
    }

    private function getPaymentcode($path) {
        $pmc = '';
        $pathParts = explode('/', $path);
        foreach ($pathParts as $pp) {
            if ($pp != 'active' && $pp != 'payment') {
                $pmc = $pp;
            }
        }
        return $pmc;
    }

}
