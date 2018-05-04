<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Block_Catalog_Layer_Filter_OnSale extends Mage_Catalog_Block_Layer_Filter_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('amasty/amshopby/attribute.phtml');
        $this->_filterModelName = 'amshopby/catalog_layer_filter_onSale';
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
            if (in_array($this->getDisplayType(), array(1,3))) //dropdown and images
                $item['css'] = '';

            if ($itemObject->getOptionId() == $this->getRequestValue()) {
                $item['css'] .= '-selected';
                if (3 == $this->getDisplayType()) //dropdown
                    $item['css'] = 'selected';
            }

            $item['rel'] = $this->getSeoRel() ? ' rel="nofollow" ' : '';
            $item['data-config'] = $itemObject->getUrlAttributeOptionConfigAsJson();

            if ($item['countValue']) {
                $items[] = $item;
            }

        }

        return $items;
    }

    public function getRequestValue()
    {
        return Mage::app()->getRequest()->getParam('onSale');
    }

    public function getItemsCount()
    {
        return count($this->getItemsAsArray());
    }
}
