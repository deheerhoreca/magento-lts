<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Block_Subcategories_Brand extends Amasty_Shopby_Block_Subcategories
{
    protected function _construct()
    {
        parent::_construct();
        $this->addData($this->getConfigData());
        $this->setTemplate('amasty/amshopby/subcategories.phtml');
    }

    /**
     * @return Mage_Catalog_Model_Resource_Category_Collection|Mage_Catalog_Model_Resource_Category_Flat_Collection
     */
    protected function getCategoryCollection()
    {
        $collection = parent::getCategoryCollection();
        $allProductsIds = Mage::getSingleton('catalog/layer')->getProductCollection()->getAllIds();
        $categoryHandler = Mage::getSingleton('amshopby/catalog_categoryHandler');
        $categoryHandler->addProductFilterToCollection($collection, $allProductsIds);

        return $collection;
    }

    /**
     * @return Mage_Catalog_Model_Resource_Category_Collection|Mage_Catalog_Model_Resource_Category_Flat_Collection``
     */
    public function getSubcategories()
    {
        $collection = parent::getSubcategories();

        $currentBrand = Mage::helper('amshopby/attributes')->getRequestedBrandOption();
        $brandAttributeCode = trim(Mage::getStoreConfig('amshopby/brands/attr'));
        $urlsWithBrandFilter = Mage::helper('amshopby/url')
            ->getCategoriesUrl($collection->getAllIds(), array($brandAttributeCode => $currentBrand->getOptionId()));
        foreach ($collection as $category) {
            $category->setFilteredUrl($urlsWithBrandFilter[$category->getId()]);
        }

        return $collection;
    }

    /**
     * @param Mage_Catalog_Model_Resource_Category_Collection|Mage_Catalog_Model_Resource_Category_Flat_Collection $collection
     * @param int $categoryId
     */
    protected function addParentFilter($collection, $categoryId)
    {
        if ($this->getEnable() === Amasty_Shopby_Model_Source_BrandCategories_DisplayMode::ENABLED_ALL) {
            $collection->addFieldToFilter('entity_id', array('neq' => $categoryId));
        } else {
            parent::addParentFilter($collection, $categoryId);
        }

    }

    /**
     * @return array
     */
    protected function getConfigData()
    {
        $configData = array();
        foreach (array('enable', 'columns', 'width', 'height', 'order', 'shownames') as $value) {
            $configData[$value] = Mage::getStoreConfig('amshopby/brands/categories_' . $value);
        }

        return $configData;
    }
}
