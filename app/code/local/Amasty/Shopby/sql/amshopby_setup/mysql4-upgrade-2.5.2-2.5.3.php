<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
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