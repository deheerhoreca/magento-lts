<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
*/
$this->startSetup();

/**
 * @Migration field_exist:amshopby/filter|depend_on:1
 */
$tableName = $this->getTable('amshopby/filter');
if (!$this->getConnection()->tableColumnExists($tableName, 'depend_on')) {
    $this->run(
        "ALTER TABLE `{$tableName}` ADD `depend_on`  VARCHAR(255) NOT NULL;"
    );
}

$this->endSetup();