<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
$this->startSetup();

/**
 * @Migration field_exist:amshopby/filter|show_search:1
 */
$tableName = $this->getTable('amshopby/filter');
if (!$this->getConnection()->tableColumnExists($tableName, 'show_search')) {
    $this->run("
        ALTER TABLE `{$tableName}` ADD `show_search` TINYINT( 1 ) NOT NULL ,
        ADD `slider_decimal` TINYINT( 1 ) NOT NULL ;
    ");
}
 
$this->endSetup();