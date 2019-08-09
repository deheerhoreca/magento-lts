<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Feed
 */


class Amasty_Feed_Model_Attribute_Compound_Abstract extends Varien_Object
{
    protected $_feed;

    protected $_attributesCodes = array();

    const CATEGORIES_SHORT_PATH = true;

    public function init($feed)
    {
        $this->_feed = $feed;

        return $this;
    }

    public function getFeed()
    {
        return $this->_feed;
    }

    function getAttributesCodes()
    {
        return $this->_attributesCodes;
    }

    function prepareCollection($collection)
    {

    }

    function getCompoundData($productData)
    {
        return null;
    }

    function hasCondition()
    {
        return false;
    }

    function hasFilterCondition()
    {
        return false;
    }

    function validateFilterCondition($productData, $operatorCode, $valueCode)
    {
        return Amasty_Feed_Model_Field_Condition::compare(
            $operatorCode,
            $this->getCompoundData($productData),
            $valueCode
        );
    }

    function prepareCondition($collection, $operator, $condVal, &$attributesFields)
    {

    }

    /**
     * @param array $productData
     *
     * @return int
     */
    protected function _getCategoryId($productData)
    {
        $categoryId = $productData['category_id'];
        $categoryIds = $this->_getCategoryIds($productData);
        $feedStoreId = $this->_feed->getStoreId();

        if (count($categoryIds) > 0) {
            $categoryModel = Mage::getModel('catalog/category');
            $rootCategoryId = Mage::app()->getStore($feedStoreId)->getRootCategoryId();
            $category = $categoryModel->load($rootCategoryId);
            $allChildCategories = $categoryModel->getResource()->getAllChildren($category);
            rsort($allChildCategories);

            foreach ($allChildCategories as $storeCategoryId) {
                foreach ($categoryIds as $productCategoryId) {
                    if ($productCategoryId == $storeCategoryId) {
                        $categoryId = $productCategoryId;
                        break 2;
                    }
                }
            }
        }

        return $categoryId;
    }

    /**
     * @param array $productData
     *
     * @return array
     */
    protected function _getCategoryIds($productData)
    {
        $result = array();
        /** @var Mage_Catalog_Model_Resource_Category_Collection $categoryCollection */
        $categoryCollection = Mage::getResourceModel('catalog/category_collection')->addIsActiveFilter();
        $categoryIds = isset($productData['category_ids']) ?
            $productData['category_ids'] : null;

        if ($categoryIds) {
            $result = explode(',', $categoryIds);
            $categoryCollection->addIdFilter($categoryIds);
        }

        $matchedIds = $categoryCollection->getAllIds();
        if (is_array($matchedIds) && $matchedIds) {
            $result = $matchedIds;
        }

        return $result;
    }

    protected function _getCategoryName($categoryId)
    {
        $ret = "";

        if (self::CATEGORIES_SHORT_PATH) {
            $categories = $this->getFeed()->getProductCategoriesLast();

            if (isset($categories[$categoryId])) {
                $ret = $categories[$categoryId];
            }

        } else {
            $categories = $this->getFeed()->getProductCategories();

            if (isset($categories[$categoryId])) {
                $ret = $categories[$categoryId];
            }
        }

        return $ret;

    }
}
