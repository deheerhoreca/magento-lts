<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
*/

/**
 * Ignore data replacement options on certain conditions.
 *
 * Class Amasty_Shopby_Model_Creareseo_Observer
 */
class Amasty_Shopby_Model_Creareseo_Observer extends Creare_CreareSeoCore_Model_Observer
{
    public function setTitle($observer)
    {
        if (Mage::registry(Amasty_Shopby_Block_Top::METADATA_PROCESSED)
            && Mage::getStoreConfig('creareseocore/amasty_shopby/improved_title')) {
            return $this;
        }
        return parent::setTitle($observer);
    }

    public function setDescription($observer)
    {
        if (Mage::registry(Amasty_Shopby_Block_Top::METADATA_PROCESSED)
            && Mage::getStoreConfig('creareseocore/amasty_shopby/improved_meta')) {
            return $this;
        }
        return parent::setDescription($observer);
    }
}