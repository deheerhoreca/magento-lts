<?php
/**
 * FAQ accordion for Magento

 */

/**
 * FAQ accordion for Magento
 *
 * Website: www.hiremagento.com 
 * Email: hiremagento@gmail.com
 */
class HM_Faq_Model_Mysql4_Faq_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected $_previewFlag;

    /**
     * Constructor
     *
     */
    protected function _construct()
    {
        $this->_init('hm_faq/faq')
            ->setOrder('question', 'ASC');
    }
    
    /**
     * Creates an options array for grid filter functionality
     *
     * @return array Options array
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('faq_id', 'question');
    }

    public function addIsActiveFilter()
    {
        $this->addFilter('is_active', 1);
        return $this;
    }
    
    /**
     * Add Filter by category
     * 
     * @param int|HM_Faq_Model_Category $category Category to be filtered
     * @return HM_Faq_Model_Mysql4_Category_Collection
     */
    public function addCategoryFilter($category)
    {
        if ($category instanceof HM_Faq_Model_Category) {
            $category = array($category->getId());
        }
        
        $this->getSelect()->join(
            array('category_table' => $this->getTable('hm_faq/category_item')),
            'main_table.faq_id = category_table.faq_id',
            array ()
        )->where('category_table.category_id in (?)', array (
            0, 
            $category
        ))->group('main_table.faq_id');
        
        return $this;
    }
    
    /**
     * Add Filter by store
     *
     * @param int|Mage_Core_Model_Store $store Store to be filtered
     * @return HM_Faq_Model_Mysql4_Faq_Collection Self
     */
    public function addStoreFilter($store)
    {
        if ($store instanceof Mage_Core_Model_Store) {
            $store = array (
                 $store->getId()
            );
        }
        
        $this->getSelect()->join(
            array('store_table' => $this->getTable('hm_faq/faq_store')),
            'main_table.faq_id = store_table.faq_id',
            array ()
        )->where('store_table.store_id in (?)', array (
            0, 
            $store
        ))->group('main_table.faq_id');
        
        return $this;
    }

    
    /**
     * After load processing - adds store information to the datasets
     *
     */
    protected function _afterLoad()
    {
        if ($this->_previewFlag) {
            $items = $this->getColumnValues('faq_id');
            if (count($items)) {
                $select = $this->getConnection()->select()->from(
                    $this->getTable('hm_faq/faq_store')
                )->where(
                    $this->getTable('hm_faq/faq_store') . '.faq_id IN (?)',
                    $items
                );
                if ($result = $this->getConnection()->fetchPairs($select)) {
                    foreach ($this as $item) {
                        if (!isset($result[$item->getData('faq_id')])) {
                            continue;
                        }
                        if ($result[$item->getData('faq_id')] == 0) {
                            $stores = Mage::app()->getStores(false, true);
                            $storeId = current($stores)->getId();
                            $storeCode = key($stores);
                        }
                        else {
                            $storeId = $result[$item->getData('faq_id')];
                            $storeCode = Mage::app()->getStore($storeId)->getCode();
                        }
                        $item->setData('_first_store_id', $storeId);
                        $item->setData('store_code', $storeCode);
                    }
                }
            }
        }
        
        parent::_afterLoad();
    }
}
