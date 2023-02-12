<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Magento 1 Base Package
*/

class Amasty_Base_Model_Resource_Inbox_Collection extends Mage_AdminNotification_Model_Mysql4_Inbox_Collection
{
    /**
     * @param \SimpleXMLElement $item
     * @return bool
     */
    public function execute(\SimpleXMLElement $item)
    {
        $this->addFieldToFilter('url', (string)$item->link);

        return $this->getSize() > 0;
    }

    /**
     * @param \SimpleXMLElement $data
     * @return string
     */
    protected function convertString(\SimpleXMLElement $data)
    {
        $data = htmlspecialchars((string)$data);
        return $data;
    }
}
