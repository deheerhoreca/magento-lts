<?php

class Profitmetrics_MagentoIntegration_Model_Tracking extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('profitmetrics/tracking');
    }

    public function _beforeSave()
    {
        if($this->isObjectNew()){
            $this->setCreatedAt(Mage::getModel('core/date')->timestamp());
        }
        $this->setUpdatedAt(Mage::getModel('core/date')->timestamp());
        return parent::_beforeSave();
    }
}
