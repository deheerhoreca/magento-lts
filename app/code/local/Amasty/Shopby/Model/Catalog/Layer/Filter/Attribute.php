<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */

/**
 * Class Amasty_Shopby_Model_Catalog_Layer_Filter_Attribute
 * @method Mage_Catalog_Model_Resource_Eav_Attribute getAttributeModel()
 */
class Amasty_Shopby_Model_Catalog_Layer_Filter_Attribute extends Amasty_Shopby_Model_Catalog_Layer_Filter_Attribute_Pure
{
    const DEPENDENT_OPTIONS_REGISTRY = 'amshopby_filter_dependent_options_';

    /** @var  Amasty_Shopby_Model_Filter */
    private $filterSettings = null;

    /** @var array|null */
    private $mappedData = null;

    /** @var array|null */
    private $mappedOptions = null;

    /**
     * @return bool
     */
    public function getIsMapped()
    {
        $data = $this->getMappedData();
        return isset($data[$this->getAttributeModel()->getAttributeId()]);
    }

    /**
     * @return bool
     */
    public function getIsShowChildFilter()
    {
        if (!$this->getIsMapped()) {
            return false;
        }
        $data = $this->getMappedData();
        return $data[$this->getAttributeModel()->getAttributeId()]['show_child_filter'];
    }

    public function getMappedData()
    {
        if ($this->mappedData === null) {
            $this->mappedData = Mage::helper('amshopby/attributes')->getMappedAttributesHash();
        }
        return $this->mappedData;
    }

    /**
     * @return Varien_Db_Select
     */
    private function prepareCountSelect()
    {
        $select = $this->_getBaseCollectionSql();
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);
        $select->reset(Zend_Db_Select::GROUP);

