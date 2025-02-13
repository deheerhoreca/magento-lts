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
            $installer->getTable('profitmetrics/tracking'),
            'cc_statistics',
            array(
                'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
                'nullable'  => false,
                'default' => 0,
                'comment' => 'cc_statistics',
            )
        );
    $installer->getConnection()
        ->addColumn(
            $installer->getTable('profitmetrics/tracking'),
            'cc_marketing',
            array(
                'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
                'nullable'  => false,
                'default' => 0,
                'comment' => 'cc_marketing',
            )
        );

} catch (Exception $exception) {
    Mage::log($exception->getMessage());
    throw $exception;
}

$installer->endSetup();
