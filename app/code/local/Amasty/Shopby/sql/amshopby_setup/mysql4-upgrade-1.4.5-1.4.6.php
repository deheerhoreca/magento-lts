<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
$this->startSetup();

/**
 * @Migration field_exist:amshopby/filter|seo_noindex:1
 * @Migration field_exist:amshopby/filter|seo_nofollow:1
 * @Migration field_exist:amshopby/filter|seo_rel:1
 */
$tableName = $this->getTable('amshopby/filter');
if (!$this->getConnection()->tableColumnExists($tableName, 'seo_noindex')) {
    $this->run("
        ALTER TABLE `{$tableName}` ADD `seo_noindex`  TINYINT(1) NOT NULL;
        ALTER TABLE `{$tableName}` ADD `seo_nofollow` TINYINT(1) NOT NULL;
        ALTER TABLE `{$tableName}` ADD `seo_rel`      TINYINT(1) NOT NULL;
    ");
}

$this->endSetup();