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
    $installer->getConnection()
        ->addColumn(
            $installer->getTable('sales_flat_order'),
            'profitmetrics_failure_date',
            array(
                'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
                'nullable'  => true,
                'comment' => 'Time of order data sent attempt to ProfitMetrics has failed',
            )
        );
    $installer->getConnection()->addColumn(
            $installer->getTable('sales_flat_order'),
            'profitmetrics_failure_count',
            array(
                'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
                'nullable'  => true,
                'comment' => 'Count of failed attempts to send to ProfitMetrics',
            )
        );
} catch (Exception $exception) {
    Mage::log($exception->getMessage());
    throw $exception;
}

$installer->endSetup();
