<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
*/
$this->startSetup();

/**
 * @Migration field_exist:amshopby/filter|single_choice:1
 * @Migration field_exist:amshopby/filter|collapsed:1
 */
$tableName = $this->getTable('amshopby/filter');
if (!$this->getConnection()->tableColumnExists($tableName, 'single_choice')) {
    $this->run("
      ALTER TABLE `{$tableName}` ADD `single_choice` TINYINT(1) NOT NULL;
      ALTER TABLE `{$tableName}` ADD `collapsed` TINYINT(1) NOT NULL;
    ");
}

$this->endSetup();