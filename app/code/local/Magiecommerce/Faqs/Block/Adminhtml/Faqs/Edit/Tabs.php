<?php

class Magiecommerce_Faqs_Block_Adminhtml_Faqs_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('items_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('faqs')->__('FAQ Information'));
        
    }

    protected function _beforeToHtml()
    {
        
        $this->addTab('general_section', array(
            'label'     => Mage::helper('faqs')->__('General Information'),
            'title'     => Mage::helper('faqs')->__('General Information'),
            'content'   => $this->getLayout()->createBlock('faqs/adminhtml_faqs_edit_tab_form')->toHtml()
        ));
        

        return parent::_beforeToHtml();
    }
}