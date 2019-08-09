<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
$this->startSetup();

/**
 * @Migration field_exist:amshopby/filter|comment:1
 * @Migration field_exist:amshopby/filter|block_pos:1
 */
$tableName = $this->getTable('amshopby/filter');
if (!$this->getConnection()->tableColumnExists($tableName, 'comment')) {
    $this->run("
      ALTER TABLE `{$tableName}` ADD `comment` TEXT NOT NULL;
      ALTER TABLE `{$tableName}` ADD `block_pos` VARCHAR(255) NOT NULL;
    ");
}

$this->endSetup();