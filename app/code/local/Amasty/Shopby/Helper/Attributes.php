<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
class Amasty_Shopby_Helper_Attributes extends Amasty_Shopby_Helper_Cached
{
    const MAPPED_PREFIX  = 'am_mapped_';

    protected $mappedAttributesHash;

    protected $_options;
    protected $_requestedFilterCodes;
    protected $allowUtf8;

    public $_appliedFilterCodes = array();

    private $valueLinkHash;
    private $amShopbyBrandPage;

    public function __construct()
    {
        $this->allowUtf8 = Mage::getStoreConfigFlag('amshopby/seo/allow_utf8');
    }

    /**
     * @param string $storeId
     * @return array
     */
    public function getAllFilterableOptionsAsHash($storeId = null)
    {
        $cacheId = $this->getCacheId('filterable_options_hash' . $storeId);

        $result = $this->load($cacheId);
        if ($result) {
            return $result;
        }

        $requireUniqueOptions = Mage::getStoreConfig('amshopby/seo/hide_attributes')
            || Mage::getStoreConfig('amshopby/seo/urls') == Amasty_Shopby_Model_Source_Url_Mode::MODE_SHORT;
        $xAttributeValuesUnique = array();
        $hash = array();
        $attributes = $this->getFilterableAttributes();

        $options = $this->getAllOptions($storeId);

        foreach ($attributes as $a){
            $code = $a->getAttributeCode();
            $code = $this->prepareAttributeCode($code);
            $hash[$code] = array();
            foreach ($options as $o){
                if ($o['disable_seo_url']) {
                    continue;
                }
                if (($o['value'] || $o['value'] === '0') && $o['attribute_id'] == $a->getId()) { // skip first empty
                    $nonUniqueValue = $o['url_alias'] ? $o['url_alias'] : $o['value'];
                    $unKey = $this->createKey($nonUniqueValue);

                    while (isset($hash[$code][$unKey])
                        || ($requireUniqueOptions && isset($xAttributeValuesUnique[$unKey]))
                    ) {
                        $unKey .= Mage::getStoreConfig('amshopby/seo/special_char');
                    }
                    $hash[$code][$unKey] = $o['option_id'];
                    $xAttributeValuesUnique[$unKey] = true;
                }
            }
        }
        $xAttributeValuesUnique = null;

        $this->save($hash, $cacheId);
        return $hash;
    }

    /**
     * @param string|null $urlAlias
     * @param string|null $storeId
     * @return string|null
     */
    protected function _unserializeUrlAlias($urlAlias, $storeId)
    {
        if ($urlAlias) {
            $storeId = $storeId ?: Mage::app()->getStore()->getId();
            $unserialized = @unserialize($urlAlias);
            if ($unserialized) {
                $urlAlias = !empty($unserialized[$storeId]) ? $unserialized[$storeId] : $unserialized[0];
            }
        }

        return $urlAlias;
    }

    public function getPositionsAttributes()
    {
        $cacheId = $this->getCacheId('positions_attributes');

        $result = $this->load($cacheId);

        if ($result) {
            return $result;
        }

        $positions = array();
        /** @var $attributes Mage_Eav_Model_Attribute[] */
        $attributes = $this->getFilterableAttributes();

        $customPositions = array(
            'ama_category_filter' => Mage::getStoreConfig('amshopby/category_filter/position'),
            'ama_stock_filter' => Mage::getStoreConfig('amshopby/stock_filter/position'),
            'ama_rating_filter' => Mage::getStoreConfig('amshopby/rating_filter/position'),
            'ama_new_filter' => Mage::getStoreConfig('amshopby/new_filter/position'),
            'ama_on_sale_filter' => Mage::getStoreConfig('amshopby/on_sale_filter/position'),
        );

        foreach ($attributes as $a) {
            $positions[$a->getAttributeCode()] = $a->getPosition();
        }
        $positions = array_merge($positions, $customPositions);

        $this->save($positions, $cacheId);

        return $positions;
    }

    public function sortFiltersByOrder($filter1, $filter2)
    {
        if ($filter1->getPosition() == $filter2->getPosition()) {
            if ($filter1 instanceof Mage_Catalog_Block_Layer_Filter_Category || $filter1 instanceof Enterprise_Search_Block_Catalog_Layer_Filter_Category) {
                return -1;
            } elseif ($filter2 instanceof Mage_Catalog_Block_Layer_Filter_Category || $filter2 instanceof Enterprise_Search_Block_Catalog_Layer_Filter_Category) {
                    return 1;
            } elseif ($filter1 instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_Attribute_Child) {
                return 1;
            } elseif ($filter2 instanceof Amasty_Shopby_Block_Catalog_Layer_Filter_Attribute_Child) {
                return -1;
            }

            return 0;
        }

        return $filter1->getPosition() >= $filter2->getPosition() ? 1 : -1;
    }

