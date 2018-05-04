<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Block_Adminhtml_Filter_Renderer_Position extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Number
{
    public function _getInputValueElement(Varien_Object $row)
    {
        return  '<input type="text" class="input-text '
            . $this->getColumn()->getValidateClass()
            . '" name="' . $this->getColumn()->getId()
            . '[' .  $row->getId() . ']'
            . '" value="' . $this->_getInputValue($row) . '"/>';
    }

    protected function _getInputValue(Varien_Object $row)
    {
        return (int) parent::_getInputValue($row);
    }
}
