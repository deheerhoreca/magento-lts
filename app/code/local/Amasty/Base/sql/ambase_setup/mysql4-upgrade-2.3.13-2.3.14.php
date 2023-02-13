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
    'image_url',
    'varchar(255) default NULL'
);

$installer->endSetup();
