<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
$this->startSetup();

/**
 * @Migration field_exist:amshopby/value|cms_block:1
 */
$tableName = $this->getTable('amshopby/value');
if (!$this->getConnection()->tableColumnExists($tableName, 'cms_block')) {
    $this->run(
        "ALTER TABLE `{$tableName}` ADD `cms_block` VARCHAR(255);"
    );
}

$this->endSetup();