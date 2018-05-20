<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Feed
 */
class Amasty_Feed_Model_Mysql4_Profile_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('amfeed/profile');
    }

    /**
     * @param $filename
     * @return $this
     */
    public function addFilenameFilter($filename)
    {
        $this->addFieldToFilter('filename', array('eq' => $filename));

        return $this;
    }

    /**
     * @return $this
     */
    public function addNoEmptyFilter()
    {
        $this->addFieldToFilter('info_cnt', array('gt' => 0));

        return $this;
    }
}