    public function getFilterableAttributesBySets(array $setIds)
    {
        $cacheId = $this->getCacheId('filterable_attributes_' . implode('.', $setIds));
        $result = $this->load($cacheId);
        if ($result) {
            return $result;
        }

        // Not use array_filter with closure due to compatibility with PHP 5.2
        $attributes = $this->getFilterableAttributes();
        $result = array();
        foreach ($attributes as $attributeId => $attribute)  {
            /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */

            if ($attribute->isInSet($setIds)) {
                $result[$attributeId] = $attribute;
            };
        }

        $this->saveLite($result, $cacheId);
        return $result;
    }

    public function getFilterableAttributes()
    {
        $cacheId = $this->getCacheId('filterable_attributes');
        $result = $this->load($cacheId);
        if ($result) {
            return $result;
        }

        /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection $collection */
        $collection = Mage::getResourceModel('amshopby/product_attribute_collection');
        $collection
            ->setItemObjectClass('eav/entity_attribute')
            ->addStoreLabel(Mage::app()->getStore()->getId())
            ->setOrder('position', 'ASC');

        $collection->getSelect()->group('main_table.attribute_id');

        if (Mage::app()->getRequest()->getModuleName() == 'catalogsearch') {
            $collection->addIsFilterableInSearchFilter();
        } else {
            $collection->addIsFilterableFilter();
        }

        $collection->addSetInfo(true);

        if (Mage::helper('core')->isModuleEnabled('Amasty_Brands')) {
            Mage::helper('ambrands')->removeBrandFilter($collection);
        }
        $collection->load();

        $result = array();
        foreach ($collection as $attribute) {
            if ($attribute->getBackendType() == 'int' && $attribute->getFrontendInput() == 'select') {
                $attribute->setData('source_model', 'eav/entity_attribute_source_table');
            }
            /** @var Mage_Eav_Model_Attribute $attribute */
            $result[$attribute->getAttributeId()]  = $attribute;
        }

        $this->save($result, $cacheId);
        return $result;
    }

    public function createKey($optionLabel)
    {
        if ($this->allowUtf8) {
            $key = preg_replace('/[^\p{L}0-9\s]+/u', Mage::getStoreConfig('amshopby/seo/special_char'), $optionLabel);
        } else {
            $key = Mage::helper('catalog/product_url')->format($optionLabel);
            $key = preg_replace('/[^0-9a-z,]+/i', Mage::getStoreConfig('amshopby/seo/special_char'), $key);
        }
        if ($this->allowUtf8 && function_exists('mb_strtolower')) {
            $key = mb_strtolower($key, 'UTF-8');
        } else {
            $key = strtolower($key);
        }
        $key = trim($key, Mage::getStoreConfig('amshopby/seo/special_char') . Mage::getStoreConfig('amshopby/seo/option_char'));

        if ($key == '') {
            $key = Mage::getStoreConfig('amshopby/seo/special_char');
        }

        return $key;
    }

    public function getDecimalAttributeCodeMap()
    {
        $cacheId = $this->getCacheId('decimal_attribute_code_map');

        $result = $this->load($cacheId);
        if ($result) {
            return $result;
        }

        $map = array();
        $attributes = $this->getFilterableAttributes();
        foreach ($attributes as $attribute) {
            /** @var Mage_Eav_Model_Attribute $attribute */
            $map[$attribute->getAttributeCode()] = $attribute->getBackendType() == 'decimal';
        }

        $this->save($map, $cacheId);
        return $map;
    }



    /**
     * Get option for specific attribute
     * @param string $attributeCode
     * @return array
     */
    public function getAttributeOptions($attributeCode)
    {
        $cacheId = $this->getCacheId('attribute_options_by_attribute_code');
        $hash = $this->load($cacheId);
        if (!$hash) {
            $hash = array();
            $attributes = $this->getFilterableAttributes();
            $options = $this->getAllOptions();
            foreach ($attributes as $attribute)
            {
                $code = $attribute->getAttributeCode();
                $hash[$code] = array();

                foreach ($options as $option) {
                    if ($option['attribute_id'] == $attribute->getAttributeId()) {
                        $hash[$code][] = array(
                            'value' => $option['option_id'],
                            'label' => $option['value'],
                        );
                    }
                }
            }
            $this->save($hash, $cacheId);
        }

        return isset($hash[$attributeCode]) ? $hash[$attributeCode] : array();
    }

