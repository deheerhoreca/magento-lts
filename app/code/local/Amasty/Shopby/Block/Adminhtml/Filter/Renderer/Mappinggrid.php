<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Block_Adminhtml_Filter_Renderer_Mappinggrid extends Varien_Data_Form_Element_Abstract
{
    protected $_element;

    public function getElementHtml()
    {
        $styles = '<style>#mapping_grid table tr>td.label {display: none; }'
            . '#mapping_grid table {width: 100%; }'
            . '#mapped_options .value {width: 80%; }'
            . '</style>';
        return $styles . Mage::app()->getLayout()
            ->createBlock('amshopby/adminhtml_filter_edit_tab_values_mapped')->toHtml();
    }
}
