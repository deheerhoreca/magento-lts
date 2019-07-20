<?php

class MagicToolbox_MagicScroll_Block_Adminhtml_Settings_Edit_Tab_Form_Renderer_Fieldset extends Mage_Adminhtml_Block_Widget_Form_Renderer_Fieldset
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('magicscroll/fieldset.phtml');
    }

}
