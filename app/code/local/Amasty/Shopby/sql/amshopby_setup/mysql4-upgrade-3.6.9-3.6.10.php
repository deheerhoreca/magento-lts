<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
*/

$this->startSetup();

$this->run("
 ALTER TABLE  `{$this->getTable('amshopby/value')}`
   ADD FOREIGN KEY (`filter_id`) REFERENCES `{$this->getTable('amshopby/filter')}` (`filter_id`) ON DELETE CASCADE ON UPDATE CASCADE;
 ");

$this->endSetup();
