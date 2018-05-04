<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Model_Catalog_CategoryHandler
{
    /**
     * @param Mage_Catalog_Model_Resource_Category_Collection|Mage_Catalog_Model_Resource_Category_Flat_Collection $collection
     * @param array $productsIds
     */
    public function addProductFilterToCollection($collection, $productsIds)
    {
        $mainTableAlias = $this->isEavCollection($collection) ? 'e' : 'main_table';
        $joinCondition = $collection->getConnection()->quoteInto(
            "cat_index.category_id = $mainTableAlias.entity_id AND cat_index.product_id IN (?)",
            $productsIds
        );
        $collection->getSelect()
            ->join(
                array('cat_index' => $collection->getTable('catalog/category_product_index')),
                $joinCondition,
                array()
            )->distinct(true);
    }

    /**
     * @param $collection
     * @return bool
     */
    private function isEavCollection($collection)
    {
        $collectionFrom = $collection->getSelect()->getPart('from');
        return isset($collectionFrom['e']);
    }
}
