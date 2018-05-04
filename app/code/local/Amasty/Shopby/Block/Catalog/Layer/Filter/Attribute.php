<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


/**
 * Class Amasty_Shopby_Block_Catalog_Layer_Filter_Attribute
 * @method Mage_Catalog_Model_Resource_Eav_Attribute getAttributeModel()
 */
class Amasty_Shopby_Block_Catalog_Layer_Filter_Attribute extends Amasty_Shopby_Block_Catalog_Layer_Filter_Attribute_Pure
{
    public function getFilter()
    {
        return $this->_filter;
    }

    public function getChildAlias()
    {
        return $this->_alias . '_child';
    }

    public function getItemsAsArray()
    {
        $cacheKey = 'items_as_array';
        $cached = $this->getData($cacheKey);
        if (isset($cached)) {
            return $cached;
        }
        $params = Mage::app()->getRequest()->getParams();
        $isMultipleNoindexMode = $this->getSeoNoindex() == Amasty_Shopby_Model_Filter::SEO_NO_INDEX_MULTIPLE_MODE;
        $isApplyByButton = Mage::app()->getStore()->getConfig('amshopby/general/submit_filters');
        $applyConfigDumb = json_encode(array());
        $displayType = $this->getDisplayType();

        $selected = false;
        $items = array();

        /** @var Amasty_Shopby_Model_Url_Builder $urlBuilder */
        $urlBuilder = Mage::getModel('amshopby/url_builder');
        $urlBuilder->reset();
        $urlBuilder->clearPagination();

        foreach ($this->getItems() as $itemObject){
            /** @var Amasty_Shopby_Model_Catalog_Layer_Filter_Item  $itemObject */
            $item = array();
            $item['id'] = $itemObject->getOptionId();
            $item['url']   = $this->htmlEscape($itemObject->getUrl($urlBuilder));
            $item['label'] = $itemObject->getLabel();
            $item['descr'] = $itemObject->getDescr();

            $item['count'] = '';
            $item['countValue']  = $itemObject->getCount();
            if (!$this->getHideCounts()) {
                $item['count']  = '&nbsp;<span class="count">(' . $itemObject->getCount() . ')</span>';
            }

            $item['image'] = '';
            if ($itemObject->getImage()){
                $item['image'] = Mage::getBaseUrl('media') . 'amshopby/' . $itemObject->getImage();
            }

            if ($itemObject->getImageHover()) {
                $item['image_hover'] = Mage::getBaseUrl('media') . 'amshopby/' . $itemObject->getImageHover();
            }

            $skipAttributeClass =
                $displayType == Amasty_Shopby_Model_Source_Attribute::DT_IMAGES_ONLY ||
                $displayType == Amasty_Shopby_Model_Source_Attribute::DT_DROPDOWN;
            $item['css'] = ($skipAttributeClass) ? '' : 'amshopby-attr';

            if ($itemObject->getIsSelected()){
                $selected = true;
                $item['css'] .= '-selected';
                if (3 == $displayType){ //dropdown
                    $item['css'] = 'selected';
                }
            }

            if ($itemObject->getCount() === 0)
            {
                $item['css'] .= ' amshopby-attr-inactive';
            }

            if($isMultipleNoindexMode) {
                if ($this->getSeoRel() && isset($params[$this->getRequestValue()]) && ($params[$this->getRequestValue()] != $item['id']))
                    $item['rel'] =  ' rel="nofollow" ';
                else
                    $item['rel'] = '';
            }
            else
                $item['rel'] =  $this->getSeoRel() ? ' rel="nofollow" ' : '';

            $item['is_featured'] = $itemObject->getIsFeatured();

            $item['data-config'] = $isApplyByButton
                ? $itemObject->getUrlAttributeOptionConfigAsJson($urlBuilder)
                : $applyConfigDumb;
            $items[] = $item;
        }

        $sortBy = $this->getSortBy();
        $functions = array(
            Amasty_Shopby_Model_Filter::SORT_BY_NAME => 'sortOptionsByName',
            Amasty_Shopby_Model_Filter::SORT_BY_QTY => 'sortOptionsByCounts'
        );
        if (isset($functions[$sortBy])){
            usort($items, array(Mage::helper('amshopby/attributes'), $functions[$sortBy]));
        }

        // add less/more
        $max = $this->getMaxOptions();
        if ($selected) {
            $max = 0;
        }
		$featuredItems = array();
		$standartItems = array();
        $i   = 0;
        foreach ($items as $k => $item){
            $style = '';
            if ($max && (++$i > $max)){
                $style = 'style="display:none" class="amshopby-attr-' . $this->getRequestValue() . '"';
            }
            $items[$k]['style'] = $style;
			$items[$k]['default_sort'] = $i;
			$items[$k]['featured_sort'] = $i;
			if($item['is_featured']) {
				$featuredItems[] = $items[$k];
			} else {
				$standartItems[] = $items[$k];
			}
        }
		if($this->getSortFeaturedFirst() && count($featuredItems) > 0) {
			usort($featuredItems, array(Mage::helper('amshopby/attributes'), 'sortOptionsByName'));
			$items = array_merge($featuredItems, $standartItems);
			$i = 0;
			foreach($items as $k=>$item) {
				$style = '';
				if ($max && (++$i > $max)){
					$style = 'style="display:none" class="amshopby-attr-' . $this->getRequestValue() . '"';
				}
				$items[$k]['style'] = $style;
				$items[$k]['featured_sort'] = $i;
			}
		}
        $this->setShowLessMore($max && ($i > $max));

        $this->setData($cacheKey, $items);
        return $items;
    }

