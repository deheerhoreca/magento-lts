<?php

class Cloud_Invoicegenerator_Model_Api2_Shoptax_Rest_Admin_V1 extends Mage_Api2_Model_Resource {

    public function _retrieve() {
    }

    public function _create() {
    }

    public function _update() {
    }

    public function _delete() {
    }

    public function _retrieveCollection() {

        $tax_class = Mage::getModel('tax/calculation_rate')->getCollection();

        $taxclasses = array();
        foreach ($tax_class->getItems() as $magtaxclass) {
            $t_class['id'] = $magtaxclass->getTax_calculation_rate_id();
            $t_class['name'] = $magtaxclass->getCode();
            $t_class['country'] = $magtaxclass->getTax_country_id();
            $t_class['percentage'] = $magtaxclass->getRate();
            $taxclasses[] = $t_class;
        }

        return $taxclasses;
    }

    public function _multiUpdate() {
    }

    public function _multiDelete() {
    }

}
