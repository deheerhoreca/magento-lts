<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


$this->startSetup();

$this->run("ALTER TABLE `{$this->getTable('amshopby/value')}` ADD `is_parent` TINYINT(1) NOT NULL DEFAULT '0'");
$this->run("ALTER TABLE `{$this->getTable('amshopby/filter')}` ADD `use_mapping` TINYINT(1) NOT NULL DEFAULT '0'");

$this->run("
ALTER TABLE `{$this->getTable('amshopby/filter')}` CHANGE `depend_on` `depend_on` TEXT NOT NULL;
ALTER TABLE `{$this->getTable('amshopby/filter')}` CHANGE `depend_on_attribute` `depend_on_attribute` TEXT NOT NULL;
ALTER TABLE `{$this->getTable('amshopby/filter')}` CHANGE `exclude_from` `exclude_from` TEXT NOT NULL;
ALTER TABLE `{$this->getTable('amshopby/filter')}` CHANGE `include_in` `include_in` TEXT NOT NULL;
");

$this->run("
ALTER TABLE `{$this->getTable('amshopby/value')}` CHANGE `option_id` `option_id` INT(10) UNSIGNED NOT NULL;
ALTER TABLE `{$this->getTable('amshopby/value')}` CHANGE `value_id` `value_id` INT(10) UNSIGNED NOT NULL auto_increment;
");

$this->run("
 CREATE TABLE IF NOT EXISTS `{$this->getTable('amshopby/value_link')}` (
   `link_id`    mediumint(8) unsigned NOT NULL auto_increment,
   `parent_id`  int(10) unsigned NOT NULL,
   `child_id`   int(10) unsigned NOT NULL,
   `option_id`   int(10) unsigned NOT NULL,
   PRIMARY KEY  (`link_id`)  
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 ");
$this->run("
 ALTER TABLE  `{$this->getTable('amshopby/value_link')}`
   ADD FOREIGN KEY (`parent_id`) REFERENCES `{$this->getTable('amshopby/value')}` (`value_id`) ON DELETE CASCADE ON UPDATE CASCADE,
   ADD FOREIGN KEY (`child_id`) REFERENCES `{$this->getTable('amshopby/value')}` (`value_id`) ON DELETE CASCADE ON UPDATE CASCADE;
 ");

$this->endSetup();
