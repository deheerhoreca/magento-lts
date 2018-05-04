<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Model_Catalog_Layer_Filter_Attribute_Child
    extends Amasty_Shopby_Model_Catalog_Layer_Filter_Attribute
{
    /**
     * @param array $itemData
     * @return bool
     */
    protected function isProcessMapped($itemData)
    {
        if (!$itemData['is_dependent']) {
            return false;
        }
        $currentVals = Mage::helper('amshopby')->getRequestValues($this->_requestVar);
        if (array_intersect($itemData['mapped_parents'], $currentVals)) {
            return true;
        }
        return false;
    }

    /**
     * @param array $currentVals
     * @return array
     */
    protected function excludeChildrenWithoutParents($currentVals)
    {
        //don't need to proceed for a child block
        return $currentVals;
    }

    /**
     * If change this, go also to Amasty_Shopby_Helper_Data::getMultiselectAttributeCodes()
     * @return bool
     */
    protected function getSingleChoice()
    {
        return false;
    }

    /**
     * @return bool
     */
    protected function getUseAndLogic()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function getIsMapped()
    {
        return false;
    }

    /**
     * @return string
     */
    protected function getCacheKey()
    {
        return parent::getCacheKey() . '_child';
    }

    /**
     * @return string
     */
    protected function _getAttributeTableAlias()
    {
        $alias = parent::_getAttributeTableAlias();
        return str_replace('_idx', '_child_idx', $alias);
    }

    /**
     * @param string $attributeCode
     * @return bool
     */
    protected function isLocked($attributeCode)
    {
        $code = $attributeCode . '_amchild';
        return parent::isLocked($code);
    }

    /**
     * Apply only child values
     *
     * @param array $currentVals
     * @return array
     */
    protected function excludeValues($currentVals)
    {
        $children = array_keys(Mage::helper('amshopby/url')->getMappedOptionsWithParents());
        return array_intersect($currentVals, $children);
    }

    /**
     * @return string
     */
    public function getName()
    {
        $data = $this->getMappedData();
        $serialized = $data[$this->getAttributeModel()->getAttributeId()]['child_filter_name'];
        $result = '';
        if ($serialized) {
            $storeId = Mage::app()->getStore()->getId();
            $unserialized = @unserialize($serialized);
            if ($unserialized) {
                $result = !empty($unserialized[$storeId]) ? $unserialized[$storeId] : $unserialized[0];
            }
        }

        return $result ? $result : parent::getName() . ' Child';
    }

    public function getResetValue()
    {
        $curVals = Mage::helper('amshopby')->getRequestValues($this->getRequestVar());
        return implode(',', parent::excludeValues($curVals));
    }
}
