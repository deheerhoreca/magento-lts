<?php

/**
 * @copyright  For copyright and license information, read the COPYING.txt file.
 * @link       /COPYING.txt
 * @license    Open Software License (OSL 3.0)
 * @package    Mage_CatalogInventory
 */

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

/** @var Varien_Db_Adapter_Pdo_Mysql $connection */
$connection = $installer->getConnection();

$installer->run("
    CREATE TABLE `{$installer->getTable('cataloginventory_stock_status')}` (
      `product_id` int(10) unsigned NOT NULL,
      `website_id` smallint(5) unsigned NOT NULL,
      `stock_id` smallint(4) unsigned NOT NULL,
      `qty` decimal(12,4) NOT NULL DEFAULT '0.0000',
      `stock_status` tinyint(3) unsigned NOT NULL,
      PRIMARY KEY (`product_id`,`website_id`,`stock_id`),
      CONSTRAINT `FK_CATALOGINVENTORY_STOCK_STATUS_STOCK` FOREIGN KEY (`stock_id`)
        REFERENCES `{$installer->getTable('cataloginventory_stock')}` (`stock_id`) ON DELETE CASCADE ON UPDATE CASCADE,
      CONSTRAINT `FK_CATALOGINVENTORY_STOCK_STATUS_PRODUCT` FOREIGN KEY (`product_id`)
        REFERENCES `{$installer->getTable('catalog_product_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
      CONSTRAINT `FK_CATALOGINVENTORY_STOCK_STATUS_WEBSITE` FOREIGN KEY (`website_id`)
        REFERENCES `{$installer->getTable('core_website')}` (`website_id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

Mage::getModel('cataloginventory/stock_status')->rebuild();
$installer->endSetup();
