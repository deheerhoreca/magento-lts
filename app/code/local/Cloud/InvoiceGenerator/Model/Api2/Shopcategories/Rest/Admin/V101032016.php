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
			if ($category->getIsActive()) { 
				$cat_id = $category->getId();
				$name = $category->getName();
				
				$pm = array();
				$pm['id'] = $cat_id;
				$pm['name'] = $name;
				$pm['shopcategoryobj'] = $this->_getItems($cat_id);
				$magcats[] = $pm;
			}
        }
//error_log(print_r($magcats), true);
        return $magcats;
    }
	
	protected function _getItems($ruleId)
	{		
		$cats = Mage::getModel('catalog/category')->load($ruleId);
		$subcats  = Mage::getModel('catalog/category')->load($ruleId)->getChildren();

		$cur_category = array();

		if($subcats != '') 
		{
			foreach(explode(',',$subcats) as $subCatid)
			{
				$_category = Mage::getModel('catalog/category')->load($subCatid);

				$node['id'] = $subCatid;
				$node['name'] = $_category->getName();
				
				$cur_category[] = $node;
			}
		}

		return $cur_category;       
	}

    public function _multiUpdate() {
    }

    public function _multiDelete() {
    }
}
