<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Searchautocomplete
 * @version    3.4.8
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */

class AW_Searchautocomplete_Model_Search extends Varien_Object
{
    public function search($searchedQuery, $storeId = null)
    {
        if (is_null($storeId)) {
            $storeId = Mage::app()->getStore()->getId();
        }
        if (is_null($searchedQuery) || is_null($storeId)) {
            return null;
        }

        $productCollection = null;

        if (Mage::helper('searchautocomplete')->canUseADVSearch()) {
            try {
                $productCollection = $this->searchProductsAdvancedSearch($searchedQuery, $storeId);
            } catch (Exception $e) {
                return null;
            }
        } else {
            $productIds = $this->searchProducts($storeId);

            if (Mage::helper('searchautocomplete/config')->getInterfaceSearchByTags()) {
                $tagProductsIds = $this->searchByTags();
                $productIds = array_unique(array_merge($productIds, $tagProductsIds));
            }

            if (!(Mage::helper('searchautocomplete/config')->getInterfaceShowOutOfStockProducts())) {
                $productIds = $this->_getInStockProductIdsOnly($productIds);
            }

            if (!count($productIds)) {
                return null;
            }

            $productCollection = $this->_prepareCollection();
            $productCollection
                ->addFieldToFilter('entity_id', array('in' => $productIds))
                ->addStoreFilter($storeId)
                ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                ->setVisibility(array(
                    Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
                    Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH
                ))
            ;
            $topIds = $this->searchProductsByName($storeId, 1000);
            if (count($topIds) > 0) {
                $productCollection->setOrderByRelevance($topIds);
            }
        }

        if (is_null($productCollection)) {
            return null;
        }
        $productCollection = $this->_postProcessCollection($productCollection);
        $productCollection->setOrder('name', Varien_Data_Collection::SORT_ORDER_ASC);
        $productCollection->setPageSize(Mage::helper('searchautocomplete/config')->getInterfaceShowProducts());
        return $productCollection;
    }

    public function searchProductsAdvancedSearch($searchedQuery, $storeId)
    {
        if ($synonym = Mage::helper('searchautocomplete')->getSynonymFor($searchedQuery)) {
            $searchedQuery = $synonym;
        }
        $result = Mage::getModel('awadvancedsearch/api')->catalogQuery($searchedQuery, $storeId);
        if ($result === false) {
            return null;
        }
        if (!is_null($result)) {
            Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($result);
        }
        return $result;
    }

    /**
     * Deprecated since 3.4.8
     */
    public function searchProductsFulltext($searchedQuery, $storeId)
    {
        $query = Mage::getModel('catalogsearch/query')
            ->setQueryText($searchedQuery)
            ->prepare();

        Mage::getResourceModel('catalogsearch/fulltext')->prepareResult(
            Mage::getModel('catalogsearch/fulltext'),
            $searchedQuery,
            $query
        );

        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->getSelect()->joinInner(
            array('search_result' => $collection->getTable('catalogsearch/result')),
            $collection->getConnection()->quoteInto(
                'search_result.product_id=e.entity_id AND search_result.query_id=?',
                $query->getId()
            ),
            array('relevance' => 'relevance')
        );

        $result = $collection->getAllIds();

        return $result;
    }

    public function searchProducts($storeId)
    {
        $ids = array();
        $resultIds = array();
        $entityTypeId = Mage::helper('searchautocomplete')->getEntityTypeId();
        $searchedWords = Mage::helper('searchautocomplete')->getSearchedWords();

        foreach ($searchedWords as $word) {

            $attributesCond = $this->_getSearchableAttributesCond($word);

            if (count($attributesCond) == 0) {
                return $ids;
            }
            $productCollection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('entity_id')
                ->addAttributeToFilter('entity_type_id', array('eq' => $entityTypeId))
                ->addAttributeToFilter($attributesCond, null, 'left')
                ->addStoreFilter($storeId)
                ->distinct(TRUE)
            ;
            $resultIds[] = $productCollection->getAllIds();
        }

        foreach ($resultIds as $result) {
            if (count($ids) == 0) {
                $ids = $result;
            } else {
                $ids = array_intersect($ids, $result);
            }
        }
        return array_unique($ids);
    }