    public function getRequestValue()
    {
        return $this->_filter->getAttributeModel()->getAttributeCode();
    }

    public function getItemsCount()
    {
        $v = Mage::app()->getRequest()->getParam($this->getRequestValue());
        if (isset($v) && $this->getRequestValue() == trim(Mage::getStoreConfig('amshopby/brands/attr'))){
            $cat    = Mage::registry('current_category');
            $rootId = (int) Mage::app()->getStore()->getRootCategoryId();
            if ($cat && $cat->getId() == $rootId){
                // and this is not landing page
                $page = Mage::app()->getRequest()->getParam('am_landing');
                if (!$page) return 0;
            }
        }

        $cnt     = parent::getItemsCount();
        $showAll = !Mage::getStoreConfig('amshopby/general/hide_one_value');
        return ($cnt > 1 || $showAll) ? $cnt : 0;
    }

    public function getRemoveUrl()
    {
        /** @var Amasty_Shopby_Model_Url_Builder $urlBuilder */
        $urlBuilder = Mage::getModel('amshopby/url_builder');
        $urlBuilder->reset();
        $urlBuilder->clearPagination();
        $urlBuilder->changeQuery(array(
            $this->getRequestValue() => null,
        ));

        $url = $urlBuilder->getUrl();
        return $url;
    }

    public function getShowSearch()
    {
        return
            parent::getShowSearch() &&
            (
                !$this->getNumberOptionsForShowSearch() ||
                $this->getNumberOptionsForShowSearch() <= count($this->getItemsAsArray())
            );
    }

    public function getSingleChoice()
    {
        $attributeCode = $this->_filter->getAttributeModel()->getAttributeCode();
        $brandCode = trim(Mage::getStoreConfig('amshopby/brands/attr'));
        $rootId = (int) Mage::app()->getStore()->getRootCategoryId();
        $moduleName = Mage::app()->getRequest()->getModuleName();
        $currentCategoryId = (int)$this->_filter->getLayer()->getCurrentCategory()->getId();

        return parent::getSingleChoice()
            || (($attributeCode === $brandCode) && ($rootId == $currentCategoryId) && ($moduleName === 'amshopby' ||$moduleName === 'cms' ));
    }

    public function getRemoveOptionConfig() {
        /** @var Amasty_Shopby_Model_Url_Builder $urlBuilder */
        $urlBuilder = Mage::getModel('amshopby/url_builder');
        $urlBuilder->reset();
        $dataConfig = $urlBuilder->getAttributeOptionConfig($this->getRequestValue(), '');

        return json_encode($dataConfig);
    }
}
