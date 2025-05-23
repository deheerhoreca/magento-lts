<?php

/**
 * @copyright  For copyright and license information, read the COPYING.txt file.
 * @link       /COPYING.txt
 * @license    Open Software License (OSL 3.0)
 * @package    Mage_Persistent
 */

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

/**
 * Create table 'persistent/session'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('persistent/session'))
    ->addColumn('persistent_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'identity' => true,
        'primary'  => true,
        'nullable' => false,
        'unsigned' => true,
    ], 'Session id')
    ->addColumn('key', Varien_Db_Ddl_Table::TYPE_TEXT, Mage_Persistent_Model_Session::KEY_LENGTH, [
        'nullable' => false,
    ], 'Unique cookie key')
    ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
        'unsigned'  => true,
    ], 'Customer id')
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ], 'Website ID')
    ->addColumn('info', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', [], 'Session Data')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, [], 'Updated At')
    ->addIndex($installer->getIdxName('persistent/session', ['key']), ['key'], [
        'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE,
    ])
    ->addIndex($installer->getIdxName('persistent/session', ['customer_id']), ['customer_id'], [
        'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE,
    ])
    ->addIndex($installer->getIdxName('persistent/session', ['updated_at']), ['updated_at'])
    ->addForeignKey(
        $installer->getFkName('persistent/session', 'customer_id', 'customer/entity', 'entity_id'),
        'customer_id',
        $installer->getTable('customer/entity'),
        'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
    )
    ->addForeignKey(
        $installer->getFkName('persistent/session', 'website_id', 'core/website', 'website_id'),
        'website_id',
        $installer->getTable('core/website'),
        'website_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
    )
    ->setComment('Persistent Session');

$installer->getConnection()->createTable($table);

/**
 * Alter sales_flat_quote table with is_persistent flag
 *
 */
$installer->getConnection()
    ->addColumn(
        $installer->getTable('sales/quote'),
        'is_persistent',
        [
            'type'     => Varien_Db_Ddl_Table::TYPE_SMALLINT,
            'unsigned' => true,
            'default'  => '0',
            'comment'  => 'Is Quote Persistent',
        ],
    );

$installer->endSetup();
