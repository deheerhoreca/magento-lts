<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
$this->startSetup();

/**
 * @Migration field_exist:amshopby/filter|use_and_logic:1
 */
$tableName = $this->getTable('amshopby/filter');
if (!$this->getConnection()->tableColumnExists($tableName, 'use_and_logic')) {
    $this->run("
        ALTER TABLE `{$tableName}` ADD `use_and_logic` TINYINT(1) NOT NULL DEFAULT 0;
    ");
}

$this->endSetup();
