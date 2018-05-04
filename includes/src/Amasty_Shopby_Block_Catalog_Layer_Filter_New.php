<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Block_Catalog_Layer_Filter_New extends Mage_Catalog_Block_Layer_Filter_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('amasty/amshopby/attribute.phtml');
        $this->_filterModelName = 'amshopby/catalog_layer_filter_new';
    }

    public function getPosition()
    {
        if (count($this->getItemsAsArray()) == 0) {
            return -1;
        }

        return parent::getPosition();
    }

    public function getDisplayType()
    {
        return 0;
    }

    public function getItemsAsArray()
    {
        $items = array();
        foreach (parent::getItems() as $itemObject) {
            $item = array();
            $item['url']   = $this->escapeHtml($itemObject->getUrl());
            $item['label'] = $itemObject->getLabel();
            $item['count'] = '';
            $item['countValue']  = $itemObject->getCount();

            if (!$this->getHideCounts()) {
                $item['count']  = ' (' . $itemObject->getCount() . ')';
            }

            $item['css'] = 'amshopby-attr';
            if (in_array(
                $this->getDisplayType(),
                array(
                    Amasty_Shopby_Model_Source_Attribute::DT_IMAGES_ONLY,
                    Amasty_Shopby_Model_Source_Attribute::DT_DROPDOWN)
            )) {
                $item['css'] = '';
            }

            if ($itemObject->getOptionId() == $this->getRequestValue()) {
                $item['css'] .= '-selected';
                if (Amasty_Shopby_Model_Source_Attribute::DT_DROPDOWN == $this->getDisplayType()) {
                    $item['css'] = 'selected';
                }
            }

            $item['rel'] = $this->getSeoRel() ? ' rel="nofollow" ' : '';
            $item['data-config'] = $itemObject->getUrlAttributeOptionConfigAsJson(false);

            if ($item['countValue']) {
                $items[] = $item;
            }

        }

        return $items;
    }

    public function getRequestValue()
    {
        return Mage::app()->getRequest()->getParam('new');
    }

    public function getItemsCount()
    {
        return count($this->getItemsAsArray());
    }
}
