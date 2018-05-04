<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Model_Catalog_Layer_Filter_New extends Mage_Catalog_Model_Layer_Filter_Abstract
{

    const FILTER_NEW = 1;

    protected $attributeIds;

    public function __construct()
    {
        parent::__construct();
        $this->_requestVar = 'new';
    }

    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $filter = (int) $request->getParam($this->getRequestVar());
        if (!$filter || Mage::registry('am_new_filter')) {
            return $this;
        }

        $collection = $this->getLayer()->getProductCollection();
        $select = $collection->getSelect();

        $this->checkAddStatusToSelect($select);

        $state = $this->_createItem(Mage::helper('amshopby')->__('New'), $filter)
            ->setVar($this->_requestVar);

        $this->getLayer()->getState()->addFilter($state);

        Mage::register('am_new_filter', true);

        return $this;
    }

    public function getName()
    {
        return Mage::getStoreConfig('amshopby/new_filter/label') ?: Mage::helper('amshopby')->__('New Filter');
    }

    /**
     * Get data array for building category filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        /** @var Amasty_Shopby_Helper_Layer_Cache $cache */
        $cache = Mage::helper('amshopby/layer_cache');
        $cache->setStateKey($this->getLayer()->getStateKey());
        $key = 'NEW';
        $data = $cache->getFilterItems($key);

        if (is_null($data)) {
            $data = array();

            $new = $this->_getCount();

            $currentValue = Mage::app()->getRequest()->getQuery($this->getRequestVar());

            $data[] = array(
                'label' => Mage::helper('amshopby')->__('New'),
                'value' => ($currentValue == self::FILTER_NEW) ? null : self:: FILTER_NEW,
                'count' => $new,
                'option_id' => self:: FILTER_NEW,
            );
            $cache->setFilterItems($key, $data);
        }

        return $data;
    }

    protected function _getCount()
    {
        if (!$this->getAttributeIds()) {
            return 0;
        }

        $collection = $this->getLayer()->getProductCollection();
        $connection = $collection->getConnection();

        //Getting count should not affect product collection
        $select = clone $collection->getSelect();

        $this->checkAddStatusToSelect($select);

        $select->reset(Zend_Db_Select::COLUMNS);
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);
        $select->reset(Zend_Db_Select::GROUP);

        $select->columns(array('COUNT(DISTINCT e.entity_id)'));

        return $connection->fetchOne($select);
    }

    protected function checkAddStatusToSelect(Varien_Db_Select $select)
    {
        if (strpos($select, 'newsToDate') === false) {
            $this->setNewsFromToDateInSelect($select);
        }
    }

    protected function setNewsFromToDateInSelect(Varien_Db_Select $select)
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');

        $attributeIds = $this->getAttributeIds();
        $select->joinLeft(
            array('newsFromDate' => $resource->getTableName('catalog_product_entity_datetime')),
            'newsFromDate.`entity_id`=`e`.entity_id AND 
        `newsFromDate`.`attribute_id` = ' . $attributeIds['news_from_date'],
            null
        );

        $select->joinLeft(
            array('newsToDate' => $resource->getTableName('catalog_product_entity_datetime')),
            'newsToDate.`entity_id`=`e`.entity_id AND 
        `newsToDate`.`attribute_id` = ' . $attributeIds['news_to_date'],
            null
        );

        $select->where('newsFromDate.value < NOW()');
        $select->where('newsToDate.value > NOW() OR newsToDate.value IS NULL');
        $select->group('e.entity_id');
    }

    protected function getAttributeIds()
    {
        if (is_null($this->attributeIds)) {
            $codes = array('news_from_date', 'news_to_date');
            /** @var Mage_Core_Model_Resource $resource */
            $resource = Mage::getSingleton('core/resource');
            $connection = $this->getLayer()->getProductCollection()->getConnection();
            $select = $connection->select()
                ->from($resource->getTableName('eav_attribute'))
                ->reset(Zend_Db_Select::COLUMNS)
                ->where('attribute_code IN ("' . join('","', $codes) . '")')
                ->columns(array('attribute_code','attribute_id'));
            $ids = $connection->fetchPairs($select);
            $this->attributeIds = (count($ids) == count($codes)) ? $ids : array();
        }
        return $this->attributeIds;
    }

    protected function _initItems()
    {
        $data  = $this->_getItemsData();
        $items = array();
        foreach ($data as $itemData) {
            $item = $this->_createItem(
                $itemData['label'],
                $itemData['value'],
                $itemData['count']
            );
            $item->setOptionId($itemData['option_id']);
            $items[] = $item;
        }
        $this->_items = $items;
        return $this;
    }

}
