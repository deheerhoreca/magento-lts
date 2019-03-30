<?php
/**
 * @var Mage_Core_Model_Resource_Setup $this
 */
$installer = $this;
$installer->startSetup();
$connection = $installer->getConnection();

$fieldsToAdd = array(
    //entity    =>  fields to add
    'sales/order' => array(
    
        'billink_fee',
        'base_billink_fee',
        
        'billink_fee_incl_tax',
        'base_billink_fee_incl_tax',
        
        'billink_fee_tax',
        'base_billink_fee_tax',
        
    ),
    'sales/invoice' => array(
    
        'billink_fee',
        'base_billink_fee',
        
        'billink_fee_incl_tax',
        'base_billink_fee_incl_tax',
        
        'billink_fee_tax',
        'base_billink_fee_tax',
    ),
    'sales/quote' => array(
    
        'billink_fee',
        'base_billink_fee',
        
        'billink_fee_incl_tax',
        'base_billink_fee_incl_tax',
        
        'billink_fee_tax',
        'base_billink_fee_tax',
    ),
    'sales/quote_address' => array(
    
        'billink_fee',
        'base_billink_fee',
        
        'billink_fee_incl_tax',
        'base_billink_fee_incl_tax',
        
        'billink_fee_tax',
        'base_billink_fee_tax',
    )
);

/**
 * Add fee columns to entities
 */
foreach($fieldsToAdd as $entity => $fields)
{
    $tableName = $installer->getTable($entity);

    foreach($fields as $field)
    {
        //The column 'billink_fee' can already be created by the old plugin (the one from another company)
        if(!$connection->tableColumnExists($tableName, $field))
        {
            $connection->addColumn(
                $tableName,
                $field,
                "decimal(12,4) null"
            );
        }
        //If the column already exists, than lets make sure it has the propper type
        else
        {
            $installer->run('alter table ' .$tableName. ' modify '.$field.' decimal(12,4)');
        }
    }
}

$installer->endSetup();
