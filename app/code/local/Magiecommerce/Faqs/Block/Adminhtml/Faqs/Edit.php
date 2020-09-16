<?php

class Magiecommerce_Faqs_Block_Adminhtml_Faqs_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {

        parent::__construct();

        $this->_objectId    = 'faqs_id';
        $this->_blockGroup  = 'faqs';
        $this->_controller  = 'adminhtml_categories';
        $this->_updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('*/*/faqs') . '\')');
        $this->_updateButton('save', 'label', Mage::helper('faqs')->__('Save'));
        $this->_updateButton('delete', 'label', Mage::helper('faqs')->__('Delete'));

    }

    public function getHeaderText()
    {

        if( Mage::registry('items_data') and Mage::registry('items_data')->getId() ) {

            return Mage::helper('faqs')->__("Edit FAQ" . " - ". $this->htmlEscape(Mage::registry('items_data')->getQuetion()));
        } else {
            return Mage::helper('faqs')->__('New FAQ');
        }
    }
   protected function _prepareLayout() {
			parent::_prepareLayout();
			if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
				$this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
			}
		}


}