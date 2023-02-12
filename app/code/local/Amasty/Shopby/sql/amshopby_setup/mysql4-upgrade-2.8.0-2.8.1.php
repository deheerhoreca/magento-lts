<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
*/
$this->startSetup();

$table = $this->getTable('amshopby/filter');
$this->run("ALTER TABLE {$table} CHANGE `block_pos` `block_pos` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'left'");

$this->endSetup();