    protected function getAllOptions($storeId = null)
    {
        if (null !== $this->_options && !$storeId) {
            return $this->_options;
        }

        $select = $this->prepareAllOptionsSelect($storeId);
        $options = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($select);
        $options = $this->mergeSort($options, $this->getMappedOptions());
        foreach ($options as &$value) {
            $value['url_alias'] = $this->_unserializeUrlAlias($value['url_alias'], $storeId);
        }

        //if storeId != null it is one time execution. No need to store in the variable.
        if (null === $storeId) {
            $this->_options = $options;
        }

        return $options;
    }

    private function prepareAllOptionsSelect($storeId = null)
    {
        /** @var Mage_Eav_Model_Resource_Entity_Attribute_option_Collection $valuesCollection */
        $valuesCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
            ->setStoreFilter($storeId);

        $select = clone $valuesCollection->getSelect();
        $select->order('sort_order', 'ASC');

        $select->joinLeft(
            array('s' => $valuesCollection->getTable('amshopby/value')),
            's.option_id = main_table.option_id',
            array('url_alias', 'title')
        );

        //Only catalog filterable attributes
        $select->joinInner(
            array('f' => $valuesCollection->getTable('amshopby/filter')),
            'f.attribute_id != 0 AND f.attribute_id = main_table.attribute_id', //"f.attribute_id != 0" to prevent a big size of select in rare cases. Usually there is empty result with f.atribute_id = 0
            array('disable_seo_url')
        );

        return $select;
    }

    /**
     * @param array $options
     * @param array $mappedOptions
     * @return array
     */
    protected function mergeSort($options, $mappedOptions)
    {
        if (empty($mappedOptions)) {
            return $options;
        }

        $currMappedOption = array_shift($mappedOptions);
        $result = array();

        foreach ($options as $option) {
            if (!$currMappedOption) {
                $result[] = $option;
                continue;
            }
            while ($currMappedOption && ($option['sort_order'] >= $currMappedOption['sort_order'])) {
                $result[] = $currMappedOption;
                $currMappedOption = array_shift($mappedOptions);
            }

            $result[] = $option;
        }

        if ($currMappedOption) {
            array_unshift($mappedOptions, $currMappedOption);
        }

        $result = array_merge($result, $mappedOptions);

        return $result;
    }

    protected function getMappedOptions()
    {
        $optionArray = array();
        $mappedOptions = Mage::getModel('amshopby/value')->getParentCollection();
        $mappedOptions->getSelect()->joinInner(
            array('f' => $mappedOptions->getTable('amshopby/filter')),
            'f.filter_id = main_table.filter_id AND f.use_mapping = 1',
            array('disable_seo_url', 'attribute_id')
        );
        $mappedOptions->getSelect()->order('mapped_position ASC');

        foreach ($mappedOptions as $filterOption) {
            /** @var Amasty_Shopby_Model_Value $filterOption */
            $optionArray[] = array(
                'value'             => $filterOption->getCurrentTitle(),
                'attribute_id'      => $filterOption->getAttributeId(),
                'url_alias'         => $filterOption->getUrlAlias(),
                'option_id'         => Amasty_Shopby_Helper_Attributes::MAPPED_PREFIX . $filterOption->getValueId(),
                'disable_seo_url'   => $filterOption->getDisableSeoUrl(),
                'sort_order'        => $filterOption->getMappedPosition(),

            );

        }
        return $optionArray;
    }


    public function getRequestedFilterCodes()
    {
        if (!isset($this->_requestedFilterCodes)) {
            $this->_requestedFilterCodes = array();
            $requestedParams = Mage::app()->getRequest()->getParams();

            $attributes = $this->getFilterableAttributes();

            foreach ($attributes as $attribute) {
                /** @var Mage_Eav_Model_Attribute $attribute*/

                $code = $attribute->getData('attribute_code');
                if (array_key_exists($code, $requestedParams)) {
                    $this->_requestedFilterCodes[$code] = $requestedParams[$code];
                }
            }
        }
        return $this->_requestedFilterCodes;
    }

