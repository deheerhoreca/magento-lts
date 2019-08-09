<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
$this->startSetup();

/**
 * @Migration field_exist:amshopby/filter|depend_on_attribute:1
 */
$tableName = $this->getTable('amshopby/filter');
if (!$this->getConnection()->tableColumnExists($tableName, 'depend_on_attribute')) {
    $this->run("
        ALTER TABLE `{$tableName}` ADD COLUMN `depend_on_attribute` VARCHAR(256) NOT NULL;
    ");
}

$this->endSetup();