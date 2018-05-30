<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Helper_Url extends Mage_Core_Helper_Abstract
{
    protected $_options    = null;
    protected $_attributes = null;
    protected $_allParamsAreValid = null;
    protected $optionChar;
    protected $specialChar;
    protected $urlSuffix;
    protected $urlSuffixLength;
    protected $optionsHash;
    protected $decimalAttributeCodeMap;

    /**
     * @var array
     */
    private $alternativeOptionsHash = array();

    /** @var  Amasty_Shopby_Model_Mysql4_Value_Link_Collection */
    private $mappedOptionsWithParents;

    public function __construct()
    {
        $this->optionChar = Mage::getStoreConfig('amshopby/seo/option_char');
        $this->specialChar = Mage::getStoreConfig('amshopby/seo/special_char');

        $suffix = Mage::getStoreConfig('catalog/seo/category_url_suffix');
        if ($suffix && '/' != $suffix && '.' != $suffix[0]){
            $suffix = '.' . $suffix;
        }
        $this->urlSuffix = $suffix;
        $this->urlSuffixLength = strlen($suffix);

        $this->optionsHash = Mage::helper('amshopby/attributes')->getAllFilterableOptionsAsHash();

        /** @var Amasty_Shopby_Helper_Attributes $attributeHelper */
        $attributeHelper = Mage::helper('amshopby/attributes');
        $this->decimalAttributeCodeMap = $attributeHelper->getDecimalAttributeCodeMap();
    }

    protected function _getCurrentUrlWithoutParams()
    {
        $url = Mage::helper('core/url')->getCurrentUrl();
        // remove query params if any
        $pos = max(0, strpos($url, '?'));
        if ($pos) {
            $url = substr($url, 0, $pos);
        }
        return $url;
    }

    public function getCanonicalUrl()
    {
        $key = Mage::getStoreConfig('amshopby/seo/key');
        $category = $this->_getCurrentCategory();
        $canonicalType = Mage::getStoreConfig('amshopby/seo/canonical' . (is_object($category) ? '_cat' : ''));

        switch ($canonicalType) {
            case Amasty_Shopby_Model_Source_Canonical::CANONICAL_KEY:
                return $category ? $category->getUrl() : (Mage::getBaseUrl() . $key);

            case Amasty_Shopby_Model_Source_Canonical::CANONICAL_CURRENT_URL:
                return $this->_getCurrentUrlWithoutParams();

            case Amasty_Shopby_Model_Source_Canonical::CANONICAL_FIRST_ATTRIBUTE_VALUE:
                return $this->_getFirstAttributeValueUrl();
        }

        return null;
    }

    protected function _getCurrentCategory()
    {
        /** @var Mage_Catalog_Model_Layer $layer */
        $layer = Mage::getSingleton('catalog/layer');
        $category = $layer->getCurrentCategory();
        $isDefault = $category->getId() == Mage::app()->getStore()->getRootCategoryId();

        return $isDefault ? null : $category;
    }

    protected function sortFilters($firstFilter, $secondFilter)
    {
        return strnatcmp($firstFilter->getVar(), $secondFilter->getVar());
    }

    protected function _getFirstAttributeValueUrl()
    {
        /** @var Amasty_Shopby_Helper_Attributes $attributeHelper */
        $attributeHelper = Mage::helper('amshopby/attributes');

        /** @var Amasty_Shopby_Model_Url_Builder $urlBuilder */
        $urlBuilder = Mage::getModel('amshopby/url_builder');
        $urlBuilder->reset();
        $urlBuilder->clearQuery();

        if ($this->isOnBrandPage()) {
            $value = $attributeHelper->getRequestedBrandOption()->getOptionId();
            $code = $attributeHelper->prepareAttributeCode(Mage::getStoreConfig('amshopby/brands/attr'));
            $urlBuilder->changeQuery(array($code => $value));
            return $urlBuilder->getUrl();
        }

        $filters = Mage::getSingleton('catalog/layer')->getState()->getFilters();
        if (!$filters) {
            return $urlBuilder->getUrl();
        }

        $sortByCode = Mage::getStoreConfig('amshopby/seo/sort_attributes_in_url')
            == Amasty_Shopby_Model_Source_Url_SortMode::MODE_CODE;

        if ($sortByCode) {
            usort($filters, array("Amasty_Shopby_Helper_Url", "sortFilters"));
        }

        /** @var Amasty_Shopby_Model_Catalog_Layer_Filter_Item $item */
        foreach ($filters as $item) {
            if (!$item->getValue()) {
                continue;
            }
            /** @var Amasty_Shopby_Model_Catalog_Layer_Filter_Attribute $filter */
            $filter = $item->getFilter();
            /** @var Mage_Eav_Model_Entity_Attribute $attribute */
            $attribute = $filter->getData('attribute_model');
            if ($attribute === null) {
                continue;
            }
            $code = $attributeHelper->prepareAttributeCode($attribute->getAttributeCode());
            if (array_key_exists($code, $this->optionsHash)) {
                $value = $this->_getFirstFilterOption($filter, $item->getValue());
                $foundOptionAlias = array_search($value, $this->optionsHash[$code]);

                if ($foundOptionAlias !== false) {
                    $urlBuilder->changeQuery(array(
                        $code => $value
                    ));
                    break;
                }
            }
        }
        $url = $urlBuilder->getUrl();

        return $url;
    }

    /**
     * Return the first Filter Option depends on improved-navigation settings
     *
     * @param Amasty_Shopby_Model_Catalog_Layer_Filter_Attribute $filter
     * @param $activeValues
     * @return string|null
     */
    protected function _getFirstFilterOption($filter, $activeValues)
    {
        if (!is_array($activeValues)) {
            $activeValues = explode(',', $activeValues);
        }
        $items = array();
        $amFilterModel = Mage::getModel('amshopby/filter')
            ->load($filter->getAttributeModel()->getAttributeId(), 'attribute_id');
        //getItems() method sets countValue & label, sorts by position
        foreach ($filter->getItems() as $initItem) {
            if (in_array($initItem->getOptionId(), $activeValues)) {
                $newItem['id'] = $initItem->getOptionId();
                $newItem['label'] = $initItem->getLabel();
                $newItem['countValue']  = $initItem->getCount();
                $newItem['is_featured']  = $initItem->getIsFeatured();
                $items[] = $newItem;
            }
        }
        if(!$items) {
            return null;
        }
        $functions = array(
            Amasty_Shopby_Model_Filter::SORT_BY_NAME => 'sortOptionsByName',
            Amasty_Shopby_Model_Filter::SORT_BY_QTY => 'sortOptionsByCounts'
        );
        if (isset($functions[$amFilterModel->getSortBy()])){
            usort($items, array(Mage::helper('amshopby/attributes'), $functions[$amFilterModel->getSortBy()]));
        }
        $featuredItems = array();
        $standartItems = array();
        foreach ($items as $k => $item){
            if($item['is_featured']) {
                $featuredItems[] = $items[$k];
            } else {
                $standartItems[] = $items[$k];
            }
        }
        if($amFilterModel->getSortFeaturedFirst() && count($featuredItems) > 0) {
            usort($featuredItems, array(Mage::helper('amshopby/attributes'), 'sortOptionsByName'));
            $items = array_merge($featuredItems, $standartItems);
        }
        return $items[0]['id'];
    }

    /**
     * @deprecated Now it is a facade to Amasty_Shopby_Model_Url
     */
    public function getFullUrl($query=array(), $clear=false, $cat = null)
    {
        /** @var Amasty_Shopby_Model_Url_Builder $builder */
        $builder = Mage::getModel('amshopby/url_builder');
        $builder->reset();

        if ($clear) {
            $builder->clearQuery();
            $moduleName = Mage::app()->getRequest()->getModuleName();
            if (Mage::app()->getRequest()->getParam('am_landing') || in_array($moduleName, array('sqli_singlesearchresult', 'catalogsearch' ,'categorysearch'))) {
                $builder->clearCategory();
            }
        }

        if ($cat === false) {
            $builder->category = Mage::getModel('catalog/category')->load(Mage::app()->getStore()->getRootCategoryId());
        } else if (is_object($cat)) {
            $query['cat'] = $cat->getId();
        }

        $builder->changeQuery($query);

        $url = $builder->getUrl();
        return $url;
    }

    public function getOptionUrl($attributeCode, $attributeValue)
    {
        /** @var Amasty_Shopby_Model_Url_Builder $urlBuilder */
        $urlBuilder = Mage::getModel('amshopby/url_builder');
        $urlBuilder->reset();
        $urlBuilder->clearCategory();
        $urlBuilder->clearModule();
        $urlBuilder->clearQuery();
        $urlBuilder->changeQuery(array(
            $attributeCode => $attributeValue,
        ));
        return $urlBuilder->getUrl();
    }

    /**
     * @param array $categoryIds
     * @param array|null $params
     * @return string[]
     */
    public function getCategoriesUrl($categoryIds, $params = null)
    {
        $categoriesUrl = array();
        /** @var Amasty_Shopby_Model_Url_Builder $urlBuilder */
        $urlBuilder = Mage::getModel('amshopby/url_builder');
        $urlBuilder->reset();
        $urlBuilder->clearModule();
        $urlBuilder->clearQuery();
        if (!empty($params)) {
            $urlBuilder->changeQuery($params);
        }

        foreach ($categoryIds as $categoryId) {
            $urlBuilder->clearCategory($categoryId);
            $categoriesUrl[$categoryId] = $urlBuilder->getUrl();
        }

        return $categoriesUrl;
    }

    public function saveParams($request)
    {
        if (!is_null($this->_allParamsAreValid)){
            return $this->_allParamsAreValid;
        }
        $this->_allParamsAreValid = true;

        if (!$this->optionsHash){
            return true;
        }

        $currentParams = Mage::registry('amshopby_current_params');
        if (!$currentParams){
            return true;
        }

        // brand-amd-canon/price-100,200 or  amd-canon/100,200
        $hideAttributeNames = Mage::getStoreConfig('amshopby/seo/hide_attributes');

        $requiredFilters = array();

        foreach ($currentParams as $params){
            $params   = explode($this->optionChar, $params);
            $firstOpt = $params[0];

            if ($hideAttributeNames && !$this->isDecimal($firstOpt)){
                $attrCode = $this->_getAttributeCodeByOptionKey($firstOpt, $this->optionsHash);
            }
            else {
                $attrCode = $firstOpt;
                array_shift($params); // remove first element
            }

            if ($attrCode && isset($this->optionsHash[$attrCode])){
                if ($this->isDecimal($attrCode)){

                    $v = $params[0];
                    if (count($params) > 1){
                        $v = $params[0] . $this->optionChar . $params[1];
                    }

                    if ($v === '' || is_null($v)){
                        $this->_allParamsAreValid = false;
                        return false;
                    }
                }
                else {
                    $ids = $this->_convertOptionKeysToIds($params, $this->optionsHash[$attrCode]);
                    $ids = $ids ? join(',', $ids) : $request->getParam($attrCode);  // fix for store changing

                    $v = is_array($ids) ? '' : $ids; // just in case
                    if (!$v){
                        $this->_allParamsAreValid = false;
                        return false;
                    }
                }

                /*
                  * Convert AttrCode back to contrast_ratio (magento way) from contrast-ratio
                  */
                $attrCode = $this->_convertAttributeToMagento($attrCode);
                $requiredFilters[] = $attrCode;
                $request->setQuery(array($attrCode => $v));
            }
            else { // we have undefined string
                $this->_allParamsAreValid = false;
                return false;
            }
        }

        if ($requiredFilters) {
            Mage::register('amshopby_required_seo_filters', $requiredFilters, true);
        }

        return true;

    }

    public function isOnBrandPage()
    {
        if (Mage::app()->getRequest()->getModuleName() != 'amshopby')
            return false;

        $cat = Mage::registry('current_category');
        $params = Mage::app()->getRequest()->getQuery();
        return $this->isBrandPage($cat, $params);
    }

    public function isBrandPage($cat, $params)
    {
        if (Mage::app()->getRequest()->getParam('am_landing')) {
            return false;
        }

        $attrCode = trim(Mage::getStoreConfig('amshopby/brands/attr'));
        if (!$attrCode) {
            return false;
        }

        if ($cat){
            $rootId = (int) Mage::app()->getStore()->getRootCategoryId();
            if ($cat->getId() != $rootId) {
                return false;
            }
        }

        if (empty($params[$attrCode])){
            return false;
        }

        return true;
    }

    public function isDecimal($attrCode)
    {
        $attrCode = $this->_convertAttributeToMagento($attrCode);
        return isset($this->decimalAttributeCodeMap[$attrCode]) ? $this->decimalAttributeCodeMap[$attrCode] : false;
    }

    /**
     * @param null|int $storeId
     * @return array
     */
    public function getAllFilterableOptionsAsHash($storeId = null)
    {
        if ($storeId) {
            if (!isset($this->alternativeOptionsHash[$storeId])) {
                $this->alternativeOptionsHash[$storeId] =
                    Mage::helper('amshopby/attributes')->getAllFilterableOptionsAsHash($storeId);
            }

            return $this->alternativeOptionsHash[$storeId];
        }

        return $this->optionsHash;
    }

    private function _convertIdToKeys($options, $ids)
    {
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        $existingIds = array_intersect($options, $ids);
        $keys = array_keys($existingIds);
        return join($this->optionChar, $keys);
    }

    public function _formatAttributePartMultilevel($attrCode, $ids)
    {
        if ($this->isDecimal($attrCode)){
            return $attrCode . $this->optionChar . $ids; // always show price and other decimal attributes
        }

        $part = $this->_convertIdToKeys($this->optionsHash[$attrCode], $ids);

        if (!$part){
            return '';
        }

        $hideAttributeNames = Mage::getStoreConfig('amshopby/seo/hide_attributes');
        $part =  $hideAttributeNames ? $part : ($attrCode . $this->optionChar . $part);

        return $part;
    }

    public function _formatAttributePartShort($attrCode, $ids)
    {
        if ($this->isDecimal($attrCode)){
            return $attrCode . $this->optionChar . $ids; // always show other decimal attributes
        }

        $part = $this->_convertIdToKeys($this->optionsHash[$attrCode], $ids);

        return $part;
    }

    private function _getAttributeCodeByOptionKey($key, $optionsHash)
    {
        if (!$key && $key !== '0') {
            return false;
        }

        foreach ($optionsHash as $code => $values){
            if (isset($values[$key])){
                return $code;
            }
        }

        return false;
    }

    private function _convertOptionKeysToIds($keys, $values)
    {
        $ids = array();
        foreach ($keys as $k){
            if (isset($values[$k])){
                $ids[] = $values[$k];
            }
        }

        return $ids;
    }

    public function _convertAttributeToMagento($attrCode)
    {
        return str_replace(array($this->optionChar, $this->specialChar), '_', $attrCode);
    }

    public function checkRemoveSuffix($url)
    {
        if ($this->urlSuffix == '') {
            return $url;
        }

        if (substr_compare($url, $this->urlSuffix, -$this->urlSuffixLength) == 0) {
            $url = substr($url, 0, -$this->urlSuffixLength);
        }

        return $url;
    }

    public function checkAddSuffix($url)
    {
        if ($this->urlSuffix == '') {
            return $url;
        }

        if (strlen($url) < $this->urlSuffixLength || substr_compare($url, $this->urlSuffix, -$this->urlSuffixLength) != 0) {
            $url.= $this->urlSuffix;
        }

        return $url;
    }

    public function getUrlSuffix()
    {
        return $this->urlSuffix;
    }

    public function checkRemoveBrandUrlKey($origUrl)
    {
        if ((Amasty_Shopby_Model_Source_Url_Mode::MODE_DISABLED == Mage::getStoreConfig('amshopby/seo/urls')) || !Mage::getStoreConfig('amshopby/brands/attr'))
            return $origUrl;

        $brandUrlKey = trim(Mage::getStoreConfig('amshopby/brands/url_key'));
        if(!$brandUrlKey)
            return $origUrl;

        $brandUrlKey .= '/';
        $len = strlen($brandUrlKey);

        $url = $origUrl;
        $url = ltrim($url, '/');

        if(substr($url,0,$len) == $brandUrlKey)
            $origUrl = substr($url, $len - 1);

        return $origUrl;
    }

    public function formatAttributePartForApply($attrCode, $ids)
    {
        if ($this->isDecimal($attrCode)) {
            return $ids;
        }

        $part = $this->_convertIdToKeys($this->optionsHash[$attrCode], $ids);

        if (!$part) {
            return '';
        }

        return $part;
    }

    public function getPriceFilterConfig($code, $value) {
        $urlBuilder = Mage::getModel('amshopby/url_builder');
        $urlBuilder->reset();
        $additionalData = $urlBuilder->getAttributeOptionConfig($code, $value);

        return json_encode($additionalData);
    }

    /**
     * Get child_id => parents_id array
     * @return Amasty_Shopby_Model_Mysql4_Value_Link_Collection
     */
    public function getMappedOptionsWithParents()
    {
        if ($this->mappedOptionsWithParents === null) {
            $attrHelper = Mage::helper('amshopby/attributes');
            $prefix = $attrHelper::MAPPED_PREFIX;
            /** Amasty_Shopby_Model_Mysql4_Value_Link_Collection $collection */
            $collection = Mage::getModel('amshopby/value_link')
                ->getCollection();
            $collection
                ->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('main_table.option_id'))
                ->join(
                    array('am_value' => $collection->getTable('amshopby/value')),
                    'main_table.child_id = am_value.value_id',
                    array()
                )->join(
                    array('am_filter' => $collection->getTable('amshopby/filter')),
                    'am_value.filter_id = am_filter.filter_id AND am_filter.use_mapping = 1 AND am_filter.show_child_filter = 1',
                    array('parents' => 'GROUP_CONCAT(CONCAT("' . $prefix . '",main_table.parent_id) SEPARATOR ",")')
                )
                ->group('main_table.option_id');

            $this->mappedOptionsWithParents =
                $collection->getConnection()->fetchPairs($collection->getSelect());
            foreach ($this->mappedOptionsWithParents as &$val) {
                $val = explode(',', $val);
            }

        }
        return $this->mappedOptionsWithParents;
    }
}
