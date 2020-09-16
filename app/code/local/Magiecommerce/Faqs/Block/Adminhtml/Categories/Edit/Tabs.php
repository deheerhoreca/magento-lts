<?php
class Magiecommerce_Faqs_Block_Adminhtml_Categories_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct() {
        parent::__construct();
        $this->setId('faqs_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('faqs')->__('Category Information'));
    }

    protected function _beforeToHtml() {
        $this->addTab('general_section', array(
            'label'     => Mage::helper('faqs')->__('General Information'),
            'title'     => Mage::helper('faqs')->__('General Information'),
            'content'   => $this->getLayout()->createBlock('faqs/adminhtml_categories_edit_tab_form')->toHtml(),
        ));
        	$this->addTab('seoinfo_section', array(
			'label'     => Mage::helper('faqs')->__('SEO Information'),
			'title'     => Mage::helper('faqs')->__('SEO Information'),
			'content'   => $this->getLayout()->createBlock('faqs/adminhtml_categories_edit_tab_seoinfo')->toHtml(),
		));

        return parent::_beforeToHtml();
    }
}