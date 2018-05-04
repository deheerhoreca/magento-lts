<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


$this->startSetup();

$this->run("ALTER TABLE `{$this->getTable('amshopby/filter')}` ADD `show_child_filter` TINYINT(1) NOT NULL DEFAULT '0'");
$this->run("ALTER TABLE `{$this->getTable('amshopby/filter')}` ADD `child_filter_name` TEXT DEFAULT NULL");

$this->endSetup();
