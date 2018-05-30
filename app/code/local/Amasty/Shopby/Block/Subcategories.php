<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Block_Subcategories extends Mage_Core_Block_Template
{
    const CATALOG_CATEGORY_PATH = 'catalog/category/';

    const SUBCATEGORY_COLUMN_COUNT = 3;

    protected function _construct()
    {
        /* if thumbnale caching
        $this->setCacheTags(array(time()));
        $this->setCacheKey(time());
        */
        $this->setCacheLifetime(null);
    }

    /**
     * @return Mage_Catalog_Model_Resource_Category_Collection|Mage_Catalog_Model_Resource_Category_Flat_Collection
     */
    public function getSubcategories()
    {
        $orders = array('position', 'name');
        $order = $this->getOrder();
        if (!in_array($order, $orders)) {
            $order = current($orders);
        }
        
        $collection = $this->getCategoryCollection();
        $collection->setOrder($order, Varien_Db_Select::SQL_ASC);
        
        foreach ($collection as $category) {
            $image = $category->getThumbnail() ?: $category->getImage();
            if ($image) {
                $imageUrl = Mage::getBaseUrl('media') . '' . self::CATALOG_CATEGORY_PATH . '' . $image;
                $category->setThumbnailUrl($imageUrl);
            }
        }

        return $collection;
    }

    /**
     * @return Mage_Catalog_Model_Resource_Category_Collection|Mage_Catalog_Model_Resource_Category_Flat_Collection
     */
    protected function getCategoryCollection()
    {
        $layer = Mage::getSingleton('catalog/layer');
        /* @var $category Mage_Catalog_Model_Category */
        $category = $layer->getCurrentCategory();
        $collection = $category->getCollection();
        $collection->addAttributeToSelect('url_key')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('image')
            ->addAttributeToFilter('is_active', 1);
        $this->addParentFilter($collection, $category->getId());
        if ($collection instanceof Mage_Catalog_Model_Resource_Category_Collection) {
            /* @var $collection Mage_Catalog_Model_Resource_Category_Collection */
            $collection->joinUrlRewrite();
            $collection->addAttributeToSelect('thumbnail');
        } else {
            /* @var $collection Mage_Catalog_Model_Resource_Category_Flat_Collection */
            $collection->addUrlRewriteToResult();
            $tableName = $collection->getResource()->getMainTable();
            if ($collection->getConnection()->tableColumnExists($tableName, 'thumbnail')) {
                $collection->addAttributeToSelect('thumbnail');
            }
        }

        return $collection;
    }

    /**
     * @param Mage_Catalog_Model_Resource_Category_Collection|Mage_Catalog_Model_Resource_Category_Flat_Collection $collection
     * @param $categoryId
     */
    protected function addParentFilter($collection, $categoryId)
    {
        $collection->addFieldToFilter('parent_id', $categoryId);
    }
    
    public function getDivWidth()
    {
        $columns = $this->getColumns() ? $this->getColumns() : self::SUBCATEGORY_COLUMN_COUNT;
        $result = sprintf("%.4f", 100 / $columns);

        return $result;
    }
}
