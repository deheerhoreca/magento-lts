<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */

class Amasty_Shopby_Helper_Layer_View_Strategy_Stock extends Amasty_Shopby_Helper_Layer_View_Strategy_Abstract
{
    protected function setTemplate()
    {
        return 'amasty/amshopby/attribute.phtml';
    }

    protected function setPosition()
    {
        return Mage::getStoreConfig('amshopby/stock_filter/position');
    }

    protected function setHasSelection()
    {
        return !is_null(Mage::app()->getRequest()->getParam('stock'));
    }

	protected function setCollapsed()
	{
		return $this->isCollapseEnabled() && Mage::getStoreConfig('amshopby/stock_filter/collapsed');
	}

    protected function setComment()
    {
        return Mage::getStoreConfig('amshopby/stock_filter/tooltip');
    }
}
