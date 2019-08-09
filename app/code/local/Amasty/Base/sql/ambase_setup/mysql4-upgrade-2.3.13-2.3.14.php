<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Base
 */


$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('adminnotification/inbox'),
    'image_url',
    'varchar(255) default NULL'
);

$installer->endSetup();
