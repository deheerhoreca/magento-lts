<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Improved Layered Navigation
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