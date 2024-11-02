<?php
/** @var Mage_Core_Model_Resource_Setup $this */

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();
/** @var Varien_Db_Adapter_Interface $connection */
$connection = $this->getConnection();

try {
    $connection->addColumn(
        $installer->getTable('profitmetrics/tracking'),
        'gbraid',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length' => 255,
            'nullable'  => true,
            'comment' => 'GBRAID App tracking ID',
        )
    );
    $connection->addColumn(
        $installer->getTable('profitmetrics/tracking'),
        'wbraid',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length' => 255,
            'nullable'  => true,
            'comment' => 'WBRAID Web tracking ID',
        )
    );
    $connection->addColumn(
        $installer->getTable('profitmetrics/tracking'),
        'ga_session_id',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length' => 255,
            'nullable'  => true,
            'comment' => 'Ga4 Session ID',
        )
    );
    $connection->addColumn(
        $installer->getTable('profitmetrics/tracking'),
        'ga_session_count',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length' => 255,
            'nullable'  => true,
            'comment' => 'Ga4 Session Count',
        )
    );
    $connection->addColumn(
        $installer->getTable('profitmetrics/tracking'),
        'landing_page',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length' => 2000,
            'nullable'  => true,
            'comment' => 'Landing Page URL truncated',
        )
    );
    $connection->addColumn(
        $installer->getTable('profitmetrics/tracking'),
        'landing_page_length',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'nullable'  => true,
            'comment' => 'Landing Page URL Length',
        )
    );
} catch (Exception $exception) {
    Mage::log($exception->getMessage());
    throw $exception;
}

$installer->endSetup();
