<?php

class Cloud_Invoicegenerator_Model_Api2_Shopcategories_Rest_Admin_V1 extends Mage_Api2_Model_Resource {

    public function _retrieve() {
    }

    public function _create() {
    }

    public function _update() {
    }

    public function _delete() {
    }

    public function _retrieveCollection() {
        $catcollection = Mage::getModel('catalog/category')->getCollection();
        $catcollection->addAttributeToSelect('id');
        $catcollection->addAttributeToSelect('name');
        $catcollection->addAttributeToSelect('is_active');
  
        $magcats = array();

        foreach ($catcollection as $category) {
            if ($category->getIsActive() and $category->getName()!= 'Default Category' ) { 
                $cat_id = $category->getId();
                $name = $category->getName();
                $parent_id = $category->getParentCategory()->getName() != 'Default Category' ? $category->getParentCategory()->getName() : '';
                
                $pm = array();
                $pm['id'] = $cat_id;
                $pm['name'] = $name;
                $pm['parent_id'] = $parent_id;
                $magcats[] = $pm;
            }
        }

        return $magcats;
    }

    public function _multiUpdate() {
    }

    public function _multiDelete() {
    }
}
