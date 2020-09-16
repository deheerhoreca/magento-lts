    <?php

    class Magiecommerce_Faqs_Model_Mysql4_Items_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
    {
        public function _construct()
        {
            //parent::__construct();
            $this->_init('faqs/items');
        }
public function addStoreFilter($store){

    if ($store instanceof Mage_Core_Model_Store) {
        $store = array($store->getId());
    }

    if (!is_array($store)) {
        $store = array($store);
    }

    $this->addFilter('store_id', array('in' => $store));

    return $this;
}	
    }
