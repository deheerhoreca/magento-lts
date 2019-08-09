<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
$this->startSetup();

/**
 * @Migration field_exist:amshopby/value|img_small_hover:1
 */
$tableName = $this->getTable('amshopby/value');
if (!$this->getConnection()->tableColumnExists($tableName, 'img_small_hover')) {
    $this->run("
        ALTER TABLE `{$tableName}` 
        ADD COLUMN `img_small_hover` VARCHAR(255) NOT NULL
    ");
}

$this->endSetup();