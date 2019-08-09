<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
$this->startSetup();

/**
 * @Migration field_exist:amshopby/value|url_alias:1
 */
$tableName = $this->getTable('amshopby/value');
if (!$this->getConnection()->tableColumnExists($tableName, 'url_alias')) {
    $this->run("
        ALTER TABLE `{$tableName}` ADD  `url_alias` VARCHAR( 255 ) NULL DEFAULT NULL ,
        ADD INDEX (  `url_alias` )
    ");
}
 
$this->endSetup();