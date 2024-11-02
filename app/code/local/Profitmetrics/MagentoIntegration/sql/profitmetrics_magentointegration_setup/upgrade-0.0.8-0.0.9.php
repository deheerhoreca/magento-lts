<?php
/** @var Mage_Core_Model_Resource_Setup $this */

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();
/** @var Varien_Db_Adapter_Interface $connection */
$connection = $this->getConnection();

try {
    $installer->getConnection()
        ->changeColumn(
            $installer->getTable('profitmetrics/tracking'),
            'cip',
            'cip',
            array(
                'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
                'length' => 46,
                'nullable'  => true,
                'comment' => 'Customer IP',
            )
        );
} catch (Exception $exception) {
    Mage::log($exception->getMessage());
    throw $exception;
}

$installer->endSetup();
