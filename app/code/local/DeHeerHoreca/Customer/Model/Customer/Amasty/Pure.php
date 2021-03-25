<?php /* added automatically by conflict fixing tool */ if (Mage::getConfig()->getNode('modules/Amasty_Emailfilter/active')) {
            class DeHeerHoreca_Customer_Model_Customer_Amasty_Pure extends Amasty_Emailfilter_Model_Customer {}
        } else { class DeHeerHoreca_Customer_Model_Customer_Amasty_Pure extends Mage_Customer_Model_Customer {} } ?>