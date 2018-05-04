<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


class Amasty_Shopby_Model_Source_BrandCategories_DisplayMode extends Varien_Object
{
    const DISABLED = '0';
    const ENABLED = '1';
    const ENABLED_ALL = '2';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $hlp = Mage::helper('amshopby');
        return array(
            array('value' => self::DISABLED, 'label' => $hlp->__('No')),
            array('value' => self::ENABLED, 'label' => $hlp->__('Show only top level categories')),
            array('value' => self::ENABLED_ALL, 'label' => $hlp->__('Show all categories'))
        );
    }
}
