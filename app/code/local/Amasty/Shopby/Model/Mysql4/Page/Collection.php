<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */

/**
 * @author Amasty
 */ 
class Amasty_Shopby_Model_Mysql4_Page_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('amshopby/page');
        $this->setOrder('num', 'desc');
    }

    /**
     * @param null $storeId
     * @return $this
     */
    public function addStoreFilter($storeId = null)
    {
        if ($storeId === null) {
            $storeId = Mage::app()->getStore()->getId();
        }
        $this->getSelect()->where('stores = "" OR stores REGEXP "(^|,)' . $storeId . '($|,)"');

        return $this;
    }

    /**
     * @param $categoryId
     */
    public function addCategoryFilter($categoryId)
    {
        if (isset($categoryId)) {
            if (is_object($categoryId)) {
                $categoryId = $categoryId->getId();
            }

            $categoryId = (int)$categoryId;
            $this->getSelect()->where('cats = "" OR cats REGEXP "(^|,)' . $categoryId . '($|,)"');
        }
    }
}
