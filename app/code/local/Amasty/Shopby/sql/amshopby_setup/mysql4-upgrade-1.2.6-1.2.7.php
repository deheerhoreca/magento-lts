<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
*/
$this->startSetup();

/**
 * @Migration field_exist:amshopby/filter|exclude_from:1
 */
$tableName = $this->getTable('amshopby/filter');
if (!$this->getConnection()->tableColumnExists($tableName, 'exclude_from')) {
    $this->run(
        "ALTER TABLE `{$tableName}` ADD `exclude_from` VARCHAR(4096) NOT NULL;"
    );
}

$this->endSetup();