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


class AW_Searchautocomplete_Block_Suggest extends Mage_Catalog_Block_Product_List
{
    protected $_suggestItems = null;

    public function isCanShowSuggestedKeywords()
    {
        if (!Mage::helper('searchautocomplete/config')->getInterfaceShowSuggest()) {
            return false;
        }
        if (count($this->getSuggests()) < 1) {
            return false;
        }
        return true;
    }

    /**
     * @return array
     */
    public function getSuggests()
    {
        if (null === $this->_suggestItems) {
            $suggestCollection = Mage::getResourceModel('catalogsearch/query_collection')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->setQueryFilter(
                    Mage::helper('searchautocomplete')->getSearchedQuery()
                )
                ->setCurPage(1)
                ->setPageSize(5)
            ;
            $this->_suggestItems = $suggestCollection->getItems();
        }
        return $this->_suggestItems;
    }

    /**
     * @param string $query
     * @return string
     */
    public function getResultUrl($query)
    {
        if (Mage::helper('searchautocomplete')->canUseADVSearch()) {
            return Mage::helper('awadvancedsearch/catalogsearch')->getResultUrl($query);
        }
        return Mage::helper('catalogsearch')->getResultUrl($query);
    }
}