    public function searchProductsByName($storeId, $limit = null)
    {
        $entityTypeId = Mage::helper('searchautocomplete')->getEntityTypeId();
        $ids = array();
        $resultIds = array();
        $searchedWords = Mage::helper('searchautocomplete')->getSearchedWords();

        $searchableAttributes = explode(',', Mage::helper('searchautocomplete/config')->getInterfaceSearchableAttributes());
        if (count($searchableAttributes) !== 0) {
            $allAttributes = Mage::getModel('eav/entity_attribute')
                ->getCollection()
                ->addFieldToFilter('attribute_id', array('in' => $searchableAttributes));

            foreach ($allAttributes as $attribute) {
                if ($attributeId = $attribute->getId() && $attribute->getAttributeCode() == 'name') {
                    $productCollection = Mage::getModel('catalog/product')->getCollection()
                        ->addAttributeToSelect('entity_id')
                        ->addAttributeToFilter('entity_type_id', array('eq' => $entityTypeId))
                        ->addStoreFilter($storeId)
                        ->setOrder('name', Varien_Data_Collection::SORT_ORDER_ASC)
                        ->distinct(TRUE)
                    ;
                    foreach ($searchedWords as $word) {
                        $productCollection->addAttributeToFilter(
                            $attribute->getAttributeCode(),
                            array("like" => "%" . addslashes($word) . "%"),
                            'left'
                        );
                    }
                    $resultIds['top'] = $productCollection->getAllIds($limit);

                    $productCollection = Mage::getModel('catalog/product')->getCollection()
                        ->addAttributeToSelect('entity_id')
                        ->addAttributeToFilter('entity_type_id', array('eq' => $entityTypeId))
                        ->addStoreFilter($storeId)
                        ->setOrder('name', Varien_Data_Collection::SORT_ORDER_ASC)
                        ->distinct(TRUE)
                    ;
                    foreach ($searchedWords as $word) {
                        $attributesCond[] = array(
                            'attribute'  => $attribute->getAttributeCode(),
                            'like' => "%". addslashes($word). "%"
                        );
                     }
                    $productCollection->addAttributeToFilter($attributesCond, null, 'left');
                    $resultIds['med'] = $productCollection->getAllIds($limit);

                    $topIds = array_reverse($resultIds['top']);
                    $medIds = array_reverse(array_diff($resultIds['med'], $topIds));
                    $ids = array_merge($medIds, $topIds);

                    if (count($ids) > $limit) {
                        $ids = array_slice($ids, count($ids) - $limit);
                    }
                    break;
                }
            }
        }
        return array_unique($ids);
    }

    public function searchByTags()
    {
        $searchedWords = Mage::helper('searchautocomplete')->getSearchedWords();
        $cond = array();
        foreach ($searchedWords as $word) {
            $cond[] = array("like" => "%{$word}%");
        }
        $tagCollection = Mage::getResourceModel('tag/tag_collection')
            ->addFieldToFilter("name", $cond);

        $tagProductIds = array();
        foreach ($tagCollection as $tag) {
            $tagProductIds = array_merge($tagProductIds, $tag->getRelatedProductIds());
        }
        return $tagProductIds;
    }

    protected function _getSearchableAttributesCond($searchedWord)
    {
        $searchableAttributes = explode(',', Mage::helper('searchautocomplete/config')->getInterfaceSearchableAttributes());
        if (count($searchableAttributes) !== 0) {
            $allAttributes = Mage::getModel('eav/entity_attribute')
                ->getCollection()
                ->addFieldToFilter('attribute_id', array('in' => $searchableAttributes));

            $attributesCond = array();
            foreach ($allAttributes as $attribute) {
                if ($attributeId = $attribute->getId()) {
                    $attributesCond[] = array(
                        'attribute'  => $attribute->getAttributeCode(),
                        'like' => "%". addslashes($searchedWord). "%"
                    );
                }
            }
        }
        return $attributesCond;
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('searchautocomplete/resource_product_collection');
        return $collection;
    }

    protected function _postProcessCollection($productCollection)
    {
        $productCollection
            ->addAttributeToSelect('*')
            ->addMinimalPrice()
            ->addFinalPrice()
            ->groupByAttribute('entity_id')
        ;
        return $productCollection;
    }

    /**
     * @param array $productIds
     *
     * @return array
     */
    protected function _getInStockProductIdsOnly($productIds)
    {
        if (!count($productIds)) {
            return array();
        }

        $products = Mage::getModel('cataloginventory/stock_item')
            ->getCollection()
            ->addProductsFilter($productIds);

        $inStockProductIds = array();
        foreach ($products as $product) {
            if ($product->getIsInStock()){
                $inStockProductIds[] = $product->getProductId();
            }
        }
        return $inStockProductIds;
    }
}