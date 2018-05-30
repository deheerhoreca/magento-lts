<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */

/**
 * Class Amasty_Shopby_Model_Catalog_Layer_Filter_Item
 * @method int getCount()
 */
class Amasty_Shopby_Model_Catalog_Layer_Filter_Item extends Mage_Catalog_Model_Layer_Filter_Item
{
    public function getUrl($urlBuilder = null)
    {
        Varien_Profiler::start('amshopby_filter_item_url');

        /** @var Amasty_Shopby_Model_Url_Builder $urlBuilder */
        if (!$urlBuilder instanceof  Amasty_Shopby_Model_Url_Builder) {
            $urlBuilder = Mage::getModel('amshopby/url_builder');
            $urlBuilder->reset();
            $urlBuilder->clearPagination();
        }

        $requestVar = $this->getFilter()->getRequestVar();

		// Fix for old magento versions (before 1.7.0)
		if($requestVar === "price")
		{
			$value = explode(",",$this->getValue());
			if(count($value) > 1) {
				$value = ($value[0]-1)*$value[1]."-".$value[0]*$value[1];
				$this->setValue($value);
			}
		}

        $urlBuilder->changeQuery(array(
            $requestVar => $this->getValue(),
        ));

        $url = $urlBuilder->getUrl();
        Varien_Profiler::stop('amshopby_filter_item_url');
        return $url;
    }
    
    
    public function getRemoveUrl()
    {
        Varien_Profiler::start('amshopby_filter_item_url');

        /** @var Amasty_Shopby_Model_Url_Builder $urlBuilder */
        $urlBuilder = Mage::getModel('amshopby/url_builder');
        $urlBuilder->reset();
        $urlBuilder->clearPagination();

        $urlBuilder->changeQuery(array(
            $this->getFilter()->getRequestVar() => $this->getFilter()->getResetValue(),
        ));

        $url = $urlBuilder->getUrl();

        Varien_Profiler::stop('amshopby_filter_item_url');
        return $url;
    }
    
    /**
     * @param Amasty_Shopby_Model_Url_Builder $urlBuilder
     * @return string
     */
    public function getUrlAttributeOptionConfigAsJson($urlBuilder = null)
    {
        if (!$urlBuilder) {
            /** @var Amasty_Shopby_Model_Url_Builder $urlBuilder */
            $urlBuilder = Mage::getModel('amshopby/url_builder');
            $urlBuilder->reset();
        }

        $requestVar = $this->getFilter()->getRequestVar();
        $isDecimal = Mage::helper('amshopby/url')->isDecimal($requestVar);

        // Fix for old magento versions (before 1.7.0)
        if($requestVar === "price" || $isDecimal) {
            $value = explode(",",$this->getValue());
            if(count($value) > 1) {
                $value = ($value[0]-1)*$value[1]."-".$value[0]*$value[1];
                $this->setValue($value);
            }
        }

        $value = ($requestVar === "price" || $isDecimal) ? $this->getValue() : $this->getOptionId();
        $additionalData = $urlBuilder->getAttributeOptionConfig($requestVar, $value);

        return json_encode($additionalData);
    }
}
