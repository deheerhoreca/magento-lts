<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
$this->startSetup();

/**
 * @Migration field_exist:amshopby/value|featured_order:1
 */
$tableName = $this->getTable('amshopby/value');
if (!$this->getConnection()->tableColumnExists($tableName, 'featured_order')) {
    $this->run("
        ALTER TABLE `{$tableName}` 
        ADD COLUMN `featured_order` TINYINT UNSIGNED DEFAULT 0
    ");
}

$this->endSetup();