<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
*/
$this->startSetup();

/**
 * @Migration field_exist:amshopby/filter|range:1
 */
$tableName = $this->getTable('amshopby/filter');
if (!$this->getConnection()->tableColumnExists($tableName, 'range')) {
    $this->run("
      ALTER TABLE `{$tableName}` ADD COLUMN `range` int NOT NULL;
    ");
}

$this->endSetup();