<?php
/** @var Mage_Core_Model_Resource_Setup $this */
/**
 * Migrate deals data
 */
$installer = $this;

$installer->startSetup();
/** @var Varien_Db_Adapter_Interface $connection */
$connection = $this->getConnection();

try {

    $table = $installer->getConnection()
        ->newTable($installer->getTable('profitmetrics/tracking'))
        ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
            'identity'  => true,
            'nullable'  => false,
            'primary'   => true,
            ), 'Visitor ID')
        ->addColumn('gacid', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
            'nullable'  => true,
            ), 'Google Analytics Customer ID')
        ->addColumn('gacid_source', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
            'nullable'  => true,
        ), 'Source for Google Analytics Customer ID')
        ->addColumn('gclid', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
            'nullable'  => true,
        ), 'Google Click ID')
        ->addColumn('fbc', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
            'nullable'  => true,
        ), 'Facebook browser ID')
        ->addColumn('fbp', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
            'nullable'  => true,
        ), 'Facebook browser ID')
        ->addColumn('fbc', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
            'nullable'  => true,
        ), 'Facebook Click ID')
        ->addColumn('cua', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
            'nullable'  => true,
        ), 'Customers user agent')
        ->addColumn('cip', Varien_Db_Ddl_Table::TYPE_VARCHAR, 20, array(
            'nullable'  => true,
        ), 'Customer IP')
        ->addColumn('t', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
            'nullable'  => true,
        ), 'Customer IP')
        ->addColumn('timestamp', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
            'nullable'  => true,
        ), 'Frontend timestamp')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable'  => false,
        ), 'Created At')
        ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable'  => false,
        ), 'Updated At')
        ->setComment('ProfitMetrics Visitors');
    $installer->getConnection()->createTable($table);
} catch (Exception $exception) {
    Mage::log($exception->getMessage());
    throw $exception;
}

$installer->endSetup();
