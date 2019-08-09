<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
$this->startSetup();

/**
 * @Migration field_exist:amshopby/filter|include_in:1
 */
$tableName = $this->getTable('amshopby/filter');
if (!$this->getConnection()->tableColumnExists($tableName, 'include_in')) {
    $this->run("
        ALTER TABLE `{$tableName}` ADD COLUMN `include_in` VARCHAR(256) NOT NULL;
    ");
}

$this->endSetup();