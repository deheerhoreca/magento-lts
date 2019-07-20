<?php

class MagicToolbox_MagicScroll_Block_Adminhtml_Settings_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {

        parent::__construct();

        $this->setId('magicscroll_config_tabs');
        $this->setDestElementId('edit_form');//this should be same as the form id
        $this->setTitle('<span style="visibility: hidden">'.Mage::helper('magicscroll')->__('Supported blocks:').'</span>');

    }

    protected function _beforeToHtml()
    {

        $blocks = Mage::helper('magicscroll/params')->getProfiles();
        $activeTab = $this->getRequest()->getParam('tab', 'product');

        foreach ($blocks as $id => $label) {
            $this->addTab($id, array(
                'label'     => Mage::helper('magicscroll')->__($label),
                'title'     => Mage::helper('magicscroll')->__($label.' settings'),
                'content'   => $this->getLayout()->createBlock('magicscroll/adminhtml_settings_edit_tab_form', 'magicscroll_'.$id.'_settings_block')->toHtml(),
                'active'    => ($id == $activeTab) ? true : false
            ));
        }

        return parent::_beforeToHtml();
    }
}
