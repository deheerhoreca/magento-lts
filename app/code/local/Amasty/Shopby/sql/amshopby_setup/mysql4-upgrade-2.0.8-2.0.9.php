<?php

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */
$this->startSetup();

/**
 * @Migration field_exist:amshopby/page|meta_kw:1
 * @Migration field_exist:amshopby/value|meta_kw:1
 */
$tablePage = $this->getTable('amshopby/page');
if (!$this->getConnection()->tableColumnExists($tablePage, 'meta_kw')) {
    $this->run("
        ALTER TABLE `{$tablePage}` ADD COLUMN `meta_kw` varchar(255) NOT NULL;
    ");
}

$tableValue = $this->getTable('amshopby/value');
if (!$this->getConnection()->tableColumnExists($tableValue, 'meta_kw')) {
    $this->run("
        ALTER TABLE `{$tableValue}` ADD COLUMN `meta_kw` varchar(255) NOT NULL;
    ");
}

$this->endSetup();