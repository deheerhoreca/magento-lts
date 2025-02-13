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
        ->addColumn('cc_statistics', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
            'unsigned'  => true,
        ), 'CC_statistics')
        ->addColumn('cc_marketing', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
            'unsigned'  => true,
        ), 'CC_marketing');

} catch (Exception $exception) {
    Mage::log($exception->getMessage());
    throw $exception;
}

$installer->endSetup();
