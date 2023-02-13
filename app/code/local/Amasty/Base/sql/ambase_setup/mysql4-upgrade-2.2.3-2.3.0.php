<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Magento 1 Base Package
*/

$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('adminnotification/inbox'),
    'is_amasty',
    'tinyint(1) unsigned default 0'
);

$installer->getConnection()->addColumn(
    $installer->getTable('adminnotification/inbox'),
    'expiration_date',
    'datetime default NULL'
);

$installer->endSetup();
