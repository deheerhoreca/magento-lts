<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
$this->startSetup();

/**
 * @Migration field_exist:amshopby/page|cats:1
 */
$tableName = $this->getTable('amshopby/page');
if (!$this->getConnection()->tableColumnExists($tableName, 'cats')) {
    $this->run("
        ALTER TABLE `{$tableName}` ADD COLUMN `cats` TEXT NOT NULL;
    ");
}

$this->endSetup();