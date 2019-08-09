<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */

class Amasty_Shopby_Block_Catalog_Layer_View_Top extends Amasty_Shopby_Block_Catalog_Layer_View
{
    protected $_blockPos = Amasty_Shopby_Model_Source_Position::TOP;

    /**
     * @return string
     */
    protected function _toHtml()
    {
        $html = '';
        $layer = Mage::getSingleton('catalog/layer');
        $category = $layer->getCurrentCategory();
        if ($category && $category->getDisplayMode() !== Mage_Catalog_Model_Category::DM_PAGE) {
            $html = parent::_toHtml();
        }

        return $html;
    }
}
