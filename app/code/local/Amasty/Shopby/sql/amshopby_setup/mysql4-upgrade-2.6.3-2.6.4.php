<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
$this->startSetup();

/**
 * @Migration field_exist:amshopby/page|cms_block_id:1
 */
$tableName = $this->getTable('amshopby/page');
if (!$this->getConnection()->tableColumnExists($tableName, 'cms_block_id')) {
    $this->run("
        ALTER TABLE `{$tableName}`
        ADD `cms_block_id` int(11) DEFAULT NULL
    ");
}

if ($this->getConnection()->tableColumnExists($tableName, 'cms_block')) {
    $this->run("
        UPDATE `{$tableName}` v,`{$this->getTable('cms/block')}` b
        SET v.`cms_block_id` = b.`block_id`
        WHERE b.`identifier` = v.`cms_block`
    ");
}

/**
 * @Migration field_exist:amshopby/page|cms_block:0
 */
if ($this->getConnection()->tableColumnExists($tableName, 'cms_block')) {
    $this->run("
        ALTER TABLE `{$tableName}`
        DROP `cms_block`
    ");
}

$this->endSetup();
