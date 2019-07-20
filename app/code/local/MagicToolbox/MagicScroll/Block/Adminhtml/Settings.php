<?php

class MagicToolbox_MagicScroll_Block_Adminhtml_Settings extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {

        $this->_blockGroup = 'magicscroll';//module name
        $this->_controller = 'adminhtml_settings';//the path to your block class
        $this->_headerText = Mage::helper('magicscroll')->__('Magic Scroll&#8482; settings');
        parent::__construct();
        $this->setTemplate('magicscroll/settings.phtml');

    }

    protected function _prepareLayout()
    {

        $this->setChild('settings_grid', $this->getLayout()->createBlock('magicscroll/adminhtml_settings_grid', 'magicscroll.grid'));
        $this->setChild('custom_design_settings_form', $this->getLayout()->createBlock('magicscroll/adminhtml_settings_form', 'magicscroll.form'));
        return parent::_prepareLayout();

    }

    public function getAddCustomSettingsFormHtml()
    {

        $html = $this->getChildHtml('custom_design_settings_form');
        if (Mage::registry('magicscroll_custom_design_settings_form')) {
            return $html;
        } else {
            return '';
        }

    }

    public function getSettingsGridHtml()
    {

        return $this->getChildHtml('settings_grid');

    }

}
