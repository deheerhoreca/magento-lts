<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
*/

if (Mage::helper('amshopby')->useSolr()) {
    $autoloader = Varien_Autoload::instance();
    $autoloader->autoload('Amasty_Shopby_Block_Catalog_Layer_View_Enterprise');
} else {
    class Amasty_Shopby_Block_Catalog_Layer_View_Pure extends Mage_Catalog_Block_Layer_View {}
}
