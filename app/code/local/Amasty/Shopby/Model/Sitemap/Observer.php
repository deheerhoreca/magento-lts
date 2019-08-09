<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Model_Sitemap_Observer
{
    /**
     * @param $observer
     * @return $this
     */
    public function generateSitemap($observer)
    {
        $storeId = $observer->getEvent()->getStoreId();

        if ($storeId) {
            $collectionBrands = $this->_getBrands($storeId);
            $customPages = $this->_getCustomPages($storeId);

            if ($customPages || $collectionBrands) {
                $resultItems = array_merge($customPages, $collectionBrands, $observer->getCollection()->getItems());
                $observer->getCollection()->setItems($resultItems);
            }
        }

        return $this;
    }

    /**
     * @param $storeId
     * @return array
     */
    protected function _getCustomPages($storeId)
    {
        $collectionPages = Mage::getModel('amshopby/page')->getCollection()->addStoreFilter($storeId);

        $urlSuffix  = Mage::helper('catalog/category')->getCategoryUrlSuffix($storeId);
        $urlSuffix  = $urlSuffix ? $urlSuffix : '';
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, Mage::app()->getStore()->isCurrentlySecure());

        $result = array();
        foreach ($collectionPages as $item) {
            if (strstr($item['url'], $baseUrl)) {
                $object = new Varien_Object();
                $object->setData(
                    array(
                        'id' => $item['page_id'],
                        'url' => str_replace($baseUrl, '', $item['url']) . $urlSuffix,
                        'title' => $item['title']
                    )
                );
                $result[] = $object;
            }
        }

        return $result;
    }

    /**
     * @param $storeId
     * @return array
     */
    protected function _getBrands($storeId)
    {
        $attrCode = trim(Mage::getStoreConfig('amshopby/brands/attr'));
        $result = array();

        if (!$attrCode) {
            return $result;
        }

        $entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
        $attribute = Mage::getModel('catalog/resource_eav_attribute')->loadByCode($entityTypeId, $attrCode);

        if (!$attribute) {
            return $result;
        }

        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

        $filter = new Varien_Object();
        $layer = Mage::getModel('catalog/layer')
            ->setCurrentCategory(Mage::app()->getStore($storeId)->getRootCategoryId());
        $filter->setData(array('layer' => $layer, 'store_id' => $storeId, 'attribute_model' => $attribute));
        $optionsCount = Mage::getResourceModel('catalog/layer_filter_attribute')->getCount($filter);

        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, Mage::app()->getStore()->isCurrentlySecure());

        foreach ($attribute->getSource()->getAllOptions() as $option) {
            if ($option['value']) {
                if (!array_key_exists($option['value'], $optionsCount)) {
                    continue;
                }

                $url = str_replace(
                    $baseUrl,
                    '',
                    Mage::helper('amshopby/url')->getOptionUrl($attrCode, $option['value'])
                );

                $object = new Varien_Object();
                $object->setData(
                    array('id' => $option['value'], 'url' => $url, 'title' => $option['label'])
                );
                $result[] = $object;
            }
        }

        return $result;
    }
}