    public function getRequestedOptionIds()
    {
        $optionIds = implode(',', $this->getRequestedFilterCodes());
        return strlen($optionIds) ? explode(',', $optionIds) : array();
    }

    public function lockApplyFilter($code, $type)
    {
        $hash = $type . '*' . $code;
        if (in_array($hash, $this->_appliedFilterCodes)) {
            return false;
        } else {
            $this->_appliedFilterCodes[] = $hash;
            return true;
        }
    }

    /**
     * @return Amasty_Shopby_Model_Value|null
     */
    public function getRequestedBrandOption()
    {
        $brandAttributeCode = trim(Mage::getStoreConfig('amshopby/brands/attr'));
        $query = Mage::app()->getRequest()->getQuery();
        if (!isset($query[$brandAttributeCode])) {
            return null;
        }

        /** @var Amasty_Shopby_Model_Value $value */
        $value = Mage::getModel('amshopby/value')->load($query[$brandAttributeCode], 'option_id');
        if (!$value->getId()) {
            return null;
        }

        return $value;
    }

	public function getIsOptionFeatured($optionId)
	{
        $cacheId = $this->getCacheId('featured_options_hash');

        $listIdsFeaturedOptions = $this->load($cacheId);
        if ($listIdsFeaturedOptions === false ) {
            $listIdsFeaturedOptions = Mage::getModel('amshopby/value')->getResource()->getFeaturedOptionsIds();
            $this->save($listIdsFeaturedOptions, $cacheId);
        }

        return in_array($optionId, $listIdsFeaturedOptions);
	}


	public function prepareAttributeCode($code)
	{
		$code = str_replace(array('_', '-'), Mage::getStoreConfig('amshopby/seo/special_char'), $code);
		return $code;
	}

    public function sortOptionsByName($a, $b)
    {
        $x = trim($a['label']);
        $y = trim($b['label']);

        if ($x == '') return 1;
        if ($y == '') return -1;

        if (is_numeric($x) && is_numeric($y)){
            if ($x == $y)
                return 0;
            return ($x > $y ? 1 : -1);
        }
        else {
            return strcasecmp($x, $y);
        }
    }

    public function sortOptionsByCounts($a, $b)
    {
        if ($a['countValue'] == $b['countValue']) {
            return 0;
        }

        return ($a['countValue'] < $b['countValue'] ? 1 : -1);
    }

    /**
     * @return bool
     */
    public function getMappedAttributesHash()
    {
        if ($this->mappedAttributesHash === null) {
            $this->mappedAttributesHash = Mage::getModel('amshopby/filter')
                ->getCollection()
                ->addFieldToFilter('use_mapping', 1)
                ->getMappedAttributes();
        }

        return $this->mappedAttributesHash;
    }

    /**
     * @return int[][]
     */
    public function getValueLinkHash()
    {
        $cacheKey = 'value_link_hash';
        $hash = $this->load($cacheKey);
        if (!$hash) {
            $data = Mage::getModel('amshopby/value_link')->getCollection()->getValueLink();
            $hash = array();
            foreach ($data as $link) {
                $hash[$link['parent_id']][] = $link['option_id'];
            }

            $this->save($hash, $cacheKey);
        }
        return $hash;
    }

    /**
     * Check if current page is a brand page from Shop by Brand module.
     *
     * @return bool
     */
    protected function isShopbyBrandPage()
    {
        if ($this->amShopbyBrandPage === null) {
            $this->amShopbyBrandPage = false;
            if (Mage::helper('core')->isModuleEnabled('Amasty_Brands')
                && Mage::app()->getRequest()->getParam('ambrand_id')
            ) {
                $this->amShopbyBrandPage = true;
            }
        }
        return $this->amShopbyBrandPage;
    }

    /**
     * @param string $alias
     * @return string
     */
    protected function getCacheId($alias)
    {
        return $this->isShopbyBrandPage() ? $alias : $alias . '_brand';
    }

    /**
     * @return Amasty_Shopby_Model_Value|null
     */
    public function getCurrentBrandPageBrand()
    {
        /** @var Mage_Catalog_Model_Layer $layer */
        $layer = Mage::getSingleton('catalog/layer');
        $category = $layer->getCurrentCategory();

        $brand = $this->getRequestedBrandOption();
        $isBrandPage = $brand
            && $category->getId() == Mage::app()->getStore()->getRootCategoryId()
            && !(Mage::registry('amlanding_page'));

        return $isBrandPage ? $brand : null;
    }
}
