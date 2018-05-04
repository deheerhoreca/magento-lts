<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


/**
 * @method string getCmsBlockId()
 * @method string getCmsBlockBottomId()
 * @method string getDescr()
 * @method int getFilterId()
 * @method string getImgBig()
 * @method string getImgMedium()
 * @method string getImgSmall()
 * @method string getImgSmallHover()
 * @method string getMetaDescr()
 * @method string getMetaKw()
 * @method string getMetaTitle()
 * @method boolean getShowOnList()
 * @method boolean setShowOnList()
 * @method int getSortOrder()
 * @method string getTitle()
 * @method string getUrlAlias()
 * @method setTitle(string $title)
 * @method string getIsParent()
 * @method array getChildIds()
 * @method $this setChildIds(array $value)
 */
class Amasty_Shopby_Model_Value extends Mage_Core_Model_Abstract
{

    /** @var Amasty_Shopby_Model_Mysql4_Value_Link_Collection */
    protected $_linkCollection;

    /** @var  Amasty_Shopby_Model_Mysql4_Value_Collection */
    protected $_childrenCollection = false;

    public function _construct()
    {
        $this->_init('amshopby/value');
    }

    public function getCurrentTitle()
    {
        return $this->_getUnserializedValue("Title");
    }

    public function getCurrentDescr()
    {
        return $this->_getUnserializedValue("Descr");
    }

    public function getCurrentMetaDescr()
    {
        return $this->_getUnserializedValue("MetaDescr");
    }

    public function getCurrentMetaKw()
    {
        return $this->_getUnserializedValue("MetaKw");
    }

    public function getCurrentMetaTitle()
    {
        return $this->_getUnserializedValue("MetaTitle");
    }

    public function getCurrentCmsBlockId()
    {
        return $this->_getUnserializedValue("CmsBlockId");
    }

    public function getCurrentCmsBlockBottomId()
    {
        return $this->_getUnserializedValue("CmsBlockBottomId");
    }

    public function getCurrentUrlAlias()
    {
        return $this->_getUnserializedValue("UrlAlias");
    }

    protected function _getUnserializedValue($field)
    {
        $storeId = Mage::app()->getStore()->getId();
        $value = $this->{'get' . $field}();
        $unserialized = @unserialize($value);
        if (!$unserialized) {
            return $value;
        }
        $result = empty($unserialized[$storeId]) ? $unserialized[0] : $unserialized[$storeId];
        if (in_array($field, array('Title', 'Descr', 'MetaDescr', 'MetaKw', 'MetaTitle'))) {
            $result = Mage::helper('amshopby')->buildTemplate($result);
        }

        return $result;
    }

    protected function _afterSave()
    {
        parent::_afterSave();

        if (!$this->getId() || !$this->getIsParent() || !is_array($this->getChildIds()) || $this->isDeleted()) {
            return $this;
        }
        foreach ($this->getLinkCollection() as $item) {
            if (!in_array($item->getChildId(), $this->getChildIds())) {
                $item->delete();
            }

        }
        foreach ($this->getChildIds() as $childId) {
            if (!$this->getLinkCollection()->getItemByColumnValue('child_id', $childId)) {
                $optionId = Mage::getModel('amshopby/value')->load($childId)->getOptionId(); //@todo optimize
                $this->_createLink($childId, $optionId);
            }
        }

        $this->getLinkCollection()->save();

        return $this;
    }

    public function getLinkCollection()
    {
        if (is_null($this->_linkCollection)) {
            $id = $this->getId() ? $this->getId() : 0; //return empty collection if new value
            $this->_linkCollection = Mage::getModel($this->getResource()->getLinkEntity())->getCollection()
                ->addFilter('parent_id', $id)
                ->load();
        }
        return $this->_linkCollection;
    }

    protected function _createLink($childId, $optionId)
    {
        $link = Mage::getModel($this->getResource()->getLinkEntity());
        $link->setData('parent_id', $this->getId());
        $link->setData('child_id', $childId);
        $link->setData('option_id', $optionId);
        $this->getLinkCollection()->addItem($link);
        return $link;
    }

    /**
     * @return Amasty_Shopby_Model_Mysql4_Value_Collection
     */
    public function getCollection()
    {
        return parent::getCollection()
            ->addFieldToFilter('is_parent', array('neq' => 1));

    }

    /**
     * @return Amasty_Shopby_Model_Mysql4_Value_Collection
     */
    public function getParentCollection()
    {
        $collection = parent::getCollection()
            ->addFieldToFilter('is_parent', 1);
        return $collection;
    }

    public function getCollectionByMixedIds($mixedIds)
    {
        $optionIds = array();
        $valueIds = array();
        $prefix = Amasty_Shopby_Helper_Attributes::MAPPED_PREFIX;
        foreach ($mixedIds as $value) {
            if (intval($value)) {
                $optionIds[] = $value;
            } elseif (strpos($value, $prefix) === 0) {
                $valueIds[] = (int)substr($value, strlen($prefix));
            }
        }

        $collection = parent::getCollection();

        $condition = array();

        if ($optionIds) {
            $condition[] = 'option_id IN (' . join(',', $optionIds) . ')';
        }

        if ($valueIds) {
            $condition[] = 'value_id IN (' . join(',', $valueIds) . ')';
        }

        if (count($condition)) {
            $condition = implode(' OR ', $condition);
            $collection->getSelect()->where($condition);
        }

        return $collection;
    }

    public function getOptionId()
    {
        if ($this->getIsParent()) {
            return Amasty_Shopby_Helper_Attributes::MAPPED_PREFIX . $this->getValueId();
        }
        return $this->getData('option_id');
    }
}
