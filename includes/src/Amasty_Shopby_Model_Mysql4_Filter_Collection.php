<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
class Amasty_Shopby_Model_Mysql4_Filter_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('amshopby/filter');
    }
    
    public function addTitles()
    {
        if (empty($this->_map))
            $this->_map = array();
            
        $this->_map['fields']['attribute_id'] = 'main_table.attribute_id';
        
        $this->getSelect()
             ->joinInner(array('a'=> $this->getTable('eav/attribute')), 'main_table.attribute_id = a.attribute_id', array('a.frontend_label','a.attribute_code'))
             ->joinInner(array('ca'=> $this->getTable('catalog/eav_attribute')), 'main_table.attribute_id = ca.attribute_id', array('ca.position'));

        return $this;
    }
    
    public function addFrontendInput($attributeCode)
    {
        $this->getSelect()
             ->joinInner(array('a'=> $this->getTable('eav/attribute')), 'main_table.attribute_id = a.attribute_id', array('a.frontend_input'))
             ->where('a.attribute_code = ?', $attributeCode);
        
        return $this;
    }

    /**
     * Retrieve all mapped attribute ids, show_child_filter and child filter name values
     *
     * @return array
     */
    public function getMappedAttributes()
    {
        $select = clone $this->getSelect();
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);
        $select->reset(Zend_Db_Select::COLUMNS);

        $select->columns(array('attribute_id', 'show_child_filter', 'child_filter_name'), 'main_table');
        $fetchResult =  $this->getConnection()->fetchAll($select);
        $result =array();
        foreach ($fetchResult as $row) {
            $key = $row['attribute_id'];
            unset($row['attribute_id']);
            $result[$key] = $row;
        }

        return $result;
    }
}
