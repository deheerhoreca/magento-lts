<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Block_Catalog_Layer_Filter_Attribute_Child
    extends Amasty_Shopby_Block_Catalog_Layer_Filter_Attribute
{
    public function __construct()
    {
        parent::__construct();
        $this->_filterModelName = 'amshopby/catalog_layer_filter_attribute_child';
    }

    /**
     * @return bool
     */
    public function getSingleChoice()
    {
        return false;
    }

    public function getDisplayType()
    {
        $result = $this->getData('display_type');
        if ($result == Amasty_Shopby_Model_Source_Attribute::DT_DROPDOWN) {
            $result = Amasty_Shopby_Model_Source_Attribute::DT_LABELS_ONLY;
        }
        return $result;
    }
}
