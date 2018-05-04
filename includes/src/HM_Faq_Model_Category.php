<?php
/**
 * FAQ accordion for Magento
 *

 * @copyright  Copyright (c) 2010 HM GmbH & Co. KG <magento@hm.de>
 */

/**
 * Category Model for FAQ Items
 *
 * Website: www.hiremagento.com 
 * Email: hiremagento@gmail.com
 */
class HM_Faq_Model_Category extends Mage_Core_Model_Abstract
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('hm_faq/category');
    }
    
    public function getName()
    {
        return $this->getCategoryName();
    }
    
    public function getItemCollection()
    {
        $collection = $this->getData('item_collection');
        if (is_null($collection)) {
            $collection = Mage::getSingleton('hm_faq/faq')->getCollection()
                ->addCategoryFilter($this);
            $this->setData('item_collection', $collection);
        }
        return $collection;
    }
}
