<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Model_Catalog_Layer_Filter_OnSale extends Mage_Catalog_Model_Layer_Filter_Abstract
{

    const FILTER_ON_SALE = 1;

    protected $attributeIds;

    public function __construct()
    {
        parent::__construct();
        $this->_requestVar = 'onSale';
    }

    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $filter = (int) $request->getParam($this->getRequestVar());
        if (!$filter || Mage::registry('am_on_sale_filter')) {
            return $this;
        }

        $collection = $this->getLayer()->getProductCollection();
        $select = $collection->getSelect();

        $this->checkAddStatusToSelect($select);

        $state = $this->_createItem(Mage::helper('amshopby')->__('On Sale'), $filter)
            ->setVar($this->_requestVar);

        $this->getLayer()->getState()->addFilter($state);

        Mage::register('am_on_sale_filter', true);

        return $this;
    }

    public function getName()
    {
        return Mage::getStoreConfig('amshopby/on_sale_filter/label') ?: Mage::helper('amshopby')->__('On Sale Filter');
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
        $key = 'ONSALE';
        $data = $cache->getFilterItems($key);

        if (is_null($data)) {
            $data = array();

            $onSale = $this->_getCount();

            $currentValue = Mage::app()->getRequest()->getQuery($this->getRequestVar());

            $data[] = array(
                'label' => Mage::helper('amshopby')->__('On Sale'),
                'value' => ($currentValue == self::FILTER_ON_SALE) ? null : self:: FILTER_ON_SALE,
                'count' => $onSale,
                'option_id' => self:: FILTER_ON_SALE,
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
        if (strpos($select, 'specialPriceTo') !== false) {
            return;
        }

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');

        $attributeIds = $this->getAttributeIds();

        $select->joinLeft(
            array('specialPrice' => $resource->getTableName('catalog_product_entity_decimal')),
            'specialPrice.`entity_id`=`e`.entity_id AND 
            `specialPrice`.`attribute_id` = ' . $attributeIds['special_price'],
            null
        );

        $select->joinLeft(
            array('specialPriceFrom' => $resource->getTableName('catalog_product_entity_datetime')),
            'specialPriceFrom.`entity_id`=`e`.entity_id AND 
            `specialPriceFrom`.`attribute_id` = ' . $attributeIds['special_from_date'],
            null
        );

        $select->joinLeft(
            array('specialPriceTo' => $resource->getTableName('catalog_product_entity_datetime')),
            'specialPriceTo.`entity_id`=`e`.entity_id AND 
            `specialPriceTo`.`attribute_id` = ' . $attributeIds['special_to_date'],
            null
        );

        $select->joinLeft(
            array('catalogRule' => $resource->getTableName('catalogrule_product_price')),
            'catalogRule.`product_id`=`e`.entity_id',
            null
        );

        $select->where(
            '(specialPrice.value IS NOT NULL AND
            (specialPriceFrom.value < NOW() OR
            specialPriceFrom.value IS NULL) AND 
            (specialPriceTo.value > NOW() OR 
            specialPriceTo.value IS NULL)) OR 
            (`catalogRule`.website_id=' .Mage::app()->getStore()->getWebsiteId(). ' AND
            `catalogRule`.customer_group_id =' .Mage::getSingleton('customer/session')->getCustomerGroupId().')'
        );
        $select->group('e.entity_id');
    }

    protected function getAttributeIds()
    {
        if (is_null($this->attributeIds)) {
            $codes = array('special_from_date', 'special_to_date', 'special_price');
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
