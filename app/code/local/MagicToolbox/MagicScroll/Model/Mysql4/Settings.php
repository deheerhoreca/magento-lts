<?php

class MagicToolbox_MagicScroll_Model_Mysql4_Settings extends Mage_Core_Model_Mysql4_Abstract
{

    public function _construct()
    {

        $this->_init('magicscroll/settings', 'setting_id');

    }

}
