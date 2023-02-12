<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
*/

class Amasty_Shopby_Model_Source_BrandCategories_Sortby extends Varien_Object
{
    const POSITION = 'position';
    const NAME = 'name';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $hlp = Mage::helper('amshopby');
        return array(
            array('value' => self::POSITION, 'label' => $hlp->__('Position')),
            array('value' => self::NAME, 'label' => $hlp->__('Name'))
        );
    }
}
