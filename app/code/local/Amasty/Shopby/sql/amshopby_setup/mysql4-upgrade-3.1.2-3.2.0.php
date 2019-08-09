<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


$this->startSetup();

$table = $this->getTable('amshopby/filter');
if (!$this->getConnection()->tableColumnExists($table, 'show_child_filter')) {
    $this->run("ALTER TABLE `{$table}` ADD `show_child_filter` TINYINT(1) NOT NULL DEFAULT '0'");
}
if (!$this->getConnection()->tableColumnExists($table, 'child_filter_name')) {
    $this->run("ALTER TABLE `{$table}` ADD `child_filter_name` TEXT DEFAULT NULL");
}

$this->endSetup();
