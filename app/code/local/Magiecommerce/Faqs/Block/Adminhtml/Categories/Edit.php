<?php
class Magiecommerce_Faqs_Block_Adminhtml_Categories_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct() {
        parent::__construct();
        $this->_objectId    = 'cat_id';
        $this->_blockGroup  = 'faqs';
        $this->_controller  = 'adminhtml_categories';
        $this->_updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('*/*/categories') . '\')');
        $this->_updateButton('save', 'label', Mage::helper('faqs')->__('Save'));
        $this->_updateButton('delete', 'label', Mage::helper('faqs')->__('Delete'));
    }

    public function getHeaderText() {
        if( Mage::registry('faqs_data') and Mage::registry('faqs_data')->getId() ) {
            return Mage::helper('faqs')->__("Edit Category".' - '. $this->htmlEscape(Mage::registry('faqs_data')->getCatName()));
        } else {
            return Mage::helper('faqs')->__('New Category');
        }
    }
}