<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


$this->startSetup();

$blockList = array('amshopby/list', 'amshopby/featured', 'amshopby/subcategories');
foreach ($blockList as $blockName) {
    try {
        /** @var Mage_Admin_Model_Block $block */
        $block = Mage::getModel('admin/block');
        if (is_object($block)) {
            //Not sure for the case, but some clients have errors
            $block->load($blockName, 'block_name');
            if (!$block->getId()) {
                $block->setData(array('block_name' => $blockName, 'is_allowed' => 1));
                $block->save();
            }
        }
    } catch (Exception $e) {
        // Magento version before 1.9.2.2: operation not required
    }
}

$this->endSetup();

