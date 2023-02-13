<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
*/
$this->startSetup();

/**
 * @Migration table_exist:amshopby/range:1
 */
$this->run("

CREATE TABLE IF NOT EXISTS `{$this->getTable('amshopby/range')}` (
  `range_id` mediumint(8) unsigned NOT NULL auto_increment,
  `price_frm` int  unsigned NOT NULL,
  `price_to`  int  unsigned NOT NULL,
  PRIMARY KEY  (`range_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 

");

$this->endSetup();