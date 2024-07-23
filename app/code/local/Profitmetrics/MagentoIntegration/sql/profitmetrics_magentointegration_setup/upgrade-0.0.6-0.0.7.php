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
            'profitmetrics_visitor_id',
            array(
                'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
                'nullable'  => false,
                'comment' => 'Profitmetrics Visitor ID',
            )
        );
    $installer->getConnection()->addColumn(
            $installer->getTable('sales_flat_order'),
            'profitmetrics_sent_date',
            array(
                'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
                'nullable'  => true,
                'comment' => 'Time of order data sending to profitmetrics',
            )
        );
} catch (Exception $exception) {
    Mage::log($exception->getMessage());
    throw $exception;
}

$installer->endSetup();
