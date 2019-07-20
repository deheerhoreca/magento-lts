<?php

class Cloud_Invoicegenerator_Model_Api2_Shopproducts_Rest_Admin_V1 extends Mage_Api2_Model_Resource {

    public function _retrieve() {
    }

    public function _create() {
    }

    public function _update() {
    }

    public function _delete() {
    }

    public function _retrieveCollection() {
		//$categoryId = 10;
		$categoryId = $this->getRequest()->getParam('category_id');
		$products = Mage::getModel('catalog/category')->load($categoryId)
		 ->getProductCollection()
		 ->addAttributeToSelect('*')
		 ->addAttributeToFilter('status', 1);
  
        $magcats = array();

        foreach ($products as $product) {			
			$product_id = $product['entity_id'];
			$name = $product['name'];				
			$pm = array();
			$pm['id'] = $product_id;
			$pm['name'] = $name;
			$magcats[] = $pm;			
        }

        return $magcats;
    }

    public function _multiUpdate() {
    }

    public function _multiDelete() {
    }
}
