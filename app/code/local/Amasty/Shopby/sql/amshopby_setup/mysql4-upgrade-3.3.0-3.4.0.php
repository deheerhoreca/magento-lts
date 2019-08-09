<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


$this->startSetup();

$table = $this->getTable('amshopby/page');
if (!$this->getConnection()->tableColumnExists($table, 'custom_layout_update_xml')) {
    $this->run("ALTER TABLE `{$table}` ADD `custom_layout_update_xml` TEXT AFTER `description`");
}

$this->endSetup();

