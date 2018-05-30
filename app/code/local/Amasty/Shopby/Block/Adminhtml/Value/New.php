<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */

class Amasty_Shopby_Block_Adminhtml_Value_New extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'amshopby';
        $this->_controller = 'adminhtml_value';
        $this->_mode = 'new';

        $this->_removeButton('reset');
    }

    public function getHeaderText()
    {
        return Mage::helper('amshopby')->__('New Grouped Option');
    }

    public function getBackUrl()
    {
        return $this->getUrl('adminhtml/amshopby_filter/edit', array('id' => $this->getRequest()->getParam('filter_id')));
    }
}