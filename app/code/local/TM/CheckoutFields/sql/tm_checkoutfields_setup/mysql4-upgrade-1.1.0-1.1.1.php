<?php

/**
 * @var Mage_Core_Model_Resource_Setup
 */
$installer = $this;

$installer->startSetup();

$installer->run("

    ALTER TABLE {$this->getTable('sales_flat_order')}
        ADD COLUMN `tm_field1` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field2` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field3` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field4` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field5` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field6` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field7` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field8` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field9` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field10` TEXT DEFAULT NULL
        ADD COLUMN `tm_field11` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field12` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field13` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field14` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field15` TEXT DEFAULT NULL;

");

$installer->endSetup();