        return $select;
    }

    /**
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @param bool $fetchMapped
     * @return array
     */
    private function getCount($attribute, $fetchMapped = false)
    {
        $select = $this->prepareCountSelect();
        $connection = $this->_getResource()->getReadConnection();
        $tableAlias = $this->_getAttributeTableAlias();
        $conditions = array(
            "{$tableAlias}.entity_id = e.entity_id",
            $connection->quoteInto("{$tableAlias}.attribute_id = ?", $attribute->getAttributeId()),
            $connection->quoteInto("{$tableAlias}.store_id = ?", $this->getStoreId()),
        );

        if ($fetchMapped) {
            $linkAlias = 'amlink_count';
            $select
                ->join(
                    array($tableAlias => $this->_getResource()->getMainTable()),
                    join(' AND ', $conditions),
                    array("$linkAlias.parent_id", 'count' => new Zend_Db_Expr("COUNT(DISTINCT {$tableAlias}.entity_id)")))
                ->join(
                    array($linkAlias => $this->_getResource()->getTable('amshopby/value_link')),
                    "$linkAlias.option_id = $tableAlias.value",
                    array("$linkAlias.parent_id"))
                ->group("$linkAlias.parent_id");
        } else {
            $select
                ->join(
                    array($tableAlias => $this->_getResource()->getMainTable()),
                    join(' AND ', $conditions),
                    array('value', 'count' => "COUNT(DISTINCT {$tableAlias}.entity_id)"))
                ->group("{$tableAlias}.value");
        }

        $optionsCount = $connection->fetchPairs($select);
        return $optionsCount;
    }

    /**
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @return array
     */
    private function getMappedCount($attribute)
    {
        return $this->getCount($attribute, true);
    }

    protected function _getAttributeTableAlias()
    {
        $alias = $this->getAttributeModel()->getAttributeCode() . '_idx';
        return $alias;
    }

    private function applyFilterToCollection($value, $notUsingFieldForCompatibilityWithEnterprise = null)
    {
        $value = $this->processMappedValue($value);
        $attribute  = $this->getAttributeModel();
        $collection = $this->getLayer()->getProductCollection();
        if (Mage::helper('amshopby')->useSolr()) {
            $fieldName = Mage::getResourceSingleton('enterprise_search/engine')
                ->getSearchEngineFieldName($attribute, 'nav');
            $prefix = '{!tag=' . $attribute->getAttributeCode() . '}';
            $collection->addFqFilter(array($prefix . $fieldName => $value));
        } else {
            $alias      = $this->_getAttributeTableAlias();
            $connection = $this->_getResource()->getReadConnection();

            if (!$this->getSingleChoice() && $this->getUseAndLogic()) {
                foreach ($value as $i => $attrValue) {
                    $alias = $alias . $i;
                    $conditions = array(
                        "{$alias}.entity_id = e.entity_id",
                        $connection->quoteInto("{$alias}.attribute_id = ?", $attribute->getAttributeId()),
                        $connection->quoteInto("{$alias}.store_id = ?",     $collection->getStoreId()),
                        $connection->quoteInto("{$alias}.value IN (?)",      $attrValue)
                    );

                    $collection->getSelect()->join(
                        array($alias => $this->_getResource()->getMainTable()),
                        join(' AND ', $conditions),
                        array()
                    );
                }
            } else {
                $conditions = array(
                    "{$alias}.entity_id = e.entity_id",
                    $connection->quoteInto("{$alias}.attribute_id = ?", $attribute->getAttributeId()),
                    $connection->quoteInto("{$alias}.store_id = ?",     $collection->getStoreId()),
                    $connection->quoteInto("{$alias}.value IN(?)",      $value)
                );

                $collection->getSelect()->join(
                    array($alias => $this->_getResource()->getMainTable()),
                    join(' AND ', $conditions),
                    array()
                );
            }
        }

        if (count($value) > 1 || $this->getIsMapped()) {
            $collection->getSelect()->distinct(true);
        }

    }

    /**
     * @param array|null $value
     * @return array
     */
    private function processMappedValue($value)
    {
        if (!$this->getIsMapped()) {
            return $value;
        }
        $prefix = Amasty_Shopby_Helper_Attributes::MAPPED_PREFIX;
        foreach ($value as $key => $id) {
            $hash = null;
            if (strpos($id, $prefix) === 0) {
                unset($value[$key]);
                $hash = $hash ? $hash : Mage::helper('amshopby/attributes')->getValueLinkHash();
                $filterOptionId = substr($id, strlen($prefix));
                if (isset($hash[$filterOptionId])) {
                    $value[] = $hash[$filterOptionId];
                }
            }
        }
        return $value;
    }

    /**
     * Proceed for apply by button.
     * @param array $currentVals
     * @return array
     */
    protected function excludeChildrenWithoutParents($currentVals)
    {
        if (!$this->getIsShowChildFilter() || !count($currentVals)) {
            return $currentVals;
        }
        $mapped = Mage::helper('amshopby/url')->getMappedOptionsWithParents();
        foreach ($currentVals as $k => $v) {
            if (isset($mapped[$v])) {
                if (!array_intersect($currentVals, $mapped[$v])) {
                    unset($currentVals[$k]);
                }
            }
        }
        return $currentVals;
    }

    /**
     * Don't apply child values.
     *
     * @param array $currentVals
     * @return array
     */
    protected function excludeValues($currentVals)
    {
        $children = array_keys(Mage::helper('amshopby/url')->getMappedOptionsWithParents());
        return array_diff($currentVals, $children);
    }

    /**
     * Apply attribute option filter to product collection
     *
     * @param   Zend_Controller_Request_Abstract $request
     * @param   Varien_Object $filterBlock
     * @return  Mage_Catalog_Model_Layer_Filter_Attribute
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $currentVals = Mage::helper('amshopby')->getRequestValues($this->_requestVar);
        $currentVals = $this->excludeChildrenWithoutParents($currentVals);
        $valsWithMapped = $currentVals;
        $currentVals = $this->excludeValues($currentVals);

        if ($currentVals) {
            $attributeCode = $this->getAttributeModel()->getAttributeCode();
            if ($this->isLocked($attributeCode)) {
                return $this;
            }

            $this->applyFilterToCollection($currentVals);

            // check if need to add state
            $route = Mage::app()->getRequest()->getRouteName();
            $branding = $route == 'amshopby'
                && count($currentVals) == 1
                && trim(Mage::getStoreConfig('amshopby/brands/attr')) == $attributeCode;
            if (!$branding) {
                $this->addState($valsWithMapped);
            }

            if (count($currentVals) > 1) {
                /** @var Amasty_Shopby_Helper_Layer_Cache $cache */
                $cache = Mage::helper('amshopby/layer_cache');
                $cache->limitLifetime(Amasty_Shopby_Helper_Layer_Cache::LIFETIME_SESSION);
            }
        }
        return $this;
    }

    /**
     * @param string $code
     * @return bool
     */
    protected function isLocked($code)
    {
        /** @var Amasty_Shopby_Helper_Attributes $attributeHelper */
        $attributeHelper = Mage::helper('amshopby/attributes');
        return !$attributeHelper->lockApplyFilter($code, 'attr');
    }

    protected function addState($currentVals)
    {
        //generate Status Block
        $attribute = $this->getAttributeModel();
        $text = '';
        $options = Mage::helper('amshopby/attributes')->getAttributeOptions($attribute->getAttributeCode());

        $children = array();

        foreach ($options as $option) {
            //skip values from child filter if this is a parent filter and vice versa
            if (!in_array($option['value'], $this->excludeValues($currentVals))) {
                continue;
            }
            $k = array_search($option['value'], $currentVals);
            if (false !== $k){

                $exclude = $currentVals;
                unset($exclude[$k]);
                $exclude = implode(',', $exclude);
                if (!$exclude)
                    $exclude = null;

                $query = array(
                    $this->getRequestVar() => $exclude,
                    Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
                );
                $url = Mage::helper('amshopby/url')->getFullUrl($query);

                $text .= $option['label'] . " ";

                $children[] = array(
                    'label' => $option['label'],
                    'url' => $url,
                );
            }
        }

        /** @var Amasty_Shopby_Model_Catalog_Layer_Filter_Item $state */
        $state = $this->_createItem($text, $currentVals)
            ->setVar($this->_requestVar);

        if (count($children) > 1) {
            $state->setData('children', $children);
        }

        $this->getLayer()->getState()->addFilter($state);
    }

    public function addFacetCondition()
    {
        $code = $this->getAttributeModel()->getAttributeCode();
        if (!$code) {
            return;
        }

        $key = 'amshopby_facet_added_' . $code;
        if (Mage::registry($key)) {
            return;
        }

        $engine = Mage::getResourceSingleton('enterprise_search/engine');
        $facetField = $engine->getSearchEngineFieldName($this->getAttributeModel(), 'nav');
        $prefix = '{!ex=' . $this->getAttributeModel()->getAttributeCode() . '}';
        $this->getLayer()->getProductCollection()->setFacetCondition($prefix . $facetField);

        Mage::register($key, true);
    }

    /**
     * @return string
     */
    protected function getCacheKey()
    {
        return 'A_' . $this->_requestVar;
    }

    /**
     * Get data array for building attribute filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        $attribute = $this->getAttributeModel();
        $this->_requestVar = $attribute->getAttributeCode();
        /** @var Amasty_Shopby_Helper_Data $helper */
        $helper = Mage::helper('amshopby');

        /** @var Amasty_Shopby_Helper_Layer_Cache $cache */
        $cache = Mage::helper('amshopby/layer_cache');
        $cache->setStateKey($this->getLayer()->getStateKey());
        $key = $this->getCacheKey();
        $data = $cache->getFilterItems($key);

        if (is_null($data)) {
            if ($helper->useSolr()) {
                $engine = Mage::getResourceSingleton('enterprise_search/engine');
                $fieldName = $engine->getSearchEngineFieldName($attribute, 'nav');
                $productCollection = $this->getLayer()->getProductCollection();
                $optionsCount = $productCollection->getFacetedData($fieldName);

                $options = Mage::helper('amshopby/attributes')->getAttributeOptions($attribute->getAttributeCode());
            } else {
                $options = Mage::helper('amshopby/attributes')->getAttributeOptions($attribute->getAttributeCode());
                $optionsCount = $this->getCount($attribute);
            }


            if ($this->getIsMapped()) {
                $children = array_keys(Mage::helper('amshopby/url')->getMappedOptionsWithParents());
                $mappedOptions = $this->getMappedOptions($attribute);
                foreach ($options as $key => $option) {
                    if (!$option['value']) {
                        //unset empty value
                        unset($options[$key]);
                    } elseif (in_array(intval($option['value']), $children)) {
                        //unset non-selected mapped option's child
                        unset($options[$key]);
                    } elseif(isset($mappedOptions[$option['value']])) {
                        //merge mapped data
                        $options[$key] = $mappedOptions[$option['value']];
                    }
                }
            }

            $data = array();
            $currentVals = $helper->getRequestValues($this->_requestVar);

            foreach ($options as $option) {
                if (is_array($option['value'])) {
                    continue;
                }
                if (!strlen($option['value'])) {
                    continue;
                }

                $ind = array_search($option['value'], $currentVals);
                $itemValue = $currentVals;
                if (false === $ind){
                    if ($this->getSingleChoice()) {
                        $itemValue = array($option['value']);
                    } else {
                        $itemValue[] = $option['value'];
                    }
                }
                else {
                    $itemValue[$ind]  = null;
                    unset($itemValue[$ind]);
                }

                $itemValue = implode(',', $itemValue);
                if (isset($option['count'])) {
                    $cnt = $option['count'];
                } else {
                    $cnt = isset($optionsCount[$option['value']]) ? $optionsCount[$option['value']] : 0;
                }
                if ($cnt || $this->_getIsFilterableAttribute($attribute) != self::OPTIONS_ONLY_WITH_RESULTS) {
                    $isFeatured = isset($option['is_featured']) ? $option['is_featured']
                        : (int) Mage::helper('amshopby/attributes')->getIsOptionFeatured($option['value']);
                    $newData =array(
                        'label'     => $option['label'],
                        'value'     => $itemValue,
                        'count'     => $cnt,
                        'option_id' => $option['value'],
                        'is_featured'  => $isFeatured,
                    );

                    $dependentOptions = $this->getDependentOptions();
                    $mappedParents = isset($dependentOptions[$option['value']]) ? $dependentOptions[$option['value']] : array();
                    $newData['is_dependent'] = (bool) $mappedParents;
                    $newData['mapped_parents'] = $mappedParents;
                    $data[] = $newData;
                }
            }

            $cache->setFilterItems($key, $data);
        }

        return $data;
    }


    /**
     * @param  $attribute
     * @return array
     */
    private function getMappedOptions($attribute)
    {
        if (!$this->getIsMapped()) {
            return array();
        }
        if ($this->mappedOptions === null) {
            $dependentOptions = array();
            $filterId = Mage::getModel('amshopby/filter')->getCollection()
                ->addFieldToFilter('attribute_id', $this->getAttributeModel()->getAttributeId())
                ->getAllIds();
            $collection = Mage::getModel('amshopby/value')//@todo implement hash in helper
            ->getParentCollection()
                ->addChildren()
                ->addFieldToFilter('filter_id', array('in' => $filterId));
            $count = $this->getMappedCount($attribute);
            $options = array();
            foreach ($collection as $key => $filterOption) {
                $newOption = array();
                /** @var Amasty_Shopby_Model_Value $filterOption */
                $newOption['value'] = $filterOption->getOptionId();
                $newOption['label'] = $filterOption->getCurrentTitle();
                $newOption['is_featured'] = $filterOption->getIsFeatured();
                $childOptionIds = explode(',', $filterOption->getChildOptionIds());
                $newOption['count'] = isset($count[$key]) ? $count[$key] : 0;
                $options[$filterOption->getOptionId()] = $newOption;
                foreach ($childOptionIds as $childId) {
                    if (isset($dependentOptions[$childId])) {
                        $dependentOptions[$childId][] = $newOption['value'];
                    } else {
                        $dependentOptions[$childId] = array($newOption['value']);
                    }
                }
            }

            Mage::unregister(self::DEPENDENT_OPTIONS_REGISTRY . $this->_requestVar);
            Mage::register(self::DEPENDENT_OPTIONS_REGISTRY . $this->_requestVar, $dependentOptions);
            $this->mappedOptions = $options;
        }

        return $this->mappedOptions;
    }

    /**
     * Registry dependent options, so child model can use it afterwards.
     *
     * @return array
     */
    private function getDependentOptions()
    {
        if (Mage::registry(self::DEPENDENT_OPTIONS_REGISTRY . $this->_requestVar) === null) {
            $this->getMappedOptions($this->getAttributeModel());
        }
        return (array)Mage::registry(self::DEPENDENT_OPTIONS_REGISTRY . $this->_requestVar);
    }

    protected function _initItems()
    {
        $data = $this->_getItemsData();
        $items = array();
        foreach ($data as $itemData) {
            if (!$this->isProcessMapped($itemData)) {
                continue;
            }
            $item = $this->_createItem(
                $itemData['label'],
                $itemData['value'],
                $itemData['count']
            );
            $item->setOptionId($itemData['option_id']);
            $item->setIsFeatured($itemData['is_featured']);
            $item->setIsDependent($itemData['is_dependent']);
            $item->setMappedParents($itemData['mapped_parents']);
            $items[] = clone $item;
        }
        $this->_items = $items;
        return $this;
    }

    protected function isProcessMapped($itemData)
    {
        return !$itemData['is_dependent'];
    }

    //start new functions

    // will work for both 1.3 and 1.4
    protected function _getBaseCollectionSql()
    {
        $alias = $this->_getAttributeTableAlias();

        if (method_exists($this->getLayer()->getProductCollection(), '_applySearchFilters')) {
            //trigger applying search filter in catalogsearch fulltext collection on magento 1.9.x
            $this->getLayer()->getProductCollection()->getSize();
        }
        $baseSelect = clone parent::_getBaseCollectionSql();

        $oldWhere = $baseSelect->getPart(Varien_Db_Select::WHERE);
        $newWhere = array();

        foreach ($oldWhere as $cond){
            if (strpos($cond, $alias) === false){
                $newWhere[] = $cond;
            }
        }

        if ($newWhere && substr($newWhere[0], 0, 3) == 'AND'){
            $newWhere[0] = substr($newWhere[0], 3);
        }

        $baseSelect->setPart(Varien_Db_Select::WHERE, $newWhere);

        $oldFrom = $baseSelect->getPart(Varien_Db_Select::FROM);
        $newFrom = array();

        foreach ($oldFrom as $name=>$val){
            if ($name != $alias){
                $newFrom[$name] = $val;
            }
        }
        $baseSelect->setPart(Varien_Db_Select::FROM, $newFrom);

        return $baseSelect;
    }

    protected function getUseAndLogic()
    {
        $settings = $this->getFilterSettings();
        return $settings ? $settings->getUseAndLogic() : null;
    }

    protected function getSingleChoice()
    {

        $settings = $this->getFilterSettings();
        if (!$settings) {
            return null;
        }
        return $settings->getSingleChoice() ;
    }

    private function getFilterSettings()
    {
        if (is_null($this->filterSettings)) {
            /** @var Amasty_Shopby_Helper_Data $helper */
            $helper = Mage::helper('amshopby');
            $attribute  = $this->getAttributeModel();
            $settings = $helper->getAttributesSettings();
            if (isset($settings[$attribute->getAttributeId()])) {
                $this->filterSettings = $settings[$attribute->getAttributeId()];
            } else {
                $this->filterSettings = null;
            }
        }

        return $this->filterSettings;
    }
}
