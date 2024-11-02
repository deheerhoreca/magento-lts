<?php

class Profitmetrics_MagentoIntegration_Model_Resource_Tracking extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('profitmetrics/tracking', 'entity_id');
    }
}
