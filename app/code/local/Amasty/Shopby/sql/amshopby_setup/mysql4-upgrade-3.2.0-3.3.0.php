<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


$this->startSetup();

$this->run("ALTER TABLE `{$this->getTable('amshopby/value')}` ADD `mapped_position` int(10) NOT NULL DEFAULT '0'");

$this->endSetup();
