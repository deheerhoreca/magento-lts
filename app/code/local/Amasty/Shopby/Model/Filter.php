<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */

/**
 * @method string getAttributeCode()
 * @method int getDisplayType()
 * @method string getExcludeFrom()
 * @method string getIncludeIn()
 * @method int getSingleChoice()
 * @method int getShowOnList()
 * @method int getSeoNofollow()
 * @method int getSeoNoindex()
 * @method int getHideCounts()
 * @method int getUseAndLogic()
 * @method Amasty_Shopby_Model_Filter setDisplayType(int)
 */
class Amasty_Shopby_Model_Filter extends Mage_Core_Model_Abstract
{
    const SEO_NO_INDEX_NO_MODE = 0;
    const SEO_NO_INDEX_YES_MODE = 1;
    const SEO_NO_INDEX_MULTIPLE_MODE = 2;
    
    const SORT_BY_POS = 0;
    const SORT_BY_NAME = 1;
    const SORT_BY_QTY = 2;

    public function _construct()
    {    
        $this->_init('amshopby/filter');
    }

    public function getDisplayTypeString()
    {
        $hash = $this->getDisplayTypeOptionsSource()->getHash();
        return $hash[$this->getDisplayType()];
    }

    public function getDisplayTypeOptionsSource()
    {
        $sourceName = ($this->getBackendType() == 'decimal') ? 'price' : 'attribute';
        $modelName = 'amshopby/source_' . $sourceName;
        $source = Mage::getModel($modelName);
        return $source;
    }

    public function getIncludeInArray()
    {
        $cats = trim(str_replace(' ', '', $this->getIncludeIn()));
        return ($cats == '') ? null : explode(',', $cats);
    }

    public function getExcludeFromArray()
    {
        $cats = trim(str_replace(' ', '', $this->getExcludeFrom()));
        return ($cats == '') ? array() : explode(',', $cats);
    }

    public function getAttributeId()
    {
        if (!$this->hasData('attribute_id')) {
            /** @var Mage_Catalog_Model_Resource_Attribute $resource */
            $resource = Mage::getResourceModel('catalog/attribute');
            $attributeId = $resource->getIdByCode(Mage_Catalog_Model_Product::ENTITY, $this->getAttributeCode());
            $this->setData('attribute_id', $attributeId);
        }
        $attributeId = $this->getData('attribute_id');
        return $attributeId;
    }

    protected function _afterSave()
    {
        if (!$this->getData('parents')) {
            return parent::_afterSave();
        }

        $allChildren = (array) $this->getData('children');
        $positions = (array) $this->getData('mapped_position');
        $parents = explode(',', $this->getData('parents'));

        foreach ($parents as $optionId) {
            if (!in_array($optionId, array_keys($allChildren))) {
                continue;
            }
            $parentSettingOption = Mage::getModel('amshopby/value')->load($optionId);
            $currentChildren = isset($allChildren[$optionId]) ? $allChildren[$optionId] : array();
            $parentSettingOption->setChildIds($currentChildren);
            if (isset($positions[$optionId])) {
                $parentSettingOption->setMappedPosition($positions[$optionId]);
            }
            $parentSettingOption->save();
        }
        return $this;
    }

    public function getCurrentUrlAlias()
    {
        return $this->_getUnserializedValue('child_filter_name');
    }

    protected function _getUnserializedValue($field)
    {
        $storeId = Mage::app()->getStore()->getId();
        $value = $this->getData($field);
        $unserialized = @unserialize($value);
        if (!$unserialized) return $value;
        !empty($unserialized[$storeId]) ? $return = $unserialized[$storeId] : $return = $unserialized[0];
        return $return;
    }

}
