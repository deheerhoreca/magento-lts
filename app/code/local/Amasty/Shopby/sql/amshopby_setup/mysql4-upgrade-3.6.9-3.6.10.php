<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


$this->startSetup();

$this->run("
 ALTER TABLE  `{$this->getTable('amshopby/value')}`
   ADD FOREIGN KEY (`filter_id`) REFERENCES `{$this->getTable('amshopby/filter')}` (`filter_id`) ON DELETE CASCADE ON UPDATE CASCADE;
 ");

$this->endSetup();
