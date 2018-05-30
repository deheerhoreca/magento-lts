<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Oaction
 */
class Amasty_Oaction_Block_Adminhtml_Index_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->removeButton('back')
            ->removeButton('reset')
            ->_updateButton('save', 'label', Mage::helper('amoaction')->__('Import'))
            ->_updateButton('save', 'id', 'upload_button');
    }
    
    protected function _construct()
    {
        parent::_construct();
        $this->_objectId   = 'import_id';
        $this->_blockGroup = 'amoaction';
        $this->_controller = 'adminhtml_index';
    }
    
    public function getHeaderText()
    {
        return Mage::helper('amoaction')->__('Import Tracking Numbers');
    }
}