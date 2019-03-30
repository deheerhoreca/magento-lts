<?php
/**
 * @var Mage_Core_Model_Resource_Setup $this
 *
 * Replace the old fee_amount config value for the new one.
 */
$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection('core_write');

$logName = 'shopworks_billink_upgrade';
$oldFeeAmountPath = 'payment/billink/fee_amount';
$newFeeRangePath = 'payment/billink/fee_ranges';

Mage::log('Starting upgrade to 1.0.2', null, $logName);

$coreConfigTableName = $this->getTable('core_config_data');

//Find all fields with the old config value
$findFeeAmountConfigRowsQuery = 'SELECT * FROM '.$coreConfigTableName.' WHERE path = "'.$oldFeeAmountPath.'"';
$configRowsToSave = array();

foreach ($connection->fetchAll($findFeeAmountConfigRowsQuery) as $row)
{
    Mage::log('convert fee amount: ' . $row['value'] . ' for scope: ' . $row['scope'] . ' with id: ' . $row['scope_id'], null, $logName);

    $data = array(
        array(
            'from'=>'0',
            'until'=>'100000',
            'fee' => $row['value']
        )
    );

    $row['value'] = serialize($data);
    $configRowsToSave[] = $row;
}

//Now update the fields to the new config value, and change the path value
foreach($configRowsToSave as $row)
{
    $value = $connection->quote($row['value']);
    $sql = 'UPDATE '.$coreConfigTableName.' SET path="'.$newFeeRangePath.'", value='.$value.' WHERE config_id = ' . $row['config_id'] . ';';
    $connection->query($sql);
}

$installer->endSetup();
