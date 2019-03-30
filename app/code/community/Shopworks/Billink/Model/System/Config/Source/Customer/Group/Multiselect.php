<?php

/**
 * Original class: Mage_Adminhtml_Model_System_Config_Source_Customer_Group_Multiselect
 */

/**
 * Class Shopworks_Billink_Model_System_Config_Source_Customer_Group_Multiselect
 */
class Shopworks_Billink_Model_System_Config_Source_Customer_Group_Multiselect
{
    /**
     * Customer groups options array
     *
     * @var null|array
     */
    protected $_options;

    /**
     * Retrieve customer groups as array
     * OVERWRITTEN: removed setRealGroupsFilter()
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = Mage::getResourceModel('customer/group_collection')
                ->loadData()->toOptionArray();
        }
        return $this->_options;
    }
}
