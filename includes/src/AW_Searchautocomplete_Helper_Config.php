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


class AW_Searchautocomplete_Helper_Config
{
    const XML_PATH_INTERFACE_QUERY_DELAY = "searchautocomplete/interface/query_delay";
    const XML_PATH_INTERFACE_PRELOADER_IMAGE = "searchautocomplete/interface/preloader_image";
    const XML_PATH_INTERFACE_HEADER = "searchautocomplete/interface/header";
    const XML_PATH_INTERFACE_FOOTER = "searchautocomplete/interface/footer";
    const XML_PATH_INTERFACE_NOTHING_FOUND_NOTICE = "searchautocomplete/interface/nothing_found_notice";
    const XML_PATH_INTERFACE_ITEM_TEMPLATE = "searchautocomplete/interface/item_template";
    const XML_PATH_INTERFACE_SEARCHABLE_ATTRIBUTES = "searchautocomplete/interface/searchable_attributes";
    const XML_PATH_INTERFACE_SHOW_PRODUCTS = "searchautocomplete/interface/show_top_x";
    const XML_PATH_INTERFACE_SHOW_SUGGEST = "searchautocomplete/interface/show_suggest";
    const XML_PATH_INTERFACE_SHOW_ALL_RESULTS_BUTTON = "searchautocomplete/interface/show_all_results_button";
    const XML_PATH_INTERFACE_THUMBNAIL_WIDTH = "searchautocomplete/interface/thumbnail_size";
    const XML_PATH_INTERFACE_USE_ADVANCED_SEARCH = "searchautocomplete/interface/advsearch_integration";
    const XML_PATH_INTERFACE_SHOW_OUT_OF_STOCK_PRODUCTS = "searchautocomplete/interface/show_out_of_stock";
    const XML_PATH_INTERFACE_OPEN_IN_NEW_WINDOW = "searchautocomplete/interface/open_in_new_window";
    const XML_PATH_INTERFACE_SEARCH_BY_TAGS = "searchautocomplete/interface/search_by_tags";


    public function getInterfaceQueryDelay($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_INTERFACE_QUERY_DELAY, $store);
    }

    public function getInterfacePreloaderImageFilename($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_INTERFACE_PRELOADER_IMAGE, $store);
    }

    public function getInterfaceHeader($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_INTERFACE_HEADER, $store);
    }

    public function getInterfaceFooter($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_INTERFACE_FOOTER, $store);
    }

    public function getInterfaceNothingFoundNotice($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_INTERFACE_NOTHING_FOUND_NOTICE, $store);
    }

    public function getInterfaceItemTemplate($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_INTERFACE_ITEM_TEMPLATE, $store);
    }

    public function getInterfaceSearchableAttributes($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_INTERFACE_SEARCHABLE_ATTRIBUTES, $store);
    }

    public function getInterfaceShowProducts($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_INTERFACE_SHOW_PRODUCTS, $store);
    }

    public function getInterfaceShowSuggest($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_INTERFACE_SHOW_SUGGEST, $store);
    }

    public function getInterfaceShowAllResultsButton($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_INTERFACE_SHOW_ALL_RESULTS_BUTTON, $store);
    }

    public function getInterfaceThumbnailWidth($store = null)
    {
        return (int)Mage::getStoreConfig(self::XML_PATH_INTERFACE_THUMBNAIL_WIDTH, $store);
    }

    public function getInterfaceUseAdvancedSearch($store = null)
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_INTERFACE_USE_ADVANCED_SEARCH, $store);
    }

    public function getInterfaceShowOutOfStockProducts($store = null)
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_INTERFACE_SHOW_OUT_OF_STOCK_PRODUCTS, $store);
    }

    public function getInterfaceOpenInNewWindow($store = null)
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_INTERFACE_OPEN_IN_NEW_WINDOW, $store);
    }

    public function getInterfaceSearchByTags($store = null)
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_INTERFACE_SEARCH_BY_TAGS, $store);
    }

    /*+++++++++++++++++++++++++++++++++HELPER METHODS+++++++++++++++++++++++++++++++++*/

    /**
     * @return float
     */
    public function getInterfaceQueryDelayInSec($store = null)
    {
        $queryDelay = $this->getInterfaceQueryDelay($store);
        if(!is_numeric($queryDelay)) {
            $queryDelay = 500;
        }
        return ($queryDelay / 1000);
    }

    /**
     * @return string
     */
    public function getInterfacePreloaderImageUrl($store = null)
    {
        $preloaderImageFilename = $this->getInterfacePreloaderImageFilename($store);
        if($preloaderImageFilename) {
            $storeMediaUrl = Mage::app()->getStore($store)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
            return $storeMediaUrl . 'aw_searchautocomplete/' . $preloaderImageFilename;
        } else {
            return Mage::getDesign()->getSkinUrl('images/aw_searchautocomplete/preloader.gif');
        }
    }
}