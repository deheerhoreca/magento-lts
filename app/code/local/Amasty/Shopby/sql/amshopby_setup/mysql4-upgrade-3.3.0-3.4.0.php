<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


$this->startSetup();

$table = $this->getTable('amshopby/page');
$this->run("ALTER TABLE `{$table}` ADD `custom_layout_update_xml` TEXT AFTER `description`");

$this->endSetup();

