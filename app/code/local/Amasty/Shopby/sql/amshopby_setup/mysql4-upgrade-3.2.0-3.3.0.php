<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
*/

$this->startSetup();

$table = $this->getTable('amshopby/value');
if (!$this->getConnection()->tableColumnExists($table, 'mapped_position')) {
    $this->run("ALTER TABLE `{$table}` ADD `mapped_position` int(10) NOT NULL DEFAULT '0'");
}

$this->endSetup